<?php
/**
 * Frontend WebP Serving Class.
 *
 * Handles the replacement of image URLs with WebP versions on the frontend.
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Class.
 */
class Img_Panda_Frontend {

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Initialize the frontend handler.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Load settings
		$this->settings = get_option( 'Img_Panda_settings', array() );

		// Check if frontend serving is enabled (default: true)
		$enabled = isset( $this->settings['enable_frontend_serving'] ) ? $this->settings['enable_frontend_serving'] : '1';

		if ( '1' !== $enabled ) {
			return;
		}

		// Start output buffering
		add_action( 'template_redirect', array( $this, 'start_buffering' ), 1 );

		// Ensure the buffer is always closed on shutdown
		add_action( 'shutdown', array( $this, 'stop_buffering' ), 0 );
	}

	/**
	 * Start output buffering.
	 *
	 * @since 1.0.0
	 */
	public function start_buffering() {
		ob_start( array( $this, 'replace_images' ) );
	}

	/**
	 * Stop output buffering.
	 *
	 * Flushes and closes the buffer opened by start_buffering().
	 * Hooked to 'shutdown' to guarantee execution regardless of plugin flow.
	 *
	 * @since 1.0.0
	 */
	public function stop_buffering() {
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}

	/**
	 * Replace images in HTML with WebP versions.
	 *
	 * @since 1.0.0
	 * @param string $content HTML content.
	 * @return string Modified HTML content.
	 */
	public function replace_images( $content ) {
		// Don't process if content is empty
		if ( empty( $content ) ) {
			return $content;
		}

		// Don't process XML or JSON
		if ( headers_sent() ) {
			$headers = headers_list();
			foreach ( $headers as $header ) {
				if ( stripos( $header, 'content-type' ) !== false && ( stripos( $header, 'xml' ) !== false || stripos( $header, 'json' ) !== false ) ) {
					return $content;
				}
			}
		}

		// Regex to find img tags
		// Matches: <img ... src="url" ... >
		$pattern = '/<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/i';

		return preg_replace_callback( $pattern, array( $this, 'replace_image_callback' ), $content );
	}

	/**
	 * Callback for image replacement.
	 *
	 * @since 1.0.0
	 * @param array $matches Regex matches.
	 * @return string Modified img tag.
	 */
	public function replace_image_callback( $matches ) {
		$img_tag = $matches[0];
		$src_url = $matches[1];

		// Check if it's a local image
		$upload_dir = wp_upload_dir();
		$base_url   = $upload_dir['baseurl'];

		if ( strpos( $src_url, $base_url ) === false ) {
			return $img_tag; // External image, skip
		}

		// Check extension
		$extension = strtolower( pathinfo( $src_url, PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, array( 'jpg', 'jpeg', 'png' ), true ) ) {
			return $img_tag; // Not a supported image
		}

		// Get local file path
		$relative_path = str_replace( $base_url, '', $src_url );
		$file_path     = $upload_dir['basedir'] . $relative_path;

		// Check if WebP exists
		$file_info = pathinfo( $file_path );
		$webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

		if ( file_exists( $webp_path ) ) {
			$webp_url = str_replace( $extension, 'webp', $src_url );
			
			// Replace src
			$img_tag = str_replace( $src_url, $webp_url, $img_tag );
			
			// Handle srcset if present
			if ( preg_match( '/srcset=["\']([^"\']+)["\']/', $img_tag, $srcset_matches ) ) {
				$srcset = $srcset_matches[1];
				$new_srcset = $this->replace_srcset( $srcset );
				$img_tag = str_replace( $srcset, $new_srcset, $img_tag );
			}
		}

		return $img_tag;
	}

	/**
	 * Replace URLs in srcset.
	 *
	 * @since 1.0.0
	 * @param string $srcset Srcset string.
	 * @return string Modified srcset string.
	 */
	private function replace_srcset( $srcset ) {
		$sources = explode( ',', $srcset );
		$new_sources = array();

		foreach ( $sources as $source ) {
			$source = trim( $source );
			$parts = preg_split( '/\s+/', $source );
			
			if ( isset( $parts[0] ) ) {
				$url = $parts[0];
				$extension = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );
				
				if ( in_array( $extension, array( 'jpg', 'jpeg', 'png' ), true ) ) {
					// Check if WebP exists for this size
					$upload_dir = wp_upload_dir();
					$base_url   = $upload_dir['baseurl'];
					
					if ( strpos( $url, $base_url ) !== false ) {
						$relative_path = str_replace( $base_url, '', $url );
						$file_path     = $upload_dir['basedir'] . $relative_path;
						
						$file_info = pathinfo( $file_path );
						$webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
						
						if ( file_exists( $webp_path ) ) {
							$url = str_replace( $extension, 'webp', $url );
						}
					}
				}
				
				$parts[0] = $url;
			}
			
			$new_sources[] = implode( ' ', $parts );
		}

		return implode( ', ', $new_sources );
	}
}
