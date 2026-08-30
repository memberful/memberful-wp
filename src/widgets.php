<?php

if ( ! class_exists( 'Memberful_WP_Profile_Widget' ) ) :

  /**
   * Define the Memberful Profile widget.
   */
  class Memberful_WP_Profile_Widget extends WP_Widget {

    /**
     * Register widget.
     *
     * @return void
     */
    public function __construct() {
      parent::__construct(
        'memberful_wp_profile_widget',
        'Memberful Profile',
        array(
          'description' => __( 'Display Memberful profile information.', 'memberful' ),
          'customize_selective_refresh' => true
        )
      );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param  array    $args        Widget arguments.
     * @param  array    $instance    Saved values from database.
     * @return void
     */
    public function widget( $args, $instance ) {
      $title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : 'Memberful Profile';
      $title = apply_filters( 'widget_title', $title );

      $args['title'] = $title;

      $args['signed_in_links'] = array(
        array('href' => memberful_account_url(), 'class' => 'memberful-account-link', 'text' => __( 'Account' )),
        array('href' => memberful_sign_out_url(), 'class' => 'memberful-sign-out-link', 'text' => __( 'Sign out' ))
      );

      $args['signed_out_links'] = array(
        array('href' => memberful_sign_in_url( is_ssl() ? 'https' : 'http' ), 'class' => 'memberful-sign-in-link', 'text' => __( 'Sign in' )),
      );

      $args = apply_filters( 'memberful_wp_widget_args', $args );

      memberful_wp_render( 'profile_widget', $args );
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @param  array     $new_instance     Values just sent to be saved.
     * @param  array     $old_instance     Previously saved values from database.
     * @return array                       Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
      $instance = array();
      $instance['title'] = strip_tags( $new_instance['title'] );

      return $instance;
    }

    /**
     * Form to define instance variables.
     *
     * @param  array    $instance    Previously saved values from database.
     * @return void
     */
    public function form( $instance ) {
      // Get title
      $title = ( isset( $instance[ 'title' ] ) ) ? $instance[ 'title' ] : 'Your account';
?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id( 'title' )); ?>"><?php _e( 'Title:' ); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id( 'title' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
<?php
    }
  }

/**
 * Register the Memberful WP Profile Widget.
 *
 * @return void
 */
function memberful_wp_register_wp_profile_widget() {
  register_widget( 'memberful_wp_profile_widget' );
}

add_action( 'widgets_init', 'memberful_wp_register_wp_profile_widget' );

/**
 * Add a stylesheet for the Memberful widget if it is active
 *
 * @return void
 */
function memberful_wp_add_stylesheet_if_action() {
  // Verify that the widget is active before adding stylesheet
  if ( ! is_active_widget( false, false, 'memberful_wp_profile_widget' ) )
    return;

  // Allow a filter to disable the stylesheet
  $add_stylesheet = apply_filters( 'memberful_wp_profile_widget_add_stylesheet', true );

  // Add the stylesheet
  if ( true === $add_stylesheet ) {
    wp_enqueue_style(
      'memberful-wp-profile-widget',
      MEMBERFUL_URL . '/stylesheets/widget.css',
      array(),
      MEMBERFUL_VERSION
    );
  }
}

function memberful_wp_format_widget_links( $links ) {
  foreach ( $links as $key => $link ) {
    $links[$key] = '<a href="'.esc_url($link['href']).'" class="'.esc_attr($link['class']).'">'.esc_html($link['text']).'</a>';
  }

  return implode( ' <span class="memberful-links-separator">|</span> ', $links );
}

add_action( 'wp_enqueue_scripts', 'memberful_wp_add_stylesheet_if_action' );

endif;
