<?php

class Memberful_Wp_Integration_WooCommerce {

  protected static $_instance;

  /**
   * @return Memberful_Wp_Integration_s
   */
  public static function instance() {
    if( self::$_instance === null ) {
      self::$_instance = new self();
    }

    return self::$_instance;
  }

  function __construct() {
    add_action( 'woocommerce_single_product_summary', array( $this, 'hide_add_to_cart_button' ), 25);
    add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'block_cart_add' ), 30, 3 );
    add_filter( 'woocommerce_is_purchasable', array( $this, 'is_purchasable'), 20, 2 );
    add_filter( 'the_content', array( $this, 'remove_erroneous_protection' ), -20 );
  }

  /**
   * Removes filtering on the content from other functionalities
   */
  function remove_erroneous_protection( $content ) {
    global $post;
    if ($post->post_type == "product") {
      remove_filter( 'the_content', 'memberful_wp_protect_content', 100 );
    }
    return $content;
  }

  /**
   * Makes it so that the add to cart button on archive page will show Read More and link to product on
   * Archive (loop) pages
   */
  function is_purchasable( $purchasable, $product ) {
    return !memberful_is_admin( wp_get_current_user() ) && !memberful_can_user_access_post( get_current_user_id(), $product->get_id() ) ? false : $purchasable;
  }

  /**
   * Hides add to cart button on single product page and shows marketing content instead
   */
  function hide_add_to_cart_button() {
    global $post;

    if ( !memberful_is_admin( wp_get_current_user() ) && !memberful_can_user_access_post( get_current_user_id(), $post->ID ) ) {
      remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
      echo $this->memberful_wp_protect_woo_content( $post->ID );
    }
  }

  /*
   * All add to cart calls pass through this filter
   * Ajax and non Ajax
   * @param (bool) $passed
   * @param (int) $product_id
   * @param (int) $quantity
   * @return bool
   */
  function block_cart_add( $passed, $product_id, $quantity ) {
    if ( !memberful_is_admin( wp_get_current_user() ) && !memberful_can_user_access_post( get_current_user_id(), $product_id ) ) {
      wc_add_notice( $this->memberful_wp_protect_woo_content( $product_id ), 'error' );
      return false;
    }
    return (bool)$passed;
  }

  function memberful_wp_protect_woo_content( $post_id ) {
    $memberful_marketing_content = memberful_wp_kses_post( memberful_marketing_content( $post_id ) );
    return apply_filters( 'memberful_wp_protect_content', $memberful_marketing_content );
  }

  
}

Memberful_Wp_Integration_WooCommerce::instance();
