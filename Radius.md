# 🐼 Img Panda — Project Memory (Radius.md)

> **Treat this file as the single source of truth for the Img Panda plugin.**
> Always read it at the start of every session. Update it when something meaningful changes.

---

## 1. Plugin Identity

| Field | Value |
|---|---|
| **Plugin Name** | Img Panda |
| **Slug** | `img-panda` |
| **Version** | 1.2.0 |
| **Text Domain** | `img-panda` |
| **Author** | Mak8it (`https://Mak8it.com`) |
| **License** | GPL v2 or later |
| **WP Min** | 5.8 |
| **WP Tested** | 6.9 |
| **PHP Min** | 7.4 |
| **Submission Target** | WordPress.org Plugin Directory |

---

## 2. Plugin Purpose & Unique Selling Points

Img Panda is a **dual-function** WordPress plugin:

1. **Image Compression** — Converts JPEG/PNG images to WebP format for smaller file sizes and faster page loads.
2. **AI SEO Suite** — Uses Vision AI (Google Gemini / OpenAI) to auto-generate SEO-friendly Alt Text, making every image accessible and unique. This AI layer is the **key differentiator** that avoids WP.org rejection for being "too similar" to existing plugins.

**Core philosophy:**
- Zero-config defaults (auto-convert on, quality 60%, keep-both mode)
- Smart rejection guard: If the WebP is *larger* than the original, the WebP is deleted and the original is kept
- Non-destructive: originals are never touched unless the user explicitly chooses "Replace" mode
- All third-party assets (Chart.js, Tailwind CSS, fonts) are **bundled locally** for WP.org compliance

---

## 3. File & Directory Structure

```
img-panda/
├── imgpanda.php              # Main entry point — constants, hooks, bootstrapper
├── readme.txt                # WP.org listing copy (changelog, screenshots, etc.)
├── uninstall.php             # Cleanup on plugin delete
├── Radius.md                 # THIS FILE — project memory
│
├── includes/                 # All PHP business logic (OOP classes)
│   ├── class-activator.php   # Activation / deactivation hooks + migration
│   ├── class-converter.php   # Core WebP conversion engine (GD & Imagick)
│   ├── class-settings.php    # Options registration, admin menu, asset enqueueing
│   ├── class-bulk-processor.php  # Batch WebP conversion engine + queue manager
│   ├── class-ai-handler.php  # AI Alt Text generation (Gemini & OpenAI)
│   ├── class-ajax-handler.php    # All wp_ajax_* action handlers
│   ├── class-stats.php       # Statistics queries & caching
│   ├── class-frontend.php    # Output-buffer-based WebP URL rewriting on frontend
│   └── index.php             # Security stub
│
├── admin/                    # Admin UI templates & assets
│   ├── settings-page.php     # Main dashboard PHP template (loaded by class-settings)
│   ├── bulk-converter.php    # Bulk converter PHP template
│   ├── system-info.php       # System info PHP template
│   ├── header.php            # Shared admin page header
│   ├── index.php             # Security stub
│   ├── css/
│   │   ├── tailwind-compiled.css  # Locally bundled Tailwind (WP.org compliant)
│   │   ├── admin-style.css        # Dashboard custom styles
│   │   ├── bulk-style.css         # Bulk converter custom styles
│   │   └── index.php
│   ├── js/
│   │   ├── admin-script.js        # Dashboard JS (chart init, AJAX settings save)
│   │   ├── bulk-converter.js      # Bulk converter JS (batch polling, progress UI)
│   │   ├── vendor/chart.min.js    # Locally bundled Chart.js 4.4.1
│   │   └── index.php
│   └── fonts/                     # Locally bundled web fonts
│
└── languages/                # i18n .pot files
```

---

## 4. WordPress DB Keys (Options & Post Meta)

### Options (`wp_options`)

| Key | Description |
|---|---|
| `Img_Panda_settings` | **Main settings object** (see §5 for fields) |
| `img_panda_stats` | Aggregated global stats (total conversions, space saved) |
| `img_panda_conversion_queue` | Array of attachment IDs waiting to be bulk-converted |
| `img_panda_conversion_status` | Bulk job state: `inactive`, `active`, `paused`, `stopped`, `completed` |
| `img_panda_conversion_progress` | Object: `{total, processed, successful, failed, skipped, start_time}` |
| `img_panda_conversion_logs` | Rolling array (max 500) of per-image log entries |
| `img_panda_conversion_errors` | Rolling array (max 100) of error records |
| `img_panda_ai_last_error` | Stores the last AI API error message for display |
| `img_panda_activated` | Unix timestamp of last activation |

