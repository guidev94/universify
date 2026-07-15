<?php
/**
 * Contato CTA section.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['contato'];
$before = (string) wv_field('wv_contato_title_before', $d['title_before']);
$highlight = (string) wv_field('wv_contato_title_highlight', $d['title_highlight']);
$phone = (string) wv_field('wv_contato_phone', $d['phone']);
$tel = (string) wv_field('wv_contato_tel', $d['tel']);
$cta = (string) wv_field('wv_contato_cta', $d['cta']);
$wa = wv_whatsapp_url();
?>
<section id="contato" class="contato-cta py-24 md:py-36 bg-todoz flex items-center justify-center text-center relative overflow-hidden">
	<div class="site-container relative z-10" data-reveal>
		<h2 class="contato-cta-title text-black font-black leading-[0.9] mb-10 md:mb-14 tracking-tighter uppercase text-center">
			<?php echo esc_html($before); ?> <br>
			<span class="contato-cta-highlight bg-black text-todoz px-4 sm:px-6 md:px-8 inline-block mt-2 text-glow"><?php echo esc_html($highlight); ?></span>
		</h2>
		<div class="contato-cta-actions">
			<a href="<?php echo esc_url('tel:' . preg_replace('/\D+/', '', $tel)); ?>" class="contato-cta-phone"><?php echo esc_html($phone); ?></a>
			<a href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer" class="magnetic contato-cta-btn"><?php echo esc_html($cta); ?> <i class="ph-bold ph-whatsapp-logo"></i></a>
		</div>
	</div>
	<div class="contato-watermark" aria-hidden="true">
		<span>TODOZ</span>
	</div>
</section>
