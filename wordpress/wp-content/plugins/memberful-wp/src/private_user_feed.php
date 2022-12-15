<?php
/**
 * All the Logic that handles the processing & information part of the Private User Feed.
 *
 * If the init decides to parse this request as a valid feed, the request will be served and no future things will be executed.
 *
 * Caching will be disabled for this completely. Both browsers and caching plugins, just to keep the data 100% valid.
 *
 * @author Andrei-Robert Rusu
 */

add_action('init', 'memberful_private_user_feed_init');

function memberful_private_user_feed_init() {
  // In case this is not available, just don't carry on with the logic, it should always be here.
  if(!isset($_SERVER['REQUEST_URI']))
    return;

  // This is not a case we can verify, so we'll skip
  if(strpos($_SERVER['REQUEST_URI'], memberful_private_user_feed_get_url_identifier()) === false)
    return;

  // Extract the token from the URL
  $feedUserToken = sanitize_text_field( $_GET['member-feed'] );

  $requiredPlan = memberful_private_user_feed_settings_get_required_plan();

  // We want to allow the private user feed only if the admin has configured it.
  if($requiredPlan == false)
    return;


  // The only reliable way to make sure it works on all WP versions
  // We'll take "all" users with the token match.
  $user_query = new WP_User_Query(
    array(
      'meta_key' => 'memberful_private_user_feed_token',
      'meta_value' => $feedUserToken
    )
  );

  // Get the results from the query
  $users = $user_query->get_results();

  // We have no results.
  if(empty($users))
    return;

  // In case somebody actually maps this with their plugin with a hook, we still need to get the first one.
  $user = array_shift($users);

  if(!is_subscribed_to_memberful_plan($requiredPlan, $user->ID))
    return;

  // Everything is in order, we'll deliver the feed.
  memberful_private_user_feed_deliver($user->ID);
}

/**
 * Deliver the Memberful Private User Feed.
 * This Function should be used only within the "init" wordpress hook.
 */
function memberful_private_user_feed_deliver($user_id) {
  header('Content-Type: '.feed_content_type('rss-http').'; charset='.get_option('blog_charset'), true);
  memberful_private_user_feed_disable_caching();

  memberful_wp_render('private_user_feed_content', array('user_id' => $user_id));

  exit;
}

/**
 * @param string $success_message - '', if present, will return an clickable link
 * @param string $error_message - "You don’t have access to this RSS feed."
 * @param bool $return
 * @return string
 */
function memberful_private_rss_feed_link($success_message = '', $error_message = "You don’t have access to this RSS feed.", $return = false, $category = false) {
  $error_message = apply_filters( 'memberful_private_rss_feed_error_message', $error_message );

  if(!is_user_logged_in())
    return memberful_private_rss_feed_link_response_helper($error_message, $return);

  $requiredPlan = memberful_private_user_feed_settings_get_required_plan();

  // We want to allow the private user feed only if the admin has configured it.
  if($requiredPlan == false)
    return memberful_private_rss_feed_link_response_helper($error_message, $return);

  $current_user_id = get_current_user_id();

  if(!is_subscribed_to_memberful_plan($requiredPlan, $current_user_id))
    return memberful_private_rss_feed_link_response_helper($error_message, $return);

  $feedToken = get_user_meta($current_user_id, 'memberful_private_user_feed_token', true);

  if($feedToken == false || $feedToken == '') {
    $feedToken = substr(md5(uniqid(rand(1,10000))), 2, 30);
    update_user_meta($current_user_id, 'memberful_private_user_feed_token', $feedToken);
  }

  $link = (get_home_url() . '/' . memberful_private_user_feed_get_url_identifier($feedToken) );

  if($category)
    $link = add_query_arg('category', $category, $link);

  if($success_message != '')
    $link = '<a href="' . esc_url($link) . '">' . do_shortcode($success_message) . '</a>';

  return memberful_private_rss_feed_link_response_helper($link, $return);
}

function memberful_private_rss_feed_link_response_helper($response, $return = false) {
  if($return)
    return $response;

  echo wp_kses_post($response);

  return '';
}

// Admin Settings Functionality

/**
 * @param $option
 * @return void
 */
function memberful_private_user_feed_settings_set_required_plan($option) {
  update_option('memberful_private_user_feed_plan', $option);
}

/**
 * @return mixed|string|array|false
 */
function memberful_private_user_feed_settings_get_required_plan() {
  return get_option('memberful_private_user_feed_plan', false);
}

// General Functionality

if(!function_exists('memberful_private_user_feed_disable_caching'))  {

  /**
   * Disable All Caching Options for the feed page.
   * @return void
   */
  function memberful_private_user_feed_disable_caching() {
    // Tell Browsers and Various caching plugins to not cache this. Also Varnish should listen to this.
    nocache_headers();
    // We want to make sure that caching plugins know this page doesn't need to be cached
    if(!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', TRUE);
  }

}

if(!function_exists('memberful_private_user_feed_get_url_identifier'))  {

  /**
   * Get the URL Prefix used to verify if it's an feed request, optionally you can provide the user token
   * @param string $user_token
   * @return string
   */
  function memberful_private_user_feed_get_url_identifier($user_token = '') {
    return '?member-feed=' . $user_token;
  }

}
function memberful_private_user_feed_description() {
  $description = memberful_get_bloginfo_rss( 'description' );
  return apply_filters( 'memberful_private_rss_description', $description );
}

function memberful_private_user_feed_title() {
  $title = memberful_get_bloginfo_rss( 'name' ) . ' Member Feed';
  return apply_filters( 'memberful_private_rss_title', $title );
}

function memberful_get_bloginfo_rss( $attribute ) {
  return apply_filters( 'bloginfo_rss', get_bloginfo_rss( $attribute ), $attribute );
}

function memberful_can_user_access_rss_post( $user_id, $post_id ) {
  $allowAccess = true;

  return apply_filters('memberful_can_user_access_rss_post', $allowAccess, $user_id, $post_id);
}
