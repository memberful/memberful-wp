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

  var insertPodcastsShortcode = function(editor) {
    function handleDialogSubmit(editor, id) {
      var content;
      var openingTag;

      if (id) {
        content = "Access the podcast"
        openingTag = "[memberful_podcasts_link podcast=" + id + "]"
      } else {
        content = "Access your Podcasts"
        openingTag = "[memberful_podcasts_link]"
      }

      wrapContentsWithShortcode(
        editor,
        openingTag,
        "[/memberful_podcasts_link]",
        content
      );
    }

    function feedSelectOptions(feed) {
      return { text: feed.name, value: feed.id };
    };

    var feeds = window.MemberfulData.feeds;
    if (feeds.length > 1) {
      var allPodcasts = { name: "All podcasts", id: null }
      feeds = [allPodcasts].concat(feeds);
    }

    var feedOptions = feeds.map(feedSelectOptions);
    var feedList = {
      name: "item",
      type: "listbox",
      label: "Podcast",
      values: feedOptions
    };

    editor.windowManager.open({
      title: "Choose a podcast",
      width: 350,
      height: 60,
      body: [
        feedList,
      ],
      onSubmit: function(e) {
        handleDialogSubmit(editor, e.data.item);
      }
    });
  }

  function insertLinkToDownload(editor) {
    downloadItemCtrl = {
      name: "downloadSlug",
      type: "listbox",
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
    function optionsForPurchasables(current) {
      return {text: current.name, value: current.slug};
    };

    var checkoutItemCtrl = {
      name: "item",
      type: "listbox",
      label: options.label,
      values: options.choices.map(optionsForPurchasables)
    };

    var linkTextCtrl = {
      name: "linkText",
      type: "textbox",
      label: "Link text"
    };

    var body = [checkoutItemCtrl, linkTextCtrl];

    if(options.showPrice) {
      var priceCtrl = {
        name: "price",
        type: "textbox",
        label: "Price (optional)",
        tooltip: "Works only with plans where members can choose what they pay."
      };

      body.push(priceCtrl);
    }

    editor.windowManager.open({
      title: options.dialogTitle || "Link to checkout",
      body: body,
      onSubmit: function(e) {
        (options.onSubmit || function() {})(editor, e.data.item, e.data.linkText, e.data.price);
      }
    });

  }

  function insertSubscriptionCheckoutLink(editor) {
    handleDialogSubmit = function(editor, plan, linkText, price = null) {
      let shortcode = `[memberful_buy_subscription_link plan=${plan}`;
      if(price) {
        shortcode += ` price=${price}`
      }
      shortcode += `]${linkText}[/memberful_buy_subscription_link]`;

      editor.insertContent(shortcode);
    };

    insertCheckoutLinkDialog(
      editor,
      {
        label: "Plan to subscribe to",
        choices: window.MemberfulData.plans,
        onSubmit: handleDialogSubmit,
        showPrice: true
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

  function insertFeedUrl(editor) {
    function handleDialogSubmit(editor, id) {
      editor.insertContent(
        "[memberful_podcast_url podcast='"+id+"']"
      );
    }

    function feedOptions(feed) {
      return {text: feed.name, value: feed.id};
    };

    var feeds = window.MemberfulData.feeds;
    var feedList = {
      name: "item",
      type: "listbox",
      label: "Podcast",
      values: feeds.map(feedOptions)
    };

    editor.windowManager.open({
      title: "Choose a podcast",
      width: 350,
      height: 60,
      body: [
        feedList,
      ],
      onSubmit: function(e) {
        handleDialogSubmit(editor, e.data.item);
      }
    });
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

      menu.push({text: 'Private WordPress RSS Feed link', onclick: function() { insertPrivateRSSFeedShortcode(editor); }});

      if (window.MemberfulData.feeds.length > 0) {
        menu.push({text: 'Link to Podcasts', onclick: function() { insertPodcastsShortcode(editor); }});
        menu.push({text: 'Show Podcast URL', onclick: function() { insertFeedUrl(editor); }});
      }

      editor.addButton('memberful_wp', {
        type: 'menubutton',
        text: 'Memberful',
        menu: menu
      });
    }
  });

  tinymce.PluginManager.add( 'memberful_wp', tinymce.plugins.memberful_wp );
})();
