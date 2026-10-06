<script setup>
import { api } from '../../shared/util';
import { ask, flash, isLocal, loadEvents, nf, state } from '../store';

const clear = () => ask('Réinitialiser le suivi intégré ?', 'Toutes les visites enregistrées seront effacées.', async () => {
  await api.delete('/admin/events');
  await loadEvents();
  flash('Suivi réinitialisé');
}, 'Réinitialiser');
</script>

<template>
  <div v-if="isLocal()" class="banner">
    <span aria-hidden="true" class="ms">sensors</span>
    <span class="f-grow"><strong>Suivi intégré activé.</strong> Visites et clics réellement enregistrés par le site ({{ nf(state.eventsTotal || 0) }} événements), sans cookie ni adresse IP.</span>
    <button type="button" class="abtn sm" @click="clear">Réinitialiser le suivi</button>
  </div>
</template>
