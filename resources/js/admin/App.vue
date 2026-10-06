<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { COLS, boot, go, loadEvents, logout, state } from './store';
import Login from './components/Login.vue';
import Drawer from './components/Drawer.vue';
import MediaPicker from './components/MediaPicker.vue';
import Dashboard from './views/Dashboard.vue';
import Stats from './views/Stats.vue';
import Messages from './views/Messages.vue';
import Newsletter from './views/Newsletter.vue';
import Collection from './views/Collection.vue';
import Skills from './views/Skills.vue';
import HomeSections from './views/HomeSections.vue';
import FormView from './views/FormView.vue';
import MediaLib from './views/MediaLib.vue';
import Trash from './views/Trash.vue';
import Labels from './views/Labels.vue';

const router = useRouter();
const onResize = () => { state.w = window.innerWidth; };
onMounted(() => { window.addEventListener('resize', onResize); boot().then(loadEvents); });
onBeforeUnmount(() => window.removeEventListener('resize', onResize));
watch(() => [state.view, state.data && state.data.settings && state.data.settings.analytics && state.data.settings.analytics.provider], () => {
  if (state.data && ['dashboard', 'stats'].includes(state.view)) loadEvents();
});

const dark = computed(() => state.theme === 'dark');
const mobile = computed(() => state.w < 1000);
const th = computed(() => (dark.value
  ? { bg: '#070E22', sf: '#0F1A36', sf2: '#13203F', ink: '#E9EDF6', mu: '#9AA6C2', ln: '#22305A', ln2: '#1A2750', ac: '#5B7CFF', ac2: '#3A4F96', acs: '#18275C', aci: '#B9C7FF', side: '#050B1C', sideLn: '#16224A' }
  : { bg: '#F3F5F9', sf: '#FFFFFF', sf2: '#F8F9FC', ink: '#0B1530', mu: '#4B5873', ln: '#D9DFEA', ln2: '#EEF1F6', ac: '#2448C8', ac2: '#8FA3E8', acs: '#E8EDFB', aci: '#1B379E', side: '#0B1530', sideLn: '#1E2A4C' }));
const cssVars = computed(() => {
  const o = { colorScheme: dark.value ? 'dark' : 'light' };
  Object.entries(th.value).forEach(([k, v]) => { o['--' + k] = v; });
  return o;
});
const toggleTheme = () => {
  state.theme = dark.value ? 'light' : 'dark';
  try { localStorage.setItem('fz.admin.theme', state.theme); } catch (e) { /* ignore */ }
};

const groups = computed(() => {
  const d = state.data, newCount = (d.messages || []).filter((m) => m.status === 'new').length;
  const it = (id, label, icon, badge) => ({ id, label, icon, badge });
  return [
    { label: 'Pilotage', items: [it('dashboard', 'Tableau de bord', 'space_dashboard'), it('stats', 'Statistiques', 'monitoring')] },
    { label: 'Échanges', items: [it('messages', 'Messages', 'mail', newCount), it('newsletter', 'Newsletter', 'mark_email_unread', (d.subscribers || []).length)] },
    { label: 'Contenu', items: [it('projects', 'Projets', 'grid_view'), it('posts', 'Blog', 'article'), it('experiences', 'Expériences', 'work_history'), it('education', 'Formation', 'school'), it('skills', 'Compétences', 'bolt'), it('services', 'Services', 'design_services'), it('certifications', 'Certifications', 'verified'), it('testimonials', 'Témoignages', 'format_quote')] },
    { label: 'Site', items: [it('profile', 'Mon profil', 'person'), it('media', 'Médiathèque', 'perm_media'), it('home', 'Page d’accueil', 'home'), it('seo', 'SEO & partage', 'travel_explore'), it('labels', 'Textes du site', 'translate'), it('settings', 'Paramètres', 'settings'), it('trash', 'Corbeille', 'delete', d.trashCount)] },
  ];
});

