<<<<<<< HEAD
# 🐼 Img Panda: AI-Powered Image Optimizer for WordPress

[![Version](https://img.shields.io/badge/version-1.0.0-purple.svg)](https://github.com/Mujtabahaider-Dev/img-panda)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-7.1+-0073AA.svg)](https://wordpress.org)
=======
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
>>>>>>> 52308bcd29728265eedaab4b59b81de6498be39b

**Img Panda** is a premium-grade WordPress plugin designed to supercharge your website's performance and accessibility. It automatically converts your media library to WebP while using state-of-the-art **Vision AI** to write SEO-optimized Alt Text for you.

<<<<<<< HEAD
---

## ✨ Key Features

- **🧠 AI Vision Suite**: Automatically generates descriptive, SEO-first Alt Text using Google Gemini or OpenAI. No more missing accessibility data!
- **⚡ Ultra-Fast WebP Conversion**: Reduces image file sizes by 40-80% without losing quality.
- **🚀 One-Click Bulk Optimizer**: Scans and optimizes your entire existing library in the background.
- **🛡️ Sacred Backup**: Always keeps a secure backup of your original JPEG/PNG files.
- **📊 SaaS-Style Dashboard**: A modern, high-fidelity interface with real-time stats and savings health.

---
=======
**Img Panda** is a premium-grade WordPress plugin designed to supercharge your website's performance and accessibility. It automatically converts your media library to WebP while using state-of-the-art Vision AI to write SEO-optimized Alt Text for you.

= Key Features =

* **AI Vision Suite**: Automatically generates descriptive, SEO-first Alt Text using Google Gemini or OpenAI.
* **Ultra-Fast WebP Conversion**: Reduces image file sizes by 40-80% without losing quality.
* **One-Click Bulk Optimizer**: Scans and optimizes your entire existing library in the background.
* **Sacred Backup**: Always keeps a secure backup of your original JPEG/PNG files.
* **SaaS-Style Dashboard**: A modern, high-fidelity interface with real-time stats and savings health.
>>>>>>> 52308bcd29728265eedaab4b59b81de6498be39b

## 📸 Dashboard Preview

<<<<<<< HEAD
> [!TIP]
> **Insert your Dashboard screenshot here!** (Replace the text below with your image link)
=======
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
>>>>>>> 52308bcd29728265eedaab4b59b81de6498be39b

![Img Panda Dashboard](https://raw.githubusercontent.com/Mujtabahaider-Dev/img-panda/main/screenshot.png)

<<<<<<< HEAD
---
=======
1. Dashboard with image stats and savings overview.
2. Bulk Converter page with progress tracking.
3. Settings page with quality control and file handling options.
>>>>>>> 52308bcd29728265eedaab4b59b81de6498be39b

## 🛠️ Installation

<<<<<<< HEAD
1.  **Clone** this repository into your `/wp-content/plugins/` directory:
    ```bash
    git clone https://github.com/Mujtabahaider-Dev/img-panda.git
    ```
2.  **Activate** the plugin in the WordPress Admin under the **Plugins** menu.
3.  **Configure** your settings in the **Img Panda** menu.
=======
= 1.0.0 =
* Initial release.
>>>>>>> 52308bcd29728265eedaab4b59b81de6498be39b

---

<<<<<<< HEAD
## 🔒 Privacy & AI Compliance

Img Panda respects your privacy. No data is sent to external AI providers (Google/OpenAI) unless you manually enable the AI SEO feature and provide your own API key. 

---

## 📄 License

This project is licensed under the GPLv2 or later. See the [LICENSE](LICENSE) file for details.

---

**Built with ❤️ by [Mak8it.com](https://mak8it.com)**
=======
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
>>>>>>> 52308bcd29728265eedaab4b59b81de6498be39b
