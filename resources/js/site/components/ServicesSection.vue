<script setup>
import { askService, vm } from '../store';
import { pad } from '../../shared/util';
</script>

<template>
  <section id="services" aria-labelledby="services-title" class="sec">
    <div class="wrap col gap40">
      <div v-if="vm.isHome" data-reveal class="sec-head">
        <div class="col gap12 mw640">
          <p class="mono-eyebrow">{{ vm.L.servicesLabel }}</p>
          <h2 id="services-title" class="h2 nobal">{{ vm.L.servicesTitle }}</h2>
        </div>
        <RouterLink :to="vm.hrefs.services" class="btn btn-outline md">{{ vm.L.allServices }}</RouterLink>
      </div>
      <ol class="svc-grid">
        <li v-for="(sv, i) in vm.services" :key="sv.id" data-reveal>
          <div class="svc-card">
            <div class="row-between start">
              <span class="svc-ico ms">{{ sv.icon }}</span>
              <span class="svc-num">{{ pad(i + 1) }}</span>
            </div>
            <h3 class="svc-card-title">{{ sv.title }}</h3>
            <div class="muted-16 rich" v-html="sv.desc"></div>
            <button type="button" class="svc-ask" @click="askService(sv.title, sv.icon)">{{ vm.L.askService }} <span class="ms">arrow_forward</span></button>
          </div>
        </li>
      </ol>
    </div>
  </section>
</template>
