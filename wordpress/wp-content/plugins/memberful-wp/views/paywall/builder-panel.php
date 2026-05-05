<?php
/**
 * Paywall builder structured-fields panel.
 *
 * @package memberful-wp
 *
 * @var array $paywall_config
 * @var bool  $is_active
 */

$features_textarea = implode( "\n", (array) $paywall_config['features'] );
?>
<div class="memberful-paywall-builder__panel" data-panel="builder"<?php if ( ! $is_active ) echo ' style="display:none"'; ?>>
  <fieldset class="memberful-paywall-builder__layout">
    <legend class="memberful-paywall-builder__section-heading"><?php esc_html_e( 'Choose a template', 'memberful' ); ?></legend>
    <div class="memberful-paywall-builder__template-grid">
      <label class="memberful-paywall-builder__template-card">
        <input type="radio" name="memberful_paywall[layout]" value="simple" <?php checked( 'simple', $paywall_config['layout'] ); ?>>
        <span class="memberful-paywall-builder__template-card-inner">
          <span class="memberful-paywall-builder__template-thumb memberful-paywall-builder__template-thumb--simple" aria-hidden="true">
            <span class="memberful-paywall-builder__thumb-line"></span>
            <span class="memberful-paywall-builder__thumb-button"></span>
          </span>
          <span class="memberful-paywall-builder__template-meta">
            <strong><?php esc_html_e( 'Minimal', 'memberful' ); ?></strong>
            <small><?php esc_html_e( 'Clean divider with text and button', 'memberful' ); ?></small>
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
      <label class="memberful-paywall-builder__template-card">
        <input type="radio" name="memberful_paywall[layout]" value="banner" <?php checked( 'banner', $paywall_config['layout'] ); ?>>
        <span class="memberful-paywall-builder__template-card-inner">
          <span class="memberful-paywall-builder__template-thumb memberful-paywall-builder__template-thumb--banner" aria-hidden="true">
            <span class="memberful-paywall-builder__thumb-line"></span>
            <span class="memberful-paywall-builder__thumb-button"></span>
          </span>
          <span class="memberful-paywall-builder__template-meta">
            <strong><?php esc_html_e( 'Banner', 'memberful' ); ?></strong>
            <small><?php esc_html_e( 'Full-width dark banner', 'memberful' ); ?></small>
          </span>
        </span>
      </label>
    </div>
  </fieldset>

  <div class="memberful-paywall-builder__split">
    <div class="memberful-paywall-builder__customize">
      <h3 class="memberful-paywall-builder__section-heading"><?php esc_html_e( 'Customize', 'memberful' ); ?></h3>

      <div class="memberful-paywall-builder__field memberful-paywall-builder__field--paired">
        <p class="memberful-paywall-builder__field-main">
          <label for="memberful-paywall-heading"><?php esc_html_e( 'Title', 'memberful' ); ?></label>
          <input id="memberful-paywall-heading" type="text" name="memberful_paywall[heading]" value="<?php echo esc_attr( $paywall_config['heading'] ); ?>">
        </p>
        <p class="memberful-paywall-builder__field-aside">
          <label for="memberful-paywall-heading-tag"><?php esc_html_e( 'Style', 'memberful' ); ?></label>
          <select id="memberful-paywall-heading-tag" name="memberful_paywall[heading_tag]">
            <option value="h1" <?php selected( 'h1', $paywall_config['heading_tag'] ); ?>>H1</option>
            <option value="h2" <?php selected( 'h2', $paywall_config['heading_tag'] ); ?>>H2</option>
            <option value="h3" <?php selected( 'h3', $paywall_config['heading_tag'] ); ?>>H3</option>
          </select>
        </p>
      </div>

      <p class="memberful-paywall-builder__field">
        <label for="memberful-paywall-subheading"><?php esc_html_e( 'Description', 'memberful' ); ?></label>
        <textarea id="memberful-paywall-subheading" rows="3" name="memberful_paywall[subheading]"><?php echo esc_textarea( $paywall_config['subheading'] ); ?></textarea>
      </p>

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

      <p class="memberful-paywall-builder__field">
        <label for="memberful-paywall-features"><?php esc_html_e( 'Features', 'memberful' ); ?></label>
        <textarea id="memberful-paywall-features" rows="4" name="memberful_paywall[features]"><?php echo esc_textarea( $features_textarea ); ?></textarea>
        <span class="description"><?php esc_html_e( 'One feature per line. Each line renders as a check-marked list item.', 'memberful' ); ?></span>
      </p>

      <p class="memberful-paywall-builder__field">
        <label for="memberful-paywall-brand-color"><?php esc_html_e( 'Brand colour', 'memberful' ); ?></label>
        <input id="memberful-paywall-brand-color" type="text" class="memberful-paywall-builder__color" name="memberful_paywall[brand_color]" value="<?php echo esc_attr( $paywall_config['brand_color'] ); ?>">
      </p>

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
</div>
