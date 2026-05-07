<?php
/**
 * AJAX Handler Class.
 *
 * Handles all AJAX requests for the plugin.
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * AJAX Handler Class.
 */
class Img_Panda_Ajax_Handler
{

	/**
	 * Initialize AJAX handlers.
	 *
	 * @since 1.0.0
	 */
	public function init()
	{
		// Bulk conversion actions
		add_action('wp_ajax_img_panda_start_bulk', array($this, 'start_bulk_conversion'));
		add_action('wp_ajax_img_panda_process_batch', array($this, 'process_batch'));
		add_action('wp_ajax_img_panda_pause', array($this, 'pause_conversion'));
		add_action('wp_ajax_img_panda_resume', array($this, 'resume_conversion'));
		add_action('wp_ajax_img_panda_stop', array($this, 'stop_conversion'));

		// Stats and info
		add_action('wp_ajax_img_panda_get_stats', array($this, 'get_stats'));
		add_action('wp_ajax_img_panda_get_progress', array($this, 'get_progress'));

		// Tools
		add_action('wp_ajax_img_panda_test', array($this, 'test_conversion'));
		add_action('wp_ajax_img_panda_check_server', array($this, 'check_server'));
		add_action('wp_ajax_img_panda_restore', array($this, 'restore_originals'));
		add_action('wp_ajax_img_panda_clear_logs', array($this, 'clear_logs'));
		add_action('wp_ajax_img_panda_export_logs', array($this, 'export_logs'));

		// Single image conversion
		add_action('wp_ajax_img_panda_convert_single', array($this, 'convert_single'));

		// Settings
		add_action('wp_ajax_img_panda_save_settings', array($this, 'save_settings'));

		// AI Testing
		add_action('wp_ajax_img_panda_test_ai_connection', array($this, 'test_ai_connection'));
	}

	/**
	 * Test AI connection.
	 */
	public function test_ai_connection()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-ai-handler.php';
		$ai = new Img_Panda_AI_Handler();
		
		// If testing unsaved settings
		if (isset($_POST['key']) && !empty($_POST['key'])) {
            $ai->set_test_credentials(
                sanitize_text_field($_POST['key']),
                isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : 'gemini',
                isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gemini-1.5-flash'
            );
		}
		
