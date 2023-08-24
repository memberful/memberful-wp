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

  private $_repository;

  /**
   * Takes a set of Memberful member details and tries to associate it with the
   * WordPress user account.  *
   * @param StdObject $details	   Details about the member
   * @return WP_User
   */
  public function map( $member, array $context = array() ) {
    if (isset($member->deleted)) {
      return $this->map_deleted_member($member);
    }

    $existing_user_with_members_email = get_user_by( 'email', $member->email );

    $mapping_from_member = $this->repository()->find_user_member_is_mapped_to( $member );
    $mapping_from_user   = $this->repository()->find_member_user_is_mapped_to( $existing_user_with_members_email );

    $result_of_precondition_check = $this->run_mapping_preconditions(
      $mapping_from_member,
      $existing_user_with_members_email,
      $mapping_from_user,
      $context
    );

    if ( is_wp_error( $result_of_precondition_check ) ) {
      return $this->add_data_to_wp_error( $result_of_precondition_check, compact( 'member' ) );
    }

    $existing_wp_user               = $mapping_from_member['user'] !== FALSE ? $mapping_from_member['user'] : $existing_user_with_members_email;
    $wp_user_existed_before_request = $existing_wp_user !== FALSE;

    $ensure_user = new Memberful_User_Mapping_Ensure_User( $existing_wp_user, $member );
    $wp_user     = $ensure_user->ensure_present();

    if ( is_wp_error( $wp_user ) ) {
      $this->add_data_to_wp_error( $wp_user, array( 'member' => $member ) );

      return $wp_user;
    }

    $context['last_sync_at'] = time();

    return $this->ensure_mapping_is_correct($mapping_from_member['mapping_exists'], $mapping_from_user['mapping_exists'], $wp_user, $member, $wp_user_existed_before_request, $context);
  }

  private function run_mapping_preconditions($mapping_from_member, $existing_user_with_email, $mapping_from_user, $context) {
    $there_is_already_a_user_with_members_email = $existing_user_with_email !== FALSE;
    $the_member_is_mapped_to_a_user             = $mapping_from_member['user'] !== FALSE;

    if ( $there_is_already_a_user_with_members_email && ! $the_member_is_mapped_to_a_user ) {
      $user_has_not_verified_they_want_to_link_these_accounts = empty($context['user_verified_they_want_to_sync_accounts']) || $context['id_of_user_who_has_verified_the_sync_link'] !== (int) $existing_user_with_email->ID;

      if ( $user_has_not_verified_they_want_to_link_these_accounts ) {
        return new WP_Error(
          'user_already_exists',
          "A user exists in WordPress with the same email address as a Memberful member, but we're not sure they belong to the same user",
          array(
            'existing_user' => $existing_user_with_email,
            'context'       => $context,
          )
        );
      }
    }

    if ( $there_is_already_a_user_with_members_email && $the_member_is_mapped_to_a_user ) {
      $user_member_is_mapped_to_is_different_from_user_with_same_email = $mapping_from_member['user']->ID !== $existing_user_with_email->ID;

      // Someone is attempting to change their email address to another user's,
      // potentially an admin's. WordPress will actually allow multiple users
      // with the same email address, so we'd better be a responsible citizen
      if ( $user_member_is_mapped_to_is_different_from_user_with_same_email ) {
        return new WP_Error(
          'user_is_mimicing_another_user',
          "The member is trying to change their email address to that of a different user in WordPress",
          array(
            'mapped_user'     => $mapping_from_member['user'],
            'user_with_email' => $existing_user_with_email,
            'context'         => $context,
          )
        );
      }
    }
  }

  private function ensure_mapping_is_correct( $mapping_from_member_exists, $mapping_from_user_exists, $wp_user, $member, $wp_user_existed_before_request, array $context ) {
    if ( $mapping_from_member_exists ) {
      $method = 'update_mapping_by_member';
    } elseif ( $mapping_from_user_exists ) {
      $method = 'update_mapping_by_user';
    } else {
      $method = 'create_mapping';
    }

    $result = $this->repository()->$method( $wp_user, $member, $context );

    if ( is_wp_error( $result ) ) {
      if ( $result->get_error_code() === "duplicate_user_for_member" && ! $wp_user_existed_before_request ) {
        wp_delete_user( $wp_user->ID );
      }

      return $result;
    } else {
      return $wp_user;
    }
  }

  private function map_deleted_member($member) {
    $mapping_from_member = $this->repository()->find_user_member_is_mapped_to($member);

    if ($mapping_from_member['mapping_exists']) {
      return $mapping_from_member['user'];
    } else {
      return $this->add_data_to_wp_error(
        new WP_Error(
          'user_does_not_exist',
          'The Wordpress user with the given member_id does not exist',
          array('member_id' => $member->id)
        ),
        compact('member')
      );
    }
  }

  private function add_data_to_wp_error( WP_Error $error, array $data ) {
    $error_data = (array) $error->get_error_data();

    $error->add_data( array_merge( $error_data, $data ) );

    return $error;
  }

  private function repository() {
    if ( empty( $this->_repository ) ) {
      $this->_repository = new Memberful_User_Mapping_Repository();
    }

    return $this->_repository;
  }
}

