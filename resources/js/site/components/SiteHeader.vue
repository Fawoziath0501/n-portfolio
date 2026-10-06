<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { href, state, vm } from '../store';
import MenuLink from './MenuLink.vue';

const router = useRouter();
const setLang = (l) => { if (l !== state.lang) router.push(href(l, state.route, state.slug)); };

// Menu mobile : panneau léger sous la barre du haut. Échap ou un toucher à côté le ferme ; la page ne défile pas derrière.
const close = () => { state.menu = false; };
const onKey = (e) => { if (e.key === 'Escape' && state.menu) close(); };
watch(() => state.menu && vm.value.mobile, (open) => { document.documentElement.style.overflow = open ? 'hidden' : ''; });
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => { window.removeEventListener('keydown', onKey); document.documentElement.style.overflow = ''; });
</script>

<template>
  <header class="hdr" :class="{ 'menu-open': state.menu && vm.mobile }">
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

      <button v-if="vm.mobile" type="button" class="burger" :class="{ open: state.menu }" :aria-expanded="state.menu" aria-controls="mnav"
        :aria-label="state.menu ? vm.L.close : vm.L.menu" @click="state.menu = !state.menu">
        <span></span><span></span><span></span>
      </button>
    </div>

    <Transition name="mnav">
      <div v-if="state.menu && vm.mobile" id="mnav" class="mnav">
        <nav :aria-label="vm.L.navAria" class="mnav-list" @click="close">
          <MenuLink v-for="n in vm.navItems" :key="n.key" :item="n" class="mnav-link" :class="{ on: n.current }" :aria-current="n.current ? 'page' : null">
            {{ n.label }}<span class="ms" aria-hidden="true">chevron_right</span>
          </MenuLink>
        </nav>
        <MenuLink v-if="vm.headerCta" :item="vm.headerCta" class="btn btn-primary mnav-cta" @click="close"><span class="ms">mail</span>{{ vm.headerCta.label }}</MenuLink>
        <a v-if="vm.p.email" :href="vm.p.mailto" class="mnav-mail">{{ vm.p.email }}</a>
      </div>
    </Transition>
  </header>

  <Transition name="mnav-fade">
    <div v-if="state.menu && vm.mobile" class="mnav-backdrop" aria-hidden="true" @click="close"></div>
  </Transition>
</template>
