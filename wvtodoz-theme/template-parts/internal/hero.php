<?php
/**
 * Internal page hero — matches Home section display language.
 *
 * @package WVTodoz
 *
 * @var array{eyebrow?:string,title_line?:string,title_accent?:string,lead?:string,breadcrumb?:string} $args
 */

if (!defined('ABSPATH')) {
	exit;
}

$eyebrow = (string) ($args['eyebrow'] ?? '');
$line = (string) ($args['title_line'] ?? '');
$accent = (string) ($args['title_accent'] ?? '');
$lead = (string) ($args['lead'] ?? '');
$crumb = (string) ($args['breadcrumb'] ?? get_the_title());
$home = home_url('/');
?>
<nav class="wv-page-breadcrumb flex items-center gap-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 mb-12" data-aos="fade-right" aria-label="<?php esc_attr_e('Breadcrumb', 'wvtodoz'); ?>">
	<a href="<?php echo esc_url($home); ?>" class="hover:text-todoz"><?php esc_html_e('Home', 'wvtodoz'); ?></a>
	<i class="ph ph-caret-right" aria-hidden="true"></i>
	<span class="text-todoz/50"><?php echo esc_html($crumb); ?></span>
</nav>

<header class="wv-page-hero mb-12 md:mb-16" data-aos="fade-up">
	<?php if ($eyebrow !== '') : ?>
	<span class="section-eyebrow text-todoz"><?php echo esc_html($eyebrow); ?></span>
	<?php endif; ?>
	<h1 class="section-display">
		<?php if ($line !== '') : ?>
		<span class="section-display-line"><?php echo esc_html($line); ?></span>
		<?php endif; ?>
		<?php if ($accent !== '') : ?>
		<span class="section-display-accent"><?php echo esc_html($accent); ?><span class="section-display-dot" aria-hidden="true"></span></span>
		<?php elseif ($line === '') : ?>
		<span class="section-display-line"><?php the_title(); ?></span>
		<?php endif; ?>
	</h1>
	<?php if ($lead !== '') : ?>
	<p class="wv-page-hero__lead"><?php echo esc_html($lead); ?></p>
	<?php endif; ?>
</header>
