<?php
/**
 * WV Todoz admin — page renderers.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Shared branded admin header (logo + title + optional actions).
 *
 * @param array<int, array{label:string,url:string,primary?:bool,danger?:bool,attrs?:string}> $actions
 */
function wv_admin_shell_open(string $title, string $subtitle = '', array $actions = []): void {
	$logo = function_exists('wv_logo_dark_url')
		? wv_logo_dark_url()
		: (WVTODOZ_URI . '/assets/img/logo-wv-dark.svg');

	if (!$actions) {
		$actions = [
			[
				'label' => __('Ver site', 'wvtodoz'),
				'url'   => home_url('/'),
			],
			[
				'label'   => __('Importar', 'wvtodoz'),
				'url'     => admin_url('admin.php?page=wvtodoz-import'),
				'primary' => true,
			],
		];
	}

	echo '<div class="wrap wv-admin">';
	echo '<header class="wv-admin__hero">';
	echo '<div class="wv-admin__hero-main">';
	echo '<a class="wv-admin__brand" href="' . esc_url(admin_url('admin.php?page=wvtodoz')) . '">';
	printf(
		'<img class="wv-admin__logo" src="%s" alt="%s" width="160" height="62" decoding="async">',
		esc_url($logo),
		esc_attr__('WV_Todoz', 'wvtodoz')
	);
	echo '</a>';
	echo '<div class="wv-admin__hero-copy">';
	echo '<p class="wv-admin__eyebrow">WV_TODOZ · ADMIN</p>';
	echo '<h1 class="wv-admin__title">' . esc_html($title) . '</h1>';
	if ($subtitle !== '') {
		echo '<p class="wv-admin__subtitle">' . esc_html($subtitle) . '</p>';
	}
	echo '</div></div>';

	if ($actions) {
		echo '<div class="wv-admin__hero-actions">';
		foreach ($actions as $action) {
			$classes = ['wv-admin__hero-btn'];
			if (!empty($action['primary'])) {
				$classes[] = 'wv-admin__hero-btn--primary';
			} else {
				$classes[] = 'wv-admin__hero-btn--ghost';
			}
			$attrs = $action['attrs'] ?? '';
			printf(
				'<a class="%s" href="%s"%s>%s</a>',
				esc_attr(implode(' ', $classes)),
				esc_url((string) $action['url']),
				$attrs !== '' ? ' ' . $attrs : '',
				esc_html((string) $action['label'])
			);
		}
		echo '</div>';
	}

	echo '</header>';
	echo '<div class="wv-admin__body">';
}

function wv_admin_shell_close(): void {
	echo '</div></div>';
}

function wv_admin_notice(string $type, string $message): void {
	$class = 'wv-admin__notice wv-admin__notice--' . sanitize_html_class($type);
	echo '<div class="' . esc_attr($class) . '" role="status"><p>' . esc_html($message) . '</p></div>';
}

/**
 * @param array<int, array{label:string,url:string,primary?:bool,danger?:bool,attrs?:string}> $actions
 */
function wv_admin_actions(array $actions): void {
	if (!$actions) {
		return;
	}
	echo '<div class="wv-admin__actions">';
	foreach ($actions as $action) {
		$classes = ['wv-admin__btn'];
		if (!empty($action['primary'])) {
			$classes[] = 'wv-admin__btn--primary';
		}
		if (!empty($action['danger'])) {
			$classes[] = 'wv-admin__btn--danger';
		}
		if (empty($action['primary']) && empty($action['danger'])) {
			$classes[] = 'wv-admin__btn--ghost';
		}
		$attrs = $action['attrs'] ?? '';
		printf(
			'<a class="%s" href="%s"%s>%s</a>',
			esc_attr(implode(' ', $classes)),
			esc_url($action['url']),
			$attrs !== '' ? ' ' . $attrs : '',
			esc_html($action['label'])
		);
	}
	echo '</div>';
}

