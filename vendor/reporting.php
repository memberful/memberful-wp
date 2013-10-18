<?php

if ( ! class_exists( 'Memberful_Wp_Reporting' ) ) :

/**
 * An object for storing transient errors
 * 
 * @author Zack Tollman (The Theme Foundry)
 * @author Matt Button (Memberful)
 * @license GPL
 */
class Memberful_Wp_Reporting {

	/**
	 * Record a message for the user in transient storage
	 *
	 * @param  string             $message       The message to display.
	 * @param  string             $type          The message type (e.g., error, success, notice).
	 * @param  string             $code          The message code.
	 * @param  string             $user_login    The user login for the user setting the message.
	 */
	public static function report($message, $type = 'success', $code = '', $user_login = '') {
		$instance = new Memberful_Wp_Reporting( $user_login );

		$instance->record( $message, $type, $code );
	}

	/**
	 * If a message is available in transient storage then return it, and remove it
	 * from transient storage
	 *
	 * @param string $user_login
	 */
	public static function pop( $user_login = '' ) {
		$instance = new Memberful_Wp_Reporting( $user_login );

		$value = $instance->get();

		$instance->delete();

		return $value;
	}

	/**
	 * Key used to store the message in a transient.
	 *
	 * @var string
	 */
	public $key;

	/**
	 * The message.
	 *
	 * @var string
	 */
	public $value;

	/**
	 * @param  string             $user_login    The user login for the user setting the message.
	 * @return Memberful_Wp_Reporting
	 */
	public function __construct( $user_login = '' ) {
		// Get the user login value
		$user = get_user_by( 'login', $user_login );
		if ( false === $user ) {
			$user = wp_get_current_user();
		}

		// Construct and cache the key
		$this->key = $this->_build_key( $user->user_login );
	}

	/**
	 * Record the message and associated data to a transient.
	 *
	 * @param  string    $message    The message to display.
	 * @param  string    $type       The message type (e.g., error, success, notice).
	 * @param  string    $code       The message code.
	 * @return bool                  True if set; false if not.
	 */
	public function record( $message, $type = 'success', $code = '' ) {
		$value = $this->sanitize_report_data( $message, $type, $code );
		return set_transient( $this->key, $value, 500 );
	}

	/**
	 * Clean the data before saving to the database.
	 *
	 * @param  string    $message    The message to display.
	 * @param  string    $type       The message type (e.g., error, success, notice).
	 * @param  string    $code       The message code.
	 * @return array                 The clean data.
	 */
	public function sanitize_report_data( $message, $type = 'success', $code = '' ) {
		// If it is a WP_Error object, deconstruct it
		if ( is_wp_error( $message ) ) {
			$code    = $message->get_error_code();
			$message = $message->get_error_message();
		}

		// Put the pieces of data together after sanitization
		$value = array(
			'code'    => sanitize_key( $code ),
			'message' => sanitize_text_field( $message ),
			'type'    => sanitize_key( $type ),
		);

		// Return the clean data
		return apply_filters( 'Memberful_Wp_Reporting_sanitize_report_data', $value, $message, $type, $code );
	}

	/**
	 * Get the stored message and data.
	 * 
	 * @return array    The array of notice data.
	 */
	public function get() {
		if ( is_null( $this->value ) ) {
			$this->value = get_transient( $this->key );
		}
		return apply_filters( 'Memberful_Wp_Reporting_get', $this->value );
	}

	/**
	 * Delete the message.
	 * 
	 * @return bool    True on success; False on failure.
	 */
	public function delete() {
		return delete_transient( $this->key );
	}

	/**
	 * Create a key to access the stored message data.
	 * 
	 * @param  string    $user_login    The user login for the user storing the data.
	 * @return string                   The generate key.
	 */
	private function _build_key( $user_login ) {
		$key = 'shirk-msg' . md5( $user_login );
		return apply_filters( 'Memberful_Wp_Reporting__build_key', $key, $user_login );
	}
}

endif;
