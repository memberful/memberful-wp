<?php
/*
Plugin Name: Memberful WP
Plugin URI: http://github.com/jestro/memberful-wp
Description: Allows Memberful users to login to WordPress using the Memberful oAuth 2.0 endpoint.
Version: 0.1
Author: Memberful
Author URI: http://memberful.com
License: GPL
*/

if( ! defined('MEMBERFUL_DIR'))
	define('MEMBERFUL_DIR', dirname(__FILE__));

define('MEMBERFUL_HTML', NULL);
define('MEMBERFUL_JSON', 'json');

// Should requests to memberful check the ssl crtificate?
define('MEMBERFUL_SSL_VERIFY', false);

// TODO: Generate this!
define('MEMBERFUL_TOKEN', 'aoisdn98q2h9can3r9uac98n3nhaiuhgmcznzwre98zcnh397hnizrchn87wr3chz9wrh9');

require_once MEMBERFUL_DIR.'/lib/memberful-wp/urls.php';
require_once MEMBERFUL_DIR.'/lib/memberful-wp/user/map.php';
require_once MEMBERFUL_DIR.'/lib/memberful-wp/authenticator.php';
require_once MEMBERFUL_DIR.'/lib/memberful-wp/options.php';
require_once MEMBERFUL_DIR.'/lib/memberful-wp/metabox.php';
require_once MEMBERFUL_DIR.'/lib/memberful-wp/acl.php';

add_filter('allowed_redirect_hosts', 'memberful_allowed_hosts');
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

function memberful_wp_render($template, array $vars = array())
{
	extract($vars);

	include MEMBERFUL_DIR.'/views/'.$template.'.php';
}

/**
 * Get details about a specific member via the API
 *
 * TODO: Clean this mess up.
 */
function memberful_api_member($member_id)
{
	$url = memberful_wrap_api_token(memberful_admin_member_url($member_id, MEMBERFUL_JSON));

	$response = wp_remote_get($url, array('sslverify' => MEMBERFUL_SSL_VERIFY));

	if(is_wp_error($response))
	{
		var_dump($response, $url);
		die();
	}

	if($response['response']['code'] !== 200 OR ! isset($response['body']))
	{
		var_dump($response);
		return new WP_Error('memberful_fail', 'Coult not get member info from api');
	}

	return json_decode($response['body']);
}

/**
 * Adds the memberful domain to the list of allowed redirect hosts
 * @param array $content A set of websites that can be redirected to
 * @return array The $content plus memberful domain
 */
function memberful_allowed_hosts($content) {
	$site = get_option('memberful_site');

	if(!empty($site))
	{
		$memberful_url = parse_url($site);
		
		if($memberful_url !== false)
			$content[] = $memberful_url['host'];
	}
	
	return $content;
}