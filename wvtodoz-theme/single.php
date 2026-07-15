<?php
/**
 * Single post (insights).
 *
 * @package WVTodoz
 */

get_header();
?>
<main id="main-wrapper" class="section-canvas py-32">
	<div class="site-container max-w-3xl mx-auto">
		<?php while (have_posts()) : the_post(); ?>
		<article <?php post_class(); ?>>
			<span class="text-todoz text-[10px] font-black tracking-[0.4em] uppercase block mb-4"><?php echo esc_html(get_the_date('d M Y')); ?></span>
			<h1 class="section-display mb-8"><?php the_title(); ?></h1>
			<?php if (has_post_thumbnail()) : ?>
			<div class="rounded-[2rem] overflow-hidden border border-white/10 mb-12">
				<?php the_post_thumbnail('large', ['class' => 'w-full h-auto']); ?>
			</div>
			<?php endif; ?>
			<div class="prose prose-invert max-w-none text-zinc-300 leading-relaxed space-y-6">
				<?php the_content(); ?>
			</div>
		</article>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
