<?php

wp_clear_scheduled_hook( 'memberful_wp_cron_sync_users' );

if ( ! wp_next_scheduled( 'memberful_wp_cron_sync' ) ) {
	  wp_schedule_event( time(), 'twicedaily', 'memberful_wp_cron_sync' );
}

add_action( 'memberful_wp_cron_sync', 'memberful_wp_cron_sync_users' );

function memberful_wp_cron_sync_users() {
	echo 'Starting memberful_wp_cron_sync_users';

	$members_to_sync = Memberful_User_Map::least_recently_synced_members();
	$mapper = new Memberful_User_Map();

	foreach ( $members_to_sync as $member_id ) {
		$account = memberful_api_member( $member_id );

		if ( is_wp_error( $account ) ) {
			echo 'Could not retrieve account';
			return memberful_wp_record_error(array(
				'caller' => 'memberful_wp_cron_sync_users',
				'error'  => $account->get_error_messages()
			));
		}

		$user = $mapper->map( $account->member );

		Memberful_Wp_User_Downloads::sync($user->ID, $account->products);
		Memberful_Wp_User_Subscriptions::sync($user->ID, $account->subscriptions);
	}
	echo 'Done memberful_wp_cron_sync_users';
}

