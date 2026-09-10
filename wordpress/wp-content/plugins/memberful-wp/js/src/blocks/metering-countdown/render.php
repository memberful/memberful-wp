<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$post_id = get_queried_object_id();

if ( Memberful_Metering_Access::DECISION_ALLOW_SAMPLE !== Memberful_Metering_Access::get_current_decision( $post_id ) ) {
  return;
}

$remaining = Memberful_Metering_Access::get_current_remaining( $post_id );

/**
 * Remaining counts views left *after* this article, so on the last free article it is zero.
 * Swap in the dedicated message there instead of rendering "0 free articles left", and use
 * the singular wording when exactly one view remains.
 */
if ( 0 === $remaining ) {
  $template = isset( $attributes['lastArticleTemplate'] ) ? (string) $attributes['lastArticleTemplate'] : '';
} elseif ( 1 === $remaining ) {
  $template = isset( $attributes['singularTemplate'] ) ? (string) $attributes['singularTemplate'] : '';
} else {
  $template = isset( $attributes['template'] ) ? (string) $attributes['template'] : '';
}

$message = str_replace(
  '{count}',
  number_format_i18n( $remaining ),
  $template
);

if ( '' === trim( $message ) ) {
  return;
}

printf(
  '<p %s>%s</p>',
  get_block_wrapper_attributes(),
  wp_kses_post( $message )
);