class Memberful_User_Mapping_Ensure_User {

  private $wp_user;
  private $member;

  public function __construct( $wp_user, $member ) {
    $this->wp_user     = $wp_user;
    $this->user_exists = $wp_user !== FALSE;
    $this->member      = $member;
  }

  public function ensure_present() {
    $user_id = $this->user_exists ? $this->update_user() : $this->create_user();

    if ( is_wp_error( $user_id ) ) {
      $user_id->add_data( (array) $user_id->get_error_data() );

      return $user_id;
    }

    $this->wp_user = get_userdata( $user_id );

    $this->update_user_meta();

    return $this->wp_user;
  }

  private function update_user() {
    $user_data = $this->fields_that_always_sync_from_memberful();
    $sync_display_name = get_option( 'memberful_auto_sync_display_names' );

    $user_data['ID']            = $this->wp_user->ID;
    $user_data['user_login']    = $this->wp_user->user_login;
    $user_data['user_nicename'] = $this->wp_user->user_nicename;
    $user_data['nickname']      = $this->wp_user->nickname;
    $user_data['display_name']  = $this->wp_user->display_name;
    $user_data['display_name']  = $sync_display_name ? $this->member->full_name : $this->wp_user->display_name;

    return wp_update_user( apply_filters( 'memberful.map_user.update', $user_data, $this->wp_user, $this->member ) );
  }

  private function create_user() {
    $user_data = $this->fields_that_always_sync_from_memberful();

    $user_data['user_pass']               = wp_generate_password();
    $user_data['show_admin_bar_front']    = FALSE;
    $user_data['user_login']              = $this->member->username;
    $user_data['user_nicename']           = $this->member->username;
    $user_data['nickname']                = $this->member->full_name;
    $user_data['display_name']            = $this->member->full_name;

    return wp_insert_user( apply_filters( 'memberful.map_user.create', $user_data, $this->member ) );
  }

  private function fields_that_always_sync_from_memberful() {
    return array(
      'user_email' => $this->member->email,
      'first_name' => $this->member->first_name,
      'last_name'  => $this->member->last_name
    );
  }

  private function update_user_meta() {
    if ( ! empty( $this->member->custom_field ) ) {
      update_user_meta( $this->wp_user->ID, MEMBERFUL_WP_SINGLE_CUSTOM_FIELD_META_KEY, $this->member->custom_field );
    }
  }

}

