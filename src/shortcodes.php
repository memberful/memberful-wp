<?php

add_shortcode( 'memberful', 'memberful_wp_shortcode' );

function memberful_wp_shortcode( $atts, $content ) {
	$show_content = FALSE;
	$does_not_have_product = $does_not_have_subscription = NULL;

	if ( ! empty( $atts['has_subscription'] ) ) {
		$show_content = has_memberful_subscription( $atts['has_subscription'] );
	}

	if ( ! empty( $atts['has_product'] ) ) {
		$has_product = has_memberful_product( $atts['has_product'] );

		$show_content = $show_content || $has_product;
	}

	if ( ! empty( $atts['does_not_have_subscription'] ) ) {
		$does_not_have_subscription = ! has_memberful_subscription(
			$atts['does_not_have_subscription']
		);
	}

	if ( ! empty( $atts['does_not_have_product'] ) ) {
		$does_not_have_product = ! has_memberful_product(
			$atts['does_not_have_product']
		);
	}

	if ( $does_not_have_product !== NULL || $does_not_have_subscription !== NULL) {
		$requirements = array($does_not_have_subscription, $does_not_have_product);

		if ( in_array( FALSE, $requirements, TRUE) ) {
			// User may have access to either the mentioned product or the subscription
			$show_content = FALSE;
		} else {
			// All specified requirements have been satisfied, so show content
			$show_content = TRUE;
		}
	}

	return $show_content ? $content : '';
}

function memberful_wp_slugs_to_ids( $slugs ) {
	if ( is_string( $slugs ) )
		$slugs = explode( ',', $slugs );

	return array_map( 'memberful_wp_extract_id_from_slug', $slugs );
}

function memberful_wp_extract_id_from_slug( $slug ) {
	list( $id, $name ) = explode( '-', $slug, 2 );

	return $id;
}
