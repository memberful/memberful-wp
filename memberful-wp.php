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

if( ! defined('MEMBERFUL_DIR'))
	define('MEMBERFUL_DIR', dirname(__FILE__));

define('MEMBERFUL_HTML', NULL);
define('MEMBERFUL_JSON', 'json');

require_once MEMBERFUL_DIR.'/lib/memberful-wp/authenticator.php';
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
 * Generate a URL to the memberful site
 *
 * @param string $uri    The URI to append
 * @param string $format The requested format
 * @return string URL
 */
function memberful_url($uri = '', $format = MEMBERFUL_HTML)
{
	$endpoint = '/'.$uri;

	if($format !== MEMBERFUL_HTML)
	{
		$endpoint .= '.'.$format;
	}

	return rtrim(get_option('memberful_site'),'/').$endpoint;
}

function memberful_member_url($format = MEMBERFUL_HTML)
{
	return memberful_url('member', $format);
}

function memberful_admin_products($format = MEMBERFUL_HTML)
{
	return memberful_url('admin/products', $format);
}

function memberful_admin_product_url($product_id, $format = MEMBERFUL_HTML)
{
	return memberful_url('admin/products/'.(int) $product_id, $format);
}
