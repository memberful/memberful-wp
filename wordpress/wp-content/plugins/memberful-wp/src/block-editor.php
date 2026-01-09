<?php
/**
 * Block Editor/Gutenberg compatibility.
 *
 * @since 1.77.0
 *
 * @package Memberful
 */

/**
 * Memberful WP Block Editor Class.
 *
 * @package Memberful
 */
class Memberful_WP_Block_Editor {

	/**
	 * Class Instance
	 *
	 * @var Memberful_WP_Block_Editor
	 */
	private static $instance;

	/**
	 * Get the class instance.
	 *
	 * @return Memberful_WP_Block_Editor The class instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( function_exists( 'register_block_type' ) ) {
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
			add_filter( 'register_block_type_args', array( $this, 'add_block_visibility_attributes' ), 10, 2 );

			add_action( 'render_block', array( $this, 'render_block' ), 10, 2 );
		}
	}

	/**
	 * Get the blocks that are excluded from the block visibility controls.
	 *
	 * @since 1.77.0
	 *
	 * @return array The blocks that are excluded from the block visibility controls.
	 */
	public static function get_block_visibility_excluded_blocks() {

		$excluded_blocks = array();

		/**
		 * Filters the blocks that are excluded from the block visibility controls.
		 *
		 * @since 1.77.0
		 *
		 * @param array $block_visibility_excluded_blocks The blocks that are excluded from the block visibility controls.
		 * @return array The blocks that are excluded from the block visibility controls.
		*/
		return apply_filters( 'memberful_wp_block_visibility_excluded_blocks', $excluded_blocks );
	}

	/**
	 * Enqueue the block editor assets.
	 *
	 * @since 1.77.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$block_editor_script      = MEMBERFUL_DIR . '/js/build/editor-scripts.asset.php';
		$block_editor_script_info = file_exists( $block_editor_script )
			? include $block_editor_script
			: array(
				'dependencies' => array(),
				'version'      => MEMBERFUL_VERSION,
			);

		wp_enqueue_script(
			'memberful-wp-block-editor',
			plugins_url( 'js/build/editor-scripts.js', MEMBERFUL_PLUGIN_FILE ),
			$block_editor_script_info['dependencies'],
			$block_editor_script_info['version']
		);
		wp_localize_script(
			'memberful-wp-block-editor',
			'memberful_wp_block_editor',
			array(
				'options'                          => memberful_wp_option_values(),
				'block_visibility_excluded_blocks' => self::get_block_visibility_excluded_blocks(),
			)
		);
	}

	/**
	 * Add the block visibility attributes to registered blocks server-side.
	 *
	 * @since 1.77.0
	 *
	 * @param array  $args The block arguments.
	 * @param string $name The block name.
	 * @return array The block arguments.
	 */
	public function add_block_visibility_attributes( $args, $name ) {
		// Skip for excluded blocks.
		if ( in_array( $name, self::get_block_visibility_excluded_blocks(), true ) ) {
			return $args;
		}

		$args['attributes']['memberful_visibility'] = array(
			'type'    => 'string',
			'enum'    => array( 'none', 'logged_in', 'specific' ),
			'default' => 'none',
		);

		$args['attributes']['memberful_visibility_hide'] = array(
			'type'    => 'boolean',
			'default' => false,
		);

		$args['attributes']['memberful_visibility_plans'] = array(
			'type'    => 'array',
			'default' => array(),
		);

		return $args;
	}

	/**
	 * Conditionally render the block based on the block visibility conditions.
	 *
	 * @since 1.77.0
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block.
	 * @return string The block content.
	 */
	public function render_block( $block_content, $block ): string {

		$original_block_content = $block_content;

		// Skip if no visibility rule is set.
		if ( empty( $block['attrs'] ) || empty( $block['attrs']['memberful_visibility'] ) ) {
			return $block_content;
		}

		// Handle the block visibility conditions.
		switch ( $block['attrs']['memberful_visibility'] ) {
			case 'logged_in':
				$block_content = $this->all_members_visibility( $block['attrs'], $block_content );
				break;

			case 'specific':
				$block_content = $this->specific_plans_visibility( $block['attrs'], $block_content );
				break;

			default:
				break;
		}

		/**
		 * Filters the block content before it is rendered.
		 *
		 * This is run after the block visibility conditions have been applied.
		 *
		 * @since 1.77.0
		 *
		 * @param string $block_content The block content.
		 * @param array $block The block.
		 * @param string $original_block_content The original block content before the visibility conditions were applied.
		 * @return string The block content.
		 */
		$block_content = apply_filters( 'memberful_wp_render_block', $block_content, $block, $original_block_content );

		return $block_content;
	}

	/**
	 * All Members Visibility.
	 *
	 * Any logged in user will see the block.
	 *
	 * @since 1.77.0
	 *
	 * @param array $block_attributes The block data.
	 * @param mixed $block_content The block content.
	 * @return mixed Returns the new block content.
	 */
	public function all_members_visibility( $block_attributes, $block_content ) {
		$is_logged_in = is_user_logged_in();

		if ( $this->should_reverse_visibility_conditions( $block_attributes ) ) {
			// Show to logged out users only.
			return $is_logged_in ? '' : $block_content;
		}

		// Default: show to any logged‑in user only.
		return $is_logged_in ? $block_content : '';
	}

	/**
	 * Specific Plans Visibility.
	 *
	 * Members with the specific plans will see the block,
	 * unless the visibility conditions are reversed or the user does not have any of the specific plans.
	 *
	 * @since 1.77.0
	 *
	 * @param array $block_attributes The block data.
	 * @param mixed $block_content The block content.
	 * @return mixed Returns the new block content.
	 */
	public function specific_plans_visibility( $block_attributes, $block_content ) {
		// Logged out users will not see the block at all.
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$plans = $block_attributes['memberful_visibility_plans'] ?? array();

		// No plans configured - fall back to rendering the block unmodified.
		if ( empty( $plans ) ) {
			return $block_content;
		}

		// Hide the block if the user has any of the specific plans.
		if ( $this->should_reverse_visibility_conditions( $block_attributes ) ) {
			return memberful_wp_user_has_subscription_to_plans( wp_get_current_user()->ID, $plans ) ? '' : $block_content;
		}

		// Show the block if the user has any of the specific plans.
		return memberful_wp_user_has_subscription_to_plans( wp_get_current_user()->ID, $plans ) ? $block_content : '';
	}

	/**
	 * Should the visibility conditions be reversed?
	 *
	 * @since 1.77.0
	 *
	 * @param array $block_attributes The block data.
	 * @return boolean Returns true if the visibility conditions should be reversed.
	 */
	public function should_reverse_visibility_conditions( $block_attributes ) {
		return ! empty( $block_attributes['memberful_visibility_hide'] );
	}
}

/**
 * Initialize the class.
 */
Memberful_WP_Block_Editor::get_instance();


/**
 * Helper Functions.
 */

/**
 * Get the blocks that are excluded from the block visibility controls.
 *
 * @since 1.77.0
 *
 * @return array The blocks that are excluded from the block visibility controls.
 */
function memberful_wp_get_block_visibility_excluded_blocks() {
	return Memberful_WP_Block_Editor::get_block_visibility_excluded_blocks();
}
