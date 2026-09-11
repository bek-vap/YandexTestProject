import axios from 'axios';

/**
 * Единый настроенный axios для всех запросов к нашему API.
 *
 * Для Sanctum SPA критичны две опции:
 *  - withCredentials: true  -> браузер прикладывает куки сессии к каждому запросу
 *    (иначе бэк нас не узнает, вход "не будет держаться").
 *  - withXSRFToken: true    -> axios сам берёт значение из куки XSRF-TOKEN
 *    и кладёт его в заголовок X-XSRF-TOKEN (защита от CSRF на POST/PUT/DELETE).
 */
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
