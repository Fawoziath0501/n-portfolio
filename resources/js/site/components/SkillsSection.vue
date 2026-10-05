<script setup>
import { vm } from '../store';
</script>

<template>
  <section id="skills" aria-labelledby="skills-title" class="sec alt bt bb">
    <div class="wrap col gap40">
      <div data-reveal class="sec-head">
        <div class="col gap14 mw640">
          <p class="pill"><span class="ms">bolt</span>{{ vm.L.skillsLabel }}</p>
          <h2 id="skills-title" class="h2">{{ vm.L.skillsTitle }}</h2>
        </div>
        <p class="muted-16 mw440">{{ vm.L.skillsIntro }}</p>
      </div>
      <div class="sk-groups">
        <div v-for="g in vm.skillGroups" :key="g.id" data-reveal class="sk-group">
          <div class="sk-group-h">
            <span class="mono-12 blue">{{ g.num }}</span>
            <h3>{{ g.label }}</h3>
          </div>
          <ul class="sk-list">
            <li v-for="s in g.skills" :key="s.name" class="sk">
              <img v-if="s.logo" :src="s.logo" alt="" width="28" height="28" loading="lazy">
              <span v-else aria-hidden="true" class="sk-ico ms">{{ s.icon }}</span>
              <span class="col minw0"><span class="sk-name">{{ s.name }}</span><span v-if="s.note" class="sk-note">{{ s.note }}</span></span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</template>