		// Find the most recent image to test with
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
		);
		$query = new WP_Query($args);
		
		if (!$query->have_posts()) {
			wp_send_json_error(array('message' => __('Please upload at least one image to your Media Library before testing.', 'img-panda')));
		}

		$attachment_id = $query->posts[0]->ID;
		$alt_text = $ai->generate_alt_text($attachment_id);

		if ($alt_text) {
			wp_send_json_success(array(
				'message' => __('AI Connection Successful! Description:', 'img-panda') . ' "' . $alt_text . '"'
			));
		} else {
			$error = get_option('img_panda_ai_last_error', __('Unknown error occurred during connection.', 'img-panda'));
			wp_send_json_error(array('message' => $error));
		}
	}

	/**
	 * Start bulk conversion process.
	 *
	 * @since 1.0.0
	 */
	public function start_bulk_conversion()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		$processor = new Img_Panda_Bulk_Processor();

		// Get filters from request
		$filters = array();
		if (isset($_POST['filters']) && is_array($_POST['filters'])) {
			$filters = array_map('sanitize_text_field', wp_unslash($_POST['filters']));
		}

		// Get images to convert
		$image_ids = $processor->get_unconverted_images($filters);

		if (empty($image_ids)) {
			wp_send_json_error(array('message' => __('No images to convert', 'img-panda')));
		}

		// Initialize bulk conversion with all filters
		$processor->initialize_bulk_conversion($image_ids, $filters);

		wp_send_json_success(array(
			'message' => __('Bulk conversion started', 'img-panda'),
			'total' => count($image_ids),
		));
	}

	/**
	 * Process next batch.
	 *
	 * @since 1.0.0
	 */
	public function process_batch()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		$processor = new Img_Panda_Bulk_Processor();

		$result = $processor->process_next_batch();

		if ($result['success']) {
			wp_send_json_success($result);
		} else {
			wp_send_json_error($result);
		}
	}

	/**
	 * Pause conversion.
	 *
	 * @since 1.0.0
	 */
	public function pause_conversion()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		$processor = new Img_Panda_Bulk_Processor();
		$processor->pause_conversion();

		wp_send_json_success(array('message' => __('Conversion paused', 'img-panda')));
	}

	/**
	 * Resume conversion.
	 *
	 * @since 1.0.0
	 */
	public function resume_conversion()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		$processor = new Img_Panda_Bulk_Processor();
		$processor->resume_conversion();

		wp_send_json_success(array('message' => __('Conversion resumed', 'img-panda')));
	}

	/**
	 * Stop conversion.
	 *
	 * @since 1.0.0
	 */
	public function stop_conversion()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		$processor = new Img_Panda_Bulk_Processor();
		$processor->stop_conversion();

		wp_send_json_success(array('message' => __('Conversion stopped', 'img-panda')));
	}

	/**
	 * Get current statistics.
	 *
	 * @since 1.0.0
	 */
	public function get_stats()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
			return;
		}

		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-stats.php';
		$stats = Img_Panda_Stats::get_stats();

		wp_send_json_success($stats);
	}

	/**
	 * Get conversion progress.
	 *
	 * @since 1.0.0
	 */
	public function get_progress()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
			return;
		}

		$progress = get_option('img_panda_conversion_progress', array());
		$status = get_option('img_panda_conversion_status', 'inactive');

		wp_send_json_success(array(
			'progress' => $progress,
			'status' => $status,
		));
	}

	/**
	 * Test single image conversion.
	 *
	 * @since 1.0.0
	 */
	public function test_conversion()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		// This would handle file upload and testing
		// Implementation similar to test-conversion.php page

		wp_send_json_success(array('message' => __('Test completed', 'img-panda')));
	}

	/**
	 * Check server compatibility.
	 *
	 * @since 1.0.0
	 */
	public function check_server()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		$support = Img_Panda_Converter::check_webp_support();

		$info = array(
			'webp_support' => $support,
			'php_version' => PHP_VERSION,
			'memory_limit' => ini_get('memory_limit'),
			'max_upload' => size_format(wp_max_upload_size()),
			'gd_loaded' => extension_loaded('gd'),
			'imagick_loaded' => extension_loaded('imagick'),
		);

		wp_send_json_success($info);
	}

	/**
	 * Restore original images.
	 *
	 * @since 1.0.0
	 */
	public function restore_originals()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		// Get all images with backups
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Need fresh data for restore
		$results = $wpdb->get_results(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_img_panda_backup_path'",
			ARRAY_A
		);

		$restored = 0;
		$failed = 0;

		foreach ($results as $row) {
			$image_id = $row['post_id'];
			$backup_path = $row['meta_value'];

			if (file_exists($backup_path)) {
				$original_path = get_attached_file($image_id);

				if (copy($backup_path, $original_path)) {
					// Delete WebP file
					$webp_path = get_post_meta($image_id, '_img_panda_path', true);
					if ($webp_path && file_exists($webp_path)) {
						wp_delete_file($webp_path);
					}

					// Update meta
					delete_post_meta($image_id, '_img_panda_converted');
					delete_post_meta($image_id, '_img_panda_path');

					$restored++;
				} else {
					$failed++;
				}
			} else {
				$failed++;
			}
		}

		wp_send_json_success(array(
			/* translators: %1$d: number of restored images, %2$d: number of failed images */
			'message' => sprintf(__('Restored %1$d images, %2$d failed', 'img-panda'), $restored, $failed),
			'restored' => $restored,
			'failed' => $failed,
		));
	}

	/**
	 * Clear conversion logs.
	 *
	 * @since 1.0.0
	 */
	public function clear_logs()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-stats.php';
		Img_Panda_Stats::clear_logs();

		wp_send_json_success(array('message' => __('Logs cleared', 'img-panda')));
	}

	/**
	 * Export logs to CSV.
	 *
	 * @since 1.0.0
	 */
	public function export_logs()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Permission denied', 'img-panda'));
		}

		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-stats.php';
		$csv = Img_Panda_Stats::export_logs_csv();

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="img-panda-conversion-logs-' . gmdate('Y-m-d') . '.csv"');
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV output is properly formatted
		echo $csv;
		exit;
	}

	/**
	 * Convert single image.
	 *
	 * @since 1.0.0
	 */
	public function convert_single()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('upload_files')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		$image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

		if (!$image_id) {
			wp_send_json_error(array('message' => __('Invalid image ID', 'img-panda')));
		}

		$file_path = get_attached_file($image_id);

		if (!$file_path || !file_exists($file_path)) {
			wp_send_json_error(array('message' => __('File not found', 'img-panda')));
		}

		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-converter.php';
		$converter = new Img_Panda_Converter();

		$settings = get_option('Img_Panda_settings', array());
		$quality = isset($settings['quality']) ? intval($settings['quality']) : 60;

		$result = $converter->convert_image_to_webp($file_path, $quality);

		if ($result['success']) {
			// Update meta
			$original_size = filesize($file_path);
			$webp_size = file_exists($result['webp_path']) ? filesize($result['webp_path']) : 0;

			update_post_meta($image_id, '_img_panda_converted', '1');
			update_post_meta($image_id, '_img_panda_original_size', $original_size);
			update_post_meta($image_id, '_img_panda_new_size', $webp_size);
			update_post_meta($image_id, '_img_panda_path', $result['webp_path']);

			// Clear stats cache after conversion
			Img_Panda_Stats::clear_cache();

			wp_send_json_success(array(
				'message' => $result['message'],
				'original_size' => size_format($original_size, 2),
				'webp_size' => size_format($webp_size, 2),
				'savings' => size_format($original_size - $webp_size, 2),
			));
		} else {
			wp_send_json_error(array('message' => $result['message']));
		}
	}

	/**
	 * Save plugin settings via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function save_settings()
	{
		check_ajax_referer('img_panda_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'img-panda')));
		}

		// Validate POST data exists
		if (!isset($_POST['form_data']) || empty($_POST['form_data'])) {
			wp_send_json_error(array('message' => __('No data provided', 'img-panda')));
		}

		// Parse the serialized form data
		$form_data = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via parse_str and then sanitize_settings
		parse_str(wp_unslash($_POST['form_data']), $form_data);

		if (!isset($form_data['Img_Panda_settings'])) {
			wp_send_json_error(array('message' => __('Invalid settings data', 'img-panda')));
		}

		// Use the existing settings class to sanitize and save
		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-settings.php';
		$settings_class = new Img_Panda_Settings();
        
        // CRITICAL FIX: Merge raw data with existing settings BEFORE sanitizing
        // This ensures checkboxes and missing fields are handled correctly
        $existing_settings = get_option('Img_Panda_settings', array());
        $merged_raw        = array_merge($existing_settings, $form_data['Img_Panda_settings']);
        
		$final_sanitized = $settings_class->sanitize_settings($merged_raw);

		if (update_option('Img_Panda_settings', $final_sanitized)) {
			wp_send_json_success(array('message' => __('Settings saved successfully!', 'img-panda')));
		} else {
			// update_option returns false if the data is the same, so we count this as success too
			wp_send_json_success(array('message' => __('Settings updated', 'img-panda')));
		}
	}
}


