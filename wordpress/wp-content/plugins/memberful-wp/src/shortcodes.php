<?php
add_shortcode( 'memberful', 'memberful_wp_shortcode' );
add_shortcode( 'memberful_account_link',  'memberful_wp_shortcode_account_link' );
add_shortcode( 'memberful_buy_download_link', 'memberful_wp_shortcode_buy_download_link' );
add_shortcode( 'memberful_buy_gift_link', 'memberful_wp_shortcode_buy_gift_link' );
add_shortcode( 'memberful_buy_subscription_link', 'memberful_wp_shortcode_buy_subscription_link' );
add_shortcode( 'memberful_download_link', 'memberful_wp_shortcode_download_link' );
add_shortcode( 'memberful_private_rss_feed_link', 'memberful_wp_shortcode_private_user_feed_link' );
add_shortcode( 'memberful_register_link', 'memberful_wp_shortcode_register_link' );
add_shortcode( 'memberful_sign_in_link',  'memberful_wp_shortcode_sign_in_link' );
add_shortcode( 'memberful_sign_out_link', 'memberful_wp_shortcode_sign_out_link' );
add_shortcode( 'memberful_podcasts_link',  'memberful_wp_shortcode_feeds_link' );
add_shortcode( 'memberful_podcast_url', 'memberful_wp_shortcode_feed_url' );
add_shortcode( 'memberful_if_has_active_subscription', 'memberful_wp_shortcode_if_has_active_subscription' );
add_shortcode( 'memberful_if_does_not_have_active_subscription', 'memberful_wp_shortcode_if_does_not_have_active_subscription' );

function memberful_wp_shortcode_buy_download_link( $atts, $content ) {
  $url = memberful_checkout_for_download_url(
    memberful_wp_extract_id_from_slug( $atts['download'] )
  );

  return '<a href="'.$url.'">'.do_shortcode($content).'</a>';
}

