import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './admin/App.vue';
import { VIEWS, state } from './admin/store';

const Empty = { render: () => null };

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/admin/:view?', component: Empty },
        { path: '/:rest(.*)*', redirect: '/admin' },
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
