<?php
$container_class = $data['class'];
?>
<div class="tablenav <?php echo $container_class; ?>">
    <div class="alignleft actions bulkactions">
        <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e( 'Select bulk action', 'ari-adminer' ); ?></label>
        <select class="bulk-action-select">
            <option value=""><?php _e( '- Bulk Actions -', 'ari-adminer' ); ?></option>
            <option value="bulk_delete"><?php _e( 'Delete', 'ari-adminer' ); ?></option>
        </select>
        <button class="button btn-bulk-action"><?php _e( 'Apply', 'ari-adminer' ); ?></button>
    </div>
</div>