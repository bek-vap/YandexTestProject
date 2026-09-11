import '../css/app.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

// Создаём Vue-приложение, подключаем роутер и монтируем в <div id="app">.
createApp(App)
    .use(router)
    .mount('#app');
