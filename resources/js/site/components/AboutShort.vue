<script setup>
import { vm } from '../store';
</script>

<template>
  <section id="about" aria-labelledby="about-short-title" class="sec-about">
    <div class="wrap split">
      <div data-reveal class="split-l">
        <p class="pill"><span class="ms">person</span>{{ vm.L.aboutLabel }}</p>
        <h2 id="about-short-title" class="h2">{{ vm.L.aboutShortTitle }}</h2>
      </div>
      <div data-reveal class="split-r">
        <p class="lead">{{ vm.L.aboutShortText }}</p>
        <ul class="chips">
          <li v-for="ac in vm.aboutChips" :key="ac.icon"><span class="ms">{{ ac.icon }}</span>{{ ac.text }}</li>
        </ul>
        <RouterLink :to="vm.hrefs.about" class="ulink">{{ vm.L.moreAbout }} <span class="ms">arrow_forward</span></RouterLink>
      </div>
    </div>
  </section>
</template>
