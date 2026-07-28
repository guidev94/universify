<?php
/**
 * Sobre section.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['sobre'];
$line = (string) wv_field('wv_sobre_title_line', $d['title_line']);
$accent = (string) wv_field('wv_sobre_title_accent', $d['title_accent']);
$lead = (string) wv_field('wv_sobre_lead', $d['lead']);
$s1n = (string) wv_field('wv_sobre_stat1_n', $d['stat1_n']);
$s1l = (string) wv_field('wv_sobre_stat1_l', $d['stat1_l']);
$s2n = (string) wv_field('wv_sobre_stat2_n', $d['stat2_n']);
$s2l = (string) wv_field('wv_sobre_stat2_l', $d['stat2_l']);
$cta1 = (string) wv_field('wv_sobre_cta_primary', $d['cta_primary']);
$cta2 = (string) wv_field('wv_sobre_cta_secondary', $d['cta_secondary']);
$img = wv_image_url(wv_field('wv_sobre_image', null), $d['image']);
?>
<section id="sobre" class="section-canvas section-glow py-32 relative">
	<div class="site-container">
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-12 md:gap-24 items-center">
			<div data-aos="fade-right" class="text-center md:text-left">
				<h2 class="section-display text-center md:text-left">
					<span class="section-display-line"><?php echo esc_html($line); ?></span>
					<span class="section-display-accent"><?php echo esc_html($accent); ?><span class="section-display-dot" aria-hidden="true"></span></span>
				</h2>
				<p class="section-lead mb-8 max-w-xl mx-auto md:mx-0"><?php echo esc_html($lead); ?></p>
				<div class="grid grid-cols-2 gap-8 md:gap-12 mb-12 max-w-md mx-auto md:mx-0 md:max-w-none">
					<div class="border-l-2 border-todoz pl-6 text-left"><span class="block text-4xl md:text-5xl font-black text-white mb-2"><?php echo esc_html($s1n); ?></span><span class="text-[10px] text-todoz uppercase tracking-[0.3em] font-black"><?php echo esc_html($s1l); ?></span></div>
					<div class="border-l-2 border-todoz pl-6 text-left"><span class="block text-4xl md:text-5xl font-black text-white mb-2"><?php echo esc_html($s2n); ?></span><span class="text-[10px] text-todoz uppercase tracking-[0.3em] font-black"><?php echo esc_html($s2l); ?></span></div>
				</div>
				<div class="flex flex-col sm:flex-row flex-wrap gap-4 sm:gap-6 items-center justify-center md:justify-start">
					<button type="button" class="open-modal magnetic bg-todoz px-10 py-5 text-black font-black rounded-full text-[10px] md:text-xs uppercase tracking-widest w-full sm:w-auto max-w-xs" data-wv-section="sobre" data-wv-lead-type="contato"><?php echo esc_html($cta1); ?></button>
					<a href="<?php echo esc_url(home_url('/#servicos')); ?>" class="magnetic px-10 py-5 bg-white/5 border border-white/10 text-white font-black rounded-full text-[10px] md:text-xs uppercase tracking-widest w-full sm:w-auto max-w-xs text-center hover:border-todoz/40 hover:text-todoz transition-colors"><?php echo esc_html($cta2); ?></a>
				</div>
			</div>
			<div class="relative" data-aos="fade-left">
				<div class="relative z-10 rounded-[3rem] overflow-hidden border border-white/10 shadow-2xl group">
					<img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($line . ' ' . $accent); ?>" class="sobre-office-img w-full h-full object-cover transition-transform duration-1000 scale-100 group-hover:scale-105">
				</div>
				<div class="absolute -bottom-8 left-0 w-40 h-40 md:w-48 md:h-48 bg-todoz/20 rounded-full blur-[80px] -z-10 pointer-events-none" aria-hidden="true"></div>
			</div>
		</div>
	</div>
</section>
