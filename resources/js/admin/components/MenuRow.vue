<script setup>
// Une ligne de menu : page visée (ou adresse libre), libellés FR / EN, ordre, affichage, suppression.
defineProps({ it: Object, pages: Array, first: Boolean, last: Boolean, cta: Boolean });
defineEmits(['page', 'set', 'text', 'move', 'remove']);
</script>

<template>
  <div class="mrow" :class="{ off: !it.visible, cta }">
    <span v-if="!cta" class="mrow-move">
      <button type="button" class="gbtn" aria-label="Monter" title="Monter" :disabled="first" @click="$emit('move', -1)"><span class="ms">keyboard_arrow_up</span></button>
      <button type="button" class="gbtn" aria-label="Descendre" title="Descendre" :disabled="last" @click="$emit('move', 1)"><span class="ms">keyboard_arrow_down</span></button>
    </span>
    <span class="mrow-page">
      <select class="ain" aria-label="Page visée" :value="it.page" @change="$emit('page', $event.target.value)">
        <option v-for="p in pages" :key="p[0]" :value="p[0]">{{ p[1] }}</option>
      </select>
      <input v-if="it.page === 'url'" class="ain" placeholder="https://… ou /fr/…" aria-label="Adresse du lien" :value="it.url" @input="$emit('set', 'url', $event.target.value)">
    </span>
    <input class="ain" aria-label="Libellé FR" placeholder="Libellé FR" :value="(it.label || {}).fr" @input="$emit('text', 'fr', $event.target.value)">
    <input class="ain" aria-label="Libellé EN" placeholder="Libellé EN" :value="(it.label || {}).en" @input="$emit('text', 'en', $event.target.value)">
    <span class="mrow-acts">
      <button type="button" class="gbtn" :class="{ on: it.visible }" :aria-label="it.visible ? 'Masquer' : 'Afficher'" :title="it.visible ? 'Affiché — cliquer pour masquer' : 'Masqué — cliquer pour afficher'" @click="$emit('set', 'visible', !it.visible)"><span class="ms">{{ it.visible ? 'visibility' : 'visibility_off' }}</span></button>
      <button v-if="!cta" type="button" class="gbtn danger" aria-label="Retirer" title="Retirer" @click="$emit('remove')"><span class="ms">delete</span></button>
    </span>
  </div>
</template>
