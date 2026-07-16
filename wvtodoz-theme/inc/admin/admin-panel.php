<?php
/**
 * WV Todoz branded admin panel — one hub per site section.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/sections.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/render.php';

/**
 * Register top-level menu + section subpages.
 */
add_action('admin_menu', static function (): void {
	add_menu_page(
		__('WV Todoz', 'wvtodoz'),
		__('WV Todoz', 'wvtodoz'),
		'edit_posts',
		'wvtodoz',
		'wv_admin_render_dashboard',
		'dashicons-superhero',
		3
	);

	add_submenu_page(
		'wvtodoz',
		__('Painel', 'wvtodoz'),
		__('Painel', 'wvtodoz'),
		'edit_posts',
		'wvtodoz',
		'wv_admin_render_dashboard'
	);

	foreach (wv_admin_sections() as $key => $section) {
		add_submenu_page(
			'wvtodoz',
			(string) $section['title'],
			(string) $section['menu_title'],
			'edit_posts',
			(string) $section['slug'],
			static function () use ($section): void {
				wv_admin_render_section_page($section);
			}
		);
	}

	add_submenu_page(
		'wvtodoz',
		__('Menus / Navegação', 'wvtodoz'),
		__('Menus', 'wvtodoz'),
		'edit_posts',
		'wvtodoz-menus',
		'wv_admin_render_menus'
	);

	add_submenu_page(
		'wvtodoz',
		__('Importar conteúdo', 'wvtodoz'),
		__('Importar conteúdo', 'wvtodoz'),
		'manage_options',
		'wvtodoz-import',
		'wv_admin_render_import'
	);

	add_submenu_page(
		'wvtodoz',
		__('Ajuda', 'wvtodoz'),
		__('Ajuda', 'wvtodoz'),
		'edit_posts',
		'wvtodoz-ajuda',
		'wv_admin_render_help'
	);
}, 9);

/**
 * Whether current screen is a WV-branded post editor (Home / CPT / wv_tab).
 */
function wv_admin_is_branded_editor(): bool {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || $screen->base !== 'post') {
		return !empty($_GET['wv_tab']);
	}
	$cpts = ['wv_case', 'wv_produto', 'wv_cliente', 'wv_servico'];
	if (in_array((string) $screen->post_type, $cpts, true)) {
		return true;
	}
	if (!empty($_GET['wv_tab'])) {
		return true;
	}
	if ($screen->post_type === 'page') {
		$home_id = wv_home_id();
		$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
		return $home_id > 0 && $post_id === $home_id;
	}
	return false;
}

/**
 * Body class for scoped admin chrome.
 *
 * @param string $classes
 */
add_filter('admin_body_class', static function (string $classes): string {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen) {
		return $classes;
	}

	$pages = wv_admin_screen_slugs();
	if (isset($pages[$screen->id]) || str_starts_with($screen->id, 'toplevel_page_wvtodoz') || str_starts_with($screen->id, 'wvtodoz_page_')) {
		$classes .= ' wv-admin-screen';
	}

	$cpts = ['wv_case', 'wv_produto', 'wv_cliente', 'wv_servico'];
	if (in_array($screen->post_type, $cpts, true) || ($screen->base === 'post' && $screen->post_type === 'post')) {
		$classes .= ' wv-admin-related';
	}

	if (wv_admin_is_branded_editor()) {
		$classes .= ' wv-admin-editor wv-admin-related';
		if ($screen->base === 'post' && $screen->post_type === 'page') {
			$home_id = wv_home_id();
			$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
			if ($home_id && $post_id === $home_id) {
				$classes .= ' wv-admin-home-editor';
			}
		}
		if (in_array((string) $screen->post_type, $cpts, true)) {
			$classes .= ' wv-admin-cpt-editor';
		}
		$tab = isset($_GET['wv_tab']) ? sanitize_key((string) wp_unslash($_GET['wv_tab'])) : '';
		if ($tab !== '') {
			$classes .= ' wv-editing-tab-' . $tab;
		}
	}

	return $classes;
});

/**
 * Soften Home editor: hide low-value metaboxes so ACF is the focus.
 */
add_action('add_meta_boxes', static function (string $post_type, $post): void {
	if ($post_type !== 'page' || !($post instanceof WP_Post)) {
		return;
	}
	$home_id = wv_home_id();
	if ($home_id <= 0 || (int) $post->ID !== $home_id) {
		return;
	}
	$remove = [
		'commentstatusdiv',
		'commentsdiv',
		'authordiv',
		'revisionsdiv',
		'slugdiv',
		'trackbacksdiv',
		'postcustom',
		'pageparentdiv',
	];
	foreach ($remove as $id) {
		remove_meta_box($id, 'page', 'normal');
		remove_meta_box($id, 'page', 'side');
		remove_meta_box($id, 'page', 'advanced');
	}
}, 99, 2);

