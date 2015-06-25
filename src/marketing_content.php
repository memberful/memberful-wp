<?php

define( 'MEMBERFUL_MARKETING_META_KEY', 'memberful_marketing_content' );
define( 'MEMBERFUL_OPTION_DEFAULT_MARKETING_CONTENT', 'memberful_default_marketing_content' );

function memberful_marketing_content( $post_id ) {
	$memberful_marketing_content = get_post_meta( $post_id, MEMBERFUL_MARKETING_META_KEY, TRUE );
	return apply_filters( 'memberful_marketing_content', $memberful_marketing_content );
}

function memberful_wp_update_post_marketing_content( $post_id, $content ) {
	update_post_meta( $post_id, MEMBERFUL_MARKETING_META_KEY, $content );
}

function memberful_wp_update_default_marketing_content( $content ) {
	update_option( MEMBERFUL_OPTION_DEFAULT_MARKETING_CONTENT, $content );
}

function memberful_wp_default_marketing_content() {
	return stripslashes( get_option( MEMBERFUL_OPTION_DEFAULT_MARKETING_CONTENT, '' ) );
}

function memberful_wp_marketing_content_explanation() {
	return __( "This marketing content will be shown in place of your protected content to anyone who is not allowed to read the post..." );
}
