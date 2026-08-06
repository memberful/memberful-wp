<?php

add_action( 'the_content', 'memberful_wp_protect_content', 100 );

/**
 * Get the marker inserted by the paywall divider block.
 *
 * @return string
 */
function memberful_wp_get_paywall_divider_marker() {
  return '<!-- memberful-paywall-divider -->';
}

/**
 * Remove the paywall divider marker from rendered content.
 *
 * @param string $content Rendered post content.
 * @return string
 */
function memberful_wp_strip_paywall_divider_marker( $content ) {
  return str_replace( memberful_wp_get_paywall_divider_marker(), '', (string) $content );
}

/**
 * Split rendered post content at the first paywall divider marker.
 *
 * @param string $content Rendered post content.
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

  $content_parts = explode( memberful_wp_get_paywall_divider_marker(), $content, 2 );

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

  if ( function_exists( 'memberful_get_teaser_css' ) && ! did_filter( 'memberful_teaser_css' ) ) {
    $wrapped_content .= apply_filters( 'memberful_teaser_css', memberful_get_teaser_css() );
  }

  return $wrapped_content;
}

/**
 * Whether the current render prints the whole post body.
 *
 * The paywall belongs only where the full post would otherwise appear: the requested post, feeds, and REST.
 * Listings render one entry per post, so a paywall there stacks up once per result.
 *
 * @return bool
 */
function memberful_wp_rendering_full_post(): bool {
  global $post;

  if ( is_feed() ) {
    return true;
  }

  if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
    return true;
  }

  $is_queried_post = is_singular() && isset( $post ) && get_queried_object_id() === (int) $post->ID;

  /**
   * Filter whether the paywall should render for the post being filtered.
   *
   * @param bool $is_queried_post Whether the post being filtered is the requested one.
   */
  return (bool) apply_filters( 'memberful_wp_rendering_full_post', $is_queried_post );
}

/**
 * Take the opening paragraphs of already rendered content.
 *
 * @param string $content Rendered post content.
 * @param int    $count   How many paragraphs to keep.
 * @return string
 */
function memberful_wp_first_paragraphs( string $content, int $count ): string {
  $offset = 0;

  for ( $i = 0; $i < $count; $i++ ) {
    $paragraph_end = strpos( $content, '</p>', $offset );

    if ( false === $paragraph_end ) {
      break;
    }

    $offset = $paragraph_end + 4;
  }

  return 0 === $offset ? '' : force_balance_tags( substr( $content, 0, $offset ) );
}

/**
 * Build the preview a protected post shows in listings.
 *
 * Bounded by the teaser the single post already shows above the paywall, so a listing can never expose more of a post
 * than the post's own page does. That ceiling is the teaser itself rather than a word count, which is why nothing is
 * trimmed here - WordPress trims this to excerpt_length on the excerpt path, and a theme widening excerpt_length still
 * cannot reach past the teaser.
 *
 * @param string $content       Rendered post content.
 * @param array  $content_split Output of memberful_wp_split_post_content_at_paywall_divider().
 * @return string
 */
function memberful_wp_listing_excerpt( string $content, array $content_split ): string {
  if ( $content_split['has_divider'] ) {
    $teaser = $content_split['content_above_divider'];
  } elseif ( get_option( 'memberful_use_global_marketing' ) && get_option( 'memberful_use_global_snippets' ) ) {
    $teaser = memberful_wp_first_paragraphs( $content, MEMBERFUL_PARAGRAPH_COUNT );
  } else {
    $teaser = '';
  }

  /**
   * Filter the preview a protected post shows in listings.
   *
   * Return a truncation of $content to get WordPress style excerpts built from the post body. Doing so publishes body
   * text the single post keeps behind the paywall, so it is opt in.
   *
   * @param string $teaser  Teaser the single post shows above the paywall.
   * @param string $content Full rendered post content.
   */
  return (string) apply_filters( 'memberful_wp_listing_excerpt', $teaser, $content );
}

function memberful_wp_protect_content( $content ) {
  global $post;

  $content_split = memberful_wp_split_post_content_at_paywall_divider( $content );

  if ( !isset( $post ) ) {
    # Return the content since we're not in the loop if `$post` is `NULL`
    # Temporary fix for Elasticpress' syncing issue
    return memberful_wp_strip_paywall_divider_marker( $content );
  }

  if(doing_filter('memberful_wp_protect_content')){
    return memberful_wp_strip_paywall_divider_marker( $content );
  }

  // Do not filter content for admins
  if ( current_user_can( 'publish_posts' ) ) {
    return memberful_wp_strip_paywall_divider_marker( $content );
  }

  if ( ! memberful_can_user_access_post( wp_get_current_user()->ID, $post->ID ) ) {
    // Returning before the paywall filters also keeps paywall.css off listing pages.
    if ( ! memberful_wp_rendering_full_post() ) {
      return memberful_wp_listing_excerpt( $content, $content_split );
    }

    // Disable Beaver Builder
    remove_action( "the_content", "FLBuilder::render_content" );

    // Remove Elementor action hook
    if (get_queried_object_id() === $post->ID) {
      remove_action("elementor/frontend/the_content", "memberful_wp_protect_content");
    }

    // Remove media enclosures from the RSS feed
    add_filter("rss_enclosure", "__return_empty_string");

    $memberful_marketing_content = memberful_marketing_content( $post->ID );

    if ( $content_split['has_divider'] ) {
      $content_above_divider = memberful_wp_format_divider_teaser_content( $content_split['content_above_divider'] );
      $rendered_marketing_content = apply_filters( 'memberful_wp_protect_content', $memberful_marketing_content );

      if ( '' !== trim( (string) $rendered_marketing_content ) ) {
        return $content_above_divider . $rendered_marketing_content;
      }

      return $content_above_divider;
    }

    return apply_filters( 'memberful_wp_protect_content', $memberful_marketing_content );
  }

  if ( $content_split['has_divider'] ) {
    return $content_split['content_above_divider'] . $content_split['content_below_divider'];
  }

  return memberful_wp_strip_paywall_divider_marker( $content );
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
