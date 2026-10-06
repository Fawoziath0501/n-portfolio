<script setup>
// Protection anti-spam des formulaires : service de captcha, clés et formulaires protégés.
// La clé secrète n'est jamais réaffichée : laisser le champ vide conserve celle enregistrée.
import { computed, reactive, ref } from 'vue';
import { api } from '../../shared/util';
import { flash, flashError, state } from '../store';
import Panel from './Panel.vue';

const cur = state.data.captcha || {};
const f = reactive({ provider: cur.provider || 'none', siteKey: cur.siteKey || '', secret: '', forms: { contact: true, service: true, newsletter: true, login: true, ...(cur.forms || {}) } });
const busy = ref(false);
const PROVIDERS = [
  ['none', 'Désactivé', ''],
  ['turnstile', 'Cloudflare Turnstile', 'Gratuit, discret (souvent sans rien à cocher), respectueux de la vie privée. Recommandé. Clés : dash.cloudflare.com → Turnstile → Ajouter un widget.', 'https://dash.cloudflare.com/?to=/:account/turnstile'],
  ['recaptcha', 'Google reCAPTCHA (case à cocher)', 'Gratuit. Choisissez « reCAPTCHA v2 → case à cocher » lors de la création des clés.', 'https://www.google.com/recaptcha/admin/create'],
  ['hcaptcha', 'hCaptcha', 'Gratuit, alternative à Google. Clé secrète dans les réglages du compte.', 'https://dashboard.hcaptcha.com/sites'],
];
const FORMS = [['contact', 'Formulaire de contact'], ['service', 'Demandes de service'], ['newsletter', 'Inscription à la newsletter'], ['login', 'Connexion à l’administration']];
const info = computed(() => PROVIDERS.find((p) => p[0] === f.provider));
const status = computed(() => {
  const c = state.data.captcha || {};
  return c.provider && c.provider !== 'none' && c.siteKey && c.hasSecret ? ['actif', 'var(--okFg)'] : ['désactivé : seul le champ piège invisible filtre les robots', 'var(--warnFg)'];
});

async function saveCaptcha() {
  busy.value = true;
  try {
    const { data } = await api.put('/admin/captcha', f);
    state.data.captcha = data;
    f.secret = '';
    if (data.provider === 'none') flash('Captcha désactivé : seul le champ piège invisible reste actif', 'warning');
    else flash('Protection anti-spam enregistrée');
  } catch (e) { flashError(e); } finally { busy.value = false; }
}
</script>

<template>
  <Panel title="Protection anti-spam (captcha)" sub="Vérifie que les formulaires sont envoyés par une personne. Un champ piège invisible bloque déjà les robots les plus simples.">
    <div class="pad-form col gap16">
      <label class="field"><span class="field-head"><span>Service</span></span>
        <select v-model="f.provider" class="ain"><option v-for="p in PROVIDERS" :key="p[0]" :value="p[0]">{{ p[1] }}</option></select></label>
      <p v-if="info && info[2]" class="sm-mu">{{ info[2] }} <a v-if="info[3]" :href="info[3]" target="_blank" rel="noopener">Obtenir les clés ↗</a></p>
      <template v-if="f.provider !== 'none'">
        <div class="fields">
          <label class="field"><span class="field-head"><span>Clé du site (publique)</span></span><input v-model="f.siteKey" class="ain" autocomplete="off" spellcheck="false"></label>
          <label class="field"><span class="field-head"><span>Clé secrète</span></span><input v-model="f.secret" type="password" class="ain" autocomplete="new-password" :placeholder="(state.data.captcha || {}).hasSecret ? '•••••••• (enregistrée, laisser vide pour la garder)' : 'clé secrète'"></label>
        </div>
        <div class="col gap8">
          <span class="sm-mu">Formulaires protégés :</span>
          <div class="row-wrap gap16">
            <label v-for="[k, lb] in FORMS" :key="k" class="row center gap8 cap-chk"><input v-model="f.forms[k]" type="checkbox">{{ lb }}</label>
          </div>
        </div>
        <p class="sm-mu">Pensez à déclarer le nom de domaine du site (et « localhost » pour vos essais) dans la console du service choisi.</p>
      </template>
      <div class="row-wrap center gap8">
        <button type="button" class="abtn primary" :disabled="busy" @click="saveCaptcha"><span class="ms">save</span>{{ busy ? 'Enregistrement…' : 'Enregistrer' }}</button>
        <span class="sm-mu">Statut : <strong :style="{ color: status[1] }">{{ status[0] }}</strong></span>
      </div>
    </div>
  </Panel>
</template>
