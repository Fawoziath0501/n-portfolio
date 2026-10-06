<script setup>
// Menus du site public : liens de la barre du haut, bouton d'action, colonnes du pied de page.
// Chaque modification est enregistrée automatiquement (réglage « menus »).
import { computed } from 'vue';
import { save, state, syncDoc } from '../store';
import Panel from '../components/Panel.vue';

const PAGES = [
  ['home', 'Accueil', 'Home'], ['about', 'À propos', 'About'], ['work', 'Projets', 'Work'], ['services', 'Services', 'Services'],
  ['blog', 'Blog', 'Blog'], ['contact', 'Contact', 'Contact'],
  ['approach', 'À propos › Méthode', 'Approach'], ['experience', 'À propos › Parcours', 'Experience'],
  ['languages', 'À propos › Langues', 'Languages'], ['skills', 'À propos › Compétences', 'Skills'],
  ['url', 'Adresse personnalisée…', ''],
];
const m = computed(() => state.data.menus || { header: { items: [], cta: {} }, footer: [] });

const uid = () => Math.random().toString(36).slice(2, 10);
const blank = () => ({ id: uid(), page: 'home', url: '', label: { fr: 'Accueil', en: 'Home' }, visible: true });
const commit = (fn, msg) => save((d) => { if (!d.menus) d.menus = { header: { items: [], cta: {} }, footer: [] }; fn(d.menus); }, msg || null, () => syncDoc('menus', msg || undefined));

const move = (list, i, dir) => { const j = i + dir; if (j >= 0 && j < list.length) commit(() => { [list[i], list[j]] = [list[j], list[i]]; }); };
const add = (list) => commit(() => list.push(blank()), 'Lien ajouté');
const remove = (list, i) => commit(() => list.splice(i, 1), 'Lien retiré');
const set = (obj, k, v) => commit(() => { obj[k] = v; });
const setText = (obj, k, lang, v) => commit(() => { obj[k] = { ...(obj[k] || {}), [lang]: v }; });
// Changement de page : le libellé reprend le nom de la page s'il était encore celui par défaut.
const setPage = (it, page) => commit(() => {
  const prev = PAGES.find((p) => p[0] === it.page), next = PAGES.find((p) => p[0] === page);
  if (next && next[2] && (!it.label || !it.label.fr || (prev && it.label.fr === prev[1]))) it.label = { fr: next[1].replace(/^À propos › /, ''), en: next[2] };
  it.page = page;
});
const addColumn = () => commit((mm) => mm.footer.push({ id: uid(), title: { fr: 'Nouvelle colonne', en: 'New column' }, items: [blank()] }), 'Colonne ajoutée');
const removeColumn = (i) => commit((mm) => mm.footer.splice(i, 1), 'Colonne retirée');
const moveColumn = (i, dir) => { const f = m.value.footer, j = i + dir; if (j >= 0 && j < f.length) commit(() => { [f[i], f[j]] = [f[j], f[i]]; }); };
</script>

