<script setup>
// Sauvegardes complètes (base de données + images) : créées chaque nuit sur le serveur, envoyées par e-mail le dimanche.
import { onMounted, ref } from 'vue';
import { api } from '../../shared/util';
import { flash, flashError } from '../store';
import Panel from './Panel.vue';

const list = ref([]);
const busy = ref(false);
const size = (b) => (b < 1048576 ? Math.max(1, Math.round(b / 1024)) + ' Ko' : (b / 1048576).toFixed(1).replace('.', ',') + ' Mo');
const when = (d) => new Date(d).toLocaleString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

onMounted(async () => { try { list.value = (await api.get('/admin/backups')).data; } catch (e) { flashError(e); } });

async function createNow() {
  busy.value = true;
  try {
    list.value = (await api.post('/admin/backups')).data;
    flash('Sauvegarde créée');
  } catch (e) { flashError(e); } finally { busy.value = false; }
}
</script>

<template>
  <Panel title="Sauvegardes complètes" sub="Base de données et images, chaque nuit sur le serveur (14 gardées), envoyées par e-mail chaque dimanche.">
    <template #action>
      <button type="button" class="abtn xs" :disabled="busy" @click="createNow"><span class="ms">backup</span>{{ busy ? 'Sauvegarde…' : 'Sauvegarder maintenant' }}</button>
    </template>
    <p v-if="!list.length" class="empty-txt">Aucune sauvegarde pour le moment.</p>
    <ul v-else class="bk-list">
      <li v-for="b in list" :key="b.name" class="bk-row">
        <span class="ms mu" aria-hidden="true">inventory_2</span>
        <span class="f1"><strong>{{ when(b.date) }}</strong><span class="sm-mu"> · {{ size(b.size) }}</span></span>
        <a class="abtn xxs" :href="'/api/admin/backups/' + encodeURIComponent(b.name)"><span class="ms">download</span>Télécharger</a>
      </li>
    </ul>
  </Panel>
</template>
