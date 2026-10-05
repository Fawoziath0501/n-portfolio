<script setup>
defineProps({ rows: Array });
</script>

<template>
  <div class="bars">
    <div v-for="(b, i) in rows" :key="i" class="bar">
      <div class="bar-top">
        <span class="bar-label"><span aria-hidden="true" class="ms">{{ b.icon }}</span><span>{{ b.label }}</span></span>
        <span class="bar-val"><strong>{{ b.value }}</strong> <span>{{ b.pct }}</span></span>
      </div>
      <div class="bar-track"><div :style="{ width: b.w }"></div></div>
    </div>
  </div>
</template>