### Transients

| Key | Description | TTL |
|---|---|---|
| `img_panda_stats_cache` | Cached stats object for dashboard | 5 min |
| `img_panda_conversion_success` | Toast notification payload | 30 sec |
| `img_panda_conversion_error` | Error notice message | 30 sec |
| `img_panda_alt_{md5_hash}` | Cached AI alt text result per image file | 48 hr |
| `img_panda_size_{md5_hash}` | Original file size, stored during upload hook | 60 sec |

### Post Meta (per attachment)

| Key | Description |
|---|---|
| `_img_panda_converted` | `'1'` if the image has been converted |
| `_img_panda_path` | Absolute server path to the WebP file |
| `_img_panda_original_size` | Original file size in bytes |
| `_img_panda_new_size` | WebP file size in bytes |
| `_img_panda_conversion_date` | Unix timestamp of conversion |
| `_img_panda_backup_path` | Absolute path to backup file (if backup enabled) |
| `_img_panda_original_id` | On WebP attachment — the ID of the source original |
| `_img_panda_version_id` | On original attachment — the ID of the linked WebP attachment |
| `_wp_attachment_image_alt` | Standard WP Alt Text field (written by AI handler) |

---

## 5. Settings Schema (`Img_Panda_settings` option)

| Key | Type | Default | Description |
|---|---|---|---|
| `auto_convert` | `'0'`/`'1'` | `'1'` | Auto-convert images on upload |
| `quality` | `int` (10–100) | `60` | WebP quality percentage |
| `replace_original` | `'keep_both'`/`'replace'` | `'keep_both'` | File handling mode after conversion |
| `show_webp_in_library` | `'0'`/`'1'` | `'1'` | Show WebP as separate Media Library entry |
| `enable_frontend_serving` | `'0'`/`'1'` | `'1'` | Rewrite image URLs to WebP on frontend |
| `min_size` | `int` (KB) | `0` | Skip images smaller than N KB (0 = disabled) |
| `max_size` | `int` (MB) | `10` | Skip images larger than N MB (0 = disabled) |
| `ai_provider` | `'gemini'`/`'openai'` | `'gemini'` | AI provider for Alt Text |
| `ai_model` | `string` | `'gemini-1.5-flash'` | AI model name |
| `ai_api_key` | `string` | `''` | User's API key (stored in DB, not exposed in JS) |
| `auto_alt` | `'0'`/`'1'` | `'0'` | Auto-generate Alt Text on upload |

> **Important:** Settings are saved via AJAX (`img_panda_save_settings`). The AJAX handler merges submitted data with existing settings *before* sanitizing to prevent checkbox unchecking from wiping unrelated fields.

---

## 6. Core Classes & Responsibilities

