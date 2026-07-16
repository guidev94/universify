<?php
/**
 * Blog posts index (when a Posts page is set).
 *
 * @package WVTodoz
 */

get_header();

$d = wv_defaults()['blog'];
$eyebrow = (string) wv_field('wv_blog_eyebrow', $d['eyebrow']);
$line = (string) wv_field('wv_blog_title_line', $d['title_line']);
$accent = (string) wv_field('wv_blog_title_accent', $d['title_accent']);
?>
<main class="pt-40 pb-32 section-canvas">
	<div class="site-container">
		<header class="mb-16 md:mb-20 text-center md:text-left" data-aos="fade-up">
			<span class="section-eyebrow text-todoz"><?php echo esc_html($eyebrow); ?></span>
			<h1 class="section-display">
				<span class="section-display-line"><?php echo esc_html($line); ?></span>
				<span class="section-display-accent"><?php echo esc_html($accent); ?><span class="section-display-dot" aria-hidden="true"></span></span>
			</h1>
		</header>

		<?php if (have_posts()) : ?>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
			<?php
			$i = 0;
			while (have_posts()) :
				the_post();
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
					<h2 class="text-xl md:text-2xl font-bold text-white mb-3 group-hover:text-todoz transition-colors text-left leading-snug"><?php the_title(); ?></h2>
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
			?>
		</div>
		<div class="flex justify-center">
			<?php
			the_posts_pagination([
				'mid_size'  => 2,
				'prev_text' => '←',
				'next_text' => '→',
			]);
			?>
		</div>
		<?php else : ?>
		<p class="text-zinc-400 text-center"><?php esc_html_e('Nenhum insight publicado ainda.', 'wvtodoz'); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
