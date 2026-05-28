=== Img Panda ===
Contributors: mak8it
Tags: webp, image optimization, convert, performance, speed
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically convert images to WebP format for better performance. Includes AI-powered alt text generation via Google Gemini or OpenAI.

== Description ==

**Img Panda** is a premium-grade WordPress plugin designed to supercharge your website's performance and accessibility. It automatically converts your media library to WebP while using state-of-the-art Vision AI to write SEO-optimized Alt Text for you.

= Key Features =

* **AI Vision Suite**: Automatically generates descriptive, SEO-first Alt Text using Google Gemini or OpenAI.
* **Ultra-Fast WebP Conversion**: Reduces image file sizes by 40-80% without losing quality.
* **One-Click Bulk Optimizer**: Scans and optimizes your entire existing library in the background.
* **Sacred Backup**: Always keeps a secure backup of your original JPEG/PNG files.
* **SaaS-Style Dashboard**: A modern, high-fidelity interface with real-time stats and savings health.

== Installation ==

1. Upload the `img-panda` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Img Panda** in the admin menu and configure your settings.

== Frequently Asked Questions ==

= Does my server need to support WebP? =

Yes. Img Panda requires either GD Library (with WebP support) or Imagick. The plugin will detect and display your server's support status on the settings page.

= Will my original images be deleted? =

Only if you choose the "Replace original with WebP" option under File Handling. By default, both files are kept.

= Is my data sent to third-party services? =

Only if you explicitly enable the AI alt text feature and provide your own API key. No data is sent by default.

== Screenshots ==

1. Dashboard with image stats and savings overview.
2. Bulk Converter page with progress tracking.
3. Settings page with quality control and file handling options.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== External Services ==

This plugin optionally uses AI services to generate image alt text.
The feature is only triggered when manually enabled by the user
after providing their own API key.

= OpenAI =
Image alt text requests are sent to OpenAI's API.
- https://openai.com
- Terms: https://openai.com/policies/terms-of-use
- Privacy: https://openai.com/policies/privacy-policy

= Google Gemini =
Image alt text requests are sent to Google's Generative Language API.
- https://ai.google.dev
- Terms: https://policies.google.com/terms
- Privacy: https://policies.google.com/privacy

No data is sent without the user providing their own API key
and explicitly enabling the AI alt text feature.
