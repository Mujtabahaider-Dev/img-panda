# 🐼 Img Panda — Development Roadmap

This document outlines future features, UI improvements, and monetization strategies to take **Img Panda** to a professional, premium level.

---

## 1. Functional Improvements
*   **"Missing Alt Text" Filter (High Priority)**  
    Update the Bulk Optimizer to selectively target only images with empty alt tags. This is a major SEO efficiency selling point.
*   **AVIF Support**  
    Add support for [AVIF](https://aomediacodec.github.io/av1-avif/), the next-generation image format that offers significantly better compression than WebP.
*   **Custom AI Prompting**  
    Allow users to provide a global "context" or "instruction" for the AI (e.g., *"Describe images for a luxury furniture brand"* or *"Always include the keyword 'organic'"*).

## 2. User Experience (UX)
*   **Onboarding Wizard**  
    A first-run setup guide to help users connect their API Keys and set their initial compression preferences.
*   **Before/After Quality Slider**  
    In the settings dashboard, provide a live preview of original vs. compressed quality using a sample image and a comparison slider.
*   **Granular Bulk Filters**  
    Enable filtering by **Post Type** (e.g., optimize only WooCommerce Product images) or **Category**.

## 3. Technical & Performance
*   **Autonomous Background Processing**  
    Migrate from browser-based polling to `WP-Cron` or `Action Scheduler`. This allows users to start a bulk job and close their dashboard while the work continues.
*   **WP.org Compliance (Crucial)**  
    Bundle all assets locally. Specifically, the **Material Symbols font** must be moved from Google Fonts CDN to the `/admin/fonts/` directory for repository acceptance.
*   **AI Pre-processing Cache**  
    Store the resized 512px thumbnails used for AI analysis in a temporary cache to avoid re-generating them during multiple runs.

## 4. Monetization & Scaling
*   **External CDN Offloading**  
    Implement a "Push" mechanism to upload WebP files to external storage (S3, Bunny.net, etc.) and serve them from a global CDN. This saves local disk space and server bandwidth.
*   **API Key Reselling (Managed AI)**  
    Launch "Img Panda Cloud." Instead of requiring users to bring their own Gemini/OpenAI keys, offer a subscription/credit system where the plugin connects to a central proxy server managed by you.
*   **Premium Add-ons**  
    Create a "Pro" version with feature-gated capabilities like AVIF conversion and CDN serving.

---

*Last Updated: May 2026*
