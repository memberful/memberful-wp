<?php
namespace Ari\Entities;

use Ari\Utils\Object as Object_Helper;

class Entity {
    protected $db_tbl = '';

    protected $db_keys = array();

    protected $db = null;

    protected $db_fields = null;

    protected $is_single_key = true;

    protected $bool_fields = array();

    function __construct( $tbl, $keys, $db, $with_prefix = true ) {
        if ( $with_prefix )
            $tbl = $db->prefix . $tbl;

        if ( ! is_array( $keys ) )
            $keys = array( $keys );

        $this->db_tbl = $tbl;
        $this->db_keys = $keys;
        $this->db = $db;
        $this->is_single_key = (count( $keys ) == 1);
    }

    public function get_db_fields() {
        if ( ! is_null( $this->db_fields ) )
            return $this->db_fields;

        $props = Object_Helper::get_default_properties( $this );
        $db_fields = array();

        foreach ( $props as $k => $v ) {
            if ( ! is_object( $v ) && ! is_array( $v ) )
                $db_fields[] = $k;
        }

        $this->db_fields = $db_fields;

        return $this->db_fields;
    }

    public function to_array( $ignore = array() ) {
        $data = array();

        $db_fields = $this->get_db_fields();

        foreach ( $db_fields as $db_field ) {
            if ( in_array( $db_field, $ignore ) )
                continue ;

            $data[$db_field] = $this->$db_field;
        }

        return $data;
    }

    public function reset() {
        $props = Object_Helper::get_default_properties( $this );

        foreach ( $props as $k => $v ) {
            if ( ! in_array( $k, $this->db_keys ) ) {
                $this->$k = $v;
            }
        }
    }

    public function load( $keys, $reset = true ) {
        if ( $reset )
            $this->reset();

        if ( ! is_array($keys) ) {
            if ( $this->is_single_key )
                $keys = array( $this->db_keys[0] => $keys );
            else
                throw new UnexpectedValueException( 'Multiple keys should be passed.' );
        }

        $query_params = array();
        $query = sprintf(
            'SELECT * FROM `%s` WHERE ',
            $this->db_tbl
        );

        $where = array();
        foreach ($keys as $k => $v) {
            $where[] = sprintf(
                '`%s` = %%s',
                $k
            );

            $query_params[] = $v;
        }

        $query .= join(' AND ', $where);

        $query = $this->db->prepare( $query, $query_params );
        $data = $this->db->get_row( $query, ARRAY_A );

        $result = false;
        if ( ! empty( $data ) ) {
            $result = $this->bind( $data );
        }

        return $result;
    }

    public function custom_load( $filter, $reset = true ) {
        if ( $reset )
            $this->reset();

        $query_params = array();
        $query = sprintf(
            'SELECT * FROM `%s` WHERE ',
            $this->db_tbl
        );

        $where = array();
        foreach ($filter as $k => $v) {
            $where[] = sprintf(
                '`%s` = %%s',
                $k
            );

            $query_params[] = $v;
        }

        $query .= join(' AND ', $where);

        $query = $this->db->prepare( $query, $query_params );
        $data = $this->db->get_row( $query, ARRAY_A );

        $result = false;
        if ( ! empty( $data ) ) {
            $result = $this->bind( $data );
        }

        return $result;
    }

    public function bind( $data, $ignore = array() ) {
        if ( is_object( $data ) )
            $data = Object_Helper::get_properties( $data );

        $props = Object_Helper::get_properties( $this );

        foreach ( $this->bool_fields as $bool_field ) {
            if ( ! isset( $data[$bool_field] ) )
                $data[$bool_field] = 0;
        }

        foreach ( $props as $k => $v ) {
            if ( in_array( $k, $ignore ) )
                continue ;

            if ( isset( $data[$k] ) )
                $this->$k = $data[$k];
        }

        return true;
    }

    public function store( $force_insert = false ) {
        $result = null;
        $db = $this->db;

        $props = Object_Helper::get_properties( $this );
        $db_fields = $this->get_db_fields();

        $data = array();

        foreach ( $db_fields as $db_field ) {
            $data[$db_field] = $props[$db_field];
        }

        if ( $force_insert || $this->is_new() ) {
            $result = $db->insert(
                $this->db_tbl,
                $data
            );

            if ( false !== $result) {
                $key = $this->db_keys[0];
                $this->$key = $db->insert_id;
                $result = true;
            }
        } else {
            $where = array();

            foreach ( $this->db_keys as $key ) {
                $where[$key] = $props[$key];
                unset( $data[$key] );
            }

            $result = $db->update(
                $this->db_tbl,
                $data,
                $where
            );

            if ( false !== $result)
                $result = true;
        }

        return $result;
    }

    public function is_new() {
        $is_new = false;

        foreach ( $this->db_keys as $key ) {
            if ( empty( $this->$key ) ) {
                $is_new = true;
                break;
            }
        }

        return $is_new;
    }

    public function get_unique_name( $name = null, $title_db_field = null ) {
        if ( empty( $title_db_field ) )
            throw new \InvalidArgumentException( 'Specify title field.' );

        $db_fields = $this->get_db_fields();

        if ( ! in_array( $title_db_field, $db_fields ) )
            throw new \InvalidArgumentException( '"' . $title_db_field . '" DB field does not exist.' );

        if ( empty( $name ) )
            $name = $this->$title_db_field;

        $name = preg_replace( '/\s\(\s*[\s0-9]+\s*\)$/', '', $name );

        $db = $this->db;

        $query = sprintf(
            'SELECT `%1$s` FROM `%2$s` WHERE `%1$s` LIKE CONCAT(%3$s," (%%)")',
            $title_db_field,
            $this->db_tbl,
            $db->prepare( '%s', $name )
        );
        $names = $db->get_col( $query );

        $postfix = 1;
        if ( is_array( $names ) && count( $names ) > 0 )
        {
            $postfix_list = array();
            foreach ( $names as $src_name )
            {
                $matches = array();
                if ( preg_match( '/\(\s*(\d+)\s*\)$/', $src_name, $matches) )
                    $postfix_list[] = $matches[1];
            }

            sort( $postfix_list, SORT_NUMERIC );
            $postfix = $postfix_list[ count( $postfix_list ) - 1 ] + 1;
        }

        return $name . ' (' . $postfix . ')';
    }

    public function validate() {
        return true;
    }
}