class Memberful_User_Mapping_Repository {

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
   * Attempts to find the ID of the user who the specified member maps to in
   * the wordpress install
   *
   * If no such user exists then NULL is returned
   *
   * @param  StdClass $member The member to map from
   * @return array			First element is the id of the user, the second is a bool indicating
   *						  whether we found this user in the map, or whether we found them by their email address
   */
  public function find_user_member_is_mapped_to( $member ) {
    global $wpdb;

    $user_member_is_mapped_to = FALSE;
    $mapping_exists           = FALSE;

    $sql =
      'SELECT `mem`.`wp_user_id`, `mem`.`member_id` '.
      'FROM `'.Memberful_User_Mapping_Repository::table().'` AS `mem`'.
      'WHERE `mem`.`member_id` = %d';

    $mapping = $wpdb->get_row( $wpdb->prepare( $sql, $member->id ) );

    if ( ! empty( $mapping ) ) {
      $mapping_exists           = TRUE;
      $user_member_is_mapped_to = get_user_by( 'id', $mapping->wp_user_id );
    }

    return array( 'mapping_exists' => $mapping_exists, 'user' => $user_member_is_mapped_to, 'member' => $member );
  }

  public function find_member_user_is_mapped_to( $user ) {
    global $wpdb;

    $id_of_member_user_is_mapped_to = FALSE;
    $mapping_exists                 = FALSE;

    if ( $user !== FALSE ) {
      $sql =
        'SELECT `mem`.`member_id` '.
        'FROM `'.Memberful_User_Mapping_Repository::table().'` AS `mem` '.
        'WHERE `mem`.`wp_user_id` = %d';

      $mapping = $wpdb->get_row( $wpdb->prepare( $sql, $user->ID ) );

      if ( ! empty( $mapping ) ) {
        $mapping_exists = TRUE;
        $id_of_member_user_is_mapped_to = $mapping->member_id;
      }
    }

    return compact( 'mapping_exists', 'id_of_member_user_is_mapped_to' );
  }

  public function update_mapping_by_member( $wp_user, $member, array $context ) {
    return $this->update_mapping( $wp_user, $member, $context, array( 'member_id' => $member->id ) );
  }

  public function update_mapping_by_user( $wp_user, $member, array $context ) {
    return $this->update_mapping( $wp_user, $member, $context, array( 'wp_user_id' => $wp_user->ID ) );
  }

  /**
   * Update information about the user in the mapping table
   *
   */
  public function update_mapping( $wp_user, $member, array $context, array $constraints ) {
    global $wpdb;

    if ( empty( $constraints ) ) {
      return new WP_Error(
        "empty_update_constraints",
        "A set of constraints must be provided when updating a mapping",
        array(
          'user'        => $wp_user,
          'member'      => $member,
          'context'     => $context,
          'constraints' => $constraints
        )
      );
    }

    $data	= array( $wp_user->ID, $member->id );
    $columns = $this->restrict_columns( array_keys( $context ) );

    $update = 'UPDATE `'.Memberful_User_Mapping_Repository::table().'` SET `wp_user_id` = %d, `member_id` = %d, ';

    foreach ( $columns as $column ) {
      $update .= '`'.$column.'` = %s, ';
      $data[]  = $context[$column];
    }

    $update = substr( $update, 0, -2 ).' WHERE ';

    foreach( $constraints as $key => $constraint ) {
      $update .= '`'.$key.'` = %d AND ';
      $data[]  = $constraint;
    }

    $update = substr( $update, 0, -4 ).' LIMIT 1';

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
  public function create_mapping( $wp_user, $member, array $context ) {
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

    $insert = 'INSERT INTO `'.Memberful_User_Mapping_Repository::table().'` ( '.$column_list.' ) VALUES ( '.$value_list.' )';

    $previous_error_state = $wpdb->hide_errors();

    $query  = $wpdb->prepare( $insert, $values );

    $result = $wpdb->query( $query );

    if ( $result === FALSE ) {
      // Race condition, some other process has reserved the mapping
      if ( strpos( strtolower( $wpdb->last_error ), 'duplicate entry' ) !== FALSE ) {
        return new WP_Error(
          "duplicate_user_for_member",
          "Some other process created the user and mapping before we could. Use the earlier version",
          array(
            'context' => $context,
            'member'  => $member,
            'wp_user' => $wp_user
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

  public function delete_mapping( $user_id ) {
    global $wpdb;

    return $wpdb->delete(self::table(), array( "wp_user_id" => $user_id ) );
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
