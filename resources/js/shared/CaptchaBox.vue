<script setup>
// Widget anti-spam (Cloudflare Turnstile, Google reCAPTCHA case à cocher ou hCaptcha), réglé dans l'administration.
// Le script du service n'est chargé que sur les pages où un formulaire protégé s'affiche. v-model : jeton à envoyer.
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({ config: Object, lang: { type: String, default: 'fr' }, theme: { type: String, default: 'light' } });
const token = defineModel({ type: String, default: '' });
const el = ref(null);
let id = null;

const SRC = {
  turnstile: 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=',
  recaptcha: 'https://www.google.com/recaptcha/api.js?render=explicit&onload=',
  hcaptcha: 'https://js.hcaptcha.com/1/api.js?render=explicit&onload=',
};
const GLOBAL = { turnstile: 'turnstile', recaptcha: 'grecaptcha', hcaptcha: 'hcaptcha' };
const loading = {};

/** Charge une seule fois le script du service, puis renvoie son objet global. */
function load(p) {
  if (window[GLOBAL[p]] && window[GLOBAL[p]].render) return Promise.resolve(window[GLOBAL[p]]);
  if (!loading[p]) {
    loading[p] = new Promise((resolve, reject) => {
      const cb = '__cap_' + p;
      window[cb] = () => resolve(window[GLOBAL[p]]);
      const s = document.createElement('script');
      s.src = SRC[p] + cb + (p === 'recaptcha' || p === 'hcaptcha' ? '&hl=' + props.lang : '');
      s.async = true; s.defer = true;
      s.onerror = () => { loading[p] = null; reject(new Error('captcha')); };
      document.head.appendChild(s);
    });
  }
  return loading[p];
}

onMounted(async () => {
  const { provider, siteKey } = props.config || {};
  if (!provider || !siteKey) return;
  try {
    const api = await load(provider);
    if (!el.value) return;
    const opts = { sitekey: siteKey, theme: props.theme, callback: (t) => { token.value = t; }, 'expired-callback': () => { token.value = ''; }, 'error-callback': () => { token.value = ''; } };
    if (provider === 'turnstile') opts.language = props.lang;
    id = api.render(el.value, opts);
  } catch (e) { /* service injoignable : le serveur décidera */ }
});

/** Nouveau défi après un envoi (un jeton ne sert qu'une fois). */
function reset() {
  token.value = '';
  const api = window[GLOBAL[(props.config || {}).provider]];
  try { if (api && id !== null) api.reset(id); } catch (e) { /* widget déjà retiré */ }
}
defineExpose({ reset });

onBeforeUnmount(() => {
  const api = window[GLOBAL[(props.config || {}).provider]];
  try { if (api && api.remove && id !== null) api.remove(id); } catch (e) { /* rien */ }
});
</script>

<template>
  <div ref="el" class="captcha-box"></div>
</template>
