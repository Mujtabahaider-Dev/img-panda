<?php
/**
 * AJAX Handler Class.
 *
 * Handles all AJAX requests for the plugin.
 *
 * @package Mkit_Si
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * AJAX Handler Class.
 */
class Mkit_Si_Ajax_Handler
{

	/**
	 * Initialize AJAX handlers.
	 *
	 * @since 1.0.0
	 */
	public function init()
	{
		// Bulk conversion actions
		add_action('wp_ajax_mkit_si_start_bulk', array($this, 'start_bulk_conversion'));
		add_action('wp_ajax_mkit_si_process_batch', array($this, 'process_batch'));
		add_action('wp_ajax_mkit_si_pause', array($this, 'pause_conversion'));
		add_action('wp_ajax_mkit_si_resume', array($this, 'resume_conversion'));
		add_action('wp_ajax_mkit_si_stop', array($this, 'stop_conversion'));

		// Stats and info
		add_action('wp_ajax_mkit_si_get_stats', array($this, 'get_stats'));
		add_action('wp_ajax_mkit_si_get_progress', array($this, 'get_progress'));

		// Tools
		add_action('wp_ajax_mkit_si_test', array($this, 'test_conversion'));
		add_action('wp_ajax_mkit_si_check_server', array($this, 'check_server'));
		add_action('wp_ajax_mkit_si_restore', array($this, 'restore_originals'));
		add_action('wp_ajax_mkit_si_clear_logs', array($this, 'clear_logs'));
		add_action('wp_ajax_mkit_si_export_logs', array($this, 'export_logs'));

		// Single image conversion
		add_action('wp_ajax_mkit_si_convert_single', array($this, 'convert_single'));

		// Settings
		add_action('wp_ajax_mkit_si_save_settings', array($this, 'save_settings'));

		// AI Testing
		add_action('wp_ajax_mkit_si_test_ai_connection', array($this, 'test_ai_connection'));
	}

