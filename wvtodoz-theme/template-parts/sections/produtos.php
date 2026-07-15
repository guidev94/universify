<?php
/**
 * Produtos bento section.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['produtos'];
$eyebrow = (string) wv_field('wv_produtos_eyebrow', $d['eyebrow']);
$line = (string) wv_field('wv_produtos_title_line', $d['title_line']);
$accent = (string) wv_field('wv_produtos_title_accent', $d['title_accent']);
$lead = (string) wv_field('wv_produtos_lead', $d['lead']);

$cards = [];
$posts = wv_query_posts('wv_produto');
$light_logo_default = WVTODOZ_URI . '/assets/img/produtos/letswork-logo-light.webp';
$light_logo_cdn = 'https://www.letswork.com.br/wp-content/themes/letswork2022/assets/img/logo.webp';
if ($posts) {
	foreach ($posts as $p) {
		$logo = get_the_post_thumbnail_url($p, 'full') ?: '';
		$featured = function_exists('get_field') ? (bool) get_field('wv_produto_featured', $p->ID) : false;
		$title = get_the_title($p);
		$logo_light = '';
		$logo_light_fallback = '';
		if ($featured && (stripos($title, "Let's Work") !== false || stripos($logo, 'letswork') !== false)) {
			$logo_light = $light_logo_default;
			$logo_light_fallback = $light_logo_cdn;
		}
		$cards[] = [
			'featured' => $featured,
			'wide' => function_exists('get_field') ? (bool) get_field('wv_produto_wide', $p->ID) : false,
			'badge' => function_exists('get_field') ? (string) get_field('wv_produto_badge', $p->ID) : '',
			'title' => $title,
			'subtitle' => function_exists('get_field') ? (string) get_field('wv_produto_subtitle', $p->ID) : '',
			'desc' => wp_strip_all_tags(get_the_content(null, false, $p)),
			'logo' => $logo,
			'logo_light' => $logo_light,
			'logo_light_fallback' => $logo_light_fallback,
			'highlights' => function_exists('get_field') ? (string) get_field('wv_produto_highlights', $p->ID) : '',
			'primary_label' => function_exists('get_field') ? (string) get_field('wv_produto_primary_label', $p->ID) : '',
			'primary_url' => function_exists('get_field') ? (string) get_field('wv_produto_primary_url', $p->ID) : '',
			'secondary_label' => function_exists('get_field') ? (string) get_field('wv_produto_secondary_label', $p->ID) : '',
			'secondary_url' => function_exists('get_field') ? (string) get_field('wv_produto_secondary_url', $p->ID) : '',
			'secondary_modal' => function_exists('get_field') ? !(string) get_field('wv_produto_secondary_url', $p->ID) : true,
		];
	}
}
if (!$cards) {
	$cards = wv_defaults()['produtos_cards'];
}
?>
<section id="produtos" class="produtos-section">
	<div class="produtos-glow" aria-hidden="true"></div>
	<div class="site-container relative z-10">
		<div class="produtos-header text-center md:text-left mb-12 md:mb-16">
			<span class="text-todoz text-[10px] font-black tracking-[0.4em] uppercase block mb-4" data-aos="fade-up"><?php echo esc_html($eyebrow); ?></span>
			<h2 class="section-display" data-aos="fade-up" data-aos-delay="50">
				<span class="section-display-line"><?php echo esc_html($line); ?></span>
				<span class="section-display-accent"><?php echo esc_html($accent); ?><span class="section-display-dot" aria-hidden="true"></span></span>
			</h2>
			<p class="section-lead mt-6 max-w-2xl mx-auto md:mx-0" data-aos="fade-up" data-aos-delay="100"><?php echo esc_html($lead); ?></p>
		</div>

		<div class="produtos-bento">
			<?php foreach ($cards as $i => $card) :
				$classes = ['produto-card'];
				if (!empty($card['featured'])) {
					$classes[] = 'produto-card--featured';
				}
				if (!empty($card['wide'])) {
					$classes[] = 'produto-card--wide';
				}
				$glow = !empty($card['featured']) ? 'lime' : 'soft';
				$delay = $i ? (40 + $i * 40) : 0;
				$highlights = array_filter(array_map('trim', explode("\n", (string) ($card['highlights'] ?? ''))));
				?>
			<article class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-glow="<?php echo esc_attr($glow); ?>" data-aos="fade-up"<?php echo $delay ? ' data-aos-delay="' . esc_attr((string) $delay) . '"' : ''; ?>>
				<?php if (!empty($card['badge'])) : ?>
				<span class="produto-badge"><?php echo esc_html((string) $card['badge']); ?></span>
				<?php endif; ?>
				<div class="produto-card-inner">
					<?php if (!empty($card['logo']) || !empty($card['logo_light'])) : ?>
					<div class="produto-brand">
						<?php if (!empty($card['logo_light']) && !empty($card['logo'])) : ?>
						<img
							src="<?php echo esc_url($card['logo_light']); ?>"
							alt="<?php echo esc_attr($card['title']); ?>"
							class="produto-logo produto-logo--light"
							loading="lazy"
							<?php if (!empty($card['logo_light_fallback'])) : ?>
							onerror="this.onerror=null;this.src='<?php echo esc_url($card['logo_light_fallback']); ?>'"
							<?php endif; ?>
						>
						<img src="<?php echo esc_url($card['logo']); ?>" alt="" class="produto-logo produto-logo--dark" loading="lazy" aria-hidden="true">
						<?php else : ?>
						<img src="<?php echo esc_url(!empty($card['logo']) ? $card['logo'] : $card['logo_light']); ?>" alt="<?php echo esc_attr($card['title']); ?>" class="produto-logo" loading="lazy">
						<?php endif; ?>
					</div>
					<?php endif; ?>
					<?php if (!empty($card['featured'])) : ?>
					<div class="produto-main">
						<h3 class="produto-title"><?php echo esc_html($card['title']); ?></h3>
						<p class="produto-subtitle"><?php echo esc_html((string) ($card['subtitle'] ?? '')); ?></p>
						<p class="produto-desc"><?php echo esc_html($card['desc']); ?></p>
						<?php if ($highlights) : ?>
						<ul class="produto-highlights">
							<?php foreach ($highlights as $h) : ?>
							<li><i class="ph ph-check-circle" aria-hidden="true"></i> <?php echo esc_html($h); ?></li>
							<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					</div>
					<?php else : ?>
					<h3 class="produto-title"><?php echo esc_html($card['title']); ?></h3>
					<p class="produto-subtitle"><?php echo esc_html((string) ($card['subtitle'] ?? '')); ?></p>
					<p class="produto-desc"><?php echo esc_html($card['desc']); ?></p>
					<?php endif; ?>
					<div class="produto-actions">
						<?php if (!empty($card['primary_label']) && !empty($card['primary_url'])) : ?>
						<a href="<?php echo esc_url($card['primary_url']); ?>" target="_blank" rel="noopener noreferrer" class="magnetic produto-btn produto-btn--primary"><?php echo esc_html($card['primary_label']); ?></a>
						<?php endif; ?>
						<?php if (!empty($card['secondary_label'])) : ?>
							<?php if (!empty($card['secondary_url']) && empty($card['secondary_modal'])) : ?>
							<a href="<?php echo esc_url($card['secondary_url']); ?>" target="_blank" rel="noopener noreferrer" class="magnetic produto-btn produto-btn--ghost"><?php echo esc_html($card['secondary_label']); ?></a>
							<?php else : ?>
							<button type="button" class="open-modal magnetic produto-btn produto-btn--ghost"><?php echo esc_html($card['secondary_label']); ?></button>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
