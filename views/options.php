<div class="wrap">
	<h2>Memberful Integration Options</h2>
  <div class="settings" style="float: left; width 45%;">
    <form method="POST" action="options.php">
      <?php settings_fields('memberful_wp'); ?>
      <?php do_settings_sections('memberful_wp_settings'); ?>
      <p>Here is the webhook endpoint <input type="text" readonly="readonly" value="<?php echo memberful_wp_webhook_url(); ?>" /></p>
      <input name="submit" type="submit" value="Save Changes" class="button-primary" />
    </form>
  </div>

  <?php if($show_products): ?>
  <div class="products" style="float:left; width: 45%; margin-left: 2%;">
    <p>Here are the products that we've synced from Memberful</p>
    <form method="POST">
      <?php if( ! empty($products)): ?>
      <ul>
      <?php foreach((array) get_option('memberful_products') as $id => $product): ?>
        <li><a href="<?php echo memberful_product_url($id); ?>"><?php echo $product['name']; ?></a></li>
      <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <input name="refresh_products" type="submit" value="Sync products" class="button-primary" />
    </form>
  </div>
  <?php endif; ?>

</div>
