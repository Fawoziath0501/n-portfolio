<script setup>
import { ref } from 'vue';
import { errorText, login } from '../store';

const email = ref('');
const password = ref('');
const error = ref('');
const busy = ref(false);

async function submit() {
  error.value = '';
  busy.value = true;
  try {
    await login(email.value, password.value);
  } catch (e) {
    error.value = errorText(e);
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
      <p v-if="error" role="alert" class="login-err">{{ error }}</p>
      <button type="submit" class="abtn primary login-btn" :disabled="busy">{{ busy ? 'Connexion…' : 'Se connecter' }}</button>
      <a href="/" class="login-back">← Retour au site</a>
    </form>
  </div>
</template>
