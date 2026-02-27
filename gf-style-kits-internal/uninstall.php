<?php
/**
 * Optional uninstall cleanup.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Descomentar para borrar settings al desinstalar.
// delete_option( 'gfsk_settings' );
