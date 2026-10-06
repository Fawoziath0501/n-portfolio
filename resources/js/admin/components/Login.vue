<script setup>
import { ref } from 'vue';
import { errorText, login } from '../store';
import CaptchaBox from '../../shared/CaptchaBox.vue';

// Captcha de connexion, si activé dans Paramètres → Protection anti-spam.
const cap = window.__LOGIN_CAPTCHA__;
const token = ref('');
const capN = ref(0);
const hp = ref('');

const email = ref('');
const password = ref('');
const remember = ref(false);
const error = ref('');
const busy = ref(false);

async function submit() {
  error.value = '';
  if (cap && !token.value) { error.value = 'Merci de confirmer que vous n’êtes pas un robot.'; return; }
  busy.value = true;
  try {
    await login(email.value, password.value, remember.value, { captcha: token.value, website: hp.value });
  } catch (e) {
    error.value = errorText(e);
    if (cap) { token.value = ''; capN.value++; }
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div class="login-wrap">
    <form class="login" @submit.prevent="submit">
      <div class="row center gap10"><span class="login-mark">[ FS ]</span><span class="login-name">Fawoziath<span>.dev</span></span></div>
      <div class="col gap6"><h1>Administration</h1><p class="mu14">Connectez-vous pour gérer le contenu du portfolio.</p></div>
      <label class="lfld">E-mail<input v-model="email" type="email" autocomplete="username" required class="ain"></label>
      <label class="lfld">Mot de passe<input v-model="password" type="password" autocomplete="current-password" required class="ain"></label>
      <label class="login-remember"><input v-model="remember" type="checkbox">Rester connecté sur cet appareil</label>
      <input v-model="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position: absolute; left: -10000px; width: 1px; height: 1px; opacity: 0">
      <CaptchaBox v-if="cap" :key="capN" v-model="token" :config="cap" />
      <p v-if="error" role="alert" class="login-err">{{ error }}</p>
      <button type="submit" class="abtn primary login-btn" :disabled="busy">{{ busy ? 'Connexion…' : 'Se connecter' }}</button>
      <a href="/" class="login-back">← Retour au site</a>
    </form>
  </div>
</template>
