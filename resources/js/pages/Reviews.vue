<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { auth, logout } from '../stores/auth';
import api from '../lib/axios';

const router = useRouter();

const org = ref(null);
const reviews = ref([]);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);
const loading = ref(true);
const error = ref('');

onMounted(async () => {
    await loadOrg();
    await loadPage(1);
});

async function loadOrg() {
    try {
        const { data } = await api.get('/api/organization');
        org.value = data.organization;
    } catch {
        error.value = 'Не удалось загрузить организацию.';
    }
}

async function loadPage(p) {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/api/organization/reviews', { params: { page: p } });
        reviews.value = data.data;
        page.value = data.current_page;
        lastPage.value = data.last_page;
        total.value = data.total;
    } catch (e) {
        error.value = e.response?.status === 404
            ? 'Сначала добавьте организацию на странице настроек.'
            : 'Не удалось загрузить отзывы.';
    } finally {
        loading.value = false;
    }
}

// красивая дата: 2026-04-20T... -> 20.04.2026
function fmtDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('ru-RU');
}

async function doLogout() {
    await logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="mx-auto max-w-3xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <router-link :to="{ name: 'settings' }" class="text-sm text-blue-600 hover:underline">
                ← Настройки
            </router-link>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-gray-500">{{ auth.user?.email }}</span>
                <button @click="doLogout" class="rounded border border-gray-300 px-3 py-1 hover:bg-gray-100">
                    Выйти
                </button>
            </div>
        </div>

        <!-- Шапка с рейтингом и счётчиками -->
        <div v-if="org" class="mb-6 rounded-xl bg-white p-6 shadow">
            <h1 class="text-2xl font-semibold">{{ org.name ?? 'Организация' }}</h1>
            <div class="mt-3 flex flex-wrap gap-6 text-sm">
                <div>
                    <div class="text-2xl font-bold text-yellow-500">★ {{ org.rating ?? '—' }}</div>
                    <div class="text-gray-500">средний рейтинг</div>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ org.ratings_count ?? '—' }}</div>
                    <div class="text-gray-500">оценок</div>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ org.reviews_count ?? '—' }}</div>
                    <div class="text-gray-500">отзывов</div>
                </div>
            </div>
        </div>

        <div v-if="error" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-600">{{ error }}</div>

        <div v-if="loading" class="py-10 text-center text-gray-400">Загружаем…</div>

        <!-- Список отзывов -->
        <div v-else class="space-y-3">
            <div v-for="r in reviews" :key="r.id" class="rounded-xl bg-white p-4 shadow">
                <div class="flex items-center justify-between">
                    <div class="font-medium">{{ r.author ?? 'Аноним' }}</div>
                    <div class="text-yellow-500">★ {{ r.rating ?? '—' }}</div>
                </div>
                <div class="text-xs text-gray-400">{{ fmtDate(r.review_date) }}</div>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ r.text }}</p>
            </div>

            <div v-if="reviews.length === 0" class="py-10 text-center text-gray-400">
                Отзывов пока нет.
            </div>
        </div>

        <!-- Переключение страниц -->
        <div v-if="lastPage > 1" class="mt-6 flex items-center justify-center gap-4">
            <button
                @click="loadPage(page - 1)"
                :disabled="page <= 1 || loading"
                class="rounded border border-gray-300 px-3 py-1 disabled:opacity-40"
            >
                ← Назад
            </button>
            <span class="text-sm text-gray-500">Страница {{ page }} из {{ lastPage }}</span>
            <button
                @click="loadPage(page + 1)"
                :disabled="page >= lastPage || loading"
                class="rounded border border-gray-300 px-3 py-1 disabled:opacity-40"
            >
                Вперёд →
            </button>
        </div>
    </div>
</template>
