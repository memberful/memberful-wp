<?php

add_shortcode('memberful', 'memberful_wp_shortcode');

function memberful_wp_shortcode($atts, $content) { 
	$show_content = FALSE;

	if ( ! empty($atts['has_subscription']) ) {
		$show_content = has_memberful_subscription(
			memberful_wp_slugs_to_ids( $atts['has_subscription'] )
		);
	}

	if ( ! empty($atts['has_product']) ) { 
		$has_product = has_memberful_product(
			memberful_wp_slugs_to_ids( $atts['has_product'] )
		);

		$show_content = $show_content || $has_product;
	}

	return $show_content ? $content : '';
}

function memberful_wp_slugs_to_ids($slugs) { 
	if ( is_string( $slugs ) )
		$slugs = explode( ',', $slugs );

	return array_map('memberful_wp_extract_id_from_slug', $slugs);
}

function memberful_wp_extract_id_from_slug($slug) { 
	list($id, $name) = explode('-', $slug, 2);

	return $id;
}
