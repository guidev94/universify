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

	<div class="hero-v2__content site-container">
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
				<div class="neural-halo" aria-hidden="true"></div>
				<svg class="connections-svg" id="connectionsSvg" viewBox="0 0 500 500" aria-hidden="true"></svg>

				<div class="central-orb" id="centralOrb">
					<span class="central-orb__ring central-orb__ring--outer" aria-hidden="true"></span>
					<span class="central-orb__pulse" aria-hidden="true"></span>
					<div class="orb-label">
						<span class="orb-label__brand">WV</span>
						<span class="orb-label__ai">AI</span>
					</div>
				</div>

				<div class="satellite-node" id="node1" data-node="agentes">
					<span class="satellite-node__glow" aria-hidden="true"></span>
					<svg class="satellite-node__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="5" y="9" width="14" height="10" rx="2"/>
						<path d="M9 9V7a3 3 0 0 1 6 0v2"/>
						<circle cx="9.5" cy="14" r="1" fill="currentColor" stroke="none"/>
						<circle cx="14.5" cy="14" r="1" fill="currentColor" stroke="none"/>
						<path d="M12 3v2M8 19v2M16 19v2"/>
					</svg>
					<span class="satellite-node__label"><?php echo esc_html__('Agentes', 'wvtodoz'); ?></span>
				</div>
				<div class="satellite-node" id="node2" data-node="automacoes">
					<span class="satellite-node__glow" aria-hidden="true"></span>
					<svg class="satellite-node__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<circle cx="12" cy="12" r="3"/>
						<path d="M12 3v2.2M12 18.8V21M4.9 4.9l1.6 1.6M17.5 17.5l1.6 1.6M3 12h2.2M18.8 12H21M4.9 19.1l1.6-1.6M17.5 6.5l1.6-1.6"/>
						<path d="M19 9.5V7a1 1 0 0 0-1-1h-2.5"/>
					</svg>
					<span class="satellite-node__label"><?php echo esc_html__('Automações', 'wvtodoz'); ?></span>
				</div>
				<div class="satellite-node" id="node3" data-node="integracoes">
					<span class="satellite-node__glow" aria-hidden="true"></span>
					<svg class="satellite-node__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<circle cx="6" cy="12" r="2.75"/>
						<circle cx="18" cy="6.5" r="2.75"/>
						<circle cx="18" cy="17.5" r="2.75"/>
						<path d="M8.6 10.8 15.2 7.4M8.6 13.2l6.6 3.4"/>
					</svg>
					<span class="satellite-node__label"><?php echo esc_html__('Integrações', 'wvtodoz'); ?></span>
				</div>
				<div class="satellite-node" id="node4" data-node="llms">
					<span class="satellite-node__glow" aria-hidden="true"></span>
					<svg class="satellite-node__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M5 7.5A2.5 2.5 0 0 1 7.5 5h7A2.5 2.5 0 0 1 17 7.5v5A2.5 2.5 0 0 1 14.5 15H10l-3.5 2.5V15H7.5A2.5 2.5 0 0 1 5 12.5v-5z"/>
						<path d="M17 10.5h.5A2.5 2.5 0 0 1 20 13v2.5A2.5 2.5 0 0 1 17.5 18H16"/>
						<path d="M8.5 9.5h5M8.5 12h3"/>
					</svg>
					<span class="satellite-node__label">LLMs</span>
				</div>
				<div class="satellite-node" id="node5" data-node="pipelines">
					<span class="satellite-node__glow" aria-hidden="true"></span>
					<svg class="satellite-node__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="3" y="3.5" width="6.5" height="4.5" rx="1.2"/>
						<rect x="14.5" y="9.75" width="6.5" height="4.5" rx="1.2"/>
						<rect x="3" y="16" width="6.5" height="4.5" rx="1.2"/>
						<path d="M9.5 5.75h2.2a3.3 3.3 0 0 1 3.3 3.3v2.2M9.5 18.25h2.2a3.3 3.3 0 0 0 3.3-3.3v-.7"/>
					</svg>
					<span class="satellite-node__label">Pipelines</span>
				</div>
				<div class="satellite-node" id="node6" data-node="analytics">
					<span class="satellite-node__glow" aria-hidden="true"></span>
					<svg class="satellite-node__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M4 20V10M10 20V5M16 20v-8"/>
						<path d="M3 20h18"/>
						<path d="M16 8l3-3 2 2"/>
					</svg>
					<span class="satellite-node__label">Analytics</span>
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
