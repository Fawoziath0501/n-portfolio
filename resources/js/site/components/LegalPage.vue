<script setup>
import { vm } from '../store';
</script>

<template>
  <section aria-labelledby="legal-title" class="legal">
    <div class="wrap">
      <div class="legal-inner">
        <p class="pill"><span class="ms">gavel</span>{{ vm.brand.full }}</p>
        <h1 id="legal-title" class="h2">{{ vm.legal.title }}</h1>
        <p v-if="vm.legal.updated" class="legal-updated">{{ vm.legal.updated }}</p>
        <div class="legal-body rich" v-html="vm.legal.html"></div>
      </div>
    </div>
  </section>
</template>
