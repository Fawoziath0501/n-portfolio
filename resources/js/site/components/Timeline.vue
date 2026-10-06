<script setup>
import { state, vm } from '../store';
import TimelineCard from './TimelineCard.vue';
</script>

<template>
  <section id="experience" aria-labelledby="exp-title" class="sec bt">
    <div class="wrap col gap48">
      <div data-reveal class="sec-head">
        <div class="col gap14 mw680">
          <p class="pill"><span class="ms">event_note</span>{{ vm.L.expLabel }}</p>
          <h2 id="exp-title" class="h2">{{ vm.L.expTitle }}</h2>
          <p class="muted-17">{{ vm.L.expIntro }}</p>
        </div>
        <RouterLink v-if="vm.isHome" :to="vm.hrefs.about" class="btn btn-outline md">{{ vm.L.fullPath }} <span class="mono-12">{{ vm.tlCount }}</span><span class="ms">arrow_forward</span></RouterLink>
      </div>

      <!-- Ordinateur : cartes en alternance ; chacune couvre deux rangées et commence une rangée après la précédente,
           ce qui les emboîte sans vide tout en gardant l'ordre chronologique le long de la ligne. -->
      <div v-if="!vm.mobile" class="tl2">
        <span aria-hidden="true" class="tl2-line"></span>
        <article v-for="it in vm.tlItems" :key="it.idx" data-reveal class="tl2-item" :class="it.idx % 2 ? 'right' : 'left'"
          :style="{ gridColumn: it.idx % 2 ? 2 : 1, gridRow: (it.idx + 1) + ' / span 2' }"
          @mouseenter="state.tlh = it.idx" @mouseleave="state.tlh = null">
          <span aria-hidden="true" class="tl2-dot" :class="{ fill: it.current || it.on, on: it.on }"></span>
          <TimelineCard :it="it" />
        </article>
      </div>

      <!-- Mobile : une colonne, ligne à gauche. -->
      <div v-else class="tl" :style="{ gap: vm.tl.gap }">
        <span aria-hidden="true" class="tl-line" :style="{ left: vm.tl.line }"></span>
        <article v-for="it in vm.tlItems" :key="it.idx" data-reveal class="tl-item" :style="{ gridTemplateColumns: vm.tl.cols, columnGap: vm.tl.colGap }">
          <span aria-hidden="true" class="tl-dot" :style="{ gridColumn: vm.tl.dotCol, background: it.current ? '#2448C8' : '#FFFFFF' }"></span>
          <TimelineCard :it="it" :style="{ gridColumn: it.col }" />
        </article>
      </div>
    </div>
  </section>
</template>
