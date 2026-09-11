import { reactive } from 'vue';
import api from '../lib/axios';

/**
 * Реактивное состояние авторизации.
 *  - user: данные залогиненного пользователя (или null).
 *  - checked: спрашивали ли мы уже бэк "кто я" (чтобы не дёргать /me лишний раз).
 * reactive() делает объект "живым": когда user меняется, все экраны,
 * которые его показывают, обновляются автоматически.
 */
export const auth = reactive({
    user: null,
    checked: false,
});

/** Спросить у бэка, кто мы (по куке). Вызывается один раз при старте. */
export async function fetchUser() {
    try {
        const { data } = await api.get('/api/me');
        auth.user = data.user;
    } catch {
        auth.user = null; // 401 -> просто не залогинены
    } finally {
        auth.checked = true;
    }
}

/** Вход по нашей схеме: сначала CSRF-кука, потом сам логин. */
export async function login(email, password) {
    await api.get('/sanctum/csrf-cookie');
    const { data } = await api.post('/api/login', { email, password });
    auth.user = data.user;
}

/** Выход: гасим сессию на бэке и очищаем состояние на фронте. */
export async function logout() {
    await api.post('/api/logout');
    auth.user = null;
}
