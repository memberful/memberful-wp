<?php
  $links = array(
    array(
      'id'    => 'settings',
      'title' => __('Memberful'),
      'url'   => memberful_wp_plugin_settings_url()
    ),
    array(
      'id'    => 'mass_protect',
      'title' => __('Mass protect content'),
      'url'   => memberful_wp_plugin_mass_protect_url()
    )
  );
?>
<h2 class="nav-tab-wrapper">
<?php foreach($links as $link): ?>
  <a href="<?php echo $link['url']; ?>" class="nav-tab <?php echo $link['id'] === $active ? 'nav-tab-active' : '' ?>"><?php echo $link['title']; ?></a>
<?php endforeach; ?>
</h2>
