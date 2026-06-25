<?php
/**
 * Admin Header Template.
 *
 * @package Mkit_Si
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for reading menu page slug
$mkit_si_current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
?>

<div
	class="mkit-si-layout-container flex flex-col min-h-screen text-[#131118] dark:text-white transition-colors duration-200">
	<!-- Top Navigation -->
	<header
		class="sticky top-[32px] z-50 bg-white/70 dark:bg-background-dark/70 backdrop-blur-md border-b border-[#f2f0f4] dark:border-white/5 py-3">
		<div class="max-w-[1200px] mx-auto flex items-center justify-between px-6">
			<div class="flex items-center gap-3">

				<div class="w-11 h-11 flex-shrink-0 rounded-xl overflow-hidden shadow-sm border border-[#f2f0f4] dark:border-white/10 flex items-center justify-center group transition-all duration-300 hover:shadow-md" style="width: 44px; height: 44px; min-width: 44px; max-width: 44px;">
					<img src="<?php echo esc_url( MKIT_SI_PLUGIN_URL . 'assets/m8-smart-image-logo.png' ); ?>" alt="M8 Smart Image Logo" class="w-full h-full object-cover transform transition-transform duration-500 group-hover:scale-110" style="width: 44px; height: 44px; object-fit: cover; display: block;">
				</div>				<div>
					<h1 class="text-lg font-black tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-[#131118] to-[#131118]/70 dark:from-white dark:to-white/70">
                        <?php esc_html_e('Mak8it Smart Image', 'mak8it-smart-image'); ?>
                    </h1>
				</div>
			</div>

			<div class="flex items-center gap-3">
				<a href="<?php echo esc_url(admin_url('admin.php?page=mak8it-smart-image-system-info')); ?>"
					class="flex items-center justify-center rounded-xl h-10 w-10 bg-white dark:bg-white/5 border border-[#f2f0f4] dark:border-white/10 text-[#131118] dark:text-white hover:text-primary hover:border-primary/20 shadow-sm hover:shadow-md transition-all duration-300 group"
					title="<?php esc_attr_e('System Info', 'mak8it-smart-image'); ?>">
					<span class="material-symbols-outlined text-[20px] transform transition-transform duration-500 group-hover:rotate-12">info</span>
				</a>
				<a href="<?php echo esc_url(admin_url('admin.php?page=mak8it-smart-image-bulk')); ?>"
					class="bg-primary text-white text-sm font-bold h-10 px-6 rounded-xl shadow-lg shadow-primary/20 flex items-center">
					<?php esc_html_e('Optimize All', 'mak8it-smart-image'); ?>
				</a>
			</div>
		</div>
	</header>