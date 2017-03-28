<?php

class Memberful_Wp_Integration_WooThemes_Sensei {

  protected static $_instance;

  /**
   * @return Memberful_Wp_Integration_WooThemes_Sensei
   */
  public static function instance() {
    if( self::$_instance === null )
      self::$_instance = new self();

    return self::$_instance;
  }

  public $hide_course_lesson_list = true;

  /**
   * This should be handled with care, if used wrong, may cause an infinite loop.
   */
  public function __construct() {

  }

  public function init() {
    if( !is_admin() )
      add_action( 'template_redirect', array( $this, 'handle_delivery'), 50);
  }

  public function handle_delivery() {
    if( is_single() ) {
      $post_type = get_post_type();

      if( $post_type == 'course' )
        $this->single_course_handler();
      else if( $post_type == 'lesson' )
        $this->single_lesson_handler();
      else if( $post_type == 'quiz' )
        $this->single_quiz_handler();
    }
  }

  public function single_course_handler() {
    global $post;

    if( is_user_logged_in() ) {
      if( WooThemes_Sensei_Utils::user_started_course( $post->ID, wp_get_current_user()->ID ) )
        return false;
    }

    // The user doesn't have access to this, so we won't allow him to start the course.
    if ( !memberful_can_user_access_post( wp_get_current_user()->ID, $post->ID ) )
      remove_all_actions( 'sensei_course_single_meta' );

    if( $this->hide_course_lesson_list )
      remove_all_actions( 'sensei_course_single_lessons' );

    return true;
  }

  public function single_lesson_handler() {
    global $post;

    // Preview Lessons shouldn't ignore this rule.
    if( WooThemes_Sensei_Utils::is_preview_lesson( $post->ID ) )
      return;

    $course_id = get_post_meta( $post->ID, '_lesson_course', true );

    // User already started this course, so ideally, we shouldn't restrict access.
    if( WooThemes_Sensei_Utils::user_started_course( $post->ID, wp_get_current_user()->ID ) )
      return;

    // This happens if the lesson isn't locked itself.
    if ( memberful_can_user_access_post( wp_get_current_user()->ID, $post->ID ) ) {
      if ( !memberful_can_user_access_post( wp_get_current_user()->ID, $course_id ) ) {
        // The user doesn't have access to this post, so he shouldn't have actions on it.
        remove_all_actions( 'sensei_lesson_single_meta' );

        // Now the funky filtering part.
        remove_action( 'the_content', 'memberful_wp_protect_content' );
        add_action( 'the_content', array( $this, 'single_lesson_special_content_filter' ), -10 );
      }
    } else {
      // The user doesn't have access to this post, so he shouldn't have actions on it.
      remove_all_actions( 'sensei_lesson_single_meta' );
    }
  }

  /**
   * This is a very tricky function, which technically enables "tricking" Memberful to check
   * the course post if the lesson post doesn't do the trick, by default.
   * This is a simple hack, that enables us to not duplicate functionality and keep things stable.
   * @param $content
   * @return mixed|void
   */
  public function single_lesson_special_content_filter( $content ) {
    global $post;

    $post_copy = $post;
    $post      = get_post( get_post_meta( $post_copy->ID, '_lesson_course', true ) );

    $content = memberful_wp_protect_content( $content );

    $post = $post_copy;

    return $content;
  }

  public function single_quiz_handler() {

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
    _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mcmc_inventory' ), '1.18.1' );
  }

}

Memberful_Wp_Integration_WooThemes_Sensei::instance()->init();
