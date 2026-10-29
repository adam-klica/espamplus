#!/bin/sh
# Sets the WP Mail SMTP password on the live site. Run it yourself:
#   ./scripts/set-smtp-pass.sh
# The password is read from a hidden prompt and sent over SSH stdin — never in arguments, files or logs.
set -eu

cd "$(dirname "$0")/.."
SSH_KEY="${SSH_KEY:-$HOME/.ssh/espam_deploy}"
. ./.deploy.env
WP_ROOT='domains/espamplus.me/public_html'

printf 'SMTP password for info@espamplus.me: '
stty -echo; IFS= read -r PASS; stty echo; echo
[ -n "$PASS" ] || { echo "Empty password — nothing changed." >&2; exit 1; }

printf '%s\n' "$PASS" | ssh -i "$SSH_KEY" -p "$SSH_PORT" -o BatchMode=yes "$SSH_HOST" \
  "cd ~/$WP_ROOT && /opt/alt/php83/usr/bin/php /usr/local/bin/wp eval '
    \$pass = rtrim( (string) fgets( STDIN ), \"\\r\\n\" );
    \$options = \\WPMailSMTP\\Options::init();
    \$all = \$options->get_all();
    \$all[\"smtp\"][\"pass\"] = \$pass;
    \$options->set( \$all );
    echo \"SMTP password saved.\", PHP_EOL;
  '"
unset PASS
