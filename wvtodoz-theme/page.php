<?php
/**
 * Generic page template — content pages (legal, serviços, etc.).
 *
 * @package WVTodoz
 */

get_header();
?>
<main class="pt-40 pb-32 section-canvas">
	<div class="site-container">
		<?php while (have_posts()) : the_post(); ?>
		<nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 mb-12" data-aos="fade-right" aria-label="<?php esc_attr_e('Breadcrumb', 'wvtodoz'); ?>">
			<a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-todoz"><?php esc_html_e('Home', 'wvtodoz'); ?></a>
			<i class="ph ph-caret-right" aria-hidden="true"></i>
			<span class="text-todoz/50"><?php the_title(); ?></span>
		</nav>
		<article <?php post_class('wv-content-page max-w-3xl'); ?> data-aos="fade-up">
			<header class="mb-12">
				<span class="section-eyebrow text-todoz"><?php esc_html_e('WV Todoz', 'wvtodoz'); ?></span>
				<h1 class="section-display"><?php the_title(); ?></h1>
			</header>
			<div class="wv-content-body prose prose-blog max-w-none">
				<?php the_content(); ?>
			</div>
		</article>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
