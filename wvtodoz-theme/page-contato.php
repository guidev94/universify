<?php
/**
 * Template Name: Contato
 * Contact page — form submits to wv_lead (type contato) with source_page.
 *
 * @package WVTodoz
 */

get_header();

$d = wv_defaults();
$contato = $d['contato'];
$before = (string) wv_field('wv_contato_title_before', $contato['title_before']);
$highlight = (string) wv_field('wv_contato_title_highlight', $contato['title_highlight']);
$phone = (string) wv_field('wv_contato_phone', $contato['phone']);
$tel = (string) wv_field('wv_contato_tel', $contato['tel']);
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
<main class="pt-40 pb-32 section-canvas">
	<div class="site-container">
		<nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 mb-12" data-aos="fade-right" aria-label="<?php esc_attr_e('Breadcrumb', 'wvtodoz'); ?>">
			<a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-todoz"><?php esc_html_e('Home', 'wvtodoz'); ?></a>
			<i class="ph ph-caret-right" aria-hidden="true"></i>
			<span class="text-todoz/50"><?php esc_html_e('Contato', 'wvtodoz'); ?></span>
		</nav>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-16 lg:gap-20 items-start">
			<div class="lg:col-span-5" data-aos="fade-up">
				<span class="section-eyebrow text-todoz"><?php esc_html_e('Fale conosco', 'wvtodoz'); ?></span>
				<h1 class="section-display mb-8">
					<span class="section-display-line"><?php echo esc_html($before); ?></span>
					<span class="section-display-accent"><?php echo esc_html($highlight); ?><span class="section-display-dot" aria-hidden="true"></span></span>
				</h1>
				<p class="text-zinc-400 text-lg leading-relaxed mb-10 max-w-md">
					<?php esc_html_e('Conte seu desafio. Retornamos com a melhor abordagem em software, IA e integrações.', 'wvtodoz'); ?>
				</p>
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
				<?php
				while (have_posts()) :
					the_post();
					$content = trim((string) get_the_content());
					if ($content !== '') :
						?>
				<div class="mt-12 prose prose-blog max-w-none text-zinc-400">
						<?php the_content(); ?>
				</div>
						<?php
					endif;
				endwhile;
				?>
			</div>

			<div class="lg:col-span-7" data-aos="fade-up" data-aos-delay="100">
				<div class="p-8 md:p-12 bg-zinc-900/30 border border-white/5 rounded-[2.5rem] md:rounded-[3rem] backdrop-blur-xl">
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
								<label class="text-[10px] font-black uppercase tracking-widest text-zinc-400 block" for="wv-page-nome"><?php echo esc_html($label_name); ?></label>
								<input id="wv-page-nome" name="nome" type="text" required class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 outline-none focus:border-todoz transition-all text-sm text-white" placeholder="<?php echo esc_attr($ph_name); ?>">
							</div>
							<div class="space-y-2">
								<label class="text-[10px] font-black uppercase tracking-widest text-zinc-400 block" for="wv-page-email"><?php echo esc_html($label_email); ?></label>
								<input id="wv-page-email" name="email" type="email" required class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 outline-none focus:border-todoz transition-all text-sm text-white" placeholder="<?php echo esc_attr($ph_email); ?>">
							</div>
						</div>
						<div class="space-y-2">
							<label class="text-[10px] font-black uppercase tracking-widest text-zinc-400 block" for="wv-page-phone"><?php echo esc_html($label_phone); ?></label>
							<input id="wv-page-phone" name="telefone" type="tel" class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 outline-none focus:border-todoz transition-all text-sm text-white" placeholder="<?php echo esc_attr($ph_phone); ?>">
						</div>
						<div class="space-y-2">
							<label class="text-[10px] font-black uppercase tracking-widest text-zinc-400 block" for="wv-page-msg"><?php echo esc_html($label_msg); ?></label>
							<textarea id="wv-page-msg" name="mensagem" rows="4" required class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 outline-none focus:border-todoz transition-all text-sm text-white resize-none" placeholder="<?php echo esc_attr($ph_msg); ?>"></textarea>
						</div>
						<p class="wv-lead-feedback text-sm text-todoz min-h-[1.25rem]" role="status" aria-live="polite" hidden></p>
						<button type="submit" class="magnetic bg-todoz w-full py-5 text-black font-black rounded-full text-xs uppercase tracking-widest text-center" data-wv-submit-label="<?php echo esc_attr($submit); ?>"><?php echo esc_html($submit); ?></button>
					</form>
				</div>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();
