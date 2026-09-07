<?php
function filter_admin_toolbar() {
  if ( get_option( 'memberful_hide_admin_toolbar' ) && !current_user_can( 'edit_posts' )) {
    show_admin_bar( false );
  }
}

add_action( 'init', 'filter_admin_toolbar' );
