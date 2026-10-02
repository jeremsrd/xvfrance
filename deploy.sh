#!/usr/bin/env bash
#
# Déploiement de xvfrance.fr sur O2switch depuis la machine locale.
#
#   ./deploy.sh              tests, build CSS, envoi des fichiers, mise à jour du serveur
#   ./deploy.sh --dry-run    affiche ce qui serait envoyé, sans rien modifier
#   ./deploy.sh --skip-tests
#
# Configuration : copier .deploy.env.example en .deploy.env (non versionné).
# Seul le contenu commité est déployé (git archive), plus le CSS compilé (public/build).

set -euo pipefail

cd "$(dirname "$0")"

DRY_RUN=false
SKIP_TESTS=false
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=true ;;
        --skip-tests) SKIP_TESTS=true ;;
        *) echo "Option inconnue : $arg" >&2; exit 1 ;;
    esac
done

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }
fail() { printf '\033[1;31m%s\033[0m\n' "$1" >&2; exit 1; }

# --- Configuration ---------------------------------------------------------

[[ -f .deploy.env ]] || fail "Fichier .deploy.env manquant (modèle : .deploy.env.example)."
# shellcheck source=/dev/null
source .deploy.env
: "${DEPLOY_SSH:?DEPLOY_SSH manquant dans .deploy.env}"
: "${DEPLOY_PATH:?DEPLOY_PATH manquant dans .deploy.env}"
DEPLOY_PHP="${DEPLOY_PHP:-php}"
DEPLOY_COMPOSER="${DEPLOY_COMPOSER:-composer}"

# --- Vérifications locales -------------------------------------------------

step "Vérification du dépôt"
[[ -z "$(git status --porcelain --untracked-files=no)" ]] \
    || fail "Des modifications ne sont pas commitées : seul le contenu commité est déployé."
COMMIT="$(git rev-parse --short HEAD)"
echo "Déploiement du commit $COMMIT ($(git rev-parse --abbrev-ref HEAD))"

if [[ "$SKIP_TESTS" == false ]]; then
    step "Tests"
    php artisan test
fi

step "Build du CSS"
npm run build

# --- Préparation de l'archive ----------------------------------------------

BUILD_DIR="$(mktemp -d)"
trap 'rm -rf "$BUILD_DIR"' EXIT

git archive HEAD | tar -x -C "$BUILD_DIR"
mkdir -p "$BUILD_DIR/public"
cp -R public/build "$BUILD_DIR/public/build"

# Fichiers inutiles en production
rm -rf "$BUILD_DIR/tests" "$BUILD_DIR/.github" "$BUILD_DIR/phpunit.xml" \
       "$BUILD_DIR/deploy.sh" "$BUILD_DIR/.deploy.env.example"

# --- Envoi -----------------------------------------------------------------

# Chemins propres au serveur : jamais envoyés ni supprimés par --delete
RSYNC_EXCLUDES=(
    --exclude=/.env
    --exclude=/vendor/
    --exclude=/storage/
    --exclude=/bootstrap/cache/
    --exclude=/public/storage
    --exclude=/public/hot
)

step "Envoi des fichiers vers $DEPLOY_SSH:$DEPLOY_PATH"
if [[ "$DRY_RUN" == true ]]; then
    rsync -az --delete --dry-run -v "${RSYNC_EXCLUDES[@]}" "$BUILD_DIR/" "$DEPLOY_SSH:$DEPLOY_PATH/"
    echo
    echo "Dry-run : aucun fichier envoyé, aucune commande exécutée sur le serveur."
    exit 0
fi

ssh "$DEPLOY_SSH" "mkdir -p '$DEPLOY_PATH' && cd '$DEPLOY_PATH' && if [ -f artisan ] && [ -d vendor ]; then $DEPLOY_PHP artisan down --retry=60 || true; fi"
rsync -az --delete "${RSYNC_EXCLUDES[@]}" "$BUILD_DIR/" "$DEPLOY_SSH:$DEPLOY_PATH/"

# --- Mise à jour sur le serveur --------------------------------------------

step "Mise à jour du serveur"
ssh "$DEPLOY_SSH" bash -s <<REMOTE
set -euo pipefail
cd '$DEPLOY_PATH'
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
[ -f .env ] || { echo ".env absent sur le serveur : à créer avant le premier déploiement." >&2; exit 1; }
$DEPLOY_COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress
$DEPLOY_PHP artisan migrate --force
$DEPLOY_PHP artisan optimize
$DEPLOY_PHP artisan filament:optimize
[ -L public/storage ] || $DEPLOY_PHP artisan storage:link
$DEPLOY_PHP artisan up
REMOTE

step "Commit $COMMIT déployé"
