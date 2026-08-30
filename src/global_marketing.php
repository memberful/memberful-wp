<?php

if(get_option('memberful_use_global_snippets')){
  add_filter( 'memberful_wp_protect_content', 'memberful_apply_global_snippets_content_filter', 1, 1 );
  add_filter( 'memberful_wp_listing_excerpt', 'memberful_wp_apply_paragraph_count_to_listing_excerpt', 10, 2 );
} else {
  add_filter( 'memberful_wp_protect_content', 'memberful_get_global_replacement', 1, 1 );
}

/**
 * Identify Post specific or global marketing content
 *
 * @param string $marketing_content
 * @return string
 */
function memberful_get_global_replacement($marketing_content){
  $override = get_option( 'memberful_global_marketing_override' );

  if ( $override ) {
    return memberful_wp_resolve_global_marketing_content();
  }

  if ( empty( trim( $marketing_content ) ) ) {
    return memberful_wp_resolve_global_marketing_content();
  }

  return $marketing_content;
}

/**
 * Resolve the global marketing HTML from whichever source the paywall config points to.
 *
 * @return string
 */
function memberful_wp_resolve_global_marketing_content(): string {
  $config = Memberful_Paywall_Config::get();

  if ( 'builder' === $config['mode'] ) {
    return Memberful_Paywall_Renderer::render( $config );
  }

  return (string) get_option( 'memberful_global_marketing_content' );
}

/**
 * Filter the paywall to return a "teaser".
 *
 * @param string $memberful_marketing_content
 *
 * @return string concat of teaser and memberful marketing content
 */
function memberful_apply_global_snippets_content_filter( $memberful_marketing_content ) {
  global $post;
  $replacement = memberful_get_global_replacement($memberful_marketing_content);

  $wrapped_global_marketing_content = "<div class='memberful-global-marketing-content'>$replacement</div>";

  if ( isset( $post ) && has_block( 'memberful/paywall-divider', $post ) ) {
    return $wrapped_global_marketing_content;
  }

  // Prevent endless loop trap
  remove_action( 'the_content', 'memberful_wp_protect_content', -10 );

  $original_content = apply_filters( 'the_content', $post->post_content );

  // re-add the action for follow-on call
  add_action( 'the_content', 'memberful_wp_protect_content', -10 );

  $has_teaser = false;
  $teaser = '';

  if ( !empty( $original_content ) ) {
    $teaser_offset = 0;
    $paragraph_count = memberful_wp_paragraph_count();

    for ( $i = 0; $i < $paragraph_count; $i++ ) {
      $paragraph_offset = strpos( $original_content, '</p>', $teaser_offset );

      if ( $paragraph_offset === false ) {
        break;
      } else {
        $teaser_offset = $paragraph_offset + 4; // Move past the </p> tag
      }

      if ( $teaser_offset === strlen( $original_content ) ) {
        break;
      }
    }

    $has_teaser = $teaser_offset <= strlen($original_content);

    if ( $has_teaser ) {
      $teaser = force_balance_tags(substr( $original_content, 0, $teaser_offset ));
    }
  }

  $teaser_class   = apply_filters( 'memberful_global_teaser_class', 'memberful-global-teaser-content' );
  $wrapped_teaser = "<div class='" . esc_attr( $teaser_class ) . "'>$teaser</div>";

  if ( $has_teaser && ! did_filter( 'memberful_teaser_css' ) ) {
    $wrapped_teaser .= apply_filters( 'memberful_teaser_css', memberful_get_teaser_css() );
  }

  return $wrapped_teaser . $wrapped_global_marketing_content;
}

function memberful_get_teaser_css(){
  $css = <<<CSS
    <style>
        .memberful-global-teaser-content p:last-child{
            -webkit-mask-image: linear-gradient(180deg, #000 0%, transparent);
            mask-image: linear-gradient(180deg, #000 0%, transparent);
        }
    </style>
CSS;

  return $css;
}

/**
 * Recut the archive listing excerpt to the configured paragraph count.
 *
 * @param string $teaser  Teaser produced for the listing.
 * @param string $content Full rendered post content.
 * @return string
 */
function memberful_wp_apply_paragraph_count_to_listing_excerpt( $teaser, $content ) {
  if ( ! function_exists( 'memberful_wp_first_paragraphs' ) ) {
    return $teaser;
  }

  if ( false !== strpos( (string) $content, memberful_wp_get_paywall_divider_marker() ) ) {
    return $teaser;
  }

  return memberful_wp_first_paragraphs( (string) $content, memberful_wp_paragraph_count() );
}
