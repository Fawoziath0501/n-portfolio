<script setup>
// Journaux du serveur : erreurs et avertissements de Laravel, pour diagnostiquer sans accès au serveur.
import { computed, onMounted, ref, watch } from 'vue';
import { api } from '../../shared/util';
import { ask, flash, flashError } from '../store';
import Panel from '../components/Panel.vue';

const files = ref([]);
const file = ref('');
const level = ref('');
const q = ref('');
const res = ref(null);
const busy = ref(false);
const open = ref({});

const LEVELS = [['', 'Tout'], ['emergency', 'Urgence'], ['alert', 'Alerte'], ['critical', 'Critique'], ['error', 'Erreur'], ['warning', 'Avertissement'], ['notice', 'Notice'], ['info', 'Info'], ['debug', 'Débogage']];
const tone = (lv) => (['emergency', 'alert', 'critical', 'error'].includes(lv) ? 'bad' : lv === 'warning' ? 'warn' : lv === 'debug' ? 'mute' : 'ok');
const chips = computed(() => LEVELS.filter(([k]) => !k || (res.value && res.value.counts[k])).map(([k, lb]) => ({ k, lb, n: k ? res.value.counts[k] : Object.values((res.value || {}).counts || {}).reduce((a, n) => a + n, 0) })));
const size = (b) => (b < 1024 ? b + ' o' : b < 1048576 ? (b / 1024).toFixed(1).replace('.', ',') + ' Ko' : (b / 1048576).toFixed(1).replace('.', ',') + ' Mo');
const when = (s) => { const d = new Date(s.replace(' ', 'T')); return isNaN(d) ? s : d.toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit' }); };

async function loadFiles() {
  const { data } = await api.get('/admin/logs');
  files.value = data.files;
  if (!files.value.find((x) => x.name === file.value)) file.value = files.value[0] ? files.value[0].name : '';
}
let timer = null;
async function load() {
  if (!file.value) { res.value = null; return; }
  busy.value = true;
  try {
    const { data } = await api.get('/admin/logs/' + encodeURIComponent(file.value), { params: { level: level.value || undefined, q: q.value || undefined } });
    res.value = data;
    open.value = {};
  } catch (e) { flashError(e); } finally { busy.value = false; }
}
async function refresh() { try { await loadFiles(); await load(); } catch (e) { flashError(e); } }
watch([file, level], load);
watch(q, () => { clearTimeout(timer); timer = setTimeout(load, 300); });
onMounted(refresh);

function clearFile() {
  ask('Vider ce journal ?', 'Toutes les entrées de ' + file.value + ' seront effacées définitivement. Téléchargez-le avant si besoin.', async () => {
    try { await api.delete('/admin/logs/' + encodeURIComponent(file.value)); flash('Journal vidé'); await refresh(); } catch (e) { flashError(e); }
  }, 'Vider');
}
const copy = async (e) => { try { await navigator.clipboard.writeText('[' + e.at + '] ' + e.level.toUpperCase() + ': ' + e.message + (e.details ? '\n' + e.details : '')); flash('Entrée copiée'); } catch (err) { flashError(err); } };
</script>

<template>
  <Panel title="Entrées du journal" :sub="res ? res.file + ' · ' + size(res.size) + (res.partial ? ' · seules les dernières entrées (2 Mo) sont lues' : '') : 'Erreurs et événements enregistrés par le serveur'">
    <template #action>
      <div class="row-wrap gap8">
        <button type="button" class="abtn xs" :disabled="busy" @click="refresh"><span class="ms">refresh</span>Actualiser</button>
        <a v-if="file" class="abtn xs" :href="'/api/admin/logs/' + encodeURIComponent(file) + '/download'"><span class="ms">download</span>Télécharger</a>
        <button v-if="file && res && res.size" type="button" class="abtn xs" @click="clearFile"><span class="ms">delete_sweep</span>Vider</button>
      </div>
    </template>
    <div class="pad-form col gap12">
      <div class="row-wrap gap10 center">
        <label v-if="files.length > 1" class="flt"><span>Fichier</span>
          <select v-model="file" class="ain"><option v-for="x in files" :key="x.name" :value="x.name">{{ x.name }} ({{ size(x.size) }})</option></select></label>
        <label class="flt wide"><span>Rechercher</span><input v-model="q" type="search" class="ain" placeholder="Mot, classe, fichier…"></label>
      </div>
      <div v-if="res" class="periods log-levels" role="group" aria-label="Niveau">
        <button v-for="c in chips" :key="c.k" type="button" :aria-pressed="level === c.k" :class="{ on: level === c.k }" @click="level = c.k">{{ c.lb }} <span class="mono13">{{ c.n }}</span></button>
      </div>
    </div>
    <p v-if="!files.length" class="empty-txt">Aucun journal pour le moment : tout va bien.</p>
    <p v-else-if="res && !res.entries.length" class="empty-txt">{{ q || level ? 'Aucune entrée ne correspond.' : 'Journal vide.' }}</p>
    <ul v-else-if="res" class="log-list">
      <li v-for="(e, i) in res.entries" :key="i" class="log-row">
        <button type="button" class="log-head" :aria-expanded="!!open[i]" :disabled="!e.details" @click="open[i] = !open[i]">
          <span class="log-lv" :class="tone(e.level)">{{ e.level }}</span>
          <span class="log-at mono13">{{ when(e.at) }}</span>
          <span class="log-msg">{{ e.message }}</span>
          <span v-if="e.details" class="ms log-chev" aria-hidden="true">{{ open[i] ? 'expand_less' : 'expand_more' }}</span>
        </button>
        <div v-if="open[i]" class="log-det">
          <button type="button" class="abtn xxs" @click="copy(e)"><span class="ms">content_copy</span>Copier</button>
          <pre>{{ e.details }}</pre>
        </div>
      </li>
    </ul>
    <p v-if="res && res.total > res.entries.length" class="sm-mu log-more">{{ res.entries.length }} entrées les plus récentes affichées sur {{ res.total }}. Affinez avec la recherche ou le niveau.</p>
  </Panel>
</template>
