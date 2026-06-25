<?php
/**
 * System Information Page.
 *
 * @package Mkit_Si
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Get detailed WebP support information
 */
function mkit_si_get_detailed_info()
{
	$info = array();
	$info['php_version'] = PHP_VERSION;
	$info['gd_loaded'] = extension_loaded('gd');
	if ($info['gd_loaded'] && function_exists('gd_info')) {
		$gd_info = gd_info();
		$info['gd_version'] = isset($gd_info['GD Version']) ? $gd_info['GD Version'] : 'Unknown';
		$info['gd_webp'] = isset($gd_info['WebP Support']) ? $gd_info['WebP Support'] : false;
		$info['gd_jpeg'] = isset($gd_info['JPEG Support']) ? $gd_info['JPEG Support'] : false;
		$info['gd_png'] = isset($gd_info['PNG Support']) ? $gd_info['PNG Support'] : false;
	}
	$info['imagewebp_exists'] = function_exists('imagewebp');
	$info['imagick_loaded'] = extension_loaded('imagick');
	$info['imagick_class'] = class_exists('Imagick');
	if ($info['imagick_loaded'] && $info['imagick_class']) {
		$imagick = new Imagick();
		$formats = $imagick->queryFormats();
		$info['imagick_webp'] = in_array('WEBP', $formats, true);
		$imagick->clear();
	}
	return $info;
}

$detailed_info = mkit_si_get_detailed_info();
$support = Mkit_Si_Converter::check_webp_support();
$has_webp = $support['supported'];

require_once MKIT_SI_PLUGIN_DIR . 'admin/header.php';
?>

