<script setup>
import { computed } from 'vue';
import { api } from '../../shared/util';
import { TRASH_HINT, ask, fmtMD, save, state, t, trash } from '../store';

const DEFS = [
  ['all', 'Tous', (m) => m.status !== 'archived'],
  ['new', 'Nouveaux', (m) => m.status === 'new'],
  ['service', 'Demandes de service', (m) => m.type === 'service' && m.status !== 'archived'],
  ['contact', 'Contact', (m) => m.type !== 'service' && m.status !== 'archived'],
  ['archived', 'Archivés', (m) => m.status === 'archived'],
];
const ST = { new: ['Nouveau', '#2448C8', '#FFFFFF'], read: ['Lu', 'var(--ln2)', 'var(--mu)'], replied: ['Répondu', '#E7F6EE', '#1E6B45'], archived: ['Archivé', 'var(--ln2)', 'var(--mu)'] };

const msgs = computed(() => (state.data.messages || []).slice().sort((a, b) => String(b.date).localeCompare(String(a.date)) || b.id - a.id));
const filters = computed(() => DEFS.map(([k, label, fn]) => ({ k, label, count: msgs.value.filter(fn).length })));
const inbox = computed(() => msgs.value.filter((DEFS.find((x) => x[0] === state.mf) || DEFS[0])[2]));
const cm = computed(() => msgs.value.find((m) => m.id === state.msgId));
const mobile = computed(() => state.w < 1000);

const setStatus = (m, status, msg) => save(() => { const x = state.data.messages.find((y) => y.id === m.id); if (x) x.status = status; }, msg, () => api.patch('/admin/messages/' + m.id, { status, activity: msg }));
const open = (m) => { state.msgId = m.id; if (m.status === 'new') setStatus(m, 'read'); };
const meta = (m) => [{ label: 'Nom', value: m.name }, { label: 'E-mail', value: m.email }, m.phone && { label: 'Téléphone', value: m.phone }, m.service && { label: 'Service', value: m.service }, { label: 'Langue', value: (m.lang || 'fr').toUpperCase() }].filter(Boolean);
const mailto = (m) => 'mailto:' + m.email + '?subject=' + encodeURIComponent('Re: ' + (t(m.subject) || 'Votre message'));
const digits = (m) => String(m.phone || '').replace(/\D/g, '');
const del = (m) => ask('Placer ce message dans la corbeille ?', TRASH_HINT, () => {
  save((d) => { d.messages = d.messages.filter((x) => x.id !== m.id); }, 'Message placé dans la corbeille', () => trash(api.delete('/admin/messages/' + m.id)));
  state.msgId = null;
}, 'Mettre à la corbeille');
</script>

<template>
  <div class="row-wrap gap8">
    <button v-for="f in filters" :key="f.k" type="button" class="mfilter" :class="{ on: state.mf === f.k }" :aria-pressed="state.mf === f.k" @click="state.mf = f.k">{{ f.label }} <span class="mono op75">{{ f.count }}</span></button>
  </div>
  <div class="inbox" :style="{ gridTemplateColumns: mobile || !cm ? 'minmax(0,1fr)' : 'minmax(0,1fr) minmax(0,1.3fr)' }">
    <div class="panel"><div class="inbox-list">
      <p v-if="!inbox.length" class="empty-txt big">Aucun message dans ce filtre.</p>
      <button v-for="m in inbox" :key="m.id" type="button" class="msg-row lg" :style="{ background: state.msgId === m.id ? 'var(--acs)' : 'transparent' }" @click="open(m)">
        <span class="msg-ico ms">{{ m.type === 'service' ? 'design_services' : 'mail' }}</span>
        <span class="msg-txt gap3">
          <span class="msg-l1"><strong :style="{ fontWeight: m.status === 'new' ? 700 : 500 }">{{ m.name }}</strong><span>{{ fmtMD(m.date) }}</span></span>
          <span class="msg-subj">{{ t(m.subject) || '(sans sujet)' }}</span>
          <span class="msg-sub">{{ String(m.body || '').split('\n')[0] }}</span>
          <span class="row gap6 mt2">
            <span class="st-tag" :style="{ background: (ST[m.status] || ST.read)[1], color: (ST[m.status] || ST.read)[2] }">{{ (ST[m.status] || ST.read)[0] }}</span>
            <span v-if="m.type === 'service'" class="st-tag svc">Demande de service</span>
          </span>
        </span>
      </button>
    </div></div>

    <div v-if="cm" class="panel"><div class="msg-view">
      <div class="msg-view-head">
        <div class="col gap4 minw0"><span class="eyebrow">{{ cm.type === 'service' ? 'Demande de service' : 'Message de contact' }}</span><h2>{{ t(cm.subject) || '(sans sujet)' }}</h2></div>
        <span class="sm-mu">{{ fmtMD(cm.date) }}</span>
      </div>
      <dl class="msg-meta"><div v-for="mm in meta(cm)" :key="mm.label"><dt>{{ mm.label }}</dt><dd>{{ mm.value }}</dd></div></dl>
      <p class="msg-body">{{ cm.body }}</p>
      <div class="msg-actions">
        <a :href="mailto(cm)" class="abtn primary" @click="setStatus(cm, 'replied')"><span class="ms">reply</span>Répondre par e-mail</a>
        <a v-if="digits(cm).length > 6" :href="'https://wa.me/' + digits(cm)" target="_blank" rel="noopener" class="abtn"><span class="ms">chat</span>WhatsApp</a>
        <button type="button" class="abtn" @click="setStatus(cm, cm.status === 'new' ? 'read' : 'new')"><span class="ms">mark_email_read</span>{{ cm.status === 'new' ? 'Marquer comme lu' : 'Marquer comme non lu' }}</button>
        <button type="button" class="abtn" @click="setStatus(cm, cm.status === 'archived' ? 'read' : 'archived', cm.status === 'archived' ? 'Message restauré' : 'Message archivé')"><span class="ms">archive</span>{{ cm.status === 'archived' ? 'Désarchiver' : 'Archiver' }}</button>
        <button type="button" class="abtn red" @click="del(cm)"><span class="ms">delete</span>Corbeille</button>
      </div>
    </div></div>
  </div>
</template>
