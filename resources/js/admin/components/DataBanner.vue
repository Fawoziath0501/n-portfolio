<script setup>
import { useRouter } from 'vue-router';
import { api } from '../../shared/util';
import { ask, flash, go, isLocal, loadEvents, nf, providerOf, state } from '../store';

const router = useRouter();
const PROVIDERS = { umami: 'Umami', plausible: 'Plausible', ga4: 'Google Analytics 4', none: 'aucune source' };
const clear = () => ask('Réinitialiser le suivi intégré ?', 'Toutes les visites enregistrées seront effacées.', async () => {
  await api.delete('/admin/events');
  await loadEvents();
  flash('Suivi réinitialisé');
}, 'Réinitialiser');
</script>

<template>
  <div v-if="isLocal()" class="banner">
    <span aria-hidden="true" class="ms">sensors</span>
    <span class="f-grow"><strong>Suivi intégré activé.</strong> Visites et clics réellement enregistrés par le site ({{ nf(state.eventsTotal) }} événements). Pays, villes et mots-clés nécessitent Umami et Search Console.</span>
    <button type="button" class="abtn sm" @click="clear">Réinitialiser le suivi</button>
  </div>
  <div v-else class="banner demo">
    <span aria-hidden="true" class="ms">info</span>
    <span class="f-grow"><strong>Données de démonstration.</strong> Les chiffres de visites sont simulés tant qu’aucune source n’est connectée ({{ PROVIDERS[providerOf()] || 'aucune source' }}). Messages, demandes et inscrits sont réels.</span>
    <button type="button" class="abtn sm" @click="go(router, 'settings')">Connecter une source</button>
  </div>
</template>
