<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'cookies_test')); ?>
  <?php memberful_wp_render('flash'); ?>
  <div class="postbox memberful-postbox">
    <p>
      Some web hosts aren't compatible with Memberful WP plugin, because they block cookies set by Memberful WP. You can perform a cookies test on this page to see if everything is working as expected.
    </p>

    <a href="<?php echo memberful_wp_endpoint_url('set_test_cookie') ?>" class="button">Perform Cookies Test</a>
  </div>
</div>
