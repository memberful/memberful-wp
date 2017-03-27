#!/usr/bin/env bash


echo "Activating Memberful WP plugin"
/usr/local/bin/wp --path=/vagrant/wordpress plugin activate memberful-wp

if ! grep "memberful.dev" /etc/hosts; then
  echo "Adding hosts entries for Memberful"
  DEFAULT_GATEWAY=`ip route | grep default | awk '{print $3}'`
  sudo sh -c "echo \"$DEFAULT_GATEWAY ttf.memberful.dev apps.memberful.dev\" >> /etc/hosts"
fi
