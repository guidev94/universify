<?php
/**
 * Internal pages registry — templates, ACF defaults, admin hubs.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

/**
 * @return array<string, array<string, mixed>>
 */
function wv_internal_pages_registry(): array {
	return [
		'contato' => [
			'slug'         => 'contato',
			'title'        => __('Contato', 'wvtodoz'),
			'template'     => 'page-contato.php',
			'admin_slug'   => 'wvtodoz-page-contato',
			'blurb'        => __('Página de contato com formulário de leads e dados da empresa.', 'wvtodoz'),
		],
		'servicos' => [
			'slug'         => 'servicos',
			'title'        => __('Serviços', 'wvtodoz'),
			'template'     => 'page-servicos.php',
			'admin_slug'   => 'wvtodoz-page-servicos',
			'blurb'        => __('Hub de serviços com intro editável e cards do CPT Serviços.', 'wvtodoz'),
			'cpt'          => 'wv_servico',
		],
		'politica-de-privacidade' => [
			'slug'         => 'politica-de-privacidade',
			'title'        => __('Política de Privacidade', 'wvtodoz'),
			'template'     => 'page-legal.php',
			'admin_slug'   => 'wvtodoz-page-privacidade',
			'blurb'        => __('Texto legal editável (LGPD / privacidade).', 'wvtodoz'),
		],
		'termos-de-uso' => [
			'slug'         => 'termos-de-uso',
			'title'        => __('Termos de Uso', 'wvtodoz'),
			'template'     => 'page-legal.php',
			'admin_slug'   => 'wvtodoz-page-termos',
			'blurb'        => __('Termos de uso do site e dos serviços.', 'wvtodoz'),
		],
		'localizacao' => [
			'slug'         => 'localizacao',
			'title'        => __('Localização', 'wvtodoz'),
			'template'     => 'page-localizacao.php',
			'admin_slug'   => 'wvtodoz-page-localizacao',
			'blurb'        => __('Endereço, mapa e contatos do escritório.', 'wvtodoz'),
		],
		'equipe' => [
			'slug'         => 'equipe',
			'title'        => __('Equipe', 'wvtodoz'),
			'template'     => 'page-equipe.php',
			'admin_slug'   => 'wvtodoz-page-equipe',
			'blurb'        => __('Apresentação do time (até 4 membros + texto).', 'wvtodoz'),
		],
		'vagas' => [
			'slug'         => 'vagas',
			'title'        => __('Vagas', 'wvtodoz'),
			'template'     => 'page-vagas.php',
			'admin_slug'   => 'wvtodoz-page-vagas',
			'blurb'        => __('Vagas abertas e CTA para candidatura.', 'wvtodoz'),
		],
		'clientes' => [
			'slug'         => 'clientes',
			'title'        => __('Clientes', 'wvtodoz'),
			'template'     => 'page-clientes.php',
			'admin_slug'   => 'wvtodoz-page-clientes-hub',
			'blurb'        => __('Hub de clientes com logos do CPT Clientes.', 'wvtodoz'),
			'cpt'          => 'wv_cliente',
		],
		'marketing-digital' => [
			'slug'         => 'marketing-digital',
			'title'        => __('Marketing Digital', 'wvtodoz'),
			'template'     => 'page-marketing.php',
			'admin_slug'   => 'wvtodoz-page-marketing',
			'blurb'        => __('Página de marketing digital: hero, benefícios e CTA.', 'wvtodoz'),
		],
	];
}

/**
 * Page templates that use the shared internal ACF group.
 *
 * @return list<string>
 */
function wv_internal_page_templates(): array {
	$templates = [];
	foreach (wv_internal_pages_registry() as $page) {
		$templates[] = (string) $page['template'];
	}
	return array_values(array_unique($templates));
}

/**
 * Resolve page ID by slug (published).
 */
function wv_internal_page_id(string $slug): int {
	$page = get_page_by_path($slug);
	return $page instanceof WP_Post ? (int) $page->ID : 0;
}

