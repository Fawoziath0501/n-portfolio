<script setup>
import { state, vm } from '../store';
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
        <RouterLink v-if="vm.isHome" :to="vm.hrefs.about" class="btn btn-outline md">{{ vm.L.fullPath }}</RouterLink>
      </div>

      <div class="tl" :style="{ gap: vm.tl.gap }">
        <span aria-hidden="true" class="tl-line" :style="{ left: vm.tl.line }"></span>
        <article v-for="(it, i) in vm.tlItems" :key="i" data-reveal class="tl-item" :style="{ gridTemplateColumns: vm.tl.cols, columnGap: vm.tl.colGap }"
          @mouseenter="state.tlh = i" @mouseleave="state.tlh = null">
          <span aria-hidden="true" class="tl-dot" :style="{ gridColumn: vm.tl.dotCol, background: it.current || it.on ? '#2448C8' : '#FFFFFF', transform: it.on ? 'scale(1.35)' : 'none' }"></span>
          <div class="tl-card" :class="{ on: it.on }" :style="{ gridColumn: it.col }">
            <span class="tl-kind">{{ it.kind }}</span>
            <h3 class="tl-title">{{ it.title }}</h3>
            <p class="tl-org">{{ it.org }}</p>
            <p class="tl-period">{{ it.period }}</p>
            <p v-if="it.desc" class="tl-desc">{{ it.desc }}</p>
            <ul v-if="it.duties.length" class="tl-duties">
              <li v-for="du in it.duties" :key="du.n"><span class="sq6"></span>{{ du.text }}</li>
            </ul>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
