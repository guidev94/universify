<?php
/**
 * Template Name: Equipe
 *
 * @package WVTodoz
 */

get_header();

$ctx = wv_internal_page_context();
$members = [];
for ($i = 1; $i <= 4; $i++) {
	$name = (string) wv_page_field("wv_page_team_{$i}_name", '', $ctx['page_id']);
	if ($name === '') {
		continue;
	}
	$members[] = [
		'name' => $name,
		'role' => (string) wv_page_field("wv_page_team_{$i}_role", '', $ctx['page_id']),
		'bio'  => (string) wv_page_field("wv_page_team_{$i}_bio", '', $ctx['page_id']),
	];
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
			'breadcrumb'   => __('Equipe', 'wvtodoz'),
		]);
		?>

		<?php if ($ctx['body'] !== '') : ?>
		<div class="wv-content-body prose prose-blog max-w-3xl mb-16" data-aos="fade-up">
			<?php echo wp_kses_post($ctx['body']); ?>
		</div>
		<?php endif; ?>

		<?php if ($members) : ?>
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 mb-16">
			<?php foreach ($members as $i => $m) :
				$initials = function_exists('mb_substr')
					? mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $m['name']) ?: 'WV', 0, 2))
					: strtoupper(substr(preg_replace('/\s+/', '', $m['name']) ?: 'WV', 0, 2));
				?>
			<article class="wv-team-card" data-aos="fade-up" data-aos-delay="<?php echo esc_attr((string) ($i * 80)); ?>">
				<div class="wv-team-card__avatar" aria-hidden="true"><?php echo esc_html($initials); ?></div>
				<h2 class="wv-team-card__name"><?php echo esc_html($m['name']); ?></h2>
				<?php if ($m['role'] !== '') : ?>
				<p class="wv-team-card__role"><?php echo esc_html($m['role']); ?></p>
				<?php endif; ?>
				<?php if ($m['bio'] !== '') : ?>
				<p class="wv-team-card__bio"><?php echo esc_html($m['bio']); ?></p>
				<?php endif; ?>
			</article>
			<?php endforeach; ?>
		</div>
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
