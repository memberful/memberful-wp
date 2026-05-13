<?php
/**
 * Paywall builder structured-fields panel.
 *
 * @package memberful-wp
 *
 * @var array $paywall_config
 * @var bool  $is_active
 */

?>
<div class="memberful-paywall-builder__panel" data-panel="builder"<?php if ( ! $is_active ) echo ' style="display:none"'; ?>>
  <div class="memberful-paywall-builder__settings">
    <fieldset class="memberful-paywall-builder__layout">
      <legend class="memberful-paywall-builder__section-heading"><?php esc_html_e( '1. Choose a template', 'memberful' ); ?></legend>
      <div class="memberful-paywall-builder__template-grid">
        <label class="memberful-paywall-builder__template-card">
          <input type="radio" name="memberful_paywall[layout]" value="inline" <?php checked( 'inline', $paywall_config['layout'] ); ?>>
          <span class="memberful-paywall-builder__template-card-inner">
            <span class="memberful-paywall-builder__template-thumb memberful-paywall-builder__template-thumb--inline" aria-hidden="true">
              <span class="memberful-paywall-builder__thumb-line"></span>
              <span class="memberful-paywall-builder__thumb-button"></span>
            </span>
            <span class="memberful-paywall-builder__template-meta">
              <strong><?php esc_html_e( 'Inline', 'memberful' ); ?></strong>
              <small><?php esc_html_e( 'Flows with your content', 'memberful' ); ?></small>
            </span>
          </span>
        </label>
        <label class="memberful-paywall-builder__template-card">
          <input type="radio" name="memberful_paywall[layout]" value="card" <?php checked( 'card', $paywall_config['layout'] ); ?>>
          <span class="memberful-paywall-builder__template-card-inner">
            <span class="memberful-paywall-builder__template-thumb memberful-paywall-builder__template-thumb--card" aria-hidden="true">
              <span class="memberful-paywall-builder__thumb-lock"></span>
            </span>
            <span class="memberful-paywall-builder__template-meta">
              <strong><?php esc_html_e( 'Card', 'memberful' ); ?></strong>
              <small><?php esc_html_e( 'Centered card with lock icon', 'memberful' ); ?></small>
            </span>
          </span>
        </label>
      </div>
    </fieldset>

    <div class="memberful-paywall-builder__customize">
      <h3 class="memberful-paywall-builder__section-heading"><?php esc_html_e( '2. Customize the content', 'memberful' ); ?></h3>

      <p class="memberful-paywall-builder__field">
        <label for="memberful-paywall-heading"><?php esc_html_e( 'Title', 'memberful' ); ?></label>
        <input id="memberful-paywall-heading" type="text" name="memberful_paywall[heading]" value="<?php echo esc_attr( $paywall_config['heading'] ); ?>" maxlength="80">
      </p>

      <p class="memberful-paywall-builder__field">
        <label for="memberful-paywall-subheading"><?php esc_html_e( 'Description', 'memberful' ); ?></label>
        <textarea id="memberful-paywall-subheading" rows="3" name="memberful_paywall[subheading]"><?php echo esc_textarea( $paywall_config['subheading'] ); ?></textarea>
      </p>

      <fieldset class="memberful-paywall-builder__field memberful-paywall-builder__benefits">
        <legend class="memberful-paywall-builder__benefits-label"><?php esc_html_e( 'What subscribers get', 'memberful' ); ?></legend>
        <div class="memberful-paywall-builder__benefit-list" id="memberful-paywall-benefits">
          <?php foreach ( (array) $paywall_config['features'] as $feature ) : ?>
            <div class="memberful-paywall-builder__benefit">
              <svg class="memberful-paywall-builder__benefit-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
              <label class="memberful-paywall-builder__benefit-label">
                <span class="screen-reader-text"><?php esc_html_e( 'Benefit', 'memberful' ); ?></span>
                <input type="text" class="memberful-paywall-builder__benefit-input" name="memberful_paywall[features][]" value="<?php echo esc_attr( $feature ); ?>" placeholder="<?php esc_attr_e( 'e.g. Ad-free listening', 'memberful' ); ?>">
              </label>
              <button type="button" class="memberful-paywall-builder__benefit-remove" aria-label="<?php esc_attr_e( 'Remove benefit', 'memberful' ); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="memberful-paywall-builder__benefit-add" id="memberful-paywall-benefit-add">+ <?php esc_html_e( 'Add benefit', 'memberful' ); ?></button>
        <template id="memberful-paywall-benefit-template">
          <div class="memberful-paywall-builder__benefit">
            <svg class="memberful-paywall-builder__benefit-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
            <label class="memberful-paywall-builder__benefit-label">
              <span class="screen-reader-text"><?php esc_html_e( 'Benefit', 'memberful' ); ?></span>
              <input type="text" class="memberful-paywall-builder__benefit-input" name="memberful_paywall[features][]" value="" placeholder="<?php esc_attr_e( 'e.g. Ad-free listening', 'memberful' ); ?>">
            </label>
            <button type="button" class="memberful-paywall-builder__benefit-remove" aria-label="<?php esc_attr_e( 'Remove benefit', 'memberful' ); ?>">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
        </template>
      </fieldset>

      <div class="memberful-paywall-builder__field memberful-paywall-builder__field--paired">
        <p class="memberful-paywall-builder__field-main">
          <label for="memberful-paywall-button-label"><?php esc_html_e( 'Button label', 'memberful' ); ?></label>
          <input id="memberful-paywall-button-label" type="text" name="memberful_paywall[button_label]" value="<?php echo esc_attr( $paywall_config['button_label'] ); ?>">
        </p>
        <p class="memberful-paywall-builder__field-aside">
          <label for="memberful-paywall-button-shape"><?php esc_html_e( 'Shape', 'memberful' ); ?></label>
          <select id="memberful-paywall-button-shape" name="memberful_paywall[button_shape]">
            <option value="pill" <?php selected( 'pill', $paywall_config['button_shape'] ); ?>><?php esc_html_e( 'Pill', 'memberful' ); ?></option>
            <option value="rounded" <?php selected( 'rounded', $paywall_config['button_shape'] ); ?>><?php esc_html_e( 'Rounded', 'memberful' ); ?></option>
            <option value="square" <?php selected( 'square', $paywall_config['button_shape'] ); ?>><?php esc_html_e( 'Square', 'memberful' ); ?></option>
          </select>
        </p>
      </div>

      <?php
      $palette_settings = wp_get_global_settings( array( 'color', 'palette' ) );
      $palette_entries  = array();
      if ( is_array( $palette_settings ) ) {
        if ( isset( $palette_settings['theme'] ) ) {
          $palette_entries = (array) $palette_settings['theme'];
        } elseif ( isset( $palette_settings[0] ) ) {
          $palette_entries = $palette_settings;
        }
      }

      $brand_palette = array();
      foreach ( $palette_entries as $palette_entry ) {
        if ( is_array( $palette_entry ) && isset( $palette_entry['color'] ) ) {
          $palette_hex = sanitize_hex_color( $palette_entry['color'] );
          if ( $palette_hex ) {
            $brand_palette[] = $palette_hex;
          }
        }
      }

      if ( empty( $brand_palette ) ) {
        $brand_palette = array( '#2563eb', '#0f172a', '#dc2626', '#16a34a', '#9333ea', '#ea580c' );
      }

      $background_palette = array( '#ffffff', '#f5f5f4', '#0f172a' );
      ?>
      <div class="memberful-paywall-builder__field memberful-paywall-builder__colors-row">
        <div>
          <label for="memberful-paywall-brand-color"><?php esc_html_e( 'Accent color', 'memberful' ); ?></label>
          <input id="memberful-paywall-brand-color" type="text" class="memberful-paywall-builder__color" name="memberful_paywall[brand_color]" value="<?php echo esc_attr( $paywall_config['brand_color'] ); ?>" data-palettes="<?php echo esc_attr( wp_json_encode( $brand_palette ) ); ?>">
        </div>
        <div>
          <label for="memberful-paywall-background-color"><?php esc_html_e( 'Background color', 'memberful' ); ?></label>
          <input id="memberful-paywall-background-color" type="text" class="memberful-paywall-builder__color" name="memberful_paywall[background_color]" value="<?php echo esc_attr( $paywall_config['background_color'] ); ?>" data-palettes="<?php echo esc_attr( wp_json_encode( $background_palette ) ); ?>">
          <span class="description"><?php esc_html_e( 'Text color adjusts automatically for contrast.', 'memberful' ); ?></span>
        </div>
      </div>

      <p class="memberful-paywall-builder__field">
        <label for="memberful-paywall-subscribe-url"><?php esc_html_e( 'Subscribe URL', 'memberful' ); ?></label>
        <input id="memberful-paywall-subscribe-url" type="url" name="memberful_paywall[subscribe_url]" value="<?php echo esc_attr( $paywall_config['subscribe_url'] ); ?>" placeholder="<?php echo esc_attr( memberful_registration_page_url() ); ?>">
        <span class="description"><?php esc_html_e( 'Leave blank to use your Memberful registration page.', 'memberful' ); ?></span>
      </p>

      <p class="memberful-paywall-builder__field">
        <label for="memberful-paywall-signin-url"><?php esc_html_e( 'Sign-in URL', 'memberful' ); ?></label>
        <input id="memberful-paywall-signin-url" type="url" name="memberful_paywall[sign_in_url]" value="<?php echo esc_attr( $paywall_config['sign_in_url'] ); ?>" placeholder="<?php echo esc_attr( memberful_sign_in_url() ); ?>">
        <span class="description"><?php esc_html_e( 'Leave blank to use your Memberful sign-in link.', 'memberful' ); ?></span>
      </p>
    </div>
  </div>

  <div class="memberful-paywall-builder__preview">
    <h3 class="memberful-paywall-builder__section-heading"><?php esc_html_e( 'Preview', 'memberful' ); ?></h3>
    <iframe
      id="memberful-paywall-preview"
      class="memberful-paywall-builder__preview-frame"
      title="<?php esc_attr_e( 'Paywall preview', 'memberful' ); ?>"
      srcdoc="<?php echo esc_attr( Memberful_Paywall_Preview::document( $paywall_config ) ); ?>"
      sandbox
    ></iframe>
  </div>
</div>
