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
