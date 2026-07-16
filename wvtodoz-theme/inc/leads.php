<?php
/**
 * Leads from contact/quote modals — CPT + AJAX + admin UI.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

add_action('init', static function (): void {
	register_post_type('wv_lead', [
		'labels' => [
			'name'               => __('Leads', 'wvtodoz'),
			'singular_name'      => __('Lead', 'wvtodoz'),
			'add_new_item'       => __('Adicionar lead', 'wvtodoz'),
			'edit_item'          => __('Ver lead', 'wvtodoz'),
			'menu_name'          => __('Leads', 'wvtodoz'),
			'search_items'       => __('Buscar leads', 'wvtodoz'),
			'not_found'          => __('Nenhum lead encontrado.', 'wvtodoz'),
			'not_found_in_trash' => __('Nenhum lead na lixeira.', 'wvtodoz'),
		],
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => false,
		'show_in_rest'        => false,
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'hierarchical'        => false,
		'supports'            => ['title'],
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
	]);
});

/**
 * Meta keys stored on wv_lead.
 *
 * @return list<string>
 */
function wv_lead_meta_keys(): array {
	return [
		'_wv_lead_name',
		'_wv_lead_email',
		'_wv_lead_phone',
		'_wv_lead_message',
		'_wv_lead_type',
		'_wv_lead_source_page',
		'_wv_lead_source_title',
		'_wv_lead_source_section',
		'_wv_lead_utm_source',
		'_wv_lead_utm_medium',
		'_wv_lead_utm_campaign',
		'_wv_lead_read',
		'_wv_lead_ip',
	];
}

add_action('admin_menu', static function (): void {
	add_submenu_page(
		'wvtodoz',
		__('Leads / Contatos', 'wvtodoz'),
		__('Leads', 'wvtodoz'),
		'edit_posts',
		'wvtodoz-leads',
		'wv_admin_render_leads_page'
	);
}, 12);

add_action('admin_init', static function (): void {
	if (!isset($_GET['page']) || $_GET['page'] !== 'wvtodoz-leads') {
		return;
	}
	if (!current_user_can('edit_posts')) {
		return;
	}

	// Mark read/unread
	if (isset($_GET['wv_lead_read'], $_GET['_wpnonce'])) {
		$lead_id = (int) $_GET['wv_lead_read'];
		if ($lead_id && wp_verify_nonce((string) wp_unslash($_GET['_wpnonce']), 'wv_lead_read_' . $lead_id)) {
			update_post_meta($lead_id, '_wv_lead_read', '1');
			wp_safe_redirect(remove_query_arg(['wv_lead_read', '_wpnonce']));
			exit;
		}
	}

	// CSV export
	if (isset($_GET['wv_export']) && $_GET['wv_export'] === 'csv') {
		if (!wp_verify_nonce((string) ($_GET['_wpnonce'] ?? ''), 'wv_leads_export')) {
			wp_die(esc_html__('Nonce inválido.', 'wvtodoz'));
		}
		wv_leads_export_csv();
	}
});

function wv_leads_export_csv(): void {
	$q = new WP_Query([
		'post_type'      => 'wv_lead',
		'post_status'    => 'publish',
		'posts_per_page' => 500,
		'orderby'        => 'date',
		'order'          => 'DESC',
	]);

	nocache_headers();
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=wv-leads-' . gmdate('Y-m-d') . '.csv');

	$out = fopen('php://output', 'w');
	if ($out === false) {
		exit;
	}
	fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
	fputcsv($out, ['id', 'nome', 'email', 'telefone', 'tipo', 'mensagem', 'pagina', 'titulo_pagina', 'secao', 'utm_source', 'utm_medium', 'utm_campaign', 'lido', 'criado_em'], ';');

	foreach ($q->posts as $post) {
		/** @var WP_Post $post */
		fputcsv($out, [
			$post->ID,
			(string) get_post_meta($post->ID, '_wv_lead_name', true),
			(string) get_post_meta($post->ID, '_wv_lead_email', true),
			(string) get_post_meta($post->ID, '_wv_lead_phone', true),
			(string) get_post_meta($post->ID, '_wv_lead_type', true),
			(string) get_post_meta($post->ID, '_wv_lead_message', true),
			(string) get_post_meta($post->ID, '_wv_lead_source_page', true),
			(string) get_post_meta($post->ID, '_wv_lead_source_title', true),
			(string) get_post_meta($post->ID, '_wv_lead_source_section', true),
			(string) get_post_meta($post->ID, '_wv_lead_utm_source', true),
			(string) get_post_meta($post->ID, '_wv_lead_utm_medium', true),
			(string) get_post_meta($post->ID, '_wv_lead_utm_campaign', true),
			(string) get_post_meta($post->ID, '_wv_lead_read', true) === '1' ? 'sim' : 'nao',
			get_the_date('Y-m-d H:i:s', $post),
		], ';');
	}
	fclose($out);
	exit;
}

