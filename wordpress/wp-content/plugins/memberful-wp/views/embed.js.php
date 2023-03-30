<script type="text/javascript">
  (function(c) {
    var script = document.createElement("script");
    script.src = "<?php echo esc_url($script_src); ?>";
    script.onload = function() { Memberful.setup(c) };
    document.head.appendChild(script);
  })({
    site: <?php echo json_encode($site_option); ?>
  });
</script>
