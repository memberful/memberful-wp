<?php

add_shortcode('memberful', 'memberful_wp_shortcode');

function memberful_wp_shortcode($atts, $content) { 
	$show_content = FALSE;

	if ( ! empty($atts['has_subscription']) ) {
		
	}

	if ( ! empty($atts['has_product']) ) { 
		$slugs = explode(',', $atts['has_product']);
		$ids   = array_map('memberful_wp_extract_id_from_slug', $slugs);

		$show_content = $show_content || memberful_current_user_has_products($ids);
	}

	return $show_content ? $content : '';
}

function memberful_wp_extract_id_from_slug($slug) { 
	list($id, $name) = explode('-', $slug, 2);

	return $id;
}
