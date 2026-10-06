<script setup>
// Menus du site public : liens de la barre du haut, bouton d'action, colonnes du pied de page.
// Chaque modification est enregistrée automatiquement (réglage « menus »).
import { computed } from 'vue';
import { save, state, syncDoc } from '../store';
import Panel from '../components/Panel.vue';
import MenuRow from '../components/MenuRow.vue';

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
  <Panel title="Barre du haut" sub="Liens affichés en haut de chaque page et dans le menu mobile, dans cet ordre.">
    <div class="mtable">
      <div class="mhead" aria-hidden="true"><span></span><span>Page</span><span>Libellé FR</span><span>Libellé EN</span><span></span></div>
      <MenuRow v-for="(it, i) in m.header.items" :key="it.id" :it="it" :pages="PAGES" :first="i === 0" :last="i === m.header.items.length - 1"
        @page="setPage(it, $event)" @set="(k, v) => set(it, k, v)" @text="(l, v) => setText(it, 'label', l, v)" @move="move(m.header.items, i, $event)" @remove="remove(m.header.items, i)" />
      <button type="button" class="abtn sm madd" @click="add(m.header.items)"><span class="ms">add</span>Ajouter un lien</button>
    </div>
    <div class="mcta">
      <span class="mcta-title"><span class="ms" aria-hidden="true">ads_click</span>Bouton d’action <small>à droite de la barre (ex. « Me contacter »)</small></span>
      <MenuRow :it="m.header.cta" :pages="PAGES" cta @page="set(m.header.cta, 'page', $event)" @set="(k, v) => set(m.header.cta, k, v)" @text="(l, v) => setText(m.header.cta, 'label', l, v)" />
    </div>
  </Panel>

  <Panel v-for="(c, ci) in m.footer" :key="c.id" :title="'Pied de page · colonne ' + (ci + 1)" sub="Une colonne sans lien affiché n’apparaît pas sur le site.">
    <template #action>
      <div class="row gap4">
        <button type="button" class="gbtn" aria-label="Déplacer la colonne à gauche" title="Déplacer à gauche" :disabled="ci === 0" @click="moveColumn(ci, -1)"><span class="ms">arrow_back</span></button>
        <button type="button" class="gbtn" aria-label="Déplacer la colonne à droite" title="Déplacer à droite" :disabled="ci === m.footer.length - 1" @click="moveColumn(ci, 1)"><span class="ms">arrow_forward</span></button>
        <button type="button" class="gbtn danger" aria-label="Supprimer la colonne" title="Supprimer la colonne" @click="removeColumn(ci)"><span class="ms">delete</span></button>
      </div>
    </template>
    <div class="mtable">
      <div class="mtitle">
        <span>Titre de la colonne</span>
        <input class="ain" aria-label="Titre FR" placeholder="Titre FR" :value="(c.title || {}).fr" @input="setText(c, 'title', 'fr', $event.target.value)">
        <input class="ain" aria-label="Titre EN" placeholder="Titre EN" :value="(c.title || {}).en" @input="setText(c, 'title', 'en', $event.target.value)">
      </div>
      <div class="mhead" aria-hidden="true"><span></span><span>Page</span><span>Libellé FR</span><span>Libellé EN</span><span></span></div>
      <MenuRow v-for="(it, i) in c.items" :key="it.id" :it="it" :pages="PAGES" :first="i === 0" :last="i === c.items.length - 1"
        @page="setPage(it, $event)" @set="(k, v) => set(it, k, v)" @text="(l, v) => setText(it, 'label', l, v)" @move="move(c.items, i, $event)" @remove="remove(c.items, i)" />
      <button type="button" class="abtn sm madd" @click="add(c.items)"><span class="ms">add</span>Ajouter un lien</button>
    </div>
  </Panel>

  <div class="row-wrap center gap10">
    <button v-if="m.footer.length < 4" type="button" class="abtn" @click="addColumn"><span class="ms">view_column</span>Ajouter une colonne au pied de page</button>
    <span class="sm-mu">Les pages légales (mentions légales, CGU…) s’ajoutent automatiquement en bas du pied de page.</span>
  </div>
</template>
