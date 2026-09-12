<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { login } from '../stores/auth';

const router = useRouter();

const email = ref('admin@example.com');
const password = ref('password');
const loading = ref(false);
const error = ref('');

async function submit() {
    error.value = '';
    loading.value = true;

    try {
        await login(email.value, password.value);
        router.push({ name: 'settings' });
    } catch (e) {
        // 422 — ошибка валидации от Laravel
        if (e.response?.status === 422) {
            error.value = e.response.data.message ?? 'Неверные данные для входа.';
        } else {
            error.value = 'Не удалось войти. Попробуйте позже.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center p-4">
        <form
            @submit.prevent="submit"
            class="w-full max-w-sm space-y-4 rounded-xl bg-white p-6 shadow"
        >
            <h1 class="text-xl font-semibold">Вход</h1>

            <p v-if="error" class="rounded bg-red-50 p-2 text-sm text-red-600">
                {{ error }}
            </p>

            <div class="space-y-1">
                <label class="text-sm text-gray-600">Email</label>
                <input
                    v-model="email"
                    type="email"
                    required
                    class="w-full rounded border border-gray-300 px-3 py-2"
                />
            </div>

            <div class="space-y-1">
                <label class="text-sm text-gray-600">Пароль</label>
                <input
                    v-model="password"
                    type="password"
                    required
                    class="w-full rounded border border-gray-300 px-3 py-2"
                />
            </div>

            <button
                type="submit"
                :disabled="loading"
                class="w-full rounded bg-blue-600 py-2 font-medium text-white disabled:opacity-50"
            >
                {{ loading ? 'Входим…' : 'Войти' }}
            </button>
        </form>
    </div>
</template>
