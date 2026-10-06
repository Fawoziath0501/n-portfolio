<script setup>
// Lien de dépôt de témoignage à envoyer aux clients et collègues (les avis arrivent ici en brouillon).
import { flash, flashError } from '../store';

const fr = window.location.origin + '/fr/temoignage';
const en = window.location.origin + '/en/testimonial';
const copy = async (url) => { try { await navigator.clipboard.writeText(url); flash('Lien copié'); } catch (e) { flashError(e); } };
</script>

<template>
  <div class="panel tl-box">
    <span class="ms acc" aria-hidden="true">forward_to_inbox</span>
    <div class="f1 col gap6">
      <strong>Recueillir des témoignages</strong>
      <span class="sm-mu fs13">Envoyez ce lien à vos clients et anciens collègues. Leurs avis arrivent ici en brouillon (vous êtes prévenue par e-mail) : relisez, traduisez si besoin, puis publiez.</span>
      <div class="row-wrap gap8 center">
        <code class="tl-url">{{ fr }}</code>
        <button type="button" class="abtn xs" @click="copy(fr)"><span class="ms">content_copy</span>Copier (FR)</button>
        <button type="button" class="abtn xs" @click="copy(en)"><span class="ms">content_copy</span>Copier (EN)</button>
        <a class="abtn xs" :href="fr" target="_blank" rel="noopener"><span class="ms">open_in_new</span>Voir la page</a>
      </div>
    </div>
  </div>
</template>
