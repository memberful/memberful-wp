(function(){

	var wrapContentsWithShortcode = function(editor, before, after, default) {
		var selection = editor.selection.getContent({format: "text"}) || "";
		var contentToWrap = selection == "" ? default : selection;

		editor.insertContent(before+contentToWrap+after);
	}

	var insertRegistrationShortcode = function(editor) {
		wrapContentsWithShortcode(
			editor,
			"[memberful_register_link]",
			"[/memberful_register_link]",
			"Sign up for a free account!"
		);
	}

	var insertSignInShortcode  = function(editor) {
		wrapContentsWithShortcode(
			editor,
			"[memberful_sign_in_link]",
			"[/memberful_sign_in_link]",
			"Sign in to your account."
		);
	}

	var insertPurchaseLink = function(editor) {
	

	};

	 /* Register the buttons */
	 tinymce.create('tinymce.plugins.memberful_wp', {
		init : function(editor, url) {
			editor.addButton('memberful_wp', {
				type: 'menubutton',
				text: 'Memberful',
				icon: true,
				menu: [
					{text: 'Registration Link', onclick: function() { insertRegistrationShortcode(editor); }},
					{text: 'Purchase Link', onclick: function() { insertPurchaseShortcode(editor); }},
				]
			});
		 }
	 });

	 tinymce.PluginManager.add( 'memberful_wp', tinymce.plugins.memberful_wp );
})();
