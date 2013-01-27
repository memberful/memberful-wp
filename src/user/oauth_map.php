<?php

class Memberful_User_Oauth_Map {
	public function map( $member, $refresh_token ) {
		$mapper = new Memberful_User_Map();

		return $mapper->map( $member, array( 'refresh_token' => $refresh_token ) );
	}
}
