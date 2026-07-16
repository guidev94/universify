<?php
/**
 * Template Name: Serviços
 *
 * @package WVTodoz
 */

get_header();

$ctx = wv_internal_page_context();
$cards = function_exists('wv_query_posts') ? wv_query_posts('wv_servico') : [];
?>
<main class="wv-internal-page pt-40 section-canvas">
	<div class="site-container">
		<?php
		get_template_part('template-parts/internal/hero', null, [
			'eyebrow'      => $ctx['eyebrow'],
			'title_line'   => $ctx['title_line'],
			'title_accent' => $ctx['title_accent'],
			'lead'         => $ctx['lead'],
			'breadcrumb'   => __('Serviços', 'wvtodoz'),
		]);
		?>

		<?php if ($ctx['body'] !== '') : ?>
		<div class="wv-content-body prose prose-blog max-w-3xl mb-16 md:mb-20" data-aos="fade-up">
			<?php echo wp_kses_post($ctx['body']); ?>
		</div>
		<?php endif; ?>

		<?php if ($cards) : ?>
		<div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 mb-8" data-aos="fade-up">
			<?php
			foreach ($cards as $card_post) :
				$pid = (int) $card_post->ID;
				$icon = (string) wv_field('wv_servico_icon', 'ph-robot', $pid);
				$title_html = (string) wv_field('wv_servico_title_html', get_the_title($pid), $pid);
				$desc = get_post_field('post_content', $pid);
				$bullets = (string) wv_field('wv_servico_bullets', '', $pid);
				$cta = (string) wv_field('wv_servico_cta', __('Saiba mais', 'wvtodoz'), $pid);
				$bullet_lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $bullets) ?: []));
				?>
			<article class="wv-service-card">
				<div class="wv-service-card__icon" aria-hidden="true"><i class="ph-bold <?php echo esc_attr($icon); ?>"></i></div>
				<h2 class="wv-service-card__title"><?php echo wp_kses_post($title_html); ?></h2>
				<?php if ($desc !== '') : ?>
				<p class="wv-service-card__desc"><?php echo esc_html(wp_strip_all_tags($desc)); ?></p>
				<?php endif; ?>
				<?php if ($bullet_lines) : ?>
				<ul class="wv-service-card__list">
					<?php foreach ($bullet_lines as $line) : ?>
					<li><?php echo esc_html($line); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
				<button type="button" class="open-modal magnetic wv-service-card__cta" data-wv-section="servicos-page" data-wv-lead-type="orcamento"><?php echo esc_html($cta); ?></button>
			</article>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
		<p class="text-zinc-400 mb-16"><?php esc_html_e('Nenhum serviço publicado ainda. Cadastre no CPT Serviços via painel WV Todoz.', 'wvtodoz'); ?></p>
		<?php endif; ?>
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
