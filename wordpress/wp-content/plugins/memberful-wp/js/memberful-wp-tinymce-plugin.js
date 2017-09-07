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

  var insertPrivateRSSFeedShortcode = function(editor) {
    wrapContentsWithShortcode(
      editor,
      "[memberful_private_rss_feed_link]",
      "[/memberful_private_rss_feed_link]",
      "Your RSS Feed"
    );
  }

  function insertLinkToDownload(editor) {
    downloadItemCtrl = {
      name: "downloadSlug",
      type: "listbox",
      text: "Choose download",
      label: "Link to this download",
      values: window.MemberfulData.downloads.map(function(x) { return {text: x.name, value: x.slug} })
    };

    editor.windowManager.open({
      title: "Link to download",
      body: [
        downloadItemCtrl,
      ],
      onSubmit: function(e) {
        if ( ! e.data.downloadSlug )
          return;

        wrapContentsWithShortcode(
          editor,
          "[memberful_download_link download='"+e.data.downloadSlug+"']",
          "[/memberful_download_link]",
          "Download file"
        );
      }
    });
  }

  function insertCheckoutLinkDialog(editor, options) {
    var checkoutItemCtrl = {}, linkTextCtrl = {};

    function optionsForPurchasables(current) {
      return {text: current.name, value: current.slug};
    };

    options = options || {};

    checkoutItemCtrl = {
      name: "item",
      type: "listbox",
      label: options.label,
      values: options.choices.map(optionsForPurchasables)
    };

    linkTextCtrl = {
      name: "linkText",
      type: "textbox",
      label: "Link text"
    };

    editor.windowManager.open({
      title: options.dialogTitle || "Link to checkout",
      body: [
        checkoutItemCtrl,
        linkTextCtrl
      ],
      onSubmit: function(e) {
        (options.onSubmit || function() {})(editor, e.data.item, e.data.linkText);
      }
    });

  }

  function insertSubscriptionCheckoutLink(editor) {
    handleDialogSubmit = function(editor, plan, linkText) {
      var shortcode = "[memberful_buy_subscription_link plan='" + plan + "']" + linkText + "[/memberful_buy_subscription_link]";

      editor.insertContent(shortcode);
    };

    insertCheckoutLinkDialog(
      editor,
      {
        label: "Plan to subscribe to",
        choices: window.MemberfulData.plans,
        onSubmit: handleDialogSubmit
      }
    );
  };

  function insertGiftLink(editor) {
    handleDialogSubmit = function (editor, plan, linkText) {
      var shortcode = "[memberful_buy_gift_link plan='" + plan + "']" + linkText + "[/memberful_buy_gift_link]";

      editor.insertContent(shortcode);
    };

    insertCheckoutLinkDialog(
      editor,
      {
        choices: window.MemberfulData.plans,
        dialogTitle: "Link to gift",
        label: "Plan",
        onSubmit: handleDialogSubmit,
      }
    );
  };

  function insertDownloadCheckoutLink(editor) {
    function handleDialogSubmit(editor, downloadSlug, linkText) {
      editor.insertContent(
        "[memberful_buy_download_link download='"+downloadSlug+"']"+
        linkText+
        "[/memberful_buy_download_link]"
      );
    }

    insertCheckoutLinkDialog(
      editor,
      {
        label: "Download to buy",
        choices: window.MemberfulData.downloads,
        onSubmit: handleDialogSubmit
      }
    );
  }

  /* Register the buttons */
  tinymce.create('tinymce.plugins.memberful_wp', {
    init : function(editor, url) {
      if (!((ref = window.MemberfulData) != null ? ref.connectedToMemberful : void 0)) {
        return;
      }

      var menu = [];

      if (window.MemberfulData.plans.length > 0) {
        menu.push({text: 'Buy Plan', onclick: function() { insertSubscriptionCheckoutLink(editor); }});
      }

      menu.push({text: 'Sign in link', onclick: function() { insertSignInShortcode(editor); }});

      if (window.MemberfulData.plans.length > 0) {
        menu.push({text: 'Buy Gift', onclick: function() { insertGiftLink(editor); }});
      }

      if (window.MemberfulData.downloads.length > 0) {
        menu.push(
          {text: 'Buy Download', onclick: function() { insertDownloadCheckoutLink(editor); }},
          {text: 'Link to Download', onClick: function() { insertLinkToDownload(editor); }}
        );
      }

      menu.push({text: 'Free sign up link', onclick: function() { insertRegistrationShortcode(editor); }});

      menu.push({text: 'Private RSS Feed link', onclick: function() { insertPrivateRSSFeedShortcode(editor); }});

      editor.addButton('memberful_wp', {
        type: 'menubutton',
        text: 'Memberful',
        menu: menu
      });
    }
  });

  tinymce.PluginManager.add( 'memberful_wp', tinymce.plugins.memberful_wp );
})();
