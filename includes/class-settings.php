<?php
/**
 * Settings Page Class.
 *
 * Handles plugin settings and admin interface.
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Settings Class.
 */
class Img_Panda_Settings
{

	/**
	 * Option name for plugin settings.
	 *
	 * @var string
	 */
	private $option_name = 'Img_Panda_settings';

	/**
	 * Initialize settings.
	 *
	 * @since 1.0.0
	 */
	public function init()
	{
		// Add admin menu
		add_action('admin_menu', array($this, 'add_settings_page'));

		// Register settings
		add_action('admin_init', array($this, 'register_settings'));

		// Enqueue admin assets
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

		// Add settings link on plugins page
		add_filter('plugin_action_links_' . IMG_PANDA_PLUGIN_BASENAME, array($this, 'add_action_links'));
	}

	/**
	 * Add settings page to admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page()
	{
		// Add main menu page (Dashboard)
		add_menu_page(
			__('Img Panda - Dashboard', 'img-panda'),
			__('Img Panda', 'img-panda'),
			'manage_options',
			'img-panda',
			array($this, 'render_settings_page'),
			'dashicons-format-image',
			65
		);

		// Add Dashboard submenu (rename the first item)
		add_submenu_page(
			'img-panda',
			__('Dashboard', 'img-panda'),
			__('Dashboard', 'img-panda'),
			'manage_options',
			'img-panda',
			array($this, 'render_settings_page')
		);

		// Add Bulk Converter submenu
		add_submenu_page(
			'img-panda',
			__('Bulk Converter', 'img-panda'),
			__('Bulk Converter', 'img-panda'),
			'manage_options',
			'img-panda-bulk',
			array($this, 'render_bulk_page')
		);

		// Add System Info submenu
		add_submenu_page(
			'img-panda',
			__('System Info', 'img-panda'),
			__('System Info', 'img-panda'),
			'manage_options',
			'img-panda-system-info',
			array($this, 'render_system_info_page')
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings()
	{
		// Register setting
		register_setting(
			'Img_Panda_settings_group',
			$this->option_name,
			array($this, 'sanitize_settings')
		);

		// Add settings section
		add_settings_section(
			'Img_Panda_main_section',
			'', // No title
			array($this, 'render_section_description'),
			'img-panda'
		);

		// Auto-convert field
		add_settings_field(
			'auto_convert',
			__('Auto-Convert on Upload', 'img-panda'),
			array($this, 'render_auto_convert_field'),
			'img-panda',
			'Img_Panda_main_section'
		);

		// Quality field
		add_settings_field(
			'quality',
			__('Conversion Quality', 'img-panda'),
			array($this, 'render_quality_field'),
			'img-panda',
			'Img_Panda_main_section'
		);

		// Replace original field
		add_settings_field(
			'replace_original',
			__('File Handling', 'img-panda'),
			array($this, 'render_replace_field'),
			'img-panda',
			'Img_Panda_main_section'
		);

		// Show WebP in library field
		add_settings_field(
			'show_webp_in_library',
			__('Media Library Display', 'img-panda'),
			array($this, 'render_show_webp_field'),
			'img-panda',
			'Img_Panda_main_section'
		);

		// Frontend serving field
		add_settings_field(
			'enable_frontend_serving',
			__('Frontend Serving', 'img-panda'),
			array($this, 'render_frontend_serving_field'),
			'img-panda',
			'Img_Panda_main_section'
		);
	}

	/**
	 * Render section description.
	 *
	 * @since 1.0.0
	 */
	public function render_section_description()
	{
		// Check WebP support
		$support = Img_Panda_Converter::check_webp_support();

		echo '<div class="img-panda-support-status">';
		if ($support['supported']) {
			echo '<p class="img-panda-support-yes">';
			echo '<span class="dashicons dashicons-yes-alt"></span> ';
			echo esc_html($support['message']);
			echo ' <strong>(' . esc_html($support['method']) . ')</strong>';
			echo '</p>';
		} else {
			echo '<p class="img-panda-support-no">';
			echo '<span class="dashicons dashicons-warning"></span> ';
			echo esc_html($support['message']);
			echo '</p>';
		}
		echo '</div>';
	}

	/**
	 * Render auto-convert field.
	 *
	 * @since 1.0.0
	 */
	public function render_auto_convert_field()
	{
		$settings = get_option($this->option_name, array());
		$value = isset($settings['auto_convert']) ? $settings['auto_convert'] : '1';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[auto_convert]" value="1" <?php checked($value, '1'); ?>>
			<?php esc_html_e('Automatically convert images to WebP when uploaded', 'img-panda'); ?>
		</label>
		<p class="description">
			<?php esc_html_e('Enable this to automatically convert JPEG and PNG images to WebP format during upload.', 'img-panda'); ?>
		</p>
		<?php
	}

