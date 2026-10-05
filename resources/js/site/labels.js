// Textes de l'interface publique : ils viennent de la table ui_labels (modifiables dans l'administration).
// Les clés numérotées (nav.0, nav.1…) redeviennent des listes : L.nav[0], L.nav[1]…

export function labels(dict, lang) {
    const L = {};
    for (const [key, v] of Object.entries(dict || {})) {
        const val = (v && (v[lang] || v.fr)) || '';
        const m = key.match(/^(.+)\.(\d+)$/);
        if (m) (L[m[1]] = L[m[1]] || [])[Number(m[2])] = val;
        else L[key] = val;
    }
    return L;
}

/** Remplace les variables {nom} d'un texte. */
export const fill = (text, vars) => String(text || '').replace(/\{(\w+)\}/g, (_, k) => (vars[k] ?? ''));
