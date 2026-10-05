<script setup>
import { computed } from 'vue';
import { state } from '../store';

const items = computed(() => (state.data.media || []).filter((m) => (state.pick.accept === 'doc' ? m.kind === 'doc' : m.kind !== 'doc')));
const choose = (m) => { state.pick.onPick(m); state.pick = null; };
const backdrop = (e) => { if (e.target === e.currentTarget) state.pick = null; };
</script>

<template>
  <div class="overlay center z85" @click="backdrop">
    <div role="dialog" aria-modal="true" aria-labelledby="pk-title" class="picker">
      <div class="picker-head"><h2 id="pk-title">Choisir dans la médiathèque</h2><button type="button" class="ibtn" aria-label="Fermer" @click="state.pick = null"><span class="ms">close</span></button></div>
      <div class="picker-body">
        <p v-if="!items.length" class="empty-txt">Aucun fichier. Importez-en depuis la Médiathèque.</p>
        <div class="picker-grid">
          <button v-for="m in items" :key="m.id" type="button" class="pick-item" @click="choose(m)">
            <span class="pick-img" :style="{ backgroundImage: m.kind === 'doc' ? 'none' : 'url(&quot;' + m.src + '&quot;)' }"><span v-if="m.kind === 'doc'" class="ms">picture_as_pdf</span></span>
            <span class="pick-name">{{ m.name }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
