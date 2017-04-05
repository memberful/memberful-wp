<?php
namespace Ari\Wordpress;

use Ari\Cache\Lite as Cache;

class Security {
    static public function get_roles( $force = false ) {
        if ( ! $force && Cache::exists( 'wp_roles' ) ) {
            return Cache::get( 'wp_roles' );
        }

        global $wp_roles;

        $roles = array();

        if ( ! isset( $wp_roles ) ) {
            get_role( 'ping' );
        }

        $roles_data = $wp_roles->roles;

        foreach ( $roles_data as $role_id => $role_data ) {
            $role = get_role( $role_id );

            $roles[] = $role;
        }

        Cache::set( 'wp_roles', $roles );

        return $roles;
    }
}