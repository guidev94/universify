<?php
/**
 * Theme footer + modals + WhatsApp float.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d     = wv_defaults();
$logo  = wv_logo_url();
$wa    = wv_whatsapp_url();
$f     = $d['footer'];
$tag   = (string) wv_field('wv_footer_tagline', $f['tagline']);
$addr  = (string) wv_field('wv_footer_address', $f['address']);
$maps  = (string) wv_field('wv_footer_maps', $f['maps']);
$copy  = (string) wv_field('wv_footer_copyright', $f['copyright']);
$fb    = (string) wv_field('wv_social_facebook', $f['facebook']);
$ig    = (string) wv_field('wv_social_instagram', $f['instagram']);
$li    = (string) wv_field('wv_social_linkedin', $f['linkedin']);
$yt    = (string) wv_field('wv_social_youtube', $f['youtube']);
$priv  = (string) wv_field('wv_footer_privacy', $f['privacy'] ?? ($wa . '?text=' . rawurlencode('Olá, gostaria de receber a Política de Privacidade.')));
$terms = (string) wv_field('wv_footer_terms', $f['terms'] ?? ($wa . '?text=' . rawurlencode('Olá, gostaria de receber os Termos de Uso.')));
$tip   = (string) wv_field('wv_wa_float_label', 'Falar com Especialista');
$modal_t = (string) wv_field('wv_modal_title', $d['modal']['title']);
$modal_a = (string) wv_field('wv_modal_title_accent', $d['modal']['title_accent']);
$home  = home_url('/');
?>

<footer class="py-32 bg-black relative overflow-hidden border-t border-white/5">
	<div class="site-container relative z-10 text-center md:text-left">
		<div class="grid grid-cols-1 md:grid-cols-12 gap-12 md:gap-24 mb-16 md:mb-24">
			<div class="md:col-span-4">
				<span class="site-logo-link site-logo-link--on-dark inline-block w-40 md:w-48 mb-8 md:mb-10 mx-auto md:mx-0">
					<img src="<?php echo esc_url(wv_logo_dark_url()); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="site-logo site-logo--dark w-full h-auto" width="1024" height="394" decoding="async">
				</span>
				<p class="footer-tagline text-zinc-500 leading-relaxed mb-8 md:mb-10 max-w-sm mx-auto md:mx-0"><?php echo esc_html($tag); ?></p>
				<div class="flex gap-6 text-zinc-400 text-3xl justify-center md:justify-start">
					<a href="<?php echo esc_url($fb); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz transition-all" aria-label="Facebook"><i class="ph ph-facebook-logo"></i></a>
					<a href="<?php echo esc_url($ig); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz transition-all" aria-label="Instagram"><i class="ph ph-instagram-logo"></i></a>
					<a href="<?php echo esc_url($li); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz transition-all" aria-label="LinkedIn"><i class="ph ph-linkedin-logo"></i></a>
					<a href="<?php echo esc_url($yt); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz transition-all" aria-label="YouTube"><i class="ph ph-youtube-logo"></i></a>
				</div>
			</div>
			<div class="md:col-span-2 uppercase">
				<h5 class="text-white font-black text-xs uppercase tracking-[0.3em] mb-6 md:mb-8"><?php esc_html_e('Navegação', 'wvtodoz'); ?></h5>
				<ul class="space-y-4 text-zinc-500 font-bold text-sm">
					<?php
					$footer_nav = [
						['Sobre Nós', $home . '#sobre'],
						['Serviços', $home . '#servicos'],
						['Produtos', $home . '#produtos'],
						['Aplicações', $home . '#aplicacoes'],
						['Projetos', $home . '#portfolio'],
						['Insights', $home . '#blog'],
					];
					if (has_nav_menu('footer')) {
						$locations = get_nav_menu_locations();
						$menu_items = wp_get_nav_menu_items($locations['footer'] ?? 0);
						if (is_array($menu_items) && $menu_items) {
							$footer_nav = [];
							foreach ($menu_items as $mi) {
								if ((int) $mi->menu_item_parent !== 0) {
									continue;
								}
								$footer_nav[] = [$mi->title, $mi->url];
							}
						}
					}
					foreach ($footer_nav as $item) {
						printf(
							'<li><a href="%s" class="hover:text-todoz transition-all">%s</a></li>',
							esc_url($item[1]),
							esc_html($item[0])
						);
					}
					?>
				</ul>
			</div>
			<div class="md:col-span-3 uppercase">
				<h5 class="text-white font-black text-xs uppercase tracking-[0.3em] mb-6 md:mb-8"><?php esc_html_e('Produtos', 'wvtodoz'); ?></h5>
				<ul class="space-y-4 text-zinc-500 font-bold text-sm">
					<?php
					$footer_products = [];
					foreach (wv_query_posts('wv_produto') as $p) {
						$url = (string) wv_field('wv_produto_primary_url', '', $p->ID);
						if ($url === '') {
							continue;
						}
						$footer_products[] = [
							'label' => get_the_title($p),
							'url'   => $url,
						];
					}
					if (!$footer_products) {
						foreach ($d['produtos_cards'] as $card) {
							$url = (string) ($card['primary_url'] ?? '');
							if ($url === '') {
								continue;
							}
							$footer_products[] = [
								'label' => (string) ($card['title'] ?? ''),
								'url'   => $url,
							];
						}
					}
					foreach ($footer_products as $item) {
						printf(
							'<li><a href="%s" target="_blank" rel="noopener noreferrer" class="hover:text-todoz transition-all">%s</a></li>',
							esc_url($item['url']),
							esc_html($item['label'])
						);
					}
					?>
				</ul>
			</div>
			<div class="md:col-span-3 uppercase">
				<h5 class="text-white font-black text-xs uppercase tracking-[0.3em] mb-6 md:mb-8"><?php esc_html_e('Localização', 'wvtodoz'); ?></h5>
				<p class="text-zinc-500 leading-relaxed font-bold text-sm max-w-xs mx-auto md:mx-0">
					<?php echo nl2br(esc_html($addr)); ?>
				</p>
				<a href="<?php echo esc_url($maps); ?>" target="_blank" rel="noopener noreferrer" class="inline-block mt-6 text-todoz text-[10px] font-black uppercase tracking-widest border-b border-todoz/30 pb-1"><?php esc_html_e('Ver no Google Maps', 'wvtodoz'); ?></a>
			</div>
		</div>

		<div class="pt-12 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-8">
			<div class="text-zinc-600 text-[10px] font-black uppercase tracking-[0.4em]">
				<?php echo esc_html($copy); ?>
			</div>
			<div class="flex items-center gap-8 text-zinc-600 text-[10px] font-black uppercase tracking-widest">
				<a href="<?php echo esc_url($priv); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors"><?php esc_html_e('Política de Privacidade', 'wvtodoz'); ?></a>
				<a href="<?php echo esc_url($terms); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors"><?php esc_html_e('Termos de Uso', 'wvtodoz'); ?></a>
			</div>
		</div>
	</div>
	<div class="absolute bottom-0 right-0 pointer-events-none select-none opacity-5 translate-y-1/4 translate-x-1/4 uppercase overflow-hidden max-w-[50vw]" aria-hidden="true">
		<span class="text-[min(40rem,55vw)] font-black text-white leading-none tracking-tighter italic">Z</span>
	</div>
</footer>

<?php get_template_part('template-parts/modals'); ?>

<a id="wa-float" href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer" class="wa-float group" aria-label="<?php esc_attr_e('Falar no WhatsApp', 'wvtodoz'); ?>">
	<div class="wa-float-glow" aria-hidden="true"></div>
	<div class="wa-float-btn">
		<i class="ph-bold ph-whatsapp-logo"></i>
	</div>
	<div class="wa-float-tip" aria-hidden="true">
		<span class="flex items-center gap-3"><?php echo esc_html($tip); ?> <i class="ph ph-arrow-right text-todoz animate-bounce-x"></i></span>
	</div>
</a>

<?php wp_footer(); ?>
</body>
</html>
