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
 *
 * @param mixed $default
 * @return mixed
 */
function wv_field(string $key, $default = '') {
	if (!function_exists('get_field')) {
		return $default;
	}
	$post_id = wv_home_id();
	$value = $post_id ? get_field($key, $post_id) : get_field($key);
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
	return WVTODOZ_URI . '/assets/img/logo-wv-light.svg';
}

/** Dark-surface wordmark: green WV_ + white TODOZ/tagline. */
function wv_logo_dark_url(): string {
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

function wv_whatsapp_url(): string {
	$d = wv_defaults();
	$url = (string) wv_field('wv_whatsapp_url', $d['whatsapp']);
	return $url !== '' ? $url : (string) $d['whatsapp'];
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
 * Default nav items when no menu assigned.
 *
 * @return array<int, array{label:string,url:string}>
 */
function wv_default_nav_items(): array {
	$home = home_url('/');
	return [
		['label' => 'Home', 'url' => $home],
		['label' => 'Quem Somos', 'url' => $home . '#sobre'],
		['label' => 'Serviços', 'url' => $home . '#servicos'],
		['label' => 'Produtos', 'url' => $home . '#produtos'],
		['label' => 'Aplicações', 'url' => $home . '#aplicacoes'],
		['label' => 'Projetos', 'url' => $home . '#portfolio'],
	];
}
