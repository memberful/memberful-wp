<?php


/**
 * Generate a URL to the Memberful site
 *
 * @param string $uri    The URI to append
 * @param string $format The requested format
 * @return string URL
 */
function memberful_url($uri = '', $format = MEMBERFUL_HTML)
{
	$endpoint = '/'.trim($uri,'/');

	if($format !== MEMBERFUL_HTML) {
		$endpoint .= '.'.$format;
	}
	else {
		$endpoint .= '/';
	}

	return rtrim(get_option('memberful_site'),'/').$endpoint;
}

function memberful_wp_login_url()
{
	return plugins_url('auth.php', dirname(dirname(__FILE__)));
}

function memberful_wp_logout_url()
{
	return add_query_arg('action', 'logout', memberful_wp_login_url());
}

function memberful_member_url($format = MEMBERFUL_HTML)
{
	return memberful_url('member', $format);
}

function memberful_signout_url()
{
	return memberful_url('auth/sign_out');
}

function memberful_admin_member_url($member_id, $format = MEMBERFUL_HTML)
{
	return memberful_url('admin/members/'.$member_id, $format);
}

function memberful_admin_products_url($format = MEMBERFUL_HTML)
{
	return memberful_url('admin/products', $format);
}

function memberful_admin_product_url($product_id, $format = MEMBERFUL_HTML)
{
	return memberful_url('admin/products/'.(int) $product_id, $format);
}

function memberful_signin_url()
{
	return memberful_url('auth/sign_in');
}

function memberful_wrap_api_token($url)
{
	return add_query_arg('auth_token', get_option('memberful_api_key'), $url);
}

function memberful_order_completed_url($order)
{
	return add_query_arg('id', $order, memberful_url('orders/completed'));
}
function memberful_wp_webhook_url()
{
	return add_query_arg('secret', MEMBERFUL_TOKEN, get_site_url(null, 'wp-content/plugins/memberful-wp/endpoint.php'));
}
