<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Texte enrichi saisi dans l'administration (éditeur Tiptap) : seules les balises de mise en forme
 * sont conservées, tout le reste (scripts, styles, attributs d'événement…) est retiré avant l'enregistrement.
 */
class RichText
{
    private const TAGS = ['p', 'br', 'strong', 'em', 'u', 's', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote', 'a', 'img', 'code'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '' || preg_match('#^<p>\s*</p>$#', $html)) {
            return '';
        }

        $clean = self::sanitizer()->sanitize(self::isHtml($html) ? $html : self::fromPlain($html));

        return trim(preg_replace('#<img(?![^>]*\bsrc=)[^>]*>#', '', $clean)); // image dont l'adresse a été refusée
    }

    public static function isHtml(string $text): bool
    {
        return (bool) preg_match('#<(p|br|ul|ol|h[1-6]|blockquote|strong|em|a|img)\b[^>]*>#i', $text);
    }

    /** Texte brut (contenu d'origine, ancien format) → un paragraphe par ligne. */
    public static function fromPlain(string $text): string
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)), 'strlen');

        return implode('', array_map(fn ($l) => '<p>'.e($l).'</p>', $lines));
    }

    /** Nettoie chaque langue d'un champ { fr, en }. */
    public static function cleanI18n(mixed $value): mixed
    {
        if (! is_array($value)) {
            return is_string($value) ? self::clean($value) : $value;
        }

        return array_map(fn ($v) => is_string($v) ? self::clean($v) : $v, $value);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        static $sanitizer;
        if ($sanitizer) {
            return $sanitizer;
        }

        $config = new HtmlSanitizerConfig;
        foreach (self::TAGS as $tag) {
            $config = $config->allowElement($tag, match ($tag) { 'a' => ['href'], 'img' => ['src', 'alt'], default => [] });
        }
        // Balises de mise en forme d'autres éditeurs : retirées, mais leur texte est gardé.
        foreach (['b', 'i', 'span', 'div', 'font', 'h1', 'h4', 'h5', 'h6'] as $tag) {
            $config = $config->blockElement($tag);
        }
        $config = $config
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['https', 'http'])
            ->allowRelativeMedias()
            ->forceAttribute('a', 'rel', 'noopener noreferrer');

        return $sanitizer = new HtmlSanitizer($config);
    }
}