function wv_admin_missing_home_state(): void {
	wv_admin_notice('warn', __('A Front Page ainda não está definida. Importe o conteúdo ou configure Configurações → Leitura.', 'wvtodoz'));
	wv_admin_actions([
		[
			'label'   => __('Importar conteúdo', 'wvtodoz'),
			'url'     => admin_url('admin.php?page=wvtodoz-import'),
			'primary' => true,
		],
		[
			'label' => __('Abrir Leitura', 'wvtodoz'),
			'url'   => admin_url('options-reading.php'),
		],
	]);
}

function wv_admin_missing_acf_state(): void {
	wv_admin_notice('warn', __('O plugin Advanced Custom Fields (free) não está ativo. Ative-o para editar os campos das seções.', 'wvtodoz'));
	wv_admin_actions([
		[
			'label'   => __('Ver plugins', 'wvtodoz'),
			'url'     => admin_url('plugins.php'),
			'primary' => true,
		],
	]);
}

/**
 * @param array<string, mixed> $section
 */
function wv_admin_render_section_page(array $section): void {
	if (!current_user_can('edit_posts')) {
		return;
	}

	wv_admin_shell_open((string) $section['title'], (string) $section['blurb']);

	$home_id = wv_home_id();
	$acf_ok  = function_exists('get_field');

	if ($home_id <= 0) {
		wv_admin_missing_home_state();
		wv_admin_shell_close();
		return;
	}

	if (!$acf_ok) {
		wv_admin_missing_acf_state();
	}

	$tab = (string) ($section['tab'] ?? '');
	$edit_url = wv_admin_home_edit_url($tab);
	$anchor = (string) ($section['anchor'] ?? '');
	$view_url = home_url('/') . $anchor;

	$actions = [
		[
			'label'   => (string) ($section['edit_label'] ?? __('Editar seção', 'wvtodoz')),
			'url'     => $edit_url,
			'primary' => empty($section['cpt_primary']),
		],
		[
			'label' => __('Ver no site', 'wvtodoz'),
			'url'   => $view_url,
		],
	];

	if (!empty($section['cpt'])) {
		$cpt = (string) $section['cpt'];
		$list = $cpt === 'post' ? admin_url('edit.php') : admin_url('edit.php?post_type=' . $cpt);
		$new  = $cpt === 'post' ? admin_url('post-new.php') : admin_url('post-new.php?post_type=' . $cpt);
		$actions[] = [
			'label'   => sprintf(
				/* translators: %s content type label */
				__('Gerenciar %s', 'wvtodoz'),
				(string) ($section['cpt_label'] ?? $cpt)
			),
			'url'     => $list,
			'primary' => !empty($section['cpt_primary']),
		];
		$actions[] = [
			'label' => __('Adicionar item', 'wvtodoz'),
			'url'   => $new,
		];
	}

	wv_admin_actions($actions);

	// Live preview of section copy
	$preview = $section['preview'] ?? null;
	if (is_array($preview) && $preview) {
		$get = static function (string $key) use ($acf_ok, $home_id) {
			$raw = $acf_ok ? get_field($key, $home_id) : get_post_meta($home_id, $key, true);
			if (($raw === null || $raw === false || $raw === '') && function_exists('wv_field')) {
				$raw = wv_field($key, '', $home_id);
			}
			return is_string($raw) ? $raw : wv_admin_format_field_value($raw);
		};
		$eyebrow = isset($preview['eyebrow']) ? $get((string) $preview['eyebrow']) : '';
		$line    = isset($preview['line']) ? $get((string) $preview['line']) : '';
		$accent  = isset($preview['accent']) ? $get((string) $preview['accent']) : '';
		$lead    = isset($preview['lead']) ? $get((string) $preview['lead']) : '';

		echo '<section class="wv-admin__preview" aria-label="' . esc_attr__('Pré-visualização da seção', 'wvtodoz') . '">';
		echo '<div class="wv-admin__preview-label">' . esc_html__('Pré-visualização', 'wvtodoz') . '</div>';
		if ($eyebrow !== '' && $eyebrow !== '—') {
			echo '<p class="wv-admin__preview-eyebrow">' . esc_html($eyebrow) . '</p>';
		}
		if (($line !== '' && $line !== '—') || ($accent !== '' && $accent !== '—')) {
			echo '<h2 class="wv-admin__preview-title">';
			if ($line !== '' && $line !== '—') {
				echo '<span>' . esc_html($line) . '</span> ';
			}
			if ($accent !== '' && $accent !== '—') {
				echo '<span class="wv-admin__preview-accent">' . esc_html($accent) . '</span>';
			}
			echo '</h2>';
		}
		if ($lead !== '' && $lead !== '—') {
			echo '<p class="wv-admin__preview-lead">' . esc_html($lead) . '</p>';
		}
		echo '</section>';
	}

	// HTML / visual fields preview
	$html_fields = $section['html_fields'] ?? [];
	if (is_array($html_fields) && $html_fields) {
		echo '<section class="wv-admin__card wv-admin__card--wide">';
		echo '<h2 class="wv-admin__card-title">' . esc_html__('Conteúdo visual (sem HTML cru)', 'wvtodoz') . '</h2>';
		echo '<p class="wv-admin__hint">' . esc_html__('Estes campos usam editor visual no ACF. Edite pela Home (aba indicada) — a prévia abaixo mostra o resultado sanitizado.', 'wvtodoz') . '</p>';
		foreach ($html_fields as $key => $label) {
			$raw = $acf_ok ? get_field((string) $key, $home_id) : get_post_meta($home_id, (string) $key, true);
			if (($raw === null || $raw === false || $raw === '') && function_exists('wv_field')) {
				$raw = wv_field((string) $key, '', $home_id);
			}
			echo '<div class="wv-admin__html-preview">';
			echo '<div class="wv-admin__html-preview-meta">';
			echo '<strong>' . esc_html((string) $label) . '</strong>';
			echo '<a class="wv-admin__btn wv-admin__btn--ghost" href="' . esc_url($edit_url) . '">' . esc_html__('Editar no editor visual', 'wvtodoz') . '</a>';
			echo '</div>';
			if ($raw === '' || $raw === null || $raw === false) {
				echo '<p class="wv-admin__muted">' . esc_html__('Vazio — preencha pelo editor visual da Home.', 'wvtodoz') . '</p>';
			} else {
				echo '<div class="wv-admin__html-preview-body">' . wp_kses_post((string) $raw) . '</div>';
			}
			echo '</div>';
		}
		echo '</section>';
	}

	echo '<div class="wv-admin__grid wv-admin__grid--2">';

	// Summary card
	echo '<section class="wv-admin__card">';
	echo '<h2 class="wv-admin__card-title">' . esc_html__('Valores atuais', 'wvtodoz') . '</h2>';
	$fields = $section['fields'] ?? [];
	if (is_array($fields) && $fields) {
		echo '<dl class="wv-admin__dl">';
		foreach ($fields as $key => $label) {
			$raw = $acf_ok ? get_field($key, $home_id) : get_post_meta($home_id, $key, true);
			if (($raw === null || $raw === false || $raw === '') && function_exists('wv_field')) {
				$raw = wv_field((string) $key, '', $home_id);
			}
			echo '<div class="wv-admin__dl-row">';
			echo '<dt>' . esc_html((string) $label) . '</dt>';
			echo '<dd>' . esc_html(wv_admin_format_field_value($raw)) . '</dd>';
			echo '</div>';
		}
		echo '</dl>';
	} else {
		echo '<p class="wv-admin__muted">' . esc_html__('Sem resumo de campos para esta seção.', 'wvtodoz') . '</p>';
	}
	echo '</section>';

	// Editable checklist
	echo '<section class="wv-admin__card">';
	echo '<h2 class="wv-admin__card-title">' . esc_html__('O que você pode editar', 'wvtodoz') . '</h2>';
	$editable = $section['editable'] ?? [];
	if (is_array($editable) && $editable) {
		echo '<ul class="wv-admin__list">';
		foreach ($editable as $item) {
			echo '<li>' . esc_html((string) $item) . '</li>';
		}
		echo '</ul>';
	}
	echo '<p class="wv-admin__hint">';
	echo esc_html__('Ao clicar em editar, a Home abre com a aba ACF correspondente destacada (quando o ACF estiver ativo).', 'wvtodoz');
	echo '</p>';
	echo '</section>';

	echo '</div>';

	// CPT list
	if (!empty($section['cpt'])) {
		$cpt = (string) $section['cpt'];
		$count = wv_admin_count_posts($cpt);
		$show_icons = !empty($section['show_icons']);
		echo '<section class="wv-admin__card wv-admin__card--wide">';
		echo '<div class="wv-admin__card-head">';
		echo '<h2 class="wv-admin__card-title">' . esc_html((string) ($section['cpt_label'] ?? $cpt)) . '</h2>';
		echo '<span class="wv-admin__badge">' . esc_html((string) $count) . '</span>';
		echo '</div>';

		if ($show_icons) {
			echo '<p class="wv-admin__hint">' . esc_html__('Ícones: ao editar um serviço, use o botão “Escolher ícone” (seletor visual Phosphor) — não digite classes à mão.', 'wvtodoz') . '</p>';
		}

		$posts = get_posts([
			'post_type'      => $cpt,
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
		]);

		if (!$posts) {
			echo '<div class="wv-admin__empty">';
			echo '<p>' . esc_html__('Nenhum item publicado ainda.', 'wvtodoz') . '</p>';
			$new = $cpt === 'post' ? admin_url('post-new.php') : admin_url('post-new.php?post_type=' . $cpt);
			echo '<a class="wv-admin__btn wv-admin__btn--primary" href="' . esc_url($new) . '">' . esc_html__('Adicionar o primeiro', 'wvtodoz') . '</a>';
			echo '</div>';
		} else {
			echo '<ul class="wv-admin__items">';
			foreach ($posts as $p) {
				$edit = get_edit_post_link($p->ID) ?: '#';
				$icon = '';
				if ($show_icons) {
					$icon = (string) ($acf_ok ? get_field('wv_servico_icon', $p->ID) : get_post_meta($p->ID, 'wv_servico_icon', true));
					if ($icon === '') {
						$icon = 'ph-robot';
					}
				}
				$title_html = '';
				if ($show_icons && $acf_ok) {
					$title_html = (string) get_field('wv_servico_title_html', $p->ID);
				}
				echo '<li class="wv-admin__item' . ($show_icons ? ' wv-admin__item--icon' : '') . '">';
				if ($show_icons) {
					echo '<span class="wv-admin__item-icon" aria-hidden="true"><i class="ph ' . esc_attr($icon) . '"></i></span>';
				}
				echo '<div class="wv-admin__item-main">';
				echo '<a href="' . esc_url($edit) . '">' . esc_html(get_the_title($p)) . '</a>';
				if ($title_html !== '') {
					echo '<div class="wv-admin__item-html">' . wp_kses($title_html, [
						'span'   => ['class' => true],
						'br'     => [],
						'em'     => [],
						'strong' => [],
						'p'      => [],
					]) . '</div>';
				}
				echo '</div>';
				echo '<span class="wv-admin__muted">' . esc_html(get_post_status($p) === 'publish' ? __('Publicado', 'wvtodoz') : get_post_status($p)) . '</span>';
				echo '</li>';
			}
			echo '</ul>';
		}
		echo '</section>';
	}

	wv_admin_shell_close();
}

