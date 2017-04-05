<?php
$options = $this->options;
?>
<tr<?php if ( $options->options->header_class ): ?> class="<?php echo $options->options->header_class; ?>"<?php endif; ?>>
    <?php
        foreach ( $options->columns as $column):
            $header_class = $column->header_class;
            $tag = $column->header_tag;
            if ( $column->sortable ) {
                $header_class .= ' sortable';

                if ( $column->key == $options->options->order_by )
                    $header_class .= ' sort sort-' . strtolower( $options->options->order_dir );
            }
    ?>
    <<?php echo $tag; ?><?php if ( $header_class ): ?> class="<?php echo $header_class; ?>"<?php endif; ?><?php if ( $column->sortable ): ?> data-sort-column="<?php echo $column->key; ?>" data-sort-dir="<?php echo $column->key == $options->options->order_by ? $options->options->order_dir : ''; ?>"<?php endif; ?>>
        <?php
            if ( is_callable( $column->header ) ) {
                $header_formatter = $column->header;
                echo $header_formatter();
            } else
                echo $column->header;
        ?>
    </<?php echo $tag; ?>>
    <?php
        endforeach;
    ?>
</tr>