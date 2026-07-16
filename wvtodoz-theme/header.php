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
$ig   = wv_social_url('instagram', 'wv_social_instagram', (string) $d['footer']['instagram']);
$li   = wv_social_url('linkedin', 'wv_social_linkedin', (string) $d['footer']['linkedin']);
$default_theme = function_exists('wv_opt') ? (string) wv_opt('default_theme', 'light') : 'light';
if (!in_array($default_theme, ['light', 'dark'], true)) {
	$default_theme = 'light';
}
$show_toggle = !function_exists('wv_opt') || (int) wv_opt('flag_theme_toggle', 1) === 1;
?><!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="<?php echo esc_attr($default_theme); ?>">
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script>
		(function () {
			try {
				var fallback = <?php echo wp_json_encode($default_theme); ?>;
				var t = localStorage.getItem('wv-theme');
				if (t !== 'light' && t !== 'dark') t = fallback || 'light';
				document.documentElement.setAttribute('data-theme', t);
			} catch (e) {
				document.documentElement.setAttribute('data-theme', <?php echo wp_json_encode($default_theme); ?>);
			}
		})();
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class('bg-primary font-sans selection:bg-todoz selection:text-black overflow-x-hidden'); ?>>
<?php wp_body_open(); ?>

<?php
// Pages without the home hero use scrolled chrome from first paint (avoids
// light-mode white-on-wash theme toggle before animations.js runs).
$wv_header_scrolled = !is_front_page();
$wv_header_class = 'fixed top-0 w-full z-[100] py-6 transition-all duration-500 border-b border-white/0' . ($wv_header_scrolled ? ' is-scrolled' : '');
?>
<header id="main-header" class="<?php echo esc_attr($wv_header_class); ?>">
	<div class="site-container flex justify-between items-center gap-3">
		<a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo-link block w-36 sm:w-40 md:w-56 overflow-hidden shrink-0">
			<img src="<?php echo esc_url(wv_logo_light_url()); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="site-logo site-logo--light w-full h-auto" width="1024" height="394" decoding="async">
			<img src="<?php echo esc_url(wv_logo_dark_url()); ?>" alt="" class="site-logo site-logo--dark w-full h-auto" width="1024" height="394" decoding="async" aria-hidden="true">
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
				<?php if ($show_toggle) : ?>
				<button type="button" class="theme-toggle" data-theme-toggle aria-label="<?php esc_attr_e('Ativar tema escuro', 'wvtodoz'); ?>" title="<?php esc_attr_e('Ativar tema escuro', 'wvtodoz'); ?>">
					<i class="ph-bold ph-moon theme-toggle__moon" aria-hidden="true"></i>
					<i class="ph-bold ph-sun theme-toggle__sun" aria-hidden="true"></i>
				</button>
				<?php endif; ?>
				<button type="button" class="open-modal magnetic bg-todoz px-8 py-3 text-black font-black rounded-full flex items-center justify-center gap-3 group/header-btn min-w-[160px]" data-wv-section="header" data-wv-lead-type="contato">
					<?php echo esc_html((string) wv_field('wv_contato_cta', 'FALE CONOSCO')); ?>
					<div class="relative w-5 h-5 flex items-center justify-center overflow-hidden">
						<i class="ph-bold ph-whatsapp-logo text-lg absolute group-hover/header-btn:-translate-y-full transition-transform duration-300"></i>
						<i class="ph-bold ph-arrow-right text-lg absolute translate-y-full group-hover/header-btn:translate-y-0 transition-transform duration-300"></i>
					</div>
				</button>
			</div>
		</nav>
		<div class="header-actions header-actions--mobile lg:hidden">
			<?php if ($show_toggle) : ?>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="<?php esc_attr_e('Ativar tema escuro', 'wvtodoz'); ?>" title="<?php esc_attr_e('Ativar tema escuro', 'wvtodoz'); ?>">
				<i class="ph-bold ph-moon theme-toggle__moon" aria-hidden="true"></i>
				<i class="ph-bold ph-sun theme-toggle__sun" aria-hidden="true"></i>
			</button>
			<?php endif; ?>
			<button type="button" id="mobile-menu-trigger" class="mobile-menu-trigger" aria-label="<?php esc_attr_e('Abrir menu', 'wvtodoz'); ?>" aria-controls="mobile-menu" aria-expanded="false">
				<span class="mobile-menu-trigger__bar" aria-hidden="true"></span>
				<span class="mobile-menu-trigger__bar mobile-menu-trigger__bar--short" aria-hidden="true"></span>
				<span class="mobile-menu-trigger__bar" aria-hidden="true"></span>
			</button>
		</div>
	</div>
</header>

<div id="mobile-menu" class="fixed inset-0 z-[200] pointer-events-none opacity-0 invisible" aria-hidden="true">
	<div class="mobile-menu-backdrop absolute inset-0 bg-black/95 backdrop-blur-2xl" data-mobile-menu-dismiss></div>
	<div id="mobile-menu-content" class="relative z-[1] h-full w-full max-w-none bg-zinc-900 border-l border-white/5 flex flex-col translate-x-full transition-transform duration-500 ease-expo">
		<div class="mobile-menu-panel flex flex-col justify-between flex-1 min-h-0 overflow-y-auto overscroll-contain">
			<div class="space-y-8 sm:space-y-12">
				<div class="flex justify-between items-center gap-4">
					<span class="site-logo-link inline-block w-28 sm:w-32 shrink-0">
						<img src="<?php echo esc_url(wv_logo_light_url()); ?>" alt="" class="site-logo site-logo--light w-full h-auto" width="1024" height="394" decoding="async">
						<img src="<?php echo esc_url(wv_logo_dark_url()); ?>" alt="" class="site-logo site-logo--dark w-full h-auto" width="1024" height="394" decoding="async" aria-hidden="true">
					</span>
					<button type="button" id="mobile-menu-close" class="mobile-menu-close text-zinc-500 hover:text-todoz transition-colors" aria-label="<?php esc_attr_e('Fechar menu', 'wvtodoz'); ?>"><i class="ph ph-x-circle" aria-hidden="true"></i></button>
				</div>
				<nav class="mobile-menu-nav flex flex-col" aria-label="<?php esc_attr_e('Mobile', 'wvtodoz'); ?>">
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
							'<a href="%s" class="mobile-menu-link text-4xl font-black text-white uppercase tracking-tighter hover:text-todoz transition-colors">%s</a>',
							esc_url($item['url']),
							esc_html($item['label'])
						);
					}
					?>
				</nav>
			</div>
			<div class="mobile-menu-footer space-y-6 sm:space-y-8">
				<div class="flex gap-6 text-zinc-400 text-3xl">
					<a href="<?php echo esc_url($ig); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz" aria-label="Instagram"><i class="ph ph-instagram-logo" aria-hidden="true"></i></a>
					<a href="<?php echo esc_url($li); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz" aria-label="LinkedIn"><i class="ph ph-linkedin-logo" aria-hidden="true"></i></a>
				</div>
				<a href="<?php echo esc_url($wa); ?>" class="mobile-menu-cta w-full py-5 sm:py-6 bg-todoz text-black font-black rounded-2xl text-center block uppercase tracking-widest text-xs"><?php echo esc_html((string) wv_field('wv_contato_cta', 'FALE CONOSCO')); ?></a>
			</div>
		</div>
	</div>
</div>
