<?php

/**
 * Maps a Memberful user to a WordPress user.
 *
 * If the WordPress user does not exist then they are created from the member
 * details provided.
 *
 */
class Memberful_User_Map {
	static public function table() {
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
	public function map( $member, array $mapping = array() ) {
		list( $user_id, $user_mapping_exists ) = $this->find_user( $member );

		$user_exists = $user_id !== NULL;

		// We initially reserve a mapping to prevent other processes
		// from trying to map the user at the same time as us
		if ( ! $user_mapping_exists ) {
			$this->reserve_mapping(
				$member,
				( $user_exists ? array( 'wp_user_id' => $user_id ) : array() )
			);
		}

		// Mapping of WordPress => Memberful keys
		$field_map = array(
			'user_email'    => 'email',
			'user_login'    => 'username',
			'display_name'  => 'full_name',
			'user_nicename' => 'username',
			'nickname'      => 'full_name',
			'first_name'    => 'first_name',
			'last_name'     => 'last_name'
		);

		$user_data = array();

		if ( ! $user_exists ) {
			$user_data['user_pass'] = wp_generate_password();
			$user_data['show_admin_bar_frontend'] = FALSE;
		} else {
			$user_data['ID'] = $user_id;
		}

		foreach ( $field_map as $key => $value ) {
			$user_data[$key] = $member->$value;
		}

		$user_id = wp_insert_user( $user_data );

		if ( ! $user_exists )
			$mapping['wp_user_id'] = $user_id;

		$mapping['last_sync_at'] = time();

		$this->update_mapping( $member, $mapping );

		return get_userdata( $user_id );
	}

	/**
	 * Attempts to find the ID of the user who the specified member maps to in
	 * the wordpress install
	 *
	 * If no such user exists then NULL is returned
	 *
	 * @param  StdClass $member The member to map from
	 * @return array            First element is the id of the user, the second is a bool indicating
	 *                          whether we found this user in the map, or whether we found them by their email address
	 */
	private function find_user( $member ) {
		global $wpdb;

		$user_id        = NULL;
		$mapping_exists = FALSE;

		$sql =
			'SELECT `wp_users`.`ID`, `mem`.`member_id` '.
			'FROM `'.self::table().'` AS `mem`'.
			'LEFT OUTER JOIN `'.$wpdb->users.'` AS `wp_users` ON (`mem`.`wp_user_id` = `wp_users`.`ID`) '.
			'WHERE `mem`.`member_id` = %d';

		$mapping = $wpdb->get_row( $wpdb->prepare( $sql, $member->id ) );

		if ( ! empty( $mapping ) ) {
			$mapping_exists = TRUE;
			$user_id = $mapping->ID;
		} else {
			$user = get_user_by( 'email', $member->email );

			$user_id = $user === FALSE ? NULL : $user->ID;
		}

		return array( $user_id, $mapping_exists );
	}

	/**
	 * Update information about the user in the mapping table
	 *
	 */
	public function update_mapping( $member, array $pairs ) {
		global $wpdb;

		$data    = array();
		$columns = $this->restrict_columns( array_keys( $pairs ) );

		$update = 'UPDATE `'.self::table().'` SET ';

		foreach ( $columns as $column ) {
			$update .= '`'.$column.'` = %s, ';
			$data[]  = $pairs[$column];
		}

		$update = substr( $update, 0, -2 );

		$update .= ' WHERE `member_id` = %d';
		$data[] = $member->id;

		$wpdb->query( $wpdb->prepare( $update, $data ) );
	}

	/**
	 * Reserves a mapping for the member
	 *
	 * We do this to prevent problems where webhooks and oauth login attempt to create
	 * a user simultaneously.
	 */
	private function reserve_mapping( $member, array $params = array() ) {
		global $wpdb;

		$columns     = array_merge( array( 'member_id' ), array_keys( $params ) );
		$columns     = $this->restrict_columns( $columns );
		$column_list = '`'.implode( '`, `', $columns ).'`';

		$values         = array( $member->id );
		$value_sub_list = array( '%d' );

		foreach ( $columns as $column ) {
			$values[]         = $params[$column];
			$value_sub_list[] = '%s';
		}

		$value_list = implode( ', ', $value_sub_list );

		$insert = 'INSERT INTO `'.self::table().'` ( '.$column_list.' ) VALUES ( '.$value_list.' )';

		$result = $wpdb->query( $wpdb->prepare( $insert, $values ) );

		if ( is_wp_error( $result ) ) {
			echo 'Could not reserve mapping:';
			var_dump( $result );
			die();
		}
	}

	/**
	 * Restricts the set of columns that the mapper can change
	 *
	 * @param array $columns Set of columns that
	 * @return array
	 */
	private function restrict_columns( array $columns ) {
		return array_intersect(
			$columns,
			array( 'member_id' ,'wp_user_id', 'refresh_token', 'last_sync_at' )
		);
	}
}
