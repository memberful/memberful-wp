#!/usr/bin/env bash
#
# Points the local WordPress environment at a Memberful app running on this
# machine behind puma-dev (https://apps.memberful.localhost) instead of
# memberful.com. Intended for Memberful staff; contributors without a local
# Memberful app don't need it.
#
# Writes .wp-env.override.json (git-ignored, per developer) and restarts wp-env
# so the new configuration is applied. To go back to memberful.com, delete the
# override file and run `npm run env:start` again.
#
# Usage: npm run env:local-memberful
set -euo pipefail

cd "$(dirname "$0")/.."

OVERRIDE_FILE=".wp-env.override.json"

if [ -f "$OVERRIDE_FILE" ]; then
  echo "Overwriting existing $OVERRIDE_FILE"
fi

# The mu-plugins mapping makes *.memberful.localhost requests leave the
# container (see dev/mu-plugins/memberful-dev-resolve.php). MEMBERFUL_APPS_HOST
# must be https: the Memberful app redirects http to https, which would turn
# the plugin's activation POST into a GET. WP_HOME/WP_SITEURL give the site the
# http://wordpress.localhost address that puma-dev proxies to wp-env; wp-env
# appends its port to them, which dev/mu-plugins/memberful-dev-site-url.php
# strips again. WP_CONTENT_URL is derived from WP_SITEURL before mu-plugins
# load, so it is set explicitly.
cat > "$OVERRIDE_FILE" <<'JSON'
{
	"$schema": "https://schemas.wp.org/trunk/wp-env.json",
	"mappings": {
		"wp-content/mu-plugins": "./dev/mu-plugins"
	},
	"config": {
		"WP_HOME": "http://wordpress.localhost",
		"WP_SITEURL": "http://wordpress.localhost",
		"WP_CONTENT_URL": "http://wordpress.localhost/wp-content",
		"MEMBERFUL_APPS_HOST": "https://apps.memberful.localhost",
		"MEMBERFUL_EMBED_HOST": "http://js.memberful.localhost",
		"MEMBERFUL_SSL_VERIFY": false
	}
}
JSON
echo "Wrote $OVERRIDE_FILE"

# puma-dev proxies http://wordpress.localhost to the wp-env port. It caches
# proxy targets, so a changed entry only takes effect after puma-dev restarts.
PUMA_DEV_ENTRY="$HOME/.puma-dev/wordpress"
if [ -d "$HOME/.puma-dev" ]; then
  if [ "$(cat "$PUMA_DEV_ENTRY" 2>/dev/null)" != "8888" ]; then
    echo 8888 > "$PUMA_DEV_ENTRY"
    echo "puma-dev: wrote $PUMA_DEV_ENTRY. Restart puma-dev for http://wordpress.localhost to reach wp-env."
  fi
else
  echo "puma-dev not found (~/.puma-dev missing): http://wordpress.localhost will not resolve, use http://localhost:8888"
fi

echo "Restarting wp-env to apply the configuration..."
npm run env:start

echo
echo "Done. WordPress runs at http://wordpress.localhost and talks to the Memberful app at https://apps.memberful.localhost."
