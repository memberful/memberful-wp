<?php

add_shortcode('memberful', 'memberful_wp_shortcode');

function memberful_wp_shortcode($atts, $content) { 
	$show_content = FALSE;

	if ( ! empty($atts['has_subscription']) ) {
		
	}

	return $show_content ? $content : '';
}
