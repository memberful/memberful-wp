<?php

add_action( 'the_content', 'memberful_wp_protect_content', 100 );

/**
 * Split post content at the first paywall divider block.
 *
 * @param string $content Raw post content.
 * @return array{
 *   has_divider: bool,
 *   content_above_divider: string,
 *   content_below_divider: string
 * }
 */
function memberful_wp_split_post_content_at_paywall_divider( $content ) {
  $content = (string) $content;

  if ( '' === $content ) {
    return array(
      'has_divider'            => false,
      'content_above_divider'  => '',
      'content_below_divider'  => '',
    );
  }

  $divider_pattern = '/<!--\s+wp:memberful\/paywall-divider(?:\s+[^>]*)?\s*\/-->|<!--\s+wp:memberful\/paywall-divider(?:\s+[^>]*)?\s*-->\s*<!--\s+\/wp:memberful\/paywall-divider\s*-->/';
  $content_parts   = preg_split( $divider_pattern, $content, 2 );

  if ( ! is_array( $content_parts ) || 2 !== count( $content_parts ) ) {
    return array(
      'has_divider'            => false,
      'content_above_divider'  => $content,
      'content_below_divider'  => '',
    );
  }

  return array(
    'has_divider'            => true,
    'content_above_divider'  => $content_parts[0],
    'content_below_divider'  => $content_parts[1],
  );
}

/**
 * Render content using the standard `the_content` pipeline without recursion.
 *
 * @param string $content Content to render.
 * @return string Rendered content.
 */
function memberful_wp_render_content_without_protection( $content ) {
  if ( '' === trim( (string) $content ) ) {
    return $content;
  }

  remove_action( 'the_content', 'memberful_wp_protect_content', 100 );
  $rendered_content = apply_filters( 'the_content', $content );
  add_action( 'the_content', 'memberful_wp_protect_content', 100 );

  return $rendered_content;
}

/**
 * Apply teaser wrapper and CSS for divider content when snippets are enabled.
 *
 * @param string $content Rendered content above the paywall divider.
 * @return string Formatted teaser content.
 */
function memberful_wp_format_divider_teaser_content( $content ) {
  if ( '' === trim( (string) $content ) ) {
    return $content;
  }

  if ( ! get_option( 'memberful_use_global_snippets' ) ) {
    return $content;
  }

  $wrapped_content = "<div class='memberful-global-teaser-content'>$content</div>";

  if ( function_exists( 'memberful_get_teaser_css' ) && ! did_action( 'memberful_teaser_css' ) ) {
    $wrapped_content .= apply_filters( 'memberful_teaser_css', memberful_get_teaser_css() );
  }

  return $wrapped_content;
}

function memberful_wp_protect_content( $content ) {
  global $post;

  if ( !isset( $post ) ) {
    # Return the content since we're not in the loop if `$post` is `NULL`
    # Temporary fix for Elasticpress' syncing issue
    return $content;
  }

  if(doing_filter('memberful_wp_protect_content')){
    return $content;
  }

  // Do not filter content for admins
  if ( current_user_can( 'publish_posts' ) ) {
    return $content;
  }

  if ( ! memberful_can_user_access_post( wp_get_current_user()->ID, $post->ID ) ) {
    // Disable Beaver Builder
    remove_action( "the_content", "FLBuilder::render_content" );

    // Remove Elementor action hook
    if (get_queried_object_id() === $post->ID) {
      remove_action("elementor/frontend/the_content", "memberful_wp_protect_content");
    }

    // Remove media enclosures from the RSS feed
    add_filter("rss_enclosure", "__return_empty_string");

    $memberful_marketing_content = memberful_marketing_content( $post->ID );

    // Split the content at the paywall divider (if present).
    $content_split = memberful_wp_split_post_content_at_paywall_divider( $post->post_content );

    if ( $content_split['has_divider'] ) {
      $rendered_content_above_divider = memberful_wp_render_content_without_protection( $content_split['content_above_divider'] );
      $rendered_content_above_divider = memberful_wp_format_divider_teaser_content( $rendered_content_above_divider );
      $rendered_marketing_content = apply_filters( 'memberful_wp_protect_content', $memberful_marketing_content );

      if ( '' !== trim( (string) $rendered_marketing_content ) ) {
        return $rendered_content_above_divider . $rendered_marketing_content;
      }

      return $rendered_content_above_divider;
    }

    return apply_filters( 'memberful_wp_protect_content', $memberful_marketing_content );
  }

  return $content;
}

add_filter( 'memberful_wp_protect_content','wptexturize');
add_filter( 'memberful_wp_protect_content','convert_smilies');
add_filter( 'memberful_wp_protect_content','convert_chars');
add_filter( 'memberful_wp_protect_content','wpautop');
add_filter( 'memberful_wp_protect_content','shortcode_unautop');
add_filter( 'memberful_wp_protect_content','prepend_attachment');

add_filter('memberful_wp_protect_content','do_blocks',15);
add_filter( 'memberful_wp_protect_content', 'do_shortcode', 11 );

if ( get_option( 'memberful_use_global_marketing' ) ) {
  include_once 'global_marketing.php';
}
