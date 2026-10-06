import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './site/App.vue';
import { SLUGS, countPostView, findLegal, parseRoute, state, track } from './site/store';

const Empty = { render: () => null };
const preferredLang = () => { try { return localStorage.getItem('fz.lang') === 'en' ? 'en' : 'fr'; } catch (e) { return 'fr'; } };

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: () => '/' + preferredLang() },
        { path: '/:lang(fr|en)', name: 'home', component: Empty },
        { path: '/:lang(fr|en)/:work(projets|work)/:slug', name: 'project', component: Empty },
        { path: '/:lang(fr|en)/blog/:slug', name: 'post', component: Empty },
        // Pages du site et pages légales (slugs définis dans l'administration).
        { path: '/:lang(fr|en)/:page([a-z0-9-]+)', name: 'page', component: Empty },
        { path: '/:rest(.*)*', redirect: () => '/' + preferredLang() },
    ],
    // Lien vers une section (#skills…) : on attend son affichage avant de défiler.
    scrollBehavior: (to, from) => (to.hash
        ? new Promise((ok) => setTimeout(() => ok({ el: to.hash, top: 90, behavior: 'smooth' }), 120))
        : to.path !== from.path ? { top: 0 } : false),
});

router.beforeEach((to) => {
    // Projet inconnu ou dépublié : retour à la liste des projets.
    if (to.name === 'project' && state.data && !state.data.projects.some((p) => p.slug === to.params.slug)) {
        return '/' + to.params.lang + '/' + (to.params.lang === 'en' ? 'work' : 'projets');
    }
    // Article inconnu : retour au blog. Page inconnue (ni page du site, ni page légale) : accueil.
    if (to.name === 'post' && state.data && !(state.data.posts || []).some((p) => p.slug === to.params.slug)) return '/' + to.params.lang + '/blog';
    if (to.name === 'page' && state.data && !Object.values(SLUGS).flat().includes(to.params.page) && !findLegal(to.params.page)) return '/' + to.params.lang;
});

router.afterEach((to, from) => {
    Object.assign(state, parseRoute(to), { menu: false });
    try { localStorage.setItem('fz.lang', state.lang); } catch (e) {}
    if (to.path !== from.path) track('pv');
    if (to.name === 'post') countPostView(to.params.slug);
});

createApp(App).use(router).mount('#app');
