<?php

/**
 * Maps a Memberful user to a WordPress user.
 *
 * If the WordPress user does not exist then they are created from the member
 * details provided.
 *
 */
class Memberful_User_Map
{
	static public function table()
	{
		global $wpdb;

		return $wpdb->prefix.'memberful_mapping';
	}

	/**
	 * Takes a set of Memberful member details and tries to associate it with the
	 * WordPress user account.
	 *
	 * @param StdObject $details       Details about the member
	 * @return WP_User
	 */
	public function map($user, array $mapping = array())
	{
		$user = $this->find_user($member);

		// Mapping of WordPress => Memberful keys
		$mapping = array(
			'user_email'    => 'email',
			'user_login'    => 'username',
			'display_name'  => 'full_name',
			'user_nicename' => 'username',
			'nickname'      => 'full_name',
			'first_name'    => 'first_name',
			'last_name'     => 'last_name'
		);

		$user_data = array();
		$unmapped_user = $user === NULL;

		if($unmapped_user) {
			$this->reserve_mapping($member);

			$user_data['user_pass'] = wp_generate_password();
			$user_data['show_admin_bar_frontend'] = FALSE;
		} else {
			$data['ID'] = $user->ID;

			if ( empty( $user->exact_match ) ) {
				$mapping['wp_user_id'] = $user->ID;
			}
		}

		foreach ($mapping as $key => $value) {
			$user_data[$key] = $member->$value;
		}

		$user_id = wp_insert_user($data);

		if ( $unmapped_user )
			$mapping['wp_user_id'] = $user_id;

		if ( ! empty($mapping) )
			$this->update_mapping($member->id, $mapping);

		return get_userdata($user_id);
	}

	private function find_user($member) {
		global $wpdb;

		$sql = 
			'SELECT `wp_users`.*, (`mem`.`member_id` = %d) AS `exact_match` '.
			'FROM `'.self::table().'` AS `mem`'.
			'FULL OUTER JOIN `'.$wpdb->users.'` AS `wp_users` ON (`mem`.`wp_user_id` = `wp_users`.`ID`) '.
			'WHERE `mem`.`member_id` = %d OR `wp_users`.`user_email` = %s '.
			'ORDER BY `exact_match` DESC';
			
		$query = $wpdb->prepare(
			$sql,
			$member->id,
			$member->id,
			$member->email
		);

		return $wpdb->get_row($query);
	}

	/**
	 * Update information about the user in the mapping table
	 *
	 */
	public function update_mapping($member_id, array $pairs) {
		global $wpdb;

		$data  = array();

		$update = 'UPDATE `'.self::table().'` SET ';

		foreach ( $pairs as $key => $value ) {
			$update .= '`'.$key.'` = %s, ';
			$data[]  = $value;
		}

		$update = substr($update, 0, -2);

		$update .= ' WHERE `member_id` = %d';
		$data[] = $member_id;

		$wpdb->query($wpdb->prepare($update, $data));
	}

	/**
	 * Reserves a mapping for the member
	 *
	 * We do this to prevent problems where webhooks and oauth login attempt to create
	 * a user simultaneously.
	 */
	private function reserve_mapping($member) {
		global $wpdb;

		$insert = 'INSERT INTO `'.self::table().'` (`member_id`) VALUES (%d)';

		$result = $wpdb->query($wpdb->prepare($insert, array($member->id)));

		if ( is_wp_error( $result ) ) {
			var_dump( $result );
			die();
		}
	}
}
