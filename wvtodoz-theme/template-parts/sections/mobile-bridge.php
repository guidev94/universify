<?php
/**
 * Mobile bridge section.
 *
 * @package WVTodoz
 */

if (!defined('ABSPATH')) {
	exit;
}

$d = wv_defaults()['mobile_bridge'];
$line1 = (string) wv_field('wv_bridge_line1', $d['line1']);
$line2_prefix = (string) wv_field('wv_bridge_line2_prefix', $d['line2_prefix']);
$accent = (string) wv_field('wv_bridge_accent', $d['accent']);
$line3 = (string) wv_field('wv_bridge_line3', $d['line3']);
$line4 = (string) wv_field('wv_bridge_line4', $d['line4']);
$eyebrow = (string) wv_field('wv_bridge_eyebrow', $d['eyebrow']);
$lead = (string) wv_field('wv_bridge_lead', $d['lead']);
$cta = (string) wv_field('wv_bridge_cta', $d['cta']);
?>
<section id="mobile-bridge" class="section-canvas mobile-bridge-section">
	<div class="site-container">
		<div class="mobile-bridge-card" data-aos="zoom-in-up">
			<div class="mobile-bridge-grid">
				<div class="mobile-bridge-headline">
					<h3 class="mobile-bridge-title">
						<span class="mobile-bridge-quote" aria-hidden="true">“</span><?php echo esc_html($line1); ?><br>
						<?php echo esc_html($line2_prefix); ?> <span class="mobile-bridge-accent"><?php echo esc_html($accent); ?></span><br>
						<?php echo esc_html($line3); ?><br>
						<?php echo esc_html($line4); ?><span class="mobile-bridge-quote mobile-bridge-quote--close" aria-hidden="true">”</span>
					</h3>
				</div>
				<div class="mobile-bridge-copy">
					<div class="mobile-bridge-eyebrow">
						<span class="mobile-bridge-eyebrow-rule" aria-hidden="true"></span>
						<span><?php echo esc_html($eyebrow); ?></span>
					</div>
					<p class="section-lead section-lead--bridge mobile-bridge-lead"><?php echo esc_html($lead); ?></p>
					<button type="button" class="open-modal magnetic mobile-bridge-cta" data-wv-section="bridge" data-wv-lead-type="contato">
						<?php echo esc_html($cta); ?>
						<i class="ph ph-arrow-right" aria-hidden="true"></i>
					</button>
				</div>
			</div>
		</div>
	</div>
</section>
