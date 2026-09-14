<?php memberful_wp_render('flash'); ?>
<div id="memberful-wrap" class="wrap">
  <div id="memberful-registration">
    <div class="memberful-sign-up postbox">
      <h1><?php _e( "You'll need a Memberful account.", 'memberful' ); ?></h1>
      <p><?php _e( '<a href="https://memberful.com">Sign up for a free Memberful account</a> and start selling memberships the easy way.', 'memberful' ); ?></p>
    </div>
    <div class="memberful-register-plugin">
      <h3><?php _e( 'Already have an account?', 'memberful' ); ?></h3>
      <form method="POST" action="<?php echo admin_url( 'options-general.php?page=memberful_options&noheader=true' ) ?>">
        <fieldset>
          <textarea placeholder="<?php echo esc_attr( __( 'Paste your WordPress registration key here...', 'memberful' ) ); ?>" name="activation_code"></textarea>
          <button class="button button-primary button-large"><?php _e( 'Connect to Memberful', 'memberful' ); ?></button>
          <input type="hidden" name="action" value="register" />
          <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
        </fieldset>
      </form>
    </div>
  </div>
</div>
