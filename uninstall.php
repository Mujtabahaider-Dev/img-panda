<?php
/**
 * Uninstall script for Img Panda plugin.
 *
 * Fired when the plugin is uninstalled (deleted).
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Delete plugin options
delete_option('Img_Panda_settings');
delete_option('img_panda_stats');
delete_option('img_panda_activated');
delete_option('img_panda_conversion_progress');
delete_option('img_panda_conversion_status');
delete_option('img_panda_conversion_queue');
delete_option('img_panda_conversion_errors');

// Delete all post meta related to WebP conversion
global $wpdb;

// Remove WebP related post meta
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup
$wpdb->query(
	"DELETE FROM {$wpdb->postmeta} 
	WHERE meta_key IN (
		'_img_panda_converted',
		'_img_panda_path',
		'_img_panda_backup_path',
		'_img_panda_original_size',
		'_img_panda_new_size',
		'_img_panda_conversion_date'
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
	WHERE meta_key = '_img_panda_path'"
);

foreach ($webp_files as $webp_file) {
	if (file_exists($webp_file)) {
		wp_delete_file($webp_file);
	}
}

// Clean up backup directory if it exists
$backup_dir = $base_dir . '/img-panda-backups';
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
wp_clear_scheduled_hook('img_panda_bulk_cron');

// Note: We don't delete converted images by default to preserve user data.
// Users should manually restore originals if needed before uninstalling.
