<?php
$lookup_done   = isset( $lookup ) && $lookup !== NULL;
$requested_tab = isset( $_GET['debug_tab'] ) ? strtolower( $_GET['debug_tab'] ) : '';

// Pick the active section. A lookup request always selects the lookup section.
// In the HTML UI we fall back to system info; over the endpoint there are no
// tabs, so we fall back to a usage page documenting the tabs and lookup params.
if ( $lookup_done || $requested_tab === 'lookup' )
  $active_tab = 'lookup';
elseif ( $requested_tab === 'system' || ! $is_endpoint )
  $active_tab = 'system';
else
  $active_tab = 'usage';

$base_url = admin_url( 'options-general.php?page=memberful_options&subpage=debug' );
?>
<?php if ( ! $is_endpoint ): ?>
<div class="wrap">
<h1><?php _e( 'Memberful debug', 'memberful' ); ?></h1>
<h2 class="nav-tab-wrapper">
  <a href="<?php echo esc_url( $base_url.'&debug_tab=system' ); ?>" class="nav-tab<?php echo $active_tab === 'system' ? ' nav-tab-active' : ''; ?>"><?php _e( 'System info', 'memberful' ); ?></a>
  <a href="<?php echo esc_url( $base_url.'&debug_tab=lookup' ); ?>" class="nav-tab<?php echo $active_tab === 'lookup' ? ' nav-tab-active' : ''; ?>"><?php _e( 'Member / user lookup', 'memberful' ); ?></a>
</h2>
<?php endif; ?>

<?php if ( $is_endpoint && $active_tab === 'usage' ): ?>
<h2><?php _e( 'Memberful debug endpoint', 'memberful' ) ?></h2>
<pre><code style="display:block;">
Add a "debug_tab" parameter to choose what to render:

  debug_tab=system   System info: WordPress, plugins, stats, config, ACL, error log
  debug_tab=lookup   Member / user lookup

For a lookup, pass one or more of these parameters (debug_tab=lookup is
implied whenever any of them are present):

  member_email       Look up by email address
  member_id          Look up by Memberful member ID
  wp_user_id         Look up by WordPress user ID
</code></pre>
<?php endif; ?>

<?php if ( $active_tab === 'system' ): ?>
<?php if ( $is_endpoint ): ?><h2><?php _e( 'Memberful debug information', 'memberful' ) ?></h2><?php endif; ?>
<pre><code style="display:block;">
Generated on: <?php echo date("Y-m-d H:i:s O"); ?>


# Meta:
WordPress: <?php echo esc_html($wp_version); ?>

Site URL: <?php echo esc_url(site_url()); ?>

Home URL: <?php echo esc_url(home_url()); ?>

PHP version: <?php echo esc_html(phpversion()); ?>


# Plugins
<?php $chars_in_longest_name = 0; foreach($plugins as $plugin) { $chars_in_longest_name = max( $chars_in_longest_name, strlen( $plugin['Name'] ) ); } ?>
<?php foreach($plugins as $file => $plugin): ?>
<?php echo str_pad('['.(is_plugin_active( $file ) ? 'Active' : 'Inactive').'] ', 11), str_pad( $plugin['Name'], $chars_in_longest_name + 1 ), str_pad(' ('.$plugin['Version'].')', 11), '- ', $plugin['Author'], ' ', $plugin['PluginURI'] ?>

<?php endforeach; ?>

# Stats
Total users: <?php echo intval($total_users); ?>

Total mapping records: <?php echo intval($total_mapping_records); ?>

Total mapped users: <?php echo intval($total_mapped_users); ?>

Total unmapped users: <?php echo intval($total_unmapped_users); ?>


# Config
<?php foreach($config as $key => $value): ?>
<?php echo esc_html($key); ?>: <?php echo esc_html( var_export( $value, true )); ?>

<?php endforeach; ?>

# ACL
<?php foreach($acl_for_all_posts as $post_id => $meta): ?>
<?php echo str_pad(intval($post_id).':', 4); ?> <?php echo esc_html( var_export($meta, true) ); ?>

<?php endforeach; ?>

