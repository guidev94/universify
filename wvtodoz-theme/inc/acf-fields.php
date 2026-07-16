<?php
/**
 * ACF free local field groups (no Pro field types).
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

add_action('acf/init', static function (): void {
	if (!function_exists('acf_add_local_field_group')) {
		return;
	}

	$home_location = [[
		[
			'param'    => 'page_type',
			'operator' => '==',
			'value'    => 'front_page',
		],
	]];

	acf_add_local_field_group([
		'key'    => 'group_wv_home',
		'title'  => 'Home — Seções WV Todoz',
		'fields' => array_merge(
			wv_acf_tab('tab_hero', 'Hero'),
			[
				wv_acf_text('wv_hero_badge', 'Badge', 'Software house · IA'),
				wv_acf_text('wv_hero_eyebrow', 'Eyebrow', 'Agentes · Automações · Integrações'),
				wv_acf_text('wv_hero_title_line', 'Título (linha)', 'Feito pra'),
				wv_acf_text('wv_hero_title_accent', 'Título (destaque lima)', 'operar.'),
				wv_acf_textarea('wv_hero_lead', 'Lead'),
				wv_acf_text('wv_hero_cta_primary', 'CTA primário — texto', 'Falar do projeto'),
				wv_acf_url('wv_hero_cta_primary_url', 'CTA primário — URL'),
				wv_acf_text('wv_hero_cta_secondary', 'CTA secundário — texto', 'Ver o que fazemos'),
				[
					'key'           => 'field_wv_hero_video',
					'label'         => 'Vídeo de fundo (MP4)',
					'name'          => 'wv_hero_video',
					'type'          => 'file',
					'return_format' => 'array',
					'mime_types'    => 'mp4',
				],
				[
					'key'           => 'field_wv_hero_show_logos',
					'label'         => 'Mostrar logos de produtos no hero',
					'name'          => 'wv_hero_show_logos',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				],
			],
			wv_acf_tab('tab_sobre', 'Sobre'),
			[
				wv_acf_text('wv_sobre_title_line', 'Título (linha)', 'Seja Todoz'),
				wv_acf_text('wv_sobre_title_accent', 'Título (destaque)', 'com a gente'),
				wv_acf_textarea('wv_sobre_lead', 'Lead'),
				wv_acf_text('wv_sobre_stat1_n', 'Stat 1 — número', '11+'),
				wv_acf_text('wv_sobre_stat1_l', 'Stat 1 — rótulo', 'Anos de Inovação'),
				wv_acf_text('wv_sobre_stat2_n', 'Stat 2 — número', '50+'),
				wv_acf_text('wv_sobre_stat2_l', 'Stat 2 — rótulo', 'Projetos Entregues'),
				wv_acf_text('wv_sobre_cta_primary', 'CTA primário', 'Agendar consultoria'),
				wv_acf_text('wv_sobre_cta_secondary', 'CTA secundário', 'Ver o que fazemos'),
				wv_acf_image('wv_sobre_image', 'Imagem do escritório'),
			],
			wv_acf_tab('tab_missao', 'Missão'),
			[
				wv_acf_text('wv_missao_eyebrow', 'Eyebrow', 'Missão'),
				wv_acf_text('wv_missao_title', 'Título'),
				wv_acf_textarea('wv_missao_support', 'Texto de apoio'),
				wv_acf_text('wv_missao_product_a', 'Produto A', 'Facíleme'),
				wv_acf_text('wv_missao_product_b', 'Produto B', "Let's Work"),
				wv_acf_text('wv_missao_stat1_label', 'Rótulo stat 1', 'anos de mercado'),
				wv_acf_text('wv_missao_stat2_label', 'Rótulo stat 2', 'projetos entregues'),
				wv_acf_text('wv_missao_products_label', 'Rótulo produtos', 'produtos próprios'),
				wv_acf_text('wv_missao_cta', 'CTA', 'Conversar com a WV_Todoz'),
			],
			wv_acf_tab('tab_bridge', 'Mobile Bridge'),
			[
				wv_acf_text('wv_bridge_line1', 'Headline linha 1', 'O mundo'),
				wv_acf_text('wv_bridge_line2_prefix', 'Headline linha 2 (prefixo)', 'é'),
				wv_acf_text('wv_bridge_accent', 'Headline destaque (lime)', 'mobile,'),
				wv_acf_text('wv_bridge_line3', 'Headline linha 3', 'sua empresa'),
				wv_acf_text('wv_bridge_line4', 'Headline linha 4', 'deve ser.'),
				wv_acf_text('wv_bridge_eyebrow', 'Eyebrow'),
				wv_acf_textarea('wv_bridge_lead', 'Lead'),
				wv_acf_text('wv_bridge_cta', 'CTA', 'Quero conversar'),
			],
			wv_acf_tab('tab_servicos', 'Serviços'),
			[
				wv_acf_text('wv_servicos_eyebrow', 'Eyebrow', 'Três frentes de IA'),
				wv_acf_text('wv_servicos_title_line', 'Título (linha)', 'O que'),
				wv_acf_text('wv_servicos_title_accent', 'Título (destaque)', 'fazemos'),
				wv_acf_textarea('wv_servicos_intro', 'Introdução'),
				wv_acf_textarea('wv_servicos_note', 'Nota do CTA'),
				wv_acf_text('wv_servicos_quote_cta', 'Texto do CTA', 'Solicitar orçamento'),
				wv_acf_text('wv_process_1_title', 'Processo 01 — título', 'Entendimento'),
				wv_acf_textarea('wv_process_1_desc', 'Processo 01 — descrição'),
				wv_acf_text('wv_process_2_title', 'Processo 02 — título', 'Planejamento'),
				wv_acf_textarea('wv_process_2_desc', 'Processo 02 — descrição'),
				wv_acf_text('wv_process_3_title', 'Processo 03 — título', 'Construção'),
				wv_acf_textarea('wv_process_3_desc', 'Processo 03 — descrição'),
				wv_acf_text('wv_process_4_title', 'Processo 04 — título', 'Evolução'),
				wv_acf_textarea('wv_process_4_desc', 'Processo 04 — descrição'),
			],
			wv_acf_tab('tab_portfolio', 'Portfólio'),
			[
				wv_acf_text('wv_portfolio_eyebrow', 'Eyebrow', 'Projetos Selecionados'),
				wv_acf_text('wv_portfolio_title_line', 'Título (linha)', 'Cases que'),
				wv_acf_text('wv_portfolio_title_accent', 'Título (destaque)', 'marcam'),
				wv_acf_textarea('wv_portfolio_lead', 'Lead'),
				wv_acf_text('wv_portfolio_cta', 'CTA', 'Explorar Todos'),
				[
					'key'          => 'field_wv_clientes_title',
					'label'        => 'Título da faixa de clientes',
					'name'         => 'wv_clientes_title',
					'type'         => 'wysiwyg',
					'tabs'         => 'visual',
					'toolbar'      => 'basic',
					'media_upload' => 0,
					'instructions' => 'Editor visual. Use negrito/itálico; span com classe text-todoz para destaque lima.',
				],
			],
			wv_acf_tab('tab_produtos', 'Produtos'),
			[
				wv_acf_text('wv_produtos_eyebrow', 'Eyebrow', 'Ecossistema'),
				wv_acf_text('wv_produtos_title_line', 'Título (linha)', 'Produtos'),
				wv_acf_text('wv_produtos_title_accent', 'Título (destaque)', 'proprietários'),
				wv_acf_textarea('wv_produtos_lead', 'Lead'),
			],
			wv_acf_tab('tab_aplicacoes', 'Aplicações'),
			[
				wv_acf_text('wv_aplicacoes_eyebrow', 'Eyebrow'),
				wv_acf_text('wv_aplicacoes_title_line', 'Título (linha)', 'Pare de perder'),
				wv_acf_text('wv_aplicacoes_title_accent', 'Título (destaque)', 'tempo'),
				wv_acf_textarea('wv_aplicacoes_lead', 'Lead'),
				wv_acf_image('wv_aplicacoes_image', 'Imagem'),
				wv_acf_text('wv_aplicacoes_chip_kicker', 'Chip — kicker', 'Menos retrabalho'),
				wv_acf_text('wv_aplicacoes_chip_title', 'Chip — título', 'Mais resultado'),
				wv_acf_text('wv_aplicacoes_b1', 'Benefício 1'),
				wv_acf_text('wv_aplicacoes_b2', 'Benefício 2'),
				wv_acf_text('wv_aplicacoes_b3', 'Benefício 3'),
				wv_acf_text('wv_aplicacoes_cta', 'CTA', 'Quero automatizar a operação'),
			],
			wv_acf_tab('tab_blog', 'Insights'),
			[
				wv_acf_text('wv_blog_eyebrow', 'Eyebrow', 'Insights'),
				wv_acf_text('wv_blog_title_line', 'Título (linha)', 'Conteúdo &'),
				wv_acf_text('wv_blog_title_accent', 'Título (destaque)', 'inovação'),
				wv_acf_text('wv_blog_cta', 'CTA', 'Ver Blog Completo'),
				wv_acf_url('wv_blog_cta_url', 'URL do CTA (opcional)'),
			],
			wv_acf_tab('tab_contato', 'Contato / Footer'),
			[
				wv_acf_text('wv_contato_title_before', 'CTA final — antes', 'Pronto para o'),
				wv_acf_text('wv_contato_title_highlight', 'CTA final — destaque', 'Próximo Nível?'),
				wv_acf_text('wv_contato_phone', 'Telefone (exibição)', '(11) 5531-1779'),
				wv_acf_text('wv_contato_tel', 'Telefone (tel:)', '1155311779'),
				wv_acf_text('wv_contato_cta', 'Texto botão WhatsApp', 'FALE CONOSCO'),
				wv_acf_url('wv_whatsapp_url', 'URL WhatsApp'),
				wv_acf_text('wv_wa_float_label', 'Tooltip botão flutuante', 'Falar com Especialista'),
				wv_acf_textarea('wv_footer_tagline', 'Rodapé — tagline'),
				wv_acf_textarea('wv_footer_address', 'Rodapé — endereço'),
				wv_acf_url('wv_footer_maps', 'URL Google Maps'),
				wv_acf_text('wv_footer_nav_title', 'Rodapé — título coluna Navegação', 'Navegação'),
				wv_acf_text('wv_footer_products_title', 'Rodapé — título coluna Produtos', 'Produtos'),
				wv_acf_text('wv_footer_copyright', 'Copyright'),
				wv_acf_url('wv_footer_privacy', 'URL Política de Privacidade'),
				wv_acf_url('wv_footer_terms', 'URL Termos de Uso'),
				wv_acf_url('wv_social_facebook', 'Facebook'),
				wv_acf_url('wv_social_instagram', 'Instagram'),
				wv_acf_url('wv_social_linkedin', 'LinkedIn'),
				wv_acf_url('wv_social_youtube', 'YouTube'),
				wv_acf_text('wv_modal_title', 'Modal — título', 'VAMOS'),
				wv_acf_text('wv_modal_title_accent', 'Modal — destaque', 'CONVERSAR.'),
				wv_acf_text('wv_modal_submit', 'Modal contato — botão enviar', 'Enviar Proposta'),
				wv_acf_text('wv_case_modal_cta', 'Modal case — CTA', 'SOLICITAR PROJETO SIMILAR'),
				wv_acf_text('wv_modal_label_name', 'Modal — label Nome', 'Nome Completo'),
				wv_acf_text('wv_modal_label_email', 'Modal — label E-mail', 'E-mail Corporativo'),
				wv_acf_text('wv_modal_label_message', 'Modal — label Mensagem', 'Conte seu desafio'),
				wv_acf_text('wv_modal_placeholder_name', 'Modal — placeholder Nome', 'João Silva'),
				wv_acf_text('wv_modal_placeholder_email', 'Modal — placeholder E-mail', 'exemplo@empresa.com.br'),
				wv_acf_text('wv_modal_placeholder_message', 'Modal — placeholder Mensagem', 'Quero criar um agente de IA para...'),
			]
		),
		'location' => $home_location,
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'active' => true,
	]);

	// CPT: Case
	acf_add_local_field_group([
		'key' => 'group_wv_case',
		'title' => 'Case — detalhes',
		'fields' => [
			wv_acf_text('wv_case_category', 'Categoria / tag', 'AI / Mobile'),
			wv_acf_text('wv_case_meta_strong', 'Meta — título curto'),
			wv_acf_textarea('wv_case_meta_desc', 'Meta — descrição curta'),
		],
		'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'wv_case']]],
	]);

	// CPT: Produto
	acf_add_local_field_group([
		'key' => 'group_wv_produto',
		'title' => 'Produto — detalhes',
		'fields' => [
			wv_acf_text('wv_produto_subtitle', 'Subtítulo'),
			wv_acf_textarea('wv_produto_highlights', 'Destaques (um por linha)'),
			wv_acf_text('wv_produto_badge', 'Badge (ex.: Destaque)'),
			[
				'key' => 'field_wv_produto_featured',
				'label' => 'Card em destaque',
				'name' => 'wv_produto_featured',
				'type' => 'true_false',
				'ui' => 1,
			],
			[
				'key' => 'field_wv_produto_wide',
				'label' => 'Card largo',
				'name' => 'wv_produto_wide',
				'type' => 'true_false',
				'ui' => 1,
			],
			wv_acf_text('wv_produto_primary_label', 'Botão primário — texto'),
			wv_acf_url('wv_produto_primary_url', 'Botão primário — URL'),
			wv_acf_text('wv_produto_secondary_label', 'Botão secundário — texto'),
			wv_acf_url('wv_produto_secondary_url', 'Botão secundário — URL (vazio = abre modal)'),
		],
		'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'wv_produto']]],
	]);

	// CPT: Serviço
	acf_add_local_field_group([
		'key' => 'group_wv_servico',
		'title' => 'Serviço — detalhes',
		'fields' => [
			[
				'key'           => 'field_wv_servico_icon',
				'label'         => 'Ícone (Phosphor)',
				'name'          => 'wv_servico_icon',
				'type'          => 'text',
				'default_value' => 'ph-robot',
				'instructions'  => 'Use o botão “Escolher ícone” ao lado do campo — não é preciso digitar a classe.',
				'wrapper'       => ['class' => 'wv-acf-icon-field'],
			],
			[
				'key'          => 'field_wv_servico_title_html',
				'label'        => 'Título do card',
				'name'         => 'wv_servico_title_html',
				'type'         => 'wysiwyg',
				'tabs'         => 'visual',
				'toolbar'      => 'basic',
				'media_upload' => 0,
				'instructions' => 'Editor visual. Para destaque lima, selecione o texto e aplique a classe span.text-todoz (ou edite no modo texto com &lt;span class="text-todoz"&gt;).',
			],
			wv_acf_textarea('wv_servico_bullets', 'Bullets (um por linha)'),
			wv_acf_text('wv_servico_cta', 'Texto do CTA'),
			[
				'key' => 'field_wv_servico_variant',
				'label' => 'Variante de layout',
				'name' => 'wv_servico_variant',
				'type' => 'select',
				'choices' => [
					'featured' => 'Featured',
					'tall' => 'Tall',
					'mobile' => 'Mobile',
					'ia' => 'IA',
					'produtos' => 'Produtos',
				],
				'default_value' => 'featured',
			],
			wv_acf_url('wv_servico_link', 'Link (se variante Produtos)'),
		],
		'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'wv_servico']]],
	]);
});

/** @return array<int, array<string, mixed>> */
function wv_acf_tab(string $key, string $label): array {
	return [[
		'key' => 'field_' . $key,
		'label' => $label,
		'name' => '',
		'type' => 'tab',
		'placement' => 'top',
	]];
}

