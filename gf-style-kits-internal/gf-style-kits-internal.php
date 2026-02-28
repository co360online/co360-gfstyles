<?php
/**
 * Plugin Name: GF Style Kits (Internal)
 * Description: Style kits visuales por formulario para Gravity Forms (Gravity Theme).
 * Version: 1.0.0
 * Author: Internal Team
 * Requires PHP: 8.0
 * Text Domain: gf-style-kits-internal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GFSK_VERSION', '1.0.0' );
define( 'GFSK_PLUGIN_FILE', __FILE__ );
define( 'GFSK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GFSK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
if ( ! defined( 'GFSK_DEBUG' ) ) {
	define( 'GFSK_DEBUG', false );
}

require_once GFSK_PLUGIN_DIR . 'includes/class-gfsk-presets.php';
require_once GFSK_PLUGIN_DIR . 'includes/class-gfsk-wrappers.php';
require_once GFSK_PLUGIN_DIR . 'includes/class-gfsk-admin.php';
require_once GFSK_PLUGIN_DIR . 'includes/class-gfsk-frontend.php';
require_once GFSK_PLUGIN_DIR . 'includes/class-gfsk-plugin.php';

GFSK_Plugin::instance();