/**
 * @return array<string, true>
 */
function wv_admin_screen_slugs(): array {
	$map = [
		'toplevel_page_wvtodoz' => true,
		'wvtodoz_page_wvtodoz-menus' => true,
		'wvtodoz_page_wvtodoz-import' => true,
		'wvtodoz_page_wvtodoz-ajuda' => true,
		'wvtodoz_page_wvtodoz-aparencia' => true,
		'wvtodoz_page_wvtodoz-leads' => true,
	];
	foreach (wv_admin_sections() as $section) {
		$map['wvtodoz_page_' . $section['slug']] = true;
	}
	return $map;
}

/**
 * Enqueue branded admin CSS/JS only where needed.
 */
add_action('admin_enqueue_scripts', static function (string $hook): void {
	$load = str_contains($hook, 'wvtodoz') || wv_admin_is_branded_editor();

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if ($screen && in_array((string) $screen->post_type, ['wv_case', 'wv_produto', 'wv_cliente', 'wv_servico', 'post'], true)) {
		$load = true;
	}

	if (!$load) {
		return;
	}

	wp_enqueue_style(
		'wvtodoz-admin-fonts',
		'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Instrument+Sans:wght@500;600;700;800&display=swap',
		[],
		null
	);

	wp_enqueue_style(
		'wvtodoz-admin',
		WVTODOZ_URI . '/assets/css/admin.css',
		['wvtodoz-admin-fonts'],
		WVTODOZ_VERSION
	);

	wp_enqueue_script(
		'phosphor-icons',
		'https://unpkg.com/@phosphor-icons/web',
		[],
		null,
		true
	);

	wp_enqueue_script(
		'wvtodoz-admin',
		WVTODOZ_URI . '/assets/js/admin.js',
		['jquery', 'phosphor-icons'],
		WVTODOZ_VERSION,
		true
	);

	$tab = isset($_GET['wv_tab']) ? sanitize_key((string) wp_unslash($_GET['wv_tab'])) : '';
	$tab_labels = [
		'hero'       => __('Hero', 'wvtodoz'),
		'sobre'      => __('Sobre', 'wvtodoz'),
		'missao'     => __('Missão', 'wvtodoz'),
		'bridge'     => __('Mobile Bridge', 'wvtodoz'),
		'servicos'   => __('Serviços', 'wvtodoz'),
		'portfolio'  => __('Portfólio', 'wvtodoz'),
		'produtos'   => __('Produtos', 'wvtodoz'),
		'aplicacoes' => __('Aplicações', 'wvtodoz'),
		'blog'       => __('Insights', 'wvtodoz'),
		'insights'   => __('Insights', 'wvtodoz'),
		'contato'    => __('Contato / Footer', 'wvtodoz'),
	];
	// Alias insights → blog ACF tab key
	if ($tab === 'insights') {
		$tab = 'blog';
	}

	$anchors = [
		'hero'       => '#hero',
		'sobre'      => '#sobre',
		'missao'     => '#missao',
		'bridge'     => '#mobile-bridge',
		'servicos'   => '#servicos',
		'portfolio'  => '#portfolio',
		'produtos'   => '#produtos',
		'aplicacoes' => '#aplicacoes',
		'blog'       => '#blog',
		'contato'    => '#contato',
	];

	$is_home_editor = false;
	$is_cpt_editor = false;
	$cpt_label = '';
	$section_hub = admin_url('admin.php?page=wvtodoz');
	$view_url = home_url('/');

	if ($screen && $screen->base === 'post') {
		$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
		if ($screen->post_type === 'page') {
			$home_id = wv_home_id();
			$is_home_editor = $home_id > 0 && $post_id === $home_id;
		}
		$cpts_map = [
			'wv_case'    => ['label' => __('Case', 'wvtodoz'), 'hub' => 'wvtodoz-portfolio', 'view' => home_url('/#portfolio')],
			'wv_produto' => ['label' => __('Produto', 'wvtodoz'), 'hub' => 'wvtodoz-produtos', 'view' => home_url('/#produtos')],
			'wv_servico' => ['label' => __('Serviço', 'wvtodoz'), 'hub' => 'wvtodoz-servicos', 'view' => home_url('/#servicos')],
			'wv_cliente' => ['label' => __('Cliente', 'wvtodoz'), 'hub' => 'wvtodoz-clientes', 'view' => home_url('/#clientes')],
		];
		if (isset($cpts_map[$screen->post_type])) {
			$is_cpt_editor = true;
			$cpt_label = $cpts_map[$screen->post_type]['label'];
			$section_hub = admin_url('admin.php?page=' . $cpts_map[$screen->post_type]['hub']);
			$view_url = $cpts_map[$screen->post_type]['view'];
		}
	}

	$sec_key = $tab === 'blog' ? 'insights' : $tab;
	if ($tab !== '' && isset(wv_admin_sections()[$sec_key])) {
		$sec = wv_admin_sections()[$sec_key];
		$section_hub = admin_url('admin.php?page=' . $sec['slug']);
		$view_url = home_url('/') . (string) ($sec['anchor'] ?? ($anchors[$tab] ?? ''));
	} elseif ($tab !== '' && isset($anchors[$tab])) {
		$view_url = home_url('/') . $anchors[$tab];
		foreach (wv_admin_sections() as $section) {
			if (($section['tab'] ?? '') === $tab) {
				$section_hub = admin_url('admin.php?page=' . $section['slug']);
				break;
			}
		}
	} elseif ($is_home_editor) {
		$view_url = home_url('/');
	}

	$notice = '';
	if ($tab !== '') {
		$notice = sprintf(
			/* translators: %s section name */
			__('Painel de manutenção da seção “%s”. A aba ACF correspondente abre automaticamente — salve para publicar.', 'wvtodoz'),
			$tab_labels[$tab] ?? $tab
		);
	} elseif ($is_home_editor) {
		$notice = __('Você está no editor da Home. Prefira os hubs do painel WV Todoz para ir direto a cada seção.', 'wvtodoz');
	} elseif ($is_cpt_editor) {
		$notice = sprintf(
			/* translators: %s CPT singular label */
			__('Editando um item de %s. Use os campos ACF abaixo — o visual segue o painel WV Todoz.', 'wvtodoz'),
			$cpt_label
		);
	}

	wp_localize_script('wvtodoz-admin', 'wvAdmin', [
		'tab'           => $tab,
		'tabLabel'      => $tab_labels[$tab] ?? $tab,
		'tabLabels'     => $tab_labels,
		'tabKeys'       => [
			'hero'       => 'field_tab_hero',
			'sobre'      => 'field_tab_sobre',
			'missao'     => 'field_tab_missao',
			'bridge'     => 'field_tab_bridge',
			'servicos'   => 'field_tab_servicos',
			'portfolio'  => 'field_tab_portfolio',
			'produtos'   => 'field_tab_produtos',
			'aplicacoes' => 'field_tab_aplicacoes',
			'blog'       => 'field_tab_blog',
			'contato'    => 'field_tab_contato',
		],
		'notice'        => $notice,
		'panelUrl'      => admin_url('admin.php?page=wvtodoz'),
		'sectionHubUrl' => $section_hub,
		'viewUrl'       => $view_url,
		'isHomeEditor'  => $is_home_editor,
		'isCptEditor'   => $is_cpt_editor,
		'isBrandedEditor' => wv_admin_is_branded_editor(),
		'cptLabel'      => $cpt_label,
		'logoUrl'       => function_exists('wv_logo_dark_url')
			? wv_logo_dark_url()
			: (WVTODOZ_URI . '/assets/img/logo-wv-dark.svg'),
		'icons'         => wv_admin_icon_catalog(),
		'i18n'          => [
			'back'        => __('← Painel WV Todoz', 'wvtodoz'),
			'sectionHub'  => __('Voltar ao hub da seção', 'wvtodoz'),
			'viewSite'    => __('Ver no site', 'wvtodoz'),
			'editing'     => __('Editando: ', 'wvtodoz'),
			'editingHome' => __('Editando: Home', 'wvtodoz'),
			'pickIcon'    => __('Escolher ícone', 'wvtodoz'),
			'searchIcon'  => __('Buscar ícone…', 'wvtodoz'),
			'close'       => __('Fechar', 'wvtodoz'),
			'noIcons'     => __('Nenhum ícone encontrado.', 'wvtodoz'),
			'selected'    => __('Selecionado', 'wvtodoz'),
		],
	]);
});

/**
 * Strip leftover top-level CPT menus (safety net if another plugin re-registers UI)
 * and redirect legacy Aparência → Importar WV bookmarks to WV Todoz → Importar.
 */
add_action('admin_menu', static function (): void {
	foreach (['wv_case', 'wv_produto', 'wv_cliente', 'wv_servico', 'wv_lead'] as $cpt) {
		remove_menu_page('edit.php?post_type=' . $cpt);
	}
	remove_submenu_page('themes.php', 'wv-seed');
}, 999);

add_action('load-appearance_page_wv-seed', static function (): void {
	if (!current_user_can('manage_options')) {
		return;
	}
	$args = ['page' => 'wvtodoz-import'];
	if (isset($_GET['wv_seed'])) {
		$args['wv_seed'] = sanitize_key((string) wp_unslash($_GET['wv_seed']));
	}
	wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
	exit;
});
