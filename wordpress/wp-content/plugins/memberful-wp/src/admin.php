<?php

require_once MEMBERFUL_DIR . '/src/options.php';
require_once MEMBERFUL_DIR . '/src/metabox.php';
require_once MEMBERFUL_DIR . '/src/ad-control.php';

add_action( 'admin_head',            'memberful_wp_announce_plans_and_download_in_head' );
add_action( 'admin_menu',            'memberful_wp_menu' );
add_action( 'admin_init',            'memberful_wp_register_options' );
add_action( 'admin_init',            'memberful_wp_activation_redirect' );
add_action( 'admin_init',            'memberful_wp_plugin_migrate_db' );
add_action( 'admin_enqueue_scripts', 'memberful_wp_admin_enqueue_scripts' );
add_filter( 'display_post_states',   'memberful_wp_add_protected_state_to_post_list', 10, 2 );

/**
 * Ensures the database is up to date
 */
function memberful_wp_plugin_migrate_db() {
  global $wpdb;

  $db_version = get_option( 'memberful_db_version', 0 );

  if ( $db_version < 1 ) {
    $result = $wpdb->query(
      'CREATE TABLE `'.Memberful_User_Mapping_Repository::table().'`(
        `wp_user_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL UNIQUE KEY,
        `member_id` BIGINT(20) UNSIGNED NOT NULL PRIMARY KEY,
        `refresh_token` VARCHAR( 45 ) NULL DEFAULT NULL,
        `last_sync_at` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0)'
      );

    if ( $result === false ) {
      echo 'Could not create the memberful mapping table. Please email info@memberful.com.';
      $wpdb->print_error();
      exit();
    }

    $columns = $wpdb->get_results( 'SHOW COLUMNS FROM `'.$wpdb->users.'` WHERE `Field` LIKE "memberful_%"' );

    if ( ! empty( $columns ) ) {
      $wpdb->query(
        'INSERT INTO `'.Memberful_User_Mapping_Repository::table().'` '.
        '(`member_id`, `wp_user_id`, `refresh_token`, `last_sync_at`) '.
        'SELECT `memberful_member_id`, `ID`, `memberful_refresh_token`, UNIX_TIMESTAMP() '.
        'FROM `'.$wpdb->users.'` '.
        'WHERE `memberful_member_id` IS NOT NULL'
      );

      $wpdb->query(
        'ALTER TABLE `'.$wpdb->users.'`
        DROP COLUMN `memberful_member_id`,
        DROP COLUMN `memberful_refresh_token`'
      );
    }

    $db_version = 1;
  }

  if ( $db_version < 2 ) {
    $result = $wpdb->query('DELETE FROM '.Memberful_User_Mapping_Repository::table().' WHERE wp_user_id=0');

    if ( $result === false ) {
      echo 'Could not trim empty users from mapping table\n';
      $wpdb->print_error();
      exit();
    }

    $db_version = 2;
  }

  if ( $db_version < 3 ) {
    if ( get_option( 'memberful_use_global_marketing' ) ) {
      add_option( 'memberful_use_global_snippets', TRUE );
    } else {
      $legacy_default_marketing_content = get_option( 'memberful_default_marketing_content', NULL );

      if ( !empty( $legacy_default_marketing_content ) ) {
        update_option( 'memberful_global_marketing_content', $legacy_default_marketing_content );
        update_option( 'memberful_global_marketing_override', FALSE );
        update_option( 'memberful_use_global_marketing', TRUE );
        update_option( 'memberful_use_global_snippets', FALSE );

        delete_option( 'memberful_default_marketing_content' );
      }
    }

    $db_version = 3;
  }

  update_option( 'memberful_db_version', $db_version );
}

/**
 * Redirects to the Memberful plugin options page after activation
 *
 */
function memberful_wp_activation_redirect() {
  if ( get_option( 'memberful_wp_activation_redirect', false ) ) {
    delete_option( 'memberful_wp_activation_redirect' );

    if ( !isset( $_GET['activate-multi'] ) ) {
      wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
    }
  }
}

/**
 * Add an options page
 */
function memberful_wp_menu() {
  add_options_page( 'Memberful', 'Memberful', 'manage_options', 'memberful_options', 'memberful_wp_options' );
}

function memberful_wp_admin_enqueue_scripts() {
  $screen = get_current_screen();

  if ( strpos( 'memberful', $screen->id ) !== null ) {
    wp_enqueue_style(
      'memberful-admin',
      plugins_url( 'stylesheets/admin.css' , dirname(__FILE__) )
    );
    wp_enqueue_script(
      'memberful-admin',
      plugins_url( 'js/src/admin.js', dirname( __FILE__ ) ),
      array('jquery'),
      MEMBERFUL_VERSION
    );
  }

  wp_enqueue_script(
    'memberful-menu',
    plugins_url( 'js/src/menu.js', dirname( __FILE__ ) ),
    array('jquery'),
    MEMBERFUL_VERSION
  );
}

/**
 * Displays the page for registering the WordPress plugin with memberful.com
 */
function memberful_wp_register() {
  $vars = array();

  if ( isset( $_POST['activation_code'] ) ) {
    if ( ! empty( $_POST['activation_code'] ) ) {
      $activation = memberful_wp_activate( $_POST['activation_code'] );

      if ( $activation === TRUE ) {
        update_option( 'memberful_embed_enabled', TRUE );
        memberful_wp_sync_products();
        memberful_wp_sync_subscription_plans();
      }
      else {
        Memberful_Wp_Reporting::report( $activation, 'error' );
      }
    }

    return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
  }

  memberful_wp_render( 'setup', $vars );
}

/**
 * Resets the plugin to its default state
 */
function memberful_wp_reset() {
  $defaults = memberful_wp_all_options();

  foreach ( memberful_wp_connection_options() as $option ) {
    update_option( $option, $defaults[$option] );
  }
}

function _memberful_wp_debug_all_post_meta() {
  global $wpdb;

  $results = $wpdb->get_results(
    "SELECT posts.ID, meta.meta_value FROM {$wpdb->posts} AS posts ".
    "LEFT JOIN {$wpdb->postmeta} AS meta ON (posts.ID = meta.post_id) ".
    "WHERE meta.meta_key = 'memberful_acl';"
  );

  $meta = array();

  foreach($results as $row) {
    $meta[$row->ID] = $row->meta_value;
  }

  return $meta;
}

function memberful_wp_debug() {
  global $wp_version;

  if ( ! function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
  }

  $mapping_stats = new Memberful_User_Map_Stats(Memberful_User_Mapping_Repository::table());
  $counts = count_users();

  $mapping_records       = $mapping_stats->mapping_records();
  $total_mapping_records = $mapping_stats->count_mapping_records();
  $unmapped_users        = $mapping_stats->unmapped_users();

  $total_users           = $counts['total_users'];
  $total_unmapped_users  = count($unmapped_users);
  $total_mapped_users    = $total_users - $total_unmapped_users;
  $config                = memberful_wp_option_values();
  $acl_for_all_posts     = _memberful_wp_debug_all_post_meta();
  $plugins               = get_plugins();
  $error_log             = memberful_wp_error_log();

  unset( $config['memberful_error_log'] );

  memberful_wp_render(
    'debug',
    compact(
      'unmapped_users',
      'total_users',
      'total_unmapped_users',
      'total_mapped_users',
      'total_mapping_records',
      'mapping_records',
      'config',
      'acl_for_all_posts',
      'wp_version',
      'plugins',
      'error_log'
    )
  );
}


/**
 * Displays the memberful options page
 */
function memberful_wp_options() {
  if ( ! function_exists( 'curl_version' ) || isset( $_GET['curl_message'] ) )
    return memberful_wp_render( 'curl_required' );

  if ( ! empty( $_POST ) ) {
    if ( ! memberful_wp_valid_nonce( 'memberful_options' ) )
      return;

    if ( isset( $_POST['manual_sync'] ) ) {
      if ( is_wp_error( $error = memberful_wp_sync_products() ) ) {
        Memberful_Wp_Reporting::report( $error, 'error' );

        return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
      }

      if ( is_wp_error( $error = memberful_wp_sync_subscription_plans() ) ) {
        Memberful_Wp_Reporting::report( $error, 'error' );

        return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
      }

      return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
    }

    if ( isset( $_POST['reset_plugin'] ) ) {
      memberful_api_disconnect();
      memberful_wp_reset();

      return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
    }

    if ( isset( $_POST['save_changes'] ) ) {
      update_option( 'memberful_extend_auth_cookie_expiration', isset( $_POST['extend_auth_cookie_expiration'] ));
      update_option( 'memberful_hide_admin_toolbar', isset( $_POST['memberful_hide_admin_toolbar'] ));
      update_option( 'memberful_block_dashboard_access', isset( $_POST['memberful_block_dashboard_access'] ));
      update_option( 'memberful_filter_account_menu_items', isset( $_POST['memberful_filter_account_menu_items'] ));
      update_option( 'memberful_auto_sync_display_names', isset( $_POST['memberful_auto_sync_display_names'] ) );
      update_option( 'memberful_show_protected_content_in_search', isset( $_POST['memberful_show_protected_content_in_search'] ) );

      return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
    }
  }

  if ( ! memberful_wp_is_connected_to_site() ) {
    return memberful_wp_register();
  }

  if ( ! empty( $_GET['subpage'] ) ) {
    switch ( $_GET['subpage'] ) {
    case 'bulk_protect':
      return memberful_wp_bulk_protect();
    case 'debug':
      return memberful_wp_debug();
    case 'advanced_settings':
      return memberful_wp_advanced_settings();
    case 'protect_bbpress':
      return memberful_wp_protect_bbpress();
    case 'private_user_feed_settings':
      return memberful_wp_private_rss_feed_settings();
    case 'cookies_test':
      return memberful_wp_render('cookies_test');
    case 'global_marketing':
      return memberful_wp_global_marketing();
    case 'ad_provider_settings':
      return memberful_wp_ad_provider_settings();
    }
  }

  $products = get_option( 'memberful_products', array() );
  $subscriptions = get_option( 'memberful_subscriptions', array() );
  $feeds = get_option( 'memberful_feeds', array() );
  $extend_auth_cookie_expiration = get_option( 'memberful_extend_auth_cookie_expiration' );
  $hide_admin_toolbar = get_option( 'memberful_hide_admin_toolbar' );
  $block_dashboard_access = get_option( 'memberful_block_dashboard_access' );
  $filter_account_menu_items = get_option( 'memberful_filter_account_menu_items' );
  $auto_sync_display_names = get_option( 'memberful_auto_sync_display_names' );
  $show_protected_content_in_search = get_option( 'memberful_show_protected_content_in_search' );

  memberful_wp_render (
    'options',
    array(
      'products' => $products,
      'feeds' => $feeds,
      'subscriptions' => $subscriptions,
      'extend_auth_cookie_expiration' => $extend_auth_cookie_expiration,
      'hide_admin_toolbar' => $hide_admin_toolbar,
      'block_dashboard_access' => $block_dashboard_access,
      'filter_account_menu_items' => $filter_account_menu_items,
      'auto_sync_display_names' => $auto_sync_display_names,
      'show_protected_content_in_search' => $show_protected_content_in_search
    )
  );
}

/**
 * Attempts to get the necessary details from memberful and set them
 * using the wordpress settings API
 *
 * @param $code string The activation code
 */
function memberful_wp_activate( $code ) {
  $params = array(
    'activation_code'    => trim($code),
    'app_name'           => trim(memberful_wp_site_name()),
    'app_url'            => home_url(),
    'oauth_redirect_url' => memberful_wp_oauth_callback_url(),
    'requirements'       => array('oauth', 'api_key', 'webhook'),
    'webhook_url'        => memberful_wp_webhook_url()
  );

  $response = memberful_wp_post_data_to_api_as_json(
    memberful_activation_url(),
    $params
  );

  if ( is_wp_error( $response ) ) {
    return new WP_Error( 'memberful_activation_request_error', "We had trouble connecting to Memberful, please email info@memberful.com. ({$response->get_error_message()})" );
  }

  $response_code = (int) wp_remote_retrieve_response_code( $response );
  $response_body = wp_remote_retrieve_body( $response );

  if ( 404 === $response_code ) {
    return new WP_Error( 'memberful_activation_code_invalid', "It looks like your activation code is wrong. Please try again, and if this keeps happening email us at info@memberful.com." );
  }

  if ( $response_code !== 200 || empty( $response_body ) ) {
    return new WP_Error( 'memberful_activation_fail', "We couldn't connect to Memberful, please email info@memberful.com." );
  }

  $credentials = json_decode( $response_body );

  update_option( 'memberful_client_id', $credentials->oauth->identifier );
  update_option( 'memberful_client_secret', $credentials->oauth->secret );
  update_option( 'memberful_api_key', $credentials->api_key->key );
  update_option( 'memberful_site', $credentials->site );
  update_option( 'memberful_webhook_secret', $credentials->webhook->secret );
  update_option( 'memberful_custom_domain', $credentials->custom_domain );

  return TRUE;
}

function memberful_wp_advanced_settings() {
  $allowed_roles         = memberful_wp_roles_that_can_be_mapped_to();
  $current_active_role   = memberful_wp_role_for_active_customer();
  $current_inactive_role = memberful_wp_role_for_inactive_customer();
  $subscription_plans    = memberful_subscription_plans();
  $current_mappings      = memberful_wp_get_all_plan_role_mappings();
  $use_per_plan_roles    = memberful_wp_use_per_plan_roles();

  /**
   * Filter to determine if user roles should be automatically updated/synced to existing users on save.
   *
   * @since 1.77.0
   *
   * @param bool $should_update_user_roles Whether to update user roles. (Default: true)
   */
  $should_update_user_roles = apply_filters( 'memberful_should_bulk_update_user_roles_on_save', true );

  if ( ! empty( $_POST ) ) {
    if ( isset( $_POST['role_mappings']['active_customer'] ) && array_key_exists( $_POST['role_mappings']['active_customer'], $allowed_roles ) ) {
      $new_active_role = sanitize_text_field($_POST['role_mappings']['active_customer']);
    }

    if ( isset( $_POST['role_mappings']['inactive_customer'] ) && array_key_exists( $_POST['role_mappings']['inactive_customer'], $allowed_roles ) ) {
      $new_inactive_role = sanitize_text_field($_POST['role_mappings']['inactive_customer']);
    }

    // Save active/inactive role mappings
    if ( isset($new_active_role) && isset($new_inactive_role) ) {
      update_option( 'memberful_role_active_customer', $new_active_role );
      update_option( 'memberful_role_inactive_customer', $new_inactive_role );

      if ( $should_update_user_roles ) {
        memberful_wp_update_customer_roles( $current_active_role, $new_active_role, $current_inactive_role, $new_inactive_role );
      }

      Memberful_Wp_Reporting::report( __('Active/Inactive role settings updated') );
    } else {
      Memberful_Wp_Reporting::report( __('The roles you chose aren\'t in the list of allowed roles'), 'error' );
    }

    // Save per-plan role mappings
    $new_use_per_plan_roles = isset( $_POST['use_per_plan_roles'] );
    memberful_wp_set_use_per_plan_roles( $new_use_per_plan_roles );

    if ( $new_use_per_plan_roles ) {
      $new_plan_mappings = array();

      if ( isset( $_POST['plan_role_mappings'] ) && is_array( $_POST['plan_role_mappings'] ) ) {
        foreach ( $_POST['plan_role_mappings'] as $plan_id => $role ) {
          if ( empty( $plan_id ) ) {
            continue;
          }

          $plan_id = is_numeric( $plan_id ) ? intval( $plan_id ) : sanitize_text_field( $plan_id );
          $role    = sanitize_text_field( $role );

          if ( 'inactive' === $plan_id ) {
            $new_plan_mappings['inactive'] = $role;
            continue;
          }

          if ( ! empty( $role ) && array_key_exists( $role, $allowed_roles ) && isset( $subscription_plans[ $plan_id ] ) ) {
            $new_plan_mappings[ $plan_id ] = $role;
          }
        }
      }

      update_option( 'memberful_plan_role_mappings', $new_plan_mappings );

      if ( $should_update_user_roles ) {
        memberful_wp_update_all_user_roles_with_plan_mappings();
      }

      Memberful_Wp_Reporting::report( __('Per-plan role mappings updated') );
    } else {
      // If disabling, clear mappings
      update_option( 'memberful_plan_role_mappings', array() );
    }

    wp_redirect( memberful_wp_plugin_advanced_settings_url() );
  }

  $vars = array(
    'available_state_mappings' => array(
      'active_customer'   => array(
        'name' => 'Any active subscription plans',
        'current_role' => $current_active_role,
      ),
      'inactive_customer' => array(
        'name' => 'No active subscription plans',
        'current_role' => $current_inactive_role,
      ),
    ),
    'available_roles' => $allowed_roles,
    'subscription_plans' => $subscription_plans,
    'current_mappings' => $current_mappings,
    'use_per_plan_roles' => $use_per_plan_roles,
  );
  memberful_wp_render( 'advanced_settings', $vars );
}

function memberful_wp_bulk_protect() {
  if ( ! empty( $_POST ) ) {
    $categories_to_protect = empty( $_POST['memberful_protect_categories'] ) ? array() : (array) $_POST['memberful_protect_categories'];
    $categories_to_protect = array_map( 'intval', $categories_to_protect );
    $categories_to_protect = array_intersect( $categories_to_protect, get_categories( array( 'fields' => 'ids' ) ) );

    $acl_for_products = empty( $_POST['memberful_product_acl'] ) ? array() : (array) $_POST['memberful_product_acl'];
    $acl_for_products = array_map( 'intval', $acl_for_products );
    $acl_for_products = array_intersect( $acl_for_products, array_keys( memberful_downloads() ) );

    $acl_for_subscriptions = empty( $_POST['memberful_subscription_acl'] ) ? array() : (array) $_POST['memberful_subscription_acl'];
    $acl_for_subscriptions = array_map( 'intval', $acl_for_subscriptions );
    $acl_for_subscriptions = array_intersect( $acl_for_subscriptions, array_keys( memberful_subscription_plans() ) );

    $marketing_content = trim( memberful_wp_kses_post( $_POST['memberful_marketing_content'] ) );
    $viewable_by_any_registered_user = isset( $_POST['memberful_viewable_by_any_registered_users'] );
    $viewable_by_anybody_subscribed_to_a_plan = isset( $_POST['memberful_viewable_by_anybody_subscribed_to_a_plan'] );

    $subscription_acl_manager = new Memberful_Post_ACL( Memberful_ACL::SUBSCRIPTION );
    $product_acl_manager = new Memberful_Post_ACL( Memberful_ACL::DOWNLOAD );

    $query_params = array('nopaging' => true, 'fields' => 'ids');

    if ( isset( $_POST['target_for_restriction'] ) ) {
      switch ( $_POST['target_for_restriction'] ) {
      case 'all_pages_and_posts':
        $query_params['post_type'] = array('post', 'page');
        break;
      case 'all_pages':
        $query_params['post_type'] = 'page';
        break;
      case 'all_posts':
        $query_params['post_type'] = 'post';
        break;
      case 'all_posts_from_category':
        if ( empty( $categories_to_protect ) ) {
          $error = "Please select a category.";
        } else {
          $query_params['category__in'] = $categories_to_protect;
        }
        break;
      default:
        if ( in_array( $_POST['target_for_restriction'], array_keys( memberful_additional_post_types_to_protect() ) ) ) {
          $query_params['post_type'] = sanitize_text_field($_POST['target_for_restriction']);
        } else {
          wp_die("Invalid request");
        }
      }
    } else {
      wp_die("Invalid request");
    }

    if ( empty( $error ) ) {
      $query = new WP_Query($query_params);

      foreach($query->posts as $id) {
        $product_acl_manager->set_acl($id, $acl_for_products);
        $subscription_acl_manager->set_acl($id, $acl_for_subscriptions);
        if ( !empty($marketing_content) ) {
          memberful_wp_update_post_marketing_content($id, $marketing_content);
        }
        memberful_wp_set_post_available_to_any_registered_users($id, $viewable_by_any_registered_user);
        memberful_wp_set_post_available_to_anybody_subscribed_to_a_plan($id, $viewable_by_anybody_subscribed_to_a_plan);
      }

      wp_redirect( add_query_arg( 'success', 'bulk', memberful_wp_plugin_bulk_protect_url() ) );
    } else {
      wp_redirect( add_query_arg( 'error', $error, memberful_wp_plugin_bulk_protect_url() ) );
    }
  }

  memberful_wp_render(
    'bulk_protect',
    array(
      'products' => memberful_wp_metabox_acl_format( array(), 'product' ),
      'subscriptions' => memberful_wp_metabox_acl_format( array(), 'subscription' ),
      'marketing_content' => '',
      'form_target' => memberful_wp_plugin_bulk_protect_url(TRUE),
      'viewable_by_any_registered_users' => false,
      'viewable_by_anybody_subscribed_to_a_plan' => false
    )
  );
}

function memberful_wp_protect_bbpress() {
  if ( ! empty( $_POST ) ) {
    $protection_enabled = isset( $_POST['memberful_protect_bbpress'] );

    if ( empty( $_POST['memberful_product_acl'] ) ) {
      $required_downloads = array();
    } else {
      $required_downloads = array_intersect(
        array_map('intval', (array) $_POST['memberful_product_acl']),
        array_keys( memberful_downloads() )
      );
    }

    if ( empty( $_POST['memberful_subscription_acl'] ) ) {
      $required_subscription_plans = array();
    } else {
      $required_subscription_plans = array_intersect(
        array_map('intval', (array) $_POST['memberful_subscription_acl']),
        array_keys( memberful_subscription_plans() )
      );
    }

    $viewable_by_any_registered_user = isset( $_POST['memberful_viewable_by_any_registered_users'] );
    $viewable_by_anybody_subscribed_to_a_plan = isset( $_POST['memberful_viewable_by_anybody_subscribed_to_a_plan'] );

    $redirect_to_homepage = empty( $_POST['memberful_send_unauthorized_users'] ) ? true : ($_POST['memberful_send_unauthorized_users'] == 'homepage');
    $redirect_to_url = empty( $_POST['memberful_send_unauthorized_users_to_url'] ) ? '' : sanitize_url( $_POST['memberful_send_unauthorized_users_to_url'] );

    if ( ! empty( $required_subscription_plans ) )
      $required_subscription_plans = array_combine( $required_subscription_plans, $required_subscription_plans );

    if ( ! empty( $required_downloads ) )
      $required_downloads = array_combine( $required_downloads, $required_downloads );

    memberful_wp_bbpress_update_send_unauthorized_users_to_homepage( $redirect_to_homepage );
    memberful_wp_bbpress_update_protect_forums( $protection_enabled );
    memberful_wp_bbpress_update_restricted_to_registered_user( $viewable_by_any_registered_user );
    memberful_wp_bbpress_update_restricted_to_subscribed_users( $viewable_by_anybody_subscribed_to_a_plan );
    memberful_wp_bbpress_update_required_downloads( $required_downloads );
    memberful_wp_bbpress_update_required_subscription_plans( $required_subscription_plans );
    memberful_wp_bbpress_update_send_unauthorized_users_to_url( $redirect_to_url );

    wp_redirect( memberful_wp_plugin_protect_bbpress_url() );
  }

  $plans     = memberful_subscription_plans();
  $downloads = memberful_downloads();

  $required_subscription_plans = memberful_wp_bbpress_required_subscription_plans();
  $required_downloads     = memberful_wp_bbpress_required_downloads();

  foreach( $plans as $id => $plan ) {
    $plans[$id]['checked'] = isset($required_subscription_plans[$id]);
  }

  foreach( $downloads as $id => $download ) {
    $downloads[$id]['checked'] = isset($required_downloads[$id]);
  }

  memberful_wp_render(
    'protect_bbpress',
    array(
      'protect_bbpress'                     => memberful_wp_bbpress_protect_forums(),
      'restricted_to_registered_users'      => memberful_wp_bbpress_restricted_to_registered_users(),
      'restricted_to_subscribed_users'      => memberful_wp_bbpress_restricted_to_subscribed_users(),
      'plans'                               => $plans,
      'downloads'                           => $downloads,
      'send_unauthorized_users_to_homepage' => memberful_wp_bbpress_send_unauthorized_users_to_homepage(),
      'send_unauthorized_users_to_url'      => memberful_wp_bbpress_send_unauthorized_users_to_url(),
      'form_target'                         => memberful_wp_plugin_protect_bbpress_url( TRUE ),
    )
  );
}

function memberful_wp_private_rss_feed_settings() {
  if(isset($_POST['memberful_private_feed_subscriptions_submit'])) {
    $private_feed_subscriptions = empty( $_POST['memberful_private_feed_subscriptions'] ) ? array() : (array) $_POST['memberful_private_feed_subscriptions'];
    $private_feed_subscriptions = array_map( 'intval', $private_feed_subscriptions );
    $private_feed_subscriptions = array_intersect( $private_feed_subscriptions, array_keys( memberful_subscription_plans() ) );

    $add_block_tags = isset($_POST['memberful_add_block_tags_to_rss_feed']);

    memberful_private_user_feed_settings_set_required_plan($private_feed_subscriptions);
    update_option('memberful_add_block_tags_to_rss_feed', $add_block_tags);
  }

  $current_feed_subscriptions = memberful_private_user_feed_settings_get_required_plan();
  $current_feed_subscriptions = !is_array($current_feed_subscriptions) ? array() : $current_feed_subscriptions;

  memberful_wp_render(
    'private_user_feed_settings',
    array(
      'form_target'                => memberful_wp_plugin_private_user_feed_settings_url(),
      'subscription_plans'         => memberful_subscription_plans(),
      'available_subscriptions'    => memberful_private_user_feed_settings_get_required_plan(),
      'current_feed_subscriptions' => $current_feed_subscriptions,
      'add_block_tags_to_rss_feed' => get_option('memberful_add_block_tags_to_rss_feed')
    )
  );
}

function memberful_wp_announce_plans_and_download_in_head() {
  memberful_wp_render(
    'js_vars',
    array(
      'data' => array(
        'plans' => array_values(memberful_subscription_plans()),
        'downloads' => array_values(memberful_downloads()),
        'feeds' => array_values(memberful_feeds()),
        'connectedToMemberful' => memberful_wp_is_connected_to_site(),
      )
    )
  );
}

function memberful_wp_add_protected_state_to_post_list($states, $post) {
  if ( ! memberful_can_user_access_post( null, $post->ID )) {
    $states[] = __('Protected by Memberful');
  }

  return $states;
}

function memberful_wp_global_marketing() {
  if ( isset( $_POST['save_global_marketing'] ) && memberful_wp_valid_nonce( 'memberful_options' ) ) {
    if ( isset( $_POST['memberful_use_global_marketing'] ) ) {
      update_option( 'memberful_use_global_marketing', true );
      update_option( 'memberful_global_marketing_override', filter_input( INPUT_POST, 'memberful_global_marketing_override', FILTER_SANITIZE_NUMBER_INT ) );
      update_option( 'memberful_global_marketing_content', memberful_wp_kses_post( filter_input( INPUT_POST, 'memberful_global_marketing_content' ) ) );
      update_option( 'memberful_use_global_snippets', (int) isset($_POST['memberful_use_global_snippets']));
    } else {
      update_option( 'memberful_use_global_marketing', false );
    }
  }

  $use_global_marketing = get_option( 'memberful_use_global_marketing' );
  $use_global_snippets = get_option( 'memberful_use_global_snippets');
  $global_marketing_content = get_option( 'memberful_global_marketing_content' );
  $global_marketing_override = get_option( 'memberful_global_marketing_override', true );

  memberful_wp_render(
    'global_marketing',
    array(
      'use_global_marketing' => $use_global_marketing,
      'use_global_snippets'  => $use_global_snippets,
      'global_marketing_content' => $global_marketing_content,
      'global_marketing_override' => $global_marketing_override,
      'form_target' => memberful_wp_plugin_global_marketing_url()
    )
  );
}

/**
 * Render and save the ad provider settings.
 */
function memberful_wp_ad_provider_settings() {
  $providers = Memberful_Wp_Integration_Ad_Provider_Manager::instance()->get_all_providers();
  $subscription_plans = memberful_subscription_plans();
  $provider_settings = memberful_wp_ad_provider_get_settings();

  if ( isset( $_POST['save_ad_provider_settings'] ) && memberful_wp_valid_nonce( 'memberful_options' ) ) {
    $raw_settings = empty( $_POST['memberful_ad_provider'] ) ? array() : (array) $_POST['memberful_ad_provider'];
    $provider_ids = array_keys( $providers );
    $plan_ids = array_keys( $subscription_plans );

    update_option(
      'memberful_ad_provider_settings',
      memberful_wp_ad_provider_sanitise_settings( $raw_settings, $provider_ids, $plan_ids )
    );

    Memberful_Wp_Reporting::report( __( 'Settings updated', 'memberful' ) );

    return wp_redirect( memberful_wp_plugin_ad_provider_settings_url() );
  }

  memberful_wp_render(
    'ad-provider-settings',
    array(
      'providers' => $providers,
      'subscription_plans' => $subscription_plans,
      'provider_settings' => $provider_settings,
      'form_target' => memberful_wp_plugin_ad_provider_settings_url( true )
    )
  );
}
