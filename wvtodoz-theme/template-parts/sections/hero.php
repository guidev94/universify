<?php
/**
 * Hero section.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['hero'];
$badge = (string) wv_field('wv_hero_badge', $d['badge']);
$eyebrow = (string) wv_field('wv_hero_eyebrow', $d['eyebrow']);
$line = (string) wv_field('wv_hero_title_line', $d['title_line']);
$accent = (string) wv_field('wv_hero_title_accent', $d['title_accent']);
$lead = (string) wv_field('wv_hero_lead', $d['lead']);
$cta1 = (string) wv_field('wv_hero_cta_primary', $d['cta_primary']);
$cta1_url = (string) wv_field('wv_hero_cta_primary_url', wv_whatsapp_url());
$cta2 = (string) wv_field('wv_hero_cta_secondary', $d['cta_secondary']);
$video = wv_file_url(wv_field('wv_hero_video', null), $d['video']);
$show_logos = wv_field('wv_hero_show_logos', true);
$logos = wv_defaults()['hero_logos'];
?>
<section id="hero" class="hero-section relative flex items-center justify-center bg-[#050505]">
	<div class="absolute inset-0 z-0 overflow-hidden">
		<video autoplay muted loop playsinline preload="metadata" class="hero-video w-full h-full object-cover opacity-[0.52] filter grayscale-[0.55] brightness-[0.83]">
			<source src="<?php echo esc_url($video); ?>" type="video/mp4">
		</video>
		<div class="hero-mesh absolute inset-0 opacity-[0.34] mix-blend-soft-light" style="background: radial-gradient(circle at 20% 30%, #CFFF04 0%, transparent 42%), radial-gradient(circle at 80% 70%, #CFFF04 0%, transparent 44%);"></div>
		<div class="hero-glow" aria-hidden="true"></div>
		<div class="hero-glass absolute inset-0 bg-gradient-to-b from-primary/48 via-primary/6 to-primary/52"></div>
		<div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-[url('https://grainy-gradients.vercel.app/noise.svg')]"></div>
	</div>

	<div class="site-container relative z-10">
		<div class="hero-inner max-w-5xl mx-auto flex flex-col items-center">
			<div class="hero-badge inline-flex items-center gap-3 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 backdrop-blur-md transition-all hover:border-todoz/40 group" data-aos="fade-down">
				<span class="w-1.5 h-1.5 rounded-full bg-todoz animate-pulse shadow-[0_0_10px_#CFFF04]"></span>
				<span class="text-white font-black text-[10px] tracking-[0.28em] uppercase"><?php echo esc_html($badge); ?></span>
			</div>

			<h1 class="hero-title text-center" data-aos="zoom-out" data-aos-delay="120">
				<span class="hero-eyebrow block text-zinc-400 font-semibold uppercase"><?php echo esc_html($eyebrow); ?></span>
				<span class="hero-display block font-black tracking-tighter text-white uppercase italic">
					<?php echo esc_html($line); ?> <br class="hero-display-br" aria-hidden="true"><span class="text-todoz text-glow not-italic"><?php echo esc_html($accent); ?></span>
				</span>
			</h1>

			<p class="hero-lead text-zinc-400 max-w-xl mx-auto leading-relaxed text-center font-medium" data-aos="fade-up" data-aos-delay="220">
				<?php echo esc_html($lead); ?>
			</p>

			<div class="hero-ctas flex flex-col sm:flex-row items-center justify-center w-full" data-aos="fade-up" data-aos-delay="320">
				<a href="<?php echo esc_url($cta1_url); ?>" target="_blank" rel="noopener noreferrer" class="hero-cta magnetic bg-todoz group relative text-black font-black rounded-full uppercase flex items-center justify-center gap-3">
					<?php echo esc_html($cta1); ?>
					<div class="relative w-5 h-5 flex items-center justify-center overflow-hidden">
						<i class="ph-fill ph-rocket-launch text-lg group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform"></i>
					</div>
				</a>
				<button type="button" class="hero-cta open-modal magnetic bg-white/5 border border-white/10 text-white font-black rounded-full hover:bg-white/10 uppercase backdrop-blur-sm" data-wv-section="hero" data-wv-lead-type="contato">
					<?php echo esc_html($cta2); ?>
				</button>
			</div>

			<?php if ($show_logos) : ?>
			<div class="hero-logos" data-aos="fade-up" data-aos-delay="400">
				<?php foreach ($logos as $logo) : ?>
				<a href="<?php echo esc_url(home_url('/#produtos')); ?>" class="hero-logo" aria-label="<?php echo esc_attr($logo['alt']); ?>">
					<img src="<?php echo esc_url($logo['src']); ?>" alt="<?php echo esc_attr($logo['alt']); ?>" width="<?php echo esc_attr((string) $logo['w']); ?>" height="<?php echo esc_attr((string) $logo['h']); ?>" loading="lazy">
				</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<div class="hero-scroll-cue" aria-hidden="true">
				<div class="hero-scroll-pill"><div class="hero-scroll-dot"></div></div>
				<span class="hero-scroll-label">Scroll</span>
			</div>
		</div>
	</div>
</section>
