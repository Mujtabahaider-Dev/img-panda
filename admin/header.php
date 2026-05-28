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