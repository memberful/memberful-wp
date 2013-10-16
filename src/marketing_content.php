<?php

define( 'MEMBERFUL_MARKETING_META_KEY', 'memberful_marketing_content' );

function memberful_marketing_content( $post_id ) {
	return get_post_meta( $post_id, MEMBERFUL_MARKETING_META_KEY, TRUE );
}

function memberful_wp_update_post_marketing_content( $post_id, $content ) {
	return update_post_meta( $post_id, MEMBERFUL_MARKETING_META_KEY, $content );
}
