<?php

add_filter( 'mce_buttons', 'memberful_wp_register_editor_buttons' );
add_filter( 'mce_external_plugins', 'memberful_wp_load_tinymce_extensions' );


function memberful_wp_register_editor_buttons( array $buttons ) {
  array_push( $buttons, 'separator', 'memberful_wp', 'separator' );

  return $buttons;
}

function memberful_wp_load_tinymce_extensions( array $plugins ) {
  $plugins['memberful_wp'] = plugins_url('/js/memberful-wp-tinymce-plugin.js', MEMBERFUL_PLUGIN_FILE );

  return $plugins;
}

