#!/bin/sh
# Deploy theme + core plugin to the live site on Hostinger, from git:
# the server pulls origin/main into its clone (~/git/espamplus) and syncs it into wp-content.
# Commit and push first — only what is on origin/main gets deployed.
#   ./scripts/deploy.sh           # sync code, lint, purge cache, health check
#   ./scripts/deploy.sh --setup   # same, plus the one-time live setup (plugins, language, settings)
# Uploads, wp-config.php and the database are never touched by the sync.
set -eu
cd "$(dirname "$0")/.."

SSH_KEY="${SSH_KEY:-$HOME/.ssh/espam_deploy}"
# Server address lives in .deploy.env (gitignored, see .deploy.env.example) — this repo is public.
[ -f .deploy.env ] || { echo ".deploy.env missing (copy .deploy.env.example)" >&2; exit 1; }
. ./.deploy.env
: "${SSH_HOST:?set in .deploy.env}" "${SSH_PORT:?set in .deploy.env}"
SITE_URL="${SITE_URL:-https://espamplus.me}"
WP_ROOT='domains/espamplus.me/public_html'
GIT_DIR='git/espamplus'

SSH="ssh -i $SSH_KEY -p $SSH_PORT -o BatchMode=yes"
# The site runs PHP 8.3 (hPanel), but the SSH shell's default `php` is 8.2 — run WP-CLI and lint with 8.3.
PHP_BIN=/opt/alt/php83/usr/bin/php
remote() { $SSH "$SSH_HOST" "php() { $PHP_BIN \"\$@\"; }; wp() { $PHP_BIN /usr/local/bin/wp \"\$@\"; }; cd ~/$WP_ROOT && $*"; }

[ -f theme/espam/style.css ] || { echo "theme/espam missing" >&2; exit 1; }
[ -f plugin/espam-core/espam-core.php ] || { echo "plugin/espam-core missing" >&2; exit 1; }

git diff --quiet HEAD && [ -z "$(git status --porcelain)" ] || { echo "Uncommitted changes — commit and push first" >&2; exit 1; }
git fetch -q origin main
[ "$(git rev-parse HEAD)" = "$(git rev-parse origin/main)" ] || { echo "HEAD is not origin/main — push (or pull) first" >&2; exit 1; }

echo "== Pull + sync on server ($(git rev-parse --short HEAD))"
RSYNC="rsync -rlpt --delete --exclude=.DS_Store --exclude=node_modules"
$SSH "$SSH_HOST" "set -e; git -C ~/$GIT_DIR pull -q --ff-only origin main; git -C ~/$GIT_DIR log -1 --format='server at %h %s'
  $RSYNC ~/$GIT_DIR/theme/espam/ ~/$WP_ROOT/wp-content/themes/espam/
  $RSYNC ~/$GIT_DIR/plugin/espam-core/ ~/$WP_ROOT/wp-content/plugins/espam-core/"

echo "== PHP lint on server"
remote 'find wp-content/themes/espam wp-content/plugins/espam-core -name "*.php" -print0 | xargs -0 -n1 '"$PHP_BIN"' -l | grep -v "^No syntax errors" || true'

if [ "${1:-}" = "--setup" ]; then
  echo "== One-time setup"
  remote '
    set -e
    wp language core install bs_BA --activate || true
    for p in contact-form-7 wordpress-seo limit-login-attempts-reloaded litespeed-cache; do
      wp plugin is-installed "$p" || wp plugin install "$p"
      wp plugin is-active "$p" || wp plugin activate "$p"
    done
    wp language plugin install --all bs_BA || true
    wp plugin delete hello akismet 2>/dev/null || true
    # Hostinger onboarding wizard can switch the theme to its AI theme on admin login.
    wp plugin deactivate hostinger-easy-onboarding 2>/dev/null || true
    wp option update hostinger_show_onboarding 0
    wp plugin is-active espam-core || wp plugin activate espam-core
    wp theme activate espam
    wp option update blogname "ESPAM PLUS"
    wp option update blogdescription "Veleprodaja širokog asortimana robe"
    wp option update timezone_string "Europe/Podgorica"
    wp option update date_format "j. F Y."
    wp option update time_format "H:i"
    wp option update default_comment_status closed
    wp rewrite structure "/%postname%/" --hard
    wp eval "espam_run_setup();"
    wp rewrite flush --hard
  '
fi

echo "== HTML browser cache"
$SSH "$SSH_HOST" "cd ~/$WP_ROOT && $PHP_BIN /usr/local/bin/wp eval-file -" < scripts/htaccess-html.php
echo "== Purge cache"
remote 'wp litespeed-purge all 2>/dev/null || wp cache flush'

echo "== Health check"
# Hostinger's onboarding wizard (hostinger-easy-onboarding) once switched the live theme to its AI theme — fail loudly.
remote 'wp eval "\$t = get_stylesheet(); \$p = is_plugin_active(\"espam-core/espam-core.php\"); echo \$t, \" / espam-core \", \$p ? \"active\" : \"INACTIVE\", PHP_EOL; exit( \"espam\" === \$t && \$p ? 0 : 1 );"' \
  || { echo "ERROR: wrong theme or espam-core inactive on the live site" >&2; exit 1; }
if curl -fsS -o /dev/null -w "%{http_code} $SITE_URL/\n" "$SITE_URL/"; then
  echo "DEPLOY OK"
else
  echo "WARNING: $SITE_URL/ not reachable from here (DNS/SSL?) — code is deployed, check the site manually." >&2
fi