function wv_admin_render_leads_page(): void {
	if (!current_user_can('edit_posts')) {
		return;
	}

	$view_id = isset($_GET['lead']) ? (int) $_GET['lead'] : 0;

	wv_admin_shell_open(
		__('Leads / Contatos & Orçamentos', 'wvtodoz'),
		__('Envios dos modais do site, com página e seção de origem.', 'wvtodoz')
	);

	$export_url = wp_nonce_url(admin_url('admin.php?page=wvtodoz-leads&wv_export=csv'), 'wv_leads_export');
	wv_admin_actions([
		['label' => __('Exportar CSV', 'wvtodoz'), 'url' => $export_url, 'primary' => true],
		['label' => __('Atualizar', 'wvtodoz'), 'url' => admin_url('admin.php?page=wvtodoz-leads')],
	]);

	if ($view_id > 0) {
		wv_admin_render_lead_detail($view_id);
		wv_admin_shell_close();
		return;
	}

	$q = new WP_Query([
		'post_type'      => 'wv_lead',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'orderby'        => 'date',
		'order'          => 'DESC',
	]);

	if (!$q->have_posts()) {
		wv_admin_notice('warn', __('Nenhum lead ainda. Envie o formulário de contato no site para testar.', 'wvtodoz'));
		wv_admin_shell_close();
		return;
	}

	echo '<div class="wv-admin__card wv-admin__card--wide">';
	echo '<table class="wv-leads-table widefat striped">';
	echo '<thead><tr>';
	echo '<th>' . esc_html__('Quando', 'wvtodoz') . '</th>';
	echo '<th>' . esc_html__('Nome', 'wvtodoz') . '</th>';
	echo '<th>' . esc_html__('Tipo', 'wvtodoz') . '</th>';
	echo '<th>' . esc_html__('Página de origem', 'wvtodoz') . '</th>';
	echo '<th>' . esc_html__('Seção', 'wvtodoz') . '</th>';
	echo '<th>' . esc_html__('Status', 'wvtodoz') . '</th>';
	echo '<th></th>';
	echo '</tr></thead><tbody>';

	foreach ($q->posts as $post) {
		/** @var WP_Post $post */
		$name = (string) get_post_meta($post->ID, '_wv_lead_name', true);
		$type = (string) get_post_meta($post->ID, '_wv_lead_type', true);
		$page = (string) get_post_meta($post->ID, '_wv_lead_source_page', true);
		$title = (string) get_post_meta($post->ID, '_wv_lead_source_title', true);
		$section = (string) get_post_meta($post->ID, '_wv_lead_source_section', true);
		$read = (string) get_post_meta($post->ID, '_wv_lead_read', true) === '1';
		$view = admin_url('admin.php?page=wvtodoz-leads&lead=' . $post->ID);
		$type_label = $type === 'orcamento' ? __('Orçamento', 'wvtodoz') : __('Contato', 'wvtodoz');
		$page_label = $title !== '' ? $title : ($page !== '' ? $page : '—');

		echo '<tr class="' . ($read ? 'is-read' : 'is-unread') . '">';
		echo '<td>' . esc_html(get_the_date('d/m/Y H:i', $post)) . '</td>';
		echo '<td><strong>' . esc_html($name !== '' ? $name : $post->post_title) . '</strong><br><span class="wv-muted">' . esc_html((string) get_post_meta($post->ID, '_wv_lead_email', true)) . '</span></td>';
		echo '<td><span class="wv-lead-type wv-lead-type--' . esc_attr($type ?: 'contato') . '">' . esc_html($type_label) . '</span></td>';
		echo '<td><code class="wv-lead-source">' . esc_html($page_label) . '</code>';
		if ($page !== '' && $title !== '') {
			echo '<br><span class="wv-muted">' . esc_html($page) . '</span>';
		}
		echo '</td>';
		echo '<td>' . esc_html($section !== '' ? $section : '—') . '</td>';
		echo '<td>' . ($read ? esc_html__('Lido', 'wvtodoz') : '<strong>' . esc_html__('Novo', 'wvtodoz') . '</strong>') . '</td>';
		echo '<td><a class="wv-admin__btn wv-admin__btn--ghost" href="' . esc_url($view) . '">' . esc_html__('Ver', 'wvtodoz') . '</a></td>';
		echo '</tr>';
	}

	echo '</tbody></table></div>';
	wv_admin_shell_close();
}

