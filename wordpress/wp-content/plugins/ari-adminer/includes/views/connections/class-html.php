<?php
namespace Ari_Adminer\Views\Connections;

use Ari_Adminer\Views\Base as Base;
use Ari_Adminer\Controls\Grid\Grid as Grid;
use Ari_Adminer\Helpers\Helper as Helper;

class Html extends Base {
    public $grid = null;

    public function display( $tmpl = null ) {
        $this->set_title( __( 'Connections', 'ari-adminer' ) );

        $this->grid = $this->create_grid();

        wp_enqueue_script( 'ari-button' );
        wp_enqueue_style( 'ari-modal' );
        wp_enqueue_script( 'ari-modal' );
        wp_enqueue_script( 'ari-adminer-page-connections', ARIADMINER_ASSETS_URL . 'common/pages/connections.js', array( 'ari-adminer-app', 'ari-modal', 'ari-button' ), ARIADMINER_VERSION );

        parent::display( $tmpl );
    }

    protected function get_app_options() {
        $app_options = array(
            'actionEl' => '#ctrl_action',

            'ajaxUrl' => admin_url( 'admin-ajax.php?action=ari_adminer' ),

            'messages' => array(
                'deleteConfirm' => __( 'Do you want to delete the selected item?', 'ari-adminer' ),
				
				'bulkDeleteConfirm' => __( 'Do you want to delete the selected items?', 'ari-adminer' ),

                'connectionOk' => __( 'Connection parameters are valid.', 'ari-adminer' ),

                'connectionFailed' => __( 'Could not connect to DB. The following error occurs: ', 'ari-adminer' ),

                'connectionSaveFailed' => __( 'The connection could not be saved. Try again please.', 'ari-adminer' ),

                'connectionTestFailed' => __( 'The connection could not be tested. Try again please.', 'ari-adminer' ),

                'selectAction' => __( 'Select an action', 'ari-adminer' ),

                'selectItem' => __( 'Select at least one item', 'ari-adminer' ),
            ),
        );

        return $app_options;
    }

    private function create_grid() {
        $data = $this->get_data();
        $filter = $data['filter'];

        $order_by = $filter['order_by'];
        $order_dir = $filter['order_dir'];

        $delete_url = Helper::build_url(
            array(
                'action' => 'delete',

                'id' => '__connectionId__',
            )
        );

        $grid = new Grid(
            'gridResults',

            array(
                'options' => array(
                    'order_by' => $order_by,

                    'order_dir' => $order_dir,
                ),

                'columns' => array(
                    array(
                        'key' => 'connection_id',

                        'header_class' => 'manage-column column-cb check-column',

                        'class' => 'check-column',

                        'header_tag' => ARI_WP_LEGACY ? 'th' : 'td',

                        'tag' => 'th',

                        'header' => function() {
                            $postfix = uniqid( '_hd', false );

                            return sprintf(
                                '<input type="checkbox" class="select-all-items select-item" id="chkAll%1$s" autocomplete="off" /><label for="chkAll%1$s"> </label>',
                                $postfix
                            );
                        },

                        'column' => function( $val, $data ) {
                            return sprintf(
                                '<input type="checkbox" autocomplete="off" class="select-item" name="connection_id[]" id="%1$s" value="%2$d" /><label for="%1$s"> </label>',
                                'chkResult_' . $val,
                                $val
                            );
                        },
                    ),

                    array(
                        'key' => 'title',

                        'header_class' => 'manage-column column-primary',

                        'class' => 'manage-column column-primary has-row-actions',

                        'header' => __( 'Title', 'ari-adminer' ),

                        'column' => function( $val, $data ) use ( $delete_url ) {
                            $html = '';

                            $html .= sprintf(
                                '<a class="row-title connection-edit" href="#" data-id="%2$d">%1$s</a>',
                                $val,
                                $data->connection_id
                            );

                            $html .= '<div class="row-actions">';

                            $html .= sprintf(
                                '<a href="#" class="connection-edit" data-id="%2$d">%1$s</a>',
                                __( 'Edit', 'ari-adminer' ),
                                $data->connection_id
                            );

                            $html .= sprintf(
                                ' | <a href="%2$s" class="btn-connection-delete" data-id="%3$d">%1$s</a>',
                                __( 'Delete', 'ari-adminer' ),
                                str_replace( '__connectionId__', $data->connection_id, $delete_url ),
                                $data->connection_id
                            );

                            $html .= '</div>';

							if ( ! ARI_WP_LEGACY )
								$html .= sprintf(
									'<button type="button" class="toggle-row"><span class="screen-reader-text">%1$s</span></button>',
									__( 'Show more details', 'ari-adminer' )
								);

                            return $html;
                        }
                    ),

                    array(
                        'key' => 'type',

                        'header' => __( 'Type', 'ari-adminer' ),

                        'header_class' => 'manage-column column-type',

                        'class' => 'column-type',

                        'column' => function( $val, $data ) {
                            return Helper::db_type_to_label( $val );
                        }
                    ),
                ),
            )
        );

        return $grid;
    }
}
