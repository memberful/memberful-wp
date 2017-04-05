<?php
use Ari_Adminer\Helpers\Helper as Helper;
use Ari_Adminer\Utils\Db_Driver as DB_Driver;

$connections = $data['connections'];
$run_url = Helper::build_url(
    array(
        'action' => 'run',

        'noheader' => '1',
    )
);
?>
<div class="adminer-runner">
    <div class="metabox-holder has-right-sidebar">
        <div class="inner-sidebar">
            <div class="postbox">
                <h3><?php _e( 'How can I help?', 'ari-adminer' ); ?></h3>
                <div class="inside">
                    <ul>
                        <li>
                            <a href="http://www.ari-soft.com/ARI-Adminer/" target="_blank"><?php _e( 'Share feedback or idea', 'ari-adminer' ); ?></a>
                        </li>
                        <li>
                            <a href="https://wordpress.org/support/plugin/ari-adminer/reviews/" target="_blank"><?php _e( 'Write a review and give a rating', 'ari-adminer' ); ?></a>
                        </li>
                        <li>
                            <a href="https://twitter.com/ARISoft" target="_blank"><?php _e( 'Follow us on Twitter', 'ari-adminer' ); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="postbox">
                <h3><?php _e( 'Other plugins', 'ari-adminer' ); ?></h3>
                <div class="inside">
                    <ul>
                        <li>
                            <a href="http://wp-quiz.ari-soft.com/plugins/wordpress-fancy-lightbox.html" target="_blank" title="Best Lightbox Plugin for WordPress"><strong>ARI Fancy Lightbox</strong><?php _e( ' is the best lightbox plugin', 'ari-adminer' ); ?></a>
                        </li>
                        <li>
                            <a href="http://wp-quiz.ari-soft.com" target="_blank" title="Viral Quiz Builder for WordPress"><strong>ARI Stream Quiz</strong><?php _e( ' is viral quiz builder', 'ari-adminer' ); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div id="post-body">
            <div id="post-body-content">
                <div class="postbox">
                    <h3><?php _e( 'DB Connection parameters', 'ari-adminer' ); ?></h3>
                    <div class="inside">
                        <div class="ari-form pure-form pure-form-stacked">
                            <fieldset>
                                <div id="rowConnectionList">
                                    <label for="ddlConnection"><?php _e( 'Connection', 'ari-adminer' ); ?></label>
                                    <select id="ddlConnection" name="connection_id" autocomplete="off">
                                        <option value="-1"><?php _e( '- Custom parameters -', 'ari-adminer' ); ?></option>
                                        <option value="0" selected="selected"><?php _e( '- WordPress database -', 'ari-adminer' ); ?></option>
                                        <?php
                                            if ( is_array( $connections ) ):
                                                foreach ( $connections as $connection ):
                                        ?>
                                        <option value="<?php echo $connection->connection_id; ?>"><?php echo $connection->title; ?></option>
                                        <?php
                                                endforeach;
                                            endif;
                                        ?>
                                    </select>
                                </div>

                                <div id="manualContainer">
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
                                        <input id="tbxConnectionDB" class="form-control pure-input-1" data-key="db_name" type="text" autocomplete="off" data-validator="require" data-validator-message="<?php echo esc_attr_e( 'Specify database', 'ari-adminer' ); ?>" />
                                    </div>

                                    <div id="rowConnectionUser" class="row">
                                        <label for="tbxConnectionUser"><?php _e( 'User', 'ari-adminer' ); ?></label>
                                        <input id="tbxConnectionUser" class="form-control pure-input-1" data-key="user" type="text" autocomplete="off" />
                                    </div>

                                    <div id="rowConnectionPass" class="row">
                                        <label for="tbxConnectionPass"><?php _e( 'Password', 'ari-adminer' ); ?></label>
                                        <input id="tbxConnectionPass" class="form-control pure-input-1" data-key="pass" type="password" autocomplete="off" />
                                    </div>

                                    <div class="align-right">
                                        <a id="btnConnectionTest" href="#" class="button"><?php _e( 'Test Connection', 'ari-adminer' ); ?></a>
                                    </div>
                                </div>

                                <div class="action-panel">
                                    <a href="#" class="btn-adminer-run-modal button button-primary" data-href="<?php echo esc_url( $run_url ); ?>"><?php _e( 'Run Adminer in modal window', 'ari-adminer' ); ?></a>
                                    <a href="#" class="btn-adminer-run button" data-href="<?php echo esc_url( $run_url ); ?>" target="_blank"><?php _e( 'Run Adminer in a new window', 'ari-adminer' ); ?></a>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>