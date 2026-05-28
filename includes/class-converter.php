<?php
/**
 * WebP Image Converter Class.
 *
 * Handles the conversion of images to WebP format.
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main Converter Class.
 */
class Img_Panda_Converter
{

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Initialize the converter.
	 *
	 * @since 1.0.0
	 */
	public function init()
	{
		// Load settings
		$this->settings = get_option('Img_Panda_settings', array());

		// Hook into upload process if auto-convert is enabled
		if (isset($this->settings['auto_convert']) && '1' === $this->settings['auto_convert']) {
			add_filter('wp_handle_upload', array($this, 'handle_upload_conversion'), 10, 2);
			add_filter('wp_generate_attachment_metadata', array($this, 'add_webp_to_metadata'), 10, 2);
			add_action('add_attachment', array($this, 'create_webp_attachment'), 10, 1);
		}

		// Add admin notices for conversion results
		add_action('admin_notices', array($this, 'show_conversion_notices'));
	}

	/**
	 * Check if server supports WebP conversion.
	 *
	 * @since 1.0.0
	 * @return array Array with 'supported' boolean and 'method' string.
	 */
	public static function check_webp_support()
	{
		$support = array(
			'supported' => false,
			'method' => '',
			'message' => '',
		);

		// Check for GD support
		if (function_exists('imagewebp') && function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng')) {
			$support['supported'] = true;
			$support['method'] = 'GD Library';
			$support['message'] = __('Your server supports WebP conversion using GD Library.', 'img-panda');
			return $support;
		}

		// Check for Imagick support
		if (extension_loaded('imagick') && class_exists('Imagick')) {
			$imagick = new Imagick();
			if (in_array('WEBP', $imagick->queryFormats(), true)) {
				$support['supported'] = true;
				$support['method'] = 'Imagick';
				$support['message'] = __('Your server supports WebP conversion using Imagick.', 'img-panda');
				return $support;
			}
		}

		$support['message'] = __('Your server does not support WebP conversion. Please install GD Library with WebP support or Imagick.', 'img-panda');
		return $support;
	}

	/**
	 * Convert an image to WebP format.
	 *
	 * @since 1.0.0
	 * @param string $file_path Path to the source image file.
	 * @param int    $quality   Conversion quality (10-100).
	 * @return array Result array with success status, message, and WebP file path.
	 */
	public function convert_image_to_webp($file_path, $quality = 60)
	{
		$result = array(
			'success' => false,
			'message' => '',
			'webp_path' => '',
			'original_path' => $file_path,
		);

		try {
			// Check if file exists
			if (!file_exists($file_path)) {
				$result['message'] = __('Source file does not exist.', 'img-panda');
				return $result;
			}

			// Check size limits
			$file_size_bytes = filesize($file_path);
			$settings = get_option('Img_Panda_settings', array());
			
			// Min size check (KB to Bytes)
			$min_size_kb = isset($settings['min_size']) ? intval($settings['min_size']) : 0;
			if ($min_size_kb > 0 && $file_size_bytes < ($min_size_kb * 1024)) {
				$result['message'] = sprintf(__('Skipped: Image is smaller than %d KB.', 'img-panda'), $min_size_kb);
				return $result;
			}

			// Max size check (MB to Bytes)
			$max_size_mb = isset($settings['max_size']) ? intval($settings['max_size']) : 0;
			if ($max_size_mb > 0 && $file_size_bytes > ($max_size_mb * 1024 * 1024)) {
				$result['message'] = sprintf(__('Skipped: Image is larger than %d MB.', 'img-panda'), $max_size_mb);
				return $result;
			}

			// Check WebP support
			$support = self::check_webp_support();
			if (!$support['supported']) {
				$result['message'] = $support['message'];
				return $result;
			}

			// Boost memory for processing
			if (function_exists('wp_raise_memory_limit')) {
				wp_raise_memory_limit('image');
			}

			// Get file info
			$file_info = pathinfo($file_path);
			$extension = strtolower($file_info['extension']);

			// Detect JPEG variations and PNG
			$allowed_exts = array('jpg', 'jpeg', 'png', 'jfif', 'pjpeg', 'pjp');
			if (!in_array($extension, $allowed_exts, true)) {
				$result['message'] = __('Unsupported image format for conversion.', 'img-panda');
				return $result;
			}

			// Generate WebP file path
			$webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

			// Validate quality
			$quality = max(10, min(100, intval($quality)));

			// Attempt conversion based on available method
			if ('GD Library' === $support['method']) {
				$result = $this->convert_with_gd($file_path, $webp_path, $quality, $extension);
			} elseif ('Imagick' === $support['method']) {
				$result = $this->convert_with_imagick($file_path, $webp_path, $quality);
			}

			// Set original path in result
			$result['original_path'] = $file_path;

			// Smart Size Comparison: If WebP is larger, abort
			if ($result['success'] && file_exists($result['webp_path'])) {
				$webp_size     = filesize($result['webp_path']);
				$original_size = filesize($file_path);

				if ($webp_size >= $original_size) {
					unlink($result['webp_path']); // Delete the "fat" WebP
					$result['success'] = false;
					$result['message'] = __('Original image is already more efficient than WebP. Skipping.', 'img-panda');
					return $result;
				}
			}
		} catch (Exception $e) {
			$result['message'] = sprintf(
				/* translators: %s: error message */
				__('Conversion error: %s', 'img-panda'),
				$e->getMessage()
			);
		}

		return $result;
	}

