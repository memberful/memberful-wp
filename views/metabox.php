<?php if ( ! empty( $subscriptions ) || ! empty( $products ) ) : ?>
	<p><?php _e( 'Restrict access by selecting from the list of products or subscriptions below.', 'memberful' ); ?></p>
<?php endif; ?>

<?php if ( ! empty( $subscriptions ) ) : ?>
	<div id="memberful-subscriptions">
		<h4 style="margin-bottom: 0;">Subscriptions</h4>
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
<?php elseif ( ! empty( $products ) ) : ?>
	<div id="memberful-products">
		<h4 style="margin-bottom: 0;">Products</h4>
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
<?php else : ?>
	<div>
		<p><em><?php _e( "We couldn't find any products or subscriptions in your Memberful account. You'll need to add some before you can restrict access.", 'memberful' ); ?></em></p>
	</div>
<?php endif; ?>
