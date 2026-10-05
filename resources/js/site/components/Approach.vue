<script setup>
import { state, vm } from '../store';

const enter = (i) => { if (!vm.value.mobile) state.flip = i; };
const leave = () => { if (!vm.value.mobile) state.flip = null; };
const toggle = (i) => { state.flip = state.flip === i ? null : i; };
</script>

<template>
  <section id="approach" aria-labelledby="approach-title" class="sec">
    <div class="wrap col gap56">
      <div class="approach-top">
        <div data-reveal class="approach-bio">
          <p class="pill"><span class="ms">person</span>{{ vm.L.aboutLabel }}</p>
          <h2 id="approach-title" class="h2">{{ vm.L.aboutTitle }}</h2>
          <p v-for="b in vm.bio" :key="b.n" class="body">{{ b.text }}</p>
        </div>
        <dl data-reveal class="info-list">
          <div v-for="ai in vm.aboutInfo" :key="ai.num">
            <dt>§{{ ai.num }} · {{ ai.label }}</dt>
            <dd>{{ ai.value }}</dd>
          </div>
        </dl>
      </div>

      <div class="col gap24">
        <div class="row-between">
          <h3 class="h3">{{ vm.L.methodTitle }}</h3>
          <span class="hint">{{ vm.L.flipHint }}</span>
        </div>
        <ol class="flip-grid">
          <li v-for="(v, i) in vm.values" :key="v.num" data-reveal class="flip" @mouseenter="enter(i)" @mouseleave="leave">
            <button type="button" class="flip-card" :aria-pressed="v.open" :aria-label="v.title + ': ' + v.text" :style="{ transform: v.open ? 'rotateY(180deg)' : 'none' }" @click="toggle(i)">
              <span class="flip-front" :style="{ background: v.bg, color: v.fg }">
                <span class="row-between">
                  <span class="ms flip-ico" :style="{ color: v.sub }">{{ v.icon }}</span>
                  <span class="mono-12" :style="{ color: v.sub }">{{ v.num }} / 03</span>
                </span>
                <span class="col gap6">
                  <span class="flip-num">{{ v.num }}</span>
                  <span class="flip-title">{{ v.title }}</span>
                </span>
              </span>
              <span class="flip-back">
                <span class="mono-12 blue">{{ v.num }} · {{ v.title }}</span>
                <span class="flip-text">{{ v.text }}</span>
                <span class="flip-keys"><span v-for="kw in v.keys" :key="kw" class="chip">{{ kw }}</span></span>
              </span>
            </button>
          </li>
        </ol>
      </div>
    </div>
  </section>
</template>
