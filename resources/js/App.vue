<script setup>
// Корневой компонент. router-view подставляет сюда нужный экран
// (Login или Settings) в зависимости от текущего адреса.
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900">
        <router-view />
    </div>
</template>
