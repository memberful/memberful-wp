<?php echo $before_widget ?>
<?php if ( ! empty($title) ): ?>
  <?php echo $before_title, $title, $after_title ?>
<?php endif; ?>
<?php if ( ! empty( $show_member_profile ) ): ?>
  <div class="memberful-profile-gravatar">
    <?php echo get_avatar( wp_get_current_user()->user_email, 48 ); ?>
  </div>
  <div class="memberful-profile-info">
    <div class="memberful-profile-name"><?php echo esc_html( memberful_wp_member_name( wp_get_current_user() ) );  ?></div>
    <div class="memberful-profile-links">
      <?php echo memberful_wp_format_widget_links($signed_in_links); ?>
    </div>
  </div>
<?php else: ?>
  <?php echo memberful_wp_format_widget_links( $signed_out_links ); ?>
<?php endif; ?>
<?php echo $after_widget ?>
