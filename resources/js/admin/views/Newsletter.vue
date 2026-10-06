<script setup>
import { computed, ref } from 'vue';
import { api } from '../../shared/util';
import { TRASH_HINT, ask, flash, flashError, fmtMD, iso, save, state, t, trash } from '../store';

const subs = computed(() => (state.data.subscribers || []).slice().sort((a, b) => String(b.date).localeCompare(String(a.date))));
const kpis = computed(() => {
  const d30 = iso(new Date(Date.now() - 30 * 864e5));
  return [{ label: 'Inscrits au total', value: subs.value.length }, { label: 'Nouveaux (30 j)', value: subs.value.filter((x) => x.date >= d30).length }, { label: 'En anglais', value: subs.value.filter((x) => x.lang === 'en').length }];
});
const del = (x) => ask('Retirer ' + x.email + ' ?', TRASH_HINT + ' S’il se réinscrit, il sera restauré automatiquement.', () => save((d) => { d.subscribers = d.subscribers.filter((y) => y.id !== x.id); }, 'Inscrit placé dans la corbeille', () => trash(api.delete('/admin/subscribers/' + x.id))), 'Retirer');

// Envoi d'un article publié aux abonnés (chacun dans sa langue, avec lien de désinscription).
const posts = computed(() => (state.data.posts || []).filter((p) => p.published).slice().sort((a, b) => String(b.date).localeCompare(String(a.date))));
const postId = ref(null);
const chosen = computed(() => posts.value.find((p) => p.id === postId.value) || posts.value[0]);
const sending = ref(false);
const sentOn = (p) => (p && p.newsletterSentAt ? new Date(p.newsletterSentAt).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : '');
function sendPost() {
  const p = chosen.value;
  if (!p) return;
  const n = subs.value.length;
  const again = !!p.newsletterSentAt;
  ask(again ? 'Renvoyer cet article ?' : 'Envoyer cet article aux abonnés ?',
    '« ' + t(p.title) + ' » sera envoyé à ' + n + ' abonné(s), chacun dans sa langue.' + (again ? ' Il a déjà été envoyé le ' + sentOn(p) + '.' : ''),
    async () => {
      sending.value = true;
      try {
        const { data } = await api.post('/admin/newsletter/' + p.id, { force: again });
        p.newsletterSentAt = data.at;
        if (data.failed) flash(data.sent + ' envoi(s) réussi(s), ' + data.failed + ' échec(s) : voir les journaux', 'warning');
        else flash('Article envoyé à ' + data.sent + ' abonné(s)');
      } catch (e) { flashError(e); } finally { sending.value = false; }
    }, again ? 'Renvoyer' : 'Envoyer');
}
</script>

<template>
  <div class="kpis">
    <div v-for="k in kpis" :key="k.label" class="kpi simple"><span class="sm-mu">{{ k.label }}</span><span class="kpi-val">{{ k.value }}</span></div>
  </div>
  <div class="panel nl-send">
    <div class="col gap6">
      <h2 class="ph2">Envoyer un article</h2>
      <span class="sm-mu fs13">Chaque abonné reçoit l’article dans sa langue (titre, extrait, image et lien), avec un lien de désinscription en un clic.</span>
    </div>
    <p v-if="!posts.length" class="sm-mu">Publiez d’abord un article dans Blog.</p>
    <div v-else class="row-wrap gap8 center">
      <select class="ain nl-select" :value="chosen && chosen.id" aria-label="Article à envoyer" @change="postId = Number($event.target.value)">
        <option v-for="p in posts" :key="p.id" :value="p.id">{{ t(p.title) }}{{ p.newsletterSentAt ? ' · envoyé le ' + sentOn(p) : '' }}</option>
      </select>
      <button type="button" class="abtn primary" :disabled="sending || !subs.length" @click="sendPost"><span class="ms">send</span>{{ sending ? 'Envoi…' : (chosen && chosen.newsletterSentAt ? 'Renvoyer' : 'Envoyer') + ' à ' + subs.length + ' abonné(s)' }}</button>
    </div>
  </div>
  <div class="panel">
    <div v-if="!subs.length" class="empty-box">
      <span aria-hidden="true" class="ms acc big">mark_email_unread</span>
      <p class="fw6">Aucun inscrit pour le moment</p>
      <p class="mu14">Les inscriptions du formulaire du footer apparaîtront ici.</p>
    </div>
    <div v-else class="scroll-x">
      <table class="tbl wide">
        <thead><tr><th>E-mail</th><th>Langue</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <tr v-for="x in subs" :key="x.id"><td class="fw5">{{ x.email }}</td><td class="mono fs12">{{ (x.lang || 'fr').toUpperCase() }}</td><td class="mu">{{ fmtMD(x.date) }}</td><td class="r"><button type="button" class="ibtn" aria-label="Supprimer" @click="del(x)"><span class="ms">delete</span></button></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
