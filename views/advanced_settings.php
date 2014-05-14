<div class="wrap">
	<?php memberful_wp_render('option_tabs', array('active' => 'advanced_settings')); ?>
	<?php memberful_wp_render('flash'); ?>
	<form method="post" action="<?php echo memberful_wp_plugin_advanced_settings_url( TRUE ); ?>">
			<table class="widefat fixed" id="memberful-role-mapping-table">
				<thead>
				<tr>
					<th scope="col" class="manage-column">Customer state</th>
					<th scope="col" class="manage-column">Assigned role</th>
				</tr>
				</thead>
				<tbody class="role-mapping">
					<?php foreach( $available_state_mappings as $state_id => $state): ?>
					<tr>
						<td class="customer-state"><strong><?php echo $state['name']; ?></strong></td>
						<td class="mapped-role">
							<select name="role_mappings[<?php echo $state_id; ?>]">
								<?php foreach( $available_roles as $role => $role_name ): ?>
								<option value="<?php echo $role; ?>" <?php echo ($state['current_role'] === $role) ? 'selected="selected"' : '' ?>><?php echo $role_name; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="button-controls"><input type="submit" name="nav-menu-locations" id="nav-menu-locations" class="button button-primary left" value="Save Changes"></p>
			<input type="hidden" id="_wpnonce" name="_wpnonce" value="bcaeefdee1"><input type="hidden" name="_wp_http_referer" value="/wp-admin/nav-menus.php?action=locations">			<input type="hidden" name="menu" id="nav-menu-meta-object-id" value="156">
		</form>
</div>
