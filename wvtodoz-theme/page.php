<?php
/**
 * Generic page template.
 *
 * @package WVTodoz
 */

get_header();
?>
<main id="main-wrapper" class="section-canvas py-32">
	<div class="site-container max-w-3xl mx-auto">
		<?php while (have_posts()) : the_post(); ?>
		<article <?php post_class(); ?>>
			<h1 class="section-display mb-10"><?php the_title(); ?></h1>
			<div class="text-zinc-300 leading-relaxed space-y-6">
				<?php the_content(); ?>
			</div>
		</article>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