function wv_admin_render_dashboard(): void {
	if (!current_user_can('edit_posts')) {
		return;
	}

	wv_admin_shell_open(
		__('Painel WV Todoz', 'wvtodoz'),
		__('Manutenção do conteúdo da landing — uma página por seção do site.', 'wvtodoz')
	);

	$home_id = wv_home_id();
	$acf_ok  = function_exists('get_field') && function_exists('acf_add_local_field_group');
	$seed    = wv_admin_seed_status();
	$theme   = wp_get_theme();

	// Status strip
	echo '<section class="wv-admin__status">';
	$items = [
		[
			'label' => __('Tema', 'wvtodoz'),
			'value' => $theme->get('Name') . ' ' . WVTODOZ_VERSION,
			'ok'    => true,
		],
		[
			'label' => __('ACF', 'wvtodoz'),
			'value' => $acf_ok ? __('Ativo', 'wvtodoz') : __('Inativo', 'wvtodoz'),
			'ok'    => $acf_ok,
		],
		[
			'label' => __('Front Page', 'wvtodoz'),
			'value' => $home_id ? get_the_title($home_id) : __('Não definida', 'wvtodoz'),
			'ok'    => $home_id > 0,
		],
		[
			'label' => __('Seed', 'wvtodoz'),
			'value' => $seed ? sprintf(
				/* translators: %s version */
				__('v%s', 'wvtodoz'),
				(string) ($seed['version'] ?? '—')
			) : __('Pendente', 'wvtodoz'),
			'ok'    => (bool) $seed,
		],
	];
	foreach ($items as $item) {
		$class = 'wv-admin__status-item' . ($item['ok'] ? ' is-ok' : ' is-bad');
		echo '<div class="' . esc_attr($class) . '">';
		echo '<span class="wv-admin__status-label">' . esc_html($item['label']) . '</span>';
		echo '<strong class="wv-admin__status-value">' . esc_html($item['value']) . '</strong>';
		echo '</div>';
	}
	echo '</section>';

	wv_admin_actions([
		[
			'label'   => __('Editar Home completa', 'wvtodoz'),
			'url'     => wv_admin_home_edit_url(),
			'primary' => true,
		],
		[
			'label' => __('Importar conteúdo', 'wvtodoz'),
			'url'   => admin_url('admin.php?page=wvtodoz-import'),
		],
		[
			'label' => __('Ver site', 'wvtodoz'),
			'url'   => home_url('/'),
		],
	]);

	if (!$seed) {
		wv_admin_notice('info', __('O conteúdo inicial ainda não foi importado. Use Importar conteúdo para popular Home + CPTs.', 'wvtodoz'));
	}

	echo '<h2 class="wv-admin__section-label">' . esc_html__('Seções do site', 'wvtodoz') . '</h2>';
	echo '<div class="wv-admin__grid wv-admin__grid--cards">';

	foreach (wv_admin_sections() as $key => $section) {
		$url = admin_url('admin.php?page=' . $section['slug']);
		$count_html = '';
		if (!empty($section['cpt'])) {
			$n = wv_admin_count_posts((string) $section['cpt']);
			$count_html = '<span class="wv-admin__badge">' . esc_html((string) $n) . '</span>';
		}
		echo '<a class="wv-admin__nav-card" href="' . esc_url($url) . '">';
		echo '<div class="wv-admin__nav-card-top">';
		echo '<span class="wv-admin__nav-card-title">' . esc_html((string) $section['menu_title']) . '</span>';
		echo $count_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
		echo '<p class="wv-admin__nav-card-blurb">' . esc_html((string) $section['blurb']) . '</p>';
		echo '<span class="wv-admin__nav-card-cta">' . esc_html__('Abrir manutenção', 'wvtodoz') . '</span>';
		echo '</a>';
	}

	// Utility cards
	$utils = [
		[
			'title' => __('Menus', 'wvtodoz'),
			'blurb' => __('Navegação desktop, mobile e rodapé.', 'wvtodoz'),
			'url'   => admin_url('admin.php?page=wvtodoz-menus'),
		],
		[
			'title' => __('Importar', 'wvtodoz'),
			'blurb' => __('Popular ou atualizar conteúdo a partir do seed.', 'wvtodoz'),
			'url'   => admin_url('admin.php?page=wvtodoz-import'),
		],
		[
			'title' => __('Ajuda', 'wvtodoz'),
			'blurb' => __('Como editar cada área sem quebrar o layout.', 'wvtodoz'),
			'url'   => admin_url('admin.php?page=wvtodoz-ajuda'),
		],
	];
	foreach ($utils as $u) {
		echo '<a class="wv-admin__nav-card wv-admin__nav-card--util" href="' . esc_url($u['url']) . '">';
		echo '<div class="wv-admin__nav-card-top"><span class="wv-admin__nav-card-title">' . esc_html($u['title']) . '</span></div>';
		echo '<p class="wv-admin__nav-card-blurb">' . esc_html($u['blurb']) . '</p>';
		echo '<span class="wv-admin__nav-card-cta">' . esc_html__('Abrir', 'wvtodoz') . '</span>';
		echo '</a>';
	}

	echo '</div>';
	wv_admin_shell_close();
}

