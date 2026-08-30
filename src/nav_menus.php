<?php

add_action( "admin_menu", "memberful_add_nav_menu_items" );

function memberful_add_nav_menu_items() {
  add_meta_box( "add-memberful-links", __( "Memberful Links" ), "memberful_nav_menu_items_meta_box", "nav-menus", "side", "low" );
}

function memberful_nav_menu_items_meta_box() {
  global $nav_menu_selected_id;
  ?>
  <div data-behaviour="memberful_nav_menu_links">
    <ul>
      <?php memberful_nav_menu_link_item(memberful_sign_in_url(), "Sign in") ?>
      <?php memberful_nav_menu_link_item(memberful_sign_out_url(), "Sign out") ?>
      <?php memberful_nav_menu_link_item(memberful_account_url(), "Account") ?>
    </ul>

    <p class="button-controls wp-clearfix">
      <span class="add-to-menu">
        <input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button submit-add-to-menu right" value="<?php esc_attr_e("Add to Menu"); ?>" name="add-custom-menu-item" data-behaviour="add_link" />
        <span class="spinner"></span>
      </span>
    </p>
  </div>
  <?php
}

function memberful_nav_menu_link_item($url, $label) { ?>
  <li>
    <label class="menu-item-title">
      <input type="checkbox" name="memberful_link" class="menu-item-checkbox" data-url="<?php echo esc_attr($url); ?>" data-label="<?php echo esc_attr($label); ?>"> <?php echo esc_html($label); ?>
    </label>
  </li>
  <?php
}