# Error Log
<?php foreach ( $error_log as $entry ):
  $entry = (array) $entry;
  $date  = isset( $entry['date'] ) ? $entry['date'] : '';
  unset( $entry['date'] );

  echo "\n===== ".esc_html( $date )." =====\n";

  foreach ( $entry as $key => $value ) {
    $printable = ( is_scalar( $value ) || is_null( $value ) ) ? (string) $value : var_export( $value, true );
    echo esc_html( $key ).": ".esc_html( $printable )."\n";
  }
endforeach; ?>

</code>
</pre>
<?php endif; ?>

<?php if ( $active_tab === 'lookup' ): ?>
<?php if ( $is_endpoint ): ?><h2><?php _e( 'Member / user lookup', 'memberful' ) ?></h2><?php endif; ?>
<?php if ( ! $is_endpoint ): ?>
<form method="get">
  <input type="hidden" name="page" value="memberful_options" />
  <input type="hidden" name="subpage" value="debug" />
  <input type="hidden" name="debug_tab" value="lookup" />
  <table class="form-table" role="presentation">
    <tr>
      <th scope="row"><label for="memberful-lookup-email"><?php _e( 'Email', 'memberful' ); ?></label></th>
      <td><input type="text" id="memberful-lookup-email" class="regular-text" name="member_email" value="<?php echo esc_attr( $lookup_inputs['email'] ); ?>" /></td>
    </tr>
    <tr>
      <th scope="row"><label for="memberful-lookup-member-id"><?php _e( 'Member ID', 'memberful' ); ?></label></th>
      <td><input type="text" id="memberful-lookup-member-id" class="regular-text" name="member_id" value="<?php echo $lookup_inputs['member_id'] > 0 ? intval( $lookup_inputs['member_id'] ) : ''; ?>" /></td>
    </tr>
    <tr>
      <th scope="row"><label for="memberful-lookup-wp-user-id"><?php _e( 'WP user ID', 'memberful' ); ?></label></th>
      <td><input type="text" id="memberful-lookup-wp-user-id" class="regular-text" name="wp_user_id" value="<?php echo $lookup_inputs['wp_user_id'] > 0 ? intval( $lookup_inputs['wp_user_id'] ) : ''; ?>" /></td>
    </tr>
  </table>
  <p class="submit"><button type="submit" class="button button-primary"><?php _e( 'Look up', 'memberful' ); ?></button></p>
</form>
<?php endif; ?>

<?php if ( $lookup_done ): ?>
<pre><code style="display:block;">
<?php if ( empty( $lookup ) ): ?>
No records found.
<?php else: ?>
<?php foreach( $lookup as $subject ):
  echo "--- ".esc_html( $subject['source'] )." ---\n";

  if ( isset( $subject['wp_user_id'] ) ) {
    echo "WP user ID:     ".intval( $subject['wp_user_id'] )."\n";
    echo "Login:          ".esc_html( $subject['user_login'] )."\n";
    echo "Email:          ".esc_html( $subject['user_email'] )."\n";
    echo "Display name:   ".esc_html( $subject['display_name'] )."\n";
    echo "Registered:     ".esc_html( $subject['registered'] )."\n";
    echo "Roles:          ".esc_html( implode( ', ', (array) $subject['roles'] ) )."\n";
  }

  if ( isset( $subject['member_id'] ) && $subject['member_id'] !== NULL ) {
    echo "Member ID:      ".intval( $subject['member_id'] )."\n";
    echo "Last sync at:   ".esc_html( $subject['last_sync_at'] )."\n";
    echo "Refresh token:  ".( $subject['has_refresh_token'] ? 'present' : 'missing' )."\n";
  }

  if ( ! empty( $subject['flags'] ) )
    echo "Flags:          ".esc_html( implode( '; ', $subject['flags'] ) )."\n";

  echo "\n";
endforeach; ?>
<?php endif; ?>
</code>
</pre>
<?php elseif ( $is_endpoint ): ?>
<pre><code style="display:block;">
No lookup parameters supplied. Pass one or more of: member_email,
member_id, wp_user_id.
</code></pre>
<?php endif; ?>
<?php endif; ?>

<?php if ( ! $is_endpoint ): ?>
</div>
<?php endif; ?>