function wv_admin_render_lead_detail(int $lead_id): void {
	$post = get_post($lead_id);
	if (!$post || $post->post_type !== 'wv_lead') {
		wv_admin_notice('bad', __('Lead não encontrado.', 'wvtodoz'));
		return;
	}

	update_post_meta($lead_id, '_wv_lead_read', '1');

	$get = static fn(string $k): string => (string) get_post_meta($lead_id, $k, true);
	$type = $get('_wv_lead_type');
	$type_label = $type === 'orcamento' ? __('Orçamento', 'wvtodoz') : __('Contato', 'wvtodoz');

	echo '<div class="wv-admin__card wv-admin__card--wide">';
	echo '<p><a class="wv-admin__btn wv-admin__btn--ghost" href="' . esc_url(admin_url('admin.php?page=wvtodoz-leads')) . '">← ' . esc_html__('Voltar à lista', 'wvtodoz') . '</a></p>';
	echo '<h2 class="wv-admin__card-title">' . esc_html($get('_wv_lead_name') ?: $post->post_title) . '</h2>';
	echo '<dl class="wv-lead-detail">';
	$rows = [
		__('Tipo', 'wvtodoz')            => $type_label,
		__('E-mail', 'wvtodoz')          => $get('_wv_lead_email'),
		__('Telefone', 'wvtodoz')        => $get('_wv_lead_phone'),
		__('Mensagem', 'wvtodoz')        => $get('_wv_lead_message'),
		__('Página (URL/path)', 'wvtodoz') => $get('_wv_lead_source_page'),
		__('Título da página', 'wvtodoz')  => $get('_wv_lead_source_title'),
		__('Seção', 'wvtodoz')           => $get('_wv_lead_source_section'),
		__('UTM source', 'wvtodoz')      => $get('_wv_lead_utm_source'),
		__('UTM medium', 'wvtodoz')      => $get('_wv_lead_utm_medium'),
		__('UTM campaign', 'wvtodoz')    => $get('_wv_lead_utm_campaign'),
		__('Recebido em', 'wvtodoz')     => get_the_date('d/m/Y H:i:s', $post),
	];
	foreach ($rows as $label => $value) {
		if ($value === '') {
			$value = '—';
		}
		echo '<div><dt>' . esc_html($label) . '</dt><dd>' . nl2br(esc_html($value)) . '</dd></div>';
	}
	echo '</dl>';
	echo '</div>';
}

/**
 * AJAX: create lead from front-end modal.
 */
add_action('wp_ajax_wv_submit_lead', 'wv_ajax_submit_lead');
add_action('wp_ajax_nopriv_wv_submit_lead', 'wv_ajax_submit_lead');

