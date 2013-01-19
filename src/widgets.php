<?php

wp_register_sidebar_widget(
	'memberful_wp_profile_widget',
	'Memberful',
	'memberful_wp_profile_widget'
);

function memberful_wp_profile_widget() {
	memberful_wp_render( 'profile_widget' );
}