	/**
	 * Convert image using GD Library.
	 *
	 * @since 1.0.0
	 * @param string $source_path Source image path.
	 * @param string $dest_path   Destination WebP path.
	 * @param int    $quality     Conversion quality.
	 * @param string $extension   Source file extension.
	 * @return array Result array.
	 */
	private function convert_with_gd($source_path, $dest_path, $quality, $extension)
	{
		$result = array(
			'success' => false,
			'message' => '',
			'webp_path' => '',
		);

		try {
			// Create image resource from source
			if (in_array($extension, array('jpg', 'jpeg', 'jfif', 'pjpeg', 'pjp'), true)) {
				$image = imagecreatefromjpeg($source_path);
			} elseif ('png' === $extension) {
				$image = imagecreatefrompng($source_path);
				// Preserve transparency
				imagepalettetotruecolor($image);
				imagealphablending($image, true);
				imagesavealpha($image, true);
			}

			if (!$image) {
				$result['message'] = __('Failed to create image resource.', 'img-panda');
				return $result;
			}

			// Convert to WebP
			if (imagewebp($image, $dest_path, $quality)) {
				$result['success'] = true;
				$result['webp_path'] = $dest_path;
				$result['message'] = __('Image successfully converted to WebP.', 'img-panda');
			} else {
				$result['message'] = __('Failed to save WebP image.', 'img-panda');
			}

			// Free memory
			imagedestroy($image);

		} catch (Exception $e) {
			$result['message'] = sprintf(
				/* translators: %s: error message */
				__('GD conversion error: %s', 'img-panda'),
				$e->getMessage()
			);
		}

		return $result;
	}

	/**
	 * Convert image using Imagick.
	 *
	 * @since 1.0.0
	 * @param string $source_path Source image path.
	 * @param string $dest_path   Destination WebP path.
	 * @param int    $quality     Conversion quality.
	 * @return array Result array.
	 */
	private function convert_with_imagick($source_path, $dest_path, $quality)
	{
		$result = array(
			'success' => false,
			'message' => '',
			'webp_path' => '',
		);

		try {
			$image = new Imagick($source_path);

			// Set format to WebP
			$image->setImageFormat('WEBP');

			// Set quality
			$image->setImageCompressionQuality($quality);

			// Preserve original dimensions
			$image->stripImage();

			// Save WebP
			if ($image->writeImage($dest_path)) {
				$result['success'] = true;
				$result['webp_path'] = $dest_path;
				$result['message'] = __('Image successfully converted to WebP.', 'img-panda');
			} else {
				$result['message'] = __('Failed to save WebP image.', 'img-panda');
			}

			// Clean up
			$image->clear();
			$image->destroy();

		} catch (Exception $e) {
			$result['message'] = sprintf(
				/* translators: %s: error message */
				__('Imagick conversion error: %s', 'img-panda'),
				$e->getMessage()
			);
		}

		return $result;
	}

