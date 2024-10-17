=== Memberful - Membership Plugin ===
Contributors: drewstrojny, jakememberful, julianmemberful, lucasmemberful, patrikmemberful
Tags: membership, subscriptions, paywall, stripe, recurring payments, memberful, oauth, oauth 2.0, members, recurring billing
Requires at least: 3.6
Tested up to: 6.5
Requires PHP: 7.0
Stable tag: 1.73.9
License: GPLv2 or later

Sell memberships and restrict access to content with WordPress and Memberful.

== Description ==

[Memberful](https://memberful.com/) is a best-in-class membership software and WordPress plugin used by some of the web's biggest creators, publishers, and independent media companies.

Generate reliable, recurring revenue by selling subscription plans while protecting access to your content. Memberful allows you to maintain full control and ownership of your audience, your brand, and your business.

## Protect Content

- Protect members-only content and select which subscribers get access to gated areas of your website using server-side content protection.
- Restrict access to content based on membership level and protect specific posts or pages right from your WordPress edit screen.
- Manage access to media including blog articles, in-house newsletters, private podcasts, protected downloads, and more.

## Integrations

- Built-in integrations with popular third-party services including Mailchimp, Discord, ConvertKit, Klaviyo, and Google Analytics.
- Add new custom applications or develop your own using the Memberful API.
- Utilize webhooks to trigger when important events happen on your site, such as when a member changes their payment/tax details.

## Membership Management

- Automatically sync your Memberful membership data to WordPress or import a list of your existing members.
- Add multiple staff users and manage their account permissions.
- Utilize built-in analytics dashboards to monitor and grow your business.

## Payment and Plans

- Easily accept payments through your Stripe account.
- Streamlined member checkout process with Single-Sign-On (SSO) and Apple & Google Pay integration.
- Create multiple membership plans and upgrades to allow tiered access to different areas of your website.
- Discounts, Referrals, and Retention bonuses to incentivize sign-ups and membership upgrades.
- Enable automatic tax calculations based on where you and your members are located.

== Installation ==

The Memberful WordPress plugin makes it easy to integrate Memberful with your WordPress website and protect content—serverside—for members only.

Install Memberful on a self-hosted WordPress site or a site hosted with WordPress.com— Creator plan or higher. If you are not on their Creator plan (or higher), and don’t want to upgrade, you can use the WordPress.com guided transfer service to move your content over to a self-hosted WordPress website.

**Install the Memberful WordPress plugin**

1. From your Memberful dashboard navigate to *Website → Settings* and click the link to activate the WordPress service.
2. Activate the WordPress service
3. Copy the WordPress registration key (ctrl/cmd + c).
4. Install and activate the plugin on your WordPress site. Paste your registration key (ctrl/cmd + v) into the field and click Connect to Memberful.
5. Once connected, your members will be signed into WordPress when they sign into Memberful. Their plans are also automatically synced, which makes it easy to protect WordPress content.

**Review the plugin settings**

Navigate to *Settings → Memberful* in your WordPress dashboard to customize the appearance and behavior of the Memberful plugin.

- Keep all WordPress users logged in for 1 year.
- Hide the WordPress admin toolbar from members.
- Block WordPress dashboard access from members.
- Conditionally show Sign in, Sign out, and Account menu items based on members' signed-in status.
- Update display names in WordPress when members change their full name in Memberful.

**Configure role mapping**

The Memberful WordPress plugin can assign different roles to active (paying) members versus inactive (non-paying) members. Memberful will automatically keep the role mappings in sync.

Navigate to *Settings → Memberful → Advanced Role Mapping* in your WordPress dashboard to map member roles.

For a detailed walkthrough, visit [https://memberful.com/help/wordpress-plugin/setup/install-the-memberful-plugin/](https://memberful.com/help/wordpress-plugin/setup/install-the-memberful-plugin/)

== Frequently Asked Questions ==

= Do I need a Memberful account to use the plugin? =

Yes. [Memberful](https://memberful.com) is a hosted membership service, and the plugin connects with Memberful to bring content protection, membership management, and single sign on features to your WordPress website.

= Can I protect content on my WordPress website? =

Yes, you can [protect WordPress content](https://memberful.com/help/integrate/services/wordpress/protect-wordpress-content/) with Memberful. Every page and post features a restrict access meta box where you can set specific permissions. We also include [several helpful functions](https://memberful.com/help/integrate/services/wordpress/wordpress-functions/) for use in WordPress themes or plugins.

= How do I contribute to the plugin or report bugs? =

Glad you asked! We manage development of the plugin over at the [Memberful WP Github repository](https://github.com/memberful/memberful-wp). Please report any bugs there. If you want to help fix something or contribute a feature, submit a [pull request](https://help.github.com/articles/using-pull-requests).

== Screenshots ==

1. View your user dashboard
2. Install the Memberful WordPress plugin
3. Customize your page branding
4. Bulk restrict access to content
5. Name and price your plan
6. Add Default Marketing Content
7. Create dynamic posts
8. Create a subscription landing page
9. Choose billing frequency and subscription length
10. Enable the private RSS feed
11. Enable fade-out teaser text
12. Restrict content by category or tag
13. Copy plan purchase links
14. Access shortcodes via the Memberful editor button
15. Restrict access with shortcodes
16. Customize your checkout process
17. Enable free or paid trials
18. View metrics and revenue reports
19. View membership information

== Changelog ==

= 1.73.9 =

* Check if WP User exists when mapping deleted Memberful members (#366)

= 1.73.8 =

* Sanitize and escape shortcode output (#365)

= 1.73.7 =

* Ensure `memberful_error_log` option is always an array (#363)
* Pin WP Docker image version to 6.4.0 (#364)
* Update README with new Memberful app UI paths

= 1.73.6 =

* Fix PHP deprecation warnings.

= 1.73.5 =

* Restored previously reverted fix for PHP warnings when mapping deleted members in Memberful_User_Map#map

= 1.73.4 =

* Reverted changes from version 1.73.3

= 1.73.3 =

* Fix PHP warnings when mapping deleted members in Memberful_User_Map#map

= 1.73.2 =

* Improve copy about RSS settings

= 1.73.1 =

* Prevent quote escaping in global marketing content editor

= 1.73.0 =

* Update Memberful embed host (js.memberful.com)

= 1.72.0 =

* Allow iframe tags in marketing content

= 1.71.6 =

* Fix issue configuring global marketing content when object caching is enabled.

= 1.71.5 =

* Allow oEmbed-enabled links to be embedded in marketing content.

= 1.71.4 =

* Fixed non-object access error when `memberful_wp_protect_content` runs outside of the WordPress Loop.

= 1.71.3 =

* Fix an issue when not sending ACL parameters.

= 1.71.2 =

* More security improvements.

= 1.71.1 =

* More security improvements.

= 1.71.0 =

* Security improvements.

= 1.70.2 =

* Add `home_url` to the plugin disconnect request to avoid disconnecting live integrations while using staging or development copies.

= 1.70.1 =

* Fix migration of default marketing content to global marketing content. If you updated to version 1.70.0 before, please check your "Automatically pull an excerpt from each post" setting in Settings → Memberful → Global marketing content.

= 1.70.0 =

* The “Default marketing content” setting has been moved to Settings → Memberful → Global marketing content. Previously, it was located underneath each of our marketing content boxes on posts, pages, tags, and categories.

= 1.69.1 =

* Fix PHP warnings

= 1.69.0 =

* Disconnect integration in the Memberful app when disconnecting or uninstalling the plugin

= 1.68.5 =

* Remove unnecessary error dump

= 1.68.4 =

* Fix warning when accessing potentially empty `member` attribute in `Memberful_Wp_Endpoint_Webhook`

= 1.68.3 =

* Fix shortcode support in global marketing content

= 1.68.2 =

* Fixed calling `Memberful_User_Mapping_Repository#delete_mapping` as a static method

= 1.68.1 =

* Improve copy

= 1.68.0 =

* Add optional display name synchronization with Memberful
* Add possibility to automatically generate marketing content

= 1.67.2 =

* Remove deprecated `compact` call in `ensure_present`

= 1.67.1 =

* Fix redirect issue on OAuth login

= 1.67.0 =

* Add filter (`memberful_can_user_access_rss_post`) that allows customizing access for an RSS post

= 1.66.1 =

* Gracefully handle users that don't have a Memberful ACL meta field

= 1.66.0 =

* Use an application lock to prevent duplicate users on simultaneous webhooks

= 1.65.1 =

* Fix selecting plans with the bulk restrict tool

= 1.65.0 =

* Add possibility to restrict access by custom taxonomies
* Improve the `memberful_wp_after_sign_in_url` filter

= 1.64.0 =

* Add custom domain option

= 1.63.0 =

* Add option to restrict content by category or tag
* Remove unsupported embed JS config

= 1.62.12 =

* Improve integration with Elementro Pro

= 1.62.11 =

* Fix ?memberful_endpoint=auth redirects in Chrome

= 1.62.10 =

* Change `do_blocks` priority

= 1.62.9 =

* Run `do_blocks` when protecting content to enable compatibility with some Gutenberg blocks

= 1.62.8 =

* Consider admins as active members when using restriction shortcodes

= 1.62.7 =

* Fix missing text filters on marketing content

= 1.62.6 =

* Fix content filter running on private member RSS feed
* Use configured Memberful user role when checking password reset permission

= 1.62.5 =

* Fix Memberful connection issue

= 1.62.4 =

* Fix support for restricting content made with the Elementor plugin

= 1.62.3 =

* Fix display of "Sign in" items in admin menu editor

= 1.62.2 =

* Improve unescaping of redirect_to param

= 1.62.1 =

* Fix conflicts with plugins that require Ajax-based admin access when using the new "Block dashboard for members" feature. These include stats tracking plugins like Statify, as well as Wordfence Login Security.

= 1.62.0 =

* Fix "Link to download" select box
* Add option to hide admin toolbar from members
* Add option to block WordPress dashboard from members
* Add option to filter account links in menus based on signed-in state

= 1.61.0 =

* Allow filtering of private feed title

= 1.60.0 =

* Add custom post types support to Bulk restrict access tool.
* Add support for SG Optimizer frontend optimization

= 1.59.0 =

* Add shortcode that checks for lack of active subscription

= 1.58.0 =

* Allow filtering of private feed description

= 1.57.0 =

* Add shortcode that checks for any active subscription

= 1.56.3 =

* Fix category select option when bulk restricting posts

= 1.56.2 =

* Fix overflow issue in mce dialog box

= 1.56.1 =

* Improve UI for the Restrict Acess tool

= 1.56.0 =

* Add an optional `podcast` attribute to the `memberful_podcasts_link` shortcode, allowing you to link to a specific Podcast using its ID.

= 1.55.0 =

* Add possibility to set price in "Choose what you pay" checkout links.

= 1.54.1 =

* Fix a bug affecting login after checkout.

= 1.54.0 =

* Sync Podcasts with Memberful
* Add shortcode to render link to All Podcasts in Memberful

= 1.53.0 =

* Improve the debug view.
* Improve the Memberful Overlay code snippet.

= 1.52.0 =

* Add option to block RSS feed from the iTunes and Google podcast directories.

= 1.51.0 =

* Add `trial_start_at` and `trial_end_at` fields to Subscription entity.

= 1.50.3 =

* Add ability to filter by category on the private member RSS feed. Thanks to Ryan Tvenge (ryantvenge.com) for contributing this feature.

= 1.50.2 =

* Fix a compatibility issue with Elementor Pro.

= 1.50.1 =

* Revert to simple Elementor hook.

= 1.50.0 =

* Support filtering private RSS feed by category.

= 1.49.2 =

* Fix a compatibility issue with the Elementor plugin that caused Memberful to restrict more than just the main post content.

= 1.49.1 =

* Add missing "Protected by Memberful" message alongside posts restricted to registered members or members with any active subscription.

= 1.49.0 =

* Add "Anybody subscribed to a plan" option to the restrict access tool.

= 1.48.0 =

* Improve plugin's internal database table structure.

= 1.47.0 =

* Improve support for Elementor page builder.

= 1.46.0 =

* Improve comment feeds for protected posts.

= 1.45.0 =

* Add Gutenberg support.

= 1.44.0 =

* Add basic support for WordPress 5.0.
* Remove private media content from public RSS feed.

= 1.43.1 =

* Fix for WP Ultimate Recipe Premium integration.

= 1.43.0 =

* Add support for WP Ultimate Recipe Premium.

= 1.42.1 =

* Send site URL to Memberful during activation.

= 1.42.0 =

* Simplify Memberful connection process.

= 1.41.1 =

* Fix warnings generated by LearnDash integration.

= 1.41.0 =

* Store information about free trials.

= 1.40.1 =

* Disable autoload for error log to possibly improve performance.

= 1.40.0 =

* Improve WordPress debugging.

= 1.39.0 =

* Add support for WP Ultimate Recipe.

= 1.38.1 =

* Improve URLs generated by the plugin.

= 1.38.0 =

* Add support for Elementor page builder.

= 1.37.1 =

* Improve WooCommerce support.

= 1.37.0 =

* Store activation time for member subscriptions.

= 1.36.0 =

* Add support for restricting access to WooCommerce products.

= 1.35.0 =

* Adds Learndash support. Protection is added to a course, and this protects all sub-types.

= 1.34.0 =

* Improve existing WordPress user account sync flow.

= 1.33.1 =

* Improve member synchronization with Memberful.

= 1.33.0 =

* Make debugging of plugin issues easier.

= 1.32.0 =

* Improve user synchronization with Memberful.

= 1.31.0 =

* Add gift links to the visual editor.

= 1.30.0 =

* Make automatic user deletion safer.

= 1.29.2 =

* Fix login failures in some WordPress environments.

= 1.29.1 =

* Improve error handling.

= 1.29.0 =

* Add support for past due subscriptions.
* Add class to the profile widget. Allows for more CSS modifications.

= 1.28.1 =

* Fix notice notification during member synchronization.

= 1.28.0 =

* Add Memberful links to the menu builder.
* Fix plugin registration with empty registration key.

= 1.27.0 =

* Add `autorenew` attribute to subscription information.

= 1.26.0 =

* Automatically delete WP users for members deleted in Memberful.

= 1.25.0 =

* Do not update marketing content from "Bulk restrict tool" if it is empty.

= 1.24.0 =

* Add possibility to filter post types included in private RSS feeds.

= 1.23.1 =
* Improve Private RSS feeds for logged out users.

= 1.23.0 =
* Improve compatibility with Beaver Builder.

= 1.22.5 =
* Improve cookies test description.
* More fixes for private RSS feeds.

= 1.22.4 =
* Do not trim content in feed when `<!-- more -->` tag is used.

= 1.22.3 =
* Add possibility to perform cookies test in admin interface to identify web host incompatible with Memberful WP.

= 1.22.2 =
* Do not show unnecessary notices if HTTP referer is not set.

= 1.22.1 =
* Improve compatibility with OptimizePress.

= 1.22.0 =
* Add new option to extend the WordPress user logged in time to 1 year. This matches the Memberful default and provides a better member experience. If you're an existing customer, and would like to use this new option, you must manually enable it from the plugin settings screen.
* Add widget support for the new WordPress 4.5 selective refresh feature.
* Improvements to help avoid inadvertent syncing with staging sites.
* Remove overly aggressive SSL recommendations.

= 1.21.1 =
* Fix bug with "Any registered user" checkbox.
* Fix plugin version in Changelog.

= 1.21.0 =
* Improve syncing of member access.

= 1.20.0 =
* More reliable syncing of member access.
* Fix "Buy Download" in the shortcode generator.

= 1.19.0 =
* Add basic support for the Sensei plugin.
* Make marketing content filterable.
* More reliable syncing with home_url instead of site_url.
* Clean up two PHP notices.
* Properly confirm bulk access actions.
* Add debugging information for OAuth failures.
* Synchronize subscription plans and downloads after webhook notification.

= 1.18.1 =
* Fix updating of full name in the Memberful Profile Widget.
* Fix incorrect display of "Protected by Memberful" in the post / page list.
* Fix Undefined Variable PHP Notice.
* Fix saving of the bbPress redirect setting.
* Fix setting "Any registered user" with Bulk Restrict Access tool.

= 1.18.0 =
* Don't require the openssl extension. Use the built in WordPress nonces.
* Hide comments on protected Memberful posts.
* Add support for nested shortcodes.
* Fix Bulk Restrict Access tool default content checkbox.
* Fix PHP notice: Undefined index: memberful_marketing_content.
* For Private RSS Feeds, mirror the WordPress feed count setting.
* Add a warning recommending an SSL certificate if no SSL is found.
* Only run syncing when connected to Memberful.
* Fix the formatting in the Private RSS Feeds.

= 1.17.1 =
* Bug fixes for private RSS feeds.

= 1.17.0 =
* Add support for private RSS feeds.

= 1.16.2 =
* Version bump.

= 1.16.1 =
* Make sure the UI properly reflects the ACL status. #128

= 1.16.0 =
* Simplified the way the plugin initially activates itself with Memberful.com.

= 1.15.0 =
* Fix some PHP warnings when interacting with WP_Error messages. #104
* Add a note to Post/Page list showing which posts are protected by Memberful. #110
* Add option to protect bbPress forums. Currently done at a global level from within settings panel. #71
* Regularly sync the current OAuth/Webhook URL to Memberful in a cronjob. #57

= 1.14.0 =
* Add a WP filter to allow other plugins to modify Memberful members prior to their creation in WP. #116
* Fix bug that caused special characters in site name to be sent to Memberful in escaped form. #112

= 1.13.0 =
* Sync custom field from Memberful.

= 1.12.1 =
* Fix bug when linking existing WP accounts to Memberful, caused by changes in WP 4.0. #113
* By default new users should not get the WP admin bar. #111

= 1.12.0 =
* Add [memberful_register_link] shortcode to WP plugin.
* Allow content to be shown to all registered users, regardless of their purchases. #108

= 1.11.1 =
* Ensure the widget links to the https version of sign in if on page served over SSL.
* Update WP compatibility for 4.0.

= 1.11.0 =
* Ensure the overlay targets both http and https varients of the sign in page.

= 1.10.0 =
* Fix regression that re-introduced ability to create duplicate users.
* Prevent the plugin from changing usernames for existing users.

= 1.9.0 =
* Store product description when syncing them from Memberful.
* Fix bug when switching to a different Memberful site, then signing in as a member from the old site.

= 1.8.1 =
* Fix plugin deployment problem.

= 1.8.0 =
* Add a fix that prevents duplicate users from being created during sign in.

= 1.7.1 =
* Remove a couple of debug lines that accidentally got included with 1.7.0.

= 1.7.0 =
* Internal improvements to the mapping of Memberful members to WordPress users. We now only create a mapping once the user has been created. Mapping errors are recorded to the internal error log.

= 1.6.2 =
* Update supported version tag.
* Fix choosing correct role for members when updating role settings.

= 1.6.1 =
* Fix determining current role when syncing roles.

= 1.6.0 =
* Improve how we handle the situation when a WP user account is created before Memberful member, then the Memberful member signs in.
* Introduce role mapping (see options page for more details).
* Add cron job for ensuring member profiles are kept up to date.
* Deprecate old helpers and shortcodes that reference "products", see our documentation [on functions](https://memberful.com/help/integrate/services/wordpress/wordpress-functions/) and [shortcodes](https://memberful.com/help/integrate/services/wordpress/wordpress-shortcodes/) for new versions.
* Allow admins to protect posts and pages in bulk. This will replace existing access rules for the posts and pages.
* Show admins a message if cURL extension is not installed.
* If the user signs into Memberful without using the overlay, and they weren't trying to access a specific page then the plugin used to send them to the Memberful account page. It now sends them to the WP homepage.
* Allow plugins to add/remove links in profile widget using `memberful_wp_widget_args` filter.
* Allow authors to see the content of shortcodes without purchasing required plans/products.
* Improve the mapping of Memberful members to WP members in edge cases.
* Allow author to remove current marketing text.

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


