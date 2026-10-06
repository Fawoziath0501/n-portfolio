import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './admin/App.vue';
import { VIEWS, state } from './admin/store';

const Empty = { render: () => null };

const router = createRouter({
    // Adresse de l'administration fournie par le serveur (ADMIN_PATH).
    history: createWebHistory(window.__ADMIN_BASE__ || '/admin'),
    routes: [
        { path: '/:view?', component: Empty },
        { path: '/:rest(.*)*', redirect: '/' },
    ],
});

router.afterEach((to) => {
    const v = to.params.view || 'dashboard';
    state.view = VIEWS.includes(v) ? v : 'dashboard';
    state.menu = false;
    state.q = '';
    if (state.view !== 'messages') state.msgId = null;
});

createApp(App).use(router).mount('#app');
