<script setup>
import { vm } from '../store';
</script>

<template>
  <section id="languages" aria-labelledby="lang-title" class="sec-sm alt bt">
    <div class="wrap row-wrap gap-lang">
      <div class="lang-col">
        <p class="pill"><span class="ms">translate</span>{{ vm.L.langLabel }}</p>
        <dl id="lang-title" class="lang-list">
          <div v-for="lg in vm.languages" :key="lg.name">
            <dt>{{ lg.name }}</dt>
            <dd>{{ lg.level }}</dd>
          </div>
        </dl>
      </div>
      <div class="lang-col">
        <p class="pill"><span class="ms">favorite</span>{{ vm.L.soft }}</p>
        <ul class="soft">
          <li v-for="sk in vm.softList" :key="sk">{{ sk }}</li>
        </ul>
        <template v-if="vm.certs.length">
          <p class="pill"><span class="ms">verified</span>{{ vm.L.certLabel }}</p>
          <div v-for="ct in vm.certs" :key="ct.id" class="cert">
            <h3>{{ ct.name }}</h3>
            <span>{{ ct.issuer }} · {{ ct.date }}</span>
            <a v-if="ct.verify" :href="ct.verify" target="_blank" rel="noopener">{{ vm.L.verify }} ↗</a>
          </div>
        </template>
      </div>
    </div>
  </section>
</template>
