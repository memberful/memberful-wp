<?php

if ( ! defined( 'MEMBERFUL_PARAGRAPH_COUNT' ) ) {
  define( 'MEMBERFUL_PARAGRAPH_COUNT', 2 );
}

if(get_option('memberful_use_global_snippets')){
  add_filter( 'memberful_wp_protect_content', 'memberful_apply_global_snippets_content_filter', 1, 1 );
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
  $global_marketing_content = get_option( 'memberful_global_marketing_content' );

  if($override) {
    return $global_marketing_content;
  }

  if(empty(trim($marketing_content))){
    return $global_marketing_content;
  }

  return $marketing_content;
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

  // Prevent endless loop trap
  remove_action( 'the_content', 'memberful_wp_protect_content', -10 );

  $original_content = apply_filters( 'the_content', $post->post_content );

  // re-add the action for follow-on call
  add_action( 'the_content', 'memberful_wp_protect_content', -10 );

  $has_teaser = false;
  $teaser = '';

  if ( !empty( $original_content ) ) {
    $teaser_offset = 0;

    for ( $i = 0; $i < MEMBERFUL_PARAGRAPH_COUNT; $i++ ) {
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

  $wrapped_teaser = "<div class='memberful-global-teaser-content'>$teaser</div>";

  if ( $has_teaser && ! did_action( 'memberful_teaser_css' ) ) {
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