	/**
	 * Handle image conversion on upload.
	 *
	 * @since 1.0.0
	 * @param array  $upload     Upload data array.
	 * @param string $context    Upload context.
	 * @return array Modified upload data.
	 */
	public function handle_upload_conversion($upload, $context = 'upload')
	{
		// Log start
		$this->debug_log('=== UPLOAD CONVERSION STARTED ===');
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging only in WP_DEBUG mode
		$this->debug_log('Upload data: ' . print_r($upload, true));
		$this->debug_log('Context: ' . $context);

		// Only process if upload was successful
		if (isset($upload['error']) && $upload['error']) {
			$this->debug_log('Upload has error, skipping');
			return $upload;
		}

		// Get file path and type
		$file_path = isset($upload['file']) ? $upload['file'] : '';
		$file_type = isset($upload['type']) ? $upload['type'] : '';

		$this->debug_log('File path: ' . $file_path);
		$this->debug_log('File type: ' . $file_type);

		// Validate file path
		if (empty($file_path) || !file_exists($file_path)) {
			$this->debug_log('File path empty or does not exist');
			return $upload;
		}

		// Only process JPEG and PNG images
		$allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/pjpeg', 'image/jfif');
		if (!in_array($file_type, $allowed_types, true)) {
			$this->debug_log('File type not allowed for conversion: ' . $file_type);
			return $upload;
		}

		$this->debug_log('Starting conversion...');

		// Store original size in a transient to pass to metadata step
		set_transient('img_panda_size_' . md5(basename($file_path)), filesize($file_path), 60);

		// Get quality setting
		$quality = isset($this->settings['quality']) ? intval($this->settings['quality']) : 60;
		$this->debug_log('Quality setting: ' . $quality);

		// Convert to WebP
		$conversion_result = $this->convert_image_to_webp($file_path, $quality);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging only in WP_DEBUG mode
		$this->debug_log('Conversion result: ' . print_r($conversion_result, true));

		// Store conversion result for admin notice
		if ($conversion_result['success']) {
			set_transient('img_panda_conversion_success', $conversion_result, 30);
			$this->debug_log('Conversion successful!');

			// Store original size for metadata step
			$upload['webp_original_size'] = filesize($file_path);
			$upload['webp_new_size'] = filesize($conversion_result['webp_path']);
		} else {
			set_transient('img_panda_conversion_error', $conversion_result['message'], 30);
			$this->debug_log('Conversion failed: ' . $conversion_result['message']);
		}

		// Handle based on replace setting
		if ($conversion_result['success']) {
			$replace_mode = isset($this->settings['replace_original']) ? $this->settings['replace_original'] : 'keep_both';
			
			// Always store original size meta before potentially deleting the file
			update_post_meta(0, '_img_panda_temp_original_size', filesize($file_path)); // Temporary link
			
			if ('replace' === $replace_mode) {
				// Delete original and use WebP
				if (file_exists($file_path)) {
					wp_delete_file($file_path);
				}

				$upload['file'] = $conversion_result['webp_path'];
				$upload['type'] = 'image/webp';
				$upload['url'] = str_replace(basename($file_path), basename($conversion_result['webp_path']), $upload['url']);
				$this->debug_log('Original replaced with WebP');
			} else {
				// Store WebP path for metadata addition
				$upload['webp_file'] = $conversion_result['webp_path'];
				$this->debug_log('Keeping both files');
			}
		}

		$this->debug_log('=== UPLOAD CONVERSION ENDED ===');
		return $upload;
	}

