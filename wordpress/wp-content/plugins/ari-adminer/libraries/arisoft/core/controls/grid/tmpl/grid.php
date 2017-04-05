<?php
$current_path = dirname( __FILE__ );
?>
<table id="<?php echo $this->id; ?>" class="ari-grid<?php if ( $this->options->options->class ) echo ' ' . $this->options->options->class; ?>">
    <thead>
    <?php
        require $current_path . '/header.php';
    ?>
    </thead>
    <tbody>
    <?php
        if ( is_array( $data ) && count( $data ) > 0 ):
            foreach ( $data as $item ):
    ?>
        <tr>
    <?php
                foreach ( $this->options->columns as $column):
                    $tag = $column->tag;
                    $column_formatter = $column->column;
                    $column_id = $column->key;
                    $column_val = $column->virtual ? null : $item->$column_id;
                    $column_title = $column->title;
                    $column_class = 'col-' . $column_id . ( $column->class ? ' ' . $column->class : '' );
    ?>
            <<?php echo $tag; ?> class="<?php echo $column_class; ?>"<?php if ( $column_title ): ?> data-colname="<?php esc_attr_e( $column_title ); ?>"<?php endif; ?>>
            <?php
                if ( is_callable( $column_formatter ) )
                    echo $column_formatter( $column_val, $item );
                else if ( is_null( $column_formatter ) )
                    echo $column_val;
                else
                    echo $column_formatter;
            ?>
            </<?php echo $tag; ?>>
            <?php
                endforeach;
            ?>
        </tr>
    <?php
            endforeach;
        else:
            $column_count = count( $this->options->columns );
    ?>
    <?php
    ?>
        <tr class="no-items">
            <td class="colspanchange" colspan="<?php echo $column_count; ?>">
                <?php echo $this->options->options->no_data_message; ?>
            </td>
        </tr>
    <?php
        endif;
    ?>
    </tbody>
    <?php
        if ( $this->options->options->show_footer ):
    ?>
    <tfoot>
    <?php
        require $current_path . '/header.php';
    ?>
    </tfoot>
    <?php
        endif;
    ?>
</table>