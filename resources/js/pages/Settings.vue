<script setup>
import { useRouter } from 'vue-router';
import { auth, logout } from '../stores/auth';

const router = useRouter();

async function doLogout() {
    await logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="mx-auto max-w-2xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Настройки</h1>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-gray-500">{{ auth.user?.email }}</span>
                <button
                    @click="doLogout"
                    class="rounded border border-gray-300 px-3 py-1 hover:bg-gray-100"
                >
                    Выйти
                </button>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow text-gray-500">
            Здесь скоро появится поле для ссылки на организацию в Яндекс.Картах.
        </div>
    </div>
</template>
