<?php
/**
 * Metering module loader and admin settings.
 *
 * @package memberful-wp
 */

require_once MEMBERFUL_DIR . '/src/metering/config.php';
require_once MEMBERFUL_DIR . '/src/metering/sanitizer.php';
require_once MEMBERFUL_DIR . '/src/metering/storage.php';
require_once MEMBERFUL_DIR . '/src/metering/access.php';
require_once MEMBERFUL_DIR . '/src/metering/metabox.php';

Memberful_Metering_Access::register();
Memberful_Metering_Metabox::register();

/**
 * Render and save the metering settings screen.
 */
function memberful_wp_metering_settings() {
  if ( isset( $_POST['save_metering'] ) && memberful_wp_valid_nonce( 'memberful_options' ) ) {
    $raw_config = empty( $_POST['memberful_metering'] ) ? array() : (array) wp_unslash( $_POST['memberful_metering'] );

    Memberful_Metering_Config::save( $raw_config );

    Memberful_Wp_Reporting::report( __( 'Settings updated', 'memberful' ) );

    wp_redirect( memberful_wp_plugin_metering_url() );
    exit;
  }

  memberful_wp_render(
    'metering/settings',
    array(
      'config'            => Memberful_Metering_Config::get(),
      'fields'            => Memberful_Metering_Config::fields(),
      'operators'         => Memberful_Metering_Config::operators(),
      'post_type_options' => memberful_wp_metering_post_type_options(),
      'form_target'       => memberful_wp_plugin_metering_url( true ),
    )
  );
}

/**
 * Build the post-type select options used by the metering rule builder.
 *
 * @return array<string, string> Map of post type slug to its singular label.
 */
function memberful_wp_metering_post_type_options(): array {
  $options = array();

  foreach ( Memberful_Metering_Config::public_post_types() as $name => $object ) {
    $options[ $name ] = ! empty( $object->labels->singular_name ) ? $object->labels->singular_name : $object->label;
  }

  return $options;
}
