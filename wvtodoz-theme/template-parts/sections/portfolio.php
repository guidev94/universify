<?php
/**
 * Portfolio / cases horizontal rail.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['portfolio'];
$eyebrow = (string) wv_field('wv_portfolio_eyebrow', $d['eyebrow']);
$line = (string) wv_field('wv_portfolio_title_line', $d['title_line']);
$accent = (string) wv_field('wv_portfolio_title_accent', $d['title_accent']);
$lead = (string) wv_field('wv_portfolio_lead', $d['lead']);
$cta = (string) wv_field('wv_portfolio_cta', $d['cta']);

$cases = [];
$posts = wv_query_posts('wv_case');
if ($posts) {
	foreach ($posts as $p) {
		$img = get_the_post_thumbnail_url($p, 'wv-case') ?: get_the_post_thumbnail_url($p, 'full');
		$cases[] = [
			'title' => get_the_title($p),
			'category' => function_exists('get_field') ? (string) get_field('wv_case_category', $p->ID) : '',
			'img' => $img ?: '',
			'desc' => wp_strip_all_tags(get_the_content(null, false, $p)),
			'meta_strong' => function_exists('get_field') ? (string) get_field('wv_case_meta_strong', $p->ID) : '',
			'meta_p' => function_exists('get_field') ? (string) get_field('wv_case_meta_desc', $p->ID) : '',
		];
	}
}
if (!$cases) {
	$cases = wv_defaults()['cases'];
}
?>
<section id="portfolio" class="portfolio-section">
	<div class="site-container mb-12 md:mb-20">
		<div class="flex flex-col md:flex-row justify-between items-center md:items-end gap-8 md:gap-10 text-center md:text-left">
			<div>
				<span class="section-eyebrow text-todoz"><?php echo esc_html($eyebrow); ?></span>
				<h2 class="section-display">
					<span class="section-display-line"><?php echo esc_html($line); ?></span>
					<span class="section-display-accent"><?php echo esc_html($accent); ?><span class="section-display-dot" aria-hidden="true"></span></span>
				</h2>
				<p class="text-zinc-500 mt-5 max-w-xl mx-auto md:mx-0 text-sm md:text-base"><?php echo esc_html($lead); ?></p>
			</div>
			<div class="pb-2">
				<a href="<?php echo esc_url(home_url('/#portfolio')); ?>" class="magnetic px-10 py-4 border border-white/10 rounded-full font-bold uppercase tracking-widest text-xs text-white inline-block hover:border-todoz/40 hover:text-todoz transition-colors"><?php echo esc_html($cta); ?></a>
			</div>
		</div>
	</div>

	<div id="portfolio-rail" class="portfolio-rail" data-lenis-prevent>
		<div id="portfolio-horizontal" class="portfolio-track">
			<?php foreach ($cases as $i => $case) :
				$idx = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
				?>
			<article class="case-item open-case cursor-pointer"
				data-case-title="<?php echo esc_attr($case['title']); ?>"
				data-case-category="<?php echo esc_attr($case['category']); ?>"
				data-case-img="<?php echo esc_url($case['img']); ?>"
				data-case-desc="<?php echo esc_attr($case['desc']); ?>">
				<div class="case-media">
					<img src="<?php echo esc_url($case['img']); ?>" alt="<?php echo esc_attr($case['title']); ?>" class="case-media-img">
					<div class="case-media-overlay"></div>
					<div class="case-card-content">
						<div class="case-card-copy">
							<span class="case-tag"><?php echo esc_html($case['category']); ?></span>
							<h4><?php echo esc_html($case['title']); ?></h4>
						</div>
						<button type="button" class="case-open-btn" aria-label="<?php echo esc_attr(sprintf(/* translators: %s case title */ __('Abrir case %s', 'wvtodoz'), $case['title'])); ?>">
							<i class="ph ph-plus"></i>
						</button>
					</div>
				</div>
				<div class="case-meta">
					<div class="case-meta-text">
						<strong><?php echo esc_html($case['meta_strong'] ?: $case['category']); ?></strong>
						<p><?php echo esc_html($case['meta_p'] ?: wp_trim_words($case['desc'], 16)); ?></p>
					</div>
					<span class="case-index"><?php echo esc_html($idx); ?></span>
				</div>
			</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
