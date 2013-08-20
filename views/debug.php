<h2><?php _e( 'Memberful debug information', 'memberful' ) ?></h2>

<pre><code style="display:block;">
Generated on: <?php echo date("Y-m-d H:i:s O"); ?>


# Stats
Total users: <?php echo $total_users; ?>

Total mapping records: <?php echo $total_mapping_records ?>

Total mapped users: <?php echo $total_mapped_users ?>

Total unmapped users: <?php echo $total_unmapped_users ?>


# Config
<?php foreach($config as $key => $value): ?>
<?php echo $key; ?>: <?php var_export($value); ?>

<?php endforeach; ?>

# ACL
<?php foreach($acl_for_all_posts as $post_id => $meta): ?>
<?php echo str_pad($post_id.':', 4); ?> <?php var_export($meta); ?>

<?php endforeach; ?>

# Mappings

<?php if ( $total_unmapped_users > 0 ): ?>

Unmapped users:
<?php echo str_pad('WP ID', 6), ' ', str_pad('Email', 30), ' ', 'Date registered' ?>
<?php foreach($unmapped_users as $unmapped_user): ?>

<?php echo str_pad($unmapped_user->ID, 6) ?> <?php echo str_pad($unmapped_user->user_email, 30) ?> <?php echo $unmapped_user->user_registered; ?>
<?php endforeach; ?>
<? endif; ?>

<?php if ( ! empty( $mapping_records ) ): ?>

Mapping records:
<?php echo str_pad('WP ID', 7), ' ', str_pad('Mem id', 7), ' ', str_pad('Last sync at', 32), ' ', str_pad('Refresh token', 32) ?>
<?php foreach($mapping_records as $record): ?>

<?php echo str_pad($record->wp_user_id, 7), ' ', str_pad($record->member_id, 7), ' ', str_pad(date('r', $record->last_sync_at), 32), ' ', str_pad($record->refresh_token, 32); ?>
<?php endforeach; ?>
<?php endif; ?>

</code>
</pre>

