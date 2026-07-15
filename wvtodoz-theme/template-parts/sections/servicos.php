<?php
/**
 * Serviços section.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['servicos'];
$process_d = wv_defaults()['process'];
$eyebrow = (string) wv_field('wv_servicos_eyebrow', $d['eyebrow']);
$line = (string) wv_field('wv_servicos_title_line', $d['title_line']);
$accent = (string) wv_field('wv_servicos_title_accent', $d['title_accent']);
$intro = (string) wv_field('wv_servicos_intro', $d['intro']);
$note = (string) wv_field('wv_servicos_note', $d['note']);
$quote = (string) wv_field('wv_servicos_quote_cta', $d['quote_cta']);

$cards = [];
$posts = wv_query_posts('wv_servico');
if ($posts) {
	foreach ($posts as $p) {
		$variant = function_exists('get_field') ? (string) get_field('wv_servico_variant', $p->ID) : 'featured';
		$title_html = function_exists('get_field') ? (string) get_field('wv_servico_title_html', $p->ID) : '';
		if ($title_html === '') {
			$title_html = esc_html(get_the_title($p));
		}
		$cards[] = [
			'variant' => $variant ?: 'featured',
			'icon' => function_exists('get_field') ? (string) get_field('wv_servico_icon', $p->ID) : 'ph-app-window',
			'title' => $title_html,
			'desc' => get_the_content(null, false, $p),
			'bullets' => function_exists('get_field') ? (string) get_field('wv_servico_bullets', $p->ID) : '',
			'cta' => function_exists('get_field') ? (string) get_field('wv_servico_cta', $p->ID) : '',
			'link' => function_exists('get_field') ? (string) get_field('wv_servico_link', $p->ID) : '',
			'cta_class' => $variant === 'tall' ? 'servicos-card-btn--accent' : '',
		];
	}
}
if (!$cards) {
	$cards = wv_defaults()['servicos_cards'];
}

$process = [];
for ($i = 1; $i <= 4; $i++) {
	$process[] = [
		'title' => (string) wv_field("wv_process_{$i}_title", $process_d[$i - 1]['title']),
		'desc' => (string) wv_field("wv_process_{$i}_desc", $process_d[$i - 1]['desc']),
	];
}
?>
<section id="servicos" class="section-canvas section-glow servicos-section relative">
	<div class="site-container">
		<div class="servicos-header" data-aos="fade-up">
			<div class="servicos-header-copy text-center md:text-left">
				<span class="servicos-eyebrow"><?php echo esc_html($eyebrow); ?></span>
				<h2 class="section-display">
					<span class="section-display-line"><?php echo esc_html($line); ?></span>
					<span class="section-display-accent"><?php echo esc_html($accent); ?><span class="section-display-dot" aria-hidden="true"></span></span>
				</h2>
				<p class="servicos-intro"><?php echo esc_html($intro); ?></p>
			</div>
			<div class="servicos-header-cta text-center md:text-right" data-aos="fade-up">
				<p class="servicos-header-note"><?php echo esc_html($note); ?></p>
				<button type="button" class="open-modal magnetic servicos-quote-link">
					<?php echo esc_html($quote); ?>
					<i class="ph ph-arrow-right" aria-hidden="true"></i>
				</button>
			</div>
		</div>

		<div class="servicos-grid">
			<?php foreach ($cards as $i => $card) :
				$variant = $card['variant'] ?? 'featured';
				$mod = 'servicos-card--' . sanitize_html_class($variant);
				$delay = $i ? ($i * 50 + 50) : 0;
				$bullets = array_filter(array_map('trim', explode("\n", (string) ($card['bullets'] ?? ''))));
				$icon = preg_replace('/^ph\s+/', '', (string) ($card['icon'] ?? 'ph-app-window'));
				if (strpos($icon, 'ph-') !== 0) {
					$icon = 'ph-' . ltrim($icon, '-');
				}
				$title_sm = in_array($variant, ['mobile', 'ia', 'produtos'], true);
				?>
			<article class="service-card servicos-card <?php echo esc_attr($mod); ?>" data-aos="fade-up"<?php echo $delay ? ' data-aos-delay="' . esc_attr((string) $delay) . '"' : ''; ?>>
				<?php if ($variant === 'mobile') : ?>
				<div class="servicos-card-top">
					<div class="servicos-card-icon servicos-card-icon--sm" aria-hidden="true"><i class="ph <?php echo esc_attr($icon); ?>"></i></div>
					<button type="button" class="open-modal magnetic servicos-card-icon-btn" aria-label="<?php esc_attr_e('Falar sobre este serviço', 'wvtodoz'); ?>">
						<i class="ph ph-arrow-up-right" aria-hidden="true"></i>
					</button>
				</div>
				<?php else : ?>
				<div class="servicos-card-icon<?php echo $title_sm ? ' servicos-card-icon--sm' : ''; ?>" aria-hidden="true">
					<i class="ph <?php echo esc_attr($icon); ?>"></i>
				</div>
				<?php endif; ?>
				<div class="servicos-card-body">
					<h3 class="servicos-card-title<?php echo $title_sm ? ' servicos-card-title--sm' : ''; ?>"><?php echo wv_kses_title((string) $card['title']); ?></h3>
					<p class="servicos-card-desc"><?php echo esc_html(wp_strip_all_tags((string) $card['desc'])); ?></p>
					<?php if ($bullets) : ?>
					<ul class="servicos-card-list">
						<?php foreach ($bullets as $b) : ?>
						<li><?php echo esc_html($b); ?></li>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>
				</div>
				<?php if ($variant === 'produtos' && !empty($card['link'])) : ?>
				<a href="<?php echo esc_url($card['link']); ?>" class="servicos-inline-link">
					<?php echo esc_html((string) ($card['cta'] ?: __('Conhecer os produtos', 'wvtodoz'))); ?>
					<i class="ph ph-arrow-right" aria-hidden="true"></i>
				</a>
				<?php elseif (!empty($card['cta'])) : ?>
				<button type="button" class="open-modal magnetic servicos-card-btn <?php echo esc_attr((string) ($card['cta_class'] ?? '')); ?>"><?php echo esc_html((string) $card['cta']); ?></button>
				<?php endif; ?>
			</article>
			<?php endforeach; ?>
		</div>

		<ol class="servicos-process" data-aos="fade-up">
			<?php foreach ($process as $i => $step) : ?>
			<li class="servicos-process-step">
				<span class="servicos-process-num"><?php echo esc_html(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)); ?></span>
				<div>
					<h4 class="servicos-process-title"><?php echo esc_html($step['title']); ?></h4>
					<p class="servicos-process-desc"><?php echo esc_html($step['desc']); ?></p>
				</div>
			</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
