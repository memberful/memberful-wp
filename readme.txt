=== Memberful WP ===
Contributors: matt-button, drewstrojny
Tags: memberful, member, memberships, recurring payments, recurring billing, paywall, subscriptions, stripe, oauth, oauth2
Requires at least: 3.6
Tested up to: 3.8
Stable tag: 1.5.0
License: GPLv2 or later

Sell memberships and restrict access to content with WordPress and Memberful.

== Description ==

A simple WordPress membership plugin that integrates your site with [Memberful](https://memberful.com). Memberful is a service for selling subscriptions to your website with [Stripe](https://stripe.com).

Features include:

* Automatic syncing of your Memberful member data to WordPress.
* Single sign on: Members are automatically signed into WordPress when they sign in to Memberful.
* Create a paywall and restrict access to content. Quickly protect any posts or pages right from your WordPress edit screen.
* A widget with links to sign in and manage your account (update credit card, cancel subscription, etc.).
 
== Installation ==
 
1. Install Memberful WP via the WordPress.org plugin directory, or download the file and visit Plugins => Add New => Upload from your WordPress dashboard.
2. Activate the WordPress service from your Memberful account.
3. From the plugin screen in your WordPress dashboard paste in your registration key and click the "Connect to Memberful" button.

== Frequently Asked Questions ==

= Do I need a Memberful account to use the plugin? =

Yes. Memberful is online [membership software](https://memberful.com), and the plugin connects with the Memberful service to bring content protection and single sign on features to your WordPress website.

= Can I protect content on my WordPress website? =

Yes, you can [protect WordPress content](https://memberful.com/help/integrate/services/wordpress/protect-wordpress-content/) with Memberful. Every page and post features an meta box where you can set permissions. We also include [several helpful functions](https://memberful.com/help/integrate/services/wordpress/wordpress-functions/) for use in WordPress themes or plugins.

= How do I contribute to Memberful WP or report bugs? =

Glad you asked! We manage development of the plugin over at the [Memberful WP Github repository](https://github.com/memberful/memberful-wp). Please report any bugs there. If you want to help fix something or contribute a feature, submit a [pull request](https://help.github.com/articles/using-pull-requests).

== Screenshots ==

1. Sync your members and their permissions.
2. Restrict access to posts or pages.
3. Simple sign in and account management widget.

== Changelog ==

= 1.5.0 =
* Improve overlay's handling of members being signed into Memberful, but not WP.

= 1.4.0 =
* Enable new overlay interface after connecting to Memberful.

= 1.3.2 =
* Make the CDN host configurable via `MEMBERFUL_EMBED_HOST` constant.

= 1.3.1 =
* Load the JS for the overlay via Memberful's CDN.

= 1.3.0 =
* Add initial JS for upcoming reach popup.
* Fix bug with setting `MEMBERFUL_SSL_VERIFY`.

= 1.2.0 =
* Allow plugins to change the URL the user is sent to after sign in/sign out via `memberful_wp_after_sign_in_url`/`memberful_wp_after_sign_out_url` filters.

= 1.1.3 =
* Ensure errors are shown to users when authentication fails.

= 1.1.1 =
* Ensure that marketing content is swapped in before WP applies default filters to content.

= 1.1.0 =
* WordPress 3.8 styling compatibility updates.
* Move plugin option to the settings menu.

= 1.0.2 =
* Send `Cache-Control: private` header from Memberful endpoints.

= 1.0.1 =
* Cleanup release to ensure WordPress can correctly auto-upgrade the plugin.

= 1.0.0 =
* New and improved Restrict Access meta box on the post and page edit screen.
* Improved included debugging tools.
* Stop syncing deleted subscriptions and products.
* Added more shortcodes: account, sign in, sign out.
* Improved error messages.
* Ensure proper re-directs on sign out.


