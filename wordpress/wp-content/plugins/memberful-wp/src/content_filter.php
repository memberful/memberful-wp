<?php

if ( ! defined( 'MEMBERFUL_PARAGRAPH_COUNT' ) ) {
  define( 'MEMBERFUL_PARAGRAPH_COUNT', 2 );
}

add_action( 'the_content', 'memberful_wp_protect_content', 100 );

/**
 * Clamp a paragraph count to the supported range.
 *
 * @param int $count Requested paragraph count.
 * @return int
 */
function memberful_wp_clamp_paragraph_count( int $count ): int {
  return min( 10, max( 1, $count ) );
}

/**
 * Resolve the configured number of teaser paragraphs shown before the paywall.
 *
 * @return int
 */
function memberful_wp_paragraph_count(): int {
  return memberful_wp_clamp_paragraph_count( (int) get_option( 'memberful_paragraph_count', MEMBERFUL_PARAGRAPH_COUNT ) );
}

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
 * Rebuild the teaser from raw post content when the rendered copy lost the divider marker.
 *
 * Excerpt generation strips blocks before `the_content` runs, so the divider block never renders its marker there.
 * The block tree is truncated at the divider, wherever it is nested, rather than splitting the raw markup, so any
 * wrapping Group or Columns blocks keep their closing markup and render as valid HTML.
 *
 * @param WP_Post $post Post being rendered.
 * @return string Rendered content above the divider block.
 */
function memberful_wp_content_above_divider_block( WP_Post $post ): string {
  $found  = false;
  $blocks = memberful_wp_blocks_above_divider( parse_blocks( (string) $post->post_content ), $found );

  // Fail closed: without the divider in the parsed tree there is nothing to cut at, so render no teaser rather than
  // the whole post.
  if ( ! $found || empty( $blocks ) ) {
    return '';
  }

  // Rendered block by block rather than through do_blocks(): called from inside the_content at priority 100,
  // do_blocks() unhooks wpautop and schedules its restore at priority 11, which never runs, so the next post rendered
  // on the page would lose its paragraphs.
  $output = '';
  foreach ( $blocks as $block ) {
    $output .= render_block( $block );
  }

  // Truncated wrappers render without their closing markup (see memberful_wp_inner_content_for_blocks()).
  return force_balance_tags( strip_shortcodes( $output ) );
}

/**
 * Keep the blocks that precede the first paywall divider, descending into nested blocks.
 *
 * Everything after the divider in document order is dropped, including later siblings of its ancestors (a divider
 * in the first column drops the second column), matching how the single-post view cuts at the divider marker.
 * A wrapper the divider opens contributes nothing and is dropped as well.
 *
 * @param array $blocks Parsed blocks.
 * @param bool  $found  Set to true once the divider has been seen; nothing after it is kept.
 * @return array
 */
function memberful_wp_blocks_above_divider( array $blocks, bool &$found ): array {
  $kept = array();

  foreach ( $blocks as $block ) {
    if ( 'memberful/paywall-divider' === $block['blockName'] ) {
      $found = true;
      break;
    }

    if ( ! empty( $block['innerBlocks'] ) ) {
      $inner_blocks = memberful_wp_blocks_above_divider( $block['innerBlocks'], $found );

      if ( $found ) {
        if ( empty( $inner_blocks ) ) {
          break;
        }

        $block['innerContent'] = memberful_wp_inner_content_for_blocks( $block['innerContent'], count( $inner_blocks ) );
        $block['innerBlocks']  = $inner_blocks;
      }
    }

    $kept[] = $block;

    if ( $found ) {
      break;
    }
  }

  return $kept;
}

/**
 * Trim a block's innerContent to its first $count inner-block placeholders.
 *
 * innerContent interleaves HTML strings with null placeholders, one per inner block in order. Dropping trailing
 * inner blocks means dropping their placeholders and every chunk from the first dropped placeholder on. That
 * includes the wrapper's closing markup, which is deliberately not re-attached: the trailing chunk can carry raw
 * HTML that sits below the divider, so the caller balances tags after rendering instead.
 *
 * @param array $inner_content Block innerContent.
 * @param int   $count         Number of inner blocks kept.
 * @return array
 */
function memberful_wp_inner_content_for_blocks( array $inner_content, int $count ): array {
  $trimmed = array();
  $seen    = 0;

  foreach ( $inner_content as $chunk ) {
    if ( null === $chunk ) {
      if ( $seen >= $count ) {
        break;
      }
      $seen++;
    }

    $trimmed[] = $chunk;
  }

  return $trimmed;
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
  global $post;

  if ( $content_split['has_divider'] ) {
    $teaser = $content_split['content_above_divider'];
  } elseif ( isset( $post ) && has_block( 'memberful/paywall-divider', $post ) ) {
    // The marker is missing but the post has a divider, so this render stripped blocks.
    return memberful_wp_content_above_divider_block( $post );
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

  // Metering decision is computed on template_redirect. Only consult it for
  // the singular post under view — related posts and page-builder internals
  // also fire `the_content` and shouldn't burn meter views.
  $metering_decision = ( (int) $post->ID === (int) get_queried_object_id() )
    ? Memberful_Metering_Access::get_current_decision( (int) $post->ID )
    : Memberful_Metering_Access::DECISION_IGNORE;

  if ( Memberful_Metering_Access::DECISION_ALLOW_SAMPLE === $metering_decision ) {
    return memberful_wp_strip_paywall_divider_marker( $content );
  }

  $force_metering_gate = ( Memberful_Metering_Access::DECISION_TRIP_METER === $metering_decision );

  if ( $force_metering_gate || ! memberful_can_user_access_post( wp_get_current_user()->ID, $post->ID ) ) {
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
