=== Img Panda ===
Contributors: mak8it
Tags: webp, image optimization, seo, alt text, compression, performance, ai
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically convert images to WebP and generate AI-powered SEO Alt Text for stunning performance and accessibility.

== Description ==

Img Panda is a professional image optimization and SEO automation plugin. It combines high-speed WebP conversion with cutting-edge Vision AI to ensure your WordPress site is fast, accessible, and search-engine friendly.

Beyond simple compression, Img Panda transforms your Media Library into a performance powerhouse. WebP images are significantly smaller than traditional JPEG and PNG files, while our AI SEO Suite handles the tedious task of writing descriptive Alt Text for your images.

== Key Features ==

*   **AI SEO Suite** - Automatically generate descriptive Alt Text using Google Gemini or OpenAI.
*   **Automatic WebP Conversion** - Seamlessly converts images to WebP format as you upload them.
*   **Bulk Optimization** - Optimized your entire existing library with a single click.
*   **Intelligent Serving** - Detects browser support and serves WebP automatically, with fallback for old browsers.
*   **SaaS-Style Dashboard** - Monitor savings, health, and recent optimizations in a modern, tabbed interface.
*   **Space Savings Stats** - Track exactly how much disk space and bandwidth you've saved.
*   **Full Backup Safety** - Always keeps a secure backup of your original images.

== How It Works ==

1.  **Optimize**: When you upload an image, Img Panda compresses it to WebP.
2.  **Describe**: Our AI analyzes the image and writes a perfect SEO-optimized Alt Text.
3.  **Deliver**: Your visitors receive faster-loading images without any quality loss.

== Requirements ==

*   WordPress 5.8 or higher
*   PHP 7.4 or higher
*   GD Library or Imagick with WebP support
*   (Optional) API Key for Gemini or OpenAI for AI features

== Privacy & External Services ==

This plugin can connect to external AI services to provide image descriptions:
*   **Google Gemini API**: Used for AI Alt Text generation if selected. [Privacy Policy](https://ai.google.dev/terms)
*   **OpenAI API**: Used for AI Alt Text generation if selected. [Privacy Policy](https://openai.com/policies/privacy-policy)

No data is sent to these services unless you manually enable the AI SEO feature and provide your own API key.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/img-panda/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **Img Panda** in your admin sidebar.
4. Configure your quality settings and (optionally) link your AI API key.

== Screenshots ==

1. The modern Dashboard with real-time optimization health and savings stats.
2. The AI SEO Suite configuration with provider selection.
3. Bulk Optimizer in action with detailed progress logs.
4. Recent optimizations history table.

== Changelog ==

= 1.2.0 =
*   **NEW**: AI SEO Suite integration for automated Alt Text generation.
*   **NEW**: Support for Google Gemini and OpenAI Vision models.
*   **NEW**: Tabbed Sidebar UI for a more compact and organized settings menu.
*   **IMPROVED**: Bulk processor now handles Alt Text generation independently of compression.
*   **FIXED**: Optimization history table now correctly shows images that were replaced by WebP.

= 1.1.0 =
*   Standardized plugin prefixes to `img_panda_` for WordPress.org compliance.
*   Added automatic migration from legacy option names.
*   Improved system audit logs.

= 1.0.0 =
*   Initial release with WebP conversion and Bulk Optimizer.

== Upgrade Notice ==

= 1.2.0 =
Added AI SEO features and a major UI overhaul. Upgrade for better accessibility and a cleaner dashboard!
