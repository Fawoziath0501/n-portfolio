<script setup>
defineProps({ it: Object });
</script>

<template>
  <div class="tl-card" :class="{ on: it.on }">
    <span class="tl-kind">{{ it.kind }}</span>
    <h3 class="tl-title">{{ it.title }}</h3>
    <p class="tl-org">{{ it.org }}</p>
    <p class="tl-period">{{ it.period }}</p>
    <div v-if="it.desc" class="tl-desc rich" v-html="it.desc"></div>
    <ul v-if="it.duties.length" class="tl-duties">
      <li v-for="du in it.duties" :key="du.n"><span class="sq6"></span>{{ du.text }}</li>
    </ul>
  </div>
</template>
