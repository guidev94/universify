<?php
/**
 * Theme options — Aparência / Opções do tema (ACF Free has no Options Page).
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

const WV_THEME_OPTIONS_KEY = 'wv_theme_options';

/**
 * @return array<string, mixed>
 */
function wv_theme_option_defaults(): array {
	$d = function_exists('wv_defaults') ? wv_defaults() : [];
	return [
		'accent'           => '#CFFF04',
		'logo_light'       => '',
		'logo_dark'        => '',
		'default_theme'    => 'light',
		'whatsapp'         => '551155311779',
		'email'            => 'contato@wvtodoz.com.br',
		'phone'            => '+55 11 5531-1779',
		'social_instagram' => (string) ($d['footer']['instagram'] ?? 'https://www.instagram.com/wvtodoz/'),
		'social_linkedin'  => (string) ($d['footer']['linkedin'] ?? 'https://www.linkedin.com/company/wvtodoz/'),
		'social_facebook'  => (string) ($d['footer']['facebook'] ?? ''),
		'social_youtube'   => (string) ($d['footer']['youtube'] ?? ''),
		'footer_tagline'   => '',
		'footer_extra'     => '',
		'flag_wa_float'    => 1,
		'flag_theme_toggle'=> 1,
		'flag_lead_whatsapp' => 1,
		'flag_blog_sidebar'=> 1,
	];
}

/**
 * @return array<string, mixed>
 */
function wv_get_theme_options(): array {
	$stored = get_option(WV_THEME_OPTIONS_KEY, []);
	if (!is_array($stored)) {
		$stored = [];
	}
	return array_merge(wv_theme_option_defaults(), $stored);
}

/**
 * @param mixed $default
 * @return mixed
 */
function wv_opt(string $key, $default = null) {
	$opts = wv_get_theme_options();
	if (!array_key_exists($key, $opts)) {
		return $default;
	}
	$value = $opts[$key];
	if ($value === '' || $value === null) {
		return $default;
	}
	return $value;
}

add_action('admin_menu', static function (): void {
	add_submenu_page(
		'wvtodoz',
		__('Aparência / Opções do tema', 'wvtodoz'),
		__('Aparência', 'wvtodoz'),
		'manage_options',
		'wvtodoz-aparencia',
		'wv_admin_render_theme_options'
	);
}, 11);

add_action('admin_init', static function (): void {
	register_setting(
		'wv_theme_options_group',
		WV_THEME_OPTIONS_KEY,
		[
			'type'              => 'array',
			'sanitize_callback' => 'wv_sanitize_theme_options',
			'default'           => wv_theme_option_defaults(),
		]
	);
});

/**
 * @param mixed $input
 * @return array<string, mixed>
 */
function wv_sanitize_theme_options($input): array {
	$defaults = wv_theme_option_defaults();
	$out = wv_get_theme_options();
	if (!is_array($input)) {
		return $out;
	}

	$out['accent'] = sanitize_hex_color((string) ($input['accent'] ?? $defaults['accent'])) ?: $defaults['accent'];
	$out['logo_light'] = esc_url_raw((string) ($input['logo_light'] ?? ''));
	$out['logo_dark'] = esc_url_raw((string) ($input['logo_dark'] ?? ''));
	$theme = sanitize_key((string) ($input['default_theme'] ?? 'light'));
	$out['default_theme'] = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';

	$wa = preg_replace('/\D+/', '', (string) ($input['whatsapp'] ?? '')) ?? '';
	$out['whatsapp'] = $wa !== '' ? $wa : $defaults['whatsapp'];
	$out['email'] = sanitize_email((string) ($input['email'] ?? ''));
	$out['phone'] = sanitize_text_field((string) ($input['phone'] ?? ''));

	foreach (['social_instagram', 'social_linkedin', 'social_facebook', 'social_youtube'] as $k) {
		$out[$k] = esc_url_raw((string) ($input[$k] ?? ''));
	}

	$out['footer_tagline'] = sanitize_text_field((string) ($input['footer_tagline'] ?? ''));
	$out['footer_extra'] = sanitize_textarea_field((string) ($input['footer_extra'] ?? ''));

	foreach (['flag_wa_float', 'flag_theme_toggle', 'flag_lead_whatsapp', 'flag_blog_sidebar'] as $flag) {
		$out[$flag] = !empty($input[$flag]) ? 1 : 0;
	}

	return $out;
}