	/**
	 * Render quality field.
	 *
	 * @since 1.0.0
	 */
	public function render_quality_field()
	{
		$settings = get_option($this->option_name, array());
		$value = isset($settings['quality']) ? intval($settings['quality']) : 60;
		?>
		<div class="quality-slider-container">
			<input type="range" id="quality-slider" name="<?php echo esc_attr($this->option_name); ?>[quality]" min="10"
				max="100" value="<?php echo esc_attr($value); ?>" step="5">
			<span class="quality-value"><?php echo esc_html($value); ?>%</span>
		</div>
		<p class="description">
			<?php esc_html_e('Set the quality for WebP conversion. Lower values = smaller file size but lower quality. Recommended: 60-80%', 'img-panda'); ?>
		</p>
		<?php
	}

	/**
	 * Render replace original field.
	 *
	 * @since 1.0.0
	 */
	public function render_replace_field()
	{
		$settings = get_option($this->option_name, array());
		$value = isset($settings['replace_original']) ? $settings['replace_original'] : 'keep_both';
		?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo esc_attr($this->option_name); ?>[replace_original]" value="keep_both" <?php checked($value, 'keep_both'); ?>>
				<?php esc_html_e('Keep both original and WebP files', 'img-panda'); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo esc_attr($this->option_name); ?>[replace_original]" value="replace" <?php checked($value, 'replace'); ?>>
				<?php esc_html_e('Replace original with WebP file', 'img-panda'); ?>
			</label>
		</fieldset>
		<p class="description">
			<?php esc_html_e('Choose whether to keep the original files or replace them with WebP versions.', 'img-panda'); ?>
		</p>
		<?php
	}

	/**
	 * Render show WebP in library field.
	 *
	 * @since 1.0.0
	 */
	public function render_show_webp_field()
	{
		$settings = get_option($this->option_name, array());
		$value = isset($settings['show_webp_in_library']) ? $settings['show_webp_in_library'] : '1';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[show_webp_in_library]" value="1" <?php checked($value, '1'); ?>>
			<?php esc_html_e('Show WebP files as separate items in Media Library', 'img-panda'); ?>
		</label>
		<p class="description">
			<?php esc_html_e('When "Keep both files" is selected, this will create separate media library entries for WebP versions. Disable this if you only want the original files to appear in the library (WebP files will still be created in the background).', 'img-panda'); ?>
		</p>
		<?php
	}

	/**
	 * Render frontend serving field.
	 *
	 * @since 1.0.0
	 */
	public function render_frontend_serving_field()
	{
		$settings = get_option($this->option_name, array());
		$value = isset($settings['enable_frontend_serving']) ? $settings['enable_frontend_serving'] : '1';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[enable_frontend_serving]" value="1" <?php checked($value, '1'); ?>>
			<?php esc_html_e('Serve WebP images on frontend', 'img-panda'); ?>
		</label>
		<p class="description">
			<?php esc_html_e('Automatically replace image URLs with WebP versions on the frontend if they exist. This uses output buffering to rewrite HTML.', 'img-panda'); ?>
		</p>
		<?php
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @since 1.0.0
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_settings($input)
	{
		$sanitized = array();

		// Sanitize auto_convert (toggle)
		$sanitized['auto_convert'] = (isset($input['auto_convert']) && '1' === $input['auto_convert']) ? '1' : '0';

		// Sanitize quality (must be 10-100)
		if (isset($input['quality'])) {
			$quality = intval($input['quality']);
			$sanitized['quality'] = max(10, min(100, $quality));
		} else {
			$sanitized['quality'] = 60;
		}

		// Sanitize replace_original (must be one of two values)
		if (isset($input['replace_original']) && in_array($input['replace_original'], array('keep_both', 'replace'), true)) {
			$sanitized['replace_original'] = sanitize_text_field($input['replace_original']);
		} else {
			$sanitized['replace_original'] = 'keep_both';
		}

		// Sanitize show_webp_in_library (checkbox)
		$sanitized['show_webp_in_library'] = isset($input['show_webp_in_library']) ? '1' : '0';

		// Sanitize enable_frontend_serving (checkbox)
		$sanitized['enable_frontend_serving'] = isset($input['enable_frontend_serving']) ? '1' : '0';

		// Sanitize Advanced Configuration (numbers)
		$sanitized['min_size'] = isset($input['min_size']) ? absint($input['min_size']) : 0;
		$sanitized['max_size'] = isset($input['max_size']) ? absint($input['max_size']) : 10;

		// Sanitize AI Settings
		$sanitized['ai_provider'] = isset($input['ai_provider']) ? sanitize_text_field($input['ai_provider']) : 'gemini';
		$sanitized['ai_model']    = isset($input['ai_model']) ? sanitize_text_field($input['ai_model']) : 'gemini-1.5-flash';
		$sanitized['ai_api_key']  = isset($input['ai_api_key']) ? sanitize_text_field($input['ai_api_key']) : '';
		$sanitized['ai_api_url']  = isset($input['ai_api_url']) ? esc_url_raw(sanitize_text_field($input['ai_api_url'])) : '';
		$sanitized['auto_alt']    = (isset($input['auto_alt']) && '1' === $input['auto_alt']) ? '1' : '0';

		return $sanitized;
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page()
	{
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'img-panda'));
		}

