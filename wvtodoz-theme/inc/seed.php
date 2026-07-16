<?php
/**
 * One-click content seed for ACF free + CPTs (idempotent).
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

const WV_SEED_OPTION = 'wv_seed_done';
const WV_SEED_VERSION = '1.5.5';

/**
 * Set ACF field or post meta with the same key.
 *
 * @param mixed $value
 */
function wv_seed_set_field(string $key, $value, int $post_id): void {
	if (function_exists('update_field')) {
		update_field($key, $value, $post_id);
		return;
	}
	update_post_meta($post_id, $key, $value);
}

/**
 * Find or create a published post by title + type.
 */
function wv_seed_upsert_post(string $post_type, string $title, array $args = []): int {
	global $wpdb;
	$existing_id = (int) $wpdb->get_var($wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_status != 'trash' LIMIT 1",
		$title,
		$post_type
	));
	if ($existing_id > 0) {
		$post_id = $existing_id;
		$update = array_merge([
			'ID'          => $post_id,
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_type'   => $post_type,
		], $args);
		wp_update_post($update);
		return $post_id;
	}

	$create = array_merge([
		'post_title'  => $title,
		'post_status' => 'publish',
		'post_type'   => $post_type,
	], $args);

	$id = wp_insert_post($create, true);
	return is_wp_error($id) ? 0 : (int) $id;
}

/**
 * Attach a local theme asset (or remote URL) as featured image.
 */
function wv_seed_set_thumbnail(int $post_id, string $source, string $filename = ''): void {
	if ($post_id <= 0 || $source === '') {
		return;
	}

	if (has_post_thumbnail($post_id)) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// Local theme file
	if (str_starts_with($source, '/') || preg_match('#^[A-Za-z]:\\\\#', $source)) {
		if (!is_readable($source)) {
			return;
		}
		$basename = $filename !== '' ? $filename : basename($source);
		$file_array = [
			'name'     => $basename,
			'tmp_name' => wp_tempnam($basename),
		];
		if (!$file_array['tmp_name']) {
			return;
		}
		copy($source, $file_array['tmp_name']);
		$attach_id = media_handle_sideload($file_array, $post_id);
		if (!is_wp_error($attach_id)) {
			set_post_thumbnail($post_id, (int) $attach_id);
		} else {
			@unlink($file_array['tmp_name']);
		}
		return;
	}

	// Remote URL
	$attach_id = media_sideload_image($source, $post_id, null, 'id');
	if (!is_wp_error($attach_id) && is_numeric($attach_id)) {
		set_post_thumbnail($post_id, (int) $attach_id);
	}
}

/**
 * Resolve theme asset absolute path (fallback to URI for remote sideload).
 *
 * @return array{path:string,uri:string}
 */
function wv_seed_asset(string $relative): array {
	$relative = ltrim($relative, '/');
	$path = WVTODOZ_DIR . '/assets/' . $relative;
	$uri = WVTODOZ_URI . '/assets/' . $relative;
	return [
		'path' => is_readable($path) ? $path : '',
		'uri'  => $uri,
	];
}

/**
 * Run full seed. Returns true on success.
 */
