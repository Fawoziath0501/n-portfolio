<script setup>
import { computed } from 'vue';
import { BRAND_PATHS, brandOf } from './brandIcons';

// Logo officiel du réseau (déduit de l'adresse), ou icône générique si le réseau n'est pas reconnu.
const props = defineProps({ url: String, label: String, size: { type: Number, default: 20 } });
const path = computed(() => BRAND_PATHS[brandOf(props.url, props.label)] || null);
</script>

<template>
  <svg v-if="path" :width="size" :height="size" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path :d="path" /></svg>
  <span v-else class="ms" aria-hidden="true" :style="{ fontSize: size + 'px' }">link</span>
</template>
