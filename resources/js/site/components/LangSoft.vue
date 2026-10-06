<script setup>
import { pad } from '../../shared/util';
import { state, vm } from '../store';
</script>

<template>
  <section id="languages" aria-labelledby="lang-title" class="sec-sm alt bt">
    <div class="wrap col gap36">
      <div data-reveal class="col gap14 mw640">
        <p class="pill"><span class="ms">translate</span>{{ vm.L.langLabel }} · {{ vm.L.soft }}</p>
        <h2 id="lang-title" class="h2">{{ vm.L.langSoftTitle }}</h2>
      </div>

      <div class="ls-grid">
        <!-- Langues : niveau en toutes lettres + jauge sur 5 (+ niveau CECRL s'il est connu) -->
        <article v-if="vm.languages.length" data-reveal class="ls-card">
          <header class="ls-head"><span class="ls-ico ms" aria-hidden="true">translate</span><h3>{{ vm.L.langLabel }}</h3></header>
          <ul class="ls-langs">
            <li v-for="lg in vm.languages" :key="lg.name">
              <div class="ls-lang-top">
                <span class="ls-lang-name">{{ lg.name }}</span>
                <span v-if="lg.cefr" class="ls-cefr">{{ lg.cefr }}</span>
              </div>
              <div class="ls-lang-bottom">
                <span class="ls-meter" role="img" :aria-label="lg.aria"><span v-for="n in 5" :key="n" :class="{ on: n <= lg.score }"></span></span>
                <span class="ls-level">{{ lg.level }}</span>
              </div>
            </li>
          </ul>
        </article>

        <!-- Qualités : liste numérotée -->
        <article v-if="vm.softList.length" data-reveal class="ls-card">
          <header class="ls-head"><span class="ls-ico ms" aria-hidden="true">favorite</span><h3>{{ vm.L.soft }}</h3></header>
          <ul class="ls-soft">
            <li v-for="(sk, i) in vm.softList" :key="sk"><span class="ls-num">{{ pad(i + 1) }}</span><span class="ms ls-check" aria-hidden="true">check_circle</span>{{ sk }}</li>
          </ul>
        </article>
      </div>

      <div v-if="vm.certs.length" data-reveal class="col gap14">
        <p class="pill"><span class="ms">verified</span>{{ vm.L.certLabel }}</p>
        <div class="ls-certs">
          <div v-for="ct in vm.certs" :key="ct.id" class="ls-card cert">
            <button v-if="ct.preview" type="button" class="cert-thumb" :style="{ backgroundImage: 'url(' + ct.preview + ')' }" :aria-label="vm.L.viewCert + ' : ' + ct.name" @click="state.certView = { src: ct.preview, title: ct.name }" @contextmenu.prevent><span class="cert-zoom"><span class="ms" aria-hidden="true">zoom_in</span>{{ vm.L.viewCert }}</span></button>
            <h3>{{ ct.name }}</h3>
            <span>{{ [ct.issuer, ct.date].filter(Boolean).join(' · ') }}</span>
            <a v-if="ct.verify" :href="ct.verify" target="_blank" rel="noopener">{{ vm.L.verify }} ↗</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
