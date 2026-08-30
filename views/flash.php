<?php if ( $message = Memberful_Wp_Reporting::pop() ): ?>
<div class="notice is-dismissible <?php echo esc_attr($message['type']); ?>">
  <p>
    <?php echo esc_html($message['message']); ?>
  </p>
</div>
<?php endif; ?>
