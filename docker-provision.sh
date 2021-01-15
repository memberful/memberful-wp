#!/usr/bin/env bash

wp() {
  docker run -it --rm \
    --volumes-from memberful-wp_wordpress_1 \
    --network container:memberful-wp_wordpress_1 \
    wordpress:cli wp $@
}

wp_create_page() {
  echo wp post create --post_type=page --post_status="publish" $@
}

wp core install \
  --url=http://wordpress.localhost \
  --title='ttf' \
  --admin_user=wordpress \
  --admin_password=wordpress \
  --admin_email=wordpress@example.com \

echo "Activating Memberful WP plugin"
wp plugin activate memberful-wp
wp_create_page --post_title="Checkout page" --post_content="[memberful_buy_subscription_link plan='1-monthly']Buy a plan[/memberful_buy_subscription_link]"
wp_create_page --post_title="After sign in page"
wp_create_page --post_title="After sign out page"
wp widget add memberful_wp_profile_widget sidebar-1 1

if ! grep "memberful.localhost" /etc/hosts; then
  echo "Adding hosts entries for Memberful"
  DEFAULT_GATEWAY=`ip route | grep default | awk '{print $3}'`
  sudo sh -c "echo \"$DEFAULT_GATEWAY ttf.memberful.localhost apps.memberful.localhost\" >> /etc/hosts"
fi
