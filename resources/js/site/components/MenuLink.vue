<script setup>
// Lien de menu : page du site (RouterLink) ou adresse externe (nouvel onglet).
defineProps({ item: Object });
</script>

<template>
  <a v-if="item.external" :href="item.href" target="_blank" rel="noopener"><slot /></a>
  <RouterLink v-else :to="item.href"><slot /></RouterLink>
</template>
