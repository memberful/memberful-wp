<?php

wp_register_sidebar_widget(
	'memberful_wp_widget_login',
	'Memberful Auth',
	'memberful_wp_widget_login'
);

function memberful_wp_widget_login() {
	memberful_wp_render('login_widget');
}

