<?php
/**
 * Bulk Converter Page Template.
 *
 * @package Mkit_Si
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'mak8it-smart-image'));
}

// Load required classes
require_once MKIT_SI_PLUGIN_DIR . 'includes/class-bulk-processor.php';
require_once MKIT_SI_PLUGIN_DIR . 'includes/class-stats.php';

$processor = new Mkit_Si_Bulk_Processor();
$stats = Mkit_Si_Stats::get_stats();

// Get unconverted images count
$unconverted_count = $processor->get_unconverted_images_count();

$total_images = (int) $stats['total'];
$converted_images = (int) $stats['converted'];
$optimization_pct = $total_images > 0 ? round(($converted_images / $total_images) * 100) : 0;
$space_saved = Mkit_Si_Stats::get_formatted_space_saved();

require_once MKIT_SI_PLUGIN_DIR . 'admin/header.php';
?>

<main class="max-w-[1200px] mx-auto w-full px-6 py-10 flex flex-col gap-10">
	<!-- Headline -->
	<div class="flex flex-col gap-1">
		<h2 class="text-3xl font-bold tracking-tight"><?php esc_html_e('Bulk Optimizer', 'mak8it-smart-image'); ?></h2>
		<p class="opacity-60 text-base">
			<?php
			/* translators: %d: Number of images */
			printf(esc_html__('Found %d images ready for optimization.', 'mak8it-smart-image'), intval($unconverted_count));
			?>
		</p>
	</div>

	<!-- Stats Grid -->
	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
		<div class="glass-card rounded-2xl p-6 shadow-sm relative overflow-hidden group">
			<div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors">
				<span class="material-symbols-outlined text-[80px]">image</span>
			</div>
			<p class="text-sm font-medium opacity-60 mb-1 uppercase tracking-wider">
				<?php esc_html_e('Total Images', 'mak8it-smart-image'); ?>
			</p>
			<h3 class="text-3xl font-bold mb-2"><?php echo esc_html(number_format($total_images)); ?></h3>
		</div>
		<div class="glass-card rounded-2xl p-6 shadow-sm border-l-4 border-primary relative overflow-hidden group">
			<div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors">
				<span class="material-symbols-outlined text-[80px]">check_circle</span>
			</div>
			<p class="text-sm font-medium opacity-60 mb-1 uppercase tracking-wider">
				<?php esc_html_e('Optimized', 'mak8it-smart-image'); ?>
			</p>
			<h3 class="text-3xl font-bold mb-2"><?php echo esc_html(number_format($converted_images)); ?></h3>
		</div>
		<div class="glass-card rounded-2xl p-6 shadow-sm relative overflow-hidden group">
			<div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors">
				<span class="material-symbols-outlined text-[80px]">pending</span>
			</div>
			<p class="text-sm font-medium opacity-60 mb-1 uppercase tracking-wider">
				<?php esc_html_e('Remaining', 'mak8it-smart-image'); ?>
			</p>
			<h3 class="text-3xl font-bold mb-2"><?php echo esc_html(number_format($unconverted_count)); ?></h3>
		</div>
		
		<div class="bg-primary rounded-2xl p-6 shadow-xl shadow-primary/20 text-white relative overflow-hidden">
			<div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4">
				<span class="material-symbols-outlined text-[120px]">database</span>
			</div>
			<p class="text-sm font-bold opacity-80 mb-1 uppercase tracking-wider">
				<?php esc_html_e('Disk Saved', 'mak8it-smart-image'); ?>
			</p>
			<h3 style="color: #fff;" class="text-3xl font-bold mb-2"><?php echo esc_html($space_saved); ?></h3>
			<div
				class="inline-flex items-center bg-white/20 px-2 py-1 rounded text-[11px] font-bold uppercase tracking-widest">
				<?php esc_html_e('High Efficiency', 'mak8it-smart-image'); ?>
			</div>
		</div>
		</div>
	<div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
		<!-- Left Column: Bulk Controls -->
		<div class="lg:col-span-2 flex flex-col gap-10">
			<div class="glass-card rounded-3xl p-8 shadow-sm">
				<div class="flex items-center gap-3 mb-8">
					<span class="material-symbols-outlined text-primary">bolt</span>
					<h4 class="text-xl font-bold"><?php esc_html_e('Optimization Engine', 'mak8it-smart-image'); ?></h4>
				</div>

				<?php if ($unconverted_count > 0): ?>
					<!-- Progress Section -->
					<div id="conversion-progress" class="mb-10" style="display: none;">
						<div class="flex justify-between items-center mb-4">
							<span class="text-sm font-bold"
								id="conversion-status"><?php esc_html_e('Processing...', 'mak8it-smart-image'); ?></span>
							<span class="text-sm font-bold text-primary" id="progress-percentage">0%</span>
						</div>
						<div class="w-full bg-[#f2f0f4] dark:bg-white/10 h-3 rounded-full overflow-hidden">
							<div id="progress-bar-fill" class="bg-primary h-full rounded-full transition-all duration-300"
								style="width: 0%"></div>
						</div>
						<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
							<div class="bg-[#f2f0f4]/50 dark:bg-white/5 p-4 rounded-2xl text-center">
								<p class="text-[10px] font-bold opacity-50 uppercase tracking-widest mb-1">
									<?php esc_html_e('Processed', 'mak8it-smart-image'); ?>
								</p>
								<p class="text-lg font-bold"><span id="stat-processed">0</span> / <span
										id="stat-total">0</span></p>
							</div>
							<div class="bg-[#f2f0f4]/50 dark:bg-white/5 p-4 rounded-2xl text-center">
								<p class="text-[10px] font-bold opacity-50 uppercase tracking-widest mb-1">
									<?php esc_html_e('Successful', 'mak8it-smart-image'); ?>
								</p>
								<p class="text-lg font-bold text-success" id="stat-successful">0</p>
							</div>
							<div class="bg-[#f2f0f4]/50 dark:bg-white/5 p-4 rounded-2xl text-center">
								<p class="text-[10px] font-bold opacity-50 uppercase tracking-widest mb-1">
									<?php esc_html_e('Failed', 'mak8it-smart-image'); ?>
								</p>
								<p class="text-lg font-bold text-red-500" id="stat-failed">0</p>
							</div>
							<div class="bg-[#f2f0f4]/50 dark:bg-white/5 p-4 rounded-2xl text-center">
								<p class="text-[10px] font-bold opacity-50 uppercase tracking-widest mb-1">
									<?php esc_html_e('Skipped', 'mak8it-smart-image'); ?>
								</p>
								<p class="text-lg font-bold opacity-40" id="stat-skipped">0</p>
							</div>
						</div>
						<div class="mt-4 text-center">
							<p class="text-xs opacity-40 font-bold uppercase tracking-widest">
								<?php esc_html_e('Estimated Time Remaining:', 'mak8it-smart-image'); ?> <span
									id="stat-estimated-time" class="text-primary">--</span>
							</p>
						</div>
					</div>

					<!-- Deep Scan Notice -->
					<div class="bg-primary/5 border border-primary/10 rounded-2xl p-6 mb-8 flex items-start gap-4">
						<div class="size-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center shrink-0">
							<span class="material-symbols-outlined">analytics</span>
						</div>
						<div>
							<h5 class="text-sm font-bold text-primary mb-1"><?php esc_html_e('Intelligent Deep Scan Active', 'mak8it-smart-image'); ?></h5>
							<p class="text-xs opacity-70 leading-relaxed">
								<?php esc_html_e('Our engine is performing a cross-audit. Any image missing either its WebP version or its SEO Alt-Text is currently targeted in the count above.', 'mak8it-smart-image'); ?>
							</p>
						</div>
					</div>

					<div class="flex flex-wrap gap-4">
						<button id="btn-start-conversion"
							class="bg-primary hover:bg-primary/90 text-white font-bold py-4 px-8 rounded-2xl transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
							<span class="material-symbols-outlined">play_arrow</span>
							<?php esc_html_e('Start Optimization', 'mak8it-smart-image'); ?>
						</button>

						<button id="btn-pause-conversion"
							class="bg-[#f2f0f4] dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 font-bold py-4 px-8 rounded-2xl transition-all flex items-center gap-2"
							style="display: none;">
							<span class="material-symbols-outlined">pause</span>
							<?php esc_html_e('Pause', 'mak8it-smart-image'); ?>
						</button>

						<button id="btn-resume-conversion"
							class="bg-primary hover:bg-primary/90 text-white font-bold py-4 px-8 rounded-2xl transition-all shadow-lg shadow-primary/20 flex items-center gap-2"
							style="display: none;">
							<span class="material-symbols-outlined">play_arrow</span>
							<?php esc_html_e('Resume', 'mak8it-smart-image'); ?>
						</button>

						<button id="btn-stop-conversion"
							class="text-red-500 font-bold py-4 px-8 rounded-2xl hover:bg-red-50 transition-all flex items-center gap-2"
							style="display: none;">
							<span class="material-symbols-outlined">stop</span>
							<?php esc_html_e('Stop', 'mak8it-smart-image'); ?>
						</button>
					</div>

				<?php else: ?>
					<div class="text-center py-10">
						<div
							class="size-20 bg-success/10 text-success rounded-full flex items-center justify-center mx-auto mb-6">
							<span class="material-symbols-outlined text-4xl">check_circle</span>
						</div>
						<h4 class="text-xl font-bold mb-2"><?php esc_html_e('Everything is Optimized!', 'mak8it-smart-image'); ?>
						</h4>
						<p class="opacity-60 mb-8">
							<?php esc_html_e('All images in your media library are already in WebP format.', 'mak8it-smart-image'); ?>
						</p>
						<a href="<?php echo esc_url(admin_url('upload.php')); ?>"
							class="text-primary font-bold hover:underline"><?php esc_html_e('View Media Library', 'mak8it-smart-image'); ?></a>
					</div>
				<?php endif; ?>
			</div>

			<!-- Advanced Options -->
			<div class="glass-card rounded-3xl p-8 shadow-sm">
				<div class="flex items-center gap-3 mb-8">
					<span class="material-symbols-outlined text-primary">filter_list</span>
					<h4 class="text-xl font-bold"><?php esc_html_e('Advanced Options', 'mak8it-smart-image'); ?></h4>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
					<!-- Format Selection -->
					<div class="flex flex-col gap-3">
						<label class="text-sm font-bold opacity-60"><?php esc_html_e('Image Format', 'mak8it-smart-image'); ?></label>
						<select id="filter-format" class="bg-[#f2f0f4] dark:bg-white/5 border-none rounded-xl p-4 font-medium focus:ring-2 focus:ring-primary">
							<option value="all"><?php esc_html_e('All (Recommended)', 'mak8it-smart-image'); ?></option>
							<option value="jpeg"><?php esc_html_e('JPEG Only', 'mak8it-smart-image'); ?></option>
							<option value="png"><?php esc_html_e('PNG Only', 'mak8it-smart-image'); ?></option>
						</select>
					</div>

					<!-- Size Selection -->
					<div class="flex flex-col gap-3">
						<label class="text-sm font-bold opacity-60"><?php esc_html_e('Optimization Depth', 'mak8it-smart-image'); ?></label>
						<select id="filter-size" class="bg-[#f2f0f4] dark:bg-white/5 border-none rounded-xl p-4 font-medium focus:ring-2 focus:ring-primary">
							<option value="all"><?php esc_html_e('All Sizes (Best Performance)', 'mak8it-smart-image'); ?></option>
							<option value="full"><?php esc_html_e('Full Size Only (Faster Setup)', 'mak8it-smart-image'); ?></option>
						</select>
                        <p class="text-[10px] opacity-40 mt-1"><?php esc_html_e('Recommended for maximum site speed scores.', 'mak8it-smart-image'); ?></p>
					</div>

					<!-- Min Size Filter -->
					<div class="flex flex-col gap-3">
						<label class="text-sm font-bold opacity-60"><?php esc_html_e('Min File Size (KB)', 'mak8it-smart-image'); ?></label>
						<div class="relative">
							<input type="number" id="filter-min-size" value="0" min="0" class="bg-[#f2f0f4] dark:bg-white/5 border-none rounded-xl p-4 w-full font-bold focus:ring-2 focus:ring-primary" placeholder="0">
						</div>
                        <p class="text-[10px] opacity-40 mt-1"><?php esc_html_e('Target only heavy images (e.g. 1024 for 1MB).', 'mak8it-smart-image'); ?></p>
					</div>
				</div>

				<!-- AI Options Toggle -->
				<div class="mt-8 pt-8 border-t border-[#f2f0f4] dark:border-white/5">
					<div class="flex items-center justify-between bg-primary/[0.03] dark:bg-white/[0.02] p-6 rounded-2xl border border-primary/10">
						<div class="flex items-center gap-4">
							<div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
								<span class="material-symbols-outlined">psychology</span>
							</div>
							<div>
								<p class="font-bold text-sm"><?php esc_html_e('Generate AI Alt-Text', 'mak8it-smart-image'); ?></p>
								<p class="text-xs opacity-60"><?php esc_html_e('Analyze images and write missing SEO descriptions.', 'mak8it-smart-image'); ?></p>
							</div>
						</div>
						<label class="relative inline-flex items-center cursor-pointer">
							<input type="checkbox" id="bulk-ai-alt" value="1" class="sr-only peer">
							<div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
						</label>
					</div>
				</div>
			</div>
		</div>

		<!-- Right Column: Info -->
		<div class="flex flex-col gap-6">
			<div class="glass-card rounded-3xl p-8 shadow-sm">
				<h4 class="text-lg font-bold mb-6"><?php esc_html_e('Pro Tips', 'mak8it-smart-image'); ?></h4>
				<ul class="space-y-4">
					<li class="flex items-start gap-3">
						<span class="material-symbols-outlined text-primary text-sm mt-1">lightbulb</span>
						<p class="text-sm opacity-70">
							<?php esc_html_e('Convert in the background while you work.', 'mak8it-smart-image'); ?>
						</p>
					</li>
					<li class="flex items-start gap-3">
						<span class="material-symbols-outlined text-primary text-sm mt-1">lightbulb</span>
						<p class="text-sm opacity-70">
							<?php esc_html_e('Keeps original files safe in backup folder.', 'mak8it-smart-image'); ?>
						</p>
					</li>
					<li class="flex items-start gap-3">
						<span class="material-symbols-outlined text-primary text-sm mt-1">lightbulb</span>
						<p class="text-sm opacity-70">
							<?php esc_html_e('Improves Google PageSpeed scores significantly.', 'mak8it-smart-image'); ?>
						</p>
					</li>
				</ul>
			</div>

			<div class="glass-card rounded-3xl p-8 shadow-sm bg-primary/5 border-primary/10">
				<h4 class="text-lg font-bold mb-4"><?php esc_html_e('How it works', 'mak8it-smart-image'); ?></h4>
				<ol class="space-y-4 list-decimal list-inside text-sm opacity-70">
					<li><?php esc_html_e('Scans library for JPEGs and PNGs.', 'mak8it-smart-image'); ?></li>
					<li><?php esc_html_e('Creates WebP versions of each size.', 'mak8it-smart-image'); ?></li>
					<li><?php esc_html_e('Serves smaller WebP files automatically.', 'mak8it-smart-image'); ?></li>
				</ol>
			</div>
		</div>
	</div>
</main>

<footer class="mt-auto border-t border-[#f2f0f4] dark:border-white/5 py-8 text-center px-6">
	<div class="max-w-[1200px] mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
		<p class="text-sm opacity-40 font-medium">
			<?php
			/* translators: %s: Current year */
			printf(esc_html__('© %s Mak8it Smart Image. All rights reserved.', 'mak8it-smart-image'), esc_html(gmdate('Y')));
			?>
		</p>
		<p class="text-sm opacity-40 font-medium">
			<?php esc_html_e('Made with', 'mak8it-smart-image'); ?> <span class="text-red-500">♥</span>
			<?php esc_html_e('by', 'mak8it-smart-image'); ?>
			<a href="https://mak8it.com" target="_blank" rel="noopener noreferrer"
				class="text-primary hover:opacity-100 font-bold">Mak8it.com</a>
		</p>
	</div>
</footer>
</div>