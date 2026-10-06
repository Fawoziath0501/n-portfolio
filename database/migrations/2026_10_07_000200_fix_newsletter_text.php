<?php

use App\Models\UiLabel;
use Illuminate\Database\Migrations\Migration;

/**
 * Le texte de la newsletter promettait une « désinscription en un clic », qui n'existe pas :
 * on le remplace, seulement s'il n'a pas été modifié depuis l'administration.
 */
return new class extends Migration
{
    private const OLD = ['Recevez mes nouveaux articles sur le développement web et le SEO. Pas de spam, désinscription en un clic.', 'Get my new articles on web development and SEO. No spam, unsubscribe in one click.'];

    private const NEW = ['Recevez mes nouveaux articles sur le développement web et le SEO. Pas de spam, désinscription sur simple demande.', 'Get my new articles on web development and SEO. No spam, unsubscribe anytime on request.'];

    public function up(): void
    {
        UiLabel::where('key', 'nlText')->where('fr', self::OLD[0])->update(['fr' => self::NEW[0]]);
        UiLabel::where('key', 'nlText')->where('en', self::OLD[1])->update(['en' => self::NEW[1]]);
    }

    public function down(): void
    {
        UiLabel::where('key', 'nlText')->where('fr', self::NEW[0])->update(['fr' => self::OLD[0]]);
        UiLabel::where('key', 'nlText')->where('en', self::NEW[1])->update(['en' => self::OLD[1]]);
    }
};
