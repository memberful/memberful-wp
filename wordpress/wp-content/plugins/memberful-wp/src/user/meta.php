<?php

define( 'MEMBERFUL_WP_SINGLE_CUSTOM_FIELD_META_KEY', 'memberful_custom_field' );

function memberful_custom_field( WP_User $user ) {
  return get_user_meta( $user->ID, MEMBERFUL_WP_SINGLE_CUSTOM_FIELD_META_KEY, true );
}

function memberful_current_user_custom_field() {
  return memberful_custom_field( wp_get_current_user() );
}