		// Load template
		require_once IMG_PANDA_PLUGIN_DIR . 'admin/settings-page.php';
	}


	/**
	 * Render bulk converter page.
	 *
	 * @since 1.0.0
	 */
	public function render_bulk_page()
	{
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'img-panda'));
		}

		// Load template
		require_once IMG_PANDA_PLUGIN_DIR . 'admin/bulk-converter.php';
	}

	/**
	 * Render system info page.
	 *
	 * @since 1.0.0
	 */
	public function render_system_info_page()
	{
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'img-panda'));
		}

		// Load template
		require_once IMG_PANDA_PLUGIN_DIR . 'admin/system-info.php';
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets($hook)
	{
		// Check if we're on a plugin page
		$is_plugin_page = (strpos($hook, 'img-panda') !== false);

		if ($is_plugin_page) {
			// Enqueue compiled Tailwind CSS with fonts (bundled locally for WP.org compliance)
			wp_enqueue_style(
				'img-panda-tailwind',
				IMG_PANDA_PLUGIN_URL . 'admin/css/tailwind-compiled.css',
				array(),
				IMG_PANDA_VERSION
			);

			wp_enqueue_style(
				'img-panda-admin',
				IMG_PANDA_PLUGIN_URL . 'admin/css/admin-style.css',
				array('img-panda-tailwind'),
				IMG_PANDA_VERSION
			);

			wp_enqueue_script(
				'img-panda-admin',
				IMG_PANDA_PLUGIN_URL . 'admin/js/admin-script.js',
				array('jquery'),
				IMG_PANDA_VERSION,
				true
			);

			// Localize admin script with stats for the dashboard chart
			wp_localize_script(
				'img-panda-admin',
				'imgPandaAdminData',
				array(
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('img_panda_nonce'),
					'stats' => Img_Panda_Stats::get_cached_stats(),
					'settings' => get_option('Img_Panda_settings', array()),
				)
			);

			// Enqueue Chart.js (bundled locally for WP.org compliance)
			wp_enqueue_script(
				'img-panda-chart',
				IMG_PANDA_PLUGIN_URL . 'admin/js/vendor/chart.min.js',
				array(),
				'4.4.1',
				true
			);

			wp_enqueue_style('img-panda-admin', IMG_PANDA_PLUGIN_URL . 'admin/css/admin-style.css', array(), IMG_PANDA_VERSION, 'all');


			wp_enqueue_style(
				'img-panda-bulk',
				IMG_PANDA_PLUGIN_URL . 'admin/css/bulk-style.css',
				array(),
				IMG_PANDA_VERSION
			);
		}

		// Load bulk converter page JavaScript
		if (strpos($hook, 'img-panda-bulk') !== false) {
			wp_enqueue_script(
				'img-panda-bulk-js',
				IMG_PANDA_PLUGIN_URL . 'admin/js/bulk-converter.js',
				array('jquery'),
				IMG_PANDA_VERSION,
				true
			);

			// Localize script with AJAX data
			wp_localize_script(
				'img-panda-bulk-js',
				'imgPandaBulkData',
				array(
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('img_panda_nonce'),
					'strings' => array(
						'startingConversion' => __('Starting conversion...', 'img-panda'),
						'processing' => __('Processing...', 'img-panda'),
						'completed' => __('Conversion completed!', 'img-panda'),
						'paused' => __('Conversion paused', 'img-panda'),
						'stopped' => __('Conversion stopped', 'img-panda'),
						'error' => __('An error occurred', 'img-panda'),
						'confirmStop' => __('Are you sure you want to stop the conversion? Progress will be lost.', 'img-panda'),
						'noFilters' => __('No filters applied', 'img-panda'),
					),
				)
			);
		}
	}

	/**
	 * Add action links on plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_action_links($links)
	{
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url('admin.php?page=img-panda'),
			__('Dashboard', 'img-panda')
		);

		$bulk_link = sprintf(
			'<a href="%s" style="color: #00a32a; font-weight: 600;">%s</a>',
			admin_url('admin.php?page=img-panda-bulk'),
			__('Bulk Convert', 'img-panda')
		);

		array_unshift($links, $settings_link, $bulk_link);

		return $links;
	}
}