/**
 * Shared context for internal page templates.
 *
 * @return array<string, mixed>
 */
function wv_internal_page_context(?int $page_id = null): array {
	$id = $page_id ?? (get_queried_object_id() ?: (int) get_the_ID());
	$slug = $id > 0 ? (string) get_post_field('post_name', $id) : '';
	$defaults = wv_internal_page_defaults()[$slug] ?? [];

	$get = static function (string $key, $fallback = '') use ($id, $defaults) {
		$def = array_key_exists($key, $defaults) ? $defaults[$key] : $fallback;
		return wv_page_field($key, $def, $id > 0 ? $id : null);
	};

	return [
		'page_id'       => $id,
		'slug'          => $slug,
		'eyebrow'       => (string) $get('wv_page_eyebrow'),
		'title_line'    => (string) $get('wv_page_title_line'),
		'title_accent'  => (string) $get('wv_page_title_accent'),
		'lead'          => (string) $get('wv_page_lead'),
		'body'          => (string) $get('wv_page_body'),
		'image'         => $get('wv_page_image', null),
		'cta_before'    => (string) $get('wv_page_cta_before'),
		'cta_highlight' => (string) $get('wv_page_cta_highlight'),
		'cta_label'     => (string) $get('wv_page_cta_label', __('Fale conosco', 'wvtodoz')),
		'cta_url'       => (string) $get('wv_page_cta_url', wv_contato_url()),
		'show_cta'      => (int) $get('wv_page_show_cta', 0) === 1,
		'address'       => (string) $get('wv_page_address'),
		'maps_url'      => (string) $get('wv_page_maps_url'),
		'phone'         => (string) $get('wv_page_phone'),
	];
}

/**
 * Read ACF/meta from a page with fallback.
 *
 * @param mixed $default
 * @return mixed
 */
function wv_page_field(string $key, $default = '', ?int $page_id = null) {
	$id = $page_id ?? get_queried_object_id();
	if ($id <= 0) {
		$id = get_the_ID() ?: 0;
	}
	if ($id <= 0) {
		return $default;
	}
	if (function_exists('get_field')) {
		$value = get_field($key, $id);
		if ($value !== null && $value !== false && $value !== '') {
			return $value;
		}
	}
	$meta = get_post_meta($id, $key, true);
	return ($meta === '' || $meta === false || $meta === null) ? $default : $meta;
}

/**
 * Default ACF copy for each internal page (PT-BR, brand-aligned).
 *
 * @return array<string, array<string, mixed>>
 */
