=== Memberful WP ===
Contributors: matt-button, drewstrojny
Tags: memberful, member, memberships, recurring payments, subscriptions, stripe, oauth, oauth2
Requires at least: 3.6
Tested up to: 3.6
Stable tag: 1.0.1
License: GPLv2 or later

Sell memberships and restrict access to content with WordPress and Memberful.

== Description ==

Integrates your WordPress site with [Memberful](http://memberful.com) using the Memberful OAuth 2.0 endpoint. Memberful is a service for selling recurring memberships to your website with [Stripe](https://stripe.com).

Features include:

* Automatic syncing of your Memberful member data to WordPress.
* Single sign on: Members are automatically signed into WordPress when they sign in to Memberful.
* Restrict access to content. Quickly protect any posts or pages right from your WordPress edit screen.
* A widget with links to sign in and manage your account (update credit card, cancel subscription, etc.).
 
== Installation ==
 
1. Install Memberful WP via the WordPress.org plugin directory, or download the file and visit Plugins => Add New => Upload from your WordPress dashboard.
2. Activate the WordPress service from your Memberful account.
3. From the plugin screen in your WordPress dashboard paste in your registration key and click the "Connect to Memberful" button.

== Frequently Asked Questions ==

= Do I need a Memberful account to use the plugin? =

Yes. The plugin connects with the Memberful service to bring content protection and single sign on features to your WordPress website.

= How do I contribute to Memberful WP or report bugs? =

Glad you asked! We manage development of the plugin over at the [Memberful WP Github repository](https://github.com/memberful/memberful-wp). Please report any bugs there. If you want to help fix something or contribute a feature, submit a [pull request](https://help.github.com/articles/using-pull-requests).

== Screenshots ==

1. Sync your members and their permissions.
2. Restrict access to posts or pages.
3. Simple sign in and account management widget.

== Changelog ==

= 1.0.1 =
* Cleanup release to ensure WordPress can correctly auto-upgrade the plugin

= 1.0.0 =
* New and improved Restrict Access meta box on the post and page edit screen.
* Improved included debugging tools.
* Stop syncing deleted subscriptions and products.
* Added more shortcodes: account, sign in, sign out.
* Improved error messages.
* Ensure proper re-directs on sign out.


