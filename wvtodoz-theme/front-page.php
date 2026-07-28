<?php
/**
 * Front page — homepage sections.
 *
 * @package WVTodoz
 */

get_header();
?>
<main id="main-wrapper">
	<?php
	get_template_part('template-parts/sections/hero');
	get_template_part('template-parts/sections/clientes');
	get_template_part('template-parts/sections/sobre');
	get_template_part('template-parts/sections/missao');
	get_template_part('template-parts/sections/mobile-bridge');
	get_template_part('template-parts/sections/servicos');
	get_template_part('template-parts/sections/portfolio');
	get_template_part('template-parts/sections/produtos');
	get_template_part('template-parts/sections/aplicacoes');
	get_template_part('template-parts/sections/blog');
	get_template_part('template-parts/sections/contato');
	?>
</main>
<?php
get_footer();
