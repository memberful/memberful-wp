<?php
/*
Plugin Name: Memberful Wordpress Integeration
Plugin URI: http://github.com/jestro/memberful-wp
Description: Allows your memberful users to login to wordpress
Version: 0.1
Author: Jestro
Author URI: http://thethemefoundry.com
License: GPL
*/

define('MEMBERFUL_DIR', dirname(__FILE__));
require_once MEMBERFUL_DIR.'/lib/memberful-wp/options.php';

add_action('admin_menu', 'memberful_wp_register_options_panel');
add_action('admin_init', 'memberful_wp_register_options');

add_action('init', 'memberful_init');
register_activation_hook(__FILE__, 'memberful_activate');

function memberful_init()
{
	global $wp, $wp_rewrite;

	// When index.php is not the endpoint the rule goes in htaccess
	// This may cause problems if .htaccess is not writable
	//
	// Facepress gets around this by rewriting to index.php then hooking into 
	// the template redirect hook to call the oauth callback
	add_rewrite_rule('oauth', 'wp-login.php?memberful_auth=1', 'top');
	flush_rewrite_rules(true);
}

function memberful_activate()
{
	flush_rewrite_rules(true);
}

/**
 * Is OAuth authentication enabled?
 *
 * @return boolean
 */
function memberful_wp_oauth_enabled()
{
	return TRUE;
}

/**
 * Gets the url for the specified action at the member oauth endpoint
 *
 * @param string $action Action to access at endpoint
 * @return string URL
 */
function memberful_oauth_member_url($action = '')
{
	return rtrim(get_option('memberful_site'),'/').'/oauth/'.$action;
}

/**
 * Returns the url of the endpoint that members will be sent to
 *
 * @return string
 */
function memberful_oauth_auth_url()
{
	$params = array(
		'response_type' => 'code',
		'client_id'     => get_option('memberful_client_id')
	);

	return add_query_arg($params, memberful_oauth_member_url());
}

/**
 * Callback for the `authenticate` hook.
 *
 * Called in wp-login.php when the login form is rendered, thus it responds
 * to both GET and POST requests.
 *
 * @return WP_User The user to be logged in or NULL if user couldn't be
 * determined
 */
function memberful_wp_oauth_setup($user, $username, $password)
{
	// If another authentication system has handled this request
	if($user instanceof WP_User || ! memberful_wp_oauth_enabled())
	{
		return $user;
	}

	// If a username or password has been posted then fallback to normal auth
	//
	// If GET isn't empty (e.g. a redirect_to is supplied) and the page that sent
	// them here hasn't requested memberful authentication then chances are its
	// some kind of admin related operation which a customer won't be able to perform,
	// in which case we should allow them to specify a username/password to 
	// login with
	if( ! empty($username) || ! empty($password) || ( ! empty($_GET) && ! isset($_GET['memberful_auth'])))
	{
		return $user;
	}

	// This is the OAuth response
	if(isset($_GET['code']))
	{
		$access_token = memberful_oauth_get_access_token($_GET['code']);
	}
	// For some reason we got an error code.
	elseif(isset($_GET['error']))
	{
		
	}

	// Send the user to memberful
	wp_redirect(memberful_oauth_auth_url(), 302);
	exit();
}
add_filter('authenticate', 'memberful_wp_oauth_setup', 10, 3);

/**
 * Gets the access token from an authorization code
 *
 * @param string $auth_code The authorization code returned from oauth endpoint
 * @return string Access token
 */
function memberful_oauth_get_access_token($auth_code)
{
	$params = array(
		'client_id'     => get_option('memberful_client_id'),
		'client_secret' => get_option('memberful_client_secret'),
		'grant_type'    => 'authorization_code',
		'code'          => $auth_code
	);
	$response = wp_remote_post(memberful_oauth_member_url('token'), array('body' => $params));

	var_dump($response);

	die();
}
