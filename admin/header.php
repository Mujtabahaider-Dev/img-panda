<?php
/**
 * Admin Header Template.
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for reading menu page slug
$img_panda_current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
?>

<style>
	/* Reset WordPress admin styles for our plugin container */
	.img-panda-layout-container {
		font-family: 'Space Grotesk', sans-serif;
		line-height: 1.5;
	}

	.img-panda-layout-container * {
		box-sizing: border-box;
	}

	.img-panda-layout-container h1,
	.img-panda-layout-container h2,
	.img-panda-layout-container h3,
	.img-panda-layout-container h4,
	.img-panda-layout-container h5,
	.img-panda-layout-container h6 {
		margin: 0;
		padding: 0;
		font-family: 'Space Grotesk', sans-serif;
	}

	.img-panda-layout-container p {
		margin: 0;
		padding: 0;
	}

	.img-panda-layout-container a {
		text-decoration: none;
	}

	.img-panda-layout-container button {
		font-family: 'Space Grotesk', sans-serif;
	}

	.glass-card {
		background: rgba(255, 255, 255, 0.8);
		backdrop-filter: blur(12px);
		border: 1px solid rgba(255, 255, 255, 0.3);
	}

	.dark .glass-card {
		background: rgba(23, 17, 33, 0.8);
		border: 1px solid rgba(255, 255, 255, 0.05);
	}

	/* Range input styles */
	.img-panda-layout-container input[type='range'] {
		-webkit-appearance: none;
		appearance: none;
		background: transparent;
		width: 100%;
	}

	.img-panda-layout-container input[type='range']::-webkit-slider-runnable-track {
		background: #e5e7eb;
		height: 6px;
		border-radius: 3px;
	}

	.img-panda-layout-container input[type='range']::-webkit-slider-thumb {
		-webkit-appearance: none;
		height: 18px;
		width: 18px;
		border-radius: 50%;
		background: #7c3bed;
		margin-top: -6px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
		cursor: pointer;
	}

	.img-panda-layout-container input[type='range']::-moz-range-track {
		background: #e5e7eb;
		height: 6px;
		border-radius: 3px;
	}

	.img-panda-layout-container input[type='range']::-moz-range-thumb {
		height: 18px;
		width: 18px;
		border-radius: 50%;
		background: #7c3bed;
		border: none;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
		cursor: pointer;
	}

	/* Radio and checkbox styles */
	.img-panda-layout-container input[type='radio'],
	.img-panda-layout-container input[type='checkbox'] {
		accent-color: #7c3bed;
	}

	/* WordPress admin background and footer */
	#wpcontent {
		padding-left: 0 !important;
		background: #FAF8FC;
	}

	.dark #wpcontent {
		background: #171121;
	}

	#wpfooter {
		display: none !important;
	}


	/* Fix button styles in WP admin - only for .bg-primary buttons */
	.img-panda-layout-container .bg-primary {
		background-color: #7c3bed !important;
		color: #ffffff !important;
	}

	/* White buttons with purple text */
	.img-panda-layout-container button.bg-white,
	.img-panda-layout-container .bg-white {
		background-color: #ffffff !important;
	}

	.img-panda-layout-container button.bg-white .text-primary,
	.img-panda-layout-container .bg-white.text-primary,
	.img-panda-layout-container button.text-primary {
		color: #7c3bed !important;
	}

	/* Gray buttons */
	.img-panda-layout-container button.bg-\[#f2f0f4\],
	.img-panda-layout-container .bg-\[#f2f0f4\] {
		background-color: #f2f0f4 !important;
		color: #131118 !important;
	}

	/* Reset default button styles */
	.img-panda-layout-container button {
		border: none;
		cursor: pointer;
	}

	.img-panda-layout-container .text-primary {
		color: #7c3bed !important;
	}

	/* Table styling */
	.img-panda-layout-container table {
		border-collapse: collapse;
		width: 100%;
	}

	.img-panda-layout-container table th,
	.img-panda-layout-container table td {
		text-align: left;
	}

	/* Fix link colors */
	.img-panda-layout-container a.text-primary {
		color: #7c3bed;
	}

	.img-panda-layout-container a.text-primary:hover {
		color: #6d2fd6;
	}
</style>

<div
	class="img-panda-layout-container flex flex-col min-h-screen text-[#131118] dark:text-white transition-colors duration-200">
	<!-- Top Navigation -->
	<header
		class="sticky top-[32px] z-50 bg-white/70 dark:bg-background-dark/70 backdrop-blur-md border-b border-[#f2f0f4] dark:border-white/5 py-3">
		<div class="max-w-[1200px] mx-auto flex items-center justify-between px-6">
			<div class="flex items-center gap-3">
				<div class="w-9 h-9 flex-shrink-0 bg-white dark:bg-white/5 rounded-xl shadow-sm border border-[#f2f0f4] dark:border-white/10 flex items-center justify-center group transition-all duration-300 hover:shadow-md hover:border-primary/20">
                    <span class="dashicons dashicons-format-image text-primary !text-[22px] !w-auto !h-auto flex items-center justify-center transform transition-transform duration-500 group-hover:scale-110"></span>
				</div>
				<div>
					<h1 class="text-lg font-black tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-[#131118] to-[#131118]/70 dark:from-white dark:to-white/70">
                        <?php esc_html_e('Img Panda', 'img-panda'); ?>
                    </h1>
				</div>
			</div>

			<div class="flex items-center gap-3">
				<button class="flex items-center justify-center rounded-xl h-10 w-10 bg-[#f2f0f4] dark:bg-white/5">
					<span class="material-symbols-outlined text-[20px]">notifications</span>
				</button>
				<a href="<?php echo esc_url(admin_url('admin.php?page=img-panda-bulk')); ?>"
					class="bg-primary text-white text-sm font-bold h-10 px-6 rounded-xl shadow-lg shadow-primary/20 flex items-center">
					<?php esc_html_e('Optimize All', 'img-panda'); ?>
				</a>
			</div>
		</div>
	</header>