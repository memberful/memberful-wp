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
	);
?>
<h2 class="nav-tab-wrapper">
<?php foreach($links as $link): ?>
  <a href="<?php echo $link['url']; ?>" id="nav_tab_<?php echo $link['id']; ?>" class="nav-tab <?php echo $link['id'] === $active ? 'nav-tab-active' : '' ?>"><?php echo $link['title']; ?></a>
<?php endforeach; ?>
</h2>
