<?php
/**
 * Template Name: Localização
 *
 * @package WVTodoz
 */

get_header();

$ctx = wv_internal_page_context();
$address = $ctx['address'] !== '' ? $ctx['address'] : (string) wv_field('wv_footer_address', "Av. Morumbi, 8435 cj.104\nBrooklin, São Paulo - SP");
$maps = $ctx['maps_url'] !== '' ? $ctx['maps_url'] : (string) wv_field('wv_footer_maps', 'https://www.google.com/maps');
$phone = $ctx['phone'] !== '' ? $ctx['phone'] : wv_company_phone();
$email = wv_company_email();
$tel = preg_replace('/\D+/', '', $phone) ?: '';
?>
<main class="wv-internal-page pt-40 section-canvas">
	<div class="site-container">
		<?php
		get_template_part('template-parts/internal/hero', null, [
			'eyebrow'      => $ctx['eyebrow'],
			'title_line'   => $ctx['title_line'],
			'title_accent' => $ctx['title_accent'],
			'lead'         => $ctx['lead'],
			'breadcrumb'   => __('Localização', 'wvtodoz'),
		]);
		?>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start mb-20">
			<div class="lg:col-span-5 space-y-8" data-aos="fade-up">
				<?php if ($ctx['body'] !== '') : ?>
				<div class="wv-content-body prose prose-blog max-w-none"><?php echo wp_kses_post($ctx['body']); ?></div>
				<?php endif; ?>
				<div class="wv-info-panel">
					<h2 class="wv-info-panel__title"><?php esc_html_e('Endereço', 'wvtodoz'); ?></h2>
					<p class="wv-info-panel__text"><?php echo nl2br(esc_html($address)); ?></p>
					<?php if ($phone !== '') : ?>
					<p class="wv-info-panel__meta">
						<a href="<?php echo esc_url('tel:' . $tel); ?>" class="hover:text-todoz"><?php echo esc_html($phone); ?></a>
					</p>
					<?php endif; ?>
					<?php if ($email !== '') : ?>
					<p class="wv-info-panel__meta">
						<a href="<?php echo esc_url('mailto:' . $email); ?>" class="hover:text-todoz"><?php echo esc_html($email); ?></a>
					</p>
					<?php endif; ?>
					<a href="<?php echo esc_url($maps); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 mt-6 text-todoz text-[10px] font-black uppercase tracking-widest border-b border-todoz/30 pb-1">
						<?php esc_html_e('Ver no Google Maps', 'wvtodoz'); ?> <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
					</a>
				</div>
			</div>
			<div class="lg:col-span-7" data-aos="fade-up" data-aos-delay="100">
				<div class="wv-map-frame">
					<iframe
						title="<?php esc_attr_e('Mapa — WV Todoz', 'wvtodoz'); ?>"
						src="https://maps.google.com/maps?q=<?php echo esc_attr(rawurlencode(wp_strip_all_tags(str_replace("\n", ', ', $address)))); ?>&z=15&output=embed"
						loading="lazy"
						referrerpolicy="no-referrer-when-downgrade"
						allowfullscreen></iframe>
				</div>
			</div>
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
