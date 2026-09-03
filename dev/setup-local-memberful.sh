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
# the plugin's activation POST into a GET.
cat > "$OVERRIDE_FILE" <<'JSON'
{
	"$schema": "https://schemas.wp.org/trunk/wp-env.json",
	"mappings": {
		"wp-content/mu-plugins": "./dev/mu-plugins"
	},
	"config": {
		"MEMBERFUL_APPS_HOST": "https://apps.memberful.localhost",
		"MEMBERFUL_EMBED_HOST": "http://js.memberful.localhost",
		"MEMBERFUL_SSL_VERIFY": false
	}
}
JSON
echo "Wrote $OVERRIDE_FILE"

echo "Restarting wp-env to apply the configuration..."
npm run env:start

echo
echo "Done. WordPress now talks to the Memberful app at https://apps.memberful.localhost."
