<script setup>
import { vm } from '../store';
</script>

<template>
  <article aria-labelledby="page-title">
    <div class="wrap case-top">
      <div class="case-cover" :style="{ background: vm.cs.bg }">
        <span class="mono-12" :style="{ color: vm.cs.sub }">{{ vm.L.caseLabel }} {{ vm.cs.num }}</span>
        <span class="case-cover-title" :style="{ color: vm.cs.fg }">{{ vm.cs.title }}</span>
        <span class="mono-12" :style="{ color: vm.cs.sub }">{{ vm.cs.gallery.length ? '' : vm.L.noShots }}</span>
      </div>
      <aside class="case-aside">
        <dl class="case-meta">
          <div v-for="m in vm.cs.meta" :key="m.label">
            <dt>{{ m.label }}</dt>
            <dd>{{ m.value }}</dd>
          </div>
          <div v-if="vm.cs.link">
            <dt>{{ vm.L.mLink }}</dt>
            <dd><a :href="vm.cs.link" target="_blank" rel="noopener">{{ vm.cs.host }} ↗</a></dd>
          </div>
        </dl>
        <div v-if="vm.cs.tech.length" class="col gap10 pt20">
          <span class="mono-eyebrow">Stack</span>
          <ul class="tech"><li v-for="tg in vm.cs.tech" :key="tg">{{ tg }}</li></ul>
        </div>
      </aside>
    </div>

    <div class="wrap case-blocks">
      <section v-for="bk in vm.cs.blocks" :key="bk.num" data-reveal class="case-block">
        <h2><span>§{{ bk.num }}</span>{{ bk.label }}</h2>
        <div class="case-block-body">
          <ul v-if="bk.isList" class="checks">
            <li v-for="it in bk.items" :key="it.n"><span class="ms">check</span>{{ it.text }}</li>
          </ul>
          <p v-else>{{ bk.text }}</p>
        </div>
      </section>
      <section v-if="vm.cs.gallery.length" class="gallery">
        <img v-for="im in vm.cs.gallery" :key="im.src" :src="im.src" :alt="im.alt" loading="lazy">
      </section>
    </div>

    <nav :aria-label="vm.L.caseNav" class="case-nav">
      <div class="wrap case-nav-in">
        <RouterLink :to="vm.cs.prev.href" class="case-prev">
          <span>← {{ vm.L.prev }}</span>
          <strong>{{ vm.cs.prev.title }}</strong>
        </RouterLink>
        <RouterLink :to="vm.cs.next.href" class="case-next">
          <span>{{ vm.L.next }} →</span>
          <strong>{{ vm.cs.next.title }}</strong>
        </RouterLink>
      </div>
    </nav>
  </article>
</template>
