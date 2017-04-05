<?php
namespace Ari_Adminer\Models;

use Ari\Models\Model as Model;

class Connection extends Model {
    public function save( $data ) {
        $id = ! empty( $data['connection_id'] ) ? intval( $data['connection_id'], 10 ) : 0;
        $entity = null;

        if ( $id > 0 ) {
            $entity = $this->get_connection( $id );

            if ( false === $entity )
                return false;
        } else {
            $entity = $this->entity();
        }

        if ( ! $entity->bind( $data ) ) {
            return false;
        }

        if ( ! $entity->validate() ) {
            return false;
        }

        if ( ! $entity->store() )
            return false;

        return $entity;
    }

    public function get_connection( $id ) {
        $entity = $this->entity();
        if ( ! $entity->load( $id ) )
            return false;

        return $entity;
    }

    public function validate_connection_params( $data ) {
        $entity = $this->entity();

        if ( ! $entity->bind( $data ) ) {
            return false;
        }

        return $entity->validate_connection_params( $data );
    }
}
