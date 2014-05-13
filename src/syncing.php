<?php
require_once MEMBERFUL_DIR.'/src/user/downloads.php';
require_once MEMBERFUL_DIR.'/src/user/subscriptions.php';

function memberful_wp_sync_member_from_memberful( $member_id, $mapping_context = array() ) {
	$member_id = (int) $member_id;

	$account = memberful_api_member( $member_id );

	if ( is_wp_error( $account ) ) {
		return memberful_wp_record_error(array(
			'caller' => 'memberful_wp_sync_member_from_memberful',
			'error'  => $account->get_error_messages()
		));
	}

	return memberful_wp_sync_member_account( $account, $mapping_context );
}

function memberful_wp_sync_member_account( $account, $mapping_context ) {
	$mapper = new Memberful_User_Map();

	$user = $mapper->map( $account->member, $mapping_context );

	Memberful_Wp_User_Downloads::sync($user->ID, $account->products);
	Memberful_Wp_User_Subscriptions::sync($user->ID, $account->subscriptions);

	Memberful_Wp_User_Role_Decision::ensure_user_role_is_correct( $user );

	return $user;
}
