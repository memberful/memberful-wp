<?php

/**
 * Maps a memberful user to a wordpress user.
 *
 * If the wordpress user does not exist then they are created from the member 
 * details provided.
 *
 */
class Memberful_User_Map
{
	public function map($user, $products, $refresh_token = NULL)
	{
		$wp_user = $this->sync_user_and_token($user, $refresh_token);

		$this->sync_products($wp_user, $products);

		return $wp_user;
	}

	/**
	 * Takes a set of memberful member details and tries to associate it with the
	 * wordpress user account.
	 *
	 * @param StdObject $details       Details about the member
	 * @param string    $refresh_token The member's refresh token for oauth
	 * @return WP_User
	 */
	public function sync_user_and_token($member, $refresh_token = NULL)
	{
		global $wpdb;

		$query = $wpdb->prepare(
			'SELECT *, (`memberful_member_id` = %d) AS `exact_match` FROM `'.$wpdb->users.'` WHERE `memberful_member_id` = %d OR `user_email` = %s ORDER BY `exact_match` DESC',
			$member->id,
			$member->id,
			$member->email
		);

		$user = $wpdb->get_row($query);

		// User does not exist
		if($user === NULL)
		{
			$data = array(
				'user_pass'     => wp_generate_password(),
				'user_login'    => $member->username,
				'user_nicename' => $member->full_name,
				'user_email'    => $member->email,
				'display_name'  => $member->full_name,
				'nickname'      => $member->full_name,
				'first_name'    => $member->first_name,
				'last_name'     => $member->last_name,
				'show_admin_bar_frontend' => FALSE,
			);

			$user_id = wp_insert_user($data);

			if(is_wp_error($user_id))
			{
				var_dump($user_id);
				die('ERRORR!!!');
				return $user_id;
			}
		}
		else
		{
			// Now sync the two accounts
			$user_id = $user->ID;

			// Mapping of wordpress => memberful keys
			$mapping = array(
				'user_email'    => 'email',
				'user_login'    => 'username',
				'display_name'  => 'full_name',
				'user_nicename' => 'full_name',

			);

			$metamap = array(
				'nickname'      => 'full_name',
				'first_name'    => 'first_name',
				'last_name'     => 'last_name'
			);

			$meta = get_user_meta($user_id, '', true);

			// For some insane reason Wordpress only allows us to do a complete update of values
			// No partial updates allowed.
			$data = (array) $user;

			foreach($mapping as $wp_key => $m_key)
			{
				$data[$wp_key] = $member->$m_key;
			}

			foreach($metamap as $wp_key => $m_key)
			{
				$data[$wp_key] = $member->$m_key;
			}

			wp_insert_user($data);
		}

		$this->_update_member_id($user_id, $member->id, $member->username, $refresh_token);
		
		return get_userdata($user_id);
	}

	public function sync_products(WP_User $user, $products)
	{
		$product_ids = array_map(array($this, '_extract_product_id'), $products);

		$new_products = empty($product_ids) 
			? array() 
			: array_combine($product_ids, $product_ids);

		update_user_meta($user->ID, 'memberful_products', $new_products);
	}

	protected function _extract_product_id($product_link)
	{
		return (int) $product_link->product_id;
	}

	protected function _update_member_id($user_id, $member_id, $login_name, $refresh_token = NULL)
	{
		global $wpdb;
		$data = array();

		$update = 'UPDATE `'.$wpdb->users.'` SET ';

		if($refresh_token !== NULL)
		{
			$update .= '`memberful_refresh_token` = %s, ';
			$data[] = $refresh_token;
		}

		$update .= '`memberful_member_id` = %d WHERE `ID` = %d';
		$data[] = $member_id;
		$data[] = $user_id;

		$wpdb->query($wpdb->prepare($update, $data));
	}
}
