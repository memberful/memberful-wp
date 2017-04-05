<?php
use Ari_Adminer\Helpers\Helper as Helper;
use Ari_Adminer\Utils\Db_Driver as DB_Driver;

$list = $data['list'];
$action_url = Helper::build_url(
    array(
        'noheader' => '1',
    ),
    array(
        'filter'
    )
);
$tmpl_path = dirname( __FILE__ ) . '/';
?>
<a href="#" class="<?php if ( ARI_WP_LEGACY ): ?>add-new-h2<?php else: ?>page-title-action<?php endif; ?>" id="btnAddConnection"><?php _e( 'Add New', 'ari-adminer' ); ?></a>
<hr class="wp-header-end">
<form action="<?php echo esc_url( $action_url ); ?>" method="POST">
<div>
    <?php $this->show_template( $tmpl_path . 'toolbar.php', array( 'class' => 'top' ) ); ?>
    <?php $this->grid->render( $list ); ?>
    <?php $this->show_template( $tmpl_path . 'toolbar.php', array( 'class' => 'bottom' ) ); ?>

    <input type="hidden" id="ctrl_action" name="action" value="display" />
    <input type="hidden" id="ctrl_sub_action" name="sub_action" value="" />
    <input type="hidden" id="hidConnectionId" name="action_connection_id" value="" />
    <input type="hidden" name="filter" value="<?php echo esc_attr( $data['filter_encoded'] ); ?>" />
</div>
</form>
<div id="newConnectionForm" class="connection-form-container mfp-hide">
    <div class="ari-form pure-form pure-form-stacked">
        <fieldset>
            <legend>
                <h3><?php _e( 'Connection settings', 'ari-adminer' ); ?></h3>
            </legend>

            <div id="rowConnectionTitle" class="row">
                <label for="tbxConnectionTitle"><?php _e( 'Title', 'ari-adminer' ); ?></label>
                <input id="tbxConnectionTitle" class="form-control pure-input-1" data-key="title" type="text" autocomplete="off" data-validator="require" data-validator-message="<?php esc_attr_e( 'Specify title', 'ari-adminer' ); ?>" data-validator-group="connection" />
            </div>

            <div id="rowConnectionDriver" class="row">
                <label for="ddlConnectionDriver"><?php _e( 'DB Type', 'ari-adminer' ); ?></label>
                <select id="ddlConnectionDriver" class="form-control" data-key="type" autocomplete="off">
                    <option value="<?php echo DB_Driver::MYSQL; ?>"><?php echo Helper::db_type_to_label( DB_Driver::MYSQL ); ?></option>
                    <option value="<?php echo DB_Driver::SQLITE; ?>"><?php echo Helper::db_type_to_label( DB_Driver::SQLITE ); ?></option>
                    <option value="<?php echo DB_Driver::POSTGRESQL; ?>"><?php echo Helper::db_type_to_label( DB_Driver::POSTGRESQL ); ?></option>
                </select>
            </div>

            <div id="rowConnectionHost" class="row">
                <label for="tbxConnectionHost"><?php _e( 'Host', 'ari-adminer' ); ?></label>
                <input id="tbxConnectionHost" class="form-control pure-input-1" data-key="host" type="text" placeholder="<?php esc_attr_e( 'Eg. localhost', 'ari-adminer' ); ?>" autocomplete="off" />
            </div>

            <div id="rowConnectionDB" class="row">
                <label id="lblConnectionPath" for="tbxConnectionDB"><?php _e( 'Path to DB file', 'ari-adminer' ); ?></label>
                <label id="lblConnectionDB" for="tbxConnectionDB"><?php _e( 'DB name', 'ari-adminer' ); ?></label>
                <input id="tbxConnectionDB" class="form-control pure-input-1" data-key="db_name" type="text" autocomplete="off" data-validator="require" data-validator-message="<?php esc_attr_e( 'Specify database', 'ari-adminer' ); ?>" data-validator-group="connection test_connection" />
            </div>

            <div id="rowConnectionUser" class="row">
                <label for="tbxConnectionUser"><?php _e( 'User', 'ari-adminer' ); ?></label>
                <input id="tbxConnectionUser" class="form-control pure-input-1" data-key="user" type="text" autocomplete="off" />
            </div>

            <div id="rowConnectionPass" class="row">
                <label for="tbxConnectionPass"><?php _e( 'Password', 'ari-adminer' ); ?></label>
                <input id="tbxConnectionPass" class="form-control pure-input-1" data-key="pass" type="password" autocomplete="off" />
            </div>

            <div class="action-panel align-right">
                <a href="#" id="btnConnectionSave" class="button button-primary"><?php _e( 'Save', 'ari-adminer' ); ?></a>
                <a href="#" id="btnConnectionTest" class="button"><?php _e( 'Test Connection', 'ari-adminer' ); ?></a>
            </div>
            <input id="hidConnectionId" type="hidden" class="form-control" data-key="connection_id" value="0" autocomplete="off" />
        </fieldset>
    </div>
</div>