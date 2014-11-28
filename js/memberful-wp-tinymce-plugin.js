(function(){

	var wrapContentsWithShortcode = function(editor, before, after, placeholder) {
		var selection = editor.selection.getContent({format: "text"}) || "";
		var contentToWrap = selection == "" ? placeholder : selection;

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

	var insertCheckoutLink = function(editor) {
		var checkoutItemCtrl = {
				name: "item",
				type: "listbox",
				text: "Choose a download or plan",
				label: "Item to buy",
				values: []
			},
			linkTextCtrl= {
				name: "linkText",
				type: "textbox",
				label: "Link text"
		};

		editor.windowManager.open({
			title: "Link to checkout",
			body: [
				checkoutItemCtrl,
				linkTextCtrl
			]
		});

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
					{text: 'Checkout Link', onclick: function() { insertCheckoutLink(editor); }},
				]
			});
		 }
	 });

	 tinymce.PluginManager.add( 'memberful_wp', tinymce.plugins.memberful_wp );
})();