function memberful_wp_shortcode_buy_subscription_link( $atts, $content ) {
  $plan_id = memberful_wp_extract_id_from_slug( $atts['plan'] );
  $url = add_query_arg( 'plan', $plan_id, memberful_url( 'checkout' ) );

  if( isset( $atts['price'] ) ) {
    $price = filter_var($atts['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    if( $price !== false && $price > 0 ) {
      $url = add_query_arg( 'price', $price, $url );
    }
  }

  return '<a href="' . esc_url($url) . '">' . do_shortcode($content) . '</a>';
}

function memberful_wp_shortcode_buy_gift_link( $atts, $content ) {
  $url = memberful_gift_url( memberful_wp_extract_id_from_slug( $atts['plan'] ) );

  return '<a href="'.$url.'">'.do_shortcode($content).'</a>';
}


function memberful_wp_shortcode_register_link( $atts, $content ) {
  return '<a href="'.memberful_registration_page_url().'" role="register">'.do_shortcode($content).'</a>';
}

function memberful_wp_shortcode_sign_out_link( $atts, $content ) {
  return '<a href="'.memberful_sign_out_url().'" role="sign_out">'.do_shortcode($content).'</a>';
}

function memberful_wp_shortcode_sign_in_link( $atts, $content ) {
  return '<a href="'.memberful_sign_in_url().'" role="sign_in">'.do_shortcode($content).'</a>';
}

function memberful_wp_shortcode_account_link( $atts, $content ) {
  return '<a href="'.memberful_account_url().'" role="account">'.do_shortcode($content).'</a>';
}

function memberful_wp_shortcode_feeds_link( $atts, $content ) {
  $url = memberful_feeds_url();

  if (!empty($atts['podcast'])) {
    $podcast_id = filter_var($atts['podcast'], FILTER_VALIDATE_INT);

    if ($podcast_id !== false && $podcast_id > 0) {
      $url = add_query_arg('id', $podcast_id, $url);
    }
  }

  return '<a href="' . esc_url($url) . '">' . do_shortcode($content) . '</a>';
}

function memberful_wp_shortcode_download_link( $atts, $content) {
  if ( ! empty($atts['product']) )
    $atts['download'] = $atts['product'];

  if ( empty($atts['download']))
    return $content;

  return '<a href="'.memberful_account_get_download_url( $atts['download'] ).'" rel="download">'.do_shortcode($content).'</a>';
}

function memberful_wp_shortcode_feed_url($atts) {
  if (isset($atts['podcast'])) {
    $id = $atts['podcast'];
    return memberful_wp_feed_url($id);
  }
}

function memberful_wp_normalize_shortcode_args( $atts ) {
  if ( ! empty( $atts['has_subscription'] ) ) {
    $atts['has_subscription_to'] = $atts['has_subscription'];
  }

  if ( ! empty( $atts['has_product'] ) ) {
    $atts['has_download'] = $atts['has_product'];
  }

  if ( ! empty( $atts['does_not_have_subscription'] ) ) {
    $atts['does_not_have_subscription_to'] = $atts['does_not_have_subscription'];
  }

  if ( ! empty( $atts['does_not_have_product'] ) ) {
    $atts['does_not_have_download'] = $atts['does_not_have_product'];
  }

  return $atts;
}

function memberful_wp_shortcode( $atts, $content ) {
  $show_content = FALSE;
  $does_not_have_download = $does_not_have_subscription = NULL;

  $atts = memberful_wp_normalize_shortcode_args( $atts );

  $shortcode_is_checking_if_the_user_has_stuff = empty( $atts['does_not_have_subscription_to'] ) && empty( $atts['does_not_have_download'] );

  if ( $shortcode_is_checking_if_the_user_has_stuff && current_user_can( 'publish_posts' ) ) {
    return do_shortcode($content);
  }

  if ( ! empty( $atts['has_subscription_to'] ) ) {
    $show_content = is_subscribed_to_memberful_plan( $atts['has_subscription_to'] );
  }

  if ( ! empty( $atts['has_download'] ) ) {
    $has_download = has_memberful_download( $atts['has_download'] );

    $show_content = $show_content || $has_download;
  }

  if ( ! empty( $atts['does_not_have_subscription_to'] ) ) {
    $does_not_have_subscription = ! is_subscribed_to_memberful_plan(
      $atts['does_not_have_subscription_to']
    );
  }

  if ( ! empty( $atts['does_not_have_download'] ) ) {
    $does_not_have_download = ! has_memberful_download(
      $atts['does_not_have_download']
    );
  }

  if ( $does_not_have_download !== NULL || $does_not_have_subscription !== NULL ) {
    $requirements = array( $does_not_have_subscription, $does_not_have_download);

    if ( in_array( FALSE, $requirements, TRUE ) ) {
      // User may have access to either the mentioned download or the subscription
      $show_content = FALSE;
    } else {
      // All specified requirements have been satisfied, so show content
      $show_content = TRUE;
    }
  }

  return $show_content ? do_shortcode($content) : '';
}

function memberful_wp_shortcode_private_user_feed_link($atts = array(), $content = '') {
  $category = $atts['category'] ?? '';

  return memberful_private_rss_feed_link($content, __("You don’t have access to this RSS feed."), true, $category);
}

function memberful_wp_shortcode_if_has_active_subscription( $atts, $content ) {
  $user_id = wp_get_current_user()->ID;

  if ( is_subscribed_to_any_memberful_plan( $user_id ) || current_user_can( 'publish_posts' ) ) {
    return do_shortcode($content);
  } else {
    return '';
  }
}

function memberful_wp_shortcode_if_does_not_have_active_subscription( $atts, $content ) {
  $user_id = wp_get_current_user()->ID;

  if ( is_subscribed_to_any_memberful_plan( $user_id ) || current_user_can( 'publish_posts' ) ) {
    return '';
  } else {
    return do_shortcode($content);
  }
}

function memberful_wp_slugs_to_ids( $slugs ) {
  if ( is_string( $slugs ) )
    $slugs = explode( ',', $slugs );

  return array_map( 'memberful_wp_extract_id_from_slug', $slugs );
}

function memberful_wp_extract_id_from_slug( $slug ) {
  if( strpos( $slug, '-') === FALSE) {
    return (int) $slug;
  }

  list( $id, $name ) = explode( '-', $slug, 2 );

  return (int) trim($id);
}
