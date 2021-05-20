#!/usr/bin/env bash

wp() {
  /usr/local/bin/wp --path=/vagrant/wordpress $@
}

wp_create_page() {
  echo wp post create --post_type=page --post_status="publish" $@
}

echo "Activating Memberful WP plugin"
wp plugin activate memberful-wp
wp_create_page --post_title="Checkout page" --post_content="[memberful_buy_subscription_link plan='1-monthly']Buy a plan[/memberful_buy_subscription_link]"
wp_create_page --post_title="After sign in page"
wp_create_page --post_title="After sign out page"
wp widget add memberful_wp_profile_widget sidebar-1 1
