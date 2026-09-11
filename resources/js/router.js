import { createRouter, createWebHistory } from 'vue-router';
import { auth, fetchUser } from './stores/auth';
import Login from './pages/Login.vue';
import Settings from './pages/Settings.vue';

const routes = [
    { path: '/', redirect: '/settings' },
    // meta.guest — страница только для НЕзалогиненных (гостей).
    { path: '/login', name: 'login', component: Login, meta: { guest: true } },
    // meta.auth — страница только для залогиненных.
    { path: '/settings', name: 'settings', component: Settings, meta: { auth: true } },
];

const router = createRouter({
    history: createWebHistory(), // "красивые" URL без #
    routes,
});

/**
 * Глобальный "охранник": выполняется ПЕРЕД каждым переходом.
 * Здесь мы решаем, пускать ли пользователя на страницу.
 */
router.beforeEach(async (to) => {
    // Первый заход: узнаём у бэка, залогинены ли мы (по куке).
    if (! auth.checked) {
        await fetchUser();
    }

    // На защищённую страницу без входа -> на логин.
    if (to.meta.auth && ! auth.user) {
        return { name: 'login' };
    }

    // Залогиненного не пускаем обратно на логин -> сразу в настройки.
    if (to.meta.guest && auth.user) {
        return { name: 'settings' };
    }
});

export default router;
