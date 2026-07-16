<?php
/**
 * Theme helpers — ACF-safe getters and shared URLs.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

function wv_home_id(): int {
	$page_id = (int) get_option('page_on_front');
	return $page_id > 0 ? $page_id : 0;
}

/**
 * Get ACF field from Front Page with fallback.
 * Pass $post_id to read from another post (e.g. CPT).
 *
 * @param mixed $default
 * @return mixed
 */
function wv_field(string $key, $default = '', ?int $post_id = null) {
	if (!function_exists('get_field')) {
		if ($post_id) {
			$meta = get_post_meta($post_id, $key, true);
			return ($meta === '' || $meta === false || $meta === null) ? $default : $meta;
		}
		return $default;
	}
	$target = $post_id ?? wv_home_id();
	$value = $target ? get_field($key, $target) : get_field($key);
	if ($value === null || $value === false || $value === '') {
		return $default;
	}
	return $value;
}

function wv_logo_url(): string {
	$custom = get_theme_mod('custom_logo');
	if ($custom) {
		$url = wp_get_attachment_image_url((int) $custom, 'full');
		if ($url) {
			return $url;
		}
	}
	$d = wv_defaults();
	return (string) $d['logo'];
}

/** Light-surface wordmark: green WV_ + black TODOZ/tagline. */
function wv_logo_light_url(): string {
	$custom = (string) (function_exists('wv_opt') ? wv_opt('logo_light', '') : '');
	if ($custom !== '') {
		return $custom;
	}
	return WVTODOZ_URI . '/assets/img/logo-wv-light.svg';
}

/** Dark-surface wordmark: green WV_ + white TODOZ/tagline. */
function wv_logo_dark_url(): string {
	$custom = (string) (function_exists('wv_opt') ? wv_opt('logo_dark', '') : '');
	if ($custom !== '') {
		return $custom;
	}
	return WVTODOZ_URI . '/assets/img/logo-wv-dark.svg';
}

/**
 * Dual theme logos (CSS toggles .site-logo--light / --dark).
 *
 * @param array{class?:string,alt?:string,on_dark?:bool,width?:string} $args
 */
function wv_logo_markup(array $args = []): string {
	$class   = $args['class'] ?? 'block w-40 md:w-56 overflow-hidden';
	$alt     = $args['alt'] ?? get_bloginfo('name');
	$on_dark = !empty($args['on_dark']);
	$w       = $args['width'] ?? '1024';
	$h       = $args['height'] ?? '394';
	$light   = esc_url(wv_logo_light_url());
	$dark    = esc_url(wv_logo_dark_url());
	$alt_esc = esc_attr((string) $alt);
	$cls     = esc_attr('site-logo-link ' . $class . ($on_dark ? ' site-logo-link--on-dark' : ''));

	if ($on_dark) {
		return sprintf(
			'<span class="%s"><img src="%s" alt="%s" class="site-logo site-logo--dark w-full h-auto" width="%s" height="%s" decoding="async"></span>',
			$cls,
			$dark,
			$alt_esc,
			esc_attr($w),
			esc_attr($h)
		);
	}

	return sprintf(
		'<span class="%s"><img src="%s" alt="%s" class="site-logo site-logo--light w-full h-auto" width="%s" height="%s" decoding="async"><img src="%s" alt="" class="site-logo site-logo--dark w-full h-auto" width="%s" height="%s" decoding="async" aria-hidden="true"></span>',
		$cls,
		$light,
		$alt_esc,
		esc_attr($w),
		esc_attr($h),
		$dark,
		esc_attr($w),
		esc_attr($h)
	);
}

function wv_whatsapp_digits(): string {
	$from_opt = function_exists('wv_opt') ? (string) wv_opt('whatsapp', '') : '';
	if ($from_opt !== '') {
		return preg_replace('/\D+/', '', $from_opt) ?: $from_opt;
	}
	$d = wv_defaults();
	$url = (string) wv_field('wv_whatsapp_url', $d['whatsapp']);
	$digits = preg_replace('/\D+/', '', $url) ?? '';
	return $digits !== '' ? $digits : '551155311779';
}

function wv_whatsapp_url(string $text = ''): string {
	$d = wv_defaults();
	$acf_url = (string) wv_field('wv_whatsapp_url', '');
	$base = $acf_url !== '' ? $acf_url : ('https://wa.me/' . wv_whatsapp_digits());
	// Prefer theme option digits when ACF empty.
	if ($acf_url === '' && function_exists('wv_opt')) {
		$base = 'https://wa.me/' . wv_whatsapp_digits();
	}
	if ($text === '') {
		return $base !== '' ? $base : (string) $d['whatsapp'];
	}
	$sep = str_contains($base, '?') ? '&' : '?';
	return $base . $sep . 'text=' . rawurlencode($text);
}

function wv_company_email(): string {
	return (string) (function_exists('wv_opt') ? wv_opt('email', 'contato@wvtodoz.com.br') : 'contato@wvtodoz.com.br');
}

function wv_company_phone(): string {
	return (string) (function_exists('wv_opt') ? wv_opt('phone', '') : '');
}

/**
 * Social URL from theme options, then ACF field, then default.
 */