/** @return array<string, mixed> */
function wv_acf_text(string $name, string $label, string $default = ''): array {
	$field = [
		'key' => 'field_' . $name,
		'label' => $label,
		'name' => $name,
		'type' => 'text',
	];
	if ($default !== '') {
		$field['default_value'] = $default;
	}
	return $field;
}

/** @return array<string, mixed> */
function wv_acf_textarea(string $name, string $label): array {
	return [
		'key' => 'field_' . $name,
		'label' => $label,
		'name' => $name,
		'type' => 'textarea',
		'rows' => 3,
	];
}

/** @return array<string, mixed> */
function wv_acf_url(string $name, string $label): array {
	return [
		'key' => 'field_' . $name,
		'label' => $label,
		'name' => $name,
		'type' => 'url',
	];
}

/** @return array<string, mixed> */
function wv_acf_image(string $name, string $label): array {
	return [
		'key' => 'field_' . $name,
		'label' => $label,
		'name' => $name,
		'type' => 'image',
		'return_format' => 'array',
		'preview_size' => 'medium',
		'library' => 'all',
	];
}

/** @return array<string, mixed> */
function wv_acf_wysiwyg(string $name, string $label): array {
	return [
		'key' => 'field_' . $name,
		'label' => $label,
		'name' => $name,
		'type' => 'wysiwyg',
		'tabs' => 'visual',
		'toolbar' => 'basic',
		'media_upload' => 0,
	];
}
