<script type="text/javascript">
	window.MemberfulOptions = {
		site: "<?php echo $memberful_site_url; ?>",
		intercept: [
			<?php foreach( $intercepted_urls as $url ): ?>
			"<?php echo $url; ?>",
			<?php endforeach; ?>
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
