<p><?php _e( 'Restrict access by selecting from the list of products or subscriptions below.', 'memberful' ); ?></p>
<ul class="wp-tab-bar">
  <li><a href="#memberful-subscriptions"><?php _e( 'Subscriptions', 'memberful' ); ?></a></li>
	<li><a href="#memberful-products"><?php _e( 'Products', 'memberful' ); ?></a></li>
</ul>
<div id="memberful-products" class="wp-tab-panel">
	<ul>
	<?php foreach($products as $id => $product): ?>
		<li>
			<label>
				<input type="checkbox" name="memberful_acl[]" value="<?php echo $id; ?>" <?php if($product['checked']):?>checked="checked"<?php endif; ?>>
				<?php echo $product['name']; ?>
			</label>
		</li>
	<?php endforeach; ?>
	</ul>
</div>
<div id="memberful-subscriptions" class="wp-tab-panel" style="display:none;">
	<ul>
	<?php foreach($subscriptions as $id => $subscription): ?>
		<li>
			<label>
				<input type="checkbox" name="memberful_acl[]" value="<?php echo $id; ?>" <?php if($subscription['checked']):?>checked="checked"<?php endif; ?>>
				<?php echo $subscription['name']; ?>
			</label>
		</li>
	<?php endforeach; ?>
	</ul>
</div>
