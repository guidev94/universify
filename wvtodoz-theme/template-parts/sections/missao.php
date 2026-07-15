<?php
/**
 * Missão section.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['missao'];
$eyebrow = (string) wv_field('wv_missao_eyebrow', $d['eyebrow']);
$title = (string) wv_field('wv_missao_title', $d['title']);
$support = (string) wv_field('wv_missao_support', $d['support']);
$pa = (string) wv_field('wv_missao_product_a', $d['product_a']);
$pb = (string) wv_field('wv_missao_product_b', $d['product_b']);
$cta = (string) wv_field('wv_missao_cta', $d['cta']);
$s1n = (string) wv_field('wv_sobre_stat1_n', '11+');
$s2n = (string) wv_field('wv_sobre_stat2_n', '50+');
$count1 = (int) preg_replace('/\D/', '', $s1n) ?: 11;
$count2 = (int) preg_replace('/\D/', '', $s2n) ?: 50;
?>
<section id="missao" class="missao-highlight relative overflow-hidden">
	<div class="site-container relative z-10">
		<div class="missao-panel" data-aos="fade-up">
			<div class="missao-main">
				<p class="missao-eyebrow"><?php echo esc_html($eyebrow); ?></p>
				<h2 class="missao-title"><?php echo esc_html($title); ?></h2>
				<p class="missao-support"><?php echo esc_html($support); ?></p>
			</div>
			<div class="missao-aside">
				<dl class="missao-stats">
					<div class="missao-stat">
						<dt><span class="missao-count" data-count="<?php echo esc_attr((string) $count1); ?>" data-suffix="+"><?php echo esc_html($s1n); ?></span></dt>
						<dd><?php esc_html_e('anos de mercado', 'wvtodoz'); ?></dd>
					</div>
					<div class="missao-stat">
						<dt><span class="missao-count" data-count="<?php echo esc_attr((string) $count2); ?>" data-suffix="+"><?php echo esc_html($s2n); ?></span></dt>
						<dd><?php esc_html_e('projetos entregues', 'wvtodoz'); ?></dd>
					</div>
					<div class="missao-stat missao-stat--products">
						<dt class="missao-products">
							<span class="missao-product"><?php echo esc_html($pa); ?></span>
							<span class="missao-product missao-product--accent"><?php echo esc_html($pb); ?></span>
						</dt>
						<dd><?php esc_html_e('produtos próprios', 'wvtodoz'); ?></dd>
					</div>
				</dl>
				<button type="button" class="open-modal magnetic missao-cta">
					<?php echo esc_html($cta); ?>
					<i class="ph ph-arrow-right" aria-hidden="true"></i>
				</button>
			</div>
		</div>
	</div>
</section>