function wv_run_seed(bool $force = false): bool {
	if (!$force && get_option(WV_SEED_OPTION)) {
		return true;
	}

	$d = wv_defaults();

	// --- Front Page: Home ---
	$home_id = wv_seed_upsert_post('page', 'Home', [
		'post_content' => '',
		'post_name'    => 'home',
	]);
	if ($home_id <= 0) {
		return false;
	}

	update_option('show_on_front', 'page');
	update_option('page_on_front', $home_id);

	// Blog index (page_for_posts) so /insights/ lists posts with home.php
	$blog_id = wv_seed_upsert_post('page', 'Insights', [
		'post_content' => '',
		'post_name'    => 'insights',
	]);
	if ($blog_id > 0) {
		update_option('page_for_posts', $blog_id);
	}

	// Contato + remaining internal pages (templates + ACF)
	$contato_id = wv_seed_upsert_post('page', 'Contato', [
		'post_content' => '',
		'post_name'    => 'contato',
	]);
	if ($contato_id > 0 && function_exists('wv_seed_apply_internal_page')) {
		wv_seed_apply_internal_page($contato_id, 'contato');
	}

	$internal_pages = wv_seed_internal_pages();
	$privacy_id = (int) ($internal_pages['politica-de-privacidade'] ?? 0);
	$terms_id = (int) ($internal_pages['termos-de-uso'] ?? 0);

	$hero = $d['hero'];
	$sobre = $d['sobre'];
	$missao = $d['missao'];
	$bridge = $d['mobile_bridge'];
	$servicos = $d['servicos'];
	$process = $d['process'];
	$portfolio = $d['portfolio'];
	$produtos = $d['produtos'];
	$aplicacoes = $d['aplicacoes'];
	$blog = $d['blog'];
	$contato = $d['contato'];
	$footer = $d['footer'];
	$modal = $d['modal'];

	$fields = [
		'wv_hero_badge'            => $hero['badge'],
		'wv_hero_eyebrow'          => $hero['eyebrow'],
		'wv_hero_title_line'       => $hero['title_line'],
		'wv_hero_title_accent'     => $hero['title_accent'],
		'wv_hero_lead'             => $hero['lead'],
		'wv_hero_cta_primary'      => $hero['cta_primary'],
		'wv_hero_cta_primary_url'  => $d['whatsapp'],
		'wv_hero_cta_secondary'    => $hero['cta_secondary'],
		'wv_hero_show_logos'       => 1,
		'wv_sobre_title_line'      => $sobre['title_line'],
		'wv_sobre_title_accent'    => $sobre['title_accent'],
		'wv_sobre_lead'            => $sobre['lead'],
		'wv_sobre_stat1_n'         => $sobre['stat1_n'],
		'wv_sobre_stat1_l'         => $sobre['stat1_l'],
		'wv_sobre_stat2_n'         => $sobre['stat2_n'],
		'wv_sobre_stat2_l'         => $sobre['stat2_l'],
		'wv_sobre_cta_primary'     => $sobre['cta_primary'],
		'wv_sobre_cta_secondary'   => $sobre['cta_secondary'],
		'wv_missao_eyebrow'        => $missao['eyebrow'],
		'wv_missao_title'          => $missao['title'],
		'wv_missao_support'        => $missao['support'],
		'wv_missao_product_a'      => $missao['product_a'],
		'wv_missao_product_b'      => $missao['product_b'],
		'wv_missao_stat1_label'    => 'anos de mercado',
		'wv_missao_stat2_label'    => 'projetos entregues',
		'wv_missao_products_label' => 'produtos próprios',
		'wv_missao_cta'            => $missao['cta'],
		'wv_bridge_line1'          => $bridge['line1'],
		'wv_bridge_line2_prefix'   => $bridge['line2_prefix'],
		'wv_bridge_accent'         => $bridge['accent'],
		'wv_bridge_line3'          => $bridge['line3'],
		'wv_bridge_line4'          => $bridge['line4'],
		'wv_bridge_eyebrow'        => $bridge['eyebrow'],
		'wv_bridge_lead'           => $bridge['lead'],
		'wv_bridge_cta'            => $bridge['cta'],
		'wv_servicos_eyebrow'      => $servicos['eyebrow'],
		'wv_servicos_title_line'   => $servicos['title_line'],
		'wv_servicos_title_accent' => $servicos['title_accent'],
		'wv_servicos_intro'        => $servicos['intro'],
		'wv_servicos_note'         => $servicos['note'],
		'wv_servicos_quote_cta'    => $servicos['quote_cta'],
		'wv_portfolio_eyebrow'     => $portfolio['eyebrow'],
		'wv_portfolio_title_line'  => $portfolio['title_line'],
		'wv_portfolio_title_accent'=> $portfolio['title_accent'],
		'wv_portfolio_lead'        => $portfolio['lead'],
		'wv_portfolio_cta'         => $portfolio['cta'],
		'wv_clientes_title'        => $d['clientes_title_html'],
		'wv_produtos_eyebrow'      => $produtos['eyebrow'],
		'wv_produtos_title_line'   => $produtos['title_line'],
		'wv_produtos_title_accent' => $produtos['title_accent'],
		'wv_produtos_lead'         => $produtos['lead'],
		'wv_aplicacoes_eyebrow'    => $aplicacoes['eyebrow'],
		'wv_aplicacoes_title_line' => $aplicacoes['title_line'],
		'wv_aplicacoes_title_accent' => $aplicacoes['title_accent'],
		'wv_aplicacoes_lead'       => $aplicacoes['lead'],
		'wv_aplicacoes_chip_kicker'=> $aplicacoes['chip_kicker'],
		'wv_aplicacoes_chip_title' => $aplicacoes['chip_title'],
		'wv_aplicacoes_b1'         => $aplicacoes['b1'],
		'wv_aplicacoes_b2'         => $aplicacoes['b2'],
		'wv_aplicacoes_b3'         => $aplicacoes['b3'],
		'wv_aplicacoes_cta'        => $aplicacoes['cta'],
		'wv_blog_eyebrow'          => $blog['eyebrow'],
		'wv_blog_title_line'       => $blog['title_line'],
		'wv_blog_title_accent'     => $blog['title_accent'],
		'wv_blog_cta'              => $blog['cta'],
		'wv_contato_title_before'  => $contato['title_before'],
		'wv_contato_title_highlight' => $contato['title_highlight'],
		'wv_contato_phone'         => $contato['phone'],
		'wv_contato_tel'           => $contato['tel'],
		'wv_contato_cta'           => $contato['cta'],
		'wv_whatsapp_url'          => $d['whatsapp'],
		'wv_wa_float_label'        => 'Falar com Especialista',
		'wv_footer_tagline'        => $footer['tagline'],
		'wv_footer_address'        => $footer['address'],
		'wv_footer_maps'           => $footer['maps'],
		'wv_footer_nav_title'      => 'Navegação',
		'wv_footer_products_title' => 'Produtos',
		'wv_footer_copyright'      => $footer['copyright'],
		'wv_footer_privacy'        => $privacy_id > 0 ? (string) get_permalink($privacy_id) : (string) $footer['privacy'],
		'wv_footer_terms'          => $terms_id > 0 ? (string) get_permalink($terms_id) : (string) $footer['terms'],
		'wv_social_facebook'       => $footer['facebook'],
		'wv_social_instagram'      => $footer['instagram'],
		'wv_social_linkedin'       => $footer['linkedin'],
		'wv_social_youtube'        => $footer['youtube'],
		'wv_modal_title'           => $modal['title'],
		'wv_modal_title_accent'    => $modal['title_accent'],
		'wv_modal_submit'          => 'Enviar Proposta',
		'wv_case_modal_cta'        => 'SOLICITAR PROJETO SIMILAR',
		'wv_modal_label_name'      => 'Nome Completo',
		'wv_modal_label_email'     => 'E-mail Corporativo',
		'wv_modal_label_message'   => 'Conte seu desafio',
		'wv_modal_placeholder_name'=> 'João Silva',
		'wv_modal_placeholder_email' => 'exemplo@empresa.com.br',
		'wv_modal_placeholder_message' => 'Quero criar um agente de IA para...',
	];

	foreach ($process as $i => $step) {
		$n = $i + 1;
		$fields["wv_process_{$n}_title"] = $step['title'];
		$fields["wv_process_{$n}_desc"] = $step['desc'];
	}

	foreach ($fields as $key => $value) {
		wv_seed_set_field($key, $value, $home_id);
	}

	// Images on Home (ACF image fields — store attachment ID when possible)
	$office = wv_seed_asset('img/office.png');
	$src = $office['path'] !== '' ? $office['path'] : $office['uri'];
	wv_seed_attach_acf_image($home_id, 'wv_sobre_image', $src, 'office.png');

	$app_img = wv_seed_asset('img/aplicacoes-ia.jpg');
	$src = $app_img['path'] !== '' ? $app_img['path'] : $app_img['uri'];
	wv_seed_attach_acf_image($home_id, 'wv_aplicacoes_image', $src, 'aplicacoes-ia.jpg');

	// --- Cases ---
	foreach ($d['cases'] as $i => $case) {
		$id = wv_seed_upsert_post('wv_case', $case['title'], [
			'post_content' => $case['desc'],
			'menu_order'   => $i + 1,
		]);
		if ($id <= 0) {
			continue;
		}
		wv_seed_set_field('wv_case_category', $case['category'], $id);
		wv_seed_set_field('wv_case_meta_strong', $case['meta_strong'], $id);
		wv_seed_set_field('wv_case_meta_desc', $case['meta_p'], $id);
		wv_seed_set_thumbnail($id, $case['img']);
	}

	// --- Produtos ---
	foreach ($d['produtos_cards'] as $i => $card) {
		$id = wv_seed_upsert_post('wv_produto', $card['title'], [
			'post_content' => $card['desc'],
			'menu_order'   => $i + 1,
		]);
		if ($id <= 0) {
			continue;
		}
		wv_seed_set_field('wv_produto_subtitle', $card['subtitle'] ?? '', $id);
		wv_seed_set_field('wv_produto_highlights', $card['highlights'] ?? '', $id);
		wv_seed_set_field('wv_produto_badge', $card['badge'] ?? '', $id);
		wv_seed_set_field('wv_produto_featured', !empty($card['featured']) ? 1 : 0, $id);
		wv_seed_set_field('wv_produto_wide', !empty($card['wide']) ? 1 : 0, $id);
		wv_seed_set_field('wv_produto_primary_label', $card['primary_label'] ?? '', $id);
		wv_seed_set_field('wv_produto_primary_url', $card['primary_url'] ?? '', $id);
		wv_seed_set_field('wv_produto_secondary_label', $card['secondary_label'] ?? '', $id);
		wv_seed_set_field('wv_produto_secondary_url', $card['secondary_url'] ?? '', $id);

		if (!empty($card['logo'])) {
			$path = str_replace(WVTODOZ_URI . '/assets/', WVTODOZ_DIR . '/assets/', $card['logo']);
			$source = is_readable($path) ? $path : $card['logo'];
			wv_seed_set_thumbnail($id, $source, basename((string) parse_url($card['logo'], PHP_URL_PATH)));
		}
	}

	// --- Clientes ---
	foreach ($d['clientes'] as $i => $cliente) {
		$id = wv_seed_upsert_post('wv_cliente', $cliente['name'], [
			'menu_order' => $i + 1,
		]);
		if ($id <= 0) {
			continue;
		}
		$url = $cliente['url'];
		$path = str_replace(WVTODOZ_URI . '/assets/', WVTODOZ_DIR . '/assets/', $url);
		$source = is_readable($path) ? $path : $url;
		wv_seed_set_thumbnail($id, $source, basename((string) parse_url($url, PHP_URL_PATH)));
	}

	// --- Serviços ---
	foreach ($d['servicos_cards'] as $i => $card) {
		$plain = wp_strip_all_tags(str_replace(['<span class="text-todoz">', '</span>'], '', $card['title']));
		$id = wv_seed_upsert_post('wv_servico', $plain, [
			'post_content' => $card['desc'],
			'menu_order'   => $i + 1,
		]);
		if ($id <= 0) {
			continue;
		}
		wv_seed_set_field('wv_servico_icon', $card['icon'], $id);
		wv_seed_set_field('wv_servico_title_html', $card['title'], $id);
		wv_seed_set_field('wv_servico_bullets', $card['bullets'], $id);
		wv_seed_set_field('wv_servico_cta', $card['cta'], $id);
		wv_seed_set_field('wv_servico_variant', $card['variant'], $id);
		wv_seed_set_field('wv_servico_link', $card['link'] ?? '', $id);
	}

	// --- Insight posts ---
	$insights = [
		[
			'title'   => 'O futuro da programação.',
			'excerpt' => 'Tendências que ditam o software de amanhã e como se preparar.',
			'tag'     => 'Tecnologia',
			'img'     => 'img/blog/insight-tech.jpg',
			'date'    => '2026-04-12 10:00:00',
		],
		[
			'title'   => 'Segmentação de Leads.',
			'excerpt' => 'Trabalhando sua base de contatos com inteligência e conversão.',
			'tag'     => 'Inteligência',
			'img'     => 'img/blog/insight-data.jpg',
			'date'    => '2026-03-08 10:00:00',
		],
		[
			'title'   => 'Revolução AI-First.',
			'excerpt' => 'IA no centro dos produtos digitais modernos e competitivos.',
			'tag'     => 'Inovação',
			'img'     => 'img/blog/insight-ai.jpg',
			'date'    => '2026-02-21 10:00:00',
		],
	];

	foreach ($insights as $insight) {
		$id = wv_seed_upsert_post('post', $insight['title'], [
			'post_excerpt' => $insight['excerpt'],
			'post_content' => '<p>' . esc_html($insight['excerpt']) . '</p>',
			'post_date'    => $insight['date'],
		]);
		if ($id <= 0) {
			continue;
		}
		$term = term_exists($insight['tag'], 'category');
		if (!$term) {
			$term = wp_insert_term($insight['tag'], 'category');
		}
		if (!is_wp_error($term)) {
			$term_id = is_array($term) ? (int) $term['term_id'] : (int) $term;
			wp_set_post_terms($id, [$term_id], 'category', false);
		}
		$asset = wv_seed_asset($insight['img']);
		$source = $asset['path'] !== '' ? $asset['path'] : $asset['uri'];
		wv_seed_set_thumbnail($id, $source, basename($insight['img']));
	}

	update_option(WV_SEED_OPTION, [
		'version' => WV_SEED_VERSION,
		'time'    => time(),
		'home_id' => $home_id,
	]);

	return true;
}

