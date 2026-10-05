<script setup>
import { vm } from '../store';
</script>

<template>
  <section id="blog" aria-labelledby="blog-title" class="sec alt bt">
    <div class="wrap col gap36">
      <div v-if="vm.isHome" data-reveal class="sec-head">
        <div class="col gap14 mw640">
          <p class="pill"><span class="ms">article</span>{{ vm.L.blogLabel }}</p>
          <h2 id="blog-title" class="h2">{{ vm.L.blogTitle }}</h2>
        </div>
        <RouterLink :to="vm.hrefs.blog" class="btn btn-outline md">{{ vm.L.allPosts }} <span class="ms">arrow_forward</span></RouterLink>
      </div>
      <ul class="posts">
        <li v-for="po in vm.posts" :key="po.id" data-reveal>
          <component :is="po.url ? 'a' : 'article'" :href="po.url || null" :target="po.url ? '_blank' : null" :rel="po.url ? 'noopener' : null" class="post">
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
                <span class="mono">{{ po.read }}</span>
              </div>
            </div>
          </component>
        </li>
      </ul>
    </div>
  </section>
</template>
