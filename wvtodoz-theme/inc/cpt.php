<?php
/**
 * Custom post types — repeating content without ACF Pro Repeaters.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

add_action('init', static function (): void {
	// show_in_menu false — edit screens stay reachable from WV Todoz hubs
	// (admin.php?page=wvtodoz-*) via edit.php?post_type=… URLs.
	$common = [
		'public'             => true,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'show_in_rest'       => true,
		'has_archive'        => false,
		'show_in_nav_menus'  => false,
		'publicly_queryable' => true,
	];

	register_post_type('wv_case', array_merge($common, [
		'labels' => [
			'name'          => __('Cases', 'wvtodoz'),
			'singular_name' => __('Case', 'wvtodoz'),
			'add_new_item'  => __('Adicionar case', 'wvtodoz'),
			'edit_item'     => __('Editar case', 'wvtodoz'),
			'menu_name'     => __('Cases', 'wvtodoz'),
		],
		'supports' => ['title', 'editor', 'thumbnail', 'page-attributes', 'excerpt'],
		'rewrite'  => ['slug' => 'cases'],
	]));

	register_post_type('wv_produto', array_merge($common, [
		'labels' => [
			'name'          => __('Produtos', 'wvtodoz'),
			'singular_name' => __('Produto', 'wvtodoz'),
			'add_new_item'  => __('Adicionar produto', 'wvtodoz'),
			'edit_item'     => __('Editar produto', 'wvtodoz'),
			'menu_name'     => __('Produtos', 'wvtodoz'),
		],
		'supports' => ['title', 'editor', 'thumbnail', 'page-attributes'],
		'rewrite'  => ['slug' => 'produtos'],
	]));

	register_post_type('wv_cliente', array_merge($common, [
		'labels' => [
			'name'          => __('Clientes', 'wvtodoz'),
			'singular_name' => __('Cliente', 'wvtodoz'),
			'add_new_item'  => __('Adicionar cliente', 'wvtodoz'),
			'edit_item'     => __('Editar cliente', 'wvtodoz'),
			'menu_name'     => __('Clientes', 'wvtodoz'),
		],
		'supports'           => ['title', 'thumbnail', 'page-attributes'],
		'publicly_queryable' => false,
		'rewrite'            => false,
	]));

	register_post_type('wv_servico', array_merge($common, [
		'labels' => [
			'name'          => __('Serviços', 'wvtodoz'),
			'singular_name' => __('Serviço', 'wvtodoz'),
			'add_new_item'  => __('Adicionar serviço', 'wvtodoz'),
			'edit_item'     => __('Editar serviço', 'wvtodoz'),
			'menu_name'     => __('Serviços', 'wvtodoz'),
		],
		'supports'           => ['title', 'editor', 'page-attributes'],
		'publicly_queryable' => false,
		'rewrite'            => false,
	]));
});
