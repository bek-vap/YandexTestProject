import { reactive } from 'vue';
import api from '../lib/axios';

// состояние авторизации; checked — спрашивали ли уже бэк "кто я"
export const auth = reactive({
    user: null,
    checked: false,
});

export async function fetchUser() {
    try {
        const { data } = await api.get('/api/me');
        auth.user = data.user;
    } catch {
        auth.user = null; // 401 — просто не залогинены
    } finally {
        auth.checked = true;
    }
}

export async function login(email, password) {
    // перед логином нужна CSRF-кука
    await api.get('/sanctum/csrf-cookie');
    const { data } = await api.post('/api/login', { email, password });
    auth.user = data.user;
}

export async function logout() {
    await api.post('/api/logout');
    auth.user = null;
}