/**
 * Sideload image and store on ACF image field.
 */
function wv_seed_attach_acf_image(int $post_id, string $field, string $source, string $filename = ''): void {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$existing = function_exists('get_field') ? get_field($field, $post_id) : get_post_meta($post_id, $field, true);
	if (!empty($existing)) {
		return;
	}

	$attach_id = 0;
	if (is_readable($source) && !preg_match('#^https?://#i', $source)) {
		$basename = $filename !== '' ? $filename : basename($source);
		$file_array = [
			'name'     => $basename,
			'tmp_name' => wp_tempnam($basename),
		];
		if ($file_array['tmp_name']) {
			copy($source, $file_array['tmp_name']);
			$result = media_handle_sideload($file_array, $post_id);
			if (!is_wp_error($result)) {
				$attach_id = (int) $result;
			} else {
				@unlink($file_array['tmp_name']);
			}
		}
	} else {
		$result = media_sideload_image($source, $post_id, null, 'id');
		if (!is_wp_error($result) && is_numeric($result)) {
			$attach_id = (int) $result;
		}
	}

	if ($attach_id > 0) {
		wv_seed_set_field($field, $attach_id, $post_id);
	}
}

/**
 * Admin notice when seed not done (or after theme switch prompt).
 */
add_action('admin_notices', static function (): void {
	if (!current_user_can('manage_options')) {
		return;
	}
	$done = get_option(WV_SEED_OPTION);
	$prompt = (bool) get_transient('wv_seed_prompt');
	if ($done && !$prompt) {
		return;
	}
	if ($done) {
		delete_transient('wv_seed_prompt');
		return;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	$allowed = ['dashboard', 'themes', 'toplevel_page_wvtodoz', 'wvtodoz_page_wvtodoz-import', 'appearance_page_wv-seed'];
	if ($screen && !in_array($screen->id, $allowed, true) && $screen->base !== 'dashboard') {
		return;
	}

	$url = wp_nonce_url(admin_url('admin-post.php?action=wv_import_content'), 'wv_import_content');
	echo '<div class="notice notice-info"><p>';
	echo '<strong>' . esc_html__('WV Todoz:', 'wvtodoz') . '</strong> ';
	echo esc_html__('Importe o conteúdo da landing (Home, Contato, Insights, Cases, Produtos, Clientes e Serviços) com um clique.', 'wvtodoz');
	echo ' <a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Importar conteúdo WV', 'wvtodoz') . '</a>';
	echo ' <a href="' . esc_url(admin_url('admin.php?page=wvtodoz-import')) . '">' . esc_html__('WV Todoz → Importar', 'wvtodoz') . '</a>';
	echo '</p></div>';
});

