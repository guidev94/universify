<?php
/**
 * Theme header.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$logo = wv_logo_url();
$wa   = wv_whatsapp_url();
$nav  = wv_default_nav_items();
$d    = wv_defaults();
$ig   = (string) wv_field('wv_social_instagram', $d['footer']['instagram']);
$li   = (string) wv_field('wv_social_linkedin', $d['footer']['linkedin']);
?><!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="light">
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script>
		(function () {
			try {
				var t = localStorage.getItem('wv-theme');
				if (t !== 'light' && t !== 'dark') t = 'light';
				document.documentElement.setAttribute('data-theme', t);
			} catch (e) {
				document.documentElement.setAttribute('data-theme', 'light');
			}
		})();
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class('bg-primary font-sans selection:bg-todoz selection:text-black overflow-x-hidden'); ?>>
<?php wp_body_open(); ?>

<header id="main-header" class="fixed top-0 w-full z-[100] py-6 transition-all duration-500 border-b border-white/0">
	<div class="site-container flex justify-between items-center">
		<a href="<?php echo esc_url(home_url('/')); ?>" class="block w-40 md:w-56 overflow-hidden">
			<img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="w-full h-auto">
		</a>
		<nav class="hidden lg:flex items-center gap-10 text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-400" aria-label="<?php esc_attr_e('Principal', 'wvtodoz'); ?>">
			<?php
			$desktop_items = $nav;
			if (has_nav_menu('primary')) {
				$locations = get_nav_menu_locations();
				$menu_items = wp_get_nav_menu_items($locations['primary'] ?? 0);
				if (is_array($menu_items) && $menu_items) {
					$desktop_items = [];
					foreach ($menu_items as $mi) {
						if ((int) $mi->menu_item_parent !== 0) {
							continue;
						}
						$desktop_items[] = ['label' => $mi->title, 'url' => $mi->url];
					}
				}
			}
			foreach ($desktop_items as $item) {
				printf(
					'<a href="%s" class="hover:text-todoz transition-colors">%s</a>',
					esc_url($item['url']),
					esc_html($item['label'])
				);
			}
			?>
			<div class="header-actions header-actions--desktop">
				<button type="button" class="theme-toggle" data-theme-toggle aria-label="<?php esc_attr_e('Ativar tema escuro', 'wvtodoz'); ?>" title="<?php esc_attr_e('Ativar tema escuro', 'wvtodoz'); ?>">
					<i class="ph-bold ph-moon theme-toggle__moon" aria-hidden="true"></i>
					<i class="ph-bold ph-sun theme-toggle__sun" aria-hidden="true"></i>
				</button>
				<a href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer" class="magnetic bg-todoz px-8 py-3 text-black font-black rounded-full flex items-center justify-center gap-3 group/header-btn min-w-[160px]">
					<?php echo esc_html((string) wv_field('wv_contato_cta', 'FALE CONOSCO')); ?>
					<div class="relative w-5 h-5 flex items-center justify-center overflow-hidden">
						<i class="ph-bold ph-whatsapp-logo text-lg absolute group-hover/header-btn:-translate-y-full transition-transform duration-300"></i>
						<i class="ph-bold ph-arrow-right text-lg absolute translate-y-full group-hover/header-btn:translate-y-0 transition-transform duration-300"></i>
					</div>
				</a>
			</div>
		</nav>
		<div class="header-actions header-actions--mobile lg:hidden">
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="<?php esc_attr_e('Ativar tema escuro', 'wvtodoz'); ?>" title="<?php esc_attr_e('Ativar tema escuro', 'wvtodoz'); ?>">
				<i class="ph-bold ph-moon theme-toggle__moon" aria-hidden="true"></i>
				<i class="ph-bold ph-sun theme-toggle__sun" aria-hidden="true"></i>
			</button>
			<button type="button" id="mobile-menu-trigger" class="text-4xl text-todoz flex flex-col gap-1.5 items-end" aria-label="<?php esc_attr_e('Abrir menu', 'wvtodoz'); ?>">
				<span class="w-8 h-1 bg-todoz rounded-full"></span>
				<span class="w-5 h-1 bg-todoz rounded-full"></span>
				<span class="w-8 h-1 bg-todoz rounded-full"></span>
			</button>
		</div>
	</div>
</header>

<div id="mobile-menu" class="fixed inset-0 z-[200] pointer-events-none opacity-0 invisible">
	<div class="absolute inset-0 bg-black/95 backdrop-blur-2xl"></div>
	<div id="mobile-menu-content" class="relative h-full w-full max-w-none bg-zinc-900 border-l border-white/5 p-12 flex flex-col justify-between translate-x-full transition-transform duration-500 ease-expo">
		<div class="space-y-12">
			<div class="flex justify-between items-center">
				<img src="<?php echo esc_url($logo); ?>" alt="" class="w-32 h-auto">
				<button type="button" id="mobile-menu-close" class="text-zinc-500 hover:text-todoz transition-colors text-4xl" aria-label="<?php esc_attr_e('Fechar menu', 'wvtodoz'); ?>"><i class="ph ph-x-circle"></i></button>
			</div>
			<nav class="flex flex-col gap-6" aria-label="<?php esc_attr_e('Mobile', 'wvtodoz'); ?>">
				<?php
				$mobile_items = $nav;
				if (has_nav_menu('mobile')) {
					$locations = get_nav_menu_locations();
					$menu_items = wp_get_nav_menu_items($locations['mobile'] ?? 0);
					if (is_array($menu_items) && $menu_items) {
						$mobile_items = [];
						foreach ($menu_items as $mi) {
							$mobile_items[] = ['label' => $mi->title, 'url' => $mi->url];
						}
					}
				}
				foreach ($mobile_items as $item) {
					printf(
						'<a href="%s" class="text-4xl font-black text-white uppercase tracking-tighter hover:text-todoz transition-colors">%s</a>',
						esc_url($item['url']),
						esc_html($item['label'])
					);
				}
				?>
			</nav>
		</div>
		<div class="space-y-8">
			<div class="flex gap-6 text-zinc-400 text-3xl">
				<a href="<?php echo esc_url($ig); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz" aria-label="Instagram"><i class="ph ph-instagram-logo"></i></a>
				<a href="<?php echo esc_url($li); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz" aria-label="LinkedIn"><i class="ph ph-linkedin-logo"></i></a>
			</div>
			<a href="<?php echo esc_url($wa); ?>" class="w-full py-6 bg-todoz text-black font-black rounded-2xl text-center block uppercase tracking-widest text-xs"><?php echo esc_html((string) wv_field('wv_contato_cta', 'FALE CONOSCO')); ?></a>
		</div>
	</div>
</div>
