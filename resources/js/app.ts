import { createApp } from 'vue';
import router from './router';
import '../css/app.css';
import { initializeTheme } from '@/composables/useAppearance';
import App from '@/layouts/AppShell.vue';

const app = createApp(App);

app.use(router);

router.isReady().then(() => {
    app.mount('#app');
});

initializeTheme();
