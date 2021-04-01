#!/usr/bin/env bash

wp() {
  docker run -it --rm \
    --volumes-from memberful-wp_wordpress_1 \
    --network container:memberful-wp_wordpress_1 \
    wordpress:cli wp $@
}

wp core install \
  --url=http://wordpress.localhost \
  --title='ttf' \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=admin@example.com \

echo "Activating Memberful WP plugin"
wp plugin activate memberful-wp
wp widget add memberful_wp_profile_widget sidebar-1 1
wp config set MEMBERFUL_APPS_HOST "http://apps.memberful.localhost"
wp config set MEMBERFUL_EMBED_HOST "http://apps.memberful.localhost"
wp config set MEMBERFUL_SSL_VERIFY false --raw

if [ -x "$(command -v puma-dev)" ]; then 
  echo "Adding puma-dev entry for wordpress.locahost"
  echo 8181 > ~/.puma-dev/wordpress
fi
