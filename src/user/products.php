<?php

/**
 * Interface for interacting with a user's products
 *
 */
class Memberful_Wp_User_Products { 
	protected $products;
	protected $user_id;

	public function __construct( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * Get the IDs of the products this user has
	 *
	 * @return array
	 */
	public function get() {
		if ( $this->products === NULL ) {
			$this->products = get_user_meta( $this->user_id, 'memberful_products' );
		}

		return $this->products;
	}

	/**
	 * Add a set of products to the products the user has
	 *
	 * @param array $products
	 */
	public function add( array $products ) {
		$ids = array();

		foreach ( $products as $product ) {
			$ids[$product->id] = $product->id;
		}

		return $this->addIds( $ids );
	}

	/**
	 * Add a set of product ids to the products the user has
	 *
	 * @param array $product_ids
	 */
	public function addIds( array $product_ids ) {
		$new_ids = array_combine( $product_ids, $product_ids );

		update_user_meta( $this->user_id, 'memberful_products', $this->get() + $new_ids );
	}
}
