<script setup>
import { state, subscribe, vm } from '../store';
import { reducedMotion } from '../../shared/util';
import SocialIcon from '../../shared/SocialIcon.vue';

const toTop = () => window.scrollTo({ top: 0, behavior: reducedMotion() ? 'auto' : 'smooth' });
const nlMsg = () => (state.nls === 'ok' ? vm.value.L.nlOk : state.nls === 'dup' ? vm.value.L.nlDup : vm.value.L.nlErr);
</script>

<template>
  <footer class="ftr">
    <div class="wrap ftr-in">
      <div class="nl">
        <div class="nl-l">
          <h2><span class="ms">mark_email_unread</span>{{ vm.L.newsletterTitle }}</h2>
          <p>{{ vm.L.nlText }}</p>
        </div>
        <form novalidate class="nl-form" @submit.prevent="subscribe">
          <div class="row-wrap gap8">
            <label class="nl-lbl"><span class="sr-only">{{ vm.L.email }}</span>
              <input v-model="state.nl" type="email" name="nl" autocomplete="email" :placeholder="vm.L.emailPh" :aria-invalid="state.nls === 'err'" :class="{ bad: state.nls === 'err' }" @input="state.nls = ''">
            </label>
            <button type="submit" class="nl-btn">{{ vm.L.nlCta }}</button>
          </div>
          <p v-if="state.nls" role="status" class="nl-msg" :style="{ color: state.nls === 'ok' ? '#8FA3E8' : '#F0A39C' }">{{ nlMsg() }}</p>
          <p class="nl-note">{{ vm.L.nlPrivacy }} <RouterLink :to="vm.hrefs.privacy">{{ vm.L.privacyLink }}</RouterLink></p>
        </form>
      </div>

      <div class="ftr-grid">
        <div class="col gap18">
          <span class="ftr-name">{{ vm.brand.short }}<span class="ftr-dot"></span></span>
          <p class="ftr-bio">{{ vm.p.title }} · {{ vm.p.stack }}. {{ vm.L.footBio }}</p>
          <div class="row gap8">
            <a v-for="so in vm.socials" :key="so.label" :href="so.url" target="_blank" rel="noopener" :aria-label="so.label" :title="so.label" class="ftr-soc"><SocialIcon :url="so.url" :label="so.label" :size="18" /></a>
          </div>
        </div>
        <nav :aria-label="vm.L.footNav" class="ftr-col">
          <h2>{{ vm.L.footNavTitle }}</h2>
          <RouterLink v-for="n in vm.navItems" :key="n.key" :to="n.href"><span>›</span>{{ n.label }}</RouterLink>
        </nav>
        <nav :aria-label="vm.L.resources" class="ftr-col">
          <h2>{{ vm.L.resources }}</h2>
          <RouterLink v-for="n in vm.resItems" :key="n.label" :to="n.href"><span>›</span>{{ n.label }}</RouterLink>
        </nav>
        <div class="ftr-col">
          <h2>{{ vm.L.footContactTitle }}</h2>
          <div v-for="cr in vm.contactRows" :key="cr.num" class="ftr-contact">
            <span class="ms">{{ cr.icon }}</span>
            <a v-if="cr.href" :href="cr.href">{{ cr.value }}</a>
            <span v-else>{{ cr.value }}</span>
          </div>
        </div>
      </div>

      <div class="ftr-bottom">
        <span>© {{ vm.year }} {{ vm.brand.full }} · {{ vm.L.rights }}</span>
        <div class="row center gap16">
          <RouterLink :to="vm.hrefs.privacy" class="ftr-link">{{ vm.L.privacyLink }}</RouterLink>
          <button type="button" class="ftr-top ms" :aria-label="vm.L.toTop" @click="toTop">arrow_upward</button>
        </div>
      </div>
    </div>
  </footer>
</template>
