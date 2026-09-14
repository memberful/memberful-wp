<?php
function redirect_members_home() {
  if ( defined( 'DOING_AJAX' ))
    return;

  if ( get_option( 'memberful_block_dashboard_access' ) && !current_user_can( 'edit_posts' )) {
    wp_redirect( home_url() );
    exit();
  }
}

add_action( 'admin_init', 'redirect_members_home' );
