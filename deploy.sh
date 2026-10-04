#!/usr/bin/env bash
#
# Déploiement de xvfrance.fr sur O2switch depuis la machine locale.
#
#   ./deploy.sh              tests, build CSS, envoi des fichiers, mise à jour du serveur
#   ./deploy.sh --dry-run    affiche ce qui serait envoyé, sans rien modifier sur le serveur
#   ./deploy.sh --skip-tests
#
# Configuration : copier .deploy.env.example en .deploy.env (non versionné).
# Seul le contenu commité est déployé (git archive), plus le CSS compilé (public/build).
#
# O2switch n'ouvre SSH qu'aux IP en liste blanche : le script autorise l'IP courante
# via l'API cPanel (outil « Autorisation SSH ») le temps du déploiement, puis la retire.

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
: "${CPANEL_HOST:?CPANEL_HOST manquant dans .deploy.env}"
: "${CPANEL_USER:?CPANEL_USER manquant dans .deploy.env}"
: "${CPANEL_TOKEN:?CPANEL_TOKEN manquant dans .deploy.env}"
DEPLOY_PHP="${DEPLOY_PHP:-php}"
DEPLOY_COMPOSER="${DEPLOY_COMPOSER:-composer}"
DEPLOY_PHP_HANDLER="${DEPLOY_PHP_HANDLER:-}"

SSH_OPTS=(-o AddressFamily=inet -o StrictHostKeyChecking=accept-new -o ConnectTimeout=20)

# --- Liste blanche SSH O2switch ---------------------------------------------

# Appel à l'API SshWhitelist ; échoue si cPanel ne répond pas status=1
whitelist_api() {
    local response
    response="$(curl -sS -m 45 -H "Authorization: cpanel $CPANEL_USER:$CPANEL_TOKEN" \
        "https://$CPANEL_HOST:2083/execute/SshWhitelist/$1")" || return 1
    if [[ "$(jq -r '.status // 0' <<<"$response" 2>/dev/null)" != "1" ]]; then
        echo "Réponse cPanel : $(jq -c '.errors // .' <<<"$response" 2>/dev/null || echo "$response")" >&2
        return 1
    fi
    printf '%s' "$response"
}

WHITELISTED_IP=""

cleanup() {
    [[ -n "${BUILD_DIR:-}" ]] && rm -rf "$BUILD_DIR"
    if [[ -n "$WHITELISTED_IP" ]]; then
        echo "Retrait de $WHITELISTED_IP de la liste blanche SSH"
        for direction in in out; do
            whitelist_api "remove?address=$WHITELISTED_IP&port=22&direction=$direction" >/dev/null \
                || echo "Retrait ($direction) échoué : à faire dans cPanel > Autorisation SSH" >&2
        done
    fi
}
trap cleanup EXIT

open_ssh_access() {
    step "Autorisation SSH de l'IP courante"
    local ip
    ip="$(curl -sS -4 -m 10 https://api.ipify.org)" || fail "Impossible de déterminer l'IP publique."
    [[ "$ip" =~ ^[0-9]+(\.[0-9]+){3}$ ]] || fail "IP publique inattendue : $ip"

    local list
    list="$(whitelist_api list)" || fail "API cPanel injoignable : vérifier CPANEL_HOST, CPANEL_USER et CPANEL_TOKEN."

    # data.ip est l'IP de l'appelant : seule data.list fait foi
    if jq -e --arg ip "$ip" 'any(.data.list[]?; .address == $ip and .port == 22)' <<<"$list" >/dev/null; then
        # Déjà autorisée (ex: IP fixe ajoutée à la main) : on n'y touche pas
        echo "$ip est déjà autorisée."
        return
    fi

    echo "Ajout de $ip (quelques secondes, le temps que le pare-feu l'applique)…"
    whitelist_api "add?address=$ip&port=22" >/dev/null \
        || fail "Ajout refusé (limite de 5 adresses atteinte ? voir cPanel > Autorisation SSH)."
    WHITELISTED_IP="$ip"
}

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
# mktemp crée un dossier privé (700) et rsync -a recopie ces droits sur le dossier du site :
# le serveur web ne pourrait plus y entrer (403)
chmod 755 "$BUILD_DIR"

git archive HEAD | tar -x -C "$BUILD_DIR"
mkdir -p "$BUILD_DIR/public"
cp -R public/build "$BUILD_DIR/public/build"

# Version de PHP du site, propre à ce domaine (directive .htaccess de l'hébergeur)
if [[ -n "$DEPLOY_PHP_HANDLER" ]]; then
    { echo "AddHandler $DEPLOY_PHP_HANDLER .php"; echo; cat "$BUILD_DIR/public/.htaccess"; } > "$BUILD_DIR/public/.htaccess.tmp"
    mv "$BUILD_DIR/public/.htaccess.tmp" "$BUILD_DIR/public/.htaccess"
fi

# Fichiers inutiles en production
rm -rf "$BUILD_DIR/tests" "$BUILD_DIR/.github" "$BUILD_DIR/phpunit.xml" \
       "$BUILD_DIR/deploy.sh" "$BUILD_DIR/.deploy.env.example"

# --- Envoi -----------------------------------------------------------------

open_ssh_access

# Chemins propres au serveur : jamais envoyés ni supprimés par --delete
RSYNC_EXCLUDES=(
    --exclude=/.env
    --exclude=/vendor/
    --exclude=/storage/
    --exclude=/bootstrap/cache/
    --exclude=/public/storage
    --exclude=/public/hot
)
RSYNC_SSH="ssh ${SSH_OPTS[*]}"

step "Envoi des fichiers vers $DEPLOY_SSH:$DEPLOY_PATH"
if [[ "$DRY_RUN" == true ]]; then
    rsync -az --delete --dry-run -v -e "$RSYNC_SSH" "${RSYNC_EXCLUDES[@]}" "$BUILD_DIR/" "$DEPLOY_SSH:$DEPLOY_PATH/"
    echo
    echo "Dry-run : aucun fichier envoyé, aucune commande exécutée sur le serveur."
    exit 0
fi

ssh "${SSH_OPTS[@]}" "$DEPLOY_SSH" "mkdir -p '$DEPLOY_PATH' && cd '$DEPLOY_PATH' && if [ -f artisan ] && [ -d vendor ]; then $DEPLOY_PHP artisan down --retry=60 || true; fi"
rsync -az --delete -e "$RSYNC_SSH" "${RSYNC_EXCLUDES[@]}" "$BUILD_DIR/" "$DEPLOY_SSH:$DEPLOY_PATH/"

# --- Mise à jour sur le serveur --------------------------------------------

step "Mise à jour du serveur"
ssh "${SSH_OPTS[@]}" "$DEPLOY_SSH" bash -s <<REMOTE
set -euo pipefail
cd '$DEPLOY_PATH'
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
[ -f .env ] || { echo ".env absent sur le serveur : à créer avant le premier déploiement." >&2; exit 1; }
$DEPLOY_COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress
$DEPLOY_PHP artisan migrate --force
# Feuilles de match versionnées : seuls les JSON nouveaux ou modifiés depuis le dernier import sont (ré)importés
$DEPLOY_PHP artisan xv:import-match-data database/data/matches --changed --no-interaction
$DEPLOY_PHP artisan optimize
$DEPLOY_PHP artisan filament:optimize
[ -L public/storage ] || $DEPLOY_PHP artisan storage:link
$DEPLOY_PHP artisan up
REMOTE

step "Commit $COMMIT déployé"
