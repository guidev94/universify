<?php
/**
 * WV Todoz theme bootstrap.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

define('WVTODOZ_VERSION', '1.3.0');
define('WVTODOZ_DIR', get_template_directory());
define('WVTODOZ_URI', get_template_directory_uri());

require_once WVTODOZ_DIR . '/inc/setup.php';
require_once WVTODOZ_DIR . '/inc/defaults.php';
require_once WVTODOZ_DIR . '/inc/helpers.php';
require_once WVTODOZ_DIR . '/inc/enqueue.php';
require_once WVTODOZ_DIR . '/inc/cpt.php';
require_once WVTODOZ_DIR . '/inc/acf-fields.php';
require_once WVTODOZ_DIR . '/inc/seed.php';
