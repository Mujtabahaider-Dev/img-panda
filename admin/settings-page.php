<?php
/**
 * Settings Page Template.
 *
 * @package Img_Panda
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Load required classes for stats
require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-stats.php';
require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-bulk-processor.php';

$stats = Img_Panda_Stats::get_stats();
$processor = new Img_Panda_Bulk_Processor();
$unconverted_count = $processor->get_unconverted_images_count();
$support = Img_Panda_Converter::check_webp_support();
$settings = get_option('Img_Panda_settings', array());

$total_images = (int) $stats['total_images'];
$converted_images = (int) $stats['converted_images'];
$optimization_pct = $total_images > 0 ? round(($converted_images / $total_images) * 100) : 0;
$space_saved = Img_Panda_Stats::get_formatted_space_saved();

require_once IMG_PANDA_PLUGIN_DIR . 'admin/header.php';
?>

<main class="max-w-[1200px] mx-auto w-full px-6 py-10 flex flex-col gap-10">
	<!-- Headline -->
	<div class="flex flex-col gap-1">
		<h2 class="text-3xl font-bold tracking-tight">
			<?php
			/* translators: %s: User display name */
			printf(esc_html__('Welcome back, %s', 'img-panda'), esc_html(wp_get_current_user()->display_name));
			?>
		</h2>
		<p class="opacity-60 text-base">
			<?php
			/* translators: %1$d: optimization percentage, %2$s: space saved */
			printf(esc_html__('Your library is %1$d%% optimized. You saved %2$s so far.', 'img-panda'), intval($optimization_pct), esc_html($space_saved));
			?>
		</p>
	</div>

	<!-- Stats Grid -->
	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
		<!-- Stat Card 1 -->
		<div
			class="glass-card rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
			<div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors">
				<span class="material-symbols-outlined text-[80px]">image</span>
			</div>
			<p class="text-sm font-medium opacity-60 mb-1 uppercase tracking-wider">
				<?php esc_html_e('Total Images', 'img-panda'); ?>
			</p>
			<h3 class="text-3xl font-bold mb-2"><?php echo esc_html(number_format($total_images)); ?></h3>
			<div class="flex items-center gap-1.5">
				<span class="material-symbols-outlined text-success text-sm">trending_up</span>
				<span class="text-success text-sm font-bold"><?php esc_html_e('Media Library', 'img-panda'); ?></span>
			</div>
		</div>
		<!-- Stat Card 2 -->
		<div
			class="glass-card rounded-2xl p-6 shadow-sm border-l-4 border-primary bg-primary/[0.02] relative overflow-hidden group">
			<div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors">
				<span class="material-symbols-outlined text-[80px]">check_circle</span>
			</div>
			<p class="text-sm font-medium opacity-60 mb-1 uppercase tracking-wider">
				<?php esc_html_e('Optimized', 'img-panda'); ?>
			</p>
			<h3 class="text-3xl font-bold mb-2"><?php echo esc_html(number_format($converted_images)); ?></h3>
			<div class="w-full bg-[#f2f0f4] dark:bg-white/10 h-1.5 rounded-full mt-3">
				<div class="bg-primary h-full rounded-full" style="width: <?php echo esc_attr($optimization_pct); ?>%">
				</div>
			</div>
		</div>
		<!-- Stat Card 3 -->
		<div class="glass-card rounded-2xl p-6 shadow-sm relative overflow-hidden group">
			<div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors">
				<span class="material-symbols-outlined text-[80px]">pending</span>
			</div>
			<p class="text-sm font-medium opacity-60 mb-1 uppercase tracking-wider">
				<?php esc_html_e('Remaining', 'img-panda'); ?>
			</p>
			<h3 class="text-3xl font-bold mb-2"><?php echo esc_html(number_format($unconverted_count)); ?></h3>
			<p class="text-sm opacity-50 font-medium"><?php esc_html_e('Ready for conversion', 'img-panda'); ?></p>
		</div>
		<!-- Stat Card 4 -->
		<div class="bg-primary rounded-2xl p-6 shadow-xl shadow-primary/20 text-white relative overflow-hidden">
			<div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4">
				<span class="material-symbols-outlined text-[120px]">database</span>
			</div>
			<p class="text-sm font-bold opacity-80 mb-1 uppercase tracking-wider">
				<?php esc_html_e('Disk Saved', 'img-panda'); ?>
			</p>
			<h3 style="color: #fff;" class="text-3xl font-bold mb-2"><?php echo esc_html($space_saved); ?></h3>
			<div
				class="inline-flex items-center bg-white/20 px-2 py-1 rounded text-[11px] font-bold uppercase tracking-widest">
				<?php esc_html_e('High Efficiency', 'img-panda'); ?>
			</div>
		</div>
	</div>

	<!-- Two Column Layout -->
	<form method="post" action="options.php" id="img-panda-main-form">
	<?php settings_fields('Img_Panda_settings_group'); ?>

	<div class="grid grid-cols-1 lg:grid-cols-3 gap-10 text-[#131118]">
		<!-- Left Column: Health & Recent -->
        <div class="lg:col-span-2 flex flex-col gap-10">
            <!-- Optimization Health (Chart style) -->
			<div class="glass-card rounded-3xl p-8 shadow-sm">
				<div class="flex justify-between items-start mb-8">
					<div>
						<h4 class="text-xl font-bold"><?php esc_html_e('Optimization Health', 'img-panda'); ?></h4>
						<p class="text-sm opacity-80"><?php esc_html_e('Storage usage overview', 'img-panda'); ?></p>
					</div>
					<a href="<?php echo esc_url(admin_url('admin.php?page=img-panda-bulk')); ?>"
						class="text-primary text-sm font-bold hover:underline"><?php esc_html_e('Run Optimizer', 'img-panda'); ?></a>
				</div>
				<div class="flex flex-col md:flex-row items-center gap-12">
					<div class="relative size-48">
						<svg class="size-full -rotate-90" viewbox="0 0 36 36">
							<circle class="stroke-[#f2f0f4] dark:stroke-white/5" cx="18" cy="18" fill="none" r="16"
								stroke-width="3"></circle>
							<circle class="stroke-primary" cx="18" cy="18" fill="none" r="16"
								stroke-dasharray="<?php echo esc_attr($optimization_pct); ?>, 100" stroke-width="3">
							</circle>
						</svg>
						<div class="absolute inset-0 flex flex-col items-center justify-center">
							<span class="text-3xl font-bold"><?php echo esc_html($optimization_pct); ?>%</span>
							<span
								class="text-[10px] font-bold opacity-80 uppercase tracking-widest"><?php esc_html_e('Optimized', 'img-panda'); ?></span>
						</div>
					</div>
					<div class="flex-1 grid grid-cols-1 gap-4 w-full">
						<div class="flex items-center justify-between p-4 bg-[#f2f0f4]/50 dark:bg-white/5 rounded-2xl">
							<div class="flex items-center gap-3">
								<div class="size-3 rounded-full bg-primary"></div>
								<span
									class="text-sm font-medium"><?php esc_html_e('Optimized Assets', 'img-panda'); ?></span>
							</div>
							<span class="font-bold"><?php echo esc_html(number_format($converted_images)); ?></span>
						</div>
						<div class="flex items-center justify-between p-4 bg-[#f2f0f4]/50 dark:bg-white/5 rounded-2xl">
							<div class="flex items-center gap-3">
								<div class="size-3 rounded-full bg-[#dfdbe6] dark:bg-white/20"></div>
								<span
									class="text-sm font-medium"><?php esc_html_e('Original Assets', 'img-panda'); ?></span>
							</div>
							<span class="font-bold"><?php echo esc_html(number_format($total_images)); ?></span>
						</div>
						<div
							class="flex items-center justify-between p-4 border border-dashed border-primary/20 rounded-2xl">
							<div class="flex items-center gap-3 text-primary">
								<span class="material-symbols-outlined text-sm">bolt</span>
								<span class="text-sm font-bold"><?php esc_html_e('Remaining', 'img-panda'); ?></span>
							</div>
							<span
								class="font-bold text-primary"><?php echo esc_html(number_format($unconverted_count)); ?></span>
						</div>
					</div>
				</div>
			</div>

			<!-- Recent Optimizations Table -->
			<div class="flex flex-col gap-4">
				<h4 class="text-xl font-bold px-2"><?php esc_html_e('Recent Optimizations', 'img-panda'); ?></h4>
				<div class="glass-card rounded-3xl overflow-hidden shadow-sm">
					<table class="w-full text-left border-collapse">
						<thead class="bg-[#f2f0f4]/30 dark:bg-white/5">
							<tr>
								<th class="p-4 text-xs font-bold uppercase tracking-widest opacity-40">
									<?php esc_html_e('Thumbnail', 'img-panda'); ?>
								</th>
								<th class="p-4 text-xs font-bold uppercase tracking-widest opacity-40">
									<?php esc_html_e('Filename', 'img-panda'); ?>
								</th>
								<th class="p-4 text-xs font-bold uppercase tracking-widest opacity-40">
									<?php esc_html_e('Savings', 'img-panda'); ?>
								</th>
								<th class="p-4 text-xs font-bold uppercase tracking-widest opacity-40">
									<?php esc_html_e('Status', 'img-panda'); ?>
								</th>
								<th class="p-4 text-xs font-bold uppercase tracking-widest opacity-40 text-right">
									<?php esc_html_e('Action', 'img-panda'); ?>
								</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-[#f2f0f4] dark:divide-white/5">
							<?php
							$recent_conversions = Img_Panda_Stats::get_recent_conversions(5);
							if (!empty($recent_conversions)):
								foreach ($recent_conversions as $conversion):
									$original_size = (int) $conversion['original_size'];
									$new_size = (int) $conversion['new_size'];

									// Fallback: If metadata is missing, calculate from actual files
									if ($original_size === 0 || $new_size === 0) {
										$file_path = get_attached_file($conversion['ID']);
										if ($file_path && file_exists($file_path)) {
											$webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);
											if (file_exists($webp_path)) {
												$original_size = filesize($file_path);
												$new_size = filesize($webp_path);
											}
										}
									}

									$savings_pct = $original_size > 0 ? round((1 - ($new_size / $original_size)) * 100) : 0;
									$thumb = wp_get_attachment_image_src($conversion['ID'], 'thumbnail');
									?>
									<tr class="hover:bg-primary/[0.02] transition-colors">
										<td class="p-4">
											<div class="size-12 rounded-lg bg-cover bg-center border border-[#f2f0f4] dark:border-white/10"
												style="background-image: url('<?php echo $thumb ? esc_url($thumb[0]) : ''; ?>');">
												<?php if (!$thumb): ?>
													<span
														class="material-symbols-outlined opacity-20 flex items-center justify-center h-full">image</span>
												<?php endif; ?>
											</div>
										</td>
										<td class="p-4">
											<p class="text-sm font-bold truncate max-w-[150px]">
												<?php echo esc_html($conversion['post_title']); ?>
											</p>
											<p class="text-[11px] opacity-40 uppercase">
												<?php echo esc_html(size_format($original_size)); ?> →
												<?php echo esc_html(size_format($new_size)); ?>
											</p>
										</td>
										<td class="p-4">
											<span
												class="inline-flex items-center px-2 py-0.5 rounded-full bg-success/10 text-success text-[11px] font-bold">-<?php echo esc_html($savings_pct); ?>%</span>
										</td>
										<td class="p-4">
											<div class="flex items-center gap-1.5 text-success">
												<span class="material-symbols-outlined text-[16px]">check_circle</span>
												<span
													class="text-xs font-bold"><?php esc_html_e('Optimized', 'img-panda'); ?></span>
											</div>
										</td>
										<td class="p-4 text-right">
											<a href="<?php echo esc_url(get_edit_post_link($conversion['ID'])); ?>"
												class="text-primary opacity-40 hover:opacity-100"><span
													class="material-symbols-outlined">open_in_new</span></a>
										</td>
									</tr>
								<?php endforeach;
							else: ?>
								<tr>
									<td colspan="5" class="p-10 text-center opacity-40 font-medium">
										<?php esc_html_e('No optimizations yet.', 'img-panda'); ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<div class="mt-8 text-center">
					<a href="<?php echo esc_url(admin_url('upload.php?mode=list')); ?>"
						class="text-primary font-bold hover:underline"><?php esc_html_e('View Media Library', 'img-panda'); ?></a>
				</div>
			</div>


		</div>

		<!-- Right Column: Configuration Panel -->
		<div class="flex flex-col gap-6">
			
		<!-- AI SEO Suite Card — Standardized UI (No Banner, No Shadow) -->
		<div class="rounded-3xl p-8 mb-6" style="background:#fff;border:1px solid #f2f0f4;">

			<!-- Simple Header -->
			<div class="flex items-center justify-between mb-8">
				<div class="flex items-center gap-3">
					<h4 class="text-xl font-bold" style="margin:0;color:#131118;"><?php esc_html_e('AI Vision Engine', 'img-panda'); ?></h4>
				</div>
				<!-- Badge -->
				<span style="background:#faf8fc;border:1px solid #f2f0f4;border-radius:6px;padding:4px 10px;font-size:9px;font-weight:800;letter-spacing:0.05em;text-transform:uppercase;color:#7c3bed;">
					<?php esc_html_e('Included Free', 'img-panda'); ?>
				</span>
			</div>

			<!-- Card Body (No nested form tag) -->
			<div id="img-panda-ai-container" style="display:flex;flex-direction:column;gap:20px;">

				<!-- Auto Alt Toggle Row -->
				<div class="flex items-center justify-between" style="background:#faf8fc;border:1px solid #f2f0f4;border-radius:14px;padding:14px 18px;">
					<div>
						<p style="font-size:14px;font-weight:700;color:#131118;margin:0 0 3px;"><?php esc_html_e('Generate Alt Text', 'img-panda'); ?></p>
						<p style="font-size:11px;color:#64748b;margin:0;font-weight:500;"><?php esc_html_e('Auto-writes SEO alt tags using Vision AI', 'img-panda'); ?></p>
					</div>
					<label class="relative inline-flex items-center cursor-pointer">
						<input type="hidden" name="Img_Panda_settings[auto_alt]" value="0">
						<input type="checkbox" name="Img_Panda_settings[auto_alt]" value="1" <?php checked(isset($settings['auto_alt']) ? $settings['auto_alt'] : '0', '1'); ?> class="sr-only peer">
						<div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#7c3bed]"></div>
					</label>
				</div>

				<!-- Provider & Model Row -->
				<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
					<div>
						<p style="font-size:10px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;margin:0 0 8px 2px;"><?php esc_html_e('AI Provider', 'img-panda'); ?></p>
						<div style="position:relative;">
							<select name="Img_Panda_settings[ai_provider]" id="ai-provider-select" style="width:100%;background:#fff;border:1.5px solid #f2f0f4;color:#131118;padding:12px 36px 12px 14px;border-radius:12px;font-size:13px;font-weight:600;appearance:none;outline:none;cursor:pointer;transition:border-color 0.2s;">
								<option value="gemini" <?php selected(isset($settings['ai_provider']) ? $settings['ai_provider'] : 'gemini', 'gemini'); ?>>Google Gemini</option>
								<option value="openai" <?php selected(isset($settings['ai_provider']) ? $settings['ai_provider'] : 'gemini', 'openai'); ?>>OpenAI</option>
							</select>
							<span class="material-symbols-outlined" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:16px;color:#94a3b8;pointer-events:none;">expand_more</span>
						</div>
					</div>
					<div>
						<p style="font-size:10px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;margin:0 0 8px 2px;"><?php esc_html_e('Neural Model', 'img-panda'); ?></p>
						<div style="position:relative;">
							<input type="text" name="Img_Panda_settings[ai_model]" id="ai-model-input" list="ai-model-suggestions" value="<?php echo esc_attr(isset($settings['ai_model']) ? $settings['ai_model'] : 'gemini-1.5-flash'); ?>" style="width:100%;background:#fff;border:1.5px solid #f2f0f4;color:#131118;padding:12px 14px;border-radius:12px;font-size:13px;font-weight:600;outline:none;box-sizing:border-box;transition:border-color 0.2s;" placeholder="<?php esc_attr_e('Enter model name...', 'img-panda'); ?>">
							<datalist id="ai-model-suggestions">
								<!-- Populated by JS -->
							</datalist>
						</div>
					</div>
				</div>

				<!-- API Key Field -->
				<div>
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;padding:0 2px;">
						<p style="font-size:10px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;margin:0;"><?php esc_html_e('Secret API Key', 'img-panda'); ?></p>
						<a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" style="font-size:10px;font-weight:700;color:#7c3bed;text-decoration:none;letter-spacing:0.05em;"><?php esc_html_e('Get Free Key ↗', 'img-panda'); ?></a>
					</div>
					<input type="password" name="Img_Panda_settings[ai_api_key]" value="<?php echo esc_attr(isset($settings['ai_api_key']) ? $settings['ai_api_key'] : ''); ?>" style="width:100%;background:#faf8fc;border:1.5px solid #f2f0f4;color:#131118;padding:14px 16px;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color 0.2s;" placeholder="<?php esc_attr_e('Paste your API key here...', 'img-panda'); ?>">
				</div>

				<!-- API Endpoint URL Field -->
				<div>
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;padding:0 2px;">
						<p style="font-size:10px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;margin:0;"><?php esc_html_e('Custom API URL (Optional)', 'img-panda'); ?></p>
					</div>
					<input type="text" name="Img_Panda_settings[ai_api_url]" value="<?php echo esc_attr(isset($settings['ai_api_url']) ? $settings['ai_api_url'] : ''); ?>" style="width:100%;background:#faf8fc;border:1.5px solid #f2f0f4;color:#131118;padding:14px 16px;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color 0.2s;" placeholder="<?php esc_attr_e('Leave empty for default API URL...', 'img-panda'); ?>">
				</div>

				<!-- Action Buttons -->
				<div class="grid grid-cols-2 gap-4 mt-2">
					<button type="button" id="btn-test-ai" class="w-full bg-[#f2f0f4] dark:bg-white/5 hover:bg-primary hover:text-white py-3 rounded-xl text-sm font-bold transition-all border border-transparent hover:border-primary/20 flex items-center justify-center gap-2">
						<span class="material-symbols-outlined" style="font-size:18px;">leak_add</span>
						<?php esc_html_e('Test API', 'img-panda'); ?>
					</button>
					<button type="submit" class="w-full bg-primary text-white hover:bg-[#f2f0f4] hover:text-[#131118] py-3 rounded-xl text-sm font-bold transition-all border border-transparent">
						<?php esc_html_e('Save AI Engine', 'img-panda'); ?>
					</button>
				</div>

			</div>
		</div>

			<div class="glass-card rounded-3xl p-6 shadow-sm">
				<div class="flex items-center gap-3 mb-4">
					
					<h4 class="text-xl font-bold"><?php esc_html_e('Compression Settings', 'img-panda'); ?></h4>
				</div>

				<div class="flex flex-col gap-4">
						<?php
						$auto_convert = isset($settings['auto_convert']) ? $settings['auto_convert'] : '1';
						$quality = isset($settings['quality']) ? intval($settings['quality']) : 60;
						$replace_mode = isset($settings['replace_original']) ? $settings['replace_original'] : 'keep_both';
						?>

						<!-- Toggle -->
						<div class="flex items-center justify-between">
							<div>
								<p class="text-sm font-bold"><?php esc_html_e('Auto Convert', 'img-panda'); ?></p>
								<p class="text-xs opacity-70"><?php esc_html_e('Recommended for speed', 'img-panda'); ?>
								</p>
							</div>
							<label class="relative inline-flex items-center cursor-pointer">
								<input type="hidden" name="Img_Panda_settings[auto_convert]" value="0">
								<input type="checkbox" name="Img_Panda_settings[auto_convert]" value="1" <?php checked($auto_convert, '1'); ?> class="sr-only peer">
								<div
									class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary">
								</div>
							</label>
						</div>

						<hr class="border-[#f2f0f4] dark:border-white/5" />

						<!-- Quality Slider -->
						<div class="flex flex-col gap-4">
							<div class="flex justify-between items-center">
								<p class="text-sm font-bold"><?php esc_html_e('Compression Level', 'img-panda'); ?></p>
								<span class="text-sm font-bold text-primary"
									id="quality-val-display"><?php echo esc_html($quality); ?>%</span>
							</div>
							<input type="range" name="Img_Panda_settings[quality]" min="10" max="100"
								value="<?php echo esc_attr($quality); ?>" step="5"
								class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary"
								oninput="document.getElementById('quality-val-display').innerText = this.value + '%'">
							<div class="flex justify-between text-[10px] opacity-70 font-bold uppercase tracking-widest">
								<span><?php esc_html_e('Lossless', 'img-panda'); ?></span>
								<span><?php esc_html_e('Balanced', 'img-panda'); ?></span>
								<span><?php esc_html_e('Max', 'img-panda'); ?></span>
							</div>
						</div>

						<hr class="border-[#f2f0f4] dark:border-white/5" />

						<!-- Radio Buttons -->
						<div class="flex flex-col gap-4">
							<p class="text-sm font-bold"><?php esc_html_e('Replacement Strategy', 'img-panda'); ?></p>
							<div class="space-y-3">
								<label class="flex items-center gap-3 cursor-pointer group">
									<input type="radio" name="Img_Panda_settings[replace_original]" value="replace"
										<?php checked($replace_mode, 'replace'); ?>
										class="w-4 h-4 text-primary bg-gray-100 border-gray-300 focus:ring-primary focus:ring-2">
									<span
										class="text-sm font-medium opacity-80 group-hover:opacity-100 transition-opacity"><?php esc_html_e('Overwrite original files', 'img-panda'); ?></span>
								</label>
								<label class="flex items-center gap-3 cursor-pointer group">
									<input type="radio" name="Img_Panda_settings[replace_original]"
										value="keep_both" <?php checked($replace_mode, 'keep_both'); ?>
										class="w-4 h-4 text-primary bg-gray-100 border-gray-300 focus:ring-primary focus:ring-2">
									<span
										class="text-sm font-medium opacity-80 group-hover:opacity-100 transition-opacity"><?php esc_html_e('Keep backup of originals', 'img-panda'); ?></span>
								</label>
							</div>
						</div>

						<hr class="border-[#f2f0f4] dark:border-white/5" />

						<!-- Collapsible Advanced Settings -->
						<div class="pt-2">
							<button type="button" 
								onclick="const el = document.getElementById('advanced-settings-area'); el.classList.toggle('hidden'); this.querySelector('.chevron').classList.toggle('rotate-180');" 
								class="flex items-center justify-between w-full text-[11px] font-bold uppercase tracking-widest text-primary/60 hover:text-primary transition-all group">
								<div class="flex items-center gap-2">
									<span class="material-symbols-outlined text-[16px]">tune</span>
									<?php esc_html_e('Advanced Configuration', 'img-panda'); ?>
								</div>
								<span class="material-symbols-outlined text-[18px] transition-transform duration-300 chevron">expand_more</span>
							</button>

							<div id="advanced-settings-area" class="hidden pt-4">
								<div class="grid grid-cols-2 gap-4">
									<div>
										<label class="text-[10px] font-extrabold uppercase tracking-tight opacity-40 mb-2 block"><?php esc_html_e('Min Size (KB)', 'img-panda'); ?></label>
										<input type="number" name="Img_Panda_settings[min_size]" value="<?php echo esc_attr(isset($settings['min_size']) ? $settings['min_size'] : '0'); ?>" min="0" class="w-full bg-[#f2f0f4]/50 dark:bg-white/5 border-none rounded-xl p-3 text-sm font-bold focus:ring-1 focus:ring-primary">
									</div>
									<div>
										<label class="text-[10px] font-extrabold uppercase tracking-tight opacity-40 mb-2 block"><?php esc_html_e('Max Size (MB)', 'img-panda'); ?></label>
										<input type="number" name="Img_Panda_settings[max_size]" value="<?php echo esc_attr(isset($settings['max_size']) ? $settings['max_size'] : '10'); ?>" min="0" class="w-full bg-[#f2f0f4]/50 dark:bg-white/5 border-none rounded-xl p-3 text-sm font-bold focus:ring-1 focus:ring-primary">
									</div>
								</div>
								<p class="text-[10px] opacity-40 mt-3 font-medium italic">
									<?php esc_html_e('* Use 0 to disable minimum or maximum file size checks.', 'img-panda'); ?>
								</p>
							</div>
						</div>

						<!-- Save Button -->
						<button type="submit"
							class="w-full bg-[#f2f0f4] dark:bg-white/5 hover:bg-primary hover:text-white py-3 rounded-xl text-sm font-bold transition-all mt-4 border border-transparent hover:border-primary/20">
							<?php esc_html_e('Save Configuration', 'img-panda'); ?>
						</button>
					</div>
				</div>

				<!-- Tab: AI SEO (removed - now unified in the AI Vision Engine card above) -->
			</div>

		</div>
	</form>
</main>

<footer class="mt-auto border-t border-[#f2f0f4] dark:border-white/5 py-8 text-center px-6">
	<div class="max-w-[1200px] mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
		<p class="text-sm opacity-40 font-medium">
			<?php
			/* translators: %s: Current year */
			printf(esc_html__('© %s Img Panda. All rights reserved.', 'img-panda'), esc_html(gmdate('Y')));
			?>
		</p>
		<p class="text-sm opacity-40 font-medium">
			<?php esc_html_e('Made with', 'img-panda'); ?> <span class="text-red-500">♥</span>
			<?php esc_html_e('by', 'img-panda'); ?>
			<a href="https://mak8it.com" target="_blank" rel="noopener noreferrer"
				class="text-primary hover:opacity-100 font-bold">Mak8it.com</a>
		</p>
	</div>
</footer>
</div>