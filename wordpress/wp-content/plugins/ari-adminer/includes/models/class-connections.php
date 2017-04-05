<?php
namespace Ari_Adminer\Models;

use Ari\Models\Model as Model;
use Ari\Utils\Request as Request;
use Ari\Utils\Array_Helper as Array_Helper;
use Ari_Adminer\Helpers\Helper as Helper;
use Ari_Adminer\Helpers\Crypt as Crypt;

class Connections extends Model {
    protected $sort_columns = array( 'title' );

    protected function populate_state() {
        $filter = array(
            'order_by' => 'title',

            'order_dir' => 'ASC',

            'page_size' => 0,

            'page_num' => 0,
        );

        $user_filter = null;
        if ( Request::exists( 'filter' ) ) {
            $user_filter = Request::get_var( 'filter' );

            if ( ! empty ( $user_filter ) )
                $user_filter = unserialize( base64_decode( $user_filter ) );
            else
                $user_filter = null;
        }

        if ( is_array( $user_filter ) ) {
            foreach ( $user_filter as $filter_key => $filter_val ) {
                if ( isset( $filter[$filter_key] ) )
                    $filter[$filter_key] = $filter_val;
            }
        }

        $this->state['filter'] = $filter;
    }

    public function data() {
        $filter = $this->get_state( 'filter' );

        $items = $this->items( $filter );
        $items_count = $this->items_count( $filter );

        $data = array(
            'count' => $items_count,

            'list' => $items,

            'filter' => $filter,

            'filter_encoded' => $this->encoded_filter_state()
        );

        return $data;
    }

    public function encoded_filter_state() {
        $filter = $this->get_state( 'filter' );

        return $filter ? base64_encode( serialize( $filter ) ) : '';
    }

    public function items_count( $filter = null ) {
        if ( is_null( $filter ) )
            $filter = $this->get_state( 'filter' );

        $query = sprintf(
            'SELECT COUNT(*) FROM `%1$sariadminer_connections`',
            $this->db->prefix
        );

        $query = $this->prepare_query( $query, $filter, false );

        $count = $this->db->get_var( $query );

        return $count;
    }

    public function items( $filter = null ) {
        if ( is_null( $filter ) )
            $filter = $this->get_state( 'filter' );

        $query = sprintf(
            'SELECT `connection_id`,`title`,`type`,`host`,`user`,`pass` FROM `%1$sariadminer_connections`',
            $this->db->prefix
        );

        $query = $this->prepare_query( $query, $filter );

        $items = $this->db->get_results( $query, OBJECT );

        return $items;
    }

    protected function prepare_query( $query, $filter, $paging = true ) {
        if ( $paging ) {
            if ( $filter['order_by'] && in_array( $filter['order_by'], $this->sort_columns ) ) {
                $order_by = $filter['order_by'];
                $order_dir = 'DESC' == $filter['order_dir'] ? 'DESC' : 'ASC';

                $query .= sprintf(
                    ' ORDER BY %s %s',
                    $order_by,
                    $order_dir
                );
            }

            if ( $filter['page_size'] > 0 && $filter['page_num'] >= 0 ) {
                $page_num = $filter['page_num'];
                $page_size = $filter['page_size'];

                $offset = $page_num * $page_size;

                $query .= sprintf(
                    ' LIMIT %d,%d',
                    $offset,
                    $page_size
                );
            }
        }

        return $query;
    }

    public function delete( $id_list ) {
        if ( ! is_array( $id_list ) )
            $id_list = array( $id_list );

        if ( count( $id_list ) == 0 )
            return false;

        $id_list = Array_Helper::to_int( $id_list, 0 );

        $query = sprintf(
            'DELETE FROM
              `%1$sariadminer_connections`
            WHERE connection_id IN (%2$s)',
            $this->db->prefix,
            join( ',', $id_list )
        );

        $result = $this->db->query( $query );

        return ( false !== $result );
    }

    public function re_crypt_passwords( $new_crypt_key, $old_crypt_key ) {
        if ( ! Helper::support_crypt() )
            return false;

        $connections = $this->db->get_results(
            sprintf(
                'SELECT `connection_id`,`pass`,`crypt` FROM `%1$sariadminer_connections` WHERE LENGTH(`pass`) > 0',
                $this->db->prefix
            ),
            OBJECT
        );

        if ( count( $connections ) == 0 )
            return true;

        foreach ( $connections as $connection ) {
            $plain_pass = $connection->crypt ? Crypt::decrypt( $connection->pass, $old_crypt_key ) : $connection->pass;
            $pass = Crypt::crypt( $plain_pass, $new_crypt_key );

            $this->db->query(
                $this->db->prepare(
                    sprintf(
                        'UPDATE `%1$sariadminer_connections` SET `pass` = %%s,`crypt` = 1 WHERE `connection_id` = %%d',
                        $this->db->prefix
                    ),
                    $pass,
                    $connection->connection_id
                )
            );
        }

        return true;
    }
}
