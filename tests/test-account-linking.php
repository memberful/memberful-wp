<?php
/**
 * Tests for linking a Memberful member to an existing WordPress user.
 *
 * @package Memberful
 */

/**
 * Class Tests_Account_Linking
 */
class Tests_Account_Linking extends WP_UnitTestCase {

  /**
   * The email address the member and the existing user share.
   */
  const EMAIL = 'owner@example.com';

  /**
   * The member's id in Memberful.
   */
  const MEMBER_ID = 990001;

  /**
   * A member the way Memberful's API describes them.
   *
   * @return stdClass
   */
  private function member() {
    return (object) array(
      'id'         => self::MEMBER_ID,
      'email'      => self::EMAIL,
      'username'   => 'member',
      'first_name' => 'Member',
      'last_name'  => 'Example',
      'full_name'  => 'Member Example',
    );
  }

  /**
   * Create a user who owns the member's email address.
   *
   * @param string $role The role to give them.
   * @return WP_User
   */
  private function user_with_members_email( $role ) {
    $user = array(
      'role'       => $role,
      'user_email' => self::EMAIL,
    );

    return $this->factory->user->create_and_get( $user );
  }

  /**
   * The context a sync carries once the user has confirmed the link with their password.
   *
   * @param WP_User $user The user who confirmed.
   * @return array
   */
  private function confirmed_by( $user ) {
    return array(
      'user_verified_they_want_to_sync_accounts'  => true,
      'id_of_user_who_has_verified_the_sync_link' => $user->ID,
    );
  }

  /**
   * Link the member to the user, as an earlier sync would have.
   *
   * @param WP_User $user The user to link.
   */
  private function link_member_to( $user ) {
    ( new Memberful_User_Mapping_Repository() )->create_mapping( $user, $this->member(), array() );
  }

  /**
   * An administrator is never offered a link to a member who shares their email.
   */
  public function test_refuses_to_link_an_administrator() {
    $this->user_with_members_email( 'administrator' );

    $result = ( new Memberful_User_Map() )->map( $this->member() );

    $this->assertWPError( $result );
    $this->assertSame( 'user_is_privileged', $result->get_error_code() );
  }

  /**
   * An administrator's password doesn't confirm a link to a member who shares their email.
   */
  public function test_refuses_to_link_an_administrator_who_confirmed_with_their_password() {
    $user = $this->user_with_members_email( 'administrator' );

    $result = ( new Memberful_User_Map() )->map( $this->member(), $this->confirmed_by( $user ) );

    $this->assertWPError( $result );
    $this->assertSame( 'user_is_privileged', $result->get_error_code() );
    $this->assertNull( Memberful_User_Mapping_Repository::find_by_member_id( self::MEMBER_ID ) );
  }

  /**
   * A subscriber who shares the member's email is still asked to confirm the link.
   */
  public function test_asks_a_subscriber_to_confirm_the_link() {
    $this->user_with_members_email( 'subscriber' );

    $result = ( new Memberful_User_Map() )->map( $this->member() );

    $this->assertWPError( $result );
    $this->assertSame( 'user_already_exists', $result->get_error_code() );
  }

  /**
   * A subscriber who confirmed with their password is linked to the member.
   */
  public function test_links_a_subscriber_who_confirmed_with_their_password() {
    $user = $this->user_with_members_email( 'subscriber' );

    $result = ( new Memberful_User_Map() )->map( $this->member(), $this->confirmed_by( $user ) );

    $this->assertSame( $user->ID, $result->ID );
  }

  /**
   * An administrator linked before this rule existed keeps signing in through Memberful.
   */
  public function test_keeps_an_existing_link_to_an_administrator() {
    $user = $this->user_with_members_email( 'administrator' );
    $this->link_member_to( $user );

    $result = ( new Memberful_User_Map() )->map( $this->member() );

    $this->assertSame( $user->ID, $result->ID );
  }

  /**
   * A linked administrator keeps their email address when the member changes theirs.
   */
  public function test_keeps_a_linked_administrators_email_when_the_member_changes_theirs() {
    $user = $this->user_with_members_email( 'administrator' );
    $this->link_member_to( $user );

    $member        = $this->member();
    $member->email = 'someone-else@example.com';
    ( new Memberful_User_Map() )->map( $member );

    $this->assertSame( self::EMAIL, get_user_by( 'id', $user->ID )->user_email );
  }

  /**
   * A linked administrator keeps their name when the member changes theirs.
   */
  public function test_keeps_a_linked_administrators_name_when_the_member_changes_theirs() {
    update_option( 'memberful_auto_sync_display_names', true );

    $user = $this->user_with_members_email( 'administrator' );
    $this->link_member_to( $user );
    update_user_meta( $user->ID, 'first_name', 'Site' );
    update_user_meta( $user->ID, 'last_name', 'Owner' );

    $member             = $this->member();
    $member->first_name = 'Someone';
    $member->last_name  = 'Else';
    $member->full_name  = 'Someone Else';
    ( new Memberful_User_Map() )->map( $member );

    $synced_user = get_user_by( 'id', $user->ID );

    $this->assertSame( 'Site', $synced_user->first_name );
    $this->assertSame( 'Owner', $synced_user->last_name );
    $this->assertSame( $user->display_name, $synced_user->display_name );
  }

  /**
   * A linked subscriber's email address follows the member's.
   */
  public function test_updates_a_linked_subscribers_email_when_the_member_changes_theirs() {
    $user = $this->user_with_members_email( 'subscriber' );
    $this->link_member_to( $user );

    $member        = $this->member();
    $member->email = 'new-address@example.com';
    ( new Memberful_User_Map() )->map( $member );

    $this->assertSame( 'new-address@example.com', get_user_by( 'id', $user->ID )->user_email );
  }
}
