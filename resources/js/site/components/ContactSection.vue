<script setup>
import { setField, state, submitContact, vm } from '../store';
import SocialIcon from '../../shared/SocialIcon.vue';
</script>

<template>
  <section :aria-label="vm.L.contactLabel" class="sec-contact">
    <div class="wrap contact-in">
      <form novalidate class="contact-form" @submit.prevent="submitContact">
        <h2 class="contact-title">{{ vm.L.formTitle }}</h2>
        <div class="grid2 gap20">
          <label class="fld up"><span>{{ vm.L.fName }} <span aria-hidden="true">*</span></span>
            <input name="name" autocomplete="name" :value="state.cf.name" :placeholder="vm.L.fNamePh" :aria-invalid="!!state.errs.name" :class="{ bad: state.errs.name }" @input="setField('name', $event.target.value)">
            <span v-if="state.errs.name" role="alert" class="fld-err">{{ state.errs.name }}</span>
          </label>
          <label class="fld up"><span>{{ vm.L.email }} <span aria-hidden="true">*</span></span>
            <input name="email" type="email" autocomplete="email" :value="state.cf.email" :placeholder="vm.L.emailPh" :aria-invalid="!!state.errs.email" :class="{ bad: state.errs.email }" @input="setField('email', $event.target.value)">
            <span v-if="state.errs.email" role="alert" class="fld-err">{{ state.errs.email }}</span>
          </label>
        </div>
        <label class="fld up"><span>{{ vm.L.subject }}</span>
          <input name="subject" :value="state.cf.subject" :placeholder="vm.L.subjectPh" @input="setField('subject', $event.target.value)">
        </label>
        <label class="fld up"><span>{{ vm.L.message }} <span aria-hidden="true">*</span></span>
          <textarea name="message" rows="7" :value="state.cf.message" :placeholder="vm.L.messagePh" :aria-invalid="!!state.errs.message" :class="{ bad: state.errs.message }" @input="setField('message', $event.target.value)"></textarea>
          <span v-if="state.errs.message" role="alert" class="fld-err">{{ state.errs.message }}</span>
        </label>
        <div class="row-wrap center gap16">
          <button type="submit" class="btn btn-primary xl" :disabled="state.fs === 'sending'"><span class="ms">send</span>{{ state.fs === 'sending' ? vm.L.sending : vm.L.send }}</button>
          <p v-if="state.fs === 'sent'" role="status" class="sent"><span class="ms">check_circle</span>{{ vm.L.sent }}</p>
          <p v-if="state.fs === 'failed'" role="alert" class="fld-err">{{ vm.L.errSend }}</p>
        </div>
        <p class="form-note">{{ vm.L.formPrivacy }} <RouterLink :to="vm.hrefs.privacy">{{ vm.L.privacyLink }}</RouterLink></p>
      </form>

      <aside class="contact-aside">
        <dl class="info-list">
          <div v-for="cr in vm.contactRows" :key="cr.num">
            <dt>§{{ cr.num }} · {{ cr.label }}</dt>
            <dd class="lg"><a v-if="cr.href" :href="cr.href" class="ink-link">{{ cr.value }}</a><template v-else>{{ cr.value }}</template></dd>
          </div>
          <div>
            <dt class="dt-gap"><span>§</span>{{ vm.availNum }} · {{ vm.L.availShort }}</dt>
            <dd class="reg">{{ vm.L.replyTime }}</dd>
          </div>
        </dl>
        <div class="col gap12">
          <span class="mono-12 muted">{{ vm.L.follow }}</span>
          <div class="row-wrap gap10">
            <a v-for="so in vm.socials" :key="so.label" :href="so.url" target="_blank" rel="noopener" :aria-label="so.label" :title="so.label" class="soc"><SocialIcon :url="so.url" :label="so.label" :size="22" /></a>
          </div>
        </div>
      </aside>
    </div>
  </section>
</template>
