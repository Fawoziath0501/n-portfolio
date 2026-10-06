<script setup>
import { nextTick, onBeforeUnmount, onMounted, watch } from 'vue';
import { state, vm, trackClicks } from './store';
import { reducedMotion, tx } from '../shared/util';
import SiteHeader from './components/SiteHeader.vue';
import ServiceModal from './components/ServiceModal.vue';
import HomeHero from './components/HomeHero.vue';
import PageHead from './components/PageHead.vue';
import AboutShort from './components/AboutShort.vue';
import Approach from './components/Approach.vue';
import WorkSection from './components/WorkSection.vue';
import Timeline from './components/Timeline.vue';
import LangSoft from './components/LangSoft.vue';
import SkillsSection from './components/SkillsSection.vue';
import ServicesSection from './components/ServicesSection.vue';
import BlogSection from './components/BlogSection.vue';
import Testimonials from './components/Testimonials.vue';
import CaseStudy from './components/CaseStudy.vue';
import ContactSection from './components/ContactSection.vue';
import CtaBand from './components/CtaBand.vue';
import SkillMarquee from './components/SkillMarquee.vue';
import SiteFooter from './components/SiteFooter.vue';
import LegalPage from './components/LegalPage.vue';
import PostPage from './components/PostPage.vue';
import CertViewer from './components/CertViewer.vue';

const onScroll = () => {
    const mh = document.documentElement.scrollHeight - window.innerHeight;
    state.scrolled = window.scrollY > 24;
    state.pc = mh > 0 ? Math.round((window.scrollY / mh) * 100) : 0;
};
const onResize = () => { state.w = window.innerWidth; };

// Titre et description mis à jour à chaque navigation.
function meta() {
    const d = state.data;
    document.documentElement.lang = state.lang;
    // Projet, article ou page légale : son propre titre ; sinon le titre du site.
    const item = (state.route === 'project' && d.projects.find((x) => x.slug === state.slug))
        || (state.route === 'post' && (d.posts || []).find((x) => x.slug === state.slug))
        || (state.route === 'legal' && (d.legalPages || []).find((x) => x.id === state.slug));
    const owner = [d.profile.firstName, d.profile.lastName].join(' ');
    // Pages du site : titre et introduction de l'en-tête de page (comme le rendu serveur).
    const pk = { about: 'pAbout', work: 'pWork', services: 'pServices', blog: 'pBlog', contact: 'pContact' }[state.route];
    const lbl = (i) => (pk && d.labels[pk + '.' + i] && d.labels[pk + '.' + i][state.lang]) || '';
    document.title = item ? tx(item.title, state.lang) + ' | ' + owner : lbl(1) ? lbl(1) + ' | ' + owner : tx(d.seo.siteTitle, state.lang);
    const m = document.querySelector('meta[name="description"]');
    const summary = item ? tx(item.summary || item.excerpt, state.lang) : lbl(2);
    if (m) m.content = summary || tx(d.seo.metaDescription, state.lang);
}

// Apparition douce des blocs [data-reveal] au défilement.
let io = null;
function observe() {
    if (reducedMotion() || !window.IntersectionObserver) return;
    if (!io) io = new IntersectionObserver((es) => es.forEach((e) => {
        if (e.isIntersecting) { e.target.style.opacity = '1'; e.target.style.transform = 'none'; io.unobserve(e.target); }
    }), { rootMargin: '0px 0px -6% 0px' });
    document.querySelectorAll('[data-reveal]:not([data-rv])').forEach((el, i) => {
        el.setAttribute('data-rv', '1');
        if (el.getBoundingClientRect().top < window.innerHeight) return;
        el.style.opacity = '0';
        el.style.transform = 'translateY(14px)';
        el.style.transition = 'opacity 480ms ease-out ' + (i % 3) * 70 + 'ms, transform 480ms ease-out ' + (i % 3) * 70 + 'ms';
        io.observe(el);
    });
}

watch(() => [state.route, state.slug, state.lang, state.filter], () => nextTick(() => { meta(); observe(); }));

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onResize);
    document.addEventListener('click', trackClicks);
    nextTick(() => { meta(); observe(); });
});
onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', onResize);
    document.removeEventListener('click', trackClicks);
    if (io) io.disconnect();
});
</script>

<template>
  <div v-if="state.data.settings.maintenance" class="maint">
    <span class="logo-mark">{{ vm.brand.mark }}</span>
    <h1>{{ vm.L.maintTitle }}<span class="dot">.</span></h1>
    <p>{{ vm.L.maintText }}</p>
    <a :href="vm.p.mailto">{{ vm.p.email }}</a>
  </div>

  <div v-else class="site">
    <div v-if="state.data.settings.maintenancePreview" class="maint-preview" role="status"><span class="ms" aria-hidden="true">construction</span>Mode maintenance actif : les visiteurs voient la page de maintenance. Vous voyez le site parce que vous êtes connectée à l’administration.</div>
    <SiteHeader />
    <ServiceModal v-if="state.svc" />
    <CertViewer v-if="state.certView" />

    <main id="main" class="main">
      <HomeHero v-if="vm.isHome" />
      <PageHead v-if="vm.hasPageHead" />
      <AboutShort v-if="vm.show.aboutShort" />
      <Approach v-if="vm.show.approach" />
      <WorkSection v-if="vm.show.work" />
      <Timeline v-if="vm.show.exp" />
      <LangSoft v-if="vm.show.edu" />
      <SkillsSection v-if="vm.show.skills" />
      <ServicesSection v-if="vm.show.services" />
      <BlogSection v-if="vm.show.blog" />
      <Testimonials v-if="vm.showTesti" />
      <CaseStudy v-if="vm.isProject" />
      <ContactSection v-if="vm.isContact" />
      <LegalPage v-if="vm.isLegal" />
      <PostPage v-if="vm.isPost" />
      <CtaBand v-if="vm.showCta" />
      <SkillMarquee />
    </main>

    <SiteFooter />
  </div>
</template>
