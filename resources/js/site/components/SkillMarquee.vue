<script setup>
import { vm } from '../store';
import { reducedMotion } from '../../shared/util';

const still = reducedMotion();
</script>

<template>
  <div aria-hidden="true" class="marquee">
    <div class="marquee-track" :class="{ still }">
      <div v-for="row in 2" :key="row" class="marquee-row">
        <span v-for="(it, i) in vm.allSkills" :key="i" class="marquee-item">
          <img v-if="it.logo" :src="it.logo" alt="" width="22" height="22">
          <span v-else class="ms">{{ it.icon }}</span>{{ it.name }}
        </span>
      </div>
    </div>
  </div>
</template>
