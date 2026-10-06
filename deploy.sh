#!/usr/bin/env bash
# Déploiement du portfolio sur Alwaysdata, en une commande, sans toucher aux données du site.
#
#   bash deploy.sh                       tests, compilation, envoi, mise à jour du serveur, vérification
#   bash deploy.sh --dry-run             vérifie tout (tests, compilation, connexion) sans rien modifier en ligne
#   bash deploy.sh --skip-tests          saute les tests (déconseillé)
#   bash deploy.sh --sync "ciste wura"   reprend aussi ces projets depuis les données de départ
#                                        (textes et captures de database/seeders), sans toucher au reste
#
# Réglages (variables d'environnement, valeurs par défaut ci-dessous) :
#   DEPLOY_HOST  utilisateur@hôte SSH       DEPLOY_KEY  clé SSH privée
#   DEPLOY_DIR   dossier sur le serveur     SITE_URL    adresse publique du site
set -euo pipefail

DEPLOY_HOST="${DEPLOY_HOST:-fawoziathsalou@ssh-fawoziathsalou.alwaysdata.net}"
DEPLOY_KEY="${DEPLOY_KEY:-$HOME/.ssh/alwaysdata_portfolio}"
DEPLOY_DIR="${DEPLOY_DIR:-n-portfolio}"
SITE_URL="${SITE_URL:-https://fawoziathsalou.alwaysdata.net}"
BRANCH=main

DRY=0; TESTS=1; SYNC=""
while [ $# -gt 0 ]; do
  case "$1" in
    --dry-run) DRY=1 ;;
    --skip-tests) TESTS=0 ;;
    --sync) SYNC="${2:?--sync attend une liste de projets}"; shift ;;
    -h|--help) sed -n '2,12p' "$0"; exit 0 ;;
    *) echo "Option inconnue : $1 (voir bash deploy.sh --help)"; exit 1 ;;
  esac
  shift
done

step() { printf '\n\033[1;34m▸ %s\033[0m\n' "$1"; }
fail() { printf '\n\033[1;31m✗ %s\033[0m\n' "$1"; exit 1; }
SSH=(ssh -i "$DEPLOY_KEY" -o BatchMode=yes -o ConnectTimeout=20)

cd "$(dirname "$0")"

step "Vérifications"
[ -f "$DEPLOY_KEY" ] || fail "Clé SSH introuvable : $DEPLOY_KEY"
[ "$(git rev-parse --abbrev-ref HEAD)" = "$BRANCH" ] || fail "Placez-vous sur la branche $BRANCH avant de déployer."
[ -z "$(git status --porcelain)" ] || { git status --short; fail "Des modifications ne sont pas committées : committez-les (ou annulez-les) avant de déployer."; }
"${SSH[@]}" "$DEPLOY_HOST" "test -d ~/$DEPLOY_DIR" || fail "Connexion SSH impossible ou dossier ~/$DEPLOY_DIR absent sur le serveur."
echo "Branche $BRANCH propre, serveur joignable."

if [ "$TESTS" = 1 ]; then
  step "Tests"
  php artisan test --compact || fail "Des tests échouent : déploiement annulé."
fi

step "Compilation de l'interface"
npm run build --silent || fail "La compilation a échoué."
ARCHIVE="$(mktemp -t build-XXXXXX).tgz"
tar -czf "$ARCHIVE" -C public build

if [ "$DRY" = 1 ]; then
  rm -f "$ARCHIVE"
  step "Essai à blanc terminé : rien n'a été envoyé ni modifié en ligne."
  exit 0
fi

step "Envoi du code sur GitHub"
git push origin "$BRANCH"

step "Envoi de l'interface compilée"
scp -q -i "$DEPLOY_KEY" -o BatchMode=yes "$ARCHIVE" "$DEPLOY_HOST:$DEPLOY_DIR/build.tgz"
rm -f "$ARCHIVE"

step "Mise à jour du serveur"
"${SSH[@]}" "$DEPLOY_HOST" "SYNC='$SYNC' bash -s" <<REMOTE
set -euo pipefail
cd ~/$DEPLOY_DIR
# Sauvegarde de la base (les 10 plus récentes sont gardées).
cp database/database.sqlite "database/backup-\$(date +%Y%m%d-%H%M%S).sqlite"
ls -1t database/backup-*.sqlite | tail -n +11 | xargs -r rm -f
git pull --ff-only -q origin $BRANCH
echo "Code : \$(git log --oneline -1)"
rm -rf public/build && tar -xzf build.tgz -C public && rm -f build.tgz
composer install --no-dev --optimize-autoloader --no-interaction -q
php artisan migrate --force
php artisan portfolio:thumbs -q   # versions allégées des images qui n'en ont pas encore
if [ -n "\$SYNC" ]; then php artisan portfolio:sync-projects \$SYNC; fi
php artisan optimize:clear -q
php artisan config:cache -q
php artisan route:cache -q
php artisan view:cache -q
REMOTE

step "Vérification du site"
for path in /fr /en /sitemap.xml; do
  code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 30 "$SITE_URL$path")"
  [ "$code" = 200 ] || fail "$SITE_URL$path répond $code : vérifiez les journaux (Administration → Journaux) ou restaurez la sauvegarde de la base."
  echo "$path : $code"
done

printf '\n\033[1;32m✓ Déploiement terminé : %s\033[0m\n' "$SITE_URL"
