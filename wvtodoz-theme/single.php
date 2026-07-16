<?php
/**
 * Single post — design aligned with blog-post.html.
 *
 * @package WVTodoz
 */

get_header();

$show_sidebar = !function_exists('wv_opt') || (int) wv_opt('flag_blog_sidebar', 1) === 1;
$home = home_url('/');
$posts_page = (int) get_option('page_for_posts');
$blog_url = $posts_page > 0 ? (string) get_permalink($posts_page) : $home . '#blog';
?>
<main class="pt-40 pb-32">
	<div class="site-container">
		<?php while (have_posts()) : the_post();
			$cats = get_the_category();
			$cat_name = $cats ? $cats[0]->name : __('Insights', 'wvtodoz');
			$author = get_the_author() ?: 'WV_Todoz';
			$initials = function_exists('mb_substr')
				? mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $author) ?: 'WV', 0, 2))
				: strtoupper(substr(preg_replace('/\s+/', '', $author) ?: 'WV', 0, 2));
			?>
		<nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 mb-12" data-aos="fade-right" aria-label="<?php esc_attr_e('Breadcrumb', 'wvtodoz'); ?>">
			<a href="<?php echo esc_url($home); ?>" class="hover:text-todoz"><?php esc_html_e('Home', 'wvtodoz'); ?></a>
			<i class="ph ph-caret-right" aria-hidden="true"></i>
			<a href="<?php echo esc_url($blog_url); ?>" class="hover:text-todoz"><?php esc_html_e('Insights', 'wvtodoz'); ?></a>
			<i class="ph ph-caret-right" aria-hidden="true"></i>
			<span class="text-todoz/50"><?php echo esc_html($cat_name); ?></span>
		</nav>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-16">
			<div class="<?php echo $show_sidebar ? 'lg:col-span-8' : 'lg:col-span-10 lg:col-start-2'; ?>">
				<article <?php post_class(); ?> data-aos="fade-up">
					<header class="mb-16">
						<span class="inline-block px-4 py-1.5 rounded-full bg-todoz text-black text-[10px] font-black uppercase mb-8"><?php echo esc_html($cat_name); ?></span>
						<h1 class="text-5xl md:text-7xl font-black tracking-tighter leading-[0.9] mb-10"><?php the_title(); ?></h1>
						<div class="flex items-center gap-6 pt-6 border-t border-white/5">
							<div class="w-12 h-12 rounded-full bg-todoz text-black flex items-center justify-center font-black text-sm tracking-wide" aria-hidden="true"><?php echo esc_html($initials); ?></div>
							<div class="flex flex-col gap-1.5">
								<span class="flex items-center gap-2 text-white font-bold text-sm"><i class="ph ph-user text-todoz" aria-hidden="true"></i> <?php echo esc_html($author); ?></span>
								<span class="flex items-center gap-2 text-[10px] text-zinc-500 uppercase tracking-widest"><i class="ph ph-calendar-blank text-todoz" aria-hidden="true"></i> <?php echo esc_html(get_the_date('d M Y')); ?></span>
							</div>
						</div>
					</header>

					<?php if (has_post_thumbnail()) : ?>
					<figure class="rounded-[3rem] overflow-hidden mb-16 border border-white/5">
						<?php the_post_thumbnail('large', ['class' => 'w-full h-auto grayscale hover:grayscale-0 transition-all duration-1000']); ?>
					</figure>
					<?php endif; ?>

					<div class="prose prose-blog max-w-none">
						<?php the_content(); ?>
					</div>

					<?php
					$tags = get_the_tags();
					if ($tags) :
						?>
					<div class="mt-20 pt-10 border-t border-white/5 flex flex-wrap gap-4">
						<?php foreach ($tags as $tag) : ?>
						<span class="px-5 py-2 bg-zinc-900 rounded-full text-[10px] font-black uppercase text-zinc-400">#<?php echo esc_html($tag->name); ?></span>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</article>
			</div>

			<?php if ($show_sidebar) : ?>
			<aside class="lg:col-span-4 space-y-16" data-aos="fade-left" data-aos-delay="200">
				<div class="p-10 bg-zinc-900/30 border border-white/5 rounded-[3rem] backdrop-blur-xl">
					<h5 class="text-white font-black text-xs uppercase tracking-[0.3em] mb-8"><?php esc_html_e('Buscar Insight', 'wvtodoz'); ?></h5>
					<form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="relative">
						<input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 outline-none focus:border-todoz transition-all text-sm pr-12" placeholder="<?php esc_attr_e('O que você procura?', 'wvtodoz'); ?>">
						<button type="submit" class="absolute right-4 top-1/2 -translate-y-1/2 text-todoz text-xl" aria-label="<?php esc_attr_e('Buscar', 'wvtodoz'); ?>">
							<i class="ph ph-magnifying-glass" aria-hidden="true"></i>
						</button>
					</form>
				</div>

				<div class="p-10 bg-zinc-900/30 border border-white/5 rounded-[3rem]">
					<h5 class="text-white font-black text-xs uppercase tracking-[0.3em] mb-8"><?php esc_html_e('Categorias', 'wvtodoz'); ?></h5>
					<ul class="space-y-4">
						<?php
						$categories = get_categories(['hide_empty' => true, 'number' => 8]);
						if ($categories) :
							foreach ($categories as $category) :
								?>
						<li>
							<a href="<?php echo esc_url(get_category_link($category)); ?>" class="flex justify-between items-center text-zinc-500 font-bold text-sm hover:text-todoz transition-all">
								<span><?php echo esc_html($category->name); ?></span>
								<span class="text-[10px] bg-zinc-800 px-2 py-1 rounded"><?php echo esc_html((string) $category->count); ?></span>
							</a>
						</li>
								<?php
							endforeach;
						else :
							?>
						<li><a href="<?php echo esc_url($blog_url); ?>" class="flex justify-between items-center text-zinc-500 font-bold text-sm hover:text-todoz transition-all"><span><?php esc_html_e('Insights', 'wvtodoz'); ?></span></a></li>
						<?php endif; ?>
					</ul>
				</div>

				<div class="p-10 bg-zinc-900/30 border border-white/5 rounded-[3rem]">
					<h5 class="text-white font-black text-xs uppercase tracking-[0.3em] mb-8"><?php esc_html_e('Mais Lidos', 'wvtodoz'); ?></h5>
					<div class="space-y-8">
						<?php
						$recent = new WP_Query([
							'post_type'           => 'post',
							'posts_per_page'      => 3,
							'post__not_in'        => [get_the_ID()],
							'ignore_sticky_posts' => true,
							'no_found_rows'       => true,
						]);
						if ($recent->have_posts()) :
							while ($recent->have_posts()) :
								$recent->the_post();
								$thumb = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: (WVTODOZ_URI . '/assets/img/blog/insight-tech.jpg');
								?>
						<a href="<?php the_permalink(); ?>" class="flex gap-4 group">
							<div class="w-16 h-16 rounded-2xl overflow-hidden flex-shrink-0 border border-white/5">
								<img src="<?php echo esc_url($thumb); ?>" alt="" class="w-full h-full object-cover" width="64" height="64" loading="lazy">
							</div>
							<div>
								<h6 class="text-white font-bold text-sm leading-tight group-hover:text-todoz transition-colors"><?php the_title(); ?></h6>
								<span class="text-[9px] text-zinc-600 uppercase font-black"><?php echo esc_html(get_the_date('F Y')); ?></span>
							</div>
						</a>
								<?php
							endwhile;
							wp_reset_postdata();
						endif;
						?>
					</div>
				</div>

				<div class="p-10 bg-todoz rounded-[3rem] text-black">
					<h5 class="text-black font-black text-xs uppercase tracking-[0.3em] mb-4"><?php esc_html_e('Fale conosco', 'wvtodoz'); ?></h5>
					<p class="font-bold text-sm mb-8 leading-tight"><?php esc_html_e('Quer transformar operação com IA? Vamos conversar.', 'wvtodoz'); ?></p>
					<button type="button" class="open-modal w-full py-4 bg-black text-todoz font-black rounded-2xl text-[10px] uppercase tracking-widest" data-wv-section="blog" data-wv-lead-type="contato"><?php esc_html_e('Abrir contato', 'wvtodoz'); ?></button>
				</div>
			</aside>
			<?php endif; ?>
		</div>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
