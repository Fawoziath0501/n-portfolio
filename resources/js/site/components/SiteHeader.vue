<script setup>
import { useRouter } from 'vue-router';
import { href, state, vm } from '../store';
import MenuLink from './MenuLink.vue';

const router = useRouter();
const setLang = (l) => { if (l !== state.lang) router.push(href(l, state.route, state.slug)); };
</script>

<template>
  <header class="hdr">
    <div class="hdr-progress" aria-hidden="true" :style="{ width: state.pc + '%' }"></div>
    <div class="wrap hdr-in" :style="{ height: state.scrolled || vm.mobile ? '64px' : '76px' }">
      <RouterLink :to="vm.hrefs.home" class="brand" :aria-label="vm.brand.short + ', ' + vm.L.nav0">
        <span class="brand-mark">{{ vm.brand.mark }}</span>
        <span class="brand-name">{{ vm.brand.name }}<span>{{ vm.brand.tld }}</span></span>
      </RouterLink>

      <nav v-if="!vm.mobile" :aria-label="vm.L.navAria" class="hdr-nav">
        <MenuLink v-for="n in vm.navItems" :key="n.key" :item="n" :aria-current="n.current ? 'page' : null" class="hdr-link" :class="{ on: n.current }">{{ n.label }}</MenuLink>
      </nav>

      <div role="group" :aria-label="vm.L.langAria" class="lang" :style="{ marginLeft: vm.mobile ? 'auto' : '0' }">
        <span aria-hidden="true" class="ms">language</span>
        <button type="button" lang="fr" :aria-pressed="vm.fr" :class="{ on: vm.fr }" @click="setLang('fr')">FR</button>
        <button type="button" lang="en" :aria-pressed="!vm.fr" :class="{ on: !vm.fr }" @click="setLang('en')">EN</button>
      </div>

      <MenuLink v-if="!vm.mobile && vm.headerCta" :item="vm.headerCta" class="btn btn-primary hdr-cta"><span class="ms">mail</span>{{ vm.headerCta.label }}</MenuLink>

      <button v-if="vm.mobile" type="button" class="burger" :aria-expanded="state.menu" :aria-label="vm.L.menu" @click="state.menu = !state.menu">
        <span></span><span></span>
      </button>
    </div>
  </header>

  <div v-if="state.menu && vm.mobile" role="dialog" aria-modal="true" :aria-label="vm.L.menu" class="mnav">
    <div class="mnav-top">
      <span class="mnav-name">{{ vm.brand.short }}</span>
      <button type="button" class="mnav-close" @click="state.menu = false">{{ vm.L.close }}</button>
    </div>
    <nav :aria-label="vm.L.navAria" class="mnav-list">
      <MenuLink v-for="n in vm.navItems" :key="n.key" :item="n">
        <span class="mnav-num">{{ n.num }}</span>
        <span class="mnav-label">{{ n.label }}</span>
      </MenuLink>
    </nav>
    <div class="mnav-foot">
      <a :href="vm.p.mailto">{{ vm.p.email }}</a>
      <span>{{ vm.p.phone }}</span>
      <span>{{ vm.p.location }}</span>
    </div>
  </div>
</template>
