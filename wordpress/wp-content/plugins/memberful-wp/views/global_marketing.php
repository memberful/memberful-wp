<div class="wrap">
  <?php memberful_wp_render( 'option_tabs', array( 'active' => 'global_marketing' ) ); ?>
  <?php memberful_wp_render( 'flash' ); ?>

  <form method="POST" action="<?php echo esc_url($form_target); ?>">
  <?php memberful_wp_nonce_field( 'memberful_options' ); ?>

  <div class="memberful-bulk-apply-box">
    <h3><?php _e( 'Global marketing content', 'memberful' ); ?></h3>
    <p>
      <label for="use_global_marketing_checkbox">
      <input id="use_global_marketing_checkbox" class="memberful-label__checkbox--multiline" type="checkbox" name="memberful_use_global_marketing" 
      <?php
      if ( $use_global_marketing ) :
        ?>
        checked="checked"<?php endif; ?>>
        <span class="memberful-label__text--multiline"><strong><?php _e('Turn on global marketing content', 'memberful') ?></strong>
          <?php _e(' This setting allows you to create default marketing content to be displayed for all locked posts, pages, categories and tags.', 'memberful');?>
        </span>
      </label>
    </p>
    <div id="global_marketing_options" data-depends-on="use_global_marketing_checkbox" data-depends-value="1">
      <label for="global_marketing_override_radio_true">
      <input id="global_marketing_override_radio_true" type="radio" name="memberful_global_marketing_override" value="1" 
      <?php
      if ( $global_marketing_override ) {
        echo 'checked="checked"';}
      ?>
      >
      Override all marketing content.
      </label>
      <label for="global_marketing_override_radio_false">
      <input id="global_marketing_override_radio_false" type="radio" name="memberful_global_marketing_override" value="0"
      <?php
      if ( ! $global_marketing_override ) {
        echo 'checked="checked"';}
      ?>
      >
      Only use the global marketing content when other content doesn't exist.
      </label>
      <hr>
  
      <div id="global_marketing_snippet_options">

      <input id="use_global_snippets_checkbox" class="memberful-label__checkbox--multiline" type="checkbox" name="memberful_use_global_snippets" 
      <?php
      if ( $use_global_snippets ) :
        ?>
        checked="checked"<?php endif; ?>>
        <span class="memberful-label__text--multiline"><strong>Automatically pull an excerpt from each post.</strong>
          <?php echo esc_html(' Memberful will pull the first two paragraphs from each protected post to use as marketing content for logged out visitors.'
          .' This feature requires <p> tags in your posts to detect which content to use.');?>
      </div>

    </div>


    <div class='global-marketing-content' data-depends-on="use_global_marketing_checkbox" data-depends-value="1">
      <?php wp_editor( $global_marketing_content, $editor_id = 'memberful_global_marketing_content', $settings = array() ); ?>

    </div>
  </div>
  <div id="global_content_required">
    <?php _e('When using global marketing content, the marketing content box cannot be empty.', 'memberful'); ?>
  </div>
  <button type="submit" name="save_global_marketing" class="button button-primary">Save Changes</button>
  </form>
</div>
