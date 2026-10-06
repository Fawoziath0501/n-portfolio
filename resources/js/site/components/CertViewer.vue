<script setup>
// Visionneuse de certificat : aperçu réduit et filigrané (jamais le fichier original), affiché en image de fond
// d'un bloc, sans menu « Enregistrer l'image », ni glisser-déposer. Échap ou un clic à côté ferme la fenêtre.
import { onBeforeUnmount, onMounted } from 'vue';
import { state } from '../store';

const close = () => { state.certView = null; };
const onKey = (e) => { if (e.key === 'Escape') close(); };
onMounted(() => { window.addEventListener('keydown', onKey); document.documentElement.style.overflow = 'hidden'; });
onBeforeUnmount(() => { window.removeEventListener('keydown', onKey); document.documentElement.style.overflow = ''; });
</script>

<template>
  <div class="cv-overlay" role="dialog" aria-modal="true" :aria-label="state.certView.title" @click.self="close" @contextmenu.prevent>
    <div class="cv-box">
      <div class="cv-head">
        <strong>{{ state.certView.title }}</strong>
        <button type="button" class="cv-close" aria-label="Fermer" @click="close"><span class="ms" aria-hidden="true">close</span></button>
      </div>
      <div class="cv-img" role="img" :aria-label="state.certView.title" :style="{ backgroundImage: 'url(&quot;' + state.certView.src + '&quot;)' }" @contextmenu.prevent @dragstart.prevent></div>
    </div>
  </div>
</template>
