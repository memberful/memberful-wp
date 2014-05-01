<script type="text/javascript">
	window.MemberfulOptions = {
		site: "<?php echo $site_url ?>",
		intercept: [
			"<?php echo memberful_sign_in_url(); ?>"
		],
		memberSignedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>
	};

	(function() {
		var s = document.createElement('script');
		s.type = 'text/javascript';
		s.async = true;
		s.src = '<?php echo $script_src; ?>';

		setup = function() { window.MemberfulEmbedded.setup(); }

		if(s.addEventListener) {
			s.addEventListener("load", setup, false);
		} else {
			s.attachEvent("onload", setup);
		}

		( document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0] ).appendChild( s );
	})();
</script>
