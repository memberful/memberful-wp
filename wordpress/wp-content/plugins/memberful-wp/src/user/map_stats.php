<?php

class Memberful_User_Map_Stats {

  protected $table;

  public function __construct($table_name) {
    $this->table = $table_name;
  }

  public function count_mapping_records() {
    global $wpdb;

    return $wpdb->get_var('SELECT COUNT(*) FROM '.$this->table);
  }

  // Joins against wp_users (and counts distinct ids) so orphaned and duplicate
  // mappings don't inflate the figure beyond the number of real mapped users.
  public function count_mapped_users() {
    global $wpdb;

    return $wpdb->get_var(
      'SELECT COUNT(DISTINCT `mapping`.`wp_user_id`) FROM '.$this->table.' AS `mapping` '.
      'INNER JOIN '.$wpdb->users.' AS `users` ON `users`.`ID` = `mapping`.`wp_user_id`'
    );
  }
}
