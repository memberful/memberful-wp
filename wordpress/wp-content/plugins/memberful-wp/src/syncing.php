<?php
require_once MEMBERFUL_DIR.'/src/user/downloads.php';
require_once MEMBERFUL_DIR.'/src/user/feeds.php';
require_once MEMBERFUL_DIR.'/src/user/subscriptions.php';
require_once MEMBERFUL_DIR.'/src/user/sync_lock.php';

function memberful_wp_sync_member_from_memberful( $member_id, $mapping_context = array() ) {
  $member_id = (int) $member_id;

  $account = memberful_api_member( $member_id );

  if ( is_wp_error( $account ) ) {
    memberful_wp_record_error(array(
      'error'  => $account->get_error_messages()
    ));

    return $account;
  }

  return memberful_wp_sync_member_account( $account, $mapping_context );
}

/**
 * @param $account
 * @param $mapping_context
 * @return WP_User
 */
function memberful_wp_sync_member_account( $account, $mapping_context, $lock_timeout = null ) {
  $user = memberful_wp_sync_user( $account, $mapping_context, $lock_timeout );

  if ( is_wp_error( $user ) ) {
    memberful_wp_record_wp_error( $user );

    if ( $user->get_error_code() === "duplicate_user_for_member" ) {
      $user = memberful_wp_sync_user( $account, $mapping_context, $lock_timeout );
      return $user;
    }
  }

  return $user;
}

function memberful_wp_sync_user( $account, $mapping_context, $lock_timeout ) {
  global $wpdb;
  $mapper = new Memberful_User_Map();
  $member = $account->member;
  $lock   = new Memberful_User_Sync_Lock($member->id, $lock_timeout);

  if (!$lock->acquire()) {
    if ($lock_timeout) {
      return new WP_Error("memberful_lock_timeout", "The lock has timed out.", array("member_id" => $member->id));
    } else {
      return null;
    }
  }

  $wpdb->query( "START TRANSACTION" );
  $user = $mapper->map( $member, $mapping_context );

  if ( ! is_wp_error( $user ) ) {
    if ( isset( $member->deleted ) ) {
      if ( memberful_is_safe_to_delete( $user ) ) {
        wp_delete_user( $user->ID );
        (new Memberful_User_Mapping_Repository())->delete_mapping( $user->ID );
      } else {
        Memberful_Wp_User_Downloads::sync($user->ID, array());
        Memberful_Wp_User_Feeds::sync($user->ID, array());
        Memberful_Wp_User_Subscriptions::sync($user->ID, array());
        Memberful_Wp_User_Role_Decision::ensure_user_role_is_correct( $user );
      }
    } else {
      Memberful_Wp_User_Downloads::sync($user->ID, $account->products);
      Memberful_Wp_User_Feeds::sync($user->ID, $account->feeds);
      Memberful_Wp_User_Subscriptions::sync($user->ID, $account->subscriptions);
      Memberful_Wp_User_Role_Decision::ensure_user_role_is_correct( $user );
    }
    $wpdb->query( "COMMIT" );
  } else {
    $wpdb->query( "ROLLBACK" );
  }

  $lock->release();

  return $user;
}

function memberful_is_safe_to_delete( $user ) {
  if ( memberful_is_admin( $user ) )
    return false;

  if ( memberful_has_content( $user ) ) {
    return false;
  }

  return true;
}

function memberful_is_admin( $user ) {
  return $user->has_cap( "delete_users" );
}

function memberful_has_content( $user ) {
  $wp_query = new WP_Query( array( "post_type" => "any", "author" => $user->ID ) );

  if ( $wp_query->have_posts() )
    return true;

  $comments_query = new WP_Comment_Query();
  $comments = $comments_query->query( array( "user_id" => $user->ID ) );

  if ( !empty( $comments ) )
    return true;

  return false;
}
