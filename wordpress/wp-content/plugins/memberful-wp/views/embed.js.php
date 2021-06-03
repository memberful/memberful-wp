<script type="text/javascript">
  window.MemberfulOptions = {
    site: <?php echo json_encode($site_option); ?>,
    memberSignedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>
  };

  (function() {
    var s = document.createElement('script');
    s.type = 'text/javascript';
    s.async = true;
    s.src = '<?php echo $script_src; ?>';

    setup = function() { window.MemberfulEmbedded.setup(); };

    s.addEventListener("load", setup, false);

    ( document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0] ).appendChild( s );
  })();
</script>
