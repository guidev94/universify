<?php
/**
 * 404 — branded WV Todoz.
 *
 * @package WVTodoz
 */

get_header();

$home = home_url('/');
$contato = function_exists('wv_contato_url') ? wv_contato_url() : home_url('/contato/');
$insights = function_exists('wv_insights_url') ? wv_insights_url() : ($home . '#blog');
?>
<main class="pt-40 pb-32 section-canvas">
	<div class="site-container">
		<div class="wv-404 max-w-3xl mx-auto text-center" data-aos="fade-up">
			<span class="section-eyebrow text-todoz"><?php esc_html_e('Erro 404', 'wvtodoz'); ?></span>
			<p class="wv-404__code" aria-hidden="true">404</p>
			<h1 class="section-display mb-6">
				<span class="section-display-line"><?php esc_html_e('Página não', 'wvtodoz'); ?></span>
				<span class="section-display-accent"><?php esc_html_e('encontrada', 'wvtodoz'); ?><span class="section-display-dot" aria-hidden="true"></span></span>
			</h1>
			<p class="wv-404__lead text-zinc-400 text-lg leading-relaxed mb-12 max-w-xl mx-auto">
				<?php esc_html_e('O endereço pode ter mudado ou nunca existiu. Volte à Home, fale conosco ou explore os Insights.', 'wvtodoz'); ?>
			</p>
			<div class="flex flex-wrap items-center justify-center gap-4">
				<a href="<?php echo esc_url($home); ?>" class="magnetic inline-block px-10 py-4 bg-todoz text-black font-black rounded-full text-[10px] uppercase tracking-widest"><?php esc_html_e('Ir para Home', 'wvtodoz'); ?></a>
				<a href="<?php echo esc_url($contato); ?>" class="magnetic inline-block px-10 py-4 border border-white/15 rounded-full font-black text-[10px] uppercase tracking-widest hover:border-todoz/40 hover:text-todoz transition-colors"><?php esc_html_e('Contato', 'wvtodoz'); ?></a>
				<a href="<?php echo esc_url($insights); ?>" class="magnetic inline-block px-10 py-4 border border-white/15 rounded-full font-black text-[10px] uppercase tracking-widest hover:border-todoz/40 hover:text-todoz transition-colors"><?php esc_html_e('Insights', 'wvtodoz'); ?></a>
			</div>
			<form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="blog-widget__search relative max-w-md mx-auto mt-14">
				<input type="search" name="s" class="blog-widget__input w-full rounded-2xl px-6 py-4 outline-none focus:border-todoz transition-all text-sm pr-12" placeholder="<?php esc_attr_e('Buscar no site…', 'wvtodoz'); ?>">
				<button type="submit" class="absolute right-4 top-1/2 -translate-y-1/2 text-todoz text-xl" aria-label="<?php esc_attr_e('Buscar', 'wvtodoz'); ?>">
					<i class="ph ph-magnifying-glass" aria-hidden="true"></i>
				</button>
			</form>
		</div>
	</div>
</main>
<?php
get_footer();
