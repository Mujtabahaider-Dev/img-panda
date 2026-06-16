<?php
/**
 * Bulk Conversion Processor Class.
 *
 * Handles batch processing of images for WebP conversion.
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Bulk Processor Class.
 */
class Img_Panda_Bulk_Processor
{

	/**
	 * Batch size for processing.
	 *
	 * @var int
	 */
	private $batch_size;

	/**
	 * Initialize bulk processor.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		// Allow filtering of batch size for different server capabilities
		$this->batch_size = apply_filters('img_panda_batch_size', 50);

		// Hook for WP Cron if needed
		add_action('img_panda_bulk_cron', array($this, 'process_next_batch_cron'));
	}

	/**
	/**
	 * Get query arguments for unconverted images.
	 *
	 * @since 1.1.0
	 * @param array $filters Filter parameters.
	 * @return array Query arguments.
	 */
	private function get_unconverted_query_args($filters = array())
	{
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/gif', 'image/pjpeg', 'image/jfif', 'image/webp' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				// Rule 1: If it is an "Original" from a backup pair, leave it alone (Sacred Backup)
				array(
					'key'     => '_img_panda_is_original_source',
					'compare' => 'NOT EXISTS',
				),
				array(
					'relation' => 'OR',
					// Case A: Needs WebP conversion (must not be a variation already)
					array(
						'relation' => 'AND',
						array(
							'key'     => '_img_panda_original_id',
							'compare' => 'NOT EXISTS',
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => '_img_panda_converted',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => '_img_panda_converted',
								'value'   => '0',
								'compare' => '=',
							),
						),
					),
					// Case B: Missing Alt Text (SEO Audit) - Target ANY image
					array(
						'relation' => 'OR',
						array(
							'key'     => '_wp_attachment_image_alt',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_wp_attachment_image_alt',
							'value'   => '',
							'compare' => '=',
						),
					),
				),
			),
		);

		// Apply date range filter
		if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
			$date_query = array();

			if (!empty($filters['date_from'])) {
				$date_query['after'] = sanitize_text_field($filters['date_from']);
			}

			if (!empty($filters['date_to'])) {
				$date_query['before'] = sanitize_text_field($filters['date_to']);
			}

			$args['date_query'] = array($date_query);
		}

		// Apply MIME type filter
		if (!empty($filters['format']) && 'all' !== $filters['format']) {
			if ('jpeg' === $filters['format']) {
				$args['post_mime_type'] = array('image/jpeg', 'image/jpg');
			} elseif ('png' === $filters['format']) {
				$args['post_mime_type'] = 'image/png';
			}
		}

		return $args;
	}

	/**
	 * Get all unconverted images from media library.
	 *
	 * @since 1.0.0
	 * @param array $filters Filter parameters.
	 * @return array Array of attachment IDs.
	 */
	public function get_unconverted_images($filters = array())
	{
		$args = $this->get_unconverted_query_args($filters);

		$query = new WP_Query($args);
		$image_ids = $query->posts;

		// Apply size filter if specified (must filter after query due to metadata)
		if (!empty($filters['size']) && 'all' !== $filters['size']) {
			$image_ids = $this->filter_by_size($image_ids, $filters['size']);
		}

		return $image_ids;
	}

	/**
	 * Get the count of all unconverted images from media library.
	 *
	 * High-performance database count that avoids loading all ID records into memory.
	 *
	 * @since 1.1.0
	 * @param array $filters Filter parameters.
	 * @return int Total unconverted count.
	 */
	public function get_unconverted_images_count($filters = array())
	{
		// If size filter is set, we must fallback to fetching all matching IDs and filtering in PHP
		if (!empty($filters['size']) && 'all' !== $filters['size']) {
			return count($this->get_unconverted_images($filters));
		}

		$args = $this->get_unconverted_query_args($filters);
		$args['posts_per_page'] = 1;
		$args['fields']         = 'ids';
		$args['no_found_rows']  = false;

		$query = new WP_Query($args);
		return $query->found_posts;
	}

	/**
	 * Filter images by minimum file size (KB).
	 *
	 * @since 1.0.0
	 * @param array $image_ids Array of image IDs.
	 * @param int   $min_size  Min size in KB.
	 * @return array Filtered IDs.
	 */
	private function filter_by_min_size($image_ids, $min_size)
	{
		if (empty($image_ids)) {
			return array();
		}

		$filtered = array();
		$min_bytes = $min_size * 1024;

		foreach ($image_ids as $image_id) {
			$file = get_attached_file($image_id);
			if ($file && file_exists($file)) {
				if (filesize($file) >= $min_bytes) {
					$filtered[] = $image_id;
				}
			}
		}

		return $filtered;
	}

	/**
	 * Filter images by WordPress size.
	 *
	 * @since 1.0.0
	 * @param array  $image_ids Array of image IDs.
	 * @param string $size      Size filter value.
	 * @return array Filtered image IDs.
	 */
	private function filter_by_size($image_ids, $size)
	{
		if (empty($image_ids)) {
			return array();
		}

		$filtered = array();

		foreach ($image_ids as $image_id) {
			$metadata = wp_get_attachment_metadata($image_id);

			if (!$metadata || !isset($metadata['width'], $metadata['height'])) {
				continue;
			}

			$width = $metadata['width'];
			$height = $metadata['height'];

			// Get WordPress image size limits
			$full_width = isset($metadata['width']) ? $metadata['width'] : 0;
			$large_width = isset($metadata['sizes']['large']['width']) ? $metadata['sizes']['large']['width'] : 0;
			$medium_width = isset($metadata['sizes']['medium']['width']) ? $metadata['sizes']['medium']['width'] : 0;

			$include = false;

			switch ($size) {
				case 'full':
					// Only full size images
					$include = true;
					break;
				case 'large':
					// Large and full
					$include = ($width >= 1024 || $large_width >= 1024);
					break;
				case 'medium':
					// Medium and above
					$include = ($width >= 300 || $medium_width >= 300);
					break;
			}

			if ($include) {
				$filtered[] = $image_id;
			}
		}

		return $filtered;
	}

	/**
	 * Get all converted images.
	 *
	 * @since 1.0.0
	 * @return array Array of attachment IDs.
	 */
	public function get_converted_images()
	{
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_img_panda_converted',
					'value'   => '1',
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query($args);
		return $query->posts;
	}

	/**
	 * Initialize bulk conversion process.
	 *
	 * @since 1.0.0
	 * @param array $image_ids Array of image IDs to convert.
	 * @return bool Success status.
	 */
	public function initialize_bulk_conversion($image_ids, $filters = array())
	{
		// Store queue in option
		update_option('img_panda_conversion_queue', $image_ids);
		update_option('img_panda_conversion_status', 'active');
		update_option('img_panda_conversion_progress', array(
			'total' => count($image_ids),
			'processed' => 0,
			'successful' => 0,
			'failed' => 0,
			'skipped' => 0,
			'start_time' => time(),
			'filters' => $filters,
		));

		return true;
	}

	/**
	 * Process next batch of images.
	 *
	 * @since 1.0.0
	 * @return array Processing result with status and data.
	 */
	public function process_next_batch()
	{
		// Check if conversion is paused
		$status = get_option('img_panda_conversion_status', 'inactive');
		if ('paused' === $status) {
			return array(
				'success' => false,
				'message' => __('Conversion is paused', 'img-panda'),
				'status' => 'paused',
			);
		}

		// Get queue
		$queue = get_option('img_panda_conversion_queue', array());

		if (empty($queue)) {
			// Mark as completed
			update_option('img_panda_conversion_status', 'completed');

			return array(
				'success' => true,
				'message' => __('All images processed', 'img-panda'),
				'status' => 'completed',
				'progress' => get_option('img_panda_conversion_progress', array()),
			);
		}

		// Get batch
		$batch = array_slice($queue, 0, $this->batch_size);
		$remaining = array_slice($queue, $this->batch_size);

		// Process batch
		$results = $this->process_batch($batch);

		// Update queue
		update_option('img_panda_conversion_queue', $remaining);

		// Update progress
		$progress = get_option('img_panda_conversion_progress', array());
		$progress['processed'] += count($batch);
		$progress['successful'] += $results['successful'];
		$progress['failed'] += $results['failed'];
		$progress['skipped'] += $results['skipped'];
		update_option('img_panda_conversion_progress', $progress);

		// Calculate estimated time
		$elapsed = time() - $progress['start_time'];
		$rate = $progress['processed'] > 0 ? $elapsed / $progress['processed'] : 0;
		$remaining_count = count($remaining);
		$estimated_time = $rate > 0 ? $remaining_count * $rate : 0;

		return array(
			'success' => true,
			'status' => 'processing',
			'progress' => $progress,
			'remaining' => $remaining_count,
			'batch_results' => $results,
			'estimated_time' => $estimated_time,
		);
	}

	/**
	 * Process a batch of images.
	 *
	 * @since 1.0.0
	 * @param array $image_ids Array of image IDs.
	 * @return array Processing statistics.
	 */
	private function process_batch($image_ids)
	{
		$stats = array(
			'successful' => 0,
			'failed' => 0,
			'skipped' => 0,
			'logs' => array(),
		);

		// Raise memory limit
		wp_raise_memory_limit('image');

		// Get converter
		require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-converter.php';
		$converter = new Img_Panda_Converter();
		$converter->init();

		// Get settings & Progress (for bulk specific options)
		$settings = get_option('Img_Panda_settings', array());
		$progress = get_option('img_panda_conversion_progress', array());
		$generate_alt_bulk = isset($progress['filters']['generate_alt']) ? (int)$progress['filters']['generate_alt'] : 0;
		$quality = isset($settings['quality']) ? intval($settings['quality']) : 60;

		foreach ($image_ids as $image_id) {
			$log_entry = array(
				'id' => $image_id,
				'time' => current_time('mysql'),
				'status' => '',
				'message' => '',
				'original_size' => 0,
				'new_size' => 0,
			);

			// Get file path
			$file_path = get_attached_file($image_id);

			if (!$file_path || !file_exists($file_path)) {
				$stats['skipped']++;
				$log_entry['status'] = 'skipped';
				$log_entry['message'] = __('File not found', 'img-panda');
				$stats['logs'][] = $log_entry;
				continue;
			}

			// Get original size
			$original_size = filesize($file_path);
			$log_entry['original_size'] = $original_size;

			// Check current states
			$is_already_converted = (get_post_meta($image_id, '_img_panda_converted', true) === '1');
			$current_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
			$is_missing_alt = empty($current_alt);

			// 1. Generate AI Alt Text if enabled AND missing
			$should_gen_alt = ($generate_alt_bulk === 1) || (isset($settings['auto_alt']) && '1' === $settings['auto_alt']);
			
			if ($should_gen_alt && $is_missing_alt) {
				require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-ai-handler.php';
				$ai_handler = new Img_Panda_AI_Handler();
				$ai_handler->generate_alt_text($image_id);
			}

			// 2. Convert to WebP if missing
			if (!$is_already_converted) {
				// Apply Min Size Filter for WebP conversion ONLY
				$min_size_kb = isset($progress['filters']['min_size']) ? intval($progress['filters']['min_size']) : 0;
				$min_bytes = $min_size_kb * 1024;

				if ($min_bytes > 0 && $original_size < $min_bytes) {
					// Skip WebP but count as "Success" because we might have handled Alt Text
					$log_entry['message'] = sprintf(__('SEO handled. WebP skipped (Size < %d KB)', 'img-panda'), $min_size_kb);
					$stats['successful']++;
					$log_entry['status'] = 'success';
				} else {
					// Backup if enabled
					if (isset($settings['enable_backup']) && '1' === $settings['enable_backup']) {
						$this->backup_image($image_id, $file_path);
					}

					// Convert image
					$result = $converter->convert_image_to_webp($file_path, $quality);

				if ($result['success']) {
					$webp_size = file_exists($result['webp_path']) ? filesize($result['webp_path']) : 0;
					$log_entry['new_size'] = $webp_size;

					// Force "Replace Mode" for Bulk Optimizer (as requested)
					$replace_mode = 'replace';

					if ('replace' === $replace_mode) {
						$this->replace_with_webp($image_id, $file_path, $result['webp_path']);
					} else {
						$converter->create_webp_attachment($image_id);
					}

					// Update post meta for conversion
					update_post_meta($image_id, '_img_panda_converted', '1');
					update_post_meta($image_id, '_img_panda_original_size', $original_size);
					update_post_meta($image_id, '_img_panda_new_size', $webp_size);
					update_post_meta($image_id, '_img_panda_conversion_date', time());
					update_post_meta($image_id, '_img_panda_path', $result['webp_path']);

					$this->update_stats(array('space_saved' => $original_size - $webp_size, 'conversion_successful' => 1));
					$stats['successful']++;
					$log_entry['status'] = 'success';
					$log_entry['message'] = __('Optimized & WebP created', 'img-panda');
					} else {
						$stats['failed']++;
						$log_entry['status'] = 'failed';
						$log_entry['message'] = $result['message'];
						$this->log_error($image_id, $result['message']);
					}
				} // End of WebP else
			} else { // Already converted
				// We already handled Alt Text above, so if we are here, we just skip WebP
				$stats['successful']++; // Still count as success because we finished what was needed (SEO)
				$log_entry['status'] = 'success';
				$log_entry['message'] = __('SEO Updated (Already WebP)', 'img-panda');
			}

			$stats['logs'][] = $log_entry;

			// Log to file
			$this->log_conversion($log_entry);
		}

		// Clear stats cache after batch processing
		Img_Panda_Stats::clear_cache();

		return $stats;
	}

	/**
	 * Backup an image before conversion.
	 *
	 * @since 1.0.0
	 * @param int    $image_id  Attachment ID.
	 * @param string $file_path File path.
	 * @return bool Success status.
	 */
	private function backup_image($image_id, $file_path)
	{
		$upload_dir = wp_upload_dir();
		$backup_dir = $upload_dir['basedir'] . '/img-panda-backups/' . gmdate('Y/m');

		// Create backup directory
		if (!file_exists($backup_dir)) {
			wp_mkdir_p($backup_dir);
		}

		$backup_path = $backup_dir . '/' . $image_id . '-' . basename($file_path);

		if (copy($file_path, $backup_path)) {
			update_post_meta($image_id, '_img_panda_backup_path', $backup_path);
			return true;
		}

		return false;
	}

	/**
	 * Pause bulk conversion.
	 *
	 * @since 1.0.0
	 * @return bool Success status.
	 */
	public function pause_conversion()
	{
		update_option('img_panda_conversion_status', 'paused');
		return true;
	}

	/**
	 * Resume bulk conversion.
	 *
	 * @since 1.0.0
	 * @return bool Success status.
	 */
	public function resume_conversion()
	{
		update_option('img_panda_conversion_status', 'active');
		return true;
	}

	/**
	 * Stop and clear conversion.
	 *
	 * @since 1.0.0
	 * @return bool Success status.
	 */
	public function stop_conversion()
	{
		delete_option('img_panda_conversion_queue');
		update_option('img_panda_conversion_status', 'stopped');
		return true;
	}

	/**
	 * Update global statistics.
	 *
	 * @since 1.0.0
	 * @param array $data Statistics data.
	 */
	private function update_stats($data)
	{
		$stats = get_option('img_panda_stats', array(
			'total_conversions' => 0,
			'total_space_saved' => 0,
			'successful_conversions' => 0,
			'failed_conversions' => 0,
		));

		if (isset($data['space_saved'])) {
			$stats['total_space_saved'] += $data['space_saved'];
		}

		if (isset($data['conversion_successful'])) {
			$stats['total_conversions']++;
			$stats['successful_conversions']++;
		}

		if (isset($data['conversion_failed'])) {
			$stats['total_conversions']++;
			$stats['failed_conversions']++;
		}

		update_option('img_panda_stats', $stats);
	}

	/**
	 * Log conversion entry.
	 *
	 * @since 1.0.0
	 * @param array $log_entry Log entry data.
	 */
	private function log_conversion($log_entry)
	{
		$logs = get_option('img_panda_conversion_logs', array());

		// Keep only last 500 entries
		if (count($logs) >= 500) {
			array_shift($logs);
		}

		$logs[] = $log_entry;
		update_option('img_panda_conversion_logs', $logs);
	}

	/**
	 * Log error.
	 *
	 * @since 1.0.0
	 * @param int    $image_id Image ID.
	 * @param string $message  Error message.
	 */
	private function log_error($image_id, $message)
	{
		$errors = get_option('img_panda_conversion_errors', array());

		$errors[] = array(
			'image_id' => $image_id,
			'message' => $message,
			'time' => current_time('mysql'),
		);

		// Keep only last 100 errors
		if (count($errors) > 100) {
			$errors = array_slice($errors, -100);
		}

		update_option('img_panda_conversion_errors', $errors);
	}

	/**
	 * Replace original image with WebP version.
	 *
	 * @since 1.0.0
	 * @param int    $image_id   Attachment ID.
	 * @param string $original_path Original file path.
	 * @param string $webp_path  WebP file path.
	 * @return bool Success status.
	 */
	private function replace_with_webp($image_id, $original_path, $webp_path)
	{
		if (!file_exists($webp_path)) {
			return false;
		}

		$upload_dir = wp_upload_dir();

		// Delete original file and all its sizes
		if (file_exists($original_path)) {
			wp_delete_file($original_path);
		}

		// Delete all generated sizes of the original image
		$metadata = wp_get_attachment_metadata($image_id);
		if ($metadata && isset($metadata['sizes'])) {
			$file_info = pathinfo($original_path);
			foreach ($metadata['sizes'] as $size_name => $size_data) {
				if (isset($size_data['file'])) {
					$size_file = $file_info['dirname'] . '/' . $size_data['file'];
					if (file_exists($size_file)) {
						wp_delete_file($size_file);
					}
				}
			}
		}

		// Handle both Windows and Unix paths for comparison and URLs
		$basedir_normalized = wp_normalize_path($upload_dir['basedir']);
		$webp_path_normalized = wp_normalize_path($webp_path);
		$webp_relative_path = str_replace($basedir_normalized . '/', '', $webp_path_normalized);

		// Update attachment file reference
		update_attached_file($image_id, $webp_relative_path);

		// Update MIME type in post
		wp_update_post(array(
			'ID' => $image_id,
			'post_mime_type' => 'image/webp',
		));

		// Update GUID to point to WebP URL
		$webp_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
		$webp_url = wp_normalize_path($webp_url);

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for GUID update
		$wpdb->update(
			$wpdb->posts,
			array('guid' => $webp_url),
			array('ID' => $image_id),
			array('%s'),
			array('%d')
		);

		// Regenerate attachment metadata for WebP file
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata($image_id, $webp_path);
		if ($metadata) {
			wp_update_attachment_metadata($image_id, $metadata);
		}

		// Update attachment post title and name if it had the original extension
		$file_info = pathinfo($webp_path);
		$original_title = get_the_title($image_id);
		$original_ext = strtolower(pathinfo($original_path, PATHINFO_EXTENSION));
		$webp_filename = $file_info['filename'] . '.webp';

		$update_post = array('ID' => $image_id);

		if (false !== strpos($original_title, '.' . $original_ext)) {
			$update_post['post_title'] = str_replace('.' . $original_ext, '.webp', $original_title);
			$update_post['post_name'] = sanitize_title($file_info['filename']);
		} else {
			// If title doesn't have extension, just update name
			$update_post['post_name'] = sanitize_title($file_info['filename']);
		}

		wp_update_post($update_post);

		// Clear any attachment cache
		clean_post_cache($image_id);

		return true;
	}

	/**
	 * Process next batch via WP Cron.
	 *
	 * @since 1.0.0
	 */
	public function process_next_batch_cron()
	{
		$result = $this->process_next_batch();

		// If not completed, schedule next batch
		if ('processing' === $result['status']) {
			wp_schedule_single_event(time() + 5, 'img_panda_bulk_cron');
		}
	}
}

