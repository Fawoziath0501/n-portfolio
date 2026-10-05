<script setup>
defineProps({ title: String, sub: String });
</script>

<template>
  <div class="panel">
    <div v-if="title" class="panel-head">
      <div class="col gap2"><h2>{{ title }}</h2><span v-if="sub">{{ sub }}</span></div>
      <slot name="action" />
    </div>
    <slot />
  </div>
</template>
