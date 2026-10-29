#!/bin/sh
# Idempotent local setup. Run from the project root: ./scripts/wp.sh setup
set -eu
: "${ADMIN_USER:?}" "${ADMIN_PASS:?}" "${ADMIN_EMAIL:?}"
URL="${SITE_URL:-http://localhost:8080}"

if ! wp core is-installed 2>/dev/null; then
  wp core install --url="$URL" --title="ESPAM PLUS" \
    --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" \
    --admin_email="$ADMIN_EMAIL" --skip-email
fi

# Language: Montenegrin (ijekavica). Falls back silently if the pack is missing.
wp language core install cnr --activate 2>/dev/null || wp language core install bs_BA --activate || true

for p in contact-form-7 wordpress-seo limit-login-attempts-reloaded litespeed-cache; do
  wp plugin is-installed "$p" || wp plugin install "$p"
  wp plugin is-active "$p" || wp plugin activate "$p"
done
wp language plugin install --all cnr 2>/dev/null || true
wp plugin delete hello akismet 2>/dev/null || true

wp plugin is-active espam-core || wp plugin activate espam-core
wp theme activate espam
for t in twentytwentythree twentytwentyfour twentytwentyfive; do wp theme delete "$t" 2>/dev/null || true; done

wp option update blogdescription "Veleprodaja širokog asortimana robe"
wp option update timezone_string "Europe/Podgorica"
wp option update date_format "j. F Y."
wp option update time_format "H:i"
wp option update default_comment_status closed
wp rewrite structure '/%postname%/' --hard
wp eval 'espam_run_setup();'
wp rewrite flush --hard
echo "SETUP OK"
