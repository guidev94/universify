<?php
/**
 * Theme supports and menus.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

add_action('after_setup_theme', static function (): void {
	load_theme_textdomain('wvtodoz', WVTODOZ_DIR . '/languages');

	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('custom-logo', [
		'height'      => 120,
		'width'       => 320,
		'flex-height' => true,
		'flex-width'  => true,
	]);
	add_theme_support('html5', [
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	]);

	register_nav_menus([
		'primary' => __('Menu principal (desktop)', 'wvtodoz'),
		'mobile'  => __('Menu mobile', 'wvtodoz'),
		'footer'  => __('Menu rodapé — Navegação', 'wvtodoz'),
	]);

	add_image_size('wv-case', 1024, 576, true);
	add_image_size('wv-blog', 1200, 675, true);
});
