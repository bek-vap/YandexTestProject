<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { auth, logout } from '../stores/auth';
import api from '../lib/axios';

const router = useRouter();

const url = ref('');              // ссылка в поле ввода
const organization = ref(null);   // сохранённая карточка
const loadingOrg = ref(true);     // идёт первичная загрузка карточки
const saving = ref(false);        // идёт сохранение
const error = ref('');            // ошибка (в т.ч. валидации)
const savedOk = ref(false);       // показать "сохранено"

// При открытии страницы — подтягиваем уже сохранённую карточку (если есть).
onMounted(loadOrganization);

async function loadOrganization() {
    loadingOrg.value = true;
    try {
        const { data } = await api.get('/api/organization');
        organization.value = data.organization;
        if (data.organization) {
            url.value = data.organization.yandex_url;
        }
    } catch {
        error.value = 'Не удалось загрузить данные.';
    } finally {
        loadingOrg.value = false;
    }
}

async function save() {
    error.value = '';
    savedOk.value = false;
    saving.value = true;

    try {
        const { data } = await api.post('/api/organization', { yandex_url: url.value });
        organization.value = data.organization;
        savedOk.value = true;
    } catch (e) {
        if (e.response?.status === 422) {
            // Ошибка валидации от Laravel.
            error.value = e.response.data.message ?? 'Проверьте ссылку.';
        } else {
            error.value = 'Не удалось сохранить. Попробуйте позже.';
        }
    } finally {
        saving.value = false;
    }
}

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

            <!-- Состояния под формой -->
            <p v-if="error" class="mt-3 rounded bg-red-50 p-2 text-sm text-red-600">
                {{ error }}
            </p>
            <p v-else-if="savedOk" class="mt-3 rounded bg-green-50 p-2 text-sm text-green-700">
                Ссылка сохранена. Дальше здесь появятся отзывы и рейтинг.
            </p>

            <!-- Инфо о текущей карточке -->
            <div v-if="organization" class="mt-4 border-t pt-4 text-sm text-gray-600">
                <div>Статус: <b>{{ organization.status }}</b></div>
            </div>
        </div>
    </div>
</template>
