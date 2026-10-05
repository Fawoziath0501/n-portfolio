import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './site/App.vue';
import { parseRoute, state, track } from './site/store';

const Empty = { render: () => null };
const preferredLang = () => { try { return localStorage.getItem('fz.lang') === 'en' ? 'en' : 'fr'; } catch (e) { return 'fr'; } };

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: () => '/' + preferredLang() },
        { path: '/:lang(fr|en)', name: 'home', component: Empty },
        { path: '/:lang(fr|en)/:work(projets|work)/:slug', name: 'project', component: Empty },
        { path: '/:lang(fr|en)/:page(a-propos|about|projets|work|services|contact|blog)', name: 'page', component: Empty },
        { path: '/:rest(.*)*', redirect: () => '/' + preferredLang() },
    ],
    scrollBehavior: (to, from) => (to.path !== from.path ? { top: 0 } : false),
});

router.beforeEach((to) => {
    // Projet inconnu ou dépublié : retour à la liste des projets.
    if (to.name === 'project' && state.data && !state.data.projects.some((p) => p.slug === to.params.slug)) {
        return '/' + to.params.lang + '/' + (to.params.lang === 'en' ? 'work' : 'projets');
    }
});

router.afterEach((to, from) => {
    Object.assign(state, parseRoute(to), { menu: false });
    try { localStorage.setItem('fz.lang', state.lang); } catch (e) {}
    if (to.path !== from.path) track('pv');
});

createApp(App).use(router).mount('#app');
