#!/bin/sh
# Helper: ./scripts/wp.sh setup | <any wp-cli args>
set -eu
cd "$(dirname "$0")/.."

# Local test credentials (generated once, never shipped)
CRED=.local-credentials
if [ ! -f "$CRED" ]; then
  {
    echo "ADMIN_USER=admin"
    echo "ADMIN_PASS=$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 20)"
    echo "ADMIN_EMAIL=admin@espam.test"
  } >"$CRED"
  chmod 600 "$CRED"
fi
set -a; . "./$CRED"; set +a

case "${1:-}" in
  setup) docker compose --progress quiet run --rm -e ADMIN_USER -e ADMIN_PASS -e ADMIN_EMAIL --entrypoint sh cli /scripts/setup.sh ;;
  *)     docker compose --progress quiet run --rm -T cli "$@" ;;
esac
