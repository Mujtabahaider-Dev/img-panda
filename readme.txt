=== Mak8it Smart Image ===
Contributors: mak8it
Tags: webp, image optimization, ai alt text, performance, seo
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Auto-convert images to WebP and generate AI-powered SEO alt text. Boost site speed and accessibility in one click.

== Description ==

**Mak8it Smart Image** is a premium-grade WordPress plugin designed to supercharge your website's performance and SEO. It automatically converts your media library images to the modern WebP format — reducing file sizes by 40–80% — while using state-of-the-art Vision AI to write descriptive, SEO-optimized alt text for you.

= Key Features =

* **WebP Conversion Engine** — Automatically converts JPEG and PNG uploads to WebP using your server's GD Library or Imagick. No external service required for conversion.
* **AI Alt Text Generator** — Optionally uses Google Gemini or OpenAI to generate descriptive, SEO-first alt text for every image you upload.
* **One-Click Bulk Optimizer** — Scans and converts your entire existing media library in the background with real-time progress tracking.
* **Safe Backup Mode** — Keep a secure backup of your original JPEG/PNG files alongside the new WebP versions.
* **SaaS-Style Dashboard** — A modern, high-fidelity admin interface with real-time stats, disk savings counter, and optimization health chart.
* **Custom API Support** — Supports custom API endpoints and proxies for both Google Gemini and OpenAI.

= How WebP Conversion Works =

When a new image is uploaded to your media library, Mak8it Smart Image automatically converts it to WebP on your own server using PHP's GD Library or Imagick. No data leaves your server during this step.

= How AI Alt Text Works =

If you choose to enable the AI alt text feature and provide your own API key, the plugin will send a small, resized (max 512px) version of each uploaded image to the AI provider of your choice (Google Gemini or OpenAI) to generate a short, SEO-friendly description. This feature is **disabled by default** and only activates when you explicitly turn it on and enter your API key.

== Installation ==

1. Upload the `mak8it-smart-image` folder to the `/wp-content/plugins/` directory, or install it directly through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Mak8it Smart Image** in the admin menu.
4. Configure your WebP quality and file-handling settings.
5. (Optional) Enable the AI Vision Engine, choose a provider (Gemini or OpenAI), and enter your API key to start generating alt text automatically.

== Frequently Asked Questions ==

= Does my server need special software for WebP conversion? =

Yes. Mak8it Smart Image requires either GD Library (with WebP support, available on most hosts) or Imagick. The plugin automatically detects and displays your server's support status on the System Info page.

= Will my original images be deleted? =

Only if you select the "Overwrite original files" option in the Compression Settings. The default mode ("Keep backup of originals") keeps both the original JPEG/PNG and the new WebP file.

= Is my data sent to any external service? =

Only if you explicitly enable the AI alt text feature and enter your own API key. WebP conversion happens entirely on your own server. See the "External Services" section for full details.

= Which AI providers are supported? =

Google Gemini (default, free tier available) and OpenAI (GPT-4o and compatible models). You must provide your own API key for either service.

= Can I use a custom or proxy API endpoint? =

Yes. The "Custom API URL" field in the AI Vision Engine settings lets you point the plugin to any compatible endpoint.

= Does the plugin support bulk conversion of existing images? =

Yes. Use the **Bulk Converter** page to scan and convert your entire existing media library. Progress is displayed in real time.

= What image formats does the plugin convert? =

JPEG (`.jpg`, `.jpeg`) and PNG (`.png`) images are supported. GIF and SVG files are skipped automatically.

== Screenshots ==

1. Dashboard — Optimization health chart, stat cards, and recent conversions table.
2. Bulk Converter — Real-time progress bar with per-image status.
3. Settings — AI Vision Engine configuration, compression level slider, and file-handling strategy.
4. System Info — Server compatibility check showing PHP, GD Library, and Imagick status.

== Changelog ==

= 1.0.0 =
* Initial release.
* WebP conversion via GD Library and Imagick.
* AI alt text generation via Google Gemini and OpenAI.
* One-click bulk optimizer with real-time progress.
* SaaS-style admin dashboard with optimization health chart.
* Custom API URL / proxy endpoint support.
* Safe backup mode to preserve original image files.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade steps required.

== External Services ==

This plugin optionally connects to external AI services to generate image alt text. These features are **disabled by default** and are only activated when you explicitly enable them and provide your own API key.

= Google Gemini (Google Generative Language API) =

When the AI alt text feature is enabled and "Google Gemini" is selected as the provider, the plugin sends a resized (max 512×512 px), base64-encoded JPEG version of each newly uploaded image to Google's Generative Language API to generate a short, SEO-friendly description.

**Data sent:** A small base64-encoded thumbnail of the uploaded image, plus a text prompt asking for an alt text description. No personally identifiable user data is transmitted.
**When it is sent:** Only when a new image is uploaded to the media library AND the AI alt text feature is enabled AND a valid Gemini API key is configured.
**Service provider:** Google LLC

* Service home: https://ai.google.dev
* Terms of Service: https://policies.google.com/terms
* Privacy Policy: https://policies.google.com/privacy

= OpenAI =

When the AI alt text feature is enabled and "OpenAI" is selected as the provider, the plugin sends a resized (max 512×512 px), base64-encoded JPEG version of each newly uploaded image to the OpenAI API to generate a short, SEO-friendly description.

**Data sent:** A small base64-encoded thumbnail of the uploaded image, plus a text prompt asking for an alt text description. No personally identifiable user data is transmitted.
**When it is sent:** Only when a new image is uploaded to the media library AND the AI alt text feature is enabled AND a valid OpenAI API key is configured.
**Service provider:** OpenAI, L.L.C.

* Service home: https://openai.com
* Terms of Service: https://openai.com/policies/terms-of-use
* Privacy Policy: https://openai.com/policies/privacy-policy
