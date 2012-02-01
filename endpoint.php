<?php

define('MEMBERFUL_DIR', dirname(__FILE__));

if( $_SERVER['REQUEST_METHOD'] !== 'POST' )
	die('The webhook can only be accessed via POST');

$body = file_get_contents('php://input');

if( ($data = json_decode($body)) === NULL)
{
	var_dump($body, $data);
	die('Could not parse JSON payload');
}


require_once MEMBERFUL_DIR.'/../../../wp-load.php';

if($_GET['secret'] !== MEMBERFUL_TOKEN)
{
	die('Invalid webhook secret specified');
}

if(isset($data->member_id))
{
	$data = memberful_api_member($data->member_id);

	if(is_wp_error($data))
	{
		var_dump($data);

		die();
	}
	$map = new Memberful_User_Map();

	$map->map($data->member, $data->products);

	die('{"response": "ok"}');
}
else
{
	die('Could not identify member ID in webhook ping');
}
