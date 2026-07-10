=== Chout - All in One ===
Contributors:       nmtnguyen56
Tags:               admin, effects, security, seo, style
Requires at least:  5.2
Tested up to:       7.0
Requires PHP:       7.4
Stable tag:         1.1.3
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

A single control panel for enabling small website features.

== Description ==

Chout - All in One brings several small website enhancements into one plugin. Each feature can be turned on or off from a dedicated settings screen, so you can keep your site focused on only the features you want to use.

= Included Features =

* **Add Featured Image Column:** Display the featured image thumbnail in the post list.
* **Add Media File Size Column:** Display file size column in the Media Library list view.
* **Add Signature to RSS:** Adds source attribution to posts shared through RSS.
* **Admin Style:** Makes the editing and administration experience cleaner and easier to read.
* **Allow SVG Files Upload:** Allow administrator users to upload SVG files safely.
* **Block IPs:** Block specific IP addresses from accessing the website, with support for AIO community blocklist.
* **Block WP-Admin Area from Non-Administrators:** Keeps the administration area limited to users who manage the website.
* **Disable Comments:** Completely disable comments and remove the Comments menu from the dashboard.
* **Disable Emojis:** Remove WordPress core emoji scripts and styles to improve page loading speed.
* **Disable jQuery Migrate:** Deregister the jquery-migrate script from the frontend to save bandwidth.
* **Disable Search & Redirect to Home:** Turns off site search and sends search attempts back to the homepage.
* **Disable XML-RPC:** Completely disable XML-RPC to improve website security and prevent brute force attacks.
* **Display Dashicons:** Makes familiar WordPress icons available on the public-facing site.
* **Keywords Everywhere:** Adds relevant keyword signals to help content be better understood.
* **Redirect to Homepage Upon Logout:** Sends users back to the homepage after they log out.
* **Remove WP Logo From Admin Bar:** Remove the WordPress logo menu from the top admin bar.
* **Scroll Add Action:** Adds a visible state change when visitors scroll to selected content.
* **Scroll Progress Bar:** Display a reading progress bar at the top or bottom of the screen as users scroll.
* **Slick Custom:** Adds support for carousel-style content displays.
* **Snow Effect:** Adds a light falling snow effect for seasonal decoration.

== Installation ==

1. Upload the `chout-all-in-one` folder to the `/wp-content/plugins/` directory.
2. Activate `Chout - All in One` from the Plugins screen.
3. Open `Chout AIO > Settings` and enable the features you want to use.
4. If an enabled feature has its own settings, use the Customize button next to it.

== Frequently Asked Questions ==

= Are features enabled automatically? =

No. All features are turned off by default so you can choose only what you need.

= Where can I configure a feature? =

Open the Chout AIO settings screen. When an enabled feature has its own settings, a Customize button appears next to it.

== Changelog ==

= 1.1.3 =

* Fix: Resolved a fatal error when adding IP addresses manually or toggling the AIO list.
* Fix: Corrected the partial matching logic for IPv6 addresses to ensure accurate blocking.

= 1.1.2 =

* Tweak: Refactored Block IPs feature to use a highly optimized PHP Hash array instead of .htaccess, improving performance for massive IP lists and preventing server slowdowns.

= 1.1.1 =

* UI: Redesigned the main settings page with a modern Card Grid layout and Toggle Switches.
* UI: Replaced standard save notices with a smooth bottom-up Toast Notification across all settings pages.
* Tweak: Enabled instant background saving via AJAX on the main settings page.
* Feature: Added 'Add Featured Image Column'.
* Feature: Added 'Add Media File Size Column'.
* Feature: Added 'Allow SVG Files Upload'.
* Feature: Added 'Disable Comments'.
* Feature: Added 'Disable Emojis'.
* Feature: Added 'Disable jQuery Migrate'.
* Feature: Added 'Disable XML-RPC'.
* Feature: Added 'Remove WP Logo From Admin Bar'.
* Feature: Added 'Scroll Add Action' (with Custom CSS support).
* Feature: Added 'Scroll Progress Bar'.

= 1.1.0 =

* Feature: Added Block IPs with manual entry, CSV bulk upload, and AIO community blocklist synchronization.

= 1.0.1 =

Add display styles for the admin interface.

= 1.0.0 =

* Initial release.