function wv_admin_render_menus(): void {
	if (!current_user_can('edit_theme_options') && !current_user_can('edit_posts')) {
		return;
	}

	wv_admin_shell_open(
		__('Menus / Navegação', 'wvtodoz'),
		__('Menus do WordPress usados no header, mobile e rodapé.', 'wvtodoz')
	);

	wv_admin_actions([
		[
			'label'   => __('Abrir Menus do WordPress', 'wvtodoz'),
			'url'     => admin_url('nav-menus.php'),
			'primary' => true,
		],
		[
			'label' => __('Ver site', 'wvtodoz'),
			'url'   => home_url('/'),
		],
	]);

	echo '<section class="wv-admin__card">';
	echo '<h2 class="wv-admin__card-title">' . esc_html__('Locais do tema', 'wvtodoz') . '</h2>';
	echo '<ul class="wv-admin__list">';
	echo '<li><strong>' . esc_html__('Menu principal', 'wvtodoz') . '</strong> — ' . esc_html__('navegação desktop.', 'wvtodoz') . '</li>';
	echo '<li><strong>' . esc_html__('Menu mobile', 'wvtodoz') . '</strong> — ' . esc_html__('overlay no celular.', 'wvtodoz') . '</li>';
	echo '<li><strong>' . esc_html__('Menu rodapé — Navegação', 'wvtodoz') . '</strong> — ' . esc_html__('coluna Navegação do footer.', 'wvtodoz') . '</li>';
	echo '</ul>';
	echo '<p class="wv-admin__hint">' . esc_html__('Sem menu atribuído, o tema usa links padrão (#sobre, #servicos, etc.). A coluna Produtos do footer lista automaticamente o CPT Produtos.', 'wvtodoz') . '</p>';
	echo '</section>';

	wv_admin_shell_close();
}

