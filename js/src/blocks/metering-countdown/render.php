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

$post_id       = get_queried_object_id();
$template      = isset( $attributes['template'] ) ? (string) $attributes['template'] : '';
$singular      = isset( $attributes['singularTemplate'] ) ? (string) $attributes['singularTemplate'] : '';
$last_template = isset( $attributes['lastArticleTemplate'] ) ? (string) $attributes['lastArticleTemplate'] : '';

if ( '' === trim( $template ) && '' === trim( $singular ) && '' === trim( $last_template ) ) {
  return;
}

// Anonymous / cached path: emit a hidden placeholder with every template so the client-side
// meter can pick the right message once it knows the remaining count.
if (
  Memberful_Metering_Access::RENDER_NONE !== Memberful_Metering_Access::current_anon_mode( $post_id )
  || ( function_exists( 'memberful_metering_is_releasing' ) && memberful_metering_is_releasing( (int) get_the_ID() ) )
) {
  printf(
    '<p %s hidden>%s</p>',
    get_block_wrapper_attributes(
      array(
        'data-memberful-countdown'         => '1',
        'data-memberful-template'          => wp_strip_all_tags( $template ),
        'data-memberful-template-singular' => wp_strip_all_tags( $singular ),
        'data-memberful-template-last'     => wp_strip_all_tags( $last_template ),
      )
    ),
    esc_html( wp_strip_all_tags( str_replace( '{count}', '', $template ) ) )
  );
  return;
}

// Logged-in / server-known path: render the count directly.
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
  $template = $last_template;
} elseif ( 1 === $remaining ) {
  $template = $singular;
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
  esc_html( $message )
);
