import { createRouter, createWebHistory } from 'vue-router';
import { auth, fetchUser } from './stores/auth';
import Login from './pages/Login.vue';
import Settings from './pages/Settings.vue';
import Reviews from './pages/Reviews.vue';

const routes = [
    { path: '/', redirect: '/settings' },
    { path: '/login', name: 'login', component: Login, meta: { guest: true } },
    { path: '/settings', name: 'settings', component: Settings, meta: { auth: true } },
    { path: '/reviews', name: 'reviews', component: Reviews, meta: { auth: true } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// охранник роутов: не пускаем гостей на защищённые страницы и наоборот
router.beforeEach(async (to) => {
    if (! auth.checked) {
        await fetchUser();
    }

    if (to.meta.auth && ! auth.user) {
        return { name: 'login' };
    }

    if (to.meta.guest && auth.user) {
        return { name: 'settings' };
    }
});

export default router;
