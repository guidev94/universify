<?php
/**
 * WV Todoz admin — section registry (one hub per site section).
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

/**
 * All content hubs under WV Todoz menu.
 *
 * @return array<string, array<string, mixed>>
 */
function wv_admin_sections(): array {
	return [
		'hero' => [
			'slug'        => 'wvtodoz-hero',
			'title'       => __('Hero', 'wvtodoz'),
			'menu_title'  => __('Hero', 'wvtodoz'),
			'tab'         => 'hero',
			'anchor'      => '#hero',
			'blurb'       => __('Primeira dobra do site: marca, título, vídeo de fundo e CTAs principais.', 'wvtodoz'),
			'edit_label'  => __('Editar Hero', 'wvtodoz'),
			'fields'      => [
				'wv_hero_badge'         => __('Badge', 'wvtodoz'),
				'wv_hero_eyebrow'       => __('Eyebrow', 'wvtodoz'),
				'wv_hero_title_line'    => __('Título (linha)', 'wvtodoz'),
				'wv_hero_title_accent'  => __('Título (destaque)', 'wvtodoz'),
				'wv_hero_lead'          => __('Lead', 'wvtodoz'),
				'wv_hero_cta_primary'   => __('CTA primário', 'wvtodoz'),
				'wv_hero_cta_secondary' => __('CTA secundário', 'wvtodoz'),
				'wv_hero_show_logos'    => __('Logos de produtos', 'wvtodoz'),
			],
			'editable'    => [
				__('Badge e eyebrow', 'wvtodoz'),
				__('Título (linha + destaque lima)', 'wvtodoz'),
				__('Lead e CTAs', 'wvtodoz'),
				__('Vídeo de fundo (MP4)', 'wvtodoz'),
				__('Toggle dos logos de produtos', 'wvtodoz'),
			],
			'preview'     => [
				'eyebrow' => 'wv_hero_eyebrow',
				'line'    => 'wv_hero_title_line',
				'accent'  => 'wv_hero_title_accent',
				'lead'    => 'wv_hero_lead',
			],
		],
		'sobre' => [
			'slug'        => 'wvtodoz-sobre',
			'title'       => __('Sobre', 'wvtodoz'),
			'menu_title'  => __('Sobre', 'wvtodoz'),
			'tab'         => 'sobre',
			'anchor'      => '#sobre',
			'blurb'       => __('Quem somos: título, números, CTAs e imagem do escritório.', 'wvtodoz'),
			'edit_label'  => __('Editar Sobre', 'wvtodoz'),
			'fields'      => [
				'wv_sobre_title_line'   => __('Título (linha)', 'wvtodoz'),
				'wv_sobre_title_accent' => __('Título (destaque)', 'wvtodoz'),
				'wv_sobre_lead'         => __('Lead', 'wvtodoz'),
				'wv_sobre_stat1_n'      => __('Stat 1', 'wvtodoz'),
				'wv_sobre_stat2_n'      => __('Stat 2', 'wvtodoz'),
				'wv_sobre_cta_primary'  => __('CTA primário', 'wvtodoz'),
			],
			'editable'    => [
				__('Título e lead', 'wvtodoz'),
				__('Stats (números e rótulos)', 'wvtodoz'),
				__('CTAs', 'wvtodoz'),
				__('Imagem do escritório', 'wvtodoz'),
			],
			'preview'     => [
				'line'   => 'wv_sobre_title_line',
				'accent' => 'wv_sobre_title_accent',
				'lead'   => 'wv_sobre_lead',
			],
		],
		'missao' => [
			'slug'        => 'wvtodoz-missao',
			'title'       => __('Missão', 'wvtodoz'),
			'menu_title'  => __('Missão', 'wvtodoz'),
			'tab'         => 'missao',
			'anchor'      => '#missao',
			'blurb'       => __('Painel de missão com copy, produtos próprios e CTA de conversa.', 'wvtodoz'),
			'edit_label'  => __('Editar Missão', 'wvtodoz'),
			'fields'      => [
				'wv_missao_eyebrow'  => __('Eyebrow', 'wvtodoz'),
				'wv_missao_title'    => __('Título', 'wvtodoz'),
				'wv_missao_support'  => __('Apoio', 'wvtodoz'),
				'wv_missao_product_a'=> __('Produto A', 'wvtodoz'),
				'wv_missao_product_b'=> __('Produto B', 'wvtodoz'),
				'wv_missao_cta'      => __('CTA', 'wvtodoz'),
			],
			'editable'    => [
				__('Eyebrow, título e texto de apoio', 'wvtodoz'),
				__('Nomes dos produtos A/B', 'wvtodoz'),
				__('Rótulos dos stats (anos, projetos, produtos)', 'wvtodoz'),
				__('CTA', 'wvtodoz'),
			],
			'preview'     => [
				'eyebrow' => 'wv_missao_eyebrow',
				'line'    => 'wv_missao_title',
				'lead'    => 'wv_missao_support',
			],
		],
		'bridge' => [
			'slug'        => 'wvtodoz-bridge',
			'title'       => __('Ponte Mobile', 'wvtodoz'),
			'menu_title'  => __('Ponte Mobile', 'wvtodoz'),
			'tab'         => 'bridge',
			'anchor'      => '#mobile-bridge',
			'blurb'       => __('Bloco de transição “mobile-first” entre missões e serviços.', 'wvtodoz'),
			'edit_label'  => __('Editar Ponte Mobile', 'wvtodoz'),
			'fields'      => [
				'wv_bridge_line1'         => __('Linha 1', 'wvtodoz'),
				'wv_bridge_accent'        => __('Destaque lime', 'wvtodoz'),
				'wv_bridge_eyebrow'       => __('Eyebrow', 'wvtodoz'),
				'wv_bridge_lead'          => __('Lead', 'wvtodoz'),
				'wv_bridge_cta'           => __('CTA', 'wvtodoz'),
			],
			'editable'    => [
				__('Headline em 4 linhas + destaque lime', 'wvtodoz'),
				__('Eyebrow e lead', 'wvtodoz'),
				__('CTA', 'wvtodoz'),
			],
			'preview'     => [
				'line'   => 'wv_bridge_line1',
				'accent' => 'wv_bridge_accent',
				'lead'   => 'wv_bridge_lead',
			],
		],
		'servicos' => [
			'slug'        => 'wvtodoz-servicos',
			'title'       => __('Serviços', 'wvtodoz'),
			'menu_title'  => __('Serviços', 'wvtodoz'),
			'tab'         => 'servicos',
			'anchor'      => '#servicos',
			'blurb'       => __('Introdução da seção, processo em 4 passos e cards do CPT Serviços.', 'wvtodoz'),
			'edit_label'  => __('Editar intro / processo', 'wvtodoz'),
			'fields'      => [
				'wv_servicos_eyebrow'      => __('Eyebrow', 'wvtodoz'),
				'wv_servicos_title_line'   => __('Título (linha)', 'wvtodoz'),
				'wv_servicos_title_accent' => __('Título (destaque)', 'wvtodoz'),
				'wv_servicos_intro'        => __('Introdução', 'wvtodoz'),
				'wv_servicos_quote_cta'    => __('CTA', 'wvtodoz'),
			],
			'editable'    => [
				__('Eyebrow, título e introdução', 'wvtodoz'),
				__('4 passos do processo', 'wvtodoz'),
				__('Nota e CTA da seção', 'wvtodoz'),
				__('Ícones dos cards via seletor visual Phosphor', 'wvtodoz'),
			],
			'preview'     => [
				'eyebrow' => 'wv_servicos_eyebrow',
				'line'    => 'wv_servicos_title_line',
				'accent'  => 'wv_servicos_title_accent',
				'lead'    => 'wv_servicos_intro',
			],
			'cpt'         => 'wv_servico',
			'cpt_label'   => __('Cards de serviços', 'wvtodoz'),
			'show_icons'  => true,
		],
		'portfolio' => [
			'slug'        => 'wvtodoz-portfolio',
			'title'       => __('Portfólio / Cases', 'wvtodoz'),
			'menu_title'  => __('Portfólio', 'wvtodoz'),
			'tab'         => 'portfolio',
			'anchor'      => '#portfolio',
			'blurb'       => __('Introdução do portfólio e cases publicados no CPT Cases.', 'wvtodoz'),
			'edit_label'  => __('Editar intro do Portfólio', 'wvtodoz'),
			'fields'      => [
				'wv_portfolio_eyebrow'      => __('Eyebrow', 'wvtodoz'),
				'wv_portfolio_title_line'   => __('Título (linha)', 'wvtodoz'),
				'wv_portfolio_title_accent' => __('Título (destaque)', 'wvtodoz'),
				'wv_portfolio_lead'         => __('Lead', 'wvtodoz'),
				'wv_portfolio_cta'          => __('CTA', 'wvtodoz'),
			],
			'editable'    => [
				__('Eyebrow, título e lead', 'wvtodoz'),
				__('Texto do CTA “Explorar todos”', 'wvtodoz'),
				__('Título da faixa de clientes (editor visual na aba Portfólio)', 'wvtodoz'),
			],
			'preview'     => [
				'eyebrow' => 'wv_portfolio_eyebrow',
				'line'    => 'wv_portfolio_title_line',
				'accent'  => 'wv_portfolio_title_accent',
				'lead'    => 'wv_portfolio_lead',
			],
			'html_fields' => [
				'wv_clientes_title' => __('Título da faixa de clientes', 'wvtodoz'),
			],
			'cpt'         => 'wv_case',
			'cpt_label'   => __('Cases', 'wvtodoz'),
		],
		'produtos' => [
			'slug'        => 'wvtodoz-produtos',
			'title'       => __('Produtos', 'wvtodoz'),
			'menu_title'  => __('Produtos', 'wvtodoz'),
			'tab'         => 'produtos',
			'anchor'      => '#produtos',
			'blurb'       => __('Introdução da seção e cards do ecossistema (CPT Produtos).', 'wvtodoz'),
			'edit_label'  => __('Editar intro de Produtos', 'wvtodoz'),
			'fields'      => [
				'wv_produtos_eyebrow'      => __('Eyebrow', 'wvtodoz'),
				'wv_produtos_title_line'   => __('Título (linha)', 'wvtodoz'),
				'wv_produtos_title_accent' => __('Título (destaque)', 'wvtodoz'),
				'wv_produtos_lead'         => __('Lead', 'wvtodoz'),
			],
			'editable'    => [
				__('Eyebrow, título e lead da seção', 'wvtodoz'),
			],
			'preview'     => [
				'eyebrow' => 'wv_produtos_eyebrow',
				'line'    => 'wv_produtos_title_line',
				'accent'  => 'wv_produtos_title_accent',
				'lead'    => 'wv_produtos_lead',
			],
			'cpt'         => 'wv_produto',
			'cpt_label'   => __('Produtos', 'wvtodoz'),
		],
		'aplicacoes' => [
			'slug'        => 'wvtodoz-aplicacoes',
			'title'       => __('Aplicações', 'wvtodoz'),
			'menu_title'  => __('Aplicações', 'wvtodoz'),
			'tab'         => 'aplicacoes',
			'anchor'      => '#aplicacoes',
			'blurb'       => __('Bloco de benefícios / automação com imagem, chip e CTA.', 'wvtodoz'),
			'edit_label'  => __('Editar Aplicações', 'wvtodoz'),
			'fields'      => [
				'wv_aplicacoes_eyebrow'     => __('Eyebrow', 'wvtodoz'),
				'wv_aplicacoes_title_line'  => __('Título (linha)', 'wvtodoz'),
				'wv_aplicacoes_title_accent'=> __('Título (destaque)', 'wvtodoz'),
				'wv_aplicacoes_lead'        => __('Lead', 'wvtodoz'),
				'wv_aplicacoes_cta'         => __('CTA', 'wvtodoz'),
			],
			'editable'    => [
				__('Copy completa da seção', 'wvtodoz'),
				__('Chip, 3 benefícios e CTA', 'wvtodoz'),
				__('Imagem', 'wvtodoz'),
			],
			'preview'     => [
				'eyebrow' => 'wv_aplicacoes_eyebrow',
				'line'    => 'wv_aplicacoes_title_line',
				'accent'  => 'wv_aplicacoes_title_accent',
				'lead'    => 'wv_aplicacoes_lead',
			],
		],
		'insights' => [
			'slug'        => 'wvtodoz-insights',
			'title'       => __('Insights / Blog', 'wvtodoz'),
			'menu_title'  => __('Insights', 'wvtodoz'),
			'tab'         => 'blog',
			'anchor'      => '#blog',
			'blurb'       => __('Introdução da faixa de insights e posts do blog.', 'wvtodoz'),
			'edit_label'  => __('Editar intro de Insights', 'wvtodoz'),
			'fields'      => [
				'wv_blog_eyebrow'      => __('Eyebrow', 'wvtodoz'),
				'wv_blog_title_line'   => __('Título (linha)', 'wvtodoz'),
				'wv_blog_title_accent' => __('Título (destaque)', 'wvtodoz'),
				'wv_blog_cta'          => __('CTA', 'wvtodoz'),
			],
			'editable'    => [
				__('Eyebrow, título e CTA da seção', 'wvtodoz'),
			],
			'preview'     => [
				'eyebrow' => 'wv_blog_eyebrow',
				'line'    => 'wv_blog_title_line',
				'accent'  => 'wv_blog_title_accent',
			],
			'cpt'         => 'post',
			'cpt_label'   => __('Posts / Insights', 'wvtodoz'),
		],
		'contato' => [
			'slug'        => 'wvtodoz-contato',
			'title'       => __('Contato / Footer', 'wvtodoz'),
			'menu_title'  => __('Contato / Footer', 'wvtodoz'),
			'tab'         => 'contato',
			'anchor'      => '#contato',
			'blurb'       => __('CTA final, WhatsApp, dados do rodapé, redes e textos dos modais.', 'wvtodoz'),
			'edit_label'  => __('Editar Contato / Footer', 'wvtodoz'),
			'fields'      => [
				'wv_contato_title_before'    => __('CTA — antes', 'wvtodoz'),
				'wv_contato_title_highlight' => __('CTA — destaque', 'wvtodoz'),
				'wv_contato_phone'           => __('Telefone', 'wvtodoz'),
				'wv_contato_cta'             => __('Botão WhatsApp', 'wvtodoz'),
				'wv_footer_tagline'          => __('Tagline', 'wvtodoz'),
				'wv_footer_copyright'        => __('Copyright', 'wvtodoz'),
			],
			'editable'    => [
				__('CTA final e telefone / WhatsApp', 'wvtodoz'),
				__('Tagline, endereço, copyright', 'wvtodoz'),
				__('Títulos das colunas do rodapé', 'wvtodoz'),
				__('Redes sociais e textos dos modais', 'wvtodoz'),
			],
			'preview'     => [
				'line'   => 'wv_contato_title_before',
				'accent' => 'wv_contato_title_highlight',
				'lead'   => 'wv_footer_tagline',
			],
		],
		'clientes' => [
			'slug'        => 'wvtodoz-clientes',
			'title'       => __('Clientes', 'wvtodoz'),
			'menu_title'  => __('Clientes', 'wvtodoz'),
			'tab'         => 'portfolio',
			'anchor'      => '#clientes',
			'blurb'       => __('Logos da faixa de clientes (CPT). O título da faixa fica na aba Portfólio da Home.', 'wvtodoz'),
			'edit_label'  => __('Editar título da faixa', 'wvtodoz'),
			'fields'      => [
				'wv_clientes_title' => __('Título da faixa', 'wvtodoz'),
			],
			'editable'    => [
				__('Título da faixa (editor visual na aba Portfólio)', 'wvtodoz'),
				__('Logos via CPT Clientes (imagem destacada)', 'wvtodoz'),
			],
			'html_fields' => [
				'wv_clientes_title' => __('Título da faixa de clientes', 'wvtodoz'),
			],
			'cpt'         => 'wv_cliente',
			'cpt_label'   => __('Clientes', 'wvtodoz'),
			'cpt_primary' => true,
		],
	];
}

