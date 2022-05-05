<?php

if ( ! defined( 'MEMBERFUL_PARAGRAPH_COUNT' ) ) {
  define( 'MEMBERFUL_PARAGRAPH_COUNT', 2 );
}

add_filter( 'memberful_wp_protect_content', 'memberful_apply_global_marketing_content_filter', 1, 1 );

/**
 * Filter the paywall to return a "teaser".
 *
 * @param string $memberful_marketing_content
 *
 * @return string concat of teaser and memberful marketing content
 */
function memberful_apply_global_marketing_content_filter( $memberful_marketing_content ) {
  global $post;
  $override = get_option( 'memberful_global_marketing_override' );

  if ( ! $override && ! empty( $memberful_marketing_content ) ) {
    return $memberful_marketing_content;
  }

  $global_marketing_content         = get_option( 'memberful_global_marketing_content' );
  $wrapped_global_marketing_content = "<div class='memberful-global-marketting-content'>$global_marketing_content</div>";

  // Prevent endless loop trap
  remove_action( 'the_content', 'memberful_wp_protect_content', -10 );

  $original_content = apply_filters( 'the_content', $post->post_content );

  // re-add the action for follow-on call
  add_action( 'the_content', 'memberful_wp_protect_content', -10 );

  $offset = 0;
  for ( $i = 0; $i < MEMBERFUL_PARAGRAPH_COUNT; $i++ ) {
    $offset = strpos( $original_content, '</p>', $offset ) + 5;
    if( $offset === strlen($original_content) ){
      continue;
    }
  }
  $has_teaser= $offset < strlen($original_content);

  if($has_teaser){
    $teaser = force_balance_tags(substr( $original_content, 0, $offset ));
  } else {
    $teaser = '';
  }

  $wrapped_teaser = "<div class='memberful-global-teaser-content'>$teaser</div>";

  if ( $has_teaser && ! did_action( 'memberful_teaser_css' ) ) {
    $wrapped_teaser.= apply_filters( 'memberful_teaser_css', memberful_get_teaser_css() );
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
