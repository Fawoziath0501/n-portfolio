<script setup>
// Rendu générique des champs d'un formulaire (profil, SEO, paramètres, tiroir d'édition).
import { getPath, state } from '../store';

const props = defineProps({ defs: Array, obj: Object });
const emit = defineEmits(['set']);
const set = (k, v) => emit('set', k, v);

const val = (f) => getPath(props.obj, f.k);
const listText = (f) => { const raw = props.obj[f.k + '__raw']; return raw != null ? raw : (val(f) || []).join(', '); };
const i18nTag = (f, lang) => { const raw = props.obj[f.k + (lang === 'fr' ? '__rawFr' : '__rawEn')]; return raw != null ? raw : (val(f) || []).map((x) => (x && x[lang]) || '').join(', '); };
const missing = (f) => {
  if (f.type === 'i18n' || f.type === 'i18nArea') { const v = val(f) || {}; return !!v.fr && !v.en; }
  if (f.type === 'i18nTags') return !!i18nTag(f, 'fr') && !i18nTag(f, 'en');
  return false;
};
const setI18n = (f, lang, v) => set(f.k, { ...(val(f) || {}), [lang]: v });
const thumb = (f) => (f.type === 'mediaList' ? String(listText(f)).split(',')[0].trim() : val(f)) || '';
const pick = (f) => {
  state.pick = {
    accept: f.accept || 'image',
    onPick: (m) => {
      if (f.type === 'mediaList') { const cur = String(listText(f)).replace(/,\s*$/, '').trim(); set(f.k + '__raw', (cur ? cur + ', ' : '') + m.src); }
      else set(f.k, m.src);
    },
  };
};
</script>

<template>
  <div class="fields">
    <div v-for="f in defs" :key="f.k" class="field" :style="{ gridColumn: f.full ? '1 / -1' : 'auto' }">
      <div class="field-head"><span>{{ f.label }}</span><span v-if="missing(f)" class="warn-tag">EN manquant</span></div>

      <div v-if="['text', 'url', 'number', 'date', 'email', 'media', 'mediaList', 'tags'].includes(f.type)" class="row center gap8">
        <span v-if="['media', 'mediaList'].includes(f.type) && (f.accept || 'image') === 'image' && thumb(f)" class="thumb" :style="{ backgroundImage: 'url(&quot;' + thumb(f) + '&quot;)' }"></span>
        <input v-if="['tags', 'mediaList'].includes(f.type)" type="text" class="ain" :value="listText(f)" :placeholder="f.ph || ''" @input="set(f.k + '__raw', $event.target.value)">
        <input v-else :type="f.type === 'media' ? 'text' : f.type" class="ain" :value="val(f) == null ? '' : val(f)" :placeholder="f.type === 'media' ? 'Choisissez un fichier ou collez une adresse' : (f.ph || '')"
          @input="set(f.k, f.type === 'number' ? (Number($event.target.value) || 0) : $event.target.value)">
        <button v-if="['media', 'mediaList'].includes(f.type)" type="button" class="abtn pick" @click="pick(f)"><span class="ms">perm_media</span>Médiathèque</button>
      </div>

      <textarea v-else-if="f.type === 'area'" rows="4" class="ain area" :value="val(f) || ''" @input="set(f.k, $event.target.value)"></textarea>

      <div v-else-if="f.type === 'i18n'" class="col gap6">
        <label class="lang-in"><span>FR</span><input class="ain" :value="(val(f) || {}).fr || ''" @input="setI18n(f, 'fr', $event.target.value)"></label>
        <label class="lang-in"><span>EN</span><input class="ain" :value="(val(f) || {}).en || ''" @input="setI18n(f, 'en', $event.target.value)"></label>
      </div>

      <div v-else-if="f.type === 'i18nTags'" class="col gap6">
        <label class="lang-in"><span>FR</span><input class="ain" :value="i18nTag(f, 'fr')" @input="set(f.k + '__rawFr', $event.target.value)"></label>
        <label class="lang-in"><span>EN</span><input class="ain" :value="i18nTag(f, 'en')" @input="set(f.k + '__rawEn', $event.target.value)"></label>
      </div>

      <div v-else-if="f.type === 'i18nArea'" class="area-pair">
        <label class="col gap4"><span class="lang-tag">FR</span><textarea rows="4" class="ain area" :value="(val(f) || {}).fr || ''" @input="setI18n(f, 'fr', $event.target.value)"></textarea></label>
        <label class="col gap4"><span class="lang-tag">EN</span><textarea rows="4" class="ain area" :value="(val(f) || {}).en || ''" @input="setI18n(f, 'en', $event.target.value)"></textarea></label>
      </div>

      <button v-else-if="f.type === 'switch'" type="button" role="switch" :aria-checked="!!val(f)" class="switch-btn" @click="set(f.k, !val(f))">
        <span class="switch" :class="{ on: !!val(f) }"><span></span></span>{{ val(f) ? (f.on || 'Activé') : (f.off || 'Désactivé') }}
      </button>

      <select v-else-if="f.type === 'select'" class="ain" :value="val(f) || ''" @change="set(f.k, $event.target.value)">
        <option v-for="op in f.options" :key="op.v" :value="op.v">{{ op.label }}</option>
      </select>

      <span v-if="f.hint" class="field-hint">{{ f.hint }}</span>
    </div>
  </div>
</template>
