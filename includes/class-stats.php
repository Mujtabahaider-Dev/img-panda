<?php
/**
 * Statistics Tracking Class.
 *
 * Tracks and manages WebP conversion statistics.
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Statistics Class.
 */
class Img_Panda_Stats
{

	/**
	 * Cache key for stats transient.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'img_panda_stats_cache';

	/**
	 * Cache duration in seconds (5 minutes).
	 *
	 * @var int
	 */
	const CACHE_DURATION = 300;

	/**
	 * Get cached stats for dashboard display.
	 *
	 * Uses transients to cache expensive database queries.
	 *
	 * @since 1.0.0
	 * @param bool $force_refresh Force refresh cache.
	 * @return array Cached statistics array.
	 */
	public static function get_cached_stats($force_refresh = false)
	{
		if (!$force_refresh) {
			$cached = get_transient(self::CACHE_KEY);
			if (false !== $cached) {
				return $cached;
			}
		}

		$stats = array(
			'total' => self::get_total_images(),
			'converted' => self::get_converted_count(),
			'pending' => 0,
		);
		$stats['pending'] = max(0, $stats['total'] - $stats['converted']);

		set_transient(self::CACHE_KEY, $stats, self::CACHE_DURATION);

		return $stats;
	}

	/**
	 * Clear the stats cache.
	 *
	 * Should be called after conversions complete.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function clear_cache()
	{
		delete_transient(self::CACHE_KEY);
	}

	/**
	 * Get all statistics.
	 *
	 * @since 1.0.0
	 * @return array Statistics array.
	 */
	public static function get_stats()
	{
		$stats = get_option('img_panda_stats', array(
			'total_conversions' => 0,
			'total_space_saved' => 0,
			'successful_conversions' => 0,
			'failed_conversions' => 0,
			'average_conversion_time' => 0,
			'last_conversion_date' => null,
		));

		// Get real-time counts from database
		$stats['total'] = self::get_total_images();
		$stats['converted'] = self::get_converted_count();
		$stats['pending'] = max(0, $stats['total'] - $stats['converted']);
		$stats['average_compression'] = self::get_average_compression();

		// Sum up original sizes for accurate percentage
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate stats query, caching handled by get_cached_stats()
		$stats['original_total_size'] = $wpdb->get_var(
			"SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_img_panda_original_size'"
		);

		// Dynamic space saved
		$stats['total_space_saved'] = self::get_total_space_saved();
		
		// Map aliases for backward compatibility
		$stats['total_images'] = $stats['total'];
		$stats['converted_images'] = $stats['converted'];

		return $stats;
	}

	/**
	 * Calculate average savings percentage.
	 */
	public static function get_average_savings_percentage()
	{
		$stats = self::get_stats();
		$original = isset($stats['original_total_size']) ? (float)$stats['original_total_size'] : 0;
		$saved = isset($stats['total_space_saved']) ? (float)$stats['total_space_saved'] : 0;

		if ($original <= 0) {
			return 0;
		}

		return round(($saved / $original) * 100);
	}

	/**
	 * Get total space saved.
	 * 
	 * @return int Total bytes saved.
	 */
	public static function get_total_space_saved()
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate stats query, no user input
		$original = (float) $wpdb->get_var( "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_img_panda_original_size'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate stats query, no user input
		$new      = (float) $wpdb->get_var( "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_img_panda_new_size'" );

		return max(0, (int) ($original - $new));
	}

