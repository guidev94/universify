<?php
/**
 * Hero section v2 — canvas + neural + marquee (dark cinematic band).
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['hero'];
$sobre = wv_defaults()['sobre'];
$badge = (string) wv_field('wv_hero_badge', $d['badge']);
$eyebrow = (string) wv_field('wv_hero_eyebrow', $d['eyebrow']);
$line = (string) wv_field('wv_hero_title_line', $d['title_line']);
$accent = (string) wv_field('wv_hero_title_accent', $d['title_accent']);
$lead = (string) wv_field('wv_hero_lead', $d['lead']);
$cta1 = (string) wv_field('wv_hero_cta_primary', $d['cta_primary']);
$cta1_url = (string) wv_field('wv_hero_cta_primary_url', wv_whatsapp_url());
$cta2 = (string) wv_field('wv_hero_cta_secondary', $d['cta_secondary']);
$show_logos = wv_field('wv_hero_show_logos', true);
$logos = wv_defaults()['hero_logos'];

$stat1_n = (string) wv_field('wv_sobre_stat1_n', $sobre['stat1_n']);
$stat1_l = (string) wv_field('wv_sobre_stat1_l', $sobre['stat1_l']);
$stat2_n = (string) wv_field('wv_sobre_stat2_n', $sobre['stat2_n']);
$stat2_l = (string) wv_field('wv_sobre_stat2_l', $sobre['stat2_l']);
$stat3_n = '4';
$stat3_l = __('Produtos Próprios', 'wvtodoz');
?>
<section id="hero" class="hero-section hero-v2" aria-label="<?php echo esc_attr__('Hero', 'wvtodoz'); ?>">
	<canvas id="hero-canvas" aria-hidden="true"></canvas>

	<div class="ambient-orb ambient-orb--1" aria-hidden="true"></div>
	<div class="ambient-orb ambient-orb--2" aria-hidden="true"></div>
	<div class="ambient-orb ambient-orb--3" aria-hidden="true"></div>
	<div class="scanlines" aria-hidden="true"></div>
	<div class="grid-overlay" id="gridOverlay" aria-hidden="true"></div>
	<div class="vignette" aria-hidden="true"></div>

	<div class="hero-v2__content">
		<div class="hero-v2__left">
			<div class="ai-badge" id="aiBadge">
				<span class="ai-badge__dot" aria-hidden="true"></span>
				<span class="ai-badge__text"><?php echo esc_html($badge); ?></span>
			</div>

			<p class="hero-v2__eyebrow" id="heroEyebrow"><?php echo esc_html($eyebrow); ?></p>

			<h1 class="hero-v2__title" id="heroTitle">
				<span class="hero-v2__title-line"><?php echo esc_html($line); ?></span>
				<span class="hero-v2__title-line hero-v2__title-accent glitch-text" data-text="<?php echo esc_attr($accent); ?>"><?php echo esc_html($accent); ?></span>
			</h1>

			<p class="hero-v2__subtitle" id="heroSubtitle">
				<?php echo esc_html($lead); ?>
			</p>

			<div class="hero-v2__stats" id="heroStats">
				<div class="hero-v2__stat">
					<span class="hero-v2__stat-num"><?php echo esc_html($stat1_n); ?></span>
					<span class="hero-v2__stat-label"><?php echo esc_html($stat1_l); ?></span>
				</div>
				<div class="hero-v2__stat">
					<span class="hero-v2__stat-num"><?php echo esc_html($stat2_n); ?></span>
					<span class="hero-v2__stat-label"><?php echo esc_html($stat2_l); ?></span>
				</div>
				<div class="hero-v2__stat">
					<span class="hero-v2__stat-num"><?php echo esc_html($stat3_n); ?></span>
					<span class="hero-v2__stat-label"><?php echo esc_html($stat3_l); ?></span>
				</div>
			</div>

			<div class="hero-v2__ctas" id="heroCtas">
				<a href="<?php echo esc_url($cta1_url); ?>" target="_blank" rel="noopener noreferrer" class="hero-v2__cta hero-v2__cta--primary magnetic">
					<span><?php echo esc_html($cta1); ?></span>
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M3 8h10M9 4l4 4-4 4"/>
					</svg>
				</a>
				<button type="button" class="hero-v2__cta hero-v2__cta--ghost open-modal" data-wv-section="hero" data-wv-lead-type="contato">
					<span><?php echo esc_html($cta2); ?></span>
				</button>
			</div>

			<?php if ($show_logos) : ?>
			<div class="hero-v2__logos hero-logos" id="heroLogos">
				<?php foreach ($logos as $logo) : ?>
				<a href="<?php echo esc_url(home_url('/#produtos')); ?>" class="hero-logo" aria-label="<?php echo esc_attr($logo['alt']); ?>">
					<img src="<?php echo esc_url($logo['src']); ?>" alt="<?php echo esc_attr($logo['alt']); ?>" width="<?php echo esc_attr((string) $logo['w']); ?>" height="<?php echo esc_attr((string) $logo['h']); ?>" loading="lazy">
				</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>

		<div class="hero-v2__right" aria-hidden="true">
			<div class="neural-container" id="neuralContainer">
				<svg class="connections-svg" id="connectionsSvg" viewBox="0 0 500 500"></svg>
				<div class="central-orb" id="centralOrb">
					<div class="orb-label">WV<br>AI</div>
				</div>
				<div class="satellite-node" id="node1">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
					<span class="satellite-node__label"><?php echo esc_html__('Agentes', 'wvtodoz'); ?></span>
				</div>
				<div class="satellite-node" id="node2">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
					<span class="satellite-node__label">NLP</span>
				</div>
				<div class="satellite-node" id="node3">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
					<span class="satellite-node__label">ML Ops</span>
				</div>
				<div class="satellite-node" id="node4">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h18c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
					<span class="satellite-node__label"><?php echo esc_html__('Integrações', 'wvtodoz'); ?></span>
				</div>
				<div class="satellite-node" id="node5">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>
					<span class="satellite-node__label">Cloud</span>
				</div>
				<div class="satellite-node" id="node6">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
					<span class="satellite-node__label"><?php echo esc_html__('Segurança', 'wvtodoz'); ?></span>
				</div>
			</div>
		</div>
	</div>

	<div class="hero-scroll-cue" id="scrollCue" aria-hidden="true">
		<div class="hero-scroll-pill">
			<div class="hero-scroll-dot"></div>
		</div>
		<span class="hero-scroll-label">Scroll</span>
	</div>

	<div class="hero-v2__marquee" id="heroMarquee" aria-hidden="true">
		<div class="marquee-track">
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Agentes de IA', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Automações Inteligentes', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Integração de Sistemas', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span>Machine Learning</div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Processamento de Linguagem Natural', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span>Computer Vision</div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Predição e Analytics', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Chatbots Avançados', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Agentes de IA', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Automações Inteligentes', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Integração de Sistemas', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span>Machine Learning</div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Processamento de Linguagem Natural', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span>Computer Vision</div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Predição e Analytics', 'wvtodoz'); ?></div>
			<div class="marquee-item"><span class="marquee-item__dot"></span><?php echo esc_html__('Chatbots Avançados', 'wvtodoz'); ?></div>
		</div>
	</div>
</section>
