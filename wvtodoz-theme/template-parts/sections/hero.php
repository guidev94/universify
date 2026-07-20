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

		<aside class="hero-v2__right" id="heroValuePanel" aria-label="<?php echo esc_attr__('Como a WV entrega valor com IA', 'wvtodoz'); ?>">
			<div class="hero-value" id="heroValue">
				<div class="hero-value__glow" aria-hidden="true"></div>
				<div class="hero-value__card">
					<div class="hero-value__top">
						<span class="hero-value__brand" aria-hidden="true">WV</span>
						<span class="hero-value__kicker"><?php echo esc_html__('AI Solutions', 'wvtodoz'); ?></span>
					</div>
					<h2 class="hero-value__headline"><?php echo esc_html__('Operação com IA, do agente à integração', 'wvtodoz'); ?></h2>
					<p class="hero-value__outcome"><?php echo esc_html__('Menos trabalho manual. Mais resultado no fluxo do negócio.', 'wvtodoz'); ?></p>

					<ul class="hero-value__pillars">
						<li class="hero-value__pillar">
							<span class="hero-value__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
									<rect x="5" y="9" width="14" height="10" rx="2"/>
									<path d="M9 9V7a3 3 0 0 1 6 0v2"/>
									<circle cx="9.5" cy="14" r="1" fill="currentColor" stroke="none"/>
									<circle cx="14.5" cy="14" r="1" fill="currentColor" stroke="none"/>
									<path d="M12 3v2"/>
								</svg>
							</span>
							<div class="hero-value__pillar-body">
								<h3 class="hero-value__pillar-title"><?php echo esc_html__('Agentes', 'wvtodoz'); ?></h3>
								<p class="hero-value__pillar-text"><?php echo esc_html__('Atendem, triam e resolvem 24/7', 'wvtodoz'); ?></p>
							</div>
						</li>
						<li class="hero-value__pillar">
							<span class="hero-value__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
									<circle cx="12" cy="12" r="3"/>
									<path d="M12 3v2.2M12 18.8V21M4.9 4.9l1.6 1.6M17.5 17.5l1.6 1.6M3 12h2.2M18.8 12H21M4.9 19.1l1.6-1.6M17.5 6.5l1.6-1.6"/>
								</svg>
							</span>
							<div class="hero-value__pillar-body">
								<h3 class="hero-value__pillar-title"><?php echo esc_html__('Automações', 'wvtodoz'); ?></h3>
								<p class="hero-value__pillar-text"><?php echo esc_html__('Tiram o manual do fluxo operacional', 'wvtodoz'); ?></p>
							</div>
						</li>
						<li class="hero-value__pillar">
							<span class="hero-value__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
									<circle cx="6" cy="12" r="2.75"/>
									<circle cx="18" cy="6.5" r="2.75"/>
									<circle cx="18" cy="17.5" r="2.75"/>
									<path d="M8.6 10.8 15.2 7.4M8.6 13.2l6.6 3.4"/>
								</svg>
							</span>
							<div class="hero-value__pillar-body">
								<h3 class="hero-value__pillar-title"><?php echo esc_html__('Integrações', 'wvtodoz'); ?></h3>
								<p class="hero-value__pillar-text"><?php echo esc_html__('Conectam ERPs, CRMs e canais sem fricção', 'wvtodoz'); ?></p>
							</div>
						</li>
					</ul>

					<div class="hero-value__pipeline" aria-hidden="true">
						<div class="hero-value__pipeline-steps">
							<span><?php echo esc_html__('Entrada', 'wvtodoz'); ?></span>
							<span class="hero-value__pipeline-sep"></span>
							<span><?php echo esc_html__('Agente', 'wvtodoz'); ?></span>
							<span class="hero-value__pipeline-sep"></span>
							<span><?php echo esc_html__('Automação', 'wvtodoz'); ?></span>
							<span class="hero-value__pipeline-sep"></span>
							<span><?php echo esc_html__('Sistema', 'wvtodoz'); ?></span>
						</div>
						<div class="hero-value__pipeline-track">
							<span class="hero-value__pipeline-fill"></span>
						</div>
					</div>

					<div class="hero-value__metric">
						<span class="hero-value__metric-dot" aria-hidden="true"></span>
						<span><?php echo esc_html__('Fluxo contínuo · resultado mensurável', 'wvtodoz'); ?></span>
					</div>
				</div>
			</div>
		</aside>
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