function wv_admin_render_theme_options(): void {
	if (!current_user_can('manage_options')) {
		return;
	}

	$opts = wv_get_theme_options();
	wv_admin_shell_open(
		__('Aparência / Opções do tema', 'wvtodoz'),
		__('Cores, logos, tema padrão, contatos e flags — sem depender do ACF Pro Options Page.', 'wvtodoz')
	);

	if (isset($_GET['settings-updated'])) {
		wv_admin_notice('ok', __('Opções salvas.', 'wvtodoz'));
	}
	?>
	<form method="post" action="options.php" class="wv-admin__card wv-admin__card--wide wv-options-form">
		<?php settings_fields('wv_theme_options_group'); ?>

		<section class="wv-options-section">
			<h2 class="wv-admin__card-title"><?php esc_html_e('Marca & tema', 'wvtodoz'); ?></h2>
			<div class="wv-options-grid">
				<label class="wv-options-field">
					<span><?php esc_html_e('Cor de destaque (lime)', 'wvtodoz'); ?></span>
					<input type="color" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[accent]" value="<?php echo esc_attr((string) $opts['accent']); ?>">
				</label>
				<label class="wv-options-field">
					<span><?php esc_html_e('Tema padrão', 'wvtodoz'); ?></span>
					<select name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[default_theme]">
						<option value="light" <?php selected($opts['default_theme'], 'light'); ?>><?php esc_html_e('Claro', 'wvtodoz'); ?></option>
						<option value="dark" <?php selected($opts['default_theme'], 'dark'); ?>><?php esc_html_e('Escuro', 'wvtodoz'); ?></option>
					</select>
				</label>
				<label class="wv-options-field wv-options-field--full">
					<span><?php esc_html_e('Logo clara (URL)', 'wvtodoz'); ?></span>
					<input type="url" class="regular-text" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[logo_light]" value="<?php echo esc_attr((string) $opts['logo_light']); ?>" placeholder="<?php echo esc_attr(WVTODOZ_URI . '/assets/img/logo-wv-light.svg'); ?>">
				</label>
				<label class="wv-options-field wv-options-field--full">
					<span><?php esc_html_e('Logo escura (URL)', 'wvtodoz'); ?></span>
					<input type="url" class="regular-text" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[logo_dark]" value="<?php echo esc_attr((string) $opts['logo_dark']); ?>" placeholder="<?php echo esc_attr(WVTODOZ_URI . '/assets/img/logo-wv-dark.svg'); ?>">
				</label>
			</div>
			<p class="wv-admin__hint"><?php esc_html_e('Deixe em branco para usar os SVGs do tema. Cole a URL da mídia após enviar em Biblioteca.', 'wvtodoz'); ?></p>
		</section>

		<section class="wv-options-section">
			<h2 class="wv-admin__card-title"><?php esc_html_e('Contato & redes', 'wvtodoz'); ?></h2>
			<div class="wv-options-grid">
				<label class="wv-options-field">
					<span><?php esc_html_e('WhatsApp (somente dígitos)', 'wvtodoz'); ?></span>
					<input type="text" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[whatsapp]" value="<?php echo esc_attr((string) $opts['whatsapp']); ?>" placeholder="551155311779">
				</label>
				<label class="wv-options-field">
					<span><?php esc_html_e('Telefone (exibição)', 'wvtodoz'); ?></span>
					<input type="text" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[phone]" value="<?php echo esc_attr((string) $opts['phone']); ?>">
				</label>
				<label class="wv-options-field">
					<span><?php esc_html_e('E-mail', 'wvtodoz'); ?></span>
					<input type="email" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[email]" value="<?php echo esc_attr((string) $opts['email']); ?>">
				</label>
				<label class="wv-options-field">
					<span>Instagram</span>
					<input type="url" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[social_instagram]" value="<?php echo esc_attr((string) $opts['social_instagram']); ?>">
				</label>
				<label class="wv-options-field">
					<span>LinkedIn</span>
					<input type="url" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[social_linkedin]" value="<?php echo esc_attr((string) $opts['social_linkedin']); ?>">
				</label>
				<label class="wv-options-field">
					<span>Facebook</span>
					<input type="url" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[social_facebook]" value="<?php echo esc_attr((string) $opts['social_facebook']); ?>">
				</label>
				<label class="wv-options-field">
					<span>YouTube</span>
					<input type="url" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[social_youtube]" value="<?php echo esc_attr((string) $opts['social_youtube']); ?>">
				</label>
			</div>
		</section>

		<section class="wv-options-section">
			<h2 class="wv-admin__card-title"><?php esc_html_e('Rodapé & extras', 'wvtodoz'); ?></h2>
			<div class="wv-options-grid">
				<label class="wv-options-field wv-options-field--full">
					<span><?php esc_html_e('Tagline extra (sobrescreve ACF se preenchida)', 'wvtodoz'); ?></span>
					<input type="text" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[footer_tagline]" value="<?php echo esc_attr((string) $opts['footer_tagline']); ?>">
				</label>
				<label class="wv-options-field wv-options-field--full">
					<span><?php esc_html_e('Texto extra do rodapé', 'wvtodoz'); ?></span>
					<textarea name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[footer_extra]" rows="3"><?php echo esc_textarea((string) $opts['footer_extra']); ?></textarea>
				</label>
			</div>
		</section>

		<section class="wv-options-section">
			<h2 class="wv-admin__card-title"><?php esc_html_e('Flags', 'wvtodoz'); ?></h2>
			<div class="wv-options-flags">
				<label><input type="checkbox" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[flag_wa_float]" value="1" <?php checked(!empty($opts['flag_wa_float'])); ?>> <?php esc_html_e('Botão flutuante WhatsApp', 'wvtodoz'); ?></label>
				<label><input type="checkbox" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[flag_theme_toggle]" value="1" <?php checked(!empty($opts['flag_theme_toggle'])); ?>> <?php esc_html_e('Toggle claro/escuro', 'wvtodoz'); ?></label>
				<label><input type="checkbox" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[flag_lead_whatsapp]" value="1" <?php checked(!empty($opts['flag_lead_whatsapp'])); ?>> <?php esc_html_e('Após enviar lead, abrir WhatsApp', 'wvtodoz'); ?></label>
				<label><input type="checkbox" name="<?php echo esc_attr(WV_THEME_OPTIONS_KEY); ?>[flag_blog_sidebar]" value="1" <?php checked(!empty($opts['flag_blog_sidebar'])); ?>> <?php esc_html_e('Sidebar no single do blog', 'wvtodoz'); ?></label>
			</div>
		</section>

		<?php submit_button(__('Salvar opções', 'wvtodoz')); ?>
	</form>
	<?php
	wv_admin_shell_close();
}

/**
 * Inject CSS variables + default theme bootstrap from options.
 */
add_action('wp_head', static function (): void {
	$opts = wv_get_theme_options();
	$accent = (string) ($opts['accent'] ?? '#CFFF04');
	$default = (string) ($opts['default_theme'] ?? 'light');
	if (!in_array($default, ['light', 'dark'], true)) {
		$default = 'light';
	}
	echo '<style id="wv-theme-options">:root{--todoz:' . esc_attr($accent) . ';--wv-accent:' . esc_attr($accent) . ';}</style>' . "\n";
	echo '<script id="wv-theme-default">(function(){try{var d=' . wp_json_encode($default) . ';var t=localStorage.getItem("wv-theme");if(t!=="light"&&t!=="dark"){localStorage.setItem("wv-theme",d);document.documentElement.setAttribute("data-theme",d);}}catch(e){}})();</script>' . "\n";
}, 0);
