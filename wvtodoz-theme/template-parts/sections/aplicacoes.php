<?php
/**
 * Aplicações / IA section.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['aplicacoes'];
$eyebrow = (string) wv_field('wv_aplicacoes_eyebrow', $d['eyebrow']);
$line = (string) wv_field('wv_aplicacoes_title_line', $d['title_line']);
$accent = (string) wv_field('wv_aplicacoes_title_accent', $d['title_accent']);
$lead = (string) wv_field('wv_aplicacoes_lead', $d['lead']);
$img = wv_image_url(wv_field('wv_aplicacoes_image', null), $d['image']);
$chip_k = (string) wv_field('wv_aplicacoes_chip_kicker', $d['chip_kicker']);
$chip_t = (string) wv_field('wv_aplicacoes_chip_title', $d['chip_title']);
$b1 = (string) wv_field('wv_aplicacoes_b1', $d['b1']);
$b2 = (string) wv_field('wv_aplicacoes_b2', $d['b2']);
$b3 = (string) wv_field('wv_aplicacoes_b3', $d['b3']);
$cta = (string) wv_field('wv_aplicacoes_cta', $d['cta']);
?>
<section id="aplicacoes" class="section-canvas section-glow py-20 md:py-24">
	<div class="site-container">
		<div class="aplicacoes-layout">
			<div class="aplicacoes-media-wrap relative" data-aos="fade-right">
				<div class="aplicacoes-media group">
					<img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr__('Aplicações e sistemas com inteligência artificial', 'wvtodoz'); ?>" width="1400" height="1400" loading="lazy" decoding="async" class="aplicacoes-media-img">
					<div class="aplicacoes-media-veil" aria-hidden="true"></div>
					<div class="aplicacoes-media-grid" aria-hidden="true"></div>
					<div class="aplicacoes-media-nodes" aria-hidden="true"></div>
					<div class="aplicacoes-media-rim" aria-hidden="true"></div>
					<div class="aplicacoes-chip">
						<span class="aplicacoes-chip-pulse" aria-hidden="true"></span>
						<div class="aplicacoes-chip-copy">
							<span class="aplicacoes-chip-kicker"><?php echo esc_html($chip_k); ?></span>
							<span class="aplicacoes-chip-title"><?php echo esc_html($chip_t); ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="aplicacoes-copy" data-aos="fade-left">
				<span class="aplicacoes-eyebrow"><?php echo esc_html($eyebrow); ?></span>
				<h2 class="section-display">
					<span class="section-display-line"><?php echo esc_html($line); ?></span>
					<span class="section-display-accent"><?php echo esc_html($accent); ?><span class="section-display-dot" aria-hidden="true"></span></span>
				</h2>
				<p class="section-lead aplicacoes-lead"><?php echo esc_html($lead); ?></p>
				<ul class="aplicacoes-benefits">
					<li><?php echo esc_html($b1); ?></li>
					<li><?php echo esc_html($b2); ?></li>
					<li><?php echo esc_html($b3); ?></li>
				</ul>
				<button type="button" class="open-modal magnetic aplicacoes-cta" data-wv-section="aplicacoes" data-wv-lead-type="contato"><?php echo esc_html($cta); ?></button>
			</div>
		</div>
	</div>
</section>
