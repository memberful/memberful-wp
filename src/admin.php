<?php

require_once MEMBERFUL_DIR . '/src/options.php';
require_once MEMBERFUL_DIR . '/src/metabox.php';

add_action( 'admin_menu',            'memberful_wp_menu' );
add_action( 'admin_init',            'memberful_wp_register_options' );
add_action( 'admin_init',            'memberful_wp_activation_redirect' );
add_action( 'admin_init',            'memberful_wp_plugin_migrate_db' );
add_action( 'admin_enqueue_scripts', 'memberful_wp_admin_enqueue_scripts' );
add_action( 'admin_head',            'memberful_wp_add_icon' );

/**
 * Ensures the database is up to date
 */
function memberful_wp_plugin_migrate_db() {
	global $wpdb;

	if ( get_option( 'memberful_db_version', 0 ) < 1 ) {
		$result = $wpdb->query(
			'CREATE TABLE `'.Memberful_User_Map::table().'`(
			`wp_user_id` INT UNSIGNED NULL DEFAULT NULL UNIQUE KEY,
			`member_id` INT UNSIGNED NOT NULL PRIMARY KEY,
			`refresh_token` VARCHAR( 45 ) NULL DEFAULT NULL,
			`last_sync_at` INT UNSIGNED NOT NULL DEFAULT 0)'
		);

		if ( $result === false ) {
			echo 'Could not create the memberful mapping table\n';
			$wpdb->print_error();
			exit();
		}

		$columns = $wpdb->get_results( 'SHOW COLUMNS FROM `'.$wpdb->users.'` WHERE `Field` LIKE "memberful_%"' );

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
}

/**
 * Redirects the admin to the memberful plugin page after they activate the
 * plugin
 */
function memberful_wp_activation_redirect() {
	if ( get_option( 'memberful_wp_activation_redirect', false ) ) {
		delete_option( 'memberful_wp_activation_redirect' );

		if ( !isset( $_GET['activate-multi'] ) ) {
			wp_redirect( admin_url( 'admin.php?page=memberful_options' ) );
		}
	}
}

/**
 * Creates the necessary items in the admin menu
 */
function memberful_wp_menu() {
	add_menu_page( 'Memberful Integration', 'Memberful', 'install_plugins', 'memberful_options', 'memberful_wp_options' );
}


/**
 * Enqueues the Memberful admin screen CSS, only on the settings page.
 * Hooked on admin_enqueue_scripts.
 */
function memberful_wp_admin_enqueue_scripts() {
	$screen = get_current_screen();

	if ( strpos( 'memberful', $screen->id ) !== null ) {
		wp_enqueue_style(
			'memberful-admin',
			plugins_url( 'stylesheets/admin.css' , dirname(__FILE__) )
		);
	}
}

/**
 * Adds CSS that applies a menu icon to the memberful menu.
 */
function memberful_wp_add_icon() {
?>
	<style type="text/css" media="screen">
		#toplevel_page_memberful_options .wp-menu-image {
			background: url(<?php echo MEMBERFUL_URL; ?>/images/memberful-menu-icon.png) no-repeat 2px 0 !important;
			background-size: 28px 56px !important;
		}
		#toplevel_page_memberful_options:hover .wp-menu-image,
		#toplevel_page_memberful_options.current .wp-menu-image {
			background-position: 2px -25px !important;
		}
	</style>
<?php
}