function wv_admin_render_import(): void {
	if (!current_user_can('manage_options')) {
		wv_admin_shell_open(__('Importar conteúdo', 'wvtodoz'));
		wv_admin_notice('warn', __('Você precisa de permissão de administrador para importar conteúdo.', 'wvtodoz'));
		wv_admin_shell_close();
		return;
	}

	wv_admin_shell_open(
		__('Importar conteúdo WV', 'wvtodoz'),
		__('Cria ou atualiza a Home (campos ACF) e popula Cases, Produtos, Clientes, Serviços e Insights.', 'wvtodoz')
	);

	if (isset($_GET['wv_seed'])) {
		$status = sanitize_key((string) wp_unslash($_GET['wv_seed']));
		if ($status === 'ok') {
			wv_admin_notice('ok', __('Conteúdo WV importado com sucesso. Use as páginas de seção para manter cada bloco.', 'wvtodoz'));
		} elseif ($status === 'fail') {
			wv_admin_notice('bad', __('Falha ao importar conteúdo WV. Tente novamente.', 'wvtodoz'));
		}
	}

	$done  = get_option(WV_SEED_OPTION);
	$url   = wp_nonce_url(admin_url('admin-post.php?action=wv_import_content'), 'wv_import_content');
	$force = wp_nonce_url(admin_url('admin-post.php?action=wv_import_content&force=1'), 'wv_import_content');

	echo '<section class="wv-admin__card">';
	echo '<h2 class="wv-admin__card-title">' . esc_html__('Como funciona', 'wvtodoz') . '</h2>';
	echo '<ol class="wv-admin__steps">';
	echo '<li>' . esc_html__('Ative o plugin Advanced Custom Fields (free).', 'wvtodoz') . '</li>';
	echo '<li>' . esc_html__('Clique em Importar (ou Reimportar para sobrescrever textos).', 'wvtodoz') . '</li>';
	echo '<li>' . esc_html__('Edite cada seção pelo menu WV Todoz — não precisa caçar páginas soltas.', 'wvtodoz') . '</li>';
	echo '</ol>';
	echo '<p class="wv-admin__hint"><strong>' . esc_html__('Atenção:', 'wvtodoz') . '</strong> ';
	echo esc_html__('Reimportar atualiza textos ACF e CPTs a partir dos defaults do tema. Imagens já anexadas não são baixadas de novo.', 'wvtodoz');
	echo '</p>';

	if ($done) {
		$ver = is_array($done) && isset($done['version']) ? (string) $done['version'] : '';
		$message = __('Seed já executado.', 'wvtodoz');
		if ($ver !== '') {
			$message .= ' ' . sprintf(/* translators: %s version */ __('Versão: %s', 'wvtodoz'), $ver);
		}
		echo '<p class="wv-admin__muted">' . esc_html($message) . '</p>';
		wv_admin_actions([
			[
				'label'   => __('Reimportar / atualizar', 'wvtodoz'),
				'url'     => $force,
				'primary' => true,
				'danger'  => true,
				'attrs'   => 'data-wv-confirm="' . esc_attr__('Isso sobrescreve textos da Home e CPTs. Continuar?', 'wvtodoz') . '"',
			],
			[
				'label' => __('Ir ao Painel', 'wvtodoz'),
				'url'   => admin_url('admin.php?page=wvtodoz'),
			],
		]);
	} else {
		wv_admin_actions([
			[
				'label'   => __('Importar conteúdo WV', 'wvtodoz'),
				'url'     => $url,
				'primary' => true,
			],
		]);
	}
	echo '</section>';

	wv_admin_shell_close();
}

