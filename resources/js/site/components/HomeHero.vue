<script setup>
import { vm, state } from '../store';
// Portrait : version allégée (WebP 480 px, 960 px sur écran haute définition), sinon l’original.
const portraitBg = (url) => {
  const th = state.data.thumbs && state.data.thumbs[url];
  const q = (u) => 'url("' + u + '")';
  return th && th.v[480] ? 'image-set(' + q(th.v[480]) + ' 1x, ' + q(th.v[960] || url) + ' 2x)' : q(url);
};
</script>

<template>
  <section aria-labelledby="hero-title" class="hero">
    <div class="wrap hero-in">
      <div class="hero-text">
        <p class="pill"><span class="ms">code</span>{{ vm.L.heroBadge }}</p>
        <div class="col gap6">
          <p class="hero-hello">{{ vm.L.hello }}</p>
          <h1 id="hero-title" class="hero-name">
            <span class="hero-first">{{ vm.p.first }} {{ vm.p.middle }}</span>
            <span class="hero-last">{{ vm.p.lastUp }}</span>
          </h1>
          <p class="hero-role">{{ vm.p.title }} <span>/ {{ vm.p.stack }}</span></p>
        </div>
        <p class="hero-tagline">{{ vm.p.tagline }}</p>
        <div class="row-wrap gap12 hero-actions">
          <RouterLink :to="vm.hrefs.work" class="btn btn-primary lg"><span class="ms">grid_view</span>{{ vm.home.cta1 }}</RouterLink>
          <RouterLink :to="vm.hrefs.contact" class="btn btn-outline lg"><span class="ms">mail</span>{{ vm.home.cta2 }}</RouterLink>
          <a v-if="vm.p.cv" :href="vm.p.cv" target="_blank" rel="noopener" class="btn btn-ghost lg hero-cv" :aria-label="vm.L.cv" :title="vm.L.cv"><span class="ms" aria-hidden="true">download</span><span class="hero-cv-lbl">{{ vm.L.cv }}</span></a>
        </div>
        <dl class="hero-stats">
          <div v-for="st in vm.heroStats" :key="st.label">
            <dt>{{ st.label }}</dt>
            <dd><span class="sq"></span>{{ st.value }}</dd>
          </div>
        </dl>
      </div>

      <div class="hero-visual" :style="{ paddingRight: vm.annotPad }">
        <div class="portrait">
          <div v-if="vm.p.photo" role="img" :aria-label="vm.p.photoAlt" class="portrait-img" :style="{ backgroundImage: portraitBg(vm.p.photo) }"></div>
          <div v-else class="portrait-empty" role="img" :aria-label="vm.p.photoAlt"><span class="ms">image</span><span>{{ vm.p.photoPlaceholder }}</span></div>
          <span aria-hidden="true" class="corner tl"></span>
          <span aria-hidden="true" class="corner br"></span>
          <div class="stack-card">
            <span class="mono-eyebrow xs">{{ vm.L.stackLabel }}</span>
            <span class="row-wrap gap6"><span v-for="hs in vm.heroStack" :key="hs" class="chip">{{ hs }}</span></span>
          </div>
        </div>
        <template v-if="!vm.mobile">
          <div v-for="an in vm.annots" :key="an.label" class="annot" :style="{ top: an.top, left: 'calc(100% - ' + vm.annotPad + ' - 6px)' }">
            <span class="annot-dot" :style="{ background: an.dot }"></span>
            <span class="annot-line"></span>
            <span class="annot-box">
              <span class="mono-eyebrow xxs">{{ an.label }}</span>
              <span class="annot-val">{{ an.value }}</span>
            </span>
          </div>
        </template>
      </div>
    </div>
  </section>
</template>
