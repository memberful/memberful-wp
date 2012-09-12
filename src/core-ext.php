<?php

/**
 * Adaptions to core wordpress code that don't fit in other areas.
 *
 * Also includes other misc code.
 */

add_filter( 'allowed_redirect_hosts', 'memberful_allowed_hosts' );

/**
 * Adds the Memberful domain to the list of allowed redirect hosts
 * @param array $content A set of websites that can be redirected to
 * @return array The $content plus Memberful domain
 */
function memberful_allowed_hosts( $content ) { 
	$site = get_option( 'memberful_site' );

	if ( !empty( $site ) ) { 
		$memberful_url = parse_url( $site );

		if ( $memberful_url !== false )
			$content[] = $memberful_url['host'];
	}

	return $content;
}

function memberful_wp_render( $template, array $vars = array() ) { 
	extract( $vars );

	include MEMBERFUL_DIR.'/views/'.$template.'.php';
}