function wv_social_url(string $network, string $acf_key, string $default = ''): string {
	$map = [
		'instagram' => 'social_instagram',
		'linkedin'  => 'social_linkedin',
		'facebook'  => 'social_facebook',
		'youtube'   => 'social_youtube',
	];
	$opt_key = $map[$network] ?? '';
	if ($opt_key !== '' && function_exists('wv_opt')) {
		$from_opt = (string) wv_opt($opt_key, '');
		if ($from_opt !== '') {
			return $from_opt;
		}
	}
	$from_acf = (string) wv_field($acf_key, $default);
	return $from_acf !== '' ? $from_acf : $default;
}

/**
 * Resolve ACF image (array|id|url) to URL string.
 *
 * @param mixed $image
 */
function wv_image_url($image, string $fallback = ''): string {
	if (is_array($image) && !empty($image['url'])) {
		return (string) $image['url'];
	}
	if (is_numeric($image)) {
		$url = wp_get_attachment_image_url((int) $image, 'full');
		return $url ? $url : $fallback;
	}
	if (is_string($image) && $image !== '') {
		return $image;
	}
	return $fallback;
}

/**
 * Resolve ACF file (array|id|url) to URL.
 *
 * @param mixed $file
 */
function wv_file_url($file, string $fallback = ''): string {
	if (is_array($file) && !empty($file['url'])) {
		return (string) $file['url'];
	}
	if (is_numeric($file)) {
		$url = wp_get_attachment_url((int) $file);
		return $url ? $url : $fallback;
	}
	if (is_string($file) && $file !== '') {
		return $file;
	}
	return $fallback;
}

/**
 * Query CPT posts ordered by menu_order.
 *
 * @return WP_Post[]
 */
function wv_query_posts(string $post_type, int $limit = -1): array {
	$q = new WP_Query([
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => [
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		],
		'no_found_rows'  => true,
	]);
	return $q->posts;
}

/**
 * Allowed HTML for accent titles in services.
 */
function wv_kses_title(string $html): string {
	return wp_kses($html, [
		'span' => ['class' => true],
		'br'   => [],
		'em'   => [],
		'strong' => [],
	]);
}

/**
 * Whether a URL points outside this WordPress site.
 */
function wv_is_external_url(string $url): bool {
	if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
		return false;
	}
	$home_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
	$url_host = (string) wp_parse_url($url, PHP_URL_HOST);
	if ($url_host === '') {
		return false;
	}
	return strtolower($url_host) !== strtolower($home_host);
}

/**
 * Permalink helper by page slug with fallback path.
 */
function wv_page_url(string $slug, string $fallback_path = ''): string {
	$page = get_page_by_path($slug);
	if ($page instanceof WP_Post) {
		$url = get_permalink($page);
		if (is_string($url) && $url !== '') {
			return $url;
		}
	}
	$path = $fallback_path !== '' ? $fallback_path : '/' . trim($slug, '/') . '/';
	return home_url($path);
}

/**
 * Permalink for Contato page (seeded slug `contato`).
 */
function wv_contato_url(): string {
	return wv_page_url('contato', '/contato/');
}

/**
 * Privacy / Terms URLs — prefer seeded pages, then ACF, then fallback path.
 */
function wv_privacy_url(): string {
	$from_field = (string) wv_field('wv_footer_privacy', '');
	if ($from_field !== '' && !wv_is_external_url($from_field)) {
		return $from_field;
	}
	$page = wv_page_url('politica-de-privacidade', '/politica-de-privacidade/');
	if ($page !== '') {
		return $page;
	}
	return $from_field !== '' ? $from_field : home_url('/politica-de-privacidade/');
}

function wv_terms_url(): string {
	$from_field = (string) wv_field('wv_footer_terms', '');
	if ($from_field !== '' && !wv_is_external_url($from_field)) {
		return $from_field;
	}
	$page = wv_page_url('termos-de-uso', '/termos-de-uso/');
	if ($page !== '') {
		return $page;
	}
	return $from_field !== '' ? $from_field : home_url('/termos-de-uso/');
}

/**
 * Permalink for Insights / posts index.
 */
function wv_insights_url(): string {
	$posts_page = (int) get_option('page_for_posts');
	if ($posts_page > 0) {
		$url = get_permalink($posts_page);
		if (is_string($url) && $url !== '') {
			return $url;
		}
	}
	$page = get_page_by_path('insights');
	if ($page instanceof WP_Post) {
		$url = get_permalink($page);
		if (is_string($url) && $url !== '') {
			return $url;
		}
	}
	return home_url('/#blog');
}

/**
 * Default nav items when no menu assigned.
 *
 * @return array<int, array{label:string,url:string}>
 */
function wv_default_nav_items(): array {
	$home = home_url('/');
	return [
		['label' => 'Home', 'url' => $home],
		['label' => 'Quem Somos', 'url' => $home . '#sobre'],
		['label' => 'Serviços', 'url' => wv_page_url('servicos', '/servicos/')],
		['label' => 'Produtos', 'url' => $home . '#produtos'],
		['label' => 'Aplicações', 'url' => $home . '#aplicacoes'],
		['label' => 'Projetos', 'url' => $home . '#portfolio'],
		['label' => 'Contato', 'url' => wv_contato_url()],
	];
}
