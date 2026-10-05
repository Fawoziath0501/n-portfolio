<script setup>
import { computed } from 'vue';
import { api } from '../../shared/util';
import { FORMS, ask, flash, reload, save, setPath, state, syncDoc } from '../store';
import Fields from '../components/Fields.vue';
import Panel from '../components/Panel.vue';

const props = defineProps({ doc: String });
const cards = computed(() => FORMS[props.doc] || []);
const obj = computed(() => state.data[props.doc] || {});
const set = (k, v) => save((d) => { if (!d[props.doc]) d[props.doc] = {}; setPath(d[props.doc], k, v); }, null, () => syncDoc(props.doc));

// Réseaux sociaux (profil)
const socials = computed(() => state.data.profile.socials || []);
const setSocial = (s, k, v) => save(() => { s[k] = v; }, null, () => syncDoc('profile'));

// Sauvegarde / restauration (paramètres)
async function exportJson() {
  const { data } = await api.get('/admin/export');
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' }));
  a.download = 'portfolio-donnees-' + new Date().toISOString().slice(0, 10) + '.json';
  a.click();
  setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  flash('Sauvegarde exportée');
}
function importJson(e) {
  const f = e.target.files && e.target.files[0];
  e.target.value = '';
  if (!f) return;
  const r = new FileReader();
  r.onload = () => {
    let obj2;
    try { obj2 = JSON.parse(r.result); if (!obj2 || !obj2.profile || !obj2.projects) throw new Error('format'); } catch (er) { flash('Fichier invalide'); return; }
    ask('Remplacer les données actuelles ?', 'Le contenu du fichier « ' + f.name + ' » remplacera tout le contenu actuel.', async () => {
      try { await api.post('/admin/import', { data: obj2 }); await reload(); flash('Sauvegarde restaurée'); } catch (er) { flash('Import impossible'); }
    }, 'Remplacer');
  };
  r.readAsText(f);
}
</script>

<template>
  <Panel v-for="c in cards" :key="c.title" :title="c.title" :sub="c.sub">
    <div class="pad-form"><Fields :defs="c.fields" :obj="obj" @set="set" /></div>
  </Panel>

  <div v-if="doc === 'settings'" class="panel backup">
    <div class="col gap2"><h2 class="ph2">Sauvegarde des données</h2><span class="sm-mu fs13">Exportez tout le contenu (JSON) avant une mise en ligne, ou restaurez une sauvegarde.</span></div>
    <div class="row-wrap gap8">
      <button type="button" class="abtn primary" @click="exportJson"><span class="ms">download</span>Exporter</button>
      <label class="abtn"><span class="ms">upload</span>Importer<input type="file" accept="application/json,.json" class="hidden-file" @change="importJson"></label>
    </div>
  </div>

  <Panel v-if="doc === 'profile'" title="Réseaux & liens" sub="Affichés dans le hero, le contact et le footer">
    <div class="socials">
      <div v-for="s in socials" :key="s.id" class="row-wrap center gap8">
        <input :value="s.label" aria-label="Libellé" class="ain soc-l" @input="setSocial(s, 'label', $event.target.value)">
        <input :value="s.url" aria-label="URL" placeholder="https://" class="ain soc-u" @input="setSocial(s, 'url', $event.target.value)">
        <button type="button" class="ibtn h44" :aria-label="s.visible ? 'Masquer' : 'Afficher'" :title="s.visible ? 'Masquer' : 'Afficher'" @click="setSocial(s, 'visible', !s.visible)"><span class="ms">{{ s.visible ? 'visibility' : 'visibility_off' }}</span></button>
      </div>
    </div>
  </Panel>
</template>
