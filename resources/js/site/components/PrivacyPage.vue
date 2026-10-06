<script setup>
import { vm } from '../store';
</script>

<template>
  <section aria-labelledby="privacy-title" class="legal">
    <div class="wrap">
      <div class="legal-inner">
        <p class="pill"><span class="ms">shield_person</span>{{ vm.L.privacyLink }}</p>
        <h1 id="privacy-title" class="h2">{{ vm.privacy.title }}</h1>
        <p v-if="vm.privacy.updated" class="legal-updated">{{ vm.privacy.updated }}</p>
        <div class="legal-body rich" v-html="vm.privacy.html"></div>
      </div>
    </div>
  </section>
</template>
