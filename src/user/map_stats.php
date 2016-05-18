<?php

class Memberful_User_Map_Stats {

  protected $table;

  public function __construct($table_name) {
    $this->table = $table_name;
  }

  public function unmapped_users() {
    global $wpdb;

    $query = 'SELECT `mapping`.`wp_user_id` FROM '.$this->table.' AS `mapping`';

    $mapped_users = array_filter( $wpdb->get_col($query) );

    $query = 'SELECT users.* FROM '.$wpdb->users.' AS `users`';

    if( !empty( $mapped_users ) )
      $query .= ' WHERE ID NOT IN('.implode(',', $mapped_users).')';

    return $wpdb->get_results($query);
  }

  public function count_mapping_records() {
    global $wpdb;

    return $wpdb->get_var('SELECT COUNT(*) FROM '.$this->table);
  }

  public function mapping_records() {
    global $wpdb;

    return $wpdb->get_results('SELECT * FROM '.$this->table);
  }
}
