<script setup>
defineProps({ chart: Object });
</script>

<template>
  <div class="chart">
    <div class="chart-legend"><span><i></i>Visiteurs</span><span><i class="pv"></i>Pages vues</span></div>
    <svg viewBox="0 0 640 220" preserveAspectRatio="none" role="img" :aria-label="chart.aria" class="chart-svg">
      <template v-for="gl in chart.grid" :key="gl.y">
        <line x1="0" x2="640" :y1="gl.y" :y2="gl.y" stroke="var(--ln2)" stroke-width="1" />
        <text x="0" :y="gl.ty" font-size="10" fill="var(--mu)" font-family="JetBrains Mono, monospace">{{ gl.label }}</text>
      </template>
      <path :d="chart.pvArea" fill="var(--acs)" opacity=".55" />
      <path :d="chart.pvLine" fill="none" stroke="var(--ac2)" stroke-width="2" vector-effect="non-scaling-stroke" />
      <path :d="chart.vArea" fill="var(--ac)" opacity=".12" />
      <path :d="chart.vLine" fill="none" stroke="var(--ac)" stroke-width="2.5" vector-effect="non-scaling-stroke" />
    </svg>
    <div class="chart-x"><span v-for="x in chart.xl" :key="x">{{ x }}</span></div>
  </div>
</template>
