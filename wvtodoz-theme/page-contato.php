<?php
/**
 * Template Name: Contato
 * Contact page — form submits to wv_lead (type contato) with source_page.
 *
 * @package WVTodoz
 */

get_header();

$ctx = wv_internal_page_context();
$d = wv_defaults();
$contato = $d['contato'];

// Page ACF first, then Home contact fields as fallback.
$before = $ctx['title_line'] !== '' ? $ctx['title_line'] : (string) wv_field('wv_contato_title_before', $contato['title_before']);
$highlight = $ctx['title_accent'] !== '' ? $ctx['title_accent'] : (string) wv_field('wv_contato_title_highlight', $contato['title_highlight']);
$lead = $ctx['lead'] !== '' ? $ctx['lead'] : __('Conte seu desafio. Retornamos com a melhor abordagem em software, IA e integrações.', 'wvtodoz');
$eyebrow = $ctx['eyebrow'] !== '' ? $ctx['eyebrow'] : __('Fale conosco', 'wvtodoz');
$phone = (string) wv_page_field('wv_page_phone', (string) wv_field('wv_contato_phone', $contato['phone']), $ctx['page_id']);
$tel = (string) wv_field('wv_contato_tel', $contato['tel']);
if ($phone !== '' && preg_replace('/\D+/', '', $phone) !== '') {
	$tel = preg_replace('/\D+/', '', $phone) ?: $tel;
}
$wa = wv_whatsapp_url();
$email = wv_company_email();

$label_name = (string) wv_field('wv_modal_label_name', 'Nome Completo');
$label_email = (string) wv_field('wv_modal_label_email', 'E-mail Corporativo');
$label_phone = (string) wv_field('wv_modal_label_phone', 'Telefone / WhatsApp');
$label_msg = (string) wv_field('wv_modal_label_message', 'Conte seu desafio');
$ph_name = (string) wv_field('wv_modal_placeholder_name', 'João Silva');
$ph_email = (string) wv_field('wv_modal_placeholder_email', 'exemplo@empresa.com.br');
$ph_phone = (string) wv_field('wv_modal_placeholder_phone', '(11) 99999-9999');
$ph_msg = (string) wv_field('wv_modal_placeholder_message', 'Quero criar um agente de IA para...');
$submit = (string) wv_field('wv_modal_submit', 'Enviar Proposta');
?>
<main class="wv-internal-page pt-40 pb-32 section-canvas">
	<div class="site-container">
		<?php
		get_template_part('template-parts/internal/hero', null, [
			'eyebrow'      => $eyebrow,
			'title_line'   => $before,
			'title_accent' => $highlight,
			'lead'         => $lead,
			'breadcrumb'   => __('Contato', 'wvtodoz'),
		]);
		?>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-16 lg:gap-20 items-start">
			<div class="lg:col-span-5" data-aos="fade-up">
				<?php if ($ctx['body'] !== '') : ?>
				<div class="wv-content-body prose prose-blog max-w-none mb-10">
					<?php echo wp_kses_post($ctx['body']); ?>
				</div>
				<?php endif; ?>
				<ul class="space-y-5 text-sm font-bold text-zinc-400">
					<?php if ($phone !== '') : ?>
					<li class="flex items-center gap-3">
						<span class="w-10 h-10 rounded-2xl bg-todoz/10 border border-todoz/30 flex items-center justify-center text-todoz"><i class="ph-bold ph-phone" aria-hidden="true"></i></span>
						<a class="hover:text-todoz transition-colors" href="<?php echo esc_url('tel:' . preg_replace('/\D+/', '', $tel)); ?>"><?php echo esc_html($phone); ?></a>
					</li>
					<?php endif; ?>
					<?php if ($email !== '') : ?>
					<li class="flex items-center gap-3">
						<span class="w-10 h-10 rounded-2xl bg-todoz/10 border border-todoz/30 flex items-center justify-center text-todoz"><i class="ph-bold ph-envelope" aria-hidden="true"></i></span>
						<a class="hover:text-todoz transition-colors" href="<?php echo esc_url('mailto:' . $email); ?>"><?php echo esc_html($email); ?></a>
					</li>
					<?php endif; ?>
					<li class="flex items-center gap-3">
						<span class="w-10 h-10 rounded-2xl bg-todoz/10 border border-todoz/30 flex items-center justify-center text-todoz"><i class="ph-bold ph-whatsapp-logo" aria-hidden="true"></i></span>
						<a class="hover:text-todoz transition-colors" href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('WhatsApp', 'wvtodoz'); ?></a>
					</li>
				</ul>
			</div>

			<div class="lg:col-span-7" data-aos="fade-up" data-aos-delay="100">
				<div class="wv-lead-card">
					<form id="contato-page-form" class="wv-lead-form space-y-6 text-left" novalidate data-wv-close-on-success="0">
						<input type="hidden" name="type" value="contato">
						<input type="hidden" name="source_section" value="contato-page">
						<input type="hidden" name="source_page" value="">
						<input type="hidden" name="source_title" value="">
						<input type="hidden" name="utm_source" value="">
						<input type="hidden" name="utm_medium" value="">
						<input type="hidden" name="utm_campaign" value="">
						<div class="wv-hp" aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
							<label for="wv_company_page"><?php esc_html_e('Empresa', 'wvtodoz'); ?></label>
							<input type="text" name="wv_company" id="wv_company_page" tabindex="-1" autocomplete="off">
						</div>
						<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
							<div class="space-y-2">
								<label class="wv-lead-label" for="wv-page-nome"><?php echo esc_html($label_name); ?></label>
								<input id="wv-page-nome" name="nome" type="text" required class="wv-lead-input" placeholder="<?php echo esc_attr($ph_name); ?>">
							</div>
							<div class="space-y-2">
								<label class="wv-lead-label" for="wv-page-email"><?php echo esc_html($label_email); ?></label>
								<input id="wv-page-email" name="email" type="email" required class="wv-lead-input" placeholder="<?php echo esc_attr($ph_email); ?>">
							</div>
						</div>
						<div class="space-y-2">
							<label class="wv-lead-label" for="wv-page-phone"><?php echo esc_html($label_phone); ?></label>
							<input id="wv-page-phone" name="telefone" type="tel" class="wv-lead-input" placeholder="<?php echo esc_attr($ph_phone); ?>">
						</div>
						<div class="space-y-2">
							<label class="wv-lead-label" for="wv-page-msg"><?php echo esc_html($label_msg); ?></label>
							<textarea id="wv-page-msg" name="mensagem" rows="4" required class="wv-lead-input wv-lead-input--area" placeholder="<?php echo esc_attr($ph_msg); ?>"></textarea>
						</div>
						<p class="wv-lead-feedback text-sm text-todoz min-h-[1.25rem]" role="status" aria-live="polite" hidden></p>
						<button type="submit" class="wv-lead-submit magnetic" data-wv-submit-label="<?php echo esc_attr($submit); ?>"><?php echo esc_html($submit); ?></button>
					</form>
				</div>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();
