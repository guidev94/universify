<?php
/**
 * Asset enqueue — mirrors static index.html stack.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

add_action('wp_enqueue_scripts', static function (): void {
	$ver = WVTODOZ_VERSION;

	wp_enqueue_style(
		'wvtodoz-fonts',
		'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400;1,9..40,500&family=Instrument+Sans:ital,wght@0,500;0,600;0,700;0,800;1,500;1,600;1,700&display=swap',
		[],
		null
	);

	wp_enqueue_style('wvtodoz-aos', 'https://unpkg.com/aos@2.3.1/dist/aos.css', [], '2.3.1');
	wp_enqueue_style('wvtodoz-main', WVTODOZ_URI . '/assets/css/main.css', ['wvtodoz-fonts', 'wvtodoz-aos'], $ver);
	wp_enqueue_style('wvtodoz-style', get_stylesheet_uri(), ['wvtodoz-main'], $ver);

	wp_enqueue_script('wvtodoz-tailwind', 'https://cdn.tailwindcss.com', [], null, false);
	wp_add_inline_script(
		'wvtodoz-tailwind',
		'tailwind.config={theme:{extend:{colors:{primary:"#0a0a0a",todoz:"#CFFF04",todozDark:"#A8D600"},fontFamily:{sans:[\'"DM Sans"\',\'system-ui\',\'sans-serif\'],display:[\'"Instrument Sans"\',\'system-ui\',\'sans-serif\']}}}};',
		'after'
	);

	wp_enqueue_script('wvtodoz-phosphor', 'https://unpkg.com/@phosphor-icons/web', [], null, false);
	wp_enqueue_script('wvtodoz-gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', [], '3.12.5', false);
	wp_enqueue_script('wvtodoz-scrolltrigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js', ['wvtodoz-gsap'], '3.12.5', false);
	wp_enqueue_script('wvtodoz-lenis', 'https://unpkg.com/lenis@1.1.20/dist/lenis.min.js', [], '1.1.20', false);
	wp_enqueue_script('wvtodoz-aos', 'https://unpkg.com/aos@2.3.1/dist/aos.js', [], '2.3.1', false);

	$deps = ['wvtodoz-gsap', 'wvtodoz-scrolltrigger', 'wvtodoz-lenis', 'wvtodoz-aos'];
	wp_enqueue_script('wvtodoz-site-utils', WVTODOZ_URI . '/assets/js/site-utils.js', $deps, $ver, true);
	wp_enqueue_script('wvtodoz-main', WVTODOZ_URI . '/assets/js/main.js', ['wvtodoz-site-utils'], $ver, true);
	wp_enqueue_script('wvtodoz-animations', WVTODOZ_URI . '/assets/js/animations.js', ['wvtodoz-main'], $ver, true);
	wp_enqueue_script('wvtodoz-components', WVTODOZ_URI . '/assets/js/components.js', ['wvtodoz-animations'], $ver, true);
}, 20);

add_filter('script_loader_tag', static function (string $tag, string $handle, string $src): string {
	if ($handle === 'wvtodoz-tailwind') {
		return $tag;
	}
	return $tag;
}, 10, 3);

add_action('wp_head', static function (): void {
	echo '<meta name="theme-color" content="#0a0a0a">' . "\n";
	$favicon = WVTODOZ_URI . '/assets/img/favicon.svg';
	echo '<link rel="icon" href="' . esc_url($favicon) . '" type="image/svg+xml">' . "\n";
	echo '<link rel="icon" href="' . esc_url(WVTODOZ_URI . '/assets/img/favicon-32.png') . '" type="image/png" sizes="32x32">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url(WVTODOZ_URI . '/assets/img/apple-touch-icon.png') . '" sizes="180x180">' . "\n";
}, 1);
