<?php
/**
 * Contact + case modals.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults();
$modal_t = (string) wv_field('wv_modal_title', $d['modal']['title']);
$modal_a = (string) wv_field('wv_modal_title_accent', $d['modal']['title_accent']);
$modal_submit = (string) wv_field('wv_modal_submit', 'Enviar Proposta');
$case_cta = (string) wv_field('wv_case_modal_cta', 'SOLICITAR PROJETO SIMILAR');
$label_name = (string) wv_field('wv_modal_label_name', 'Nome Completo');
$label_email = (string) wv_field('wv_modal_label_email', 'E-mail Corporativo');
$label_msg = (string) wv_field('wv_modal_label_message', 'Conte seu desafio');
$ph_name = (string) wv_field('wv_modal_placeholder_name', 'João Silva');
$ph_email = (string) wv_field('wv_modal_placeholder_email', 'exemplo@empresa.com.br');
$ph_msg = (string) wv_field('wv_modal_placeholder_message', 'Quero criar um agente de IA para...');
?>
<div id="case-modal" class="site-modal" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="site-modal-backdrop" data-modal-backdrop></div>
	<div class="site-modal-panel site-modal-panel--case" data-modal-panel>
		<button type="button" id="close-case-modal" class="site-modal-close" aria-label="<?php esc_attr_e('Fechar case', 'wvtodoz'); ?>"><i class="ph ph-x-circle"></i></button>
		<div class="site-modal-media">
			<img id="case-modal-img" src="" alt="">
			<div class="site-modal-media-fade"></div>
		</div>
		<div class="site-modal-body">
			<span id="case-modal-category" class="text-todoz text-[10px] font-black uppercase tracking-[0.5em] mb-6 block text-left"><?php esc_html_e('Categoria', 'wvtodoz'); ?></span>
			<h2 id="case-modal-title" class="text-4xl md:text-6xl font-black mb-8 tracking-tighter leading-[0.9] text-white text-left uppercase"><?php esc_html_e('Título', 'wvtodoz'); ?></h2>
			<p id="case-modal-desc" class="text-zinc-400 text-lg leading-relaxed mb-10 text-left normal-case"><?php esc_html_e('Descrição detalhada do projeto.', 'wvtodoz'); ?></p>
			<button type="button" class="open-modal-from-case magnetic bg-todoz px-10 py-6 text-black font-black rounded-full text-xs uppercase tracking-widest"><?php echo esc_html($case_cta); ?></button>
		</div>
	</div>
</div>

<div id="contact-modal" class="site-modal site-modal--contact" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="site-modal-backdrop" data-modal-backdrop></div>
	<div class="site-modal-panel site-modal-panel--contact" data-modal-panel>
		<button type="button" id="close-modal" class="site-modal-close" aria-label="<?php esc_attr_e('Fechar formulário', 'wvtodoz'); ?>"><i class="ph ph-x"></i></button>
		<h2 class="text-4xl font-black mb-8 tracking-tighter text-left text-white uppercase"><?php echo esc_html($modal_t); ?> <span class="text-todoz text-glow uppercase"><?php echo esc_html($modal_a); ?></span></h2>
		<form id="contact-form" class="space-y-6 text-left normal-case">
			<div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-left">
				<div class="space-y-2 text-left">
					<label class="text-[10px] font-black uppercase tracking-widest text-zinc-400 block text-left"><?php echo esc_html($label_name); ?></label>
					<input name="nome" type="text" required class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 outline-none focus:border-todoz transition-all text-sm text-white" placeholder="<?php echo esc_attr($ph_name); ?>">
				</div>
				<div class="space-y-2 text-left">
					<label class="text-[10px] font-black uppercase tracking-widest text-zinc-400 block text-left"><?php echo esc_html($label_email); ?></label>
					<input name="email" type="email" required class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 outline-none focus:border-todoz transition-all text-sm text-white" placeholder="<?php echo esc_attr($ph_email); ?>">
				</div>
			</div>
			<div class="space-y-2 text-left">
				<label class="text-[10px] font-black uppercase tracking-widest text-zinc-400 block text-left"><?php echo esc_html($label_msg); ?></label>
				<textarea name="mensagem" rows="3" required class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 outline-none focus:border-todoz transition-all text-sm text-white resize-none" placeholder="<?php echo esc_attr($ph_msg); ?>"></textarea>
			</div>
			<button type="submit" class="magnetic bg-todoz w-full py-5 text-black font-black rounded-full text-xs uppercase tracking-widest text-center"><?php echo esc_html($modal_submit); ?></button>
		</form>
	</div>
</div>
