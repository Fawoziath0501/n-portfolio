<script setup>
import { computed } from 'vue';
import { closeSvc, state, submitSvc, vm } from '../store';

const L = computed(() => vm.value.L);
const sv = computed(() => state.svc);
const err = computed(() => state.svcErr || {});
const icon = computed(() => (vm.value.services.find((x) => x.title === sv.value.service) || {}).icon || sv.value.icon);
const whens = computed(() => [L.value.choose, ...L.value.svcWhens]);
const set = (k, v) => { state.svc[k] = v; state.svcErr = { ...state.svcErr, [k]: null }; };
const backdrop = (e) => { if (e.target === e.currentTarget) closeSvc(); };
</script>

<template>
  <div class="svc-back" @click="backdrop">
    <div role="dialog" aria-modal="true" aria-labelledby="svc-title" class="svc" @keydown.esc="closeSvc">
      <div class="svc-head">
        <div class="svc-head-l">
          <span class="ico-box ms">{{ icon }}</span>
          <div class="col gap4">
            <span class="mono-eyebrow sm">{{ L.svcEyebrow }}</span>
            <h2 id="svc-title" class="svc-title">{{ sv.service }}</h2>
          </div>
        </div>
        <button type="button" class="icon-btn ms" :aria-label="L.close" @click="closeSvc">close</button>
      </div>

      <div v-if="state.svcFs === 'sent'" role="status" class="svc-ok">
        <span class="ok-ico ms">check</span>
        <h3>{{ L.svcOkTitle }}</h3>
        <p>{{ L.svcOkText }}</p>
        <button type="button" class="btn btn-dark" @click="closeSvc">{{ L.close }}</button>
      </div>

      <form v-else novalidate class="svc-form" @submit.prevent="submitSvc">
        <label class="fld"><span>{{ L.svcService }}</span>
          <select name="svc-service" :value="sv.service" @change="set('service', $event.target.value)">
            <option v-for="o in vm.services" :key="o.id" :value="o.title">{{ o.title }}</option>
          </select>
        </label>
        <div class="grid2">
          <label class="fld"><span>{{ L.nameLbl }} <span aria-hidden="true">*</span></span>
            <input name="svc-name" type="text" autocomplete="name" :value="sv.name" :placeholder="L.fNamePh" :aria-invalid="!!err.name" :class="{ bad: err.name }" @input="set('name', $event.target.value)">
            <span v-if="err.name" role="alert" class="fld-err">{{ err.name }}</span>
          </label>
          <label class="fld"><span>{{ L.emailLbl }} <span aria-hidden="true">*</span></span>
            <input name="svc-email" type="email" autocomplete="email" :value="sv.email" :placeholder="L.emailPh" :aria-invalid="!!err.email" :class="{ bad: err.email }" @input="set('email', $event.target.value)">
            <span v-if="err.email" role="alert" class="fld-err">{{ err.email }}</span>
          </label>
        </div>
        <div class="grid2">
          <label class="fld"><span>{{ L.phoneLbl }}</span>
            <input name="svc-phone" type="tel" autocomplete="tel" :value="sv.phone" placeholder="+229 …" @input="set('phone', $event.target.value)">
          </label>
          <label class="fld"><span>{{ L.svcWhen }}</span>
            <select name="svc-when" :value="sv.when || L.choose" @change="set('when', $event.target.value)">
              <option v-for="w in whens" :key="w" :value="w">{{ w }}</option>
            </select>
          </label>
        </div>
        <label class="fld"><span>{{ L.svcNeed }} <span aria-hidden="true">*</span></span>
          <textarea name="svc-message" rows="5" :value="sv.message" :placeholder="L.svcNeedPh" :aria-invalid="!!err.message" :class="{ bad: err.message }" @input="set('message', $event.target.value)"></textarea>
          <span v-if="err.message" role="alert" class="fld-err">{{ err.message }}</span>
        </label>
        <p class="form-note">{{ L.formPrivacy }} <RouterLink :to="vm.hrefs.privacy" @click="closeSvc">{{ L.privacyLink }}</RouterLink></p>
        <div class="svc-actions">
          <button type="button" class="btn btn-ghost" @click="closeSvc">{{ L.cancel }}</button>
          <button type="submit" class="btn btn-primary" :disabled="state.svcFs === 'sending'"><span class="ms">send</span>{{ state.svcFs === 'sending' ? L.svcSending : L.svcSend }}</button>
        </div>
      </form>
    </div>
  </div>
</template>
