<script type="text/javascript">
   window.MemberfulOptions = {
     site: "<?php echo get_option( 'memberful_site' ) ?>",
     intercept: [
       "<?php echo memberful_sign_in_url(); ?>"
     ]
   };

   (function() {
     var s = document.createElement('script');
     s.type = 'text/javascript';
     s.async = true;
     s.src = '<?php echo MEMBERFUL_EMBED_HOST ?>/assets/embedded.js';

     setup = function() { window.MemberfulEmbedded.setup(); }

     if(s.addEventListener) {
       s.addEventListener("load", setup, false);
     } else {
       s.attachEvent("onload", setup);
     }

     ( document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0] ).appendChild( s );
   })();
</script>