	/**
	 * Get total image count.
	 * 
	 * Excludes WebP images created by this plugin (those with _webp_original_id meta)
	 * to avoid double-counting converted images.
	 *
	 * @since 1.0.0
	 * @return int Total count.
	 */
	public static function get_total_images()
	{
		global $wpdb;

		// Count JPEG/PNG images that could be converted
		// Plus WebP images that are NOT created by our plugin (user uploaded WebP)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom stats query, caching handled by caller
		$count = (int) $wpdb->get_var(
			"SELECT COUNT(p.ID) 
			FROM {$wpdb->posts} p 
			WHERE p.post_type = 'attachment' 
			AND (p.post_mime_type LIKE 'image/%')
			AND p.post_mime_type NOT IN ('image/svg+xml', 'image/x-icon', 'image/avif')
			AND NOT EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} pm 
				WHERE pm.post_id = p.ID 
				AND pm.meta_key = '_img_panda_original_id'
			)"
		);

		return $count;
	}

	/**
	 * Get converted images count.
	 * 
	 * Only counts original images that have been converted,
	 * not the WebP versions created by the plugin.
	 *
	 * @since 1.0.0
	 * @return int Converted count.
	 */
	public static function get_converted_count()
	{
		global $wpdb;

		// Count images with _webp_converted meta that are NOT plugin-created WebP
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom stats query, caching handled by caller
		$count = (int) $wpdb->get_var(
			"SELECT COUNT(p.ID) 
			FROM {$wpdb->posts} p 
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
			WHERE p.post_type = 'attachment' 
			AND (p.post_mime_type LIKE 'image/%')
			AND pm.meta_key = '_img_panda_converted' 
			AND pm.meta_value = '1'
			AND NOT EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} pm2 
				WHERE pm2.post_id = p.ID 
				AND pm2.meta_key = '_img_panda_original_id'
			)"
		);

		return $count;
	}

	/**
	 * Get average compression ratio.
	 *
	 * @since 1.0.0
	 * @return float Average compression percentage.
	 */
	public static function get_average_compression()
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate stats query, no user input
		$original = (float) $wpdb->get_var( "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_img_panda_original_size'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate stats query, no user input
		$new      = (float) $wpdb->get_var( "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_img_panda_new_size'" );

		if ( $original <= 0 ) {
			return 0;
		}

		return round( ( 1 - ( $new / $original ) ) * 100, 2 );
	}

	/**
	 * Get recent conversion activity.
	 * 
	 * Only returns original images that were converted,
	 * excluding plugin-created WebP attachments.
	 *
	 * @since 1.0.0
	 * @param int $limit Number of entries to retrieve.
	 * @return array Recent conversions.
	 */
	public static function get_recent_conversions($limit = 10)
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom stats query with pagination
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, pm1.meta_value as conversion_date, pm2.meta_value as original_size, pm3.meta_value as new_size
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm_main ON p.ID = pm_main.post_id AND pm_main.meta_key = '_img_panda_converted' AND pm_main.meta_value = '1'
				LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_img_panda_conversion_date'
				LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_img_panda_original_size'
				LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_img_panda_new_size'
				WHERE p.post_type = 'attachment'
				AND p.post_mime_type IN ('image/jpeg', 'image/jpg', 'image/png', 'image/webp')
				AND NOT EXISTS (
					SELECT 1 FROM {$wpdb->postmeta} pm_check 
					WHERE pm_check.post_id = p.ID 
					AND pm_check.meta_key = '_img_panda_original_id'
				)
				ORDER BY pm1.meta_value DESC, p.ID DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return $results;
	}

	/**
	 * Get conversion logs.
	 *
	 * @since 1.0.0
	 * @param int $page     Page number.
	 * @param int $per_page Items per page.
	 * @return array Logs array with pagination data.
	 */
	public static function get_conversion_logs($page = 1, $per_page = 50)
	{
		$logs = get_option('img_panda_conversion_logs', array());
		$total = count($logs);

		// Reverse to show newest first
		$logs = array_reverse($logs);

		// Paginate
		$offset = ($page - 1) * $per_page;
		$paged_logs = array_slice($logs, $offset, $per_page);

		return array(
			'logs' => $paged_logs,
			'total' => $total,
			'total_pages' => ceil($total / $per_page),
			'current_page' => $page,
		);
	}

	/**
	 * Clear all conversion logs.
	 *
	 * @since 1.0.0
	 * @return bool Success status.
	 */
	public static function clear_logs()
	{
		delete_option('img_panda_conversion_logs');
		return true;
	}

	/**
	 * Export logs to CSV.
	 *
	 * @since 1.0.0
	 * @return string CSV content.
	 */
	public static function export_logs_csv()
	{
		$logs = get_option('img_panda_conversion_logs', array());

		$csv = "Date,Image ID,Status,Original Size (bytes),New Size (bytes),Savings (bytes),Message\n";

		foreach ($logs as $log) {
			$savings = isset($log['original_size'], $log['new_size'])
				? $log['original_size'] - $log['new_size']
				: 0;

			$csv .= sprintf(
				"%s,%d,%s,%d,%d,%d,%s\n",
				isset($log['time']) ? $log['time'] : '',
				isset($log['id']) ? $log['id'] : 0,
				isset($log['status']) ? $log['status'] : '',
				isset($log['original_size']) ? $log['original_size'] : 0,
				isset($log['new_size']) ? $log['new_size'] : 0,
				$savings,
				isset($log['message']) ? str_replace(',', ';', $log['message']) : ''
			);
		}

		return $csv;
	}

	/**
	 * Get conversion errors.
	 *
	 * @since 1.0.0
	 * @return array Error logs.
	 */
	public static function get_errors()
	{
		return get_option('img_panda_conversion_errors', array());
	}

	/**
	 * Get total space saved in human-readable format.
	 *
	 * @since 1.0.0
	 * @return string Formatted size.
	 */
	public static function get_formatted_space_saved()
	{
		$stats = self::get_stats();
		return size_format($stats['total_space_saved'], 2);
	}

	/**
	 * Reset all statistics.
	 *
	 * @since 1.0.0
	 * @return bool Success status.
	 */
	public static function reset_stats()
	{
		$default_stats = array(
			'total_conversions' => 0,
			'total_space_saved' => 0,
			'successful_conversions' => 0,
			'failed_conversions' => 0,
			'average_conversion_time' => 0,
			'last_conversion_date' => null,
		);

		update_option('img_panda_stats', $default_stats);
		delete_option('img_panda_conversion_logs');
		delete_option('img_panda_conversion_errors');

		return true;
	}
}


