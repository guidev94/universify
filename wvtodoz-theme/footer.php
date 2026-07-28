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
$opt_tag = function_exists('wv_opt') ? (string) wv_opt('footer_tagline', '') : '';
if ($opt_tag !== '') {
	$tag = $opt_tag;
}
$footer_extra = function_exists('wv_opt') ? (string) wv_opt('footer_extra', '') : '';
$addr  = (string) wv_field('wv_footer_address', $f['address']);
$maps  = (string) wv_field('wv_footer_maps', $f['maps']);
$nav_title = (string) wv_field('wv_footer_nav_title', __('Navegação', 'wvtodoz'));
$products_title = (string) wv_field('wv_footer_products_title', __('Produtos', 'wvtodoz'));
$copy  = (string) wv_field('wv_footer_copyright', $f['copyright']);
$fb    = wv_social_url('facebook', 'wv_social_facebook', (string) $f['facebook']);
$ig    = wv_social_url('instagram', 'wv_social_instagram', (string) $f['instagram']);
$li    = wv_social_url('linkedin', 'wv_social_linkedin', (string) $f['linkedin']);
$yt    = wv_social_url('youtube', 'wv_social_youtube', (string) $f['youtube']);
$priv  = (string) wv_field('wv_footer_privacy', $f['privacy'] ?? ($wa . '?text=' . rawurlencode('Olá, gostaria de receber a Política de Privacidade.')));
$terms = (string) wv_field('wv_footer_terms', $f['terms'] ?? ($wa . '?text=' . rawurlencode('Olá, gostaria de receber os Termos de Uso.')));
$tip   = (string) wv_field('wv_wa_float_label', 'Falar com Especialista');
$show_wa_float = !function_exists('wv_opt') || (int) wv_opt('flag_wa_float', 1) === 1;
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
				<?php if ($footer_extra !== '') : ?>
				<p class="text-zinc-600 text-sm leading-relaxed mb-8 max-w-sm mx-auto md:mx-0"><?php echo nl2br(esc_html($footer_extra)); ?></p>
				<?php endif; ?>
				<?php
				$phone_display = wv_company_phone();
				$email_display = wv_company_email();
				if ($phone_display !== '' || $email_display !== '') :
					?>
				<p class="text-zinc-500 text-sm font-bold mb-8 space-y-1">
					<?php if ($phone_display !== '') : ?>
					<span class="block"><?php echo esc_html($phone_display); ?></span>
					<?php endif; ?>
					<?php if ($email_display !== '') : ?>
					<a class="block hover:text-todoz" href="<?php echo esc_url('mailto:' . $email_display); ?>"><?php echo esc_html($email_display); ?></a>
					<?php endif; ?>
				</p>
				<?php endif; ?>
				<div class="flex gap-6 text-zinc-400 text-3xl justify-center md:justify-start">
					<a href="<?php echo esc_url($fb); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz transition-all" aria-label="Facebook"><i class="ph ph-facebook-logo"></i></a>
					<a href="<?php echo esc_url($ig); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz transition-all" aria-label="Instagram"><i class="ph ph-instagram-logo"></i></a>
					<a href="<?php echo esc_url($li); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz transition-all" aria-label="LinkedIn"><i class="ph ph-linkedin-logo"></i></a>
					<a href="<?php echo esc_url($yt); ?>" target="_blank" rel="noopener noreferrer" class="hover:text-todoz transition-all" aria-label="YouTube"><i class="ph ph-youtube-logo"></i></a>
				</div>
			</div>
			<div class="md:col-span-2 uppercase">
				<h5 class="text-white font-black text-xs uppercase tracking-[0.3em] mb-6 md:mb-8"><?php echo esc_html($nav_title); ?></h5>
				<ul class="space-y-4 text-zinc-500 font-bold text-sm">
					<?php
					$insights_url = function_exists('wv_insights_url') ? wv_insights_url() : ($home . '#blog');
					$contato_url = function_exists('wv_contato_url') ? wv_contato_url() : ($home . 'contato/');
					$servicos_url = function_exists('wv_page_url') ? wv_page_url('servicos', '/servicos/') : ($home . '#servicos');
					$footer_nav = [
						['Sobre Nós', $home . '#sobre'],
						['Serviços', $servicos_url],
						['Produtos', $home . '#produtos'],
						['Aplicações', $home . '#aplicacoes'],
						['Projetos', $home . '#portfolio'],
						['Insights', $insights_url],
						['Contato', $contato_url],
						['Vagas', function_exists('wv_page_url') ? wv_page_url('vagas', '/vagas/') : ($home . 'vagas/')],
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
				<h5 class="text-white font-black text-xs uppercase tracking-[0.3em] mb-6 md:mb-8"><?php echo esc_html($products_title); ?></h5>
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
				<?php
				$priv_url = function_exists('wv_privacy_url') ? wv_privacy_url() : $priv;
				$terms_url = function_exists('wv_terms_url') ? wv_terms_url() : $terms;
				$priv_ext = function_exists('wv_is_external_url') && wv_is_external_url($priv_url);
				$terms_ext = function_exists('wv_is_external_url') && wv_is_external_url($terms_url);
				?>
				<a href="<?php echo esc_url($priv_url); ?>" class="hover:text-white transition-colors"<?php echo $priv_ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php esc_html_e('Política de Privacidade', 'wvtodoz'); ?></a>
				<a href="<?php echo esc_url($terms_url); ?>" class="hover:text-white transition-colors"<?php echo $terms_ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php esc_html_e('Termos de Uso', 'wvtodoz'); ?></a>
			</div>
		</div>
	</div>
	<div class="absolute bottom-0 right-0 pointer-events-none select-none opacity-5 translate-y-1/4 translate-x-1/4 uppercase overflow-hidden max-w-[50vw]" aria-hidden="true">
		<span class="text-[min(40rem,55vw)] font-black text-white leading-none tracking-tighter italic">Z</span>
	</div>
</footer>

<?php get_template_part('template-parts/modals'); ?>

<?php if ($show_wa_float) : ?>
<a id="wa-float" href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer" class="wa-float group" aria-label="<?php esc_attr_e('Falar no WhatsApp', 'wvtodoz'); ?>">
	<div class="wa-float-glow" aria-hidden="true"></div>
	<div class="wa-float-btn">
		<i class="ph-bold ph-whatsapp-logo"></i>
	</div>
	<div class="wa-float-tip" aria-hidden="true">
		<span class="flex items-center gap-3"><?php echo esc_html($tip); ?> <i class="ph ph-arrow-right text-todoz animate-bounce-x"></i></span>
	</div>
</a>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