function wv_ajax_submit_lead(): void {
	check_ajax_referer('wv_lead_nonce', 'nonce');

	// Honeypot
	$honey = sanitize_text_field((string) wp_unslash($_POST['wv_company'] ?? ''));
	if ($honey !== '') {
		wp_send_json_success(['ok' => true, 'skipped' => true]);
	}

	$name = sanitize_text_field((string) wp_unslash($_POST['nome'] ?? $_POST['name'] ?? ''));
	$email = sanitize_email((string) wp_unslash($_POST['email'] ?? ''));
	$phone = sanitize_text_field((string) wp_unslash($_POST['telefone'] ?? $_POST['phone'] ?? ''));
	$message = sanitize_textarea_field((string) wp_unslash($_POST['mensagem'] ?? $_POST['message'] ?? ''));
	$type = sanitize_key((string) wp_unslash($_POST['type'] ?? 'contato'));
	if (!in_array($type, ['contato', 'orcamento'], true)) {
		$type = 'contato';
	}

	$source_page = esc_url_raw((string) wp_unslash($_POST['source_page'] ?? ''));
	if ($source_page === '') {
		$source_page = sanitize_text_field((string) wp_unslash($_POST['source_path'] ?? ''));
	}
	$source_title = sanitize_text_field((string) wp_unslash($_POST['source_title'] ?? ''));
	$source_section = sanitize_text_field((string) wp_unslash($_POST['source_section'] ?? ''));
	$utm_source = sanitize_text_field((string) wp_unslash($_POST['utm_source'] ?? ''));
	$utm_medium = sanitize_text_field((string) wp_unslash($_POST['utm_medium'] ?? ''));
	$utm_campaign = sanitize_text_field((string) wp_unslash($_POST['utm_campaign'] ?? ''));

	if ($name === '' || $email === '' || !is_email($email) || $message === '') {
		wp_send_json_error(['message' => __('Preencha nome, e-mail e mensagem.', 'wvtodoz')], 400);
	}

	$title = sprintf(
		'%s — %s',
		$name,
		$type === 'orcamento' ? __('Orçamento', 'wvtodoz') : __('Contato', 'wvtodoz')
	);

	$post_id = wp_insert_post([
		'post_type'   => 'wv_lead',
		'post_status' => 'publish',
		'post_title'  => $title,
		'post_content'=> $message,
	], true);

	if (is_wp_error($post_id)) {
		wp_send_json_error(['message' => __('Não foi possível salvar o lead.', 'wvtodoz')], 500);
	}

	update_post_meta($post_id, '_wv_lead_name', $name);
	update_post_meta($post_id, '_wv_lead_email', $email);
	update_post_meta($post_id, '_wv_lead_phone', $phone);
	update_post_meta($post_id, '_wv_lead_message', $message);
	update_post_meta($post_id, '_wv_lead_type', $type);
	update_post_meta($post_id, '_wv_lead_source_page', $source_page);
	update_post_meta($post_id, '_wv_lead_source_title', $source_title);
	update_post_meta($post_id, '_wv_lead_source_section', $source_section);
	update_post_meta($post_id, '_wv_lead_utm_source', $utm_source);
	update_post_meta($post_id, '_wv_lead_utm_medium', $utm_medium);
	update_post_meta($post_id, '_wv_lead_utm_campaign', $utm_campaign);
	update_post_meta($post_id, '_wv_lead_read', '0');
	update_post_meta($post_id, '_wv_lead_ip', sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? '')));

	wp_send_json_success([
		'ok'      => true,
		'lead_id' => $post_id,
		'message' => __('Mensagem enviada com sucesso.', 'wvtodoz'),
	]);
}

/**
 * Localize lead form config on front.
 */
add_action('wp_enqueue_scripts', static function (): void {
	$opts = function_exists('wv_get_theme_options') ? wv_get_theme_options() : [];
	$wa_digits = (string) ($opts['whatsapp'] ?? '551155311779');
	// Path-absolute ajax URL so localhost vs 127.0.0.1 both work in Docker.
	$ajax = (string) wp_parse_url(admin_url('admin-ajax.php'), PHP_URL_PATH);
	if ($ajax === '') {
		$ajax = '/wp-admin/admin-ajax.php';
	}

	wp_localize_script('wvtodoz-components', 'wvLeads', [
		'ajaxUrl'       => $ajax,
		'nonce'         => wp_create_nonce('wv_lead_nonce'),
		'action'        => 'wv_submit_lead',
		'openWhatsApp'  => !empty($opts['flag_lead_whatsapp']),
		'whatsappPhone' => $wa_digits,
		'i18n'          => [
			'sending' => __('Enviando…', 'wvtodoz'),
			'success' => __('Recebemos sua mensagem. Em breve falamos com você.', 'wvtodoz'),
			'error'   => __('Não foi possível enviar. Tente novamente.', 'wvtodoz'),
		],
	]);
}, 30);
