// Парсер отзывов Яндекс.Карт через настоящий браузер (Playwright).
// Запуск: node parse.mjs "<ссылка>" [лимит отзывов]
// Результат: один JSON в файл из PARSER_OUT (или в stdout, если его нет).

import fs from 'node:fs';
import { chromium } from 'playwright';

const inputUrl = process.argv[2];
const limit = process.argv[3] ? Number(process.argv[3]) : Infinity;

// большой JSON через stdout может не успеть уйти до process.exit, поэтому пишем в файл
function out(obj) {
    const json = JSON.stringify(obj);
    if (process.env.PARSER_OUT) {
        fs.writeFileSync(process.env.PARSER_OUT, json);
    } else {
        process.stdout.write(json);
    }
    process.exit(0);
}

if (!inputUrl) out({ ok: false, error: 'no_url' });

// из любой ссылки на карточку делаем ссылку на вкладку отзывов
// (отрезаем /photos/, /menu/, ?ll=... и всё лишнее)
function toReviewsUrl(url) {
    const m = String(url).match(/^(https?:\/\/[^/]+\/maps\/(?:\d+\/[^/]+\/)?org\/(?:[^/?#]+\/)?(\d+))/);
    if (m) return { reviewsUrl: m[1] + '/reviews/', id: m[2] };
    const oid = String(url).match(/[?&]oid=(\d+)/);
    if (oid) return { reviewsUrl: 'https://yandex.ru/maps/org/' + oid[1] + '/reviews/', id: oid[1] };
    return null;
}

let businessId = null;

// приводим один отзыв к нашему виду
function normalize(r) {
    return {
        externalId: r.reviewId,
        author: r.author?.name ?? null,
        rating: r.rating ?? null,
        text: r.text ?? '',
        date: r.updatedTime ?? null,
    };
}

const run = async () => {
    const browser = await chromium.launch({
        headless: true,
        // флаги для Docker; single-process не ставим — он ломает долгую прокрутку
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
        ],
    });
    const context = await browser.newContext({
        locale: 'ru-RU',
        viewport: { width: 1280, height: 900 },
        userAgent:
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    });
    const page = await context.newPage();

    // не грузим картинки/видео/шрифты — сильно экономит память (нам нужен только текст)
    await page.route('**/*', (route) => {
        const type = route.request().resourceType();
        if (type === 'image' || type === 'media' || type === 'font') {
            route.abort();
        } else {
            route.continue();
        }
    });

    const reviews = new Map(); // reviewId -> отзыв (Map сам убирает дубли)
    let total = null;

    // ловим отзывы, которые браузер подгружает при прокрутке
    page.on('response', async (resp) => {
        if (!resp.url().includes('/maps/api/business/fetchReviews')) return;
        try {
            const j = await resp.json();
            const d = j.data;
            if (!d || !Array.isArray(d.reviews)) return;
            if (typeof d.params?.count === 'number') total = d.params.count;
            for (const r of d.reviews) if (r.reviewId) reviews.set(r.reviewId, normalize(r));
        } catch (e) {}
    });

    // короткую ссылку (/maps/-/...) сначала открываем, чтобы узнать полный адрес карточки
    let card = toReviewsUrl(inputUrl);
    if (!card && /\/maps\/-\//.test(inputUrl)) {
        try {
            await page.goto(inputUrl, { waitUntil: 'domcontentloaded', timeout: 45000 });
            await page.waitForTimeout(2000);
        } catch (e) {
            await browser.close();
            out({ ok: false, error: 'page_load_failed' });
        }
        if (/showcaptcha|smartcaptcha/i.test(page.url())) {
            await browser.close();
            out({ ok: false, error: 'captcha' });
        }
        card = toReviewsUrl(page.url());
        if (!card) {
            const canonical = await page.evaluate(() => {
                const el = document.querySelector('link[rel="canonical"], meta[property="og:url"]');
                return el ? (el.href || el.content) : null;
            });
            if (canonical) card = toReviewsUrl(canonical);
        }
    }

    if (!card) {
        await browser.close();
        out({ ok: false, error: 'bad_url' });
    }
    businessId = card.id;

    try {
        await page.goto(card.reviewsUrl, { waitUntil: 'domcontentloaded', timeout: 45000 });
    } catch (e) {
        await browser.close();
        out({ ok: false, error: 'page_load_failed' });
    }

    if (/showcaptcha|smartcaptcha/i.test(page.url())) {
        await browser.close();
        out({ ok: false, error: 'captcha' });
    }

    await page.waitForTimeout(3000);

    // первая страница отзывов лежит прямо в HTML — берём её оттуда,
    // а заодно рейтинг, число оценок и отзывов из микроразметки
    const initial = await page.evaluate(() => {
        const res = { reviews: [], meta: {} };

        // общие цифры
        const agg = document.querySelector('[itemprop="aggregateRating"]');
        const readProp = (el, name) => {
            const n = el?.querySelector(`[itemprop="${name}"]`);
            return n ? n.getAttribute('content') || n.innerText : null;
        };
        res.meta.rating = agg ? Number(readProp(agg, 'ratingValue')) : null;
        res.meta.ratingsCount = agg ? Number(readProp(agg, 'ratingCount')) : null;
        res.meta.reviewsCount = agg ? Number(readProp(agg, 'reviewCount')) : null;

        // название из заголовка вкладки: «...»
        const t = document.title.match(/«([^»]+)»/);
        res.meta.name = t ? t[1] : null;

        // первая страница отзывов из встроенного JSON
        const html = document.documentElement.innerHTML;
        const key = '"reviews":[';
        const start = html.indexOf(key);
        if (start !== -1) {
            let i = start + key.length - 1, depth = 0, inStr = false, esc = false;
            for (; i < html.length; i++) {
                const c = html[i];
                if (inStr) {
                    if (esc) esc = false;
                    else if (c === '\\') esc = true;
                    else if (c === '"') inStr = false;
                } else if (c === '"') inStr = true;
                else if (c === '[') depth++;
                else if (c === ']') { depth--; if (depth === 0) { i++; break; } }
            }
            try { res.reviews = JSON.parse(html.slice(start + key.length - 1, i)); } catch (e) {}
        }
        return res;
    });

    for (const r of initial.reviews) if (r.reviewId) reviews.set(r.reviewId, normalize(r));

    const meta = initial.meta;
    const target = Math.min(limit, total ?? meta.reviewsCount ?? 0);

    // если совсем ничего не нашли — значит парсер сломался (разметка/защита)
    if (reviews.size === 0 && !target) {
        await browser.close();
        out({ ok: false, error: 'reviews_not_found' });
    }

    // крутим вниз, пока не соберём все (или пока новые не кончатся)
    await page.mouse.move(300, 450);
    let stall = 0;
    for (let i = 0; i < 400; i++) {
        if (target && reviews.size >= target) break;
        if (stall >= 12) break;
        const before = reviews.size;

        // скроллим сам контейнер списка (надёжнее колеса мыши)
        await page.evaluate(() => {
            const els = Array.from(document.querySelectorAll('*')).filter((el) => {
                const s = getComputedStyle(el);
                return (s.overflowY === 'auto' || s.overflowY === 'scroll')
                    && el.scrollHeight > el.clientHeight + 200;
            });
            els.sort((a, b) => b.scrollHeight - a.scrollHeight);
            if (els[0]) els[0].scrollTo(0, els[0].scrollHeight);
        });
        await page.mouse.wheel(0, 6000);
        await page.waitForTimeout(1300);

        stall = reviews.size === before ? stall + 1 : 0;
    }

    await browser.close();

    let list = Array.from(reviews.values());
    if (list.length > limit) list = list.slice(0, limit);

    out({
        ok: true,
        businessId,
        name: meta.name,
        rating: meta.rating,
        ratingsCount: meta.ratingsCount,
        reviewsCount: meta.reviewsCount ?? total,
        collected: list.length,
        reviews: list,
    });
};

run().catch((e) => out({ ok: false, error: 'crashed', message: String(e) }));
