<script setup>
import { vm } from '../store';
</script>

<template>
  <section aria-labelledby="cta-title" class="sec-sm alt bt">
    <div class="wrap">
      <div data-reveal class="cta">
        <div class="cta-l">
          <span class="mono-eyebrow wide">{{ vm.L.ctaEyebrow }}</span>
          <h2 id="cta-title" class="cta-title">{{ vm.ctaTitle }}</h2>
          <p class="muted-17">{{ vm.L.ctaText }}</p>
        </div>
        <div class="cta-r">
          <RouterLink :to="vm.hrefs.contact" class="cta-btn light"><span class="row center gap10"><span class="ms blue">send</span>{{ vm.L.ctaContact }}</span><span class="ms">arrow_forward</span></RouterLink>
          <a v-if="vm.wa" :href="vm.wa" target="_blank" rel="noopener" class="cta-btn line"><span class="row center gap10"><span class="ms">chat</span>WhatsApp</span><span class="ms">arrow_forward</span></a>
        </div>
      </div>
    </div>
  </section>
</template>
