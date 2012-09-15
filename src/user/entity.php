<?php

abstract class Memberful_Wp_User_Entity { 
	static public function sync($user_id, $entities) {
		$syncer = new static($user_id);
		return $syncer->set($entities);
	}

	protected $entities;
	protected $user_id;

	public function __construct( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * Get the IDs of the entities this user has
	 *
	 * @return array
	 */
	public function get() {
		if ( $this->entities === NULL ) {
			$this->entities = get_user_meta( $this->user_id, $this->meta_field() );
		}

		return $this->entities;
	}

	/**
	 * Add a set of entities to the entities the user has
	 *
	 * @param array $entities
	 */
	public function add( array $entities ) {
		$ids = array();

		foreach ( $entities as $entity ) {
			$ids[$entity->id] = $this->format($entity);
		}

		return $this->addIds( $ids );
	}

	/**
	 * Add a set of entity ids to the entities the user has
	 *
	 * @param array $entity_ids
	 */
	public function addIds( array $entity_ids ) {
		$entities = $this->get();

		foreach ( $entity_ids as $entity_id ) {
			$entities[$entity_id] = $entity_id;
		}

		$this->setIds($entities);
	}

	public function set( array $entities ) {
		$ids = array();
		
		foreach ( $entities as $entity) {
			$data = $this->format($entity);

			$ids[$data['id']] = $data;
		}

		return $this->setIds($ids);
	}

	protected function setIds( array $entities ) {
		$new_ids = array();

		foreach ( $entities as $entity ) {
			$new_ids[$entity['id']] = $entity;
		}

		update_user_meta( $this->user_id, $this->meta_field(), $new_ids );
	}

	protected function meta_field() {
		return 'memberful_'.$this->entity_type();
	}

	abstract protected function entity_type();
	abstract protected function format($entity);
}
