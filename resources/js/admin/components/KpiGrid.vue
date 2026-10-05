<script setup>
defineProps({ kpis: Array });
</script>

<template>
  <div class="kpis">
    <div v-for="k in kpis" :key="k.label" class="kpi">
      <div class="kpi-top"><span>{{ k.label }}</span><span class="kpi-ico ms">{{ k.icon }}</span></div>
      <div class="kpi-mid">
        <span class="kpi-val">{{ k.value }}</span>
        <svg v-if="k.spark" viewBox="0 0 100 32" preserveAspectRatio="none" aria-hidden="true" class="kpi-spark"><path :d="k.spark" fill="none" stroke="var(--ac)" stroke-width="2" vector-effect="non-scaling-stroke" /></svg>
      </div>
      <span class="kpi-delta" :style="{ background: k.dBg, color: k.dFg }">{{ k.delta }}</span>
    </div>
  </div>
</template>
