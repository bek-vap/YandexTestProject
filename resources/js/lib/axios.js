import axios from 'axios';

// axios для всех запросов к API.
// withCredentials + withXSRFToken нужны для Sanctum SPA: браузер шлёт куку сессии,
// а axios сам подставляет CSRF-токен из куки в заголовок X-XSRF-TOKEN.
const api = axios.create({
    baseURL: '/',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export default api;
