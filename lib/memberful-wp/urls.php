<?php


/**
 * Generate a URL to the memberful site
 *
 * @param string $uri    The URI to append
 * @param string $format The requested format
 * @return string URL
 */
function memberful_url($uri = '', $format = MEMBERFUL_HTML)
{
	$endpoint = '/'.$uri;

	if($format !== MEMBERFUL_HTML)
	{
		$endpoint .= '.'.$format;
	}

	return rtrim(get_option('memberful_site'),'/').$endpoint;
}

function memberful_member_url($format = MEMBERFUL_HTML)
{
	return memberful_url('member', $format);
}

function memberful_admin_products_url($format = MEMBERFUL_HTML)
{
	return memberful_url('admin/products', $format);
}

function memberful_admin_product_url($product_id, $format = MEMBERFUL_HTML)
{
	return memberful_url('admin/products/'.(int) $product_id, $format);
}
