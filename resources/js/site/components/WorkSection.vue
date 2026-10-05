<script setup>
import { state, vm } from '../store';
</script>

<template>
  <section id="work" aria-labelledby="work-title" class="sec alt">
    <div class="wrap col gap36">
      <div v-if="vm.isHome" data-reveal class="sec-head">
        <div class="col gap12 mw640">
          <p class="mono-eyebrow">{{ vm.L.workLabel }}</p>
          <h2 id="work-title" class="h2">{{ vm.L.workTitle }}</h2>
          <p class="muted-17">{{ vm.L.workIntro }}</p>
        </div>
        <RouterLink :to="vm.hrefs.work" class="btn btn-outline md">{{ vm.L.allWork }} <span class="mono-12">{{ vm.projCount }}</span></RouterLink>
      </div>

      <div v-if="vm.isWork" role="group" :aria-label="vm.L.filterAria" class="row-wrap gap8">
        <button v-for="fl in vm.filters" :key="fl.key" type="button" class="filter" :class="{ on: fl.on }" :aria-pressed="fl.on" @click="state.filter = fl.key">
          {{ fl.label }} <span class="mono-12 op8">{{ fl.count }}</span>
        </button>
      </div>

      <ul class="cards">
        <li v-for="pj in vm.cards" :key="pj.id" data-reveal>
          <article class="card">
            <RouterLink :to="pj.href" class="card-cover" :style="{ background: pj.bg }">
              <span class="card-cover-top" :style="{ color: pj.sub }"><span>{{ pj.num }}</span><span>{{ pj.kind }}</span></span>
              <span class="card-cover-title" :style="{ color: pj.fg }">{{ pj.title }}</span>
            </RouterLink>
            <div class="card-body">
              <span class="mono-12 muted">{{ pj.cat }}</span>
              <h3 class="card-title"><RouterLink :to="pj.href">{{ pj.title }}</RouterLink></h3>
              <p class="card-text">{{ pj.summary }}</p>
              <ul v-if="pj.tags.length" class="tags"><li v-for="tg in pj.tags" :key="tg">{{ tg }}</li></ul>
              <div class="card-foot">
                <RouterLink :to="pj.href" class="blue-link">{{ vm.L.readCase }} →</RouterLink>
                <a v-if="pj.link" :href="pj.link" target="_blank" rel="noopener" class="ext-link">{{ pj.host }} ↗</a>
              </div>
            </div>
          </article>
        </li>
      </ul>
    </div>
  </section>
</template>
