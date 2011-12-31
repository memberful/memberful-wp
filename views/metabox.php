<p>Restrict access to members who've purchased</p>
<div style="overflow:scroll; height: 200px;">
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
