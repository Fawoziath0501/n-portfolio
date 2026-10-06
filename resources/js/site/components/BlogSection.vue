<script setup>
import { state, vm } from '../store';
</script>

<template>
  <section id="blog" aria-labelledby="blog-title" class="sec alt bt">
    <div class="wrap col gap36">
      <div v-if="vm.isHome" data-reveal class="sec-head">
        <div class="col gap14 mw640">
          <p class="pill"><span class="ms">article</span>{{ vm.L.blogLabel }}</p>
          <h2 id="blog-title" class="h2">{{ vm.L.blogTitle }}</h2>
        </div>
        <RouterLink :to="vm.hrefs.blog" class="btn btn-outline md">{{ vm.L.allPosts }} <span class="mono-12">{{ vm.postCount }}</span><span class="ms">arrow_forward</span></RouterLink>
      </div>

      <!-- Page Blog : thèmes, recherche et tri -->
      <div v-else class="blog-tools">
        <div role="group" :aria-label="vm.L.blogFilterAria" class="filters">
          <button v-for="tg in vm.blog.tags" :key="tg.v" type="button" class="filter" :class="{ on: tg.on }" :aria-pressed="tg.on" @click="state.blogTag = tg.v">{{ tg.label }}</button>
        </div>
        <div class="blog-tools-r">
          <label class="blog-search"><span class="ms" aria-hidden="true">search</span><span class="sr-only">{{ vm.L.searchPosts }}</span>
            <input v-model="state.blogQ" type="search" :placeholder="vm.L.searchPosts">
          </label>
          <div role="group" class="seg">
            <button type="button" :class="{ on: vm.blog.sort === 'recent' }" :aria-pressed="vm.blog.sort === 'recent'" @click="state.blogSort = 'recent'">{{ vm.L.sortRecent }}</button>
            <button type="button" :class="{ on: vm.blog.sort === 'popular' }" :aria-pressed="vm.blog.sort === 'popular'" @click="state.blogSort = 'popular'">{{ vm.L.sortPopular }}</button>
          </div>
        </div>
      </div>

      <p v-if="vm.blog.empty" role="status" class="muted-16">{{ vm.L.noPosts }}</p>
      <ul class="posts">
        <li v-for="po in vm.posts" :key="po.id" data-reveal>
          <component :is="po.href ? 'RouterLink' : po.url ? 'a' : 'article'" :to="po.href || undefined" :href="!po.href && po.url ? po.url : undefined" :target="!po.href && po.url ? '_blank' : null" :rel="!po.href && po.url ? 'noopener' : null" class="post">
            <div aria-hidden="true" class="post-cover">
              <span class="ms">{{ po.icon }}</span>
              <span class="mono-12 blue">{{ po.num }}</span>
            </div>
            <div class="post-body">
              <ul class="post-tags"><li v-for="tg in po.tags" :key="tg">{{ tg }}</li></ul>
              <h3 class="post-title">{{ po.title }}</h3>
              <p class="post-excerpt">{{ po.excerpt }}</p>
              <div class="post-foot">
                <span>{{ po.date }}</span>
                <span class="mono">{{ po.read }}<template v-if="po.views"> · {{ po.views }}</template></span>
              </div>
            </div>
          </component>
        </li>
      </ul>
    </div>
  </section>
</template>
