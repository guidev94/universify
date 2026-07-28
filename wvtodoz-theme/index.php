<?php
/**
 * Fallback index — blog-style listing.
 *
 * @package WVTodoz
 */

if (is_home() || is_archive() || is_search()) {
	require get_template_directory() . '/home.php';
	return;
}

get_header();
?>
<main id="main-wrapper" class="section-canvas pt-40 pb-32">
	<div class="site-container max-w-3xl mx-auto">
		<?php if (have_posts()) : ?>
			<?php while (have_posts()) : the_post(); ?>
			<article <?php post_class('mb-16'); ?>>
				<span class="text-todoz text-[10px] font-black tracking-[0.4em] uppercase block mb-4"><?php echo esc_html(get_the_date('d M Y')); ?></span>
				<h1 class="section-display mb-8"><a href="<?php the_permalink(); ?>" class="hover:text-todoz"><?php the_title(); ?></a></h1>
				<div class="prose prose-blog max-w-none text-zinc-300 leading-relaxed">
					<?php the_excerpt(); ?>
				</div>
			</article>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
		<p class="text-zinc-400"><?php esc_html_e('Nenhum conteúdo encontrado.', 'wvtodoz'); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
