<?php

class Memberful_Wp_Integration_Sfwd_Learndash {

  protected static $_instance;

  /**
   * @return Memberful_Wp_Integration_SFWD_Learndash
   */
  public static function instance() {
    if( self::$_instance === null )
      self::$_instance = new self();

    return self::$_instance;
  }

  function __construct() {
    add_filter( 'memberful_metabox_post_types', array( $this, 'filter_learndash_subtypes' ) );
    add_filter( 'the_content', array( $this, 'protect_learndash_content' ), 1001 );
    add_filter( 'comments_open', array( $this, 'hide_comments_on_protected_content' ), 200, 2 );
  }

  /**
   * hide_comments_on_protected_content
   *
   * Filter hook to close comments on protected courses and related subtypes
   *
   * @param bool $comments_open - are comments open coming into the filter
   * @param int $post_id - ID of WP_Post the comments should be open or closed for
   * @since 1.35.0
   * @access public
   * @return boolean
   */
  function hide_comments_on_protected_content($comments_open, $post_id) {
    global $learndash_post_types;
    $post = get_post( $post_id );
    if ( ! in_array( $post->post_type, $learndash_post_types ) ) {
      return $comments_open;
    }
    $post_id = $post->post_type != 'sfwd-courses' ? (int) get_post_meta( $post->ID, 'course_id', true ) : $post_id;

    return memberful_can_user_access_post( get_current_user_id(), $post_id ); 
  }

  /**
   * filter_learndash_subtypes
   *
   * Excludes LD subtypes like lessons from having a Memberful protection metabox
   *
   * @param array $types post_types that get Memberful metabox
   * @since 1.35.0
   * @access public
   * @return array
   */
  function filter_learndash_subtypes( $types ) {
    global $learndash_post_types;
    foreach( $learndash_post_types as $post_type ) {
      if ( ! in_array( $post_type , array( 'post', 'page', 'sfwd-courses' ) ) ) {
        unset( $types[ $post_type ] );
      }
    }
    return $types;
  }

  /**
   * Protect content
   *
   * Adds protection to content after making sure it is LD content
   *
   * @since 1.35.0
   * @access public
   * @return string
   */
  function protect_learndash_content( $content ) {
    global $learndash_post_types, $post;

    if ( ! is_single() || ! in_array( $post->post_type, $learndash_post_types ) ) {
      return $content;
    }

    if (in_array( $post->post_type, $learndash_post_types )) {
      $post_id = $post->post_type != 'sfwd-courses' ? (int) get_post_meta( $post->ID, 'course_id', true ) : $post->ID;
    }

    return !memberful_can_user_access_post( get_current_user_id(), $post_id ) ? $this->memberful_wp_protect_learndash_content( $content, $post_id ) : $content;
  }

  /**
   * Protect content at the course level
   *
   * The existing protect function protects content the "the_post() ($GLOBALS['post']"
   * but we need to send in the id of the parent course to protect all sub-types
   *
   * @since 1.35.0
   * @access public
   * @return string
   */
  function memberful_wp_protect_learndash_content( $content, $post_id ) {
    $memberful_marketing_content = memberful_wp_kses_post( memberful_marketing_content( $post_id ) );
    return apply_filters( 'memberful_wp_protect_content', $memberful_marketing_content );
  }

  /**
   * Throw error on object clone
   *
   * The whole idea of the singleton design pattern is that there is a single
   * object therefore, we don't want the object to be cloned.
   *
   * @since 1.6
   * @access protected
   * @return void
   */
  public function __clone() {
    _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'memberful' ), '1.18.1' );
  }
  /**
   * Disable unserializing of the class
   *
   * @since 1.6
   * @access protected
   * @return void
   */
  public function __wakeup() {
    _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'memberful' ), '1.18.1' );
  }

}

Memberful_Wp_Integration_Sfwd_Learndash::instance();
