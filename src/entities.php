<?php

function memberful_product( $slug ) {
	$products = memberful_products();
	$id       = memberful_wp_extract_id_from_slug( $slug );

	return empty( $products[$id] ) ? NULL : $products[$id];
}

function memberful_subscription_plan( $slug ) {
	$plans = memberful_subscription_plans();
	$id    = memberful_wp_extract_id_from_slug( $slug );

	return empty( $plans[$id] ) ? NULL : $plans[$id];
}

function memberful_products() {
	return get_option( 'memberful_products', array() );
}

function memberful_subscription_plans() {
	return get_option( 'memberful_subscriptions', array() );
}

function memberful_wp_sync_products() {
	$url = memberful_admin_products_url( MEMBERFUL_JSON );

	update_option( 'memberful_products', memberful_wp_fetch_entities( $url ) );

	return TRUE;
}

function memberful_wp_sync_subscriptions() {

	$url = memberful_admin_subscriptions_url( MEMBERFUL_JSON );

	update_option( 'memberful_subscriptions', memberful_wp_fetch_entities( $url ) );

	return TRUE;
}

function memberful_wp_fetch_entities( $url ) {
	$full_url = add_query_arg( 'auth_token', get_option( 'memberful_api_key' ), $url );

	$response = wp_remote_get( $full_url, array( 'sslverify' => MEMBERFUL_SSL_VERIFY ) );

	if ( is_wp_error( $response ) ) {
		var_dump( $response, $full_url, $url );
		die();
	}

	if ( $response['response']['code'] != 200 OR ! isset( $response['body'] ) ) {
		return new WP_Error( 'memberful_sync_fail', "Couldn't retrieve list of entities from Memberful." );
	}

	$raw_entity = json_decode( $response['body'] );
	$entities   = array();

	foreach ( $raw_entity as $entity ) {
		$entities[$entity->id] = memberful_wp_format_entity( $entity );
	}

	return $entities;
}

function memberful_wp_format_entity( $entity ) {
	return array(
		'id'          => $entity->id,
		'name'        => $entity->name,
		'slug'        => $entity->slug,
		'for_sale'    => $entity->for_sale,
		'price'       => $entity->price
	);
}
