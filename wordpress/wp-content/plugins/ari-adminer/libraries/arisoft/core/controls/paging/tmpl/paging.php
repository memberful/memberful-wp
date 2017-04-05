<?php
$page_count = $this->get_page_count();
$page_num = $this->options->page_num;

$enabled_first_btn = $page_num > 0;
$enabled_last_btn = $page_num < $page_count - 1;
$page_buttons = $this->get_page_buttons();
?>
<div class="col s12 m6 l8 right-align paging ari-paging">
    <select class="go-to-page right browser-default" autocomplete="off">
        <option value="-1" selected="selected"><?php echo $this->options->go_to_message; ?></option>
        <?php
        for ( $i = 0; $i < $page_count; $i++ ):
            ?>
            <option value="<?php echo $i; ?>"<?php if ( $i == $page_num ): ?> disabled="disabled"<?php endif; ?>><?php echo ( $i + 1 ); ?></option>
        <?php
        endfor;
        ?>
    </select>
    <ul class="pagination right">
        <li class="<?php echo $enabled_first_btn ? 'waves-effect' : 'disabled'; ?>"><a href="#"<?php if ( $enabled_first_btn ): ?> class="grid-page" data-page="0"<?php else: ?> class="disabled" onclick="this.blur();return false;"<?php endif; ?>>«</a></li>
        <li class="<?php echo $enabled_first_btn ? 'waves-effect' : 'disabled'; ?>"><a href="#"<?php if ( $enabled_first_btn ): ?> class="grid-page" data-page="<?php echo $page_num - 1; ?>"<?php else: ?> class="disabled" onclick="this.blur();return false;"<?php endif; ?>>‹</a></li>
        <?php
        foreach ( $page_buttons as $i ):
            $page_css_class = ( $i == $page_num ) ? 'active blue' : 'waves-effect';
            ?>
            <li class="<?php echo $page_css_class; ?>"><a href="#" class="grid-page" data-page="<?php echo $i; ?>"><?php echo ( $i + 1 ); ?></a></li>
        <?php
        endforeach;
        ?>
        <li class="<?php echo $enabled_last_btn ? 'waves-effect' : 'disabled'; ?>"><a href="#"<?php if ( $enabled_last_btn ): ?> class="grid-page" data-page="<?php echo $page_num + 1; ?>"<?php else: ?> class="disabled" onclick="this.blur();return false;"<?php endif; ?>>›</a></li>
        <li class="<?php echo $enabled_last_btn ? 'waves-effect' : 'disabled'; ?>"><a href="#"<?php if ( $enabled_last_btn ): ?> class="grid-page" data-page="<?php echo $pages_count - 1; ?>"<?php else: ?> class="disabled" onclick="this.blur();return false;"<?php endif; ?>>»</a></li>
    </ul>
    <br class="clearfix" />
</div>
