<?php
/**
 * Template Name: Contato
 * Premium contact page — leads form + company channels.
 *
 * @package WVTodoz
 */

get_header();

$ctx = wv_internal_page_context();
$d = wv_defaults();
$contato = $d['contato'];
$footer = $d['footer'];

$before = $ctx['title_line'] !== '' ? $ctx['title_line'] : (string) wv_field('wv_contato_title_before', $contato['title_before']);
$highlight = $ctx['title_accent'] !== '' ? $ctx['title_accent'] : (string) wv_field('wv_contato_title_highlight', $contato['title_highlight']);
$lead = $ctx['lead'] !== '' ? $ctx['lead'] : __('Conte o desafio. Em até um dia útil retornamos com a melhor abordagem em software, IA e integrações.', 'wvtodoz');
$eyebrow = $ctx['eyebrow'] !== '' ? $ctx['eyebrow'] : __('Fale conosco', 'wvtodoz');

$phone = (string) wv_page_field('wv_page_phone', (string) wv_field('wv_contato_phone', $contato['phone']), $ctx['page_id']);
$tel = (string) wv_field('wv_contato_tel', $contato['tel']);
if ($phone !== '') {
	$digits = preg_replace('/\D+/', '', $phone);
	if (is_string($digits) && $digits !== '') {
		$tel = $digits;
	}
}
$wa = wv_whatsapp_url();
$email = wv_company_email();
$address = (string) wv_page_field('wv_page_address', (string) wv_field('wv_footer_address', $footer['address']), $ctx['page_id']);
$maps = (string) wv_page_field('wv_page_maps_url', (string) wv_field('wv_footer_maps', $footer['maps']), $ctx['page_id']);

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
<main class="wv-contato-page section-canvas">
	<div class="wv-contato-page__atmosphere" aria-hidden="true"></div>

	<div class="site-container wv-contato-page__inner">
		<nav class="wv-page-breadcrumb flex items-center gap-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 mb-10 md:mb-12" data-aos="fade-right" aria-label="<?php esc_attr_e('Breadcrumb', 'wvtodoz'); ?>">
			<a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-todoz"><?php esc_html_e('Home', 'wvtodoz'); ?></a>
			<i class="ph ph-caret-right" aria-hidden="true"></i>
			<span class="text-todoz/50"><?php esc_html_e('Contato', 'wvtodoz'); ?></span>
		</nav>

		<header class="wv-contato-page__hero" data-aos="fade-up">
			<span class="section-eyebrow text-todoz"><?php echo esc_html($eyebrow); ?></span>
			<h1 class="section-display">
				<span class="section-display-line"><?php echo esc_html($before); ?></span>
				<span class="section-display-accent"><?php echo esc_html($highlight); ?><span class="section-display-dot" aria-hidden="true"></span></span>
			</h1>
			<p class="wv-contato-page__lead"><?php echo esc_html($lead); ?></p>
		</header>

		<div class="wv-contato-page__layout">
			<aside class="wv-contato-aside" data-aos="fade-up">
				<?php if ($ctx['body'] !== '') : ?>
				<div class="wv-content-body prose prose-blog max-w-none wv-contato-aside__body">
					<?php echo wp_kses_post($ctx['body']); ?>
				</div>
				<?php endif; ?>

				<div class="wv-contato-channels">
					<?php if ($phone !== '') : ?>
					<a class="wv-contato-channel" href="<?php echo esc_url('tel:' . $tel); ?>">
						<span class="wv-contato-channel__icon" aria-hidden="true"><i class="ph-bold ph-phone"></i></span>
						<span class="wv-contato-channel__meta">
							<span class="wv-contato-channel__label"><?php esc_html_e('Telefone', 'wvtodoz'); ?></span>
							<span class="wv-contato-channel__value"><?php echo esc_html($phone); ?></span>
						</span>
					</a>
					<?php endif; ?>

					<?php if ($email !== '') : ?>
					<a class="wv-contato-channel" href="<?php echo esc_url('mailto:' . $email); ?>">
						<span class="wv-contato-channel__icon" aria-hidden="true"><i class="ph-bold ph-envelope"></i></span>
						<span class="wv-contato-channel__meta">
							<span class="wv-contato-channel__label"><?php esc_html_e('E-mail', 'wvtodoz'); ?></span>
							<span class="wv-contato-channel__value"><?php echo esc_html($email); ?></span>
						</span>
					</a>
					<?php endif; ?>

					<a class="wv-contato-channel wv-contato-channel--wa" href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer">
						<span class="wv-contato-channel__icon" aria-hidden="true"><i class="ph-bold ph-whatsapp-logo"></i></span>
						<span class="wv-contato-channel__meta">
							<span class="wv-contato-channel__label"><?php esc_html_e('WhatsApp', 'wvtodoz'); ?></span>
							<span class="wv-contato-channel__value"><?php esc_html_e('Falar com especialista', 'wvtodoz'); ?></span>
						</span>
						<i class="ph ph-arrow-up-right wv-contato-channel__arrow" aria-hidden="true"></i>
					</a>
				</div>

				<?php if ($address !== '') : ?>
				<div class="wv-contato-office">
					<span class="wv-contato-office__label"><?php esc_html_e('Escritório', 'wvtodoz'); ?></span>
					<p class="wv-contato-office__addr"><?php echo nl2br(esc_html($address)); ?></p>
					<?php if ($maps !== '') : ?>
					<a class="wv-contato-office__maps" href="<?php echo esc_url($maps); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e('Ver no Google Maps', 'wvtodoz'); ?>
						<i class="ph ph-arrow-up-right" aria-hidden="true"></i>
					</a>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<ul class="wv-contato-trust" aria-label="<?php esc_attr_e('Compromissos', 'wvtodoz'); ?>">
					<li><i class="ph-bold ph-clock" aria-hidden="true"></i> <?php esc_html_e('Resposta em até 1 dia útil', 'wvtodoz'); ?></li>
					<li><i class="ph-bold ph-shield-check" aria-hidden="true"></i> <?php esc_html_e('Dados tratados com LGPD', 'wvtodoz'); ?></li>
					<li><i class="ph-bold ph-buildings" aria-hidden="true"></i> <?php esc_html_e('Software house · São Paulo', 'wvtodoz'); ?></li>
				</ul>
			</aside>

			<div class="wv-contato-form-wrap" data-aos="fade-up" data-aos-delay="80">
				<div class="wv-lead-card wv-contato-form-card">
					<div class="wv-contato-form-card__head">
						<h2 class="wv-contato-form-card__title"><?php esc_html_e('Envie sua mensagem', 'wvtodoz'); ?></h2>
						<p class="wv-contato-form-card__hint"><?php esc_html_e('Preencha os campos — retornamos com o próximo passo.', 'wvtodoz'); ?></p>
					</div>
					<form id="contato-page-form" class="wv-lead-form wv-contato-form space-y-5 text-left" novalidate data-wv-close-on-success="0">
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
						<div class="wv-contato-form__grid">
							<div class="wv-contato-field">
								<label class="wv-lead-label" for="wv-page-nome"><?php echo esc_html($label_name); ?></label>
								<input id="wv-page-nome" name="nome" type="text" required autocomplete="name" class="wv-lead-input" placeholder="<?php echo esc_attr($ph_name); ?>">
							</div>
							<div class="wv-contato-field">
								<label class="wv-lead-label" for="wv-page-email"><?php echo esc_html($label_email); ?></label>
								<input id="wv-page-email" name="email" type="email" required autocomplete="email" class="wv-lead-input" placeholder="<?php echo esc_attr($ph_email); ?>">
							</div>
						</div>
						<div class="wv-contato-field">
							<label class="wv-lead-label" for="wv-page-phone"><?php echo esc_html($label_phone); ?></label>
							<input id="wv-page-phone" name="telefone" type="tel" autocomplete="tel" class="wv-lead-input" placeholder="<?php echo esc_attr($ph_phone); ?>">
						</div>
						<div class="wv-contato-field">
							<label class="wv-lead-label" for="wv-page-msg"><?php echo esc_html($label_msg); ?></label>
							<textarea id="wv-page-msg" name="mensagem" rows="5" required class="wv-lead-input wv-lead-input--area" placeholder="<?php echo esc_attr($ph_msg); ?>"></textarea>
						</div>
						<p class="wv-lead-feedback wv-contato-feedback" role="status" aria-live="polite" hidden></p>
						<button type="submit" class="wv-lead-submit wv-contato-submit magnetic" data-wv-submit-label="<?php echo esc_attr($submit); ?>">
							<span><?php echo esc_html($submit); ?></span>
							<i class="ph-bold ph-arrow-right" aria-hidden="true"></i>
						</button>
						<p class="wv-contato-form__footnote">
							<?php
							printf(
								/* translators: %s privacy policy URL */
								esc_html__('Ao enviar, você concorda com nossa %s.', 'wvtodoz'),
								'<a href="' . esc_url(function_exists('wv_privacy_url') ? wv_privacy_url() : home_url('/politica-de-privacidade/')) . '">' . esc_html__('Política de Privacidade', 'wvtodoz') . '</a>'
							);
							?>
						</p>
					</form>
				</div>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();
