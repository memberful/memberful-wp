<?php
/**
 * Per-post "Exempt from meter" override.
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Metering_Metabox.
 */
class Memberful_Metering_Metabox {
  /**
   * Post meta key holding the per-post exempt flag.
   *
   * @var string
   */
  const META_KEY = 'memberful_metering_exempt';

  /**
   * Nonce action for the exempt metabox save.
   *
   * @var string
   */
  const NONCE_ACTION = 'memberful_metering_exempt';

  /**
   * Nonce field name. Deliberately distinct from the access metabox's `memberful_nonce` field so the two boxes never
   * clash on a shared edit screen.
   *
   * @var string
   */
  const NONCE_FIELD = 'memberful_metering_nonce';

  /**
   * Register hooks. Called once from src/metering.php.
   */
  public static function register(): void {
    add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
    add_action( 'save_post', array( __CLASS__, 'save' ) );
  }

  /**
   * Add the metabox to supported post types, but only while metering is enabled.
   */
  public static function add_meta_box(): void {
    if ( empty( Memberful_Metering_Config::get()['enabled'] ) ) {
      return;
    }

    foreach ( memberful_wp_metabox_types() as $post_type ) {
      add_meta_box(
        'memberful_metering_exempt',
        __( 'Memberful: Metering', 'memberful' ),
        array( __CLASS__, 'render' ),
        $post_type
      );
    }
  }

  /**
   * Render the metabox body.
   *
   * @param WP_Post $post Post being edited.
   */
  public static function render( WP_Post $post ): void {
    wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

    memberful_wp_render(
      'metering/metabox',
      array(
        'exempt' => (bool) get_post_meta( $post->ID, self::META_KEY, true ),
      )
    );
  }

  /**
   * Persist the exempt flag on save_post.
   *
   * @param int $post_id Post being saved.
   */
  public static function save( int $post_id ): void {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return;
    }

    $nonce = filter_input( INPUT_POST, self::NONCE_FIELD );
    if ( ! $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
      return;
    }

    $parent_id = wp_is_post_revision( $post_id );
    if ( $parent_id ) {
      $post_id = $parent_id;
    }

    $post_type = get_post_type( $post_id );
    if ( ! in_array( $post_type, memberful_wp_metabox_types(), true ) ) {
      return;
    }

    $permission = 'page' === $post_type ? 'edit_page' : 'edit_post';
    if ( ! current_user_can( $permission, $post_id ) ) {
      return;
    }

    if ( filter_input( INPUT_POST, self::META_KEY ) ) {
      update_post_meta( $post_id, self::META_KEY, true );
    } else {
      delete_post_meta( $post_id, self::META_KEY );
    }
  }
}
