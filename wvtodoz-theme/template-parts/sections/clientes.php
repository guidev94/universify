<?php
/**
 * Clientes logos strip.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults();
$title = wv_field('wv_clientes_title', $d['clientes_title_html']);
$posts = wv_query_posts('wv_cliente');
$items = [];

if ($posts) {
	foreach ($posts as $p) {
		$img = get_the_post_thumbnail_url($p, 'full');
		if (!$img) {
			continue;
		}
		$items[] = ['name' => get_the_title($p), 'url' => $img];
	}
}
if (!$items) {
	$items = $d['clientes'];
}
?>
<section id="clientes" class="section-canvas section-glow py-24">
	<div class="site-container text-center">
		<h3 class="text-xs md:text-lg font-bold uppercase tracking-[0.2em] text-white mb-16 max-w-4xl mx-auto leading-relaxed" data-aos="fade-up">
			<?php echo wp_kses_post((string) $title); ?>
		</h3>
		<ul class="clientes-logos" aria-label="<?php esc_attr_e('Marcas clientes', 'wvtodoz'); ?>">
			<?php foreach ($items as $i => $item) : ?>
			<li class="clientes-logo" data-aos="fade-up"<?php echo $i ? ' data-aos-delay="' . esc_attr((string) ($i * 100)) . '"' : ''; ?>>
				<img src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item['name']); ?>" width="180" height="56" loading="lazy" decoding="async" class="clientes-logo__img">
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