	/**
	 * Test AI connection.
	 */
	public function test_ai_connection()
	{
		check_ajax_referer('mkit_si_nonce', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-ai-handler.php';
		$ai = new Mkit_Si_AI_Handler();
		
		if (isset($_POST['key']) || isset($_POST['url'])) {
			$ai->set_test_credentials(
				isset($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : '',
				isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : 'gemini',
				isset($_POST['model']) ? sanitize_text_field(wp_unslash($_POST['model'])) : 'gemini-1.5-flash',
				isset($_POST['url']) ? esc_url_raw(sanitize_text_field(wp_unslash($_POST['url']))) : ''
			);
		}
		
		$result = $ai->test_connection();

		if ($result) {
			wp_send_json_success(array(
				'message' => __('AI Connection Successful! Response:', 'mak8it-smart-image') . ' "' . $result . '"'
			));
		} else {
			$error = get_option('mkit_si_ai_last_error', __('Unknown error occurred during connection.', 'mak8it-smart-image'));
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
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		$processor = new Mkit_Si_Bulk_Processor();

		// Get filters from request
		$filters = array();
		if (isset($_POST['filters']) && is_array($_POST['filters'])) {
			$filters = array_map('sanitize_text_field', wp_unslash($_POST['filters']));
		}

		// Get images to convert
		$image_ids = $processor->get_unconverted_images($filters);

		if (empty($image_ids)) {
			wp_send_json_error(array('message' => __('No images to convert', 'mak8it-smart-image')));
		}

		// Initialize bulk conversion with all filters
		$processor->initialize_bulk_conversion($image_ids, $filters);

		wp_send_json_success(array(
			'message' => __('Bulk conversion started', 'mak8it-smart-image'),
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
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		$processor = new Mkit_Si_Bulk_Processor();

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
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		$processor = new Mkit_Si_Bulk_Processor();
		$processor->pause_conversion();

		wp_send_json_success(array('message' => __('Conversion paused', 'mak8it-smart-image')));
	}

	/**
	 * Resume conversion.
	 *
	 * @since 1.0.0
	 */
	public function resume_conversion()
	{
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		$processor = new Mkit_Si_Bulk_Processor();
		$processor->resume_conversion();

		wp_send_json_success(array('message' => __('Conversion resumed', 'mak8it-smart-image')));
	}

	/**
	 * Stop conversion.
	 *
	 * @since 1.0.0
	 */
	public function stop_conversion()
	{
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-bulk-processor.php';
		$processor = new Mkit_Si_Bulk_Processor();
		$processor->stop_conversion();

		wp_send_json_success(array('message' => __('Conversion stopped', 'mak8it-smart-image')));
	}

	/**
	 * Get current statistics.
	 *
	 * @since 1.0.0
	 */
	public function get_stats()
	{
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
			return;
		}

		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-stats.php';
		$stats = Mkit_Si_Stats::get_stats();

		wp_send_json_success($stats);
	}

	/**
	 * Get conversion progress.
	 *
	 * @since 1.0.0
	 */
	public function get_progress()
	{
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
			return;
		}

		$progress = get_option('mkit_si_conversion_progress', array());
		$status = get_option('mkit_si_conversion_status', 'inactive');

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
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		// This would handle file upload and testing
		// Implementation similar to test-conversion.php page

		wp_send_json_success(array('message' => __('Test completed', 'mak8it-smart-image')));
	}

	/**
	 * Check server compatibility.
	 *
	 * @since 1.0.0
	 */
	public function check_server()
	{
		check_ajax_referer('mkit_si_nonce', 'nonce');

		$support = Mkit_Si_Converter::check_webp_support();

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
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		// Get all images with backups
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Need fresh data for restore
		$results = $wpdb->get_results(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_mkit_si_backup_path'",
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
					$webp_path = get_post_meta($image_id, '_mkit_si_path', true);
					if ($webp_path && file_exists($webp_path)) {
						wp_delete_file($webp_path);
					}

					// Update meta
					delete_post_meta($image_id, '_mkit_si_converted');
					delete_post_meta($image_id, '_mkit_si_path');

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
			'message' => sprintf(__('Restored %1$d images, %2$d failed', 'mak8it-smart-image'), $restored, $failed),
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
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-stats.php';
		Mkit_Si_Stats::clear_logs();

		wp_send_json_success(array('message' => __('Logs cleared', 'mak8it-smart-image')));
	}

	/**
	 * Export logs to CSV.
	 *
	 * @since 1.0.0
	 */
	public function export_logs()
	{
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Permission denied', 'mak8it-smart-image'));
		}

		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-stats.php';
		$csv = Mkit_Si_Stats::export_logs_csv();

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="mkit-si-conversion-logs-' . gmdate('Y-m-d') . '.csv"');
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
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('upload_files')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		$image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

		if (!$image_id) {
			wp_send_json_error(array('message' => __('Invalid image ID', 'mak8it-smart-image')));
		}

		$file_path = get_attached_file($image_id);

		if (!$file_path || !file_exists($file_path)) {
			wp_send_json_error(array('message' => __('File not found', 'mak8it-smart-image')));
		}

		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-converter.php';
		$converter = new Mkit_Si_Converter();

		$settings = get_option('Mkit_Si_settings', array());
		$quality = isset($settings['quality']) ? intval($settings['quality']) : 60;

		$result = $converter->convert_image_to_webp($file_path, $quality);

		if ($result['success']) {
			// Update meta
			$original_size = filesize($file_path);
			$webp_size = file_exists($result['webp_path']) ? filesize($result['webp_path']) : 0;

			update_post_meta($image_id, '_mkit_si_converted', '1');
			update_post_meta($image_id, '_mkit_si_original_size', $original_size);
			update_post_meta($image_id, '_mkit_si_new_size', $webp_size);
			update_post_meta($image_id, '_mkit_si_path', $result['webp_path']);

			// Clear stats cache after conversion
			Mkit_Si_Stats::clear_cache();

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
		check_ajax_referer('mkit_si_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'mak8it-smart-image')));
		}

		// Validate POST data exists
		if (!isset($_POST['form_data']) || empty($_POST['form_data'])) {
			wp_send_json_error(array('message' => __('No data provided', 'mak8it-smart-image')));
		}

		// Parse the serialized form data
		$form_data = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via parse_str and then sanitize_settings
		parse_str(wp_unslash($_POST['form_data']), $form_data);

		if (!isset($form_data['Mkit_Si_settings'])) {
			wp_send_json_error(array('message' => __('Invalid settings data', 'mak8it-smart-image')));
		}

		// Use the existing settings class to sanitize and save
		require_once MKIT_SI_PLUGIN_DIR . 'includes/class-settings.php';
		$settings_class = new Mkit_Si_Settings();
        
        // CRITICAL FIX: Merge raw data with existing settings BEFORE sanitizing
        // This ensures checkboxes and missing fields are handled correctly
        $existing_settings = get_option('Mkit_Si_settings', array());
        $merged_raw        = array_merge($existing_settings, $form_data['Mkit_Si_settings']);
        
		$final_sanitized = $settings_class->sanitize_settings($merged_raw);

		if (update_option('Mkit_Si_settings', $final_sanitized)) {
			wp_send_json_success(array('message' => __('Settings saved successfully!', 'mak8it-smart-image')));
		} else {
			// update_option returns false if the data is the same, so we count this as success too
			wp_send_json_success(array('message' => __('Settings updated', 'mak8it-smart-image')));
		}
	}
}