add_action('admin_post_wv_import_content', static function (): void {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('Sem permissão.', 'wvtodoz'));
	}
	check_admin_referer('wv_import_content');
	$force = isset($_GET['force']) && $_GET['force'] === '1';
	$ok = wv_run_seed($force);
	delete_transient('wv_seed_prompt');
	$redirect = add_query_arg(
		['page' => 'wvtodoz-import', 'wv_seed' => $ok ? 'ok' : 'fail'],
		admin_url('admin.php')
	);
	wp_safe_redirect($redirect);
	exit;
});

/**
 * Seed internal content pages present on production (hubs + legal).
 *
 * @return array<string, int> slug => post ID
 */
function wv_seed_internal_pages(): array {
	$pages = [
		'politica-de-privacidade' => [
			'title'   => 'Política de Privacidade',
			'content' => <<<'HTML'
<p>Esta Política de Privacidade descreve como a WV Todoz coleta, usa e protege informações pessoais enviadas por meio do site, formulários de contato e canais digitais.</p>
<h2>1. Dados que coletamos</h2>
<p>Podemos receber nome, e-mail, telefone, mensagem e metadados de origem (página, seção e parâmetros UTM) quando você solicita contato ou orçamento.</p>
<h2>2. Finalidade</h2>
<p>Utilizamos os dados exclusivamente para responder solicitações, elaborar propostas comerciais, melhorar nossos serviços e cumprir obrigações legais.</p>
<h2>3. Compartilhamento</h2>
<p>Não vendemos dados pessoais. Podemos compartilhar informações com prestadores essenciais (hospedagem, e-mail, CRM) sob contrato de confidencialidade, ou quando exigido por lei.</p>
<h2>4. Retenção e segurança</h2>
<p>Mantemos os dados pelo tempo necessário ao atendimento e às obrigações legais, com medidas técnicas e organizacionais adequadas.</p>
<h2>5. Seus direitos (LGPD)</h2>
<p>Você pode solicitar acesso, correção, anonimização ou exclusão dos seus dados. Contato: <a href="mailto:contato@wvtodoz.com.br">contato@wvtodoz.com.br</a>.</p>
<p><em>Texto placeholder — revise com assessoria jurídica antes de publicar em produção.</em></p>
HTML,
		],
		'termos-de-uso' => [
			'title'   => 'Termos de Uso',
			'content' => <<<'HTML'
<p>Ao utilizar este site e os canais da WV Todoz, você concorda com estes Termos de Uso.</p>
<h2>1. Uso do site</h2>
<p>O conteúdo é informado para apresentação da empresa, produtos e serviços. É proibido uso abusivo, scraping agressivo ou tentativa de comprometer a segurança da plataforma.</p>
<h2>2. Propriedade intelectual</h2>
<p>Marcas, textos, layouts e materiais pertencem à WV Todoz ou a licenciantes. Reprodução sem autorização prévia é vedada.</p>
<h2>3. Propostas e serviços</h2>
<p>Orçamentos e escopos são definidos em proposta comercial específica. Estes termos não substituem contratos de prestação de serviço.</p>
<h2>4. Limitação</h2>
<p>Empregamos esforços para manter informações atualizadas, sem garantia de disponibilidade ininterrupta do site.</p>
<h2>5. Contato</h2>
<p>Dúvidas: <a href="mailto:contato@wvtodoz.com.br">contato@wvtodoz.com.br</a> ou página <a href="/contato/">Contato</a>.</p>
<p><em>Texto placeholder — revise com assessoria jurídica antes de publicar em produção.</em></p>
HTML,
		],
		'servicos' => [
			'title'   => 'Serviços',
			'content' => <<<'HTML'
<p>A WV Todoz desenvolve software, agentes de IA, automações e integrações que reduzem fricção operacional e geram resultado de negócio.</p>
<h2>O que entregamos</h2>
<ul>
<li>Aplicações web e mobile sob medida</li>
<li>Agentes e automações com IA</li>
<li>Integrações entre sistemas, APIs e CRMs</li>
<li>Produtos próprios (Let's Work, Facíleme e ecossistema)</li>
</ul>
<p>Quer detalhar um desafio? Vá para a <a href="/#servicos">seção Serviços na Home</a> ou fale conosco em <a href="/contato/">Contato</a>.</p>
HTML,
		],
		'localizacao' => [
			'title'   => 'Localização',
			'content' => <<<'HTML'
<p>Estamos no Brooklin, em São Paulo — SP.</p>
<p><strong>Endereço</strong><br>Av. Morumbi, 8435 cj.104<br>Brooklin, São Paulo - SP<br>Brasil</p>
<p><strong>Telefone</strong><br><a href="tel:1155311779">(11) 5531-1779</a></p>
<p><a href="https://www.google.com/maps/search/?api=1&amp;query=Av.+Morumbi,+8435+Brooklin+S%C3%A3o+Paulo+SP" target="_blank" rel="noopener noreferrer">Ver no Google Maps</a></p>
HTML,
		],
		'equipe' => [
			'title'   => 'Equipe',
			'content' => <<<'HTML'
<p>Somos uma software house com time multidisciplinar em produto, engenharia, dados e design — focados em entregar sistemas que a operação realmente usa.</p>
<p>Conheça nossa história e números na seção <a href="/#sobre">Quem Somos</a> da Home, ou fale com a gente em <a href="/contato/">Contato</a>.</p>
HTML,
		],
		'vagas' => [
			'title'   => 'Vagas',
			'content' => <<<'HTML'
<p>Quer fazer parte da WV Todoz? Buscamos pessoas curiosas, práticas e apaixonadas por produto digital.</p>
<p>No momento, publique aqui as vagas abertas ou envie currículo e portfólio para <a href="mailto:contato@wvtodoz.com.br">contato@wvtodoz.com.br</a> com o assunto “Vagas”.</p>
<p><em>Conteúdo editável — atualize as posições abertas pelo painel WordPress.</em></p>
HTML,
		],
		'clientes' => [
			'title'   => 'Clientes',
			'content' => <<<'HTML'
<p>Trabalhamos com empresas que precisam de software confiável, integrações sólidas e produtos digitais com impacto real.</p>
<p>Veja a faixa de clientes e cases na Home: <a href="/#clientes">Clientes</a> e <a href="/#portfolio">Portfólio</a>.</p>
HTML,
		],
		'marketing-digital' => [
			'title'   => 'Marketing Digital',
			'content' => <<<'HTML'
<p>Além de engenharia, apoiamos estratégias digitais que convertem: landing pages, funis, conteúdo e integração com CRM e automação.</p>
<p>Para um projeto completo de produto + aquisição, fale conosco em <a href="/contato/">Contato</a>.</p>
HTML,
		],
	];

	$ids = [];
	foreach ($pages as $slug => $page) {
		$id = wv_seed_upsert_post('page', $page['title'], [
			'post_content' => $page['content'],
			'post_name'    => $slug,
			'post_status'  => 'publish',
		]);
		if ($id > 0) {
			$ids[$slug] = $id;
			if (function_exists('wv_seed_apply_internal_page')) {
				wv_seed_apply_internal_page($id, $slug, (string) $page['content']);
			}
		}
	}

	// Retire sample/default WP pages that clutter the site
	foreach (['sample-page', 'privacy-policy'] as $junk_slug) {
		$junk = get_page_by_path($junk_slug);
		if ($junk instanceof WP_Post) {
			wp_delete_post((int) $junk->ID, true);
		}
	}

	return $ids;
}

/**
 * Prompt after theme switch if not seeded.
 */
add_action('after_switch_theme', static function (): void {
	if (!get_option(WV_SEED_OPTION)) {
		set_transient('wv_seed_prompt', 1, WEEK_IN_SECONDS);
	}
});
