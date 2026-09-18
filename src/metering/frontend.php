<?php
/**
 * Front-end metering runtime: enqueues the cache-safe script and styles for anonymous metered post views, and prints
 * a pre-paint guard so a free post that is already over the limit hides its body before first paint (no flash).
 *
 * @package memberful-wp
 */

add_action( 'wp_enqueue_scripts', 'memberful_metering_enqueue_frontend' );
add_action( 'wp_head', 'memberful_metering_prepaint', 1 );

/**
 * Enqueue the runtime + styles with per-URL-static config only (no per-visitor data, so the page stays cacheable).
 */
function memberful_metering_enqueue_frontend(): void {
  if ( ! Memberful_Metering_Access::is_anon_metered_view() ) {
    return;
  }

  wp_enqueue_style(
    'memberful-metering',
    MEMBERFUL_URL . '/stylesheets/metering.css',
    array(),
    MEMBERFUL_VERSION
  );

  $asset = MEMBERFUL_DIR . '/js/build/metering.asset.php';
  $info  = file_exists( $asset ) ? require $asset : array(
    'dependencies' => array(),
    'version'      => MEMBERFUL_VERSION,
  );

  wp_enqueue_script(
    'memberful-metering',
    MEMBERFUL_URL . '/js/build/metering.js',
    $info['dependencies'],
    $info['version'],
    true
  );

  $runtime_config = wp_json_encode( memberful_metering_runtime_config(), JSON_HEX_TAG );
  if ( false === $runtime_config ) {
    return;
  }

  wp_add_inline_script(
    'memberful-metering',
    'window.memberfulMetering = ' . $runtime_config . ';',
    'before'
  );
}

/**
 * For a free metered post, mark the document (before paint) when localStorage already shows the meter tripped, so CSS
 * can hide the body without a flash. The snippet is identical for every anonymous visitor, so it stays cacheable.
 */
function memberful_metering_prepaint(): void {
  if ( Memberful_Metering_Access::RENDER_FREE_METER !== Memberful_Metering_Access::current_anon_mode( (int) get_queried_object_id() ) ) {
    return;
  }

  $config = wp_json_encode( memberful_metering_runtime_config(), JSON_HEX_TAG );
  if ( false === $config ) {
    return;
  }

  // Read-only mirror of the free-meter "already over the limit?" check; metering.js (footer) does the authoritative
  // metering. JSON_HEX_TAG keeps the injected config safe inside the script tag.
  $script = sprintf(
    '( function ( config ) {
  try {
    var data = JSON.parse( window.localStorage.getItem( config.storageKey ) || "{}" ) || {};
    var views = data.views || {};
    var cutoff = Math.floor( Date.now() / 1000 ) - config.periodDays * 86400;
    var count = 0;
    var alreadyCounted = false;

    Object.keys( views ).forEach( function ( postId ) {
      if ( views[ postId ] >= cutoff ) {
        count++;
        if ( Number( postId ) === config.postId ) {
          alreadyCounted = true;
        }
      }
    } );

    if ( ! alreadyCounted && count >= config.limit ) {
      document.documentElement.className += " memberful-metering-tripped";
    }
  } catch ( error ) {}
}( %s ) );',
    $config
  );

  wp_print_inline_script_tag( $script );
}

/**
 * Per-URL-static runtime config shared by the enqueued script and the pre-paint guard.
 *
 * @return array
 */
function memberful_metering_runtime_config(): array {
  $config  = Memberful_Metering_Config::get();
  $post_id = (int) get_queried_object_id();

  return array_merge(
    array(
      'postId'     => $post_id,
      'mode'       => Memberful_Metering_Access::current_anon_mode( $post_id ),
      'limit'      => (int) $config['anonymous_limit'],
      'periodDays' => (int) $config['period_days'],
      'storageKey' => Memberful_Metering_Storage::COOKIE_NAME,
    ),
    Memberful_Metering_Sample::script_args()
  );
}
