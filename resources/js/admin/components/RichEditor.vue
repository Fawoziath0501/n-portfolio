<script setup>
// Éditeur de texte enrichi (Tiptap) : titres, gras, italique, souligné, listes, citation, liens.
// Le HTML produit est nettoyé par le serveur à l'enregistrement (App\Support\RichText).
import { onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { richHtml } from '../../shared/util';
import { state } from '../store';

const props = defineProps({ modelValue: { type: String, default: '' }, label: String });
const emit = defineEmits(['update:modelValue']);

const html = (ed) => (ed.isEmpty ? '' : ed.getHTML());

const editor = useEditor({
  content: richHtml(props.modelValue),
  extensions: [StarterKit.configure({
    heading: { levels: [2, 3] },
    code: false, codeBlock: false, horizontalRule: false,
    link: { openOnClick: false, autolink: true, defaultProtocol: 'https' },
  }), Image.configure({ allowBase64: false })],
  editorProps: { attributes: { class: 'rte-content', 'aria-label': props.label || 'Texte', 'aria-multiline': 'true', role: 'textbox' } },
  onUpdate: ({ editor: ed }) => emit('update:modelValue', html(ed)),
});

// Valeur remplacée de l'extérieur (autre élément ouvert, restauration…) : on recharge le contenu.
watch(() => props.modelValue, (v) => {
  const ed = editor.value;
  if (ed && (v || '') !== html(ed)) ed.commands.setContent(richHtml(v), { emitUpdate: false });
});

onBeforeUnmount(() => editor.value && editor.value.destroy());

const run = (fn) => { const ch = editor.value.chain().focus(); fn(ch).run(); };
const active = (name, attrs) => !!editor.value && editor.value.isActive(name, attrs);
// Image : choisie dans la médiathèque (le texte alternatif reprend le nom du fichier, modifiable ensuite).
function insertImage() {
  state.pick = { accept: 'image', onPick: (m) => run((c) => c.setImage({ src: m.src, alt: (m.name || '').replace(/[.][a-z0-9]+$/i, '') })) };
}
function setLink() {
  const prev = editor.value.getAttributes('link').href || '';
  const url = window.prompt('Adresse du lien (https://…, mailto:…) — laisser vide pour retirer le lien', prev);
  if (url === null) return;
  if (!url.trim()) run((c) => c.extendMarkRange('link').unsetLink());
  else run((c) => c.extendMarkRange('link').setLink({ href: url.trim() }));
}

const tools = [
  ['format_h2', 'Titre', (c) => c.toggleHeading({ level: 2 }), () => active('heading', { level: 2 })],
  ['format_h3', 'Sous-titre', (c) => c.toggleHeading({ level: 3 }), () => active('heading', { level: 3 })],
  null,
  ['format_bold', 'Gras', (c) => c.toggleBold(), () => active('bold')],
  ['format_italic', 'Italique', (c) => c.toggleItalic(), () => active('italic')],
  ['format_underlined', 'Souligné', (c) => c.toggleUnderline(), () => active('underline')],
  null,
  ['format_list_bulleted', 'Liste à puces', (c) => c.toggleBulletList(), () => active('bulletList')],
  ['format_list_numbered', 'Liste numérotée', (c) => c.toggleOrderedList(), () => active('orderedList')],
  ['format_quote', 'Citation', (c) => c.toggleBlockquote(), () => active('blockquote')],
];
</script>

<template>
  <div class="rte">
    <div v-if="editor" class="rte-bar" role="toolbar" :aria-label="'Mise en forme' + (label ? ' · ' + label : '')">
      <template v-for="(tl, i) in tools" :key="i">
        <span v-if="!tl" class="rte-sep" aria-hidden="true"></span>
        <button v-else type="button" class="rte-btn" :class="{ on: tl[3]() }" :aria-pressed="tl[3]()" :title="tl[1]" :aria-label="tl[1]" @click="run(tl[2])"><span class="ms">{{ tl[0] }}</span></button>
      </template>
      <button type="button" class="rte-btn" :class="{ on: active('link') }" title="Lien" aria-label="Lien" @click="setLink"><span class="ms">link</span></button>
      <button type="button" class="rte-btn" title="Image (médiathèque)" aria-label="Insérer une image" @click="insertImage"><span class="ms">image</span></button>
      <span class="rte-sep" aria-hidden="true"></span>
      <button type="button" class="rte-btn" title="Annuler" aria-label="Annuler" :disabled="!editor.can().undo()" @click="run((c) => c.undo())"><span class="ms">undo</span></button>
      <button type="button" class="rte-btn" title="Rétablir" aria-label="Rétablir" :disabled="!editor.can().redo()" @click="run((c) => c.redo())"><span class="ms">redo</span></button>
    </div>
    <EditorContent :editor="editor" />
  </div>
</template>
