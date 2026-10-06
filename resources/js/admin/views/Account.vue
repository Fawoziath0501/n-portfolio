<script setup>
// Compte administrateur : nom, e-mail, mot de passe. Toute modification demande le mot de passe actuel.
import { reactive, ref } from 'vue';
import { api } from '../../shared/util';
import { flash, state } from '../store';
import Panel from '../components/Panel.vue';

const f = reactive({ name: (state.user && state.user.name) || '', email: (state.user && state.user.email) || '', current_password: '', password: '', password_confirmation: '' });
const errors = ref({});
const busy = ref(false);

async function submit() {
  busy.value = true;
  errors.value = {};
  try {
    const { data } = await api.put('/admin/account', f);
    state.user = data.user;
    flash(f.password ? 'Mot de passe modifié' : 'Compte enregistré');
    Object.assign(f, { current_password: '', password: '', password_confirmation: '' });
  } catch (e) {
    const r = e.response;
    if (r && r.status === 401) state.user = null;
    else errors.value = (r && r.data && r.data.errors) || { form: ['Enregistrement impossible, réessayez.'] };
  } finally {
    busy.value = false;
  }
}
const err = (k) => (errors.value[k] || [])[0];
</script>

<template>
  <form class="col gap20" novalidate @submit.prevent="submit">
    <Panel title="Identité" sub="Nom affiché dans l’administration et e-mail de connexion.">
      <div class="pad-form"><div class="fields">
        <label class="field"><span class="field-head"><span>Nom</span></span><input v-model="f.name" class="ain" autocomplete="name" required></label>
        <label class="field"><span class="field-head"><span>E-mail de connexion</span></span><input v-model="f.email" type="email" class="ain" autocomplete="username" required>
          <span v-if="err('email')" role="alert" class="field-err">{{ err('email') }}</span></label>
      </div></div>
    </Panel>

    <Panel title="Mot de passe" sub="Laissez vide pour le conserver. 12 caractères minimum, avec des lettres et des chiffres. Les autres appareils connectés seront déconnectés.">
      <div class="pad-form"><div class="fields">
        <label class="field"><span class="field-head"><span>Nouveau mot de passe</span></span><input v-model="f.password" type="password" class="ain" autocomplete="new-password">
          <span v-if="err('password')" role="alert" class="field-err">{{ err('password') }}</span></label>
        <label class="field"><span class="field-head"><span>Confirmer le nouveau mot de passe</span></span><input v-model="f.password_confirmation" type="password" class="ain" autocomplete="new-password"></label>
      </div></div>
    </Panel>

    <Panel title="Confirmation" sub="Saisissez votre mot de passe actuel pour enregistrer les modifications.">
      <div class="pad-form"><div class="fields">
        <label class="field"><span class="field-head"><span>Mot de passe actuel</span></span><input v-model="f.current_password" type="password" class="ain" autocomplete="current-password" required>
          <span v-if="err('current_password')" role="alert" class="field-err">{{ err('current_password') }}</span></label>
      </div>
      <p v-if="err('form') || err('name')" role="alert" class="field-err">{{ err('form') || err('name') }}</p>
      <div class="row gap8 pad-top">
        <button type="submit" class="abtn primary" :disabled="busy || !f.current_password"><span class="ms">check</span>{{ busy ? 'Enregistrement…' : 'Enregistrer' }}</button>
      </div></div>
    </Panel>
  </form>
</template>
