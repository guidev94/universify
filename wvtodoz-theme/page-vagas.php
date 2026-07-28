<?php
/**
 * Template Name: Vagas
 *
 * @package WVTodoz
 */

get_header();

$ctx = wv_internal_page_context();
$body = $ctx['body'];
if ($body === '') {
	ob_start();
	the_content();
	$body = (string) ob_get_clean();
}
?>
<main class="wv-internal-page pt-40 section-canvas">
	<div class="site-container">
		<?php
		get_template_part('template-parts/internal/hero', null, [
			'eyebrow'      => $ctx['eyebrow'],
			'title_line'   => $ctx['title_line'],
			'title_accent' => $ctx['title_accent'],
			'lead'         => $ctx['lead'],
			'breadcrumb'   => __('Vagas', 'wvtodoz'),
		]);
		?>
		<article class="wv-content-page max-w-3xl mb-16" data-aos="fade-up">
			<div class="wv-content-body prose prose-blog max-w-none">
				<?php echo wp_kses_post($body); ?>
			</div>
		</article>
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
