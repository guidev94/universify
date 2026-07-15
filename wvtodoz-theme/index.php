<?php
/**
 * Fallback index / blog listing.
 *
 * @package WVTodoz
 */

get_header();
?>
<main id="main-wrapper" class="section-canvas py-32">
	<div class="site-container">
		<h1 class="section-display mb-16">
			<span class="section-display-line"><?php esc_html_e('Insights', 'wvtodoz'); ?></span>
		</h1>
		<?php if (have_posts()) : ?>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
			<?php while (have_posts()) : the_post(); ?>
			<a href="<?php the_permalink(); ?>" class="blog-card group block">
				<?php if (has_post_thumbnail()) : ?>
				<div class="blog-card-media"><?php the_post_thumbnail('wv-blog', ['class' => 'blog-card-image']); ?></div>
				<?php endif; ?>
				<div class="blog-card-body">
					<h2 class="text-xl font-bold text-white group-hover:text-todoz"><?php the_title(); ?></h2>
					<p class="blog-excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
				</div>
			</a>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
		<?php else : ?>
		<p class="text-zinc-400"><?php esc_html_e('Nenhum conteúdo encontrado.', 'wvtodoz'); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
