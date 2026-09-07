<?php

class Memberful_User_Sync_Lock {

  private $lock_timeout = 0;
  private $member_id;

  public function __construct($member_id, $lock_timeout = null) {
    $this->member_id = $member_id;

    if ($lock_timeout) {
      $this->lock_timeout = $lock_timeout;
    }
  }

  public function acquire() {
    global $wpdb;

    $statement = $wpdb->prepare("SELECT GET_LOCK(%s,%d) AS acquired", $this->lock_identifier(), $this->lock_timeout);
    $lock_acquired = $wpdb->get_row($statement)->acquired;

    return $lock_acquired === "1";
  }

  public function release() {
    global $wpdb;

    $statement = $wpdb->prepare("SELECT RELEASE_LOCK(%s)", $this->lock_identifier());
    $wpdb->query($statement);
  }

  private function lock_identifier() {
    $id = $this->member_id;

    return "memberful-member-mapping-$id";
  }
}
