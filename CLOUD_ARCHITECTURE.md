# 🐼 Img Panda Cloud - Architecture Memory Board

This document serves as the "Technical Soul" of the cloud-offloading feature. It contains the architecture, security patterns, and workflow for the Img Panda Cloud Gateway.

## 🔗 1. The "Middle Server" (Gateway)
- **Primary Hosting:** Render.com (Continuous) or Vercel (Serverless).
- **Technology:** Node.js / Express.js.
- **Role:** Acts as a bridge between the WordPress plugin and the Cloud Storage (Supabase/S3).
- **Public Domain:** `imgpanda.onrender.com` or `imgpanda.vercel.app`.

## 🤝 2. The Relationship Loop (Core + Bridge)
- **Img Panda (The Optimizer):** Performs the heavy lifting. It converts images to WebP, applies SEO Alt Text, and generates thumbnails. It ensures the files are as small as possible BEFORE they leave the server.
- **Panda Cloud Bridge (The Offloader):** Handles the logistics. It takes the optimized files produced by Img Panda and transports them to the cloud.
- **Synergy:** By using both, the user gets **Smart Files** (WebP/SEO) that are served from a **Fast Location** (CDN).

## 🛡️ 3. Security & Authentication
- **User Key:** Each user gets a unique `PANDA_API_KEY`.
- **Proxy Pattern:** The WordPress plugin NEVER knows the real S3/Supabase credentials. It only talks to the Middle Server. This prevents "Key Theft."
- **Encryption:** All transfers must happen over HTTPS.

## 📦 3. Storage Layer (The Vault)
- **Current Provider:** Supabase (S3-Compatible Storage).
- **Public URL Pattern:** `https://[PROJECT-ID].supabase.co/storage/v1/object/public/[BUCKET]`
- **Redirection:** The Middle Server returns a "Branded URL" (from the Vercel/Render subdomain) to the WordPress site.

## ⚙️ 4. The Offloading Workflow (Phase 1-5)
1. **Optimization:** Img Panda compresses local images to WebP.
2. **Detection:** The Cloud Bridge identifies the original and ALL thumbnail sizes.
3. **Transmission:** Securely signs the request using **S3v4 Signature** and pushes to the Gateway.
4. **Offloading:** If "Destroy Local" is enabled, the physical file is deleted from the server to save space.
5. **Rewriting:** WordPress hooks (`wp_calculate_image_srcset`) swap local URLs with Cloud URLs.

## 🛠️ 5. Key Technical Snippets
- **Auth Signer:** Uses custom `Panda_Bridge_S3_Signer` for manual HMAC-SHA256 headers.
- **Restore Logic:** One-click restoration from Cloud back to Local uploads directory.
- **Status Badges:** 
    - `OFFLOADED` (Purple): In Cloud & Local.
    - `CLOUD ONLY` (Red): In Cloud only (Space Saved).

## 🚀 Future Roadmap
- [ ] Auto-push on upload hooks.
- [ ] Bulk sync selector.
- [ ] Bandwidth usage monitoring.
- [ ] Img Panda Dashboard Integration.

---
*Last Updated: 2026-05-06*
