<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'cookies_test')); ?>
  <?php memberful_wp_render('flash'); ?>
  <div class="postbox memberful-postbox">
    <p>
      Some web hosting services may block cookies or use extensive caching. This prevents Memberful from working properly. If the cookies test fails, please ask your hosting service to allow cookies from Memberful and also ask them to disable caching of URLs that contain <code>?memberful_endpoint</code>. <a href="https://memberful.com/docs/wordpress-plugin/troubleshooting/wordpress-troubleshooting#my-members-can%E2%80%99t-access-my-protected-content">Learn more about solving cookie/cache issues</a>.
    </p>

    <a href="<?php echo esc_url(memberful_wp_endpoint_url('set_test_cookie')); ?>" class="button">Run Cookies Test</a>
  </div>
</div>
