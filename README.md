=== Auto Lightbox for Blocks ===
Contributors: Jitka Klingenbergová
Tags: lightbox, gallery, blocks, image, glightbox
Requires at least: 5.8
Tested up to: 6.9
Stable Tag: 1.0.8
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically adds a responsive lightbox to your WordPress block images. Supports galleries and lazy-loading.

== Description ==

Auto Lightbox for Blocks is a lightweight, zero-configuration plugin that automatically adds a beautiful lightbox to your WordPress image and gallery blocks. Built with the powerful GLightbox library.

**Key Features:**

*   **Zero Configuration:** Works out of the box with standard WordPress blocks (`.wp-block-image`, `.wp-block-gallery`).
*   **Original Image Detection:** Automatically finds and displays the full-size image, even if you inserted a smaller size.
*   **Lazy Load Support:** Fully compatible with WordPress native lazy loading.
*   **Accessibility:** Built with accessibility in mind (ARIA support, keyboard navigation).
*   **Smart Grouping:** Automatically groups images in the same gallery block. Option to group ALL page images.
*   **Touch Friendly:** Swipe navigation for mobile devices.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/auto-lightbox-for-blocks` directory, or install the plugin through the WordPress plugins screen.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Navigate to **Settings -> Auto Lightbox** to configure options (optional).

== Frequently Asked Questions ==

= Does this work with custom blocks? =
Yes! You can add any CSS selector in the plugin settings to target custom blocks or containers.

== Changelog ==

= 1.0.8 =
*   Fix release version to udpate plugin properly

= 1.0.7 =
*   Fix visible caption

= 1.0.6 =
*   Disabled captions/descriptions in lightbox view.

= 1.0.5 =
*   Switched to custom update server to avoid GitHub API rate limits.

= 1.0.4 =
*   Fixed settings persistence issue where settings would disappear after plugin updates.
*   Added version tracking and automatic settings migration.
*   Changed default for "Group All Page Images" to OFF (unchecked) for better out-of-box experience.
*   Settings now properly merge with defaults on every page load.

= 1.0.3 =
*   Refactored lightbox engine for strict block isolation using per-block GLightbox instances.
*   Fixed issue where single images were incorrectly grouped with galleries.
*   Improved compatibility with lazy-loaded images and attachment page links.

= 1.0.2 =
*   Fixed hanging issue when lightbox opening images linked to attachment pages.
*   Improved image link detection.

= 1.0.1 =
*   Added plugin update checker for WP

= 1.0.0 =
*   Initial release.