<main class="max-w-[1200px] mx-auto w-full px-6 py-10 flex flex-col gap-10">
	<!-- Headline -->
	<div class="flex flex-col gap-1">
		<h2 class="text-3xl font-bold tracking-tight"><?php esc_html_e('System Status', 'mak8it-smart-image'); ?></h2>
		<p class="opacity-60 text-base">
			<?php esc_html_e('Check your server environment for WebP support.', 'mak8it-smart-image'); ?>
		</p>
	</div>

	<!-- Status Summary Row -->
	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
		<div
			class="glass-card rounded-2xl p-6 shadow-sm border-l-4 relative overflow-hidden group <?php echo esc_attr($has_webp ? 'border-success bg-success/5' : 'border-red-500 bg-red-50'); ?>">
			<div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors">
				<span
					class="material-symbols-outlined text-[80px]"><?php echo esc_html($has_webp ? 'verified' : 'warning'); ?></span>
			</div>
			<p class="text-sm font-medium opacity-60 mb-1 uppercase tracking-wider">
				<?php esc_html_e('Engine Status', 'mak8it-smart-image'); ?>
			</p>
			<h3 class="text-2xl font-bold mb-2">
				<?php echo $has_webp ? esc_html__('Ready', 'mak8it-smart-image') : esc_html__('Action Needed', 'mak8it-smart-image'); ?>
			</h3>
		</div>

		<div class="glass-card rounded-2xl p-6 shadow-sm relative overflow-hidden group">
			<div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors">
				<span class="material-symbols-outlined text-[80px]">memory</span>
			</div>
			<p class="text-sm font-medium opacity-60 mb-1 uppercase tracking-wider">
				<?php esc_html_e('GD Library', 'mak8it-smart-image'); ?>
			</p>
			<h3 class="text-2xl font-bold mb-2">
				<?php echo $detailed_info['gd_loaded'] ? esc_html__('Enabled', 'mak8it-smart-image') : esc_html__('Missing', 'mak8it-smart-image'); ?>
			</h3>
		</div>

		<div class="glass-card rounded-2xl p-6 shadow-sm relative overflow-hidden group">
			<div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors">
				<span class="material-symbols-outlined text-[80px]">auto_awesome</span>
			</div>
			<p class="text-sm font-medium opacity-60 mb-1 uppercase tracking-wider">
				<?php esc_html_e('Imagick', 'mak8it-smart-image'); ?>
			</p>
			<h3 class="text-2xl font-bold mb-2">
				<?php echo $detailed_info['imagick_loaded'] ? esc_html__('Active', 'mak8it-smart-image') : esc_html__('Optional', 'mak8it-smart-image'); ?>
			</h3>
		</div>

		<div class="glass-card rounded-2xl p-6 shadow-sm relative overflow-hidden group">
			<div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors">
				<span class="material-symbols-outlined text-[80px]">code</span>
			</div>
			<p class="text-sm font-medium opacity-60 mb-1 uppercase tracking-wider">
				<?php esc_html_e('PHP Version', 'mak8it-smart-image'); ?>
			</p>
			<h3 class="text-2xl font-bold mb-2"><?php echo esc_html($detailed_info['php_version']); ?></h3>
		</div>
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
		<!-- Left Col: Technical Details -->
		<div class="lg:col-span-2 flex flex-col gap-10">
			<div class="glass-card rounded-3xl p-8 shadow-sm">
				<div class="flex items-center gap-3 mb-8">
					<span class="material-symbols-outlined text-primary">terminal</span>
					<h4 class="text-xl font-bold"><?php esc_html_e('Server Environment', 'mak8it-smart-image'); ?></h4>
				</div>

				<div class="overflow-hidden rounded-2xl border border-[#f2f0f4] dark:border-white/5">
					<table class="w-full text-left border-collapse">
						<thead class="bg-[#f2f0f4]/30 dark:bg-white/5">
							<tr>
								<th class="p-4 text-xs font-bold uppercase tracking-widest opacity-40">
									<?php esc_html_e('Component', 'mak8it-smart-image'); ?>
								</th>
								<th class="p-4 text-xs font-bold uppercase tracking-widest opacity-40">
									<?php esc_html_e('Status', 'mak8it-smart-image'); ?>
								</th>
								<th class="p-4 text-xs font-bold uppercase tracking-widest opacity-40">
									<?php esc_html_e('WebP Support', 'mak8it-smart-image'); ?>
								</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-[#f2f0f4] dark:divide-white/5">
							<tr>
								<td class="p-4 font-bold">GD Library</td>
								<td class="p-4">
									<?php echo wp_kses_post($detailed_info['gd_loaded'] ? '<span class="text-success font-bold">✓ Enabled</span>' : '<span class="text-red-500 font-bold">✗ Missing</span>'); ?>
								</td>
								<td class="p-4">
									<?php echo wp_kses_post((isset($detailed_info['gd_webp']) && $detailed_info['gd_webp']) ? '<span class="text-success font-bold">✓ Supported</span>' : '<span class="opacity-40">✗ No Support</span>'); ?>
								</td>
							</tr>
							<tr>
								<td class="p-4 font-bold">Imagick</td>
								<td class="p-4">
									<?php echo wp_kses_post($detailed_info['imagick_loaded'] ? '<span class="text-success font-bold">✓ Enabled</span>' : '<span class="opacity-40">✗ Not Installed</span>'); ?>
								</td>
								<td class="p-4">
									<?php echo wp_kses_post((isset($detailed_info['imagick_webp']) && $detailed_info['imagick_webp']) ? '<span class="text-success font-bold">✓ Supported</span>' : '<span class="opacity-40">✗ No Support</span>'); ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<?php if (!$has_webp): ?>
					<div class="mt-8 p-6 bg-red-50 border border-red-100 rounded-2xl flex gap-4">
						<span class="material-symbols-outlined text-red-500">warning</span>
						<div>
							<h4 class="font-bold text-red-900 mb-1">
								<?php esc_html_e('Correction Required', 'mak8it-smart-image'); ?>
							</h4>
							<p class="text-sm text-red-700">
								<?php esc_html_e('Your server currently lacks WebP image processing capabilities. Please contact your host or server administrator to enable the GD or Imagick PHP extensions with WebP support.', 'mak8it-smart-image'); ?>
							</p>
						</div>
					</div>
				<?php else: ?>
					<div class="mt-8 p-6 bg-success/5 border border-success/10 rounded-2xl flex gap-4">
						<span class="material-symbols-outlined text-success">check_circle</span>
						<div>
							<h4 class="font-bold text-success mb-1"><?php esc_html_e('System Ready', 'mak8it-smart-image'); ?></h4>
							<p class="text-sm opacity-70">
								<?php
								/* translators: %s: Conversion engine name (GD or Imagick) */
								printf(esc_html__('Great news! Your server is perfectly configured to use the %s engine for optimizations.', 'mak8it-smart-image'), '<strong>' . esc_html($support['method']) . '</strong>');
								?>
							</p>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Right Col: Support -->
		<div class="flex flex-col gap-6">
			<div class="glass-card rounded-3xl p-8 shadow-sm">
				<h4 class="text-lg font-bold mb-6"><?php esc_html_e('Server Specs', 'mak8it-smart-image'); ?></h4>
				<ul class="space-y-4">
					<li class="flex justify-between text-sm">
						<span class="opacity-50"><?php esc_html_e('Memory Limit', 'mak8it-smart-image'); ?></span>
						<span class="font-bold"><?php echo esc_html(ini_get('memory_limit')); ?></span>
					</li>
					<li class="flex justify-between text-sm">
						<span class="opacity-50"><?php esc_html_e('Max Upload', 'mak8it-smart-image'); ?></span>
						<span class="font-bold"><?php echo esc_html(size_format(wp_max_upload_size())); ?></span>
					</li>
					<li class="flex justify-between text-sm">
						<span class="opacity-50"><?php esc_html_e('Active Engine', 'mak8it-smart-image'); ?></span>
						<span class="font-bold text-primary"><?php echo esc_html($support['method']); ?></span>
					</li>
				</ul>
			</div>

			<div class="glass-card rounded-3xl p-8 shadow-sm">
				<h4 class="text-lg font-bold mb-4"><?php esc_html_e('Refresh Status', 'mak8it-smart-image'); ?></h4>
				<p class="text-sm opacity-60 mb-6">
					<?php esc_html_e('Changed your server settings? Click below to re-scan.', 'mak8it-smart-image'); ?>
				</p>
				<button type="button" id="btn-rescan-server"
					class="w-full bg-[#f2f0f4] dark:bg-white/5 hover:bg-primary hover:text-white py-3 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2">
					<span class="material-symbols-outlined text-sm">refresh</span>
					<span class="btn-text"><?php esc_html_e('Re-scan Server', 'mak8it-smart-image'); ?></span>
				</button>
				<p id="rescan-status" class="text-xs mt-3 text-center opacity-60"></p>
			</div>

			<div class="glass-card rounded-3xl p-8 shadow-sm mt-6">
				<h4 class="text-lg font-bold mb-4"><?php esc_html_e('Report a Bug / Support', 'mak8it-smart-image'); ?></h4>
				<p class="text-sm opacity-60 mb-6">
					<?php esc_html_e('Found a bug or need assistance? Report it directly on our GitHub Issues page. You can copy your system details below to help us troubleshoot faster.', 'mak8it-smart-image'); ?>
				</p>
				
				<div class="flex flex-col gap-3">
					<button type="button" id="btn-copy-sysinfo"
						class="w-full bg-[#f2f0f4] dark:bg-white/5 hover:bg-primary hover:text-white py-3 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2">
						<span class="material-symbols-outlined text-sm">content_copy</span>
						<span class="btn-text"><?php esc_html_e('Copy System Details', 'mak8it-smart-image'); ?></span>
					</button>
					
					<a href="https://github.com/Mujtabahaider-Dev/M8-Smart-Image/issues" target="_blank" rel="noopener noreferrer"
						class="w-full bg-primary text-white text-center hover:bg-opacity-90 py-3 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2" style="text-decoration:none;">
						<span class="material-symbols-outlined text-sm">bug_report</span>
						<?php esc_html_e('Report a Bug on GitHub ↗', 'mak8it-smart-image'); ?>
					</a>
				</div>
				
				<!-- Hidden textarea for copying system details -->
				<textarea id="sysinfo-text" class="hidden" style="display:none;"><?php
					global $wp_version;
					$sysinfo = array(
						'Plugin Name'       => 'Mak8it Smart Image',
						'Plugin Version'    => MKIT_SI_VERSION,
						'WordPress Version' => $wp_version,
						'PHP Version'       => PHP_VERSION,
						'GD Library'        => $detailed_info['gd_loaded'] ? 'Enabled (WebP: ' . ($detailed_info['gd_webp'] ? 'Yes' : 'No') . ')' : 'Disabled',
						'Imagick'           => $detailed_info['imagick_loaded'] ? 'Enabled (WebP: ' . ($detailed_info['imagick_webp'] ? 'Yes' : 'No') . ')' : 'Disabled',
						'Active Engine'     => $support['method'],
						'Memory Limit'      => ini_get('memory_limit'),
						'Max Upload Size'   => size_format(wp_max_upload_size()),
						'User Agent'        => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : 'N/A',
					);
					foreach ( $sysinfo as $key => $val ) {
						echo esc_html( "$key: $val\n" );
					}
				?></textarea>
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