<?php
/**
 * Search results — branded WV Todoz.
 *
 * @package WVTodoz
 */

get_header();

$query = get_search_query();
$home = home_url('/');
$blog_url = function_exists('wv_insights_url') ? wv_insights_url() : ($home . '#blog');
?>
<main class="pt-40 pb-32 section-canvas">
	<div class="site-container">
		<nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 mb-12" data-aos="fade-right" aria-label="<?php esc_attr_e('Breadcrumb', 'wvtodoz'); ?>">
			<a href="<?php echo esc_url($home); ?>" class="hover:text-todoz"><?php esc_html_e('Home', 'wvtodoz'); ?></a>
			<i class="ph ph-caret-right" aria-hidden="true"></i>
			<span class="text-todoz/50"><?php esc_html_e('Busca', 'wvtodoz'); ?></span>
		</nav>

		<header class="mb-12 md:mb-16" data-aos="fade-up">
			<span class="section-eyebrow text-todoz"><?php esc_html_e('Busca', 'wvtodoz'); ?></span>
			<h1 class="section-display mb-6">
				<?php
				if ($query !== '') {
					printf(
						/* translators: %s search query */
						esc_html__('Resultados para “%s”', 'wvtodoz'),
						esc_html($query)
					);
				} else {
					esc_html_e('Buscar insights', 'wvtodoz');
				}
				?>
			</h1>
			<form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="blog-widget__search relative max-w-xl">
				<input type="search" name="s" value="<?php echo esc_attr($query); ?>" class="blog-widget__input w-full rounded-2xl px-6 py-4 outline-none focus:border-todoz transition-all text-sm pr-12" placeholder="<?php esc_attr_e('O que você procura?', 'wvtodoz'); ?>">
				<button type="submit" class="absolute right-4 top-1/2 -translate-y-1/2 text-todoz text-xl" aria-label="<?php esc_attr_e('Buscar', 'wvtodoz'); ?>">
					<i class="ph ph-magnifying-glass" aria-hidden="true"></i>
				</button>
			</form>
		</header>

		<?php if (have_posts()) : ?>
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
			<?php
			$i = 0;
			while (have_posts()) :
				the_post();
				$cats = get_the_category();
				$tag = $cats ? $cats[0]->name : __('Insights', 'wvtodoz');
				$img = get_the_post_thumbnail_url(get_the_ID(), 'wv-blog') ?: (WVTODOZ_URI . '/assets/img/blog/insight-tech.jpg');
				$delay = ($i % 3) * 100;
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
		<div class="wv-empty-state max-w-xl" data-aos="fade-up">
			<p class="text-zinc-400 text-lg mb-8">
				<?php esc_html_e('Nenhum resultado encontrado. Tente outros termos ou explore os Insights.', 'wvtodoz'); ?>
			</p>
			<div class="flex flex-wrap gap-4">
				<a href="<?php echo esc_url($blog_url); ?>" class="magnetic inline-block px-8 py-4 bg-todoz text-black font-black rounded-full text-[10px] uppercase tracking-widest"><?php esc_html_e('Ver Insights', 'wvtodoz'); ?></a>
				<a href="<?php echo esc_url($home); ?>" class="magnetic inline-block px-8 py-4 border border-white/15 rounded-full font-black text-[10px] uppercase tracking-widest hover:border-todoz/40 hover:text-todoz transition-colors"><?php esc_html_e('Voltar à Home', 'wvtodoz'); ?></a>
			</div>
		</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
