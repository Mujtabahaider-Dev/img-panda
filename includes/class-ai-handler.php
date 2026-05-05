<?php
/**
 * AI Handler Class.
 *
 * Handles communication with AI providers and automatic SEO generation.
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * AI Handler Class.
 */
class Img_Panda_AI_Handler
{

	/**
	 * Settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Initialize AI handler.
	 */
	public function __construct()
	{
		$this->settings = get_option('Img_Panda_settings', array());
	}

	/**
	 * Initialize automation hooks.
	 */
	public function init()
	{
		// Only hook if Auto Alt is enabled
		if (isset($this->settings['auto_alt']) && '1' === $this->settings['auto_alt']) {
			add_action('wp_generate_attachment_metadata', array($this, 'handle_automation'), 20, 2);
		}
	}

	/**
	 * Handle automatic generation during upload.
	 * 
	 * @param array $metadata Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array
	 */
	public function handle_automation($metadata, $attachment_id)
	{
		// If in 'keep_both' mode, skip AI for the original (JPG/PNG) and only process the WebP version
		$replace_mode = isset($this->settings['replace_original']) ? $this->settings['replace_original'] : 'keep_both';
		$mime_type = get_post_mime_type($attachment_id);

		if ('keep_both' === $replace_mode && 'image/webp' !== $mime_type) {
			return $metadata;
		}

		// Production logic: Generate synchronously for accuracy on upload
		$this->generate_alt_text($attachment_id);
		return $metadata;
	}

	/**
	 * set_test_credentials
	 * Overrides internal settings for a single test connection.
	 */
	public function set_test_credentials($key, $provider, $model)
	{
		$this->settings['ai_api_key']  = $key;
		$this->settings['ai_provider'] = $provider;
		$this->settings['ai_model']    = $model;
	}

	/**
	 * Generate ALT text for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|bool Generated ALT text or false on failure.
	 */
	public function generate_alt_text($attachment_id)
	{
		try {
			$api_key = isset($this->settings['ai_api_key']) ? $this->settings['ai_api_key'] : '';
			$provider = isset($this->settings['ai_provider']) ? $this->settings['ai_provider'] : 'gemini';

			if (empty($api_key)) {
				return false;
			}

			$file_path = get_attached_file($attachment_id);
			if (!file_exists($file_path)) {
				return false;
			}

            // EXCLUSION CHECK: Don't overwrite existing Alt-Text (User provided or previous AI)
            $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if (!empty($existing_alt)) {
                return $existing_alt;
            }

			// FINGERPRINT CACHING: Prevent redundant API calls
			$file_hash = md5_file($file_path);
			$cache_key = 'img_panda_alt_' . $file_hash;
			$cached = get_transient($cache_key);

			if ($cached) {
				update_post_meta($attachment_id, '_wp_attachment_image_alt', $cached);
				return $cached;
			}

			// Ensure we have a small version to send
			$image_info = $this->get_base64_image($file_path);
			if (!$image_info) {
				return false;
			}

			$image_data = $image_info['data'];
			$mime_type = $image_info['mime'];

			$prompt = "Describe this image in one short, SEO-friendly sentence (maximum 125 characters). Focus on the main subject. Provide only the description text.";

			$alt_text = false;
			if ('gemini' === $provider) {
				$alt_text = $this->call_gemini($image_data, $api_key, $prompt, $mime_type);
			} elseif ('openai' === $provider) {
				$alt_text = $this->call_openai($image_data, $api_key, $prompt, $mime_type);
			}

			if ($alt_text) {
				update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
				// Cache result for 48 hours
				set_transient($cache_key, $alt_text, 48 * HOUR_IN_SECONDS);
				return $alt_text;
			}

		} catch (Exception $e) {
			error_log('Img Panda AI Exception: ' . $e->getMessage());
		}

		return false;
	}

	/**
	 * Get Base64 encoded image data of a resized version.
	 */
	private function get_base64_image($path)
	{
		if (!function_exists('wp_get_image_editor')) {
			require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		}

		$editor = wp_get_image_editor($path);
		if (is_wp_error($editor)) {
			return false;
		}

		// Production standard: 512px is the sweet spot for Vision AI models
		$editor->resize(512, 512, false);
		$temp_file = tempnam(sys_get_temp_dir(), 'panda_ai_');
		$saved = $editor->save($temp_file, 'image/jpeg');
		
		if (is_wp_error($saved)) {
			@unlink($temp_file);
			return false;
		}

		$data = file_get_contents($saved['path']);
		@unlink($saved['path']);
		@unlink($temp_file);

		return array(
			'data' => base64_encode($data),
			'mime' => 'image/jpeg'
		);
	}

	/**
	 * Get SSL verification setting based on environment.
	 */
	private function get_ssl_verify()
	{
		$is_local = (defined('WP_ENVIRONMENT_TYPE') && 'local' === WP_ENVIRONMENT_TYPE) || 
					strpos(site_url(), '.test') !== false || 
					strpos(site_url(), 'localhost') !== false;

		return $is_local ? false : true;
	}

	/**
	 * Call Google Gemini API (v1 Stable).
	 */
	private function call_gemini($base64_image, $api_key, $prompt, $mime_type = 'image/jpeg')
	{
		$model = isset($this->settings['ai_model']) ? strtolower($this->settings['ai_model']) : 'gemini-flash-latest';
		$url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $api_key;

		$body = array(
			'contents' => array(
				array(
					'parts' => array(
						array('text' => $prompt),
						array('inline_data' => array('mime_type' => $mime_type, 'data' => $base64_image))
					)
				)
			),
			'safetySettings' => array(
				array('category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'),
				array('category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'),
				array('category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'),
				array('category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'),
			)
		);

		$response = wp_remote_post($url, array(
			'headers'   => array('Content-Type' => 'application/json'),
			'body'      => wp_json_encode($body),
			'timeout'   => 45,
			'sslverify' => $this->get_ssl_verify()
		));

		if (is_wp_error($response)) {
			update_option('img_panda_ai_last_error', $response->get_error_message());
			return false;
		}

		$data = json_decode(wp_remote_retrieve_body($response), true);
		
		if (isset($data['error']['message'])) {
			update_option('img_panda_ai_last_error', $data['error']['message']);
			return false;
		}

		if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
			delete_option('img_panda_ai_last_error');
			return trim($data['candidates'][0]['content']['parts'][0]['text']);
		}

		return false;
	}

	/**
	 * Call OpenAI API.
	 */
	private function call_openai($base64_image, $api_key, $prompt, $mime_type = 'image/jpeg')
	{
		$url = "https://api.openai.com/v1/chat/completions";
		$model = isset($this->settings['ai_model']) ? $this->settings['ai_model'] : 'gpt-4o-mini';

		$body = array(
			'model' => $model,
			'messages' => array(
				array(
					'role' => 'user',
					'content' => array(
						array('type' => 'text', 'text' => $prompt),
						array('type' => 'image_url', 'image_url' => array('url' => "data:" . $mime_type . ";base64," . $base64_image))
					)
				)
			),
			'max_tokens' => 60
		);

		$response = wp_remote_post($url, array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $api_key
			),
			'body'      => wp_json_encode($body),
			'timeout'   => 45,
			'sslverify' => $this->get_ssl_verify()
		));

		if (is_wp_error($response)) {
			update_option('img_panda_ai_last_error', $response->get_error_message());
			return false;
		}

		$data = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($data['choices'][0]['message']['content'])) {
			delete_option('img_panda_ai_last_error');
			return trim($data['choices'][0]['message']['content']);
		}

		return false;
	}
}
