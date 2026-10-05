<script setup>
import { scrollToId, vm } from '../store';
</script>

<template>
  <section aria-labelledby="page-title" class="phead">
    <div class="wrap phead-in">
      <div class="phead-bar">
        <nav :aria-label="vm.L.crumbAria" class="crumbs">
          <RouterLink :to="vm.hrefs.home">{{ vm.L.nav0 }}</RouterLink><span>/</span>
          <template v-if="vm.isProject"><RouterLink :to="vm.hrefs.work">{{ vm.L.crumbWork }}</RouterLink><span>/</span></template>
          <span class="crumb-cur">{{ vm.page.crumb }}</span>
        </nav>
        <span class="phead-idx">{{ vm.page.index }}</span>
      </div>
      <div class="phead-main">
        <div class="phead-text">
          <p class="phead-eyebrow"><span class="ms phead-ico">{{ vm.page.icon }}</span>{{ vm.page.eyebrow }}</p>
          <h1 id="page-title" class="phead-title">{{ vm.page.title }}<span class="dot">.</span></h1>
          <p class="phead-intro">{{ vm.page.intro }}</p>
        </div>
        <aside v-if="vm.page.rows.length" :aria-label="vm.L.glance" class="glance">
          <p class="glance-head"><span>{{ vm.L.glance }}</span><span class="ms">insights</span></p>
          <dl>
            <div v-for="pr in vm.page.rows" :key="pr.num" class="glance-row">
              <dt><span>{{ pr.num }}</span>{{ pr.label }}</dt>
              <dd>{{ pr.value }}</dd>
            </div>
          </dl>
        </aside>
      </div>
      <nav v-if="vm.page.jumps.length" :aria-label="vm.L.onPage" class="jumps">
        <span class="jumps-label">{{ vm.L.onPage }}</span>
        <button v-for="jp in vm.page.jumps" :key="jp.id" type="button" class="jump" @click="scrollToId(jp.id)"><span class="ms">south</span>{{ jp.label }}</button>
      </nav>
    </div>
  </section>
</template>
