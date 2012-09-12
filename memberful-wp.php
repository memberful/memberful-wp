<?php
/*
Plugin Name: Memberful WP
Plugin URI: http://github.com/memberful/memberful-wp
Description: Allows Memberful users to login to WordPress using the Memberful OAuth 2.0 endpoint.
Version: 0.1
Author: Memberful
Author URI: http://memberful.com
License: GPL
*/

if ( ! defined( 'MEMBERFUL_DIR' ) )
	define( 'MEMBERFUL_DIR', dirname( __FILE__ ) );

if ( ! defined( 'MEMBERFUL_APPS_HOST' ) )
	define( 'MEMBERFUL_APPS_HOST', 'https://apps.memberful.com' );

define( 'MEMBERFUL_HTML', NULL );
define( 'MEMBERFUL_JSON', 'json' );

// Should requests to memberful check the SSL certificate?
define( 'MEMBERFUL_SSL_VERIFY', defined( 'SITE_ENVIRONMENT' ) ? SITE_ENVIRONMENT == 'production' : FALSE );

// TODO: Generate this!
define( 'MEMBERFUL_TOKEN', 'aoisdn98q2h9can3r9uac98n3nhaiuhgmcznzwre98zcnh397hnizrchn87wr3chz9wrh9' );

require_once MEMBERFUL_DIR.'/src/urls.php';
require_once MEMBERFUL_DIR.'/src/user/map.php';
require_once MEMBERFUL_DIR.'/src/authenticator.php';
require_once MEMBERFUL_DIR.'/src/options.php';
require_once MEMBERFUL_DIR.'/src/metabox.php';
require_once MEMBERFUL_DIR.'/src/acl.php';
require_once MEMBERFUL_DIR.'/src/activator.php';

add_filter( 'allowed_redirect_hosts', 'memberful_allowed_hosts' );
add_action( 'admin_menu', 'memberful_wp_menu' );
add_action( 'admin_init', 'memberful_wp_register_options' );
add_action( 'admin_init', 'memberful_wp_activation_redirect' );
add_action( 'admin_enqueue_scripts', 'memberful_admin_enqueue_scripts' );

register_activation_hook( __FILE__, 'memberful_activate' );

function memberful_wp_activation_redirect() { 
	if ( get_option( 'memberful_wp_activation_redirect', FALSE ) ) { 
		delete_option( 'memberful_wp_activation_redirect' );

		if ( !isset( $_GET['activate-multi'] ) ) { 
			wp_redirect( admin_url( 'admin.php?page=memberful_options' ) );
		}
	}
}
function memberful_wp_menu() { 
	add_menu_page( 'Memberful Integration', 'Memberful', 'install_plugins', 'memberful_options', 'memberful_wp_options' );
}

function memberful_activate() { 
	global $wpdb;

	$columns = $wpdb->get_results( 'SHOW COLUMNS FROM `'.$wpdb->users.'` WHERE `Field` LIKE "memberful_%"' );

	if ( get_option( 'memberful_db_version', 0 ) < 1 ) { 
		$result = $wpdb->query(
			'CREATE TABLE `'.Memberful_User_Map::table().'`(
			`wp_user_id` INT UNSIGNED NULL DEFAULT NULL UNIQUE KEY,
			`member_id` INT UNSIGNED NOT NULL PRIMARY KEY,
			`refresh_token` VARCHAR( 45 ) NULL DEFAULT NULL,
			`last_sync_at` INT UNSIGNED NOT NULL DEFAULT 0)'
		);

		if ( $result === FALSE ) { 
			echo 'Could not create the memberful mapping table\n';
			$wpdb->print_error();
			exit();
		}

		if ( ! empty( $columns ) ) { 
			$wpdb->query(
				'INSERT INTO `'.Memberful_User_Map::table().'` '.
				'(`member_id`, `wp_user_id`, `refresh_token`, `last_sync_at`) '.
				'SELECT `memberful_member_id`, `ID`, `memberful_refresh_token`, UNIX_TIMESTAMP() '.
				'FROM `'.$wpdb->users.'` '.
				'WHERE `memberful_member_id` IS NOT NULL'
			);

			$wpdb->query(
				'ALTER TABLE `'.$wpdb->users.'`
				DROP COLUMN `memberful_member_id`,
				DROP COLUMN `memberful_refresh_token`'
			);
		}

		update_option( 'memberful_db_version', 1 );
	}

	add_option( 'memberful_wp_activation_redirect' , TRUE );
}

function memberful_wp_render( $template, array $vars = array() ) { 
	extract( $vars );

	include MEMBERFUL_DIR.'/views/'.$template.'.php';
}

/**
 * Get details about a specific member via the API
 *
 * TODO: Clean this mess up.
 */
function memberful_api_member( $member_id ) { 
	$url = memberful_wrap_api_token( memberful_admin_member_url( $member_id, MEMBERFUL_JSON ) );

	$response = wp_remote_get( $url, array( 'sslverify' => MEMBERFUL_SSL_VERIFY ) );

	if ( is_wp_error( $response ) ) { 
		var_dump( $response, $url );
		die();
	}

	if ( $response['response']['code'] != 200 OR ! isset( $response['body'] ) ) { 
		var_dump( $response );
		return new WP_Error( 'memberful_fail', 'Coult not get member info from api' );
	}

	return json_decode( $response['body'] );
}

/**
 * Adds the Memberful domain to the list of allowed redirect hosts
 * @param array $content A set of websites that can be redirected to
 * @return array The $content plus Memberful domain
 */
function memberful_allowed_hosts( $content ) { 
	$site = get_option( 'memberful_site' );

	if ( !empty( $site ) ) { 
		$memberful_url = parse_url( $site );

		if ( $memberful_url !== false )
			$content[] = $memberful_url['host'];
	}

	return $content;
}

/**
 * Enqueues the Memberful admin screen CSS, only on the settings page.
 * Hooked on admin_enqueue_scripts.
 */
function memberful_admin_enqueue_scripts() { 
	$screen = get_current_screen();

	if ( strpos( 'memberful', $screen->id ) !== NULL ) { 
		wp_enqueue_style(
			'memberful-admin',
			plugins_url( 'stylesheets/admin.css' , __FILE__ )
		);
	}
}
