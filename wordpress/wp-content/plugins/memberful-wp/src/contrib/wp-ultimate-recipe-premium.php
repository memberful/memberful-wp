<?php
if ( in_array( 'wp-ultimate-recipe-premium/wp-ultimate-recipe-premium.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
  add_action( 'wp', 'memberful_disable_wp_ultimate_recipe_premium_content_filter' );

  function memberful_disable_wp_ultimate_recipe_premium_content_filter() {
    global $post;

    if ( ! memberful_can_user_access_post( wp_get_current_user()->ID, $post->ID ) ) {
      $wp_ultimate_recipe_premium = WPUltimateRecipe::get();
      $recipe_content = $wp_ultimate_recipe_premium->helper( "recipe_content" );

      remove_filter( 'the_content', array( $recipe_content, 'content_filter' ), 10 );
    }
  }
}
