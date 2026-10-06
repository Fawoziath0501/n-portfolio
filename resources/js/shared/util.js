import axios from 'axios';

// Session + CSRF : axios renvoie automatiquement le cookie XSRF-TOKEN de Laravel.
export const api = axios.create({
    baseURL: '/api',
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
    withCredentials: true,
});

/** Valeur traduite d'un champ { fr, en } (ou chaîne simple). */
export const tx = (v, lang) => {
    if (v == null) return '';
    if (typeof v === 'object') return v[lang] || v.fr || v.en || '';
    return String(v);
};

export const pad = (n) => String(n).padStart(2, '0');

/** Découpe un texte « un point par ligne ». */
export const lines = (v) => String(v || '').split('\n').map((s) => s.trim()).filter(Boolean).map((text, i) => ({ text, n: pad(i + 1) }));

export const host = (u) => (u || '').replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '');

export const isEmail = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v).trim());

export const reducedMotion = () => !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

export const escHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

export const isHtml = (s) => /<(p|br|ul|ol|h[1-6]|blockquote|strong|em|a)\b[^>]*>/i.test(String(s || ''));

/**
 * Texte enrichi saisi dans l'administration (HTML déjà nettoyé par le serveur), prêt pour v-html.
 * Un ancien texte brut devient un paragraphe par ligne, échappé.
 */
export const richHtml = (v) => {
    const s = String(v || '').trim();
    if (!s || /^<p>\s*<\/p>$/.test(s)) return '';
    return isHtml(s) ? s : s.split('\n').map((l) => l.trim()).filter(Boolean).map((l) => '<p>' + escHtml(l) + '</p>').join('');
};

/** Texte brut d'un contenu enrichi (aperçus, extraits). DOMParser ne charge aucune ressource. */
export const plainText = (v) => {
    const s = String(v || '');
    if (!isHtml(s)) return s;
    return (new DOMParser().parseFromString(s.replace(/<\/(p|li|h[23]|blockquote)>/g, '$& '), 'text/html').body.textContent || '').replace(/\s+/g, ' ').trim();
};
