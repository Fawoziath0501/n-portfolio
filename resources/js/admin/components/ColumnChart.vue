<script setup>
// Histogramme en colonnes (heures, jours, volumes quotidiens). « every » : une étiquette sur N pour rester lisible.
import { computed } from 'vue';

const props = defineProps({ rows: Array, every: { type: Number, default: 1 }, label: String, height: { type: Number, default: 120 } });
const max = computed(() => Math.max(1, ...props.rows.map((r) => r.n)));
const total = computed(() => props.rows.reduce((a, r) => a + r.n, 0));
</script>

<template>
  <div class="colchart" role="img" :aria-label="(label || 'Histogramme') + ' : ' + rows.map((r) => r.label + ' ' + r.n).join(', ')">
    <p v-if="!total" class="empty-txt">Aucune donnée sur la période.</p>
    <template v-else>
      <div class="colchart-bars" :style="{ height: height + 'px' }">
        <span v-for="(r, i) in rows" :key="i" class="colchart-bar" :title="r.label + ' : ' + r.n" :style="{ height: Math.max(r.n ? 4 : 1, r.n / max * 100) + '%' }" :class="{ peak: r.n === max }"></span>
      </div>
      <div class="colchart-x"><span v-for="(r, i) in rows" :key="i">{{ i % every === 0 ? r.label : '' }}</span></div>
    </template>
  </div>
</template>
