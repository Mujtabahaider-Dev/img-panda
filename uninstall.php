<?php
/**
 * Uninstall script for Mak8it Smart Image plugin.
 *
 * Fired when the plugin is uninstalled (deleted).
 *
 * @package Mkit_Si
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Delete plugin options
delete_option('Mkit_Si_settings');
delete_option('mkit_si_stats');
delete_option('mkit_si_activated');
delete_option('mkit_si_conversion_progress');
delete_option('mkit_si_conversion_status');
delete_option('mkit_si_conversion_queue');
delete_option('mkit_si_conversion_errors');

// Delete plugin transients
delete_transient('mkit_si_stats_cache');
delete_transient('mkit_si_conversion_success');
delete_transient('mkit_si_conversion_error');


// Delete all post meta related to WebP conversion
global $wpdb;

// Remove WebP related post meta
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup
$wpdb->query(
	"DELETE FROM {$wpdb->postmeta} 
	WHERE meta_key IN (
		'_mkit_si_converted',
		'_mkit_si_path',
		'_mkit_si_backup_path',
		'_mkit_si_original_size',
		'_mkit_si_new_size',
		'_mkit_si_conversion_date'
	)"
);

// Optional: Clean up WebP files from uploads directory
// Note: Commented out by default for safety. Enable if you want to delete all WebP files on uninstall.
/*
$upload_dir = wp_upload_dir();
$base_dir = $upload_dir['basedir'];

// Get all WebP files created by the plugin
$webp_files = $wpdb->get_col(
	"SELECT meta_value FROM {$wpdb->postmeta} 
	WHERE meta_key = '_mkit_si_path'"
);

foreach ($webp_files as $webp_file) {
	if (file_exists($webp_file)) {
		wp_delete_file($webp_file);
	}
}

// Clean up backup directory if it exists
$backup_dir = $base_dir . '/mkit-si-backups';
if (is_dir($backup_dir)) {
	// Recursively delete backup directory
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($backup_dir, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($files as $fileinfo) {
		$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
		$todo($fileinfo->getRealPath());
	}

	rmdir($backup_dir);
}
*/

// Clear any scheduled cron events
wp_clear_scheduled_hook('mkit_si_bulk_cron');

// Note: We don't delete converted images by default to preserve user data.
// Users should manually restore originals if needed before uninstalling.
