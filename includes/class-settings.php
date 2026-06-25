<?php
/**
 * Settings Page Class.
 *
 * Handles plugin settings and admin interface.
 *
 * @package Mkit_Si
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Settings Class.
 */
class Mkit_Si_Settings
{

	/**
	 * Option name for plugin settings.
	 *
	 * @var string
	 */
	private $option_name = 'Mkit_Si_settings';

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
		add_filter('plugin_action_links_' . MKIT_SI_PLUGIN_BASENAME, array($this, 'add_action_links'));

		// Fix menu icon size in admin sidebar
		add_action('admin_head', array($this, 'fix_menu_icon_css'));
	}

	/**
	 * Fix custom menu icon size in the WordPress admin sidebar.
	 *
	 * @since 1.0.0
	 */
	public function fix_menu_icon_css()
	{
		echo '<style>
			#toplevel_page_mak8it-smart-image .wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				padding: 0 !important;
				margin: 0 !important;
				max-width: 20px !important;
				max-height: 20px !important;
				object-fit: contain !important;
			}
			#toplevel_page_mak8it-smart-image .wp-menu-image {
				display: flex !important;
				align-items: center !important;
				justify-content: center !important;
			}
		</style>';
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
			__('Mak8it Smart Image - Dashboard', 'mak8it-smart-image'),
			__('M8 Smart Image', 'mak8it-smart-image'),
			'manage_options',
			'mak8it-smart-image',
			array($this, 'render_settings_page'),
			MKIT_SI_PLUGIN_URL . 'assets/m8-smart-image-logo.png',
			65
		);

		// Add Dashboard submenu (rename the first item)
		add_submenu_page(
			'mak8it-smart-image',
			__('Dashboard', 'mak8it-smart-image'),
			__('Dashboard', 'mak8it-smart-image'),
			'manage_options',
			'mak8it-smart-image',
			array($this, 'render_settings_page')
		);

		// Add Bulk Converter submenu
		add_submenu_page(
			'mak8it-smart-image',
			__('Bulk Converter', 'mak8it-smart-image'),
			__('Bulk Converter', 'mak8it-smart-image'),
			'manage_options',
			'mak8it-smart-image-bulk',
			array($this, 'render_bulk_page')
		);

		// Add System Info submenu
		add_submenu_page(
			'mak8it-smart-image',
			__('System Info', 'mak8it-smart-image'),
			__('System Info', 'mak8it-smart-image'),
			'manage_options',
			'mak8it-smart-image-system-info',
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
			'Mkit_Si_settings_group',
			$this->option_name,
			array($this, 'sanitize_settings')
		);

		// Add settings section
		add_settings_section(
			'Mkit_Si_main_section',
			'', // No title
			array($this, 'render_section_description'),
			'mak8it-smart-image'
		);

		// Auto-convert field
		add_settings_field(
			'auto_convert',
			__('Auto-Convert on Upload', 'mak8it-smart-image'),
			array($this, 'render_auto_convert_field'),
			'mak8it-smart-image',
			'Mkit_Si_main_section'
		);

		// Quality field
		add_settings_field(
			'quality',
			__('Conversion Quality', 'mak8it-smart-image'),
			array($this, 'render_quality_field'),
			'mak8it-smart-image',
			'Mkit_Si_main_section'
		);

		// Replace original field
		add_settings_field(
			'replace_original',
			__('File Handling', 'mak8it-smart-image'),
			array($this, 'render_replace_field'),
			'mak8it-smart-image',
			'Mkit_Si_main_section'
		);

		// Show WebP in library field
		add_settings_field(
			'show_webp_in_library',
			__('Media Library Display', 'mak8it-smart-image'),
			array($this, 'render_show_webp_field'),
			'mak8it-smart-image',
			'Mkit_Si_main_section'
		);

		// Frontend serving field
		add_settings_field(
			'enable_frontend_serving',
			__('Frontend Serving', 'mak8it-smart-image'),
			array($this, 'render_frontend_serving_field'),
			'mak8it-smart-image',
			'Mkit_Si_main_section'
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
		$support = Mkit_Si_Converter::check_webp_support();

		echo '<div class="mkit-si-support-status">';
		if ($support['supported']) {
			echo '<p class="mkit-si-support-yes">';
			echo '<span class="dashicons dashicons-yes-alt"></span> ';
			echo esc_html($support['message']);
			echo ' <strong>(' . esc_html($support['method']) . ')</strong>';
			echo '</p>';
		} else {
			echo '<p class="mkit-si-support-no">';
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
			<?php esc_html_e('Automatically convert images to WebP when uploaded', 'mak8it-smart-image'); ?>
		</label>
		<p class="description">
			<?php esc_html_e('Enable this to automatically convert JPEG and PNG images to WebP format during upload.', 'mak8it-smart-image'); ?>
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
			<?php esc_html_e('Set the quality for WebP conversion. Lower values = smaller file size but lower quality. Recommended: 60-80%', 'mak8it-smart-image'); ?>
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
				<?php esc_html_e('Keep both original and WebP files', 'mak8it-smart-image'); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo esc_attr($this->option_name); ?>[replace_original]" value="replace" <?php checked($value, 'replace'); ?>>
				<?php esc_html_e('Replace original with WebP file', 'mak8it-smart-image'); ?>
			</label>
		</fieldset>
		<p class="description">
			<?php esc_html_e('Choose whether to keep the original files or replace them with WebP versions.', 'mak8it-smart-image'); ?>
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
			<?php esc_html_e('Show WebP files as separate items in Media Library', 'mak8it-smart-image'); ?>
		</label>
		<p class="description">
			<?php esc_html_e('When "Keep both files" is selected, this will create separate media library entries for WebP versions. Disable this if you only want the original files to appear in the library (WebP files will still be created in the background).', 'mak8it-smart-image'); ?>
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
			<?php esc_html_e('Serve WebP images on frontend', 'mak8it-smart-image'); ?>
		</label>
		<p class="description">
			<?php esc_html_e('Automatically replace image URLs with WebP versions on the frontend if they exist. This uses output buffering to rewrite HTML.', 'mak8it-smart-image'); ?>
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
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'mak8it-smart-image'));
		}

		// Load template
		require_once MKIT_SI_PLUGIN_DIR . 'admin/settings-page.php';
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
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'mak8it-smart-image'));
		}

		// Load template
		require_once MKIT_SI_PLUGIN_DIR . 'admin/bulk-converter.php';
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
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'mak8it-smart-image'));
		}

		// Load template
		require_once MKIT_SI_PLUGIN_DIR . 'admin/system-info.php';
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
		$is_plugin_page = (strpos($hook, 'mak8it-smart-image') !== false);

		if ($is_plugin_page) {
			// Enqueue compiled Tailwind CSS with fonts (bundled locally for WP.org compliance)
			wp_enqueue_style(
				'mkit-si-tailwind',
				MKIT_SI_PLUGIN_URL . 'admin/css/tailwind-compiled.css',
				array(),
				MKIT_SI_VERSION
			);

			wp_enqueue_style(
				'mkit-si-header-layout',
				MKIT_SI_PLUGIN_URL . 'admin/css/header-layout.css',
				array('mkit-si-tailwind'),
				MKIT_SI_VERSION
			);

			wp_enqueue_style(
				'mkit-si-admin',
				MKIT_SI_PLUGIN_URL . 'admin/css/admin-style.css',
				array('mkit-si-tailwind', 'mkit-si-header-layout'),
				MKIT_SI_VERSION
			);

			wp_enqueue_style(
				'mkit-si-toast',
				MKIT_SI_PLUGIN_URL . 'admin/css/toast-notice.css',
				array(),
				MKIT_SI_VERSION
			);

			wp_enqueue_script(
				'mkit-si-toast',
				MKIT_SI_PLUGIN_URL . 'admin/js/toast-notice.js',
				array('jquery'),
				MKIT_SI_VERSION,
				true
			);

			wp_enqueue_script(
				'mkit-si-admin',
				MKIT_SI_PLUGIN_URL . 'admin/js/admin-script.js',
				array('jquery'),
				MKIT_SI_VERSION,
				true
			);

			// Localize admin script with stats for the dashboard chart
			wp_localize_script(
				'mkit-si-admin',
				'mkitSiAdminData',
				array(
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('mkit_si_nonce'),
					'stats' => Mkit_Si_Stats::get_cached_stats(),
					'settings' => get_option('Mkit_Si_settings', array()),
				)
			);

			// Enqueue Chart.js (bundled locally for WP.org compliance)
			wp_enqueue_script(
				'mkit-si-chart',
				MKIT_SI_PLUGIN_URL . 'admin/js/vendor/chart.min.js',
				array(),
				'4.4.8',
				true
			);

			wp_enqueue_style(
				'mak8it-smart-image-bulk',
				MKIT_SI_PLUGIN_URL . 'admin/css/bulk-style.css',
				array(),
				MKIT_SI_VERSION
			);
		}

		// Load bulk converter page JavaScript
		if (strpos($hook, 'mak8it-smart-image-bulk') !== false) {
			wp_enqueue_script(
				'mak8it-smart-image-bulk-js',
				MKIT_SI_PLUGIN_URL . 'admin/js/bulk-converter.js',
				array('jquery'),
				MKIT_SI_VERSION,
				true
			);

			// Localize script with AJAX data
			wp_localize_script(
				'mak8it-smart-image-bulk-js',
				'mkitSiBulkData',
				array(
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('mkit_si_nonce'),
					'strings' => array(
						'startingConversion' => __('Starting conversion...', 'mak8it-smart-image'),
						'processing' => __('Processing...', 'mak8it-smart-image'),
						'completed' => __('Conversion completed!', 'mak8it-smart-image'),
						'paused' => __('Conversion paused', 'mak8it-smart-image'),
						'stopped' => __('Conversion stopped', 'mak8it-smart-image'),
						'error' => __('An error occurred', 'mak8it-smart-image'),
						'confirmStop' => __('Are you sure you want to stop the conversion? Progress will be lost.', 'mak8it-smart-image'),
						'noFilters' => __('No filters applied', 'mak8it-smart-image'),
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
			admin_url('admin.php?page=mak8it-smart-image'),
			__('Dashboard', 'mak8it-smart-image')
		);

		$bulk_link = sprintf(
			'<a href="%s" style="color: #00a32a; font-weight: 600;">%s</a>',
			admin_url('admin.php?page=mak8it-smart-image-bulk'),
			__('Bulk Convert', 'mak8it-smart-image')
		);

		array_unshift($links, $settings_link, $bulk_link);

		return $links;
	}
}

