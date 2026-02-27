<?php
/**
 * Main plugin orchestrator.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSK_Plugin {

	private static ?GFSK_Plugin $instance = null;

	private GFSK_Admin $admin;

	private GFSK_Frontend $frontend;

	private GFSK_Wrappers $wrappers;

	public static function instance(): GFSK_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'bootstrap' ) );
	}

	public function bootstrap(): void {
		$this->admin    = new GFSK_Admin();
		$this->frontend = new GFSK_Frontend();
		$this->wrappers = new GFSK_Wrappers();

		if ( ! $this->is_gravity_forms_active() ) {
			add_action( 'admin_notices', array( $this, 'render_gf_missing_notice' ) );
			return;
		}

		$this->admin->register();
		$this->frontend->register();
		$this->wrappers->register();
	}

	public function is_gravity_forms_active(): bool {
		return class_exists( 'GFForms' ) && class_exists( 'GFAPI' );
	}

	public function render_gf_missing_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'GF Style Kits (Internal) requiere Gravity Forms activo para funcionar.', 'gf-style-kits-internal' ); ?></p>
		</div>
		<?php
	}
}
