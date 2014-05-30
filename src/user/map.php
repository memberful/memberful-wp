<?php
require_once( ABSPATH.'wp-admin/includes/user.php' );


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

	static public function fetch_ids_of_members_that_need_syncing() {
		global $wpdb;

		$sync_cut_off_point = 3600 * 24 * 7;

		return $wpdb->get_col(
			"SELECT member_id FROM ".self::table()." WHERE last_sync_at < ".(time()-$sync_cut_off_point)." AND wp_user_id > 0 ORDER BY last_sync_at ASC LIMIT 50"
		);
	}

	static public function fetch_user_ids_of_all_mapped_members() {
		global $wpdb;

		return $wpdb->get_col(
			"SELECT wp_user_id FROM ".self::table()." WHERE wp_user_id > 0;"
		);
	}

	/**
	 * Takes a set of Memberful member details and tries to associate it with the
	 * WordPress user account.
	 *
	 * @param StdObject $details	   Details about the member
	 * @return WP_User
	 */
	public function map( $member, array $context = array() ) {
		extract($this->find_user_member_is_mapped_to( $member ));

		$existing_user_with_members_email = get_user_by( 'email', $member->email );

		if ( $existing_user_with_members_email !== FALSE && $user_member_is_mapped_to === FALSE ) {
			if ( empty($context['user_verified_they_want_to_sync_accounts']) || $context['id_of_user_who_has_verified_the_sync_link'] !== (int) $existing_user_with_members_email->ID ) {
				return new WP_Error(
					'user_already_exists',
					"A user exists in WordPress with the same email address as a Memberful member, but we're not sure they belong to the same user",
					array(
						'member'        => $member,
						'existing_user' => $existing_user_with_members_email,
						'context'       => $context,
					)
				);
			}
		}

		if ( $existing_user_with_members_email !== FALSE && $user_member_is_mapped_to !== FALSE ) {
			// Someone is attempting to change their email address to another user's,
			// potentially an admin's. WordPress will actually allow multiple users
			// with the same email address, so we'd better be a responsible citizen
			if ( $user_member_is_mapped_to->ID !== $existing_user_with_members_email->ID ) {
				return new WP_Error(
					'user_is_mimicing_another_user',
					"The member is trying to change their email address to that of a different user in WordPress",
					array(
						'member'          => $member,
						'mapped_user'     => $user_member_is_mapped_to,
						'user_with_email' => $existing_user_with_members_email,
						'context'         => $context,
					)
				);
			}
		}

		$user_data = array();

		if ( $user_member_is_mapped_to !== FALSE ) {
			$user_data['ID'] = $user_member_is_mapped_to->ID;
		} elseif ( $existing_user_with_members_email !== FALSE ) {
			$user_data['ID'] = $existing_user_with_members_email->ID;
		} else {
			$user_data['user_pass'] = wp_generate_password();
			$user_data['show_admin_bar_frontend'] = FALSE;
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

		foreach ( $field_map as $key => $value ) {
			$user_data[$key] = $member->$value;
		}

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			$data = $user_id->get_error_data();
			$data['member']    = $member;
			$data['user_data'] = $user_data;
			$user_id->add_data($data);

			return $user_id;
		}

		$user_member_is_mapped_to = get_userdata( $user_id );

		$context['last_sync_at'] = time();

		$outcome_of_mapping = $this->ensure_mapping_is_correct( $mapping_exists, $user_member_is_mapped_to, $member, $context );

		if ( is_wp_error( $outcome_of_mapping ) ) {
			if ( $outcome_of_mapping->get_error_code() === "duplicate_user_for_member" ) {
				// We only record this error as others will be passed up and recorded
				// by something else, whereas here we're working around the error.
				memberful_wp_record_wp_error( $outcome_of_mapping );

				wp_delete_user( $user_id );

				$error_data = $outcome_of_mapping->get_error_data();

				return $error_data['canonical_user'];
			} else {
				return $outcome_of_mapping;
			}
		}

		return $user_member_is_mapped_to;
	}

	/**
	 * Attempts to find the ID of the user who the specified member maps to in
	 * the wordpress install
	 *
	 * If no such user exists then NULL is returned
	 *
	 * @param  StdClass $member The member to map from
	 * @return array			First element is the id of the user, the second is a bool indicating
	 *						  whether we found this user in the map, or whether we found them by their email address
	 */
	private function find_user_member_is_mapped_to( $member ) {
		global $wpdb;

		$user_member_is_mapped_to = FALSE;
		$mapping_exists           = FALSE;

		$sql =
			'SELECT `mem`.`wp_user_id`, `mem`.`member_id` '.
			'FROM `'.self::table().'` AS `mem`'.
			'WHERE `mem`.`member_id` = %d';

		$mapping = $wpdb->get_row( $wpdb->prepare( $sql, $member->id ) );

		if ( ! empty( $mapping ) ) {
			$mapping_exists           = TRUE;
			$user_member_is_mapped_to = get_user_by( 'id', $mapping->wp_user_id );
		}

		return compact( "mapping_exists", "user_member_is_mapped_to" );
	}

	private function ensure_mapping_is_correct( $mapping_existed_before, $wp_user, $member, array $context ) {
		return $mapping_existed_before
			? $this->update_mapping( $wp_user, $member, $context )
			: $this->create_mapping( $wp_user, $member, $context );
	}

	/**
	 * Update information about the user in the mapping table
	 *
	 */
	public function update_mapping( $wp_user, $member, array $context ) {
		global $wpdb;

		$data	= array( $wp_user->ID );
		$columns = $this->restrict_columns( array_keys( $context ) );

		$update = 'UPDATE `'.self::table().'` SET `wp_user_id` = %d, ';

		foreach ( $columns as $column ) {
			$update .= '`'.$column.'` = %s, ';
			$data[]  = $context[$column];
		}

		$update = substr( $update, 0, -2 );

		$update .= ' WHERE `member_id` = %d';
		$data[] = $member->id;

		$query = $wpdb->prepare( $update, $data );

		$result = $wpdb->query( $query );

		if ( $result === FALSE ) {
			return new WP_Error(
				"database_error",
				$wpdb->last_error,
				array(
					'query'   => $query,
					'wp_user' => $wp_user,
					'member'  => $member,
					'context' => $context
				)
			);
		}

		return $wp_user->ID;
	}

	/**
	 * Creates a mapping of Memberful member to WordPress user
	 */
	private function create_mapping( $wp_user, $member, array $context ) {
		global $wpdb;

		$columns     = array( 'wp_user_id', 'member_id' );
		$columns     = array_merge( $columns, array_keys( $context ) );
		$columns     = $this->restrict_columns( $columns );
		$column_list = '`'.implode( '`, `', $columns ).'`';

		$values      = array( $wp_user->ID, $member->id );
		$value_sub_list = array( '%d', '%d' );

		foreach ( $columns as $column ) {
			if ( $column === 'member_id' || $column === 'wp_user_id' )
				continue;

			$values[] = $context[$column];
			$value_sub_list[] = '%s';
		}

		$value_list = implode( ', ', $value_sub_list );

		$insert = 'INSERT INTO `'.self::table().'` ( '.$column_list.' ) VALUES ( '.$value_list.' )';

		$previous_error_state = $wpdb->hide_errors();

		$query  = $wpdb->prepare( $insert, $values );

		$result = $wpdb->query( $query );

		if ( $result === FALSE ) {
			// Race condition, some other process has reserved the mapping
			if ( strpos( strtolower( $wpdb->last_error ), 'duplicate entry' ) !== FALSE ) {
				$real_mapping = $this->find_user_member_is_mapped_to( $member );

				return new WP_Error(
					"duplicate_user_for_member",
					"Some other process created the user and mapping before we could. Use the earlier version",
					array(
						'canonical_user' => $real_mapping['user_member_is_mapped_to'],
						'member'         => $member,
						'context'        => $context,
						'our_user'       => $wp_user,
					)
				);
			} else {
				return new WP_Error(
					"database_error",
					$wpdb->last_error,
					array(
						'query'   => $query,
						'wp_user' => $wp_user,
						'member'  => $member,
						'context' => $context
					)
				);
			}
		}

		return $wp_user->ID;
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
