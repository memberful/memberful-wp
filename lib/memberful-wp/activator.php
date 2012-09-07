<?php

/**
 * Allows wordpress to activate itself via Memberful's activation
 * endpoint
 *
 */
class Memberful_Activator
{
	const OAUTH	 = 'oauth';
	const API		 = 'api_key';
	const WEBHOOK = 'webhook';

	private $params = array('requirements' => array());

	/**
	 * @param $activation_code The activation code from memberful service
	 */
	public function __construct($activation_code, $app_name)
	{
		$this->params['activation_code'] = trim($activation_code);
		$this->params['app_name']        = trim($app_name);
	}

	/**
	 * Require that an oauth token is generated
	 *
	 * Requires a redirect url for generating the oauth credentials
	 *
	 * @param $redirect_url string
	 * @return Memberful_Activator
	 */
	public function requireOauth($redirect_url)
	{
		$this->params['requirements'][] = self::OAUTH;

		$this->params['oauth_redirect_url'] = trim($redirect_url);

		return $this;
	}

	/**
	 * Require that a webhook be setup for the app
	 *
	 * @param $webhook_url The url that memberful should ping
	 * @return Memberful_Activator
	 */
	public function requireWebhook($webhook_url)
	{
		$this->params['requirements'][] = self::WEBHOOK;

		$this->params['webhook_url'] = trim($webhook_url);

		return $this;
	}

	/**
	 * Require an API key be setup
	 *
	 * @return Memberful_Activator
	 */
	public function requireApiKey()
	{
		$this->params['requirements'][] = self::API;

		return $this;
	}

	public function activate()
	{
		$response = wp_remote_post(
			memberful_activation_url(),
			array(
				'headers' => array(
					'Content-Type' => 'application/json'
				),
				'body' => json_encode($this->params)
			)
		);

		if ( $response['response']['code'] != 200 OR ! isset($response['body']) ) {
			return new WP_Error('memberful_activation_fail', "Memberful returned an invalid response");
		}

		return json_decode($response['body']);
	}
}
