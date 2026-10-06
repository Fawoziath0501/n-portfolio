<script setup>
// Répondre à un message ou à une demande de service : par e-mail (envoyé depuis le site, via le SMTP des Paramètres)
// ou par WhatsApp (ouvert avec le texte prérempli). Chaque réponse est gardée dans l'historique du message.
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../../shared/util';
import SocialIcon from '../../shared/SocialIcon.vue';
import { flash, flashError, go, state, t } from '../store';

const props = defineProps({ message: Object });
const router = useRouter();

const owner = computed(() => [state.data.profile.firstName, state.data.profile.lastName].filter(Boolean).join(' '));
const phone = computed(() => String(props.message.phone || '').replace(/\D/g, ''));
const ready = computed(() => !!(state.data.mail && state.data.mail.ready));
const greeting = (m) => (m.lang === 'en'
  ? 'Hello ' + m.name + ',\n\nThank you for your message.\n\n\nBest regards,\n' + owner.value
  : 'Bonjour ' + m.name + ',\n\nMerci pour votre message.\n\n\nBien cordialement,\n' + owner.value);

const channel = ref('email');
const subject = ref('');
const body = ref('');
const busy = ref(false);
const reset = () => {
  const m = props.message;
  channel.value = 'email';
  subject.value = (m.lang === 'en' ? 'Re: ' : 'Re : ') + (t(m.subject) || t(m.service) || (m.lang === 'en' ? 'your message' : 'votre message'));
  body.value = greeting(m);
};
watch(() => props.message.id, reset, { immediate: true });

const replace = (data) => { const i = state.data.messages.findIndex((x) => x.id === data.id); if (i >= 0) state.data.messages[i] = data; };

async function sendEmail() {
  busy.value = true;
  try {
    const { data } = await api.post('/admin/messages/' + props.message.id + '/reply', { subject: subject.value, body: body.value });
    replace(data);
    flash('E-mail envoyé à ' + props.message.email);
    reset();
  } catch (e) { flashError(e); } finally { busy.value = false; }
}
async function sendWhatsapp() {
  window.open('https://wa.me/' + phone.value + '?text=' + encodeURIComponent(body.value), '_blank', 'noopener');
  try {
    const { data } = await api.post('/admin/messages/' + props.message.id + '/whatsapp', { body: body.value });
    replace(data);
    flash('Réponse WhatsApp ouverte et gardée dans l’historique');
    reset();
  } catch (e) { flashError(e); }
}
const fmt = (iso) => new Date(iso).toLocaleString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
</script>

<template>
  <div class="reply">
    <div v-if="message.replies && message.replies.length" class="thread">
      <span class="eyebrow">Échanges ({{ message.replies.length }})</span>
      <div v-for="r in message.replies" :key="r.id" class="thread-item">
        <div class="thread-meta">
          <span class="row center gap6"><SocialIcon v-if="r.channel === 'whatsapp'" url="https://wa.me/" label="WhatsApp" :size="14" /><span v-else class="ms" aria-hidden="true">mail</span>{{ r.channel === 'whatsapp' ? 'WhatsApp' : 'E-mail' }}<template v-if="r.subject"> · {{ r.subject }}</template></span>
          <span>{{ fmt(r.at) }}</span>
        </div>
        <p class="thread-body">{{ r.body }}</p>
      </div>
    </div>

    <div class="composer">
      <div role="tablist" aria-label="Canal de réponse" class="seg-tabs">
        <button type="button" role="tab" :aria-selected="channel === 'email'" :class="{ on: channel === 'email' }" @click="channel = 'email'"><span class="ms">mail</span>E-mail</button>
        <button type="button" role="tab" :aria-selected="channel === 'whatsapp'" :class="{ on: channel === 'whatsapp' }" :disabled="phone.length < 7" :title="phone.length < 7 ? 'Pas de numéro de téléphone' : ''" @click="channel = 'whatsapp'"><SocialIcon url="https://wa.me/" label="WhatsApp" :size="16" />WhatsApp</button>
      </div>
      <label v-if="channel === 'email'" class="field"><span class="field-head"><span>Objet</span></span><input v-model="subject" class="ain"></label>
      <label class="field"><span class="field-head"><span>Message</span><span class="sm-mu">{{ channel === 'email' ? 'à ' + message.email : 'au ' + message.phone }}</span></span>
        <textarea v-model="body" rows="7" class="ain area"></textarea></label>

      <div v-if="channel === 'email'" class="row-wrap center gap8">
        <button type="button" class="abtn primary" :disabled="busy || !ready || !body.trim() || !subject.trim()" @click="sendEmail"><span class="ms">send</span>{{ busy ? 'Envoi…' : 'Envoyer l’e-mail' }}</button>
        <span v-if="!ready" class="sm-mu">Envoi non configuré : <button type="button" class="linkbtn" @click="go(router, 'settings')">réglez le serveur SMTP</button>, ou <a :href="'mailto:' + message.email + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body)">ouvrez votre messagerie</a>.</span>
      </div>
      <div v-else class="row-wrap center gap8">
        <button type="button" class="abtn primary" :disabled="!body.trim()" @click="sendWhatsapp"><SocialIcon url="https://wa.me/" label="WhatsApp" :size="16" />Ouvrir dans WhatsApp</button>
        <span class="sm-mu">Le message s’ouvre dans WhatsApp, prêt à être envoyé.</span>
      </div>
    </div>
  </div>
</template>
