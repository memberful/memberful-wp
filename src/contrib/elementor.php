<?php

add_filter( 'elementor/frontend/the_content', 'memberful_wp_protect_elementor_content' );

/**
 * Protect Elementor content as the post it was built for.
 *
 * Elementor renders layouts outside the loop too: header and footer templates, Template widgets, and add-ons that
 * render a post by ID over admin-ajax. There the global post is another post or none at all, so the content filter
 * checks the post that owns the layout being rendered instead.
 *
 * @param string $content Rendered Elementor content.
 * @return string
 */
function memberful_wp_protect_elementor_content( $content ) {
  $document = \Elementor\Plugin::$instance->documents->get_current();

  return memberful_wp_protect_elementor_document_content( $content, $document ? (int) $document->get_main_id() : 0 );
}

/**
 * Run the content filter on Elementor content as the given post.
 *
 * @param string $content Rendered Elementor content.
 * @param int    $post_id Post the layout belongs to.
 * @return string
 */
function memberful_wp_protect_elementor_document_content( $content, int $post_id ) {
  global $post;

  $previous_post = $post;
  $post          = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored below.

  $content = memberful_wp_protect_content( $content );

  $post = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the value saved above.

  return $content;
}
