<?php
/**
 * Block Editor/Gutenberg compatibility.
 *
 * @package Memberful
 */


add_action( 'enqueue_block_editor_assets', 'memberful_wp_enqueue_block_editor_assets' );

/**
 * Enqueue block editor assets.
 *
 * These scripts are specifically used for the block editor experience.
 *
 * @return void
 */
function memberful_wp_enqueue_block_editor_assets() {
  $block_editor_script = include_once MEMBERFUL_DIR . '/js/build/editor-scripts.asset.php';

  wp_enqueue_script(
    'memberful-wp-block-editor',
    plugins_url( 'js/build/editor-scripts.js', MEMBERFUL_PLUGIN_FILE ),
    $block_editor_script['dependencies'],
    $block_editor_script['version']
  );
}