	/**
	 * Debug logging function.
	 *
	 * Uses WordPress debug logging system instead of direct file writes.
	 * To enable logging, add these to wp-config.php:
	 * define('WP_DEBUG', true);
	 * define('WP_DEBUG_LOG', true);
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 */
	private function debug_log($message)
	{
		// Only log if WP_DEBUG and WP_DEBUG_LOG are enabled
		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log('[Img Panda] ' . $message);
		}
	}

	/**
	 * Add WebP file information to attachment metadata.
	 *
	 * @since 1.0.0
	 * @param array $metadata      Attachment metadata.
	 * @param int   $attachment_id Attachment post ID.
	 * @return array Modified metadata.
	 */
	public function add_webp_to_metadata($metadata, $attachment_id)
	{
		// Get the uploaded file info
		$file_path = get_attached_file($attachment_id);

		if (!$file_path) {
			return $metadata;
		}

		// Generate WebP path
		$file_info = pathinfo($file_path);
		$webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

		// Check if WebP file exists
		if (file_exists($webp_path)) {
			// Add WebP info to metadata
			if (!isset($metadata['webp_versions'])) {
				$metadata['webp_versions'] = array();
			}

			$metadata['webp_versions']['full'] = basename($webp_path);
			$metadata['has_webp'] = true;

			// Update post meta for statistics
			update_post_meta($attachment_id, '_img_panda_converted', '1');
			update_post_meta($attachment_id, '_img_panda_path', $webp_path);
			update_post_meta($attachment_id, '_img_panda_conversion_date', time());

			// Get sizes for stats
			$webp_size = filesize($webp_path);
			update_post_meta($attachment_id, '_img_panda_new_size', $webp_size);

			// Try to get original size
			$original_size = 0;
			$file_name = basename($file_path);
			$cached_size = get_transient('img_panda_size_' . md5($file_name));
			
			if ($cached_size) {
				$original_size = intval($cached_size);
				delete_transient('img_panda_size_' . md5($file_name));
			} elseif (file_exists($file_path) && $file_path !== $webp_path) {
				$original_size = filesize($file_path);
			}

			if ($original_size > 0) {
				update_post_meta($attachment_id, '_img_panda_original_size', $original_size);
			}

			// Also update image sizes with WebP versions if they exist
			if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
				foreach ($metadata['sizes'] as $size_name => $size_data) {
					$size_file = $file_info['dirname'] . '/' . $size_data['file'];
					$size_info = pathinfo($size_file);
					$size_webp = $size_info['dirname'] . '/' . $size_info['filename'] . '.webp';

					if (file_exists($size_webp)) {
						$metadata['webp_versions'][$size_name] = basename($size_webp);
					}
				}
			}
		}

		return $metadata;
	}

	/**
	 * Show admin notices for conversion results.
	 *
	 * @since 1.0.0
	 */
	public function show_conversion_notices()
	{
		// Check for success notice
		$success = get_transient('img_panda_conversion_success');
		if ($success) {
			$webp_file = isset($success['webp_path']) ? basename($success['webp_path']) : '';
			?>
			<div class="notice is-dismissible img-panda-toast" style="position: fixed; bottom: 30px; right: 30px; z-index: 999999; background: linear-gradient(135deg, #7c3bed 0%, #a855f7 100%); border: none; border-radius: 12px; padding: 20px 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04), 0 0 0 1px rgba(255,255,255,0.1) inset; margin: 0; max-width: 400px; color: white; display: flex; align-items: center; gap: 16px; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; animation: imgPandaSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;">
				<div style="position: absolute; right: -20px; top: -20px; opacity: 0.1; pointer-events: none;">
					<span class="dashicons dashicons-images-alt2" style="font-size: 140px; width: 140px; height: 140px;"></span>
				</div>
                <div style="background: rgba(255, 255, 255, 0.2); border-radius: 50%; padding: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-yes-alt" style="color: white; font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
				<div style="position: relative; z-index: 1;">
					<strong style="font-size: 16px; display: block; margin-bottom: 4px; letter-spacing: 0.5px;"><?php esc_html_e('Optimization Successful!', 'img-panda'); ?></strong>
					<p style="margin: 0; font-size: 13px; opacity: 0.9; line-height: 1.5;">
					<?php
					printf(
						/* translators: %s: WebP filename */
						esc_html__('Img Panda successfully converted your image to WebP format: %s', 'img-panda'),
						'<code style="background: rgba(0,0,0,0.2); border-radius: 6px; padding: 2px 6px; color: #fff; font-size: 11px; border: 1px solid rgba(255,255,255,0.1); margin-top: 4px; display: inline-block;">' . esc_html($webp_file) . '</code>'
					);
					?>
					</p>
				</div>

			</div>

			<?php
			delete_transient('img_panda_conversion_success');
		}

		// Check for error notice
		$error = get_transient('img_panda_conversion_error');
		if ($error) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong><?php esc_html_e('WebP Conversion Failed!', 'img-panda'); ?></strong><br>
					<?php echo esc_html($error); ?>
				</p>
			</div>
			<?php
			delete_transient('img_panda_conversion_error');
		}
	}

	/**
	 * Create a separate attachment for WebP file in keep_both mode.
	 *
	 * @since 1.0.0
	 * @param int $attachment_id The attachment post ID.
	 */
	public function create_webp_attachment($attachment_id)
	{
		$this->debug_log('create_webp_attachment called for ID: ' . $attachment_id);

		// Ensure settings are loaded
		if (empty($this->settings)) {
			$this->settings = get_option('Img_Panda_settings', array());
		}

		// Check if this attachment is already WebP (might have been replaced)
		$mime_type = get_post_mime_type($attachment_id);
		if ('image/webp' === $mime_type) {
			$this->debug_log('Attachment is already WebP, skipping');
			return; // Already WebP, no need to create separate attachment
		}

		// Get replace mode setting
		$replace_mode = isset($this->settings['replace_original']) ? $this->settings['replace_original'] : 'keep_both';

		// Only create separate attachment if in keep_both mode
		if ('keep_both' !== $replace_mode) {
			$this->debug_log('Not in keep_both mode, skipping');
			return;
		}

		// Check if user wants to show WebP in library
		$show_in_library = isset($this->settings['show_webp_in_library']) ? $this->settings['show_webp_in_library'] : '1';

		if ('1' !== $show_in_library) {
			$this->debug_log('Show in library disabled, skipping');
			return;
		}

		// Get the original file path
		$file_path = get_attached_file($attachment_id);

		if (!$file_path) {
			$this->debug_log('No file path found for attachment');
			return;
		}

		// Check if it's an image we converted
		$file_info = pathinfo($file_path);
		$extension = strtolower($file_info['extension']);

		if (!in_array($extension, array('jpg', 'jpeg', 'png'), true)) {
			$this->debug_log('Not a supported image extension: ' . $extension);
			return;
		}

		// Check if WebP exists
		$webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

		if (!file_exists($webp_path)) {
			$this->debug_log('WebP file does not exist at: ' . $webp_path);
			return;
		}

		// Normalize paths for comparison
		$upload_dir = wp_upload_dir();
		$basedir = wp_normalize_path($upload_dir['basedir']);
		$webp_path_normalized = wp_normalize_path($webp_path);
		$relative_path = str_replace($basedir . '/', '', $webp_path_normalized);

		$this->debug_log('Checking for existing attachment with file: ' . $relative_path);

		// Check if WebP attachment already exists
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Required for deduplication check
		$existing = get_posts(array(
			'post_type' => 'attachment',
			'meta_key' => '_wp_attached_file',
			'meta_value' => $relative_path,
			'posts_per_page' => 1,
		));

		if (!empty($existing)) {
			$this->debug_log('WebP attachment already exists (ID: ' . $existing[0]->ID . ')');
			return; // Already exists
		}

		// Get original attachment data
		$original_post = get_post($attachment_id);

		if (!$original_post) {
			return;
		}

		// Prepare WebP file URL
		$webp_url = str_replace($basedir, $upload_dir['baseurl'], $webp_path_normalized);

		// Create attachment post for WebP
		$webp_attachment = array(
			'guid' => $webp_url,
			'post_mime_type' => 'image/webp',
			'post_title' => $original_post->post_title . ' (WebP)',
			'post_content' => $original_post->post_content,
			'post_excerpt' => $original_post->post_excerpt,
			'post_status' => 'inherit',
		);

		// Insert the attachment
		$webp_id = wp_insert_attachment($webp_attachment, $webp_path);

		if (!is_wp_error($webp_id)) {
			$this->debug_log('Created WebP attachment with ID: ' . $webp_id);

			// Generate metadata for WebP
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$webp_metadata = wp_generate_attachment_metadata($webp_id, $webp_path);
			wp_update_attachment_metadata($webp_id, $webp_metadata);

			// Link WebP to original and protect original from bulk re-processing
			update_post_meta($webp_id, '_img_panda_original_id', $attachment_id);
			update_post_meta($attachment_id, '_img_panda_version_id', $webp_id);
			update_post_meta($attachment_id, '_img_panda_is_original_source', '1');
            update_post_meta($attachment_id, '_img_panda_converted', '1');

            // Copy Alt-Text from original if it exists
            $original_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if (!empty($original_alt)) {
                update_post_meta($webp_id, '_wp_attachment_image_alt', $original_alt);
                $this->debug_log('Copied Alt-Text to WebP attachment: ' . $original_alt);
            }
		} else {
			$this->debug_log('Failed to create attachment: ' . $webp_id->get_error_message());
		}
	}
}