function wv_internal_page_defaults(): array {
	$wa = function_exists('wv_whatsapp_url') ? wv_whatsapp_url() : 'https://wa.me/551155311779';
	return [
		'contato' => [
			'wv_page_eyebrow'       => 'Fale conosco',
			'wv_page_title_line'    => 'Vamos',
			'wv_page_title_accent'  => 'conversar',
			'wv_page_lead'          => 'Conte seu desafio. Retornamos com a melhor abordagem em software, IA e integrações.',
			'wv_page_body'          => '',
			'wv_page_cta_before'    => 'Pronto para o',
			'wv_page_cta_highlight' => 'próximo nível?',
			'wv_page_cta_label'     => 'Falar no WhatsApp',
			'wv_page_cta_url'       => $wa,
			'wv_page_show_cta'      => 0,
		],
		'servicos' => [
			'wv_page_eyebrow'       => 'O que fazemos',
			'wv_page_title_line'    => 'Serviços',
			'wv_page_title_accent'  => 'com impacto',
			'wv_page_lead'          => 'Agentes de IA, automações e software sob medida — do diagnóstico à evolução contínua.',
			'wv_page_body'          => '<p>Escolhemos a frente certa para o seu momento: produto digital, operação com IA ou integração entre sistemas. Cada entrega é pensada para reduzir fricção e gerar resultado mensurável.</p>',
			'wv_page_cta_before'    => 'Quer um',
			'wv_page_cta_highlight' => 'orçamento?',
			'wv_page_cta_label'     => 'Solicitar proposta',
			'wv_page_cta_url'        => home_url('/contato/'),
			'wv_page_show_cta'      => 1,
		],
		'politica-de-privacidade' => [
			'wv_page_eyebrow'       => 'Legal',
			'wv_page_title_line'    => 'Política de',
			'wv_page_title_accent'  => 'Privacidade',
			'wv_page_lead'          => 'Como a WV Todoz coleta, usa e protege dados pessoais enviados pelos canais digitais.',
			'wv_page_show_cta'      => 0,
		],
		'termos-de-uso' => [
			'wv_page_eyebrow'       => 'Legal',
			'wv_page_title_line'    => 'Termos de',
			'wv_page_title_accent'  => 'Uso',
			'wv_page_lead'          => 'Condições de uso do site, marcas e canais da WV Todoz.',
			'wv_page_show_cta'      => 0,
		],
		'localizacao' => [
			'wv_page_eyebrow'       => 'Onde estamos',
			'wv_page_title_line'    => 'Nosso',
			'wv_page_title_accent'  => 'escritório',
			'wv_page_lead'          => 'Brooklin, São Paulo — SP. Atendemos projetos em todo o Brasil.',
			'wv_page_address'       => "Av. Morumbi, 8435 cj.104\nBrooklin, São Paulo - SP\nBrasil",
			'wv_page_maps_url'      => 'https://www.google.com/maps/search/?api=1&query=Av.+Morumbi,+8435+Brooklin+S%C3%A3o+Paulo+SP',
			'wv_page_phone'         => '(11) 5531-1779',
			'wv_page_cta_before'    => 'Vamos',
			'wv_page_cta_highlight' => 'marcar uma visita?',
			'wv_page_cta_label'     => 'Falar conosco',
			'wv_page_cta_url'       => home_url('/contato/'),
			'wv_page_show_cta'      => 1,
		],
		'equipe' => [
			'wv_page_eyebrow'       => 'Quem somos',
			'wv_page_title_line'    => 'Time',
			'wv_page_title_accent'  => 'WV Todoz',
			'wv_page_lead'          => 'Produto, engenharia, dados e design — focados em sistemas que a operação realmente usa.',
			'wv_page_body'          => '<p>Somos uma software house com cultura prática: pouca cerimônia, muita entrega. Trabalhamos lado a lado com clientes para transformar complexidade em vantagem competitiva.</p>',
			'wv_page_team_1_name'   => 'Produto & Estratégia',
			'wv_page_team_1_role'   => 'Discovery e roadmap',
			'wv_page_team_1_bio'    => 'Priorização, escopo e alinhamento de negócio com tecnologia.',
			'wv_page_team_2_name'   => 'Engenharia',
			'wv_page_team_2_role'   => 'Software & integrações',
			'wv_page_team_2_bio'    => 'APIs, apps, automações e plataformas estáveis em produção.',
			'wv_page_team_3_name'   => 'IA & Dados',
			'wv_page_team_3_role'   => 'Agentes e inteligência',
			'wv_page_team_3_bio'    => 'Agentes, pipelines e decisões orientadas a dados.',
			'wv_page_team_4_name'   => 'Design & UX',
			'wv_page_team_4_role'   => 'Experiência digital',
			'wv_page_team_4_bio'    => 'Interfaces claras, acessíveis e com identidade de marca.',
			'wv_page_cta_before'    => 'Quer',
			'wv_page_cta_highlight' => 'trabalhar conosco?',
			'wv_page_cta_label'     => 'Ver vagas',
			'wv_page_cta_url'       => home_url('/vagas/'),
			'wv_page_show_cta'      => 1,
		],
		'vagas' => [
			'wv_page_eyebrow'       => 'Carreiras',
			'wv_page_title_line'    => 'Vagas',
			'wv_page_title_accent'  => 'abertas',
			'wv_page_lead'          => 'Buscamos pessoas curiosas, práticas e apaixonadas por produto digital.',
			'wv_page_body'          => '<p>Envie currículo e portfólio para <a href="mailto:contato@wvtodoz.com.br">contato@wvtodoz.com.br</a> com o assunto “Vagas”. Atualizamos as posições abertas nesta página.</p><h2>Como nos candidatamos</h2><ul><li>Currículo + LinkedIn</li><li>Portfólio ou GitHub</li><li>Breve nota sobre o que você quer construir</li></ul>',
			'wv_page_cta_before'    => 'Pronto para',
			'wv_page_cta_highlight' => 'aplicar?',
			'wv_page_cta_label'     => 'Enviar e-mail',
			'wv_page_cta_url'       => 'mailto:contato@wvtodoz.com.br?subject=Vagas%20WV%20Todoz',
			'wv_page_show_cta'      => 1,
		],
		'clientes' => [
			'wv_page_eyebrow'       => 'Parcerias',
			'wv_page_title_line'    => 'Clientes',
			'wv_page_title_accent'  => 'que confiam',
			'wv_page_lead'          => 'Empresas que precisam de software confiável, integrações sólidas e produtos com impacto real.',
			'wv_page_body'          => '<p>Do ponto eletrônico com IA a plataformas de eventos e e-commerce — construímos relações de longo prazo.</p>',
			'wv_page_cta_before'    => 'Quer ser o',
			'wv_page_cta_highlight' => 'próximo case?',
			'wv_page_cta_label'     => 'Falar com a WV',
			'wv_page_cta_url'       => home_url('/contato/'),
			'wv_page_show_cta'      => 1,
		],
		'marketing-digital' => [
			'wv_page_eyebrow'       => 'Crescimento',
			'wv_page_title_line'    => 'Marketing',
			'wv_page_title_accent'  => 'digital',
			'wv_page_lead'          => 'Produto + aquisição: landing pages, funis, conteúdo e integração com CRM e automação.',
			'wv_page_body'          => '<p>Unimos engenharia e estratégia digital para gerar conversões de qualidade — não só tráfego.</p>',
			'wv_page_b1_title'      => 'Landing & funis',
			'wv_page_b1_text'       => 'Páginas rápidas, tracking e experimentos de conversão.',
			'wv_page_b2_title'      => 'Conteúdo & SEO',
			'wv_page_b2_text'       => 'Insights e páginas que ranqueiam e educam o lead.',
			'wv_page_b3_title'      => 'CRM & automação',
			'wv_page_b3_text'       => 'Integração entre site, WhatsApp, e-mail e operação.',
			'wv_page_cta_before'    => 'Vamos',
			'wv_page_cta_highlight' => 'acelerar?',
			'wv_page_cta_label'     => 'Agendar conversa',
			'wv_page_cta_url'       => home_url('/contato/'),
			'wv_page_show_cta'      => 1,
		],
	];
}