<template>
  <Panel title="Barre du haut" sub="Liens affichés en haut de chaque page (et dans le menu mobile), dans cet ordre.">
    <div class="menu-list">
      <div v-for="(it, i) in m.header.items" :key="it.id" class="menu-row" :class="{ off: !it.visible }">
        <div class="menu-move">
          <button type="button" class="ibtn" aria-label="Monter" :disabled="i === 0" @click="move(m.header.items, i, -1)"><span class="ms">arrow_upward</span></button>
          <button type="button" class="ibtn" aria-label="Descendre" :disabled="i === m.header.items.length - 1" @click="move(m.header.items, i, 1)"><span class="ms">arrow_downward</span></button>
        </div>
        <select class="ain" aria-label="Page" :value="it.page" @change="setPage(it, $event.target.value)">
          <option v-for="p in PAGES" :key="p[0]" :value="p[0]">{{ p[1] }}</option>
        </select>
        <input v-if="it.page === 'url'" class="ain" placeholder="https://… ou /fr/…" aria-label="Adresse" :value="it.url" @input="set(it, 'url', $event.target.value)">
        <label class="lang-in"><span>FR</span><input class="ain" :value="(it.label || {}).fr" @input="setText(it, 'label', 'fr', $event.target.value)"></label>
        <label class="lang-in"><span>EN</span><input class="ain" :value="(it.label || {}).en" @input="setText(it, 'label', 'en', $event.target.value)"></label>
        <button type="button" class="ibtn" :aria-label="it.visible ? 'Masquer' : 'Afficher'" :title="it.visible ? 'Masquer' : 'Afficher'" @click="set(it, 'visible', !it.visible)"><span class="ms">{{ it.visible ? 'visibility' : 'visibility_off' }}</span></button>
        <button type="button" class="ibtn" aria-label="Retirer" title="Retirer" @click="remove(m.header.items, i)"><span class="ms">delete</span></button>
      </div>
      <button type="button" class="abtn self-start" @click="add(m.header.items)"><span class="ms">add</span>Ajouter un lien</button>
    </div>
  </Panel>

  <Panel title="Bouton d’action" sub="Bouton mis en avant à droite de la barre du haut (ex. « Me contacter »).">
    <div class="menu-row">
      <button type="button" role="switch" :aria-checked="!!m.header.cta.visible" class="switch-btn" @click="set(m.header.cta, 'visible', !m.header.cta.visible)">
        <span class="switch" :class="{ on: !!m.header.cta.visible }"><span></span></span>{{ m.header.cta.visible ? 'Affiché' : 'Masqué' }}
      </button>
      <select class="ain" aria-label="Page" :value="m.header.cta.page" @change="set(m.header.cta, 'page', $event.target.value)">
        <option v-for="p in PAGES" :key="p[0]" :value="p[0]">{{ p[1] }}</option>
      </select>
      <input v-if="m.header.cta.page === 'url'" class="ain" placeholder="https://… ou /fr/…" aria-label="Adresse" :value="m.header.cta.url" @input="set(m.header.cta, 'url', $event.target.value)">
      <label class="lang-in"><span>FR</span><input class="ain" :value="(m.header.cta.label || {}).fr" @input="setText(m.header.cta, 'label', 'fr', $event.target.value)"></label>
      <label class="lang-in"><span>EN</span><input class="ain" :value="(m.header.cta.label || {}).en" @input="setText(m.header.cta, 'label', 'en', $event.target.value)"></label>
    </div>
  </Panel>

  <Panel v-for="(c, ci) in m.footer" :key="c.id" :title="'Pied de page · colonne ' + (ci + 1)" sub="Titre et liens de la colonne. Une colonne sans lien visible n’est pas affichée.">
    <template #action>
      <div class="row gap4">
        <button type="button" class="ibtn" aria-label="Déplacer à gauche" :disabled="ci === 0" @click="moveColumn(ci, -1)"><span class="ms">arrow_back</span></button>
        <button type="button" class="ibtn" aria-label="Déplacer à droite" :disabled="ci === m.footer.length - 1" @click="moveColumn(ci, 1)"><span class="ms">arrow_forward</span></button>
        <button type="button" class="ibtn" aria-label="Supprimer la colonne" title="Supprimer la colonne" @click="removeColumn(ci)"><span class="ms">delete</span></button>
      </div>
    </template>
    <div class="menu-list">
      <div class="menu-title">
        <span class="fw6 fs13">Titre</span>
        <label class="lang-in"><span>FR</span><input class="ain" :value="(c.title || {}).fr" @input="setText(c, 'title', 'fr', $event.target.value)"></label>
        <label class="lang-in"><span>EN</span><input class="ain" :value="(c.title || {}).en" @input="setText(c, 'title', 'en', $event.target.value)"></label>
      </div>
      <div v-for="(it, i) in c.items" :key="it.id" class="menu-row" :class="{ off: !it.visible }">
        <div class="menu-move">
          <button type="button" class="ibtn" aria-label="Monter" :disabled="i === 0" @click="move(c.items, i, -1)"><span class="ms">arrow_upward</span></button>
          <button type="button" class="ibtn" aria-label="Descendre" :disabled="i === c.items.length - 1" @click="move(c.items, i, 1)"><span class="ms">arrow_downward</span></button>
        </div>
        <select class="ain" aria-label="Page" :value="it.page" @change="setPage(it, $event.target.value)">
          <option v-for="p in PAGES" :key="p[0]" :value="p[0]">{{ p[1] }}</option>
        </select>
        <input v-if="it.page === 'url'" class="ain" placeholder="https://… ou /fr/…" aria-label="Adresse" :value="it.url" @input="set(it, 'url', $event.target.value)">
        <label class="lang-in"><span>FR</span><input class="ain" :value="(it.label || {}).fr" @input="setText(it, 'label', 'fr', $event.target.value)"></label>
        <label class="lang-in"><span>EN</span><input class="ain" :value="(it.label || {}).en" @input="setText(it, 'label', 'en', $event.target.value)"></label>
        <button type="button" class="ibtn" :aria-label="it.visible ? 'Masquer' : 'Afficher'" :title="it.visible ? 'Masquer' : 'Afficher'" @click="set(it, 'visible', !it.visible)"><span class="ms">{{ it.visible ? 'visibility' : 'visibility_off' }}</span></button>
        <button type="button" class="ibtn" aria-label="Retirer" title="Retirer" @click="remove(c.items, i)"><span class="ms">delete</span></button>
      </div>
      <button type="button" class="abtn self-start" @click="add(c.items)"><span class="ms">add</span>Ajouter un lien</button>
    </div>
  </Panel>

  <div class="row-wrap gap8">
    <button v-if="m.footer.length < 4" type="button" class="abtn" @click="addColumn"><span class="ms">view_column</span>Ajouter une colonne au pied de page</button>
    <span class="sm-mu self-center">Les pages légales (mentions légales, CGU…) sont ajoutées automatiquement en bas du pied de page.</span>
  </div>
</template>