### `Img_Panda_Converter` (`class-converter.php`)
- **The conversion engine.** Called by both the upload hook and bulk processor.
- `check_webp_support()` — static, detects GD or Imagick support
- `convert_image_to_webp($path, $quality)` — orchestrates conversion; performs **smart size check** (deletes WebP if it's >= original size)
- `convert_with_gd()` — GD Library path, preserves PNG transparency
- `convert_with_imagick()` — Imagick path
- `handle_upload_conversion()` — hooked to `wp_handle_upload` filter
- `add_webp_to_metadata()` — hooked to `wp_generate_attachment_metadata`, stores WebP info in attachment metadata
- `create_webp_attachment()` — creates a separate WP attachment post for the WebP file (in `keep_both` mode)
- Supported input formats: `jpg`, `jpeg`, `png`, `jfif`, `pjpeg`, `pjp`

### `Img_Panda_Settings` (`class-settings.php`)
- Registers admin menu with 3 pages: **Dashboard** (`img-panda`), **Bulk Converter** (`img-panda-bulk`), **System Info** (`img-panda-system-info`)
- Registers the `Img_Panda_settings_group` settings group
- `sanitize_settings()` — the canonical sanitizer, handles all field types
- `enqueue_admin_assets()` — loads Tailwind CSS, admin-style.css, admin-script.js, Chart.js, and bulk-converter.js conditionally per page hook
- Localizes `imgPandaAdminData` (ajaxUrl, nonce, stats) to admin-script.js
- Localizes `imgPandaBulkData` (ajaxUrl, nonce, strings) to bulk-converter.js

### `Img_Panda_Bulk_Processor` (`class-bulk-processor.php`)
- `get_unconverted_images($filters)` — WP_Query to find unconverted attachments. Supports filters: `date_from`, `date_to`, `format` (jpeg/png/all), `size` (full/large/medium/all)
- `initialize_bulk_conversion($ids)` — saves queue and resets progress to DB
- `process_next_batch()` — pops `$batch_size` (default: 50) IDs from queue, processes them, updates progress
- `process_batch($ids)` — private; the actual per-image loop. Also triggers AI alt text if `auto_alt` is on.
- Queue state persisted in `img_panda_conversion_queue` (wp_options)
- WP-Cron hook: `img_panda_bulk_cron` for background processing
- Backup system: copies originals to `uploads/img-panda-backups/YYYY/MM/`

### `Img_Panda_AI_Handler` (`class-ai-handler.php`)
- `generate_alt_text($attachment_id)` — main entry point
  - Skips if alt text already exists (non-destructive)
  - Uses **MD5 file fingerprint** as cache key to avoid duplicate API calls
  - Resizes image to **512×512 px** before sending to AI (cost/performance sweet spot)
  - Caches result as transient for 48 hours
- `call_gemini()` — calls `v1beta/models/{model}:generateContent` with inline base64 image data. Uses safety settings `BLOCK_NONE` for all categories.
- `call_openai()` — calls `/v1/chat/completions` with `image_url` content type
- `set_test_credentials()` — overrides settings temporarily for the "Test Connection" AJAX flow
- SSL verify is **disabled on local/dev** (`WP_ENVIRONMENT_TYPE=local`, `.test`, `localhost`)
- Default model: `gemini-1.5-flash` (free tier, no card required)

### `Img_Panda_Ajax_Handler` (`class-ajax-handler.php`)
All handlers require nonce `img_panda_nonce` and `manage_options` capability (except `img_panda_convert_single` which requires `upload_files`).

| Action | Method | Description |
|---|---|---|
| `img_panda_start_bulk` | `start_bulk_conversion()` | Initialise queue |
| `img_panda_process_batch` | `process_batch()` | Process next batch |
| `img_panda_pause` | `pause_conversion()` | Pause queue |
| `img_panda_resume` | `resume_conversion()` | Resume queue |
| `img_panda_stop` | `stop_conversion()` | Clear queue and stop |
| `img_panda_get_stats` | `get_stats()` | Return full stats object |
| `img_panda_get_progress` | `get_progress()` | Return queue progress |
| `img_panda_test` | `test_conversion()` | Stub (TODO) |
| `img_panda_check_server` | `check_server()` | Return server capabilities |
| `img_panda_restore` | `restore_originals()` | Restore from backups |
| `img_panda_clear_logs` | `clear_logs()` | Wipe conversion logs |
| `img_panda_export_logs` | `export_logs()` | Download CSV of logs |
| `img_panda_convert_single` | `convert_single()` | Convert one image by ID |
| `img_panda_save_settings` | `save_settings()` | AJAX settings save |
| `img_panda_test_ai_connection` | `test_ai_connection()` | Test AI API key |

### `Img_Panda_Stats` (`class-stats.php`)
- All methods are `static`
- `get_cached_stats()` — fast path using transient `img_panda_stats_cache` (5 min TTL)
- `get_total_images()` — counts attachments (excludes SVG, AVIF, and plugin-created WebP attachments via `_img_panda_original_id`)
- `get_converted_count()` — counts attachments with `_img_panda_converted = 1` (also excludes plugin-created WebP)
- `get_total_space_saved()` — SUM(original_size) - SUM(new_size) from postmeta
- `get_average_compression()` — returns percentage (0–100)
- `export_logs_csv()` — returns raw CSV string

### `Img_Panda_Frontend` (`class-frontend.php`)
- Hooks `template_redirect` → starts `ob_start()` with `replace_images()` callback
- `replace_images()` — regex-walks all `<img>` tags in HTML output
- `replace_image_callback()` — for each local JPEG/PNG `src`, checks if a `.webp` sibling exists on disk and swaps the URL
- `replace_srcset()` — also rewrites `srcset` attributes
- Skips external images, non-image MIME types, and XML/JSON responses

### `Img_Panda_Activator` (`class-activator.php`)
- Checks PHP >= 7.4 and WP >= 5.8 before activating
- Sets default options (if not already set)
- Runs `migrate_options()` — migrates from old prefix `wp_webp_optimizer_*` and post meta `_webp_*` → `img_panda_*` and `_img_panda_*`
- `deactivate()` — only removes `img_panda_activated`. Settings and data are preserved until uninstall.

---

## 7. Admin UI Pages

### Dashboard (`settings-page.php`)
- Loaded by `Img_Panda_Settings::render_settings_page()`
- Built with Tailwind CSS (compiled, locally bundled)
- Shows: Optimization Health stats, Space Saved, a Chart.js doughnut chart, Recent Conversions table, and the Settings form
- **AI SEO Engine** config card is displayed prominently (non-tabbed) on the dashboard for visibility
- Settings saved via AJAX (`img_panda_save_settings`) — no page reload

### Bulk Converter (`bulk-converter.php`)
- Loaded via `bulk-converter.js` polling loop
- Shows: Filter panel (format, date, size), Progress bar, Live log feed, Start/Pause/Stop controls
- Batch polling: JS calls `img_panda_process_batch` repeatedly until `status === 'completed'`

### System Info (`system-info.php`)
- Read-only panel showing PHP version, WP version, GD/Imagick state, memory limit, upload limit, WebP support status

---

## 8. Frontend Serving Logic

When enabled, every page load on the frontend:
1. Output buffer captures the full HTML
2. Regex finds all `<img src="...jpg/png">` tags pointing to the site's upload dir
3. Checks if `{filename}.webp` exists on disk (sibling file)
4. If yes, swaps the `src` (and all `srcset` entries) to the `.webp` URL
5. Also handles `srcset` rewriting for responsive images

---

## 9. Key Design Decisions & Gotchas

### WP.org Compliance
- **No external CDN calls** at runtime. All CSS, JS, and fonts are bundled locally.
- `google.com/css2?family=Material+Symbols+Outlined` is loaded in the **admin only**, not on the frontend (this should be reviewed for full compliance and potentially bundled too).
- Third-party services (Gemini, OpenAI) are **opt-in only** — no data sent unless user provides an API key and enables the feature.

### Settings Merge Pattern (CRITICAL)
The `save_settings` AJAX handler **merges** form data with existing DB settings before calling `sanitize_settings()`. This is essential because:
- HTML forms do NOT submit unchecked checkboxes
- Without the merge, saving a partial form (e.g., only the AI section) would zero-out all checkbox fields

### Smart WebP Rejection
After conversion, if `filesize(webp) >= filesize(original)`, the WebP is deleted immediately and the conversion is marked as failed. This prevents enlarging images and wasting disk space.

### Alt Text Non-Destructive
`generate_alt_text()` checks for an existing `_wp_attachment_image_alt` value and returns early without calling the API if one is already set. This is important for respecting manual edits.

### Bulk Processor — AI is Independent
During bulk runs, AI alt text generation is performed **before** WebP conversion and independently — a failed AI call does not block the image from being converted to WebP.

### `_img_panda_original_id` meta key
This meta key is set on plugin-created WebP *attachment posts* to link them back to the source image. It is used throughout stats queries to exclude these "shadow" attachments from counts, preventing double-counting.

---

## 10. Planned / Future Features

- [ ] **Post-type filter in Bulk Converter** — filter by post type (e.g., only WooCommerce product images)
- [ ] **Missing Alt Text filter in Bulk Converter** — target only images currently missing alt text
- [ ] **AVIF support** — Add AVIF as a second output format option
- [ ] **Bundle Material Symbols font locally** — for full WP.org self-hosting compliance
- [ ] **Stub for `img_panda_test` AJAX action** — currently returns a dummy success response
- [ ] **Backup toggle in settings UI** — `enable_backup` key exists in bulk processor but has no UI setting registered yet

---

## 11. WP.org Submission Checklist

- [x] Prefixes all options, hooks, functions with `img_panda_` or `Img_Panda_`
- [x] All user-facing strings wrapped in `__()` / `esc_html__()` with `'img-panda'` text domain
- [x] Nonce verification on all AJAX handlers
- [x] Capability checks on all AJAX handlers (`manage_options` or `upload_files`)
- [x] Input sanitized via `sanitize_text_field()`, `intval()`, `absint()`
- [x] Output escaped via `esc_html()`, `esc_attr()`, `esc_url()`
- [x] Direct DB queries justified with inline comments and `phpcs:ignore` notes
- [x] No `die()` — uses `wp_die()` and `wp_send_json_error()`
- [x] No `file_put_contents()` for logging — uses WP error log via `error_log()` (gated behind `WP_DEBUG`)
- [x] `readme.txt` complete with changelog, screenshots description, privacy & external services section
- [ ] Screenshots need to be created (`screenshot-1.png` through `screenshot-4.png`)
- [ ] Final `readme.txt` must match the live plugin version on commit
- [ ] SVN tag must be created at submission time