/**
 * Apply template + ACF defaults to an internal page.
 */
function wv_seed_apply_internal_page(int $page_id, string $slug, string $body_html = ''): void {
	if ($page_id <= 0) {
		return;
	}
	$reg = wv_internal_pages_registry()[$slug] ?? null;
	if (!$reg) {
		return;
	}
	update_post_meta($page_id, '_wp_page_template', (string) $reg['template']);

	$defaults = wv_internal_page_defaults()[$slug] ?? [];
	if ($body_html !== '' && empty($defaults['wv_page_body'])) {
		$defaults['wv_page_body'] = $body_html;
	}
	foreach ($defaults as $key => $value) {
		if (function_exists('update_field')) {
			update_field($key, $value, $page_id);
		} else {
			update_post_meta($page_id, $key, $value);
		}
	}
}

/**
 * Edit URL for an internal page (branded editor).
 */
function wv_internal_page_edit_url(string $slug): string {
	$id = wv_internal_page_id($slug);
	if ($id <= 0) {
		return admin_url('edit.php?post_type=page');
	}
	$url = get_edit_post_link($id, 'raw');
	return $url ?: admin_url('post.php?post=' . $id . '&action=edit');
}

/**
 * Admin: Páginas hub + optional deep links.
 */
add_action('admin_menu', static function (): void {
	add_submenu_page(
		'wvtodoz',
		__('Páginas internas', 'wvtodoz'),
		__('Páginas', 'wvtodoz'),
		'edit_posts',
		'wvtodoz-paginas',
		'wv_admin_render_internal_pages'
	);
}, 13);

