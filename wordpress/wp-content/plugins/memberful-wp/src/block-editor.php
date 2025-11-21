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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );

		if ( ! is_admin() ) {
			add_action( 'render_block', array( $this, 'render_block' ), 5, 2 );
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

		/**
		 * Filters the blocks that are excluded from the block visibility controls.
		 *
		 * @since 1.77.0
		 *
		 * @param array $block_visibility_excluded_blocks The blocks that are excluded from the block visibility controls.
		 * @return array The blocks that are excluded from the block visibility controls.
		*/
		return apply_filters( 'memberful_wp_block_visibility_excluded_blocks', array() );
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
	 * Render the block.
	 *
	 * @since 1.77.0
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block.
	 * @return string The block content.
	 */
	public function render_block( $block_content, $block ): string {

		$original_block_content = $block_content;

		// Handle the block visibility conditions.
		if ( ! empty( $block['attrs']['memberfulVisibility'] ) ) {
			switch ( $block['attrs']['memberfulVisibility'] ) {
				case 'all':
					$block_content = $this->all_members_visibility( $block['attrs'], $block_content );
					break;

				case 'active':
					$block_content = $this->active_members_visibility( $block['attrs'], $block_content );
					break;

				case 'specific':
					$block_content = $this->specific_members_visibility( $block['attrs'], $block_content );
					break;

				default:
					break;
			}
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
		if ( ! empty( $block_attributes['memberfulVisibilityHide'] ) && is_user_logged_in() ) {
			return '';
		}

		return $block_content;
	}

	/**
	 * Active Members Visibility.
	 *
	 * Members with any active plan will see the block,
	 * unless the visibility conditions are reversed.
	 *
	 * @since 1.77.0
	 *
	 * @param array $block_attributes The block data.
	 * @param mixed $block_content The block content.
	 * @return mixed Returns the new block content.
	 */
	public function active_members_visibility( $block_attributes, $block_content ) {
		// Logged out users will not see the block at all.
		if ( ! is_user_logged_in() ) {
			return '';
		}

		if ( $this->should_reverse_visibility_conditions( $block_attributes ) ) {
			return is_subscribed_to_any_memberful_plan( wp_get_current_user()->ID ) ? '' : $block_content;
		}

		return is_subscribed_to_any_memberful_plan( wp_get_current_user()->ID ) ? $block_content : '';
	}

	/**
	 * Specific Members Visibility.
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
	public function specific_members_visibility( $block_attributes, $block_content ) {
		// Logged out users will not see the block at all.
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$plans = $block_attributes['memberfulVisibilitySpecificPlans'] ?? array();

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
		return ! empty( $block_attributes['memberfulVisibilityHide'] );
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
