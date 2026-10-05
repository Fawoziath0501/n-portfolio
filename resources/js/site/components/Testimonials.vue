<script setup>
import { vm } from '../store';
</script>

<template>
  <section id="testimonials" aria-labelledby="testi-title" class="sec alt">
    <div class="wrap col gap40">
      <h2 id="testi-title" class="h2">{{ vm.L.testiTitle }}</h2>
      <div class="testis">
        <figure v-for="ts in vm.testis" :key="ts.id" class="testi">
          <blockquote>« {{ ts.quote }} »</blockquote>
          <figcaption>
            <span class="testi-av">{{ ts.initial }}</span>
            <span class="col"><strong>{{ ts.name }}</strong><span>{{ ts.role }}</span></span>
          </figcaption>
        </figure>
      </div>
    </div>
  </section>
</template>
