<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { auth, logout } from '../stores/auth';
import api from '../lib/axios';

const router = useRouter();

const url = ref('');
const organization = ref(null);
const loadingOrg = ref(true);
const saving = ref(false);
const error = ref('');

let pollTimer = null;

onMounted(async () => {
    await loadOrganization();
    // если парсинг ещё идёт — начинаем следить за статусом
    if (['pending', 'parsing'].includes(organization.value?.status)) {
        startPolling();
    }
});

onUnmounted(stopPolling);

async function loadOrganization() {
    loadingOrg.value = true;
    try {
        const { data } = await api.get('/api/organization');
        organization.value = data.organization;
        if (data.organization) url.value = data.organization.yandex_url;
    } catch {
        error.value = 'Не удалось загрузить данные.';
    } finally {
        loadingOrg.value = false;
    }
}

async function save() {
    error.value = '';
    saving.value = true;
    try {
        const { data } = await api.post('/api/organization', { yandex_url: url.value });
        organization.value = data.organization;
        startPolling(); // ждём, пока фоновый парсинг отработает
    } catch (e) {
        error.value = e.response?.status === 422
            ? (e.response.data.message ?? 'Проверьте ссылку.')
            : 'Не удалось сохранить. Попробуйте позже.';
    } finally {
        saving.value = false;
    }
}

function startPolling() {
    stopPolling();
    pollTimer = setInterval(async () => {
        const { data } = await api.get('/api/organization');
        organization.value = data.organization;
        if (['done', 'failed'].includes(data.organization?.status)) stopPolling();
    }, 3000);
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

const status = computed(() => organization.value?.status);

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
                <button @click="doLogout" class="rounded border border-gray-300 px-3 py-1 hover:bg-gray-100">
                    Выйти
                </button>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <label class="mb-1 block text-sm font-medium text-gray-700">
                Ссылка на организацию в Яндекс.Картах
            </label>

            <form @submit.prevent="save" class="flex gap-2">
                <input
                    v-model="url"
                    type="url"
                    placeholder="https://yandex.ru/maps/org/…"
                    :disabled="loadingOrg"
                    class="flex-1 rounded border border-gray-300 px-3 py-2"
                />
                <button
                    type="submit"
                    :disabled="saving || loadingOrg"
                    class="rounded bg-blue-600 px-4 py-2 font-medium text-white disabled:opacity-50"
                >
                    {{ saving ? 'Сохраняем…' : 'Сохранить' }}
                </button>
            </form>

            <p v-if="error" class="mt-3 rounded bg-red-50 p-2 text-sm text-red-600">{{ error }}</p>

            <!-- Статус парсинга -->
            <div v-if="organization" class="mt-4 border-t pt-4 text-sm">
                <div v-if="status === 'pending'" class="text-gray-500">
                    ⏳ В очереди на парсинг…
                </div>
                <div v-else-if="status === 'parsing'" class="text-blue-600">
                    🔄 Парсим отзывы… это может занять пару минут.
                </div>
                <div v-else-if="status === 'failed'" class="text-red-600">
                    ⚠️ Не получилось: {{ organization.last_error }}
                </div>
                <div v-else-if="status === 'done'" class="flex items-center justify-between">
                    <span class="text-green-700">✅ Готово: {{ organization.reviews_count }} отзывов</span>
                    <router-link :to="{ name: 'reviews' }" class="rounded bg-gray-900 px-3 py-1 text-white hover:bg-gray-700">
                        Смотреть отзывы →
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>
