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

	// CPT + front-page editor branding
	$cpts = ['wv_case', 'wv_produto', 'wv_cliente', 'wv_servico'];
	if (in_array($screen->post_type, $cpts, true) || ($screen->base === 'post' && $screen->post_type === 'post')) {
		$classes .= ' wv-admin-related';
	}
	if ($screen->base === 'post' && $screen->post_type === 'page') {
		$home_id = wv_home_id();
		$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
		if ($home_id && $post_id === $home_id) {
			$classes .= ' wv-admin-related wv-admin-home-editor';
		}
	}

	return $classes;
});

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
	$load = false;
	$load_tab_js = false;

	if (str_contains($hook, 'wvtodoz')) {
		$load = true;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if ($screen) {
		$cpts = ['wv_case', 'wv_produto', 'wv_cliente', 'wv_servico', 'post'];
		if (in_array($screen->post_type, $cpts, true)) {
			$load = true;
		}
		if ($screen->base === 'post' && $screen->post_type === 'page') {
			$home_id = wv_home_id();
			$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
			if ($home_id && $post_id === $home_id) {
				$load = true;
				$load_tab_js = true;
			}
		}
		if (!empty($_GET['wv_tab'])) {
			$load_tab_js = true;
			$load = true;
		}
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

	// Phosphor for icon picker previews in admin.
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
		['phosphor-icons'],
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
		'contato'    => __('Contato / Footer', 'wvtodoz'),
	];

	$is_home_editor = false;
	$section_hub = admin_url('admin.php?page=wvtodoz');
	if ($screen && $screen->base === 'post' && $screen->post_type === 'page') {
		$home_id = wv_home_id();
		$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
		$is_home_editor = $home_id > 0 && $post_id === $home_id;
	}
	if ($tab !== '' && isset(wv_admin_sections()[$tab === 'blog' ? 'insights' : $tab])) {
		$sec_key = $tab === 'blog' ? 'insights' : $tab;
		$sec = wv_admin_sections()[$sec_key] ?? null;
		if (is_array($sec)) {
			$section_hub = admin_url('admin.php?page=' . $sec['slug']);
		}
	} elseif ($tab === 'bridge') {
		$section_hub = admin_url('admin.php?page=wvtodoz-bridge');
	}

	$notice = '';
	if ($tab !== '') {
		$notice = sprintf(
			/* translators: %s section name */
			__('A aba ACF “%s” deve abrir automaticamente. Salve a página para publicar no site.', 'wvtodoz'),
			$tab_labels[$tab] ?? $tab
		);
	} elseif ($is_home_editor) {
		$notice = __('Você está editando a Home. Use as abas ACF ou volte ao painel WV Todoz para ir direto a uma seção.', 'wvtodoz');
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
		'isHomeEditor'  => $is_home_editor,
		'logoUrl'       => function_exists('wv_logo_dark_url')
			? wv_logo_dark_url()
			: (WVTODOZ_URI . '/assets/img/logo-wv-dark.svg'),
		'icons'         => wv_admin_icon_catalog(),
		'i18n'          => [
			'back'        => __('← Painel WV Todoz', 'wvtodoz'),
			'sectionHub'  => __('Hub da seção', 'wvtodoz'),
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
 * Redirect legacy Aparência → Importar WV to the new panel page.
 */
add_action('admin_menu', static function (): void {
	// Keep seed.php theme page; we redirect visitors to the branded import screen.
}, 20);

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
