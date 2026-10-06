<script setup>
import { vm } from '../store';
</script>

<template>
  <article aria-labelledby="post-title" class="article">
    <div class="wrap">
      <div class="article-inner">
        <RouterLink :to="vm.hrefs.blog" class="article-back"><span class="ms" aria-hidden="true">arrow_back</span>{{ vm.L.backToBlog }}</RouterLink>
        <ul v-if="vm.post.tags.length" class="post-tags"><li v-for="tg in vm.post.tags" :key="tg">{{ tg }}</li></ul>
        <h1 id="post-title" class="h2">{{ vm.post.title }}</h1>
        <p class="article-meta">
          <span><span class="ms" aria-hidden="true">calendar_today</span>{{ vm.post.date }}</span>
          <span><span class="ms" aria-hidden="true">schedule</span>{{ vm.post.read }}</span>
          <span><span class="ms" aria-hidden="true">visibility</span>{{ vm.post.views }}</span>
        </p>
        <p v-if="vm.post.excerpt" class="lead">{{ vm.post.excerpt }}</p>
        <div v-if="vm.post.html" class="article-body rich" v-html="vm.post.html"></div>
        <a v-if="vm.post.url" :href="vm.post.url" target="_blank" rel="noopener" class="btn btn-outline md self-start">{{ vm.L.readOriginal.replace('{host}', vm.post.host) }}<span class="ms">open_in_new</span></a>

        <nav v-if="vm.post.prev || vm.post.next" :aria-label="vm.L.blogNav" class="article-nav">
          <RouterLink v-if="vm.post.prev" :to="vm.post.prev.href"><span>← {{ vm.L.postPrev }}</span>{{ vm.post.prev.title }}</RouterLink>
          <RouterLink v-if="vm.post.next" :to="vm.post.next.href" class="next"><span>{{ vm.L.postNext }} →</span>{{ vm.post.next.title }}</RouterLink>
        </nav>
      </div>
    </div>
  </article>
</template>
