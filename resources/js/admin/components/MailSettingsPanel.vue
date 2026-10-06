<script setup>
// Serveur d'envoi des e-mails (SMTP) : notifications de nouveaux messages et réponses envoyées depuis l'administration.
// Le mot de passe n'est jamais réaffiché : laisser le champ vide conserve celui enregistré.
import { reactive, ref } from 'vue';
import { api } from '../../shared/util';
import { flash, flashError, state } from '../store';
import Panel from './Panel.vue';

const cur = state.data.mail || {};
const f = reactive({ enabled: !!cur.enabled, host: cur.host || '', port: cur.port || 587, encryption: cur.encryption || 'tls', username: cur.username || '', password: '', fromAddress: cur.fromAddress || '', fromName: cur.fromName || '' });
const testTo = ref((state.data.settings && state.data.settings.notifyEmail) || (state.user && state.user.email) || '');
const busy = ref('');
const PRESETS = [
  ['Gmail', { host: 'smtp.gmail.com', port: 587, encryption: 'tls' }, 'Mot de passe d’application Google (compte avec validation en 2 étapes)'],
  ['Outlook / Microsoft 365', { host: 'smtp.office365.com', port: 587, encryption: 'tls' }, ''],
  ['Hostinger', { host: 'smtp.hostinger.com', port: 465, encryption: 'ssl' }, ''],
  ['Brevo', { host: 'smtp-relay.brevo.com', port: 587, encryption: 'tls' }, 'Clé SMTP Brevo comme mot de passe'],
];
const hint = ref('');
const preset = (p) => { Object.assign(f, p[1]); hint.value = p[2]; };

async function saveMail() {
  busy.value = 'save';
  try {
    const { data } = await api.put('/admin/mail', f);
    state.data.mail = data;
    f.password = '';
    if (data.enabled) flash('Serveur d’envoi enregistré'); else flash('Envoi par SMTP désactivé : les e-mails ne partiront pas réellement', 'warning');
  } catch (e) { flashError(e); } finally { busy.value = ''; }
}
async function test() {
  busy.value = 'test';
  try {
    await api.post('/admin/mail/test', { to: testTo.value });
    flash('E-mail de test envoyé à ' + testTo.value);
  } catch (e) { flashError(e); } finally { busy.value = ''; }
}
</script>

<template>
  <Panel title="Envoi des e-mails (SMTP)" sub="Serveur utilisé pour les alertes de nouveaux messages et pour répondre aux visiteurs depuis la boîte de réception.">
    <div class="pad-form col gap16">
      <div class="row-wrap center gap8">
        <button type="button" role="switch" :aria-checked="f.enabled" class="switch-btn" @click="f.enabled = !f.enabled"><span class="switch" :class="{ on: f.enabled }"><span></span></span>{{ f.enabled ? 'Envoi par SMTP activé' : 'Envoi par SMTP désactivé' }}</button>
        <span class="sm-mu">Préréglages :</span>
        <button v-for="p in PRESETS" :key="p[0]" type="button" class="abtn xs" @click="preset(p)">{{ p[0] }}</button>
      </div>
      <p v-if="hint" class="sm-mu">{{ hint }}</p>
      <div class="fields">
        <label class="field"><span class="field-head"><span>Serveur SMTP</span></span><input v-model="f.host" class="ain" placeholder="smtp.exemple.com"></label>
        <label class="field"><span class="field-head"><span>Port</span></span><input v-model.number="f.port" type="number" class="ain" placeholder="587"></label>
        <label class="field"><span class="field-head"><span>Chiffrement</span></span>
          <select v-model="f.encryption" class="ain"><option value="tls">TLS (port 587)</option><option value="ssl">SSL (port 465)</option><option value="none">Aucun</option></select></label>
        <label class="field"><span class="field-head"><span>Identifiant</span></span><input v-model="f.username" class="ain" autocomplete="off" placeholder="adresse ou identifiant"></label>
        <label class="field"><span class="field-head"><span>Mot de passe</span></span><input v-model="f.password" type="password" class="ain" autocomplete="new-password" :placeholder="(state.data.mail || {}).hasPassword ? '•••••••• (enregistré, laisser vide pour le garder)' : 'mot de passe SMTP'"></label>
        <label class="field"><span class="field-head"><span>Adresse d’expédition</span></span><input v-model="f.fromAddress" type="email" class="ain" placeholder="contact@votre-domaine.com"></label>
        <label class="field"><span class="field-head"><span>Nom d’expédition</span></span><input v-model="f.fromName" class="ain" placeholder="Fawoziath SALOU"></label>
      </div>
      <div class="row-wrap center gap8">
        <button type="button" class="abtn primary" :disabled="!!busy" @click="saveMail"><span class="ms">save</span>{{ busy === 'save' ? 'Enregistrement…' : 'Enregistrer' }}</button>
        <span class="f-grow"></span>
        <input v-model="testTo" type="email" class="ain test-to" aria-label="Adresse du test" placeholder="adresse du test">
        <button type="button" class="abtn" :disabled="!!busy || !testTo" @click="test"><span class="ms">outgoing_mail</span>{{ busy === 'test' ? 'Envoi…' : 'Envoyer un e-mail de test' }}</button>
      </div>
      <p class="sm-mu">Statut : <strong :style="{ color: (state.data.mail || {}).ready ? 'var(--okFg)' : 'var(--warnFg)' }">{{ (state.data.mail || {}).ready ? 'serveur configuré' : 'aucun envoi réel (e-mails seulement journalisés)' }}</strong>. Enregistrez, puis envoyez un e-mail de test pour vérifier que l’envoi fonctionne.</p>
    </div>
  </Panel>
</template>
