<?php
/**
 * Lime CTA band — mirrors Home #contato.
 *
 * @package WVTodoz
 *
 * @var array{before?:string,highlight?:string,label?:string,url?:string,show?:bool|int} $args
 */

if (!defined('ABSPATH')) {
	exit;
}

$show = !empty($args['show']);
if (!$show) {
	return;
}

$before = (string) ($args['before'] ?? '');
$highlight = (string) ($args['highlight'] ?? '');
$label = (string) ($args['label'] ?? __('Fale conosco', 'wvtodoz'));
$url = (string) ($args['url'] ?? (function_exists('wv_contato_url') ? wv_contato_url() : home_url('/contato/')));
if ($before === '' && $highlight === '') {
	return;
}
$external = function_exists('wv_is_external_url') && wv_is_external_url($url);
$is_mail = str_starts_with($url, 'mailto:');
?>
<section class="contato-cta wv-page-cta py-24 md:py-32 bg-todoz flex items-center justify-center text-center relative overflow-hidden">
	<div class="site-container relative z-10" data-reveal>
		<h2 class="contato-cta-title text-black font-black leading-[0.9] mb-10 md:mb-12 tracking-tighter uppercase text-center">
			<?php echo esc_html($before); ?>
			<?php if ($highlight !== '') : ?>
			<br>
			<span class="contato-cta-highlight bg-black text-todoz px-4 sm:px-6 md:px-8 inline-block mt-2 text-glow"><?php echo esc_html($highlight); ?></span>
			<?php endif; ?>
		</h2>
		<div class="contato-cta-actions">
			<a href="<?php echo esc_url($url); ?>" class="magnetic contato-cta-btn"<?php echo ($external || $is_mail) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
				<?php echo esc_html($label); ?>
				<?php if (str_contains(strtolower($label), 'whatsapp') || str_contains($url, 'wa.me')) : ?>
				<i class="ph-bold ph-whatsapp-logo" aria-hidden="true"></i>
				<?php else : ?>
				<i class="ph-bold ph-arrow-right" aria-hidden="true"></i>
				<?php endif; ?>
			</a>
		</div>
	</div>
	<div class="contato-watermark" aria-hidden="true"><span>TODOZ</span></div>
</section>
