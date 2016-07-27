<?php
$links = array(
  array(
    'id'    => 'settings',
    'title' => __('Memberful'),
    'url'   => memberful_wp_plugin_settings_url()
  ),
  array(
    'id'    => 'bulk_protect',
    'title' => __('Bulk restrict access tool'),
    'url'   => memberful_wp_plugin_bulk_protect_url()
  ),
  array(
    'id'    => 'advanced_settings',
    'title' => __('Advanced Role Mapping'),
    'url'   => memberful_wp_plugin_advanced_settings_url()
  ),
  array(
    'id'    => 'private_user_feed_settings',
    'title' => __('Private RSS Feeds'),
    'url'   => memberful_wp_plugin_private_user_feed_settings_url()
  ),
  array(
    'id'    => 'cookies_test',
    'title' => __('Cookies Test'),
    'url'   => memberful_wp_plugin_cookies_test_url()
  ),
);
if ( is_plugin_active( 'bbpress/bbpress.php' ) ) {
  $links[] = array(
    'id'  => 'protect_bbpress',
    'title' => __('bbPress Forums'),
    'url'   => memberful_wp_plugin_protect_bbpress_url()
  );
}
?>
<h2 class="nav-tab-wrapper">
<?php foreach($links as $link): ?>
  <a href="<?php echo $link['url']; ?>" id="nav_tab_<?php echo $link['id']; ?>" class="nav-tab <?php echo $link['id'] === $active ? 'nav-tab-active' : '' ?>"><?php echo $link['title']; ?></a>
<?php endforeach; ?>
</h2>
