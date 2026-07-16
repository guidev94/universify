<?php
/**
 * Insights / blog cards.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['blog'];
$eyebrow = (string) wv_field('wv_blog_eyebrow', $d['eyebrow']);
$line = (string) wv_field('wv_blog_title_line', $d['title_line']);
$accent = (string) wv_field('wv_blog_title_accent', $d['title_accent']);
$cta = (string) wv_field('wv_blog_cta', $d['cta']);
$default_blog_url = function_exists('wv_insights_url') ? wv_insights_url() : home_url('/');
$cta_url = (string) wv_field('wv_blog_cta_url', $default_blog_url);

$q = new WP_Query([
	'post_type'      => 'post',
	'posts_per_page' => 3,
	'post_status'    => 'publish',
	'no_found_rows'  => true,
]);

$fallback = [];
foreach ((array) (wv_defaults()['insights'] ?? []) as $insight) {
	$img_rel = (string) ($insight['img'] ?? '');
	$fallback[] = [
		'title'   => (string) ($insight['title'] ?? ''),
		'tag'     => (string) ($insight['tag'] ?? 'Insights'),
		'excerpt' => (string) ($insight['excerpt'] ?? ''),
		'img'     => $img_rel !== '' ? (WVTODOZ_URI . '/assets/' . ltrim($img_rel, '/')) : (string) ($insight['img_url'] ?? ''),
		'date'    => !empty($insight['date']) ? date_i18n('d M Y', strtotime((string) $insight['date'])) : '',
		'url'     => !empty($insight['slug']) ? home_url('/' . trim((string) $insight['slug'], '/') . '/') : '#',
	];
}
?>
<section id="blog" class="section-canvas section-glow py-24 md:py-32">
	<div class="site-container">
		<div class="flex flex-col md:flex-row justify-between items-center md:items-end mb-12 md:mb-16 gap-8 text-center md:text-left">
			<div>
				<span class="section-eyebrow text-todoz"><?php echo esc_html($eyebrow); ?></span>
				<h2 class="section-display">
					<span class="section-display-line"><?php echo esc_html($line); ?></span>
					<span class="section-display-accent"><?php echo esc_html($accent); ?><span class="section-display-dot" aria-hidden="true"></span></span>
				</h2>
			</div>
		</div>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
			<?php
			if ($q->have_posts()) :
				$i = 0;
				while ($q->have_posts()) :
					$q->the_post();
					$cats = get_the_category();
					$tag = $cats ? $cats[0]->name : __('Insights', 'wvtodoz');
					$img = get_the_post_thumbnail_url(get_the_ID(), 'wv-blog') ?: (WVTODOZ_URI . '/assets/img/blog/insight-tech.jpg');
					$delay = $i * 100;
					?>
			<a href="<?php the_permalink(); ?>" class="blog-card group cursor-pointer text-left block" data-aos="fade-up"<?php echo $delay ? ' data-aos-delay="' . esc_attr((string) $delay) . '"' : ''; ?>>
				<div class="blog-card-media">
					<img src="<?php echo esc_url($img); ?>" alt="<?php the_title_attribute(); ?>" width="1200" height="675" loading="lazy" decoding="async" class="blog-card-image">
					<div class="blog-card-media-veil" aria-hidden="true"></div>
				</div>
				<div class="blog-card-body">
					<span class="blog-tag"><?php echo esc_html($tag); ?></span>
					<h4 class="text-xl md:text-2xl font-bold text-white mb-3 group-hover:text-todoz transition-colors text-left leading-snug"><?php the_title(); ?></h4>
					<p class="blog-excerpt mb-6 text-left"><?php echo esc_html(get_the_excerpt()); ?></p>
					<div class="blog-author">
						<div class="blog-author-left">
							<div class="blog-author-avatar" aria-hidden="true">WV</div>
							<span class="blog-author-name"><i class="ph ph-user"></i> <?php echo esc_html(get_the_author()); ?></span>
						</div>
						<span class="blog-author-meta"><i class="ph ph-calendar-blank"></i> <?php echo esc_html(get_the_date('d M Y')); ?></span>
					</div>
				</div>
			</a>
					<?php
					$i++;
				endwhile;
				wp_reset_postdata();
			else :
				foreach ($fallback as $i => $item) :
					$delay = $i * 100;
					?>
			<a href="<?php echo esc_url($item['url']); ?>" class="blog-card group cursor-pointer text-left block" data-aos="fade-up"<?php echo $delay ? ' data-aos-delay="' . esc_attr((string) $delay) . '"' : ''; ?>>
				<div class="blog-card-media">
					<img src="<?php echo esc_url($item['img']); ?>" alt="<?php echo esc_attr($item['title']); ?>" width="1200" height="675" loading="lazy" decoding="async" class="blog-card-image">
					<div class="blog-card-media-veil" aria-hidden="true"></div>
				</div>
				<div class="blog-card-body">
					<span class="blog-tag"><?php echo esc_html($item['tag']); ?></span>
					<h4 class="text-xl md:text-2xl font-bold text-white mb-3 group-hover:text-todoz transition-colors text-left leading-snug"><?php echo esc_html($item['title']); ?></h4>
					<p class="blog-excerpt mb-6 text-left"><?php echo esc_html($item['excerpt']); ?></p>
					<div class="blog-author">
						<div class="blog-author-left">
							<div class="blog-author-avatar" aria-hidden="true">WV</div>
							<span class="blog-author-name"><i class="ph ph-user"></i> WV_Todoz</span>
						</div>
						<span class="blog-author-meta"><i class="ph ph-calendar-blank"></i> <?php echo esc_html($item['date']); ?></span>
					</div>
				</div>
			</a>
				<?php endforeach;
			endif;
			?>
		</div>
		<div class="text-center">
			<a href="<?php echo esc_url($cta_url ?: home_url('/')); ?>" class="magnetic inline-block px-12 py-5 border border-white/10 rounded-full font-black text-xs uppercase tracking-[0.2em] hover:border-todoz/40 hover:text-todoz transition-colors"><?php echo esc_html($cta); ?></a>
		</div>
	</div>
</section>
