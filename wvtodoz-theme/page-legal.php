<?php
/**
 * Template Name: Legal
 * Política de Privacidade / Termos de Uso.
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
<main class="wv-internal-page pt-40 pb-32 section-canvas">
	<div class="site-container">
		<?php
		get_template_part('template-parts/internal/hero', null, [
			'eyebrow'      => $ctx['eyebrow'],
			'title_line'   => $ctx['title_line'],
			'title_accent' => $ctx['title_accent'],
			'lead'         => $ctx['lead'],
			'breadcrumb'   => get_the_title(),
		]);
		?>
		<article class="wv-content-page max-w-3xl" data-aos="fade-up">
			<div class="wv-content-body prose prose-blog max-w-none">
				<?php echo wp_kses_post($body); ?>
			</div>
		</article>
	</div>
</main>
<?php
get_footer();
