<?php
/**
 * Paywall builder structured-fields panel.
 *
 * @package memberful-wp
 *
 * @var array $paywall_config
 * @var bool  $is_active
 */

$metering_enabled = class_exists( 'Memberful_Metering_Config' ) && ! empty( Memberful_Metering_Config::get()['enabled'] );
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
        <span class="memberful-paywall-builder__field-row">
          <label for="memberful-paywall-heading"><?php esc_html_e( 'Headline', 'memberful' ); ?></label>
          <span class="memberful-paywall-builder__counter" data-counter-for="memberful-paywall-heading" data-max="60"><?php echo (int) mb_strlen( $paywall_config['heading'] ); ?>/60</span>
        </span>
        <input id="memberful-paywall-heading" type="text" name="memberful_paywall[heading]" value="<?php echo esc_attr( $paywall_config['heading'] ); ?>" maxlength="60">
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

      <fieldset class="memberful-paywall-builder__field memberful-paywall-builder__button-shape">
        <legend class="memberful-paywall-builder__button-shape-label"><?php esc_html_e( 'Button shape', 'memberful' ); ?></legend>
        <div class="memberful-paywall-builder__segmented">
          <label class="memberful-paywall-builder__segmented-option">
            <input type="radio" name="memberful_paywall[button_shape]" value="square" <?php checked( 'square', $paywall_config['button_shape'] ); ?>>
            <span class="memberful-paywall-builder__segmented-option-inner">
              <svg width="20" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="5" width="14" height="14" rx="0"/></svg>
              <?php esc_html_e( 'Square', 'memberful' ); ?>
            </span>
          </label>
          <label class="memberful-paywall-builder__segmented-option">
            <input type="radio" name="memberful_paywall[button_shape]" value="rounded" <?php checked( 'rounded', $paywall_config['button_shape'] ); ?>>
            <span class="memberful-paywall-builder__segmented-option-inner">
              <svg width="20" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="7" width="20" height="10" rx="3"/></svg>
              <?php esc_html_e( 'Rounded', 'memberful' ); ?>
            </span>
          </label>
          <label class="memberful-paywall-builder__segmented-option">
            <input type="radio" name="memberful_paywall[button_shape]" value="pill" <?php checked( 'pill', $paywall_config['button_shape'] ); ?>>
            <span class="memberful-paywall-builder__segmented-option-inner">
              <svg width="20" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="7" width="20" height="10" rx="5"/></svg>
              <?php esc_html_e( 'Pill', 'memberful' ); ?>
            </span>
          </label>
        </div>
      </fieldset>

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
      $brand_palette = array_values( array_unique( $brand_palette ) );

      if ( empty( $brand_palette ) ) {
        $brand_palette = array( '#0065F4', '#0B172B', '#F00013', '#00A63D', '#A02AF4', '#FD4900' );
      }

      $background_palette = array( '#ffffff', '#f5f5f4', '#0f172a' );

      $brand_current       = sanitize_hex_color( (string) $paywall_config['brand_color'] );
      $brand_lower         = $brand_current ? strtolower( $brand_current ) : '';
      $brand_palette_lower = array_map( 'strtolower', $brand_palette );
      $brand_is_preset     = '' !== $brand_lower && in_array( $brand_lower, $brand_palette_lower, true );
      $brand_custom_value  = $brand_current ? $brand_current : $brand_palette[0];

      $background_current       = sanitize_hex_color( (string) $paywall_config['background_color'] );
      $background_lower         = $background_current ? strtolower( $background_current ) : '';
      $background_palette_lower = array_map( 'strtolower', $background_palette );
      $background_is_preset     = '' !== $background_lower && in_array( $background_lower, $background_palette_lower, true );
      $background_custom_value  = $background_current ? $background_current : $background_palette[0];
      ?>
      <fieldset class="memberful-paywall-builder__field memberful-paywall-builder__color-field" data-color-field>
        <legend class="memberful-paywall-builder__color-label"><?php esc_html_e( 'Accent color', 'memberful' ); ?></legend>
        <div class="memberful-paywall-builder__color-row">
          <div class="memberful-paywall-builder__swatches" role="group" aria-label="<?php esc_attr_e( 'Accent color presets', 'memberful' ); ?>">
            <?php foreach ( $brand_palette as $brand_hex ) : ?>
              <?php $is_selected = '' !== $brand_lower && 0 === strcasecmp( $brand_hex, $brand_current ); ?>
              <button type="button" class="memberful-paywall-builder__swatch<?php echo $is_selected ? ' is-selected' : ''; ?>" data-color="<?php echo esc_attr( $brand_hex ); ?>" aria-label="<?php echo esc_attr( $brand_hex ); ?>" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>" style="background-color: <?php echo esc_attr( $brand_hex ); ?>"></button>
            <?php endforeach; ?>
            <label class="memberful-paywall-builder__swatch memberful-paywall-builder__swatch--custom<?php echo ( '' !== $brand_lower && ! $brand_is_preset ) ? ' is-selected' : ''; ?>"<?php if ( '' !== $brand_lower && ! $brand_is_preset ) echo ' style="--memberful-custom-swatch-color: ' . esc_attr( $brand_current ) . '"'; ?>>
              <span class="screen-reader-text"><?php esc_html_e( 'Custom accent color', 'memberful' ); ?></span>
              <svg class="memberful-paywall-builder__swatch-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5v14"/></svg>
              <input type="color" value="<?php echo esc_attr( $brand_custom_value ); ?>">
            </label>
          </div>
          <button type="button" class="memberful-paywall-builder__color-reset"><?php esc_html_e( 'Reset', 'memberful' ); ?></button>
        </div>
        <input type="hidden" id="memberful-paywall-brand-color" class="memberful-paywall-builder__color-input" name="memberful_paywall[brand_color]" value="<?php echo esc_attr( $brand_current ); ?>">
      </fieldset>
      <fieldset class="memberful-paywall-builder__field memberful-paywall-builder__color-field" data-color-field>
        <legend class="memberful-paywall-builder__color-label"><?php esc_html_e( 'Background color', 'memberful' ); ?></legend>
        <div class="memberful-paywall-builder__color-row">
          <div class="memberful-paywall-builder__swatches" role="group" aria-label="<?php esc_attr_e( 'Background color presets', 'memberful' ); ?>">
            <?php foreach ( $background_palette as $background_hex ) : ?>
              <?php $is_selected = '' !== $background_lower && 0 === strcasecmp( $background_hex, $background_current ); ?>
              <button type="button" class="memberful-paywall-builder__swatch<?php echo $is_selected ? ' is-selected' : ''; ?>" data-color="<?php echo esc_attr( $background_hex ); ?>" aria-label="<?php echo esc_attr( $background_hex ); ?>" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>" style="background-color: <?php echo esc_attr( $background_hex ); ?>"></button>
            <?php endforeach; ?>
            <label class="memberful-paywall-builder__swatch memberful-paywall-builder__swatch--custom<?php echo ( '' !== $background_lower && ! $background_is_preset ) ? ' is-selected' : ''; ?>"<?php if ( '' !== $background_lower && ! $background_is_preset ) echo ' style="--memberful-custom-swatch-color: ' . esc_attr( $background_current ) . '"'; ?>>
              <span class="screen-reader-text"><?php esc_html_e( 'Custom background color', 'memberful' ); ?></span>
              <svg class="memberful-paywall-builder__swatch-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5v14"/></svg>
              <input type="color" value="<?php echo esc_attr( $background_custom_value ); ?>">
            </label>
          </div>
          <button type="button" class="memberful-paywall-builder__color-reset"><?php esc_html_e( 'Reset', 'memberful' ); ?></button>
        </div>
        <input type="hidden" id="memberful-paywall-background-color" class="memberful-paywall-builder__color-input" name="memberful_paywall[background_color]" value="<?php echo esc_attr( $background_current ); ?>">
        <span class="description"><?php esc_html_e( 'Text color adjusts automatically for contrast.', 'memberful' ); ?></span>
      </fieldset>

      <?php if ( $metering_enabled ) : ?>
        <fieldset class="memberful-paywall-builder__field memberful-paywall-builder__button-set">
          <legend class="memberful-paywall-builder__button-set-label"><?php esc_html_e( 'Free registration button', 'memberful' ); ?></legend>
          <span class="description memberful-paywall-builder__button-set-description"><?php esc_html_e( 'Shown instead of the subscribe button when a logged-out visitor hits the metered limit. Free members get their own allowance, so registering always unlocks more articles.', 'memberful' ); ?></span>
          <p class="memberful-paywall-builder__field">
            <label for="memberful-paywall-free-button-label"><?php esc_html_e( 'Button label', 'memberful' ); ?></label>
            <input id="memberful-paywall-free-button-label" type="text" name="memberful_paywall[free_button_label]" value="<?php echo esc_attr( $paywall_config['free_button_label'] ); ?>">
          </p>
          <p class="memberful-paywall-builder__field">
            <label for="memberful-paywall-free-button-url"><?php esc_html_e( 'Button URL', 'memberful' ); ?></label>
            <input id="memberful-paywall-free-button-url" type="url" name="memberful_paywall[free_button_url]" value="<?php echo esc_attr( $paywall_config['free_button_url'] ); ?>" placeholder="<?php echo esc_attr( memberful_registration_page_url() ); ?>">
            <span class="description"><?php esc_html_e( 'Leave blank to use your Memberful registration page.', 'memberful' ); ?></span>
          </p>
        </fieldset>

        <fieldset class="memberful-paywall-builder__field memberful-paywall-builder__button-set">
          <legend class="memberful-paywall-builder__button-set-label"><?php esc_html_e( 'Free-view count', 'memberful' ); ?></legend>
          <label class="memberful-paywall-builder__checkline">
            <input type="checkbox" id="memberful-paywall-show-counter" name="memberful_paywall[show_counter]" value="1" <?php checked( ! empty( $paywall_config['show_counter'] ) ); ?>>
            <?php esc_html_e( 'Show how many free articles the visitor has used', 'memberful' ); ?>
          </label>
          <p class="memberful-paywall-builder__field">
            <label for="memberful-paywall-counter-template"><?php esc_html_e( 'Message', 'memberful' ); ?></label>
            <input id="memberful-paywall-counter-template" type="text" name="memberful_paywall[counter_template]" value="<?php echo esc_attr( $paywall_config['counter_template'] ); ?>">
            <span class="description"><?php esc_html_e( 'Only appears when the meter has blocked the visitor. Use {limit} for their number of free articles.', 'memberful' ); ?></span>
          </p>
        </fieldset>
      <?php else : ?>
        <input type="hidden" name="memberful_paywall[free_button_label]" value="<?php echo esc_attr( $paywall_config['free_button_label'] ); ?>">
        <input type="hidden" name="memberful_paywall[free_button_url]" value="<?php echo esc_attr( $paywall_config['free_button_url'] ); ?>">
        <?php if ( ! empty( $paywall_config['show_counter'] ) ) : ?>
          <input type="hidden" name="memberful_paywall[show_counter]" value="1">
        <?php endif; ?>
        <input type="hidden" name="memberful_paywall[counter_template]" value="<?php echo esc_attr( $paywall_config['counter_template'] ); ?>">
      <?php endif; ?>

      <fieldset class="memberful-paywall-builder__field memberful-paywall-builder__button-set">
        <legend class="memberful-paywall-builder__button-set-label"><?php esc_html_e( 'Subscribe button', 'memberful' ); ?></legend>
        <p class="memberful-paywall-builder__field">
          <label for="memberful-paywall-button-label"><?php esc_html_e( 'Button label', 'memberful' ); ?></label>
          <input id="memberful-paywall-button-label" type="text" name="memberful_paywall[button_label]" value="<?php echo esc_attr( $paywall_config['button_label'] ); ?>">
        </p>
        <p class="memberful-paywall-builder__field">
          <label for="memberful-paywall-subscribe-url"><?php esc_html_e( 'Button URL', 'memberful' ); ?></label>
          <input id="memberful-paywall-subscribe-url" type="url" name="memberful_paywall[subscribe_url]" value="<?php echo esc_attr( $paywall_config['subscribe_url'] ); ?>" placeholder="<?php echo esc_attr( memberful_registration_page_url() ); ?>">
          <span class="description"><?php esc_html_e( 'Leave blank to use your Memberful registration page (only works if free registration is enabled). Otherwise link to your checkout or pricing page.', 'memberful' ); ?></span>
        </p>
      </fieldset>

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
      sandbox="allow-same-origin"
    ></iframe>
  </div>
</div>
