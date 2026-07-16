<?php
/**
 * Template Name: Clientes
 *
 * @package WVTodoz
 */

get_header();

$ctx = wv_internal_page_context();
$clientes = function_exists('wv_query_posts') ? wv_query_posts('wv_cliente') : [];
?>
<main class="wv-internal-page pt-40 section-canvas">
	<div class="site-container">
		<?php
		get_template_part('template-parts/internal/hero', null, [
			'eyebrow'      => $ctx['eyebrow'],
			'title_line'   => $ctx['title_line'],
			'title_accent' => $ctx['title_accent'],
			'lead'         => $ctx['lead'],
			'breadcrumb'   => __('Clientes', 'wvtodoz'),
		]);
		?>

		<?php if ($ctx['body'] !== '') : ?>
		<div class="wv-content-body prose prose-blog max-w-3xl mb-16" data-aos="fade-up">
			<?php echo wp_kses_post($ctx['body']); ?>
		</div>
		<?php endif; ?>

		<?php if ($clientes) : ?>
		<div class="wv-clientes-grid" data-aos="fade-up">
			<?php foreach ($clientes as $cliente) :
				$thumb = get_the_post_thumbnail_url($cliente->ID, 'medium') ?: '';
				if ($thumb === '') {
					continue;
				}
				?>
			<div class="wv-clientes-grid__item">
				<img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title($cliente)); ?>" loading="lazy" decoding="async">
			</div>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
		<p class="text-zinc-400 mb-16"><?php esc_html_e('Nenhum cliente cadastrado. Adicione logos no CPT Clientes.', 'wvtodoz'); ?></p>
		<?php endif; ?>

		<p class="text-center mt-16 mb-8">
			<a href="<?php echo esc_url(home_url('/#portfolio')); ?>" class="magnetic inline-block px-10 py-4 border border-white/15 rounded-full font-black text-[10px] uppercase tracking-widest hover:border-todoz/40 hover:text-todoz transition-colors"><?php esc_html_e('Ver cases no site', 'wvtodoz'); ?></a>
		</p>
	</div>

	<?php
	get_template_part('template-parts/internal/cta-band', null, [
		'show'      => $ctx['show_cta'],
		'before'    => $ctx['cta_before'],
		'highlight' => $ctx['cta_highlight'],
		'label'     => $ctx['cta_label'],
		'url'       => $ctx['cta_url'],
	]);
	?>
</main>
<?php
get_footer();