/**
 * Branded chrome for internal page editors.
 */
add_filter('admin_body_class', static function (string $classes): string {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'page') {
		return $classes;
	}
	$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
	if ($post_id <= 0) {
		return $classes;
	}
	$template = (string) get_page_template_slug($post_id);
	if (in_array($template, wv_internal_page_templates(), true)) {
		$classes .= ' wv-admin-editor wv-admin-related wv-admin-internal-page';
	}
	return $classes;
});

function wv_admin_render_internal_pages(): void {
	if (!current_user_can('edit_posts')) {
		return;
	}

	wv_admin_shell_open(
		__('Páginas internas', 'wvtodoz'),
		__('Hubs de páginas com o visual da Home — edite hero, conteúdo e CTA via ACF.', 'wvtodoz'),
		[
			['label' => __('Ver site', 'wvtodoz'), 'url' => home_url('/')],
			['label' => __('Todas as páginas WP', 'wvtodoz'), 'url' => admin_url('edit.php?post_type=page')],
		]
	);

	echo '<div class="wv-admin__grid wv-admin__grid--pages">';
	foreach (wv_internal_pages_registry() as $slug => $page) {
		$id = wv_internal_page_id($slug);
		$view = $id > 0 ? (string) get_permalink($id) : home_url('/' . $slug . '/');
		$edit = wv_internal_page_edit_url($slug);
		$eyebrow = $id > 0 ? (string) wv_page_field('wv_page_eyebrow', '', $id) : '';
		$line = $id > 0 ? (string) wv_page_field('wv_page_title_line', '', $id) : '';
		$accent = $id > 0 ? (string) wv_page_field('wv_page_title_accent', '', $id) : '';
		$lead = $id > 0 ? (string) wv_page_field('wv_page_lead', '', $id) : '';
		$status = $id > 0 ? __('Publicada', 'wvtodoz') : __('Não encontrada — rode Importar', 'wvtodoz');

		echo '<article class="wv-admin__card">';
		echo '<p class="wv-admin__eyebrow">' . esc_html((string) $page['template']) . '</p>';
		echo '<h2 class="wv-admin__card-title">' . esc_html((string) $page['title']) . '</h2>';
		echo '<p class="wv-admin__card-text">' . esc_html((string) $page['blurb']) . '</p>';
		if ($line !== '' || $accent !== '') {
			echo '<p class="wv-admin__preview"><strong>' . esc_html(trim($eyebrow . ' · ' . $line . ' ' . $accent)) . '</strong></p>';
			if ($lead !== '') {
				echo '<p class="wv-muted">' . esc_html(wp_html_excerpt($lead, 120, '…')) . '</p>';
			}
		}
		echo '<p class="wv-muted">' . esc_html($status) . '</p>';
		echo '<div class="wv-admin__actions">';
		printf('<a class="wv-admin__btn wv-admin__btn--primary" href="%s">%s</a>', esc_url($edit), esc_html__('Editar', 'wvtodoz'));
		printf('<a class="wv-admin__btn wv-admin__btn--ghost" href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url($view), esc_html__('Ver no site', 'wvtodoz'));
		if (!empty($page['cpt'])) {
			$cpt = (string) $page['cpt'];
			$list = $cpt === 'post' ? admin_url('edit.php') : admin_url('edit.php?post_type=' . $cpt);
			printf('<a class="wv-admin__btn wv-admin__btn--ghost" href="%s">%s</a>', esc_url($list), esc_html__('Itens CPT', 'wvtodoz'));
		}
		echo '</div></article>';
	}
	echo '</div>';

	wv_admin_shell_close();
}
