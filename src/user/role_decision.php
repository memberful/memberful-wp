<?php

class Memberful_Wp_User_Role_Decision {
	public static function ensure_user_role_is_correct( $user ) {
		$decision = new Memberful_Wp_User_Role_Decision();

		$new_role = $decision->role_for_user(
			$user->role,
			memberful_wp_user_plans_subscribed_to( $user->ID )
		);

		$user->set_role( $new_role );
	}

	public function role_for_user($current_role, $current_subscriptions) {
		$is_active     = ! empty( $current_subscriptions );
		$active_role   = get_option( 'memberful_role_active_customer', 'subscriber' );
		$inactive_role = get_option( 'memberful_role_inactive_customer', 'subscriber' );

		$roles_memberful_is_allowed_to_change_from = array(
			$active_role,
			$inactive_role,
			'subscriber'
		);

		if ( ! in_array( $current_role, $roles_memberful_is_allowed_to_change_from ) ) {
			return $current_role;
		}

		return $is_active ? $active_role : $inactive_role;
	}
}
