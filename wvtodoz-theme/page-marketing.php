<?php
/**
 * Template Name: Marketing Digital
 *
 * @package WVTodoz
 */

get_header();

$ctx = wv_internal_page_context();
$benefits = [];
for ($i = 1; $i <= 3; $i++) {
	$title = (string) wv_page_field("wv_page_b{$i}_title", '', $ctx['page_id']);
	if ($title === '') {
		continue;
	}
	$benefits[] = [
		'title' => $title,
		'text'  => (string) wv_page_field("wv_page_b{$i}_text", '', $ctx['page_id']),
	];
}
$img = $ctx['image'];
$img_url = is_array($img) ? (string) ($img['url'] ?? '') : '';
?>
<main class="wv-internal-page pt-40 section-canvas">
	<div class="site-container">
		<?php
		get_template_part('template-parts/internal/hero', null, [
			'eyebrow'      => $ctx['eyebrow'],
			'title_line'   => $ctx['title_line'],
			'title_accent' => $ctx['title_accent'],
			'lead'         => $ctx['lead'],
			'breadcrumb'   => __('Marketing Digital', 'wvtodoz'),
		]);
		?>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start mb-16">
			<div class="lg:col-span-7" data-aos="fade-up">
				<?php if ($ctx['body'] !== '') : ?>
				<div class="wv-content-body prose prose-blog max-w-none mb-12">
					<?php echo wp_kses_post($ctx['body']); ?>
				</div>
				<?php endif; ?>
				<?php if ($benefits) : ?>
				<div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
					<?php foreach ($benefits as $i => $b) : ?>
					<div class="wv-benefit-card" data-aos="fade-up" data-aos-delay="<?php echo esc_attr((string) ($i * 80)); ?>">
						<span class="wv-benefit-card__index" aria-hidden="true">0<?php echo esc_html((string) ($i + 1)); ?></span>
						<h2 class="wv-benefit-card__title"><?php echo esc_html($b['title']); ?></h2>
						<p class="wv-benefit-card__text"><?php echo esc_html($b['text']); ?></p>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
			<?php if ($img_url !== '') : ?>
			<div class="lg:col-span-5" data-aos="fade-up" data-aos-delay="100">
				<figure class="wv-page-figure">
					<img src="<?php echo esc_url($img_url); ?>" alt="" loading="lazy" decoding="async">
				</figure>
			</div>
			<?php endif; ?>
		</div>
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