function wv_admin_render_help(): void {
	if (!current_user_can('edit_posts')) {
		return;
	}

	wv_admin_shell_open(
		__('Ajuda — manutenção WV Todoz', 'wvtodoz'),
		__('Guia curto para editores não técnicos.', 'wvtodoz')
	);

	echo '<div class="wv-admin__grid wv-admin__grid--2">';

	echo '<section class="wv-admin__card">';
	echo '<h2 class="wv-admin__card-title">' . esc_html__('Fluxo recomendado', 'wvtodoz') . '</h2>';
	echo '<ol class="wv-admin__steps">';
	echo '<li>' . esc_html__('Abra WV Todoz → Painel para ver o status.', 'wvtodoz') . '</li>';
	echo '<li>' . esc_html__('Escolha a seção que quer alterar (Hero, Sobre, Cases…).', 'wvtodoz') . '</li>';
	echo '<li>' . esc_html__('Revise os valores atuais e clique no botão principal de edição.', 'wvtodoz') . '</li>';
	echo '<li>' . esc_html__('Na Home, use a aba ACF da seção (ela já abre focada quando possível).', 'wvtodoz') . '</li>';
	echo '<li>' . esc_html__('Para cards repetíveis (cases, produtos, etc.), use “Gerenciar” / “Adicionar item”.', 'wvtodoz') . '</li>';
	echo '</ol>';
	echo '</section>';

	echo '<section class="wv-admin__card">';
	echo '<h2 class="wv-admin__card-title">' . esc_html__('Dicas', 'wvtodoz') . '</h2>';
	echo '<ul class="wv-admin__list">';
	echo '<li>' . esc_html__('Ordem dos CPTs: campo Ordem (menu_order) — menor número aparece primeiro.', 'wvtodoz') . '</li>';
	echo '<li>' . esc_html__('Clientes usam a Imagem destacada como logo.', 'wvtodoz') . '</li>';
	echo '<li>' . esc_html__('Não precisa do ACF Pro: Options Page e Repeater não são usados.', 'wvtodoz') . '</li>';
	echo '<li>' . esc_html__('Reimportar sobrescreve textos — use só quando quiser voltar ao conteúdo padrão.', 'wvtodoz') . '</li>';
	echo '<li>' . esc_html__('Menus ficam em WV Todoz → Menus (atalho para Aparência → Menus).', 'wvtodoz') . '</li>';
	echo '</ul>';
	echo '</section>';

	echo '</div>';

	wv_admin_actions([
		[
			'label'   => __('Ir ao Painel', 'wvtodoz'),
			'url'     => admin_url('admin.php?page=wvtodoz'),
			'primary' => true,
		],
		[
			'label' => __('Ver site', 'wvtodoz'),
			'url'   => home_url('/'),
		],
	]);

	wv_admin_shell_close();
}