/**
 * @return array{version?:string,at?:string}|false
 */
function wv_admin_seed_status() {
	$done = get_option(WV_SEED_OPTION);
	if (!$done) {
		return false;
	}
	return is_array($done) ? $done : ['version' => (string) $done];
}

function wv_admin_count_posts(string $post_type): int {
	$counts = wp_count_posts($post_type);
	if (!$counts) {
		return 0;
	}
	return (int) ($counts->publish ?? 0);
}

/**
 * Deep link to edit Front Page with optional ACF tab focus.
 */
function wv_admin_home_edit_url(string $tab = ''): string {
	$home_id = wv_home_id();
	if ($home_id <= 0) {
		return admin_url('edit.php?post_type=page');
	}
	$url = get_edit_post_link($home_id, 'raw');
	if (!$url) {
		$url = admin_url('post.php?post=' . $home_id . '&action=edit');
	}
	if ($tab !== '') {
		$url = add_query_arg('wv_tab', sanitize_key($tab), $url);
	}
	return $url;
}

/**
 * Format a field value for summary display.
 *
 * @param mixed $value
 */
function wv_admin_format_field_value($value): string {
	if (is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1') {
		return ((int) $value) ? __('Sim', 'wvtodoz') : __('Não', 'wvtodoz');
	}
	if (is_array($value)) {
		if (isset($value['url'])) {
			return (string) $value['url'];
		}
		if (isset($value['ID'])) {
			return '#' . (string) $value['ID'];
		}
		return wp_json_encode($value) ?: '—';
	}
	$text = trim(wp_strip_all_tags((string) $value));
	if ($text === '') {
		return '—';
	}
	if (function_exists('mb_strlen') && mb_strlen($text) > 120) {
		return mb_substr($text, 0, 117) . '…';
	}
	if (strlen($text) > 120) {
		return substr($text, 0, 117) . '…';
	}
	return $text;
}
