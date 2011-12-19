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

function memberful_init() {}

function memberful_activate()
{
	global $wpdb;

	$columns = $wpdb->get_results('SHOW COLUMNS FROM `'.$wpdb->users.'` WHERE `Field` LIKE "memberful_%"');

	if(empty($columns))
	{
		$result = $wpdb->query('ALTER TABLE `'.$wpdb->users.'`
			ADD COLUMN `memberful_member_id` INT UNSIGNED NULL DEFAULT NULL,
			ADD COLUMN `memberful_refresh_token` VARCHAR(45) NULL DEFAULT NULL,
			ADD UNIQUE INDEX `memberful_member_id_UNIQUE` (`memberful_member_id` ASC),
			ADD UNIQUE INDEX `memberful_refresh_token_UNIQUE` (`memberful_refresh_token` ASC)');

		// If for some reason the plugin could not be activated
		if($result === FALSE)
		{
			echo 'Could not create the necessary modifications to the users table\n';
			$wpdb->print_error();
			exit();
		}
	}

	// When index.php is not the endpoint the rule goes in htaccess
	// This may cause problems if .htaccess is not writable
	//
	// Facepress gets around this by rewriting to index.php then hooking into
	// the template redirect hook to call the oauth callback
	add_rewrite_rule('oauth', 'wp-login.php?memberful_auth=1', 'top');
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
		$tokens = memberful_oauth_get_tokens($_GET['code']);

		$member = memberful_get_member_data($tokens->access_token);

		return memberful_sync_user_from_memberful($member, $tokens->refresh_token);
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
 * Gets the access token and refresh token from an authorization code
 *
 * @param string $auth_code The authorization code returned from oauth endpoint
 * @return StdObject Access token and Refresh token
 */
function memberful_oauth_get_tokens($auth_code)
{
	$params = array(
		'client_id'     => get_option('memberful_client_id'),
		'client_secret' => get_option('memberful_client_secret'),
		'grant_type'    => 'authorization_code',
		'code'          => $auth_code
	);
	$response = wp_remote_post(memberful_oauth_member_url('token'), array('body' => $params));

	return json_decode($response['body']);
}

/**
 * Gets information about a user from memberful.
 *
 * @param string $access_token An access token which can be used to get info
 * about the member
 * @return array
 */
function memberful_get_member_data($access_token)
{
	$url = rtrim(get_option('memberful_site'),'/').'/member.json';

	$response = wp_remote_get(add_query_arg('access_token', $access_token, $url));

	return json_decode($response['body']);
}

/**
 * Takes a set of memberful member details and tries to associate it with the
 * wordpress user account.
 *
 * @param StdObject $details       Details about the member
 * @param string    $refresh_token The member's refresh token for oauth
 * @return WP_User
 */
function memberful_sync_user_from_memberful($details, $refresh_token)
{
	global $wpdb;

	$member   = $details->member;
	$products = $details->products;

	$query = $wpdb->prepare(
		'SELECT *, (`memberful_member_id` = %d) AS `exact_match` FROM `'.$wpdb->users.'` WHERE `memberful_member_id` = %d OR `user_email` = %s ORDER BY `exact_match` DESC',
		$member->id,
		$member->id,
		$member->email
	);

	$user = $wpdb->get_row($query);

	// User does not exist
	if($user === NULL)
	{
		$data = array(
			'user_pass'     => wp_generate_password(),
			'user_login'    => $member->username,
			'user_nicename' => $member->full_name,
			'user_email'    => $member->email,
			'display_name'  => $member->full_name,
			'nickname'      => $member->full_name,
			'first_name'    => $member->first_name,
			'last_name'     => $member->last_name,
			'show_admin_bar_frontend' => FALSE,
		);

		$user_id = wp_insert_user($data);

		if(is_wp_error($user_id))
		{
			var_dump($user_id);
			die('ERRORR!!!');
			return $user_id;
		}
	}
	else
	{
		// Now sync the two accounts
		$user_id = $user->ID;

		// Mapping of wordpress => memberful keys
		$mapping = array(
			'user_email'    => 'email',
			'user_login'    => 'username',
			'display_name'  => 'full_name',
			'user_nicename' => 'full_name',

		);

		$metamap = array(
			'nickname'      => 'full_name',
			'first_name'    => 'first_name',
			'last_name'     => 'last_name'
		);

		$meta = get_user_meta($user_id, '', true);

		// For some insane reason Wordpress only allows us to do a complete update of values
		// No partial updates allowed.
		$data = (array) $user;

		foreach($mapping as $wp_key => $m_key)
		{
			$data[$wp_key] = $member->$m_key;
		}

		foreach($metamap as $wp_key => $m_key)
		{
			$data[$wp_key] = $member->$m_key;
		}

		wp_insert_user($data);
	}

	$wpdb->query($wpdb->prepare('UPDATE `'.$wpdb->users.'` SET `memberful_refresh_token` = %s, `memberful_member_id` = %d WHERE `ID` = %d', $refresh_token, $member->id, $user_id));
	
	return get_userdata($user_id);
}