const META = {
  dashboard: ['Pilotage', 'Tableau de bord', 'Bonjour Fawoziath', 'Vue d’ensemble de l’audience, des échanges et des contenus à compléter.'],
  stats: ['Pilotage', 'Statistiques', 'Statistiques de visite', 'Audience, pages, provenance, localisation, appareils et mots-clés.'],
  messages: ['Échanges', 'Messages', 'Boîte de réception', 'Messages du formulaire de contact et demandes de service.'],
  newsletter: ['Échanges', 'Newsletter', 'Inscrits à la newsletter', 'Adresses collectées par le formulaire du footer.'],
  media: ['Site', 'Médiathèque', 'Médiathèque', 'Portrait, CV, captures de projets et images de partage.'],
  skills: ['Contenu', 'Compétences', 'Compétences', 'Groupes et technologies affichés dans « Environnement technique ».'],
  profile: ['Site', 'Mon profil', 'Mon profil', 'Identité, présentation, coordonnées et liens.'],
  home: ['Site', 'Page d’accueil', 'Page d’accueil', 'Activez ou masquez les sections de l’accueil.'],
  seo: ['Site', 'SEO & partage', 'SEO & partage', 'Titres, descriptions et image de partage, en FR et en EN.'],
  settings: ['Site', 'Paramètres', 'Paramètres', 'Source des statistiques, notifications et réglages du site.'],
  labels: ['Site', 'Textes du site', 'Textes du site', 'Menus, titres de sections, formulaires et messages du site public, en FR et en EN.'],
  trash: ['Site', 'Corbeille', 'Corbeille', 'Éléments supprimés : restaurez-les ou supprimez-les définitivement.'],
};
const col = computed(() => COLS[state.view]);
const view = computed(() => {
  const mt = META[state.view] || ['Contenu', col.value.label, col.value.label, 'Ajoutez, modifiez, publiez et réordonnez.'];
  return { group: mt[0], title: mt[1], h1: mt[2], sub: mt[3] };
});
const hasExport = computed(() => state.view === 'newsletter' && (state.data.subscribers || []).length > 0);
const addItem = () => { state.edit = { col: state.view, isNew: true, draft: col.value.blank() }; };
const exportCsv = () => {
  const rows = [['email', 'langue', 'date']].concat(state.data.subscribers.map((x) => [x.email, x.lang, x.date]));
  const a = document.createElement('a');
  // Champs entre guillemets ; une valeur commençant par = + - @ est neutralisée pour qu'Excel ne l'exécute pas comme formule.
  const cell = (v) => { let s = String(v ?? ''); if (/^[=+\-@\t\r]/.test(s)) s = "'" + s; return '"' + s.replace(/"/g, '""') + '"'; };
  a.href = URL.createObjectURL(new Blob([rows.map((r) => r.map(cell).join(';')).join('\n')], { type: 'text/csv' }));
  a.download = 'newsletter-inscrits.csv';
  a.click();
  setTimeout(() => URL.revokeObjectURL(a.href), 1000);
};
const autosave = computed(() => !mobile.value && ['profile', 'seo', 'settings', 'skills', 'home', 'labels'].includes(state.view));
const confirmYes = () => { const ok = state.confirm && state.confirm.ok; state.confirm = null; if (ok) ok(); };
const doLogout = async () => { await logout(); router.push('/admin'); };
</script>

<template>
  <div class="adm" :style="cssVars">
    <div v-if="!state.ready" class="adm-loading"><span class="ms">progress_activity</span></div>
    <Login v-else-if="!state.user || !state.data" />

    <template v-else>
      <div class="adm-layout">
        <aside v-if="!mobile || state.menu" class="side" :class="{ floating: mobile }">
          <div class="side-top">
            <a href="/" class="side-brand"><span class="side-mark">[ FS ]</span><span class="side-name">Fawoziath<span>.dev</span></span></a>
            <button v-if="mobile" type="button" class="side-x ms" aria-label="Fermer le menu" @click="state.menu = false">close</button>
          </div>
          <nav aria-label="Administration" class="side-nav">
            <div v-for="g in groups" :key="g.label" class="side-group">
              <p>{{ g.label }}</p>
              <button v-for="it in g.items" :key="it.id" type="button" class="side-item" :class="{ on: state.view === it.id }" :aria-current="state.view === it.id ? 'page' : null" @click="go(router, it.id)">
                <span aria-hidden="true" class="ms">{{ it.icon }}</span>
                <span class="f1">{{ it.label }}</span>
                <span v-if="it.badge" class="side-badge">{{ it.badge }}</span>
              </button>
            </div>
          </nav>
          <div class="side-user">
            <span class="side-av">FS</span>
            <span class="side-who"><strong>{{ state.user.name }}</strong><span>Administratrice</span></span>
            <button type="button" class="side-out ms" aria-label="Se déconnecter" title="Se déconnecter" @click="doLogout">logout</button>
          </div>
        </aside>
        <div v-if="mobile && state.menu" class="scrim" @click="state.menu = false"></div>

        <div class="adm-main">
          <header class="topbar">
            <button v-if="mobile" type="button" class="ibtn lg" aria-label="Menu" @click="state.menu = !state.menu"><span class="ms">menu</span></button>
            <div class="topbar-title"><span>{{ view.group }}</span><strong>{{ view.title }}</strong></div>
            <span class="f1"></span>
            <span v-if="autosave" class="autosave"><span class="ms">cloud_done</span>Enregistrement automatique</span>
            <button type="button" class="ibtn lg" :aria-label="dark ? 'Passer en mode clair' : 'Passer en mode sombre'" :title="dark ? 'Passer en mode clair' : 'Passer en mode sombre'" @click="toggleTheme"><span class="ms">{{ dark ? 'light_mode' : 'dark_mode' }}</span></button>
            <a href="/" target="_blank" rel="noopener" class="abtn"><span class="ms">open_in_new</span><span v-if="!mobile">Voir le site</span></a>
          </header>

          <main class="content">
            <div class="page-head">
              <div class="col gap4"><h1>{{ view.h1 }}</h1><p>{{ view.sub }}</p></div>
              <div class="page-actions">
                <div v-if="['dashboard', 'stats'].includes(state.view)" role="group" aria-label="Période" class="periods">
                  <button v-for="p in [7, 30, 90]" :key="p" type="button" :aria-pressed="state.period === p" :class="{ on: state.period === p }" @click="state.period = p">{{ p }} j</button>
                </div>
                <button v-if="col" type="button" class="abtn primary" @click="addItem"><span class="ms">add</span>Ajouter</button>
                <button v-if="hasExport" type="button" class="abtn" @click="exportCsv"><span class="ms">download</span>Exporter CSV</button>
              </div>
            </div>

            <Dashboard v-if="state.view === 'dashboard'" />
            <Stats v-else-if="state.view === 'stats'" />
            <Messages v-else-if="state.view === 'messages'" />
            <Newsletter v-else-if="state.view === 'newsletter'" />
            <Collection v-else-if="col" :key="state.view" />
            <Skills v-else-if="state.view === 'skills'" />
            <HomeSections v-else-if="state.view === 'home'" />
            <MediaLib v-else-if="state.view === 'media'" />
            <Trash v-else-if="state.view === 'trash'" />
            <Labels v-else-if="state.view === 'labels'" />
            <FormView v-else :key="state.view" :doc="state.view" />
          </main>
        </div>
      </div>

      <Drawer v-if="state.edit" />
      <MediaPicker v-if="state.pick" />

      <div v-if="state.confirm" class="overlay center z90">
        <div role="alertdialog" aria-modal="true" aria-labelledby="cf-title" class="confirm">
          <h2 id="cf-title">{{ state.confirm.title }}</h2>
          <p>{{ state.confirm.msg }}</p>
          <div class="confirm-actions">
            <button type="button" class="abtn" @click="state.confirm = null">Annuler</button>
            <button type="button" class="abtn danger" @click="confirmYes">{{ state.confirm.okLabel }}</button>
          </div>
        </div>
      </div>
    </template>

    <div v-if="state.toast" role="status" class="toast"><span class="ms">check_circle</span>{{ state.toast }}</div>
  </div>
</template>
