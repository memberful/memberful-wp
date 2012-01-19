<?php

/**
 * Is OAuth authentication enabled?
 *
 * @return boolean
 */
function memberful_wp_oauth_enabled()
{
	return TRUE;
}

class Memberful_Authenticator
{
	/**
	 * Gets the url for the specified action at the member oauth endpoint
	 *
	 * @param string $action Action to access at endpoint
	 * @return string URL
	 */
	static function oauth_member_url($action = '')
	{
		return memberful_url('oauth/'.$action);
	}

	/**
	 * Returns the url of the endpoint that members will be sent to
	 *
	 * @return string
	 */
	static function oauth_auth_url()
	{
		$params = array(
			'response_type' => 'code',
			'client_id'     => get_option('memberful_client_id')
		);

		return add_query_arg($params, self::oauth_member_url());
	}

	/**
	 * @var WP_Error Errors encountered
	 */
	protected $_wp_error = NULL;

	protected function _error($code, $message = NULL)
	{
		if($message === NULL)
		{
			$message = 'Could not authenticate against memberful';
		}

		$message .= '<br/>Please contact site admin';

		return $this->_wp_error = new WP_Error($code, $message);
	}

	/**
	 * Authentication for subscribers is handled by Memberful.
	 * Prevent subscribers from requesting password resets
	 *
	 * @return boolean
	 */
	public function audit_password_reset($allowed, $user_id)
	{
		$user = new WP_User($user_id);

		return $user->has_cap('subscriber') ? FALSE : $allowed;
	}

	/**
	 * Callback for the `authenticate` hook.
	 *
	 * Called in wp-login.php when the login form is rendered, thus it responds
	 * to both GET and POST requests.
	 *
	 * @return WP_User The user to be logged in or NULL if user couldn't be
	 * determined
	 */
	public function init($user, $username, $password)
	{
		// If another authentication system has handled this request
		if($user instanceof WP_User || ! memberful_wp_oauth_enabled())
		{
			return $user;
		}

		// If a username or password has been posted then fallback to normal auth
		//
		// If GET isn't empty (e.g. a redirect_to is supplied) and the page that sent
		// them here hasn't requested memberful authentication then chances are its
		// some kind of admin related operation which a customer won't be able to perform,
		// in which case we should allow them to specify a username/password to
		// login with
		if( ! empty($username) || ! empty($password) || ( ! empty($_GET) && ! isset($_GET['memberful_auth'])))
		{
			return $user;
		}

		// This is the OAuth response
		if(isset($_GET['code']))
		{
			$tokens = $this->get_oauth_tokens($_GET['code']);

			if(is_wp_error($tokens))
				return $tokens;

			$details = $this->get_member_data($tokens->access_token);

			$mapper = new Memberful_User_Map;
			$user   = $mapper->map($details->member, $details->products, $tokens->refresh_token);

			return $user;
		}
		// For some reason we got an error code.
		elseif(isset($_GET['error']))
		{
			return $this->_error(
				'memberful_oauth_error', 
				'An error prevented you from being logged in.('.htmlentities($_GET['error']).')'
			);
		}

		// Send the user to memberful
		wp_redirect(self::oauth_auth_url(), 302);
		exit();
	}

	/**
	 * For some amazingly obvious reason which I don't quite understand, 
	 * wp_authenticate_username_password always overrides any errors generated
	 * by authentication hooks.
	 *
	 * This filter is injected after username_password and will re-set any 
	 * memberful errors.
	 *
	 * @param mixed $user
	 * @return mixed
	 */
	public function relay_errors($user)
	{
		if($user instanceof WP_Error)
		{
			if(in_array($user->get_error_code(), array('empty_username', 'empty_password')))
			{
				return $this->_wp_error;
			}
		}

		return $user;
	}

	/**
	 * Gets the access token and refresh token from an authorization code
	 *
	 * @param string $auth_code The authorization code returned from oauth endpoint
	 * @return StdObject Access token and Refresh token
	 */
	public function get_oauth_tokens($auth_code)
	{
		$params = array(
			'client_id'     => get_option('memberful_client_id'),
			'client_secret' => get_option('memberful_client_secret'),
			'grant_type'    => 'authorization_code',
			'code'          => $auth_code
		);
		$response = wp_remote_post(
			self::oauth_member_url('token'), 
			array(
				'body'      => $params, 
				'sslverify' => false
			)
		);

		$body = json_decode($response['body']); 
		$code = $response['response']['code'];

		if ($code !== 200 OR $body === NULL OR empty($body->access_token))
		{
			return $this->_error(
				'oauth_access_fail', 
				'Could not get access token from Memberful'
			);
		}

		return json_decode($response['body']);
	}

	/**
	 * Gets information about a user from memberful.
	 *
	 * @param string $access_token An access token which can be used to get info
	 * about the member
	 * @return array
	 */
	public function get_member_data($access_token)
	{
		$url = memberful_member_url(MEMBERFUL_JSON);

		$response = wp_remote_get(
			add_query_arg('access_token', $access_token, $url),
			array('sslverify' => false)
		);

		$body = json_decode($response['body']);

		if($response['response']['code'] !== 200 OR $body === NULL)
		{
			return $this->error('memberful_data_error', 'Could not fetch your data from Memberful.');
		}

		return $body;
	}

}

$authenticator = new Memberful_Authenticator;

add_filter('authenticate', array($authenticator, 'init'), 10, 3);
add_filter('authenticate', array($authenticator, 'relay_errors'), 50, 3);
add_filter('allow_password_reset', array($authenticator, 'audit_password_reset'), 50, 2);
