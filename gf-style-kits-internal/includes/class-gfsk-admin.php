<?php
/**
 * Admin settings UI.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSK_Admin {

	public const OPTION_KEY = 'gfsk_settings';

	public function register(): void {
		/*
		 * Register after GF menu is available; keeps submenu under Forms and avoids bad URLs.
		 */
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_gfsk_save_settings', array( $this, 'handle_save' ) );
		add_action( 'admin_post_gfsk_export_preset', array( $this, 'handle_export' ) );
		add_action( 'admin_post_gfsk_import_preset', array( $this, 'handle_import' ) );
	}

	public function register_menu(): void {
		if ( ! class_exists( 'GFForms' ) ) {
			return;
		}

		add_submenu_page(
			'gf_edit_forms',
			__( 'GF Style Kits', 'gf-style-kits-internal' ),
			__( 'GF Style Kits', 'gf-style-kits-internal' ),
			'manage_options',
			'gf-style-kits',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, 'gf-style-kits' ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'gfsk-admin', GFSK_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), GFSK_VERSION, true );
	}

	public static function get_settings(): array {
		$settings = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['forms']   = isset( $settings['forms'] ) && is_array( $settings['forms'] ) ? $settings['forms'] : array();
		$settings['presets'] = isset( $settings['presets'] ) && is_array( $settings['presets'] ) ? $settings['presets'] : array();

		return $settings;
	}

	public static function get_form_settings( int $form_id, array $settings = array() ): array {
		if ( empty( $settings ) ) {
			$settings = self::get_settings();
		}
		$presets     = GFSK_Presets::merged_presets( $settings );
		$defaults    = GFSK_Presets::defaults()['minimal'];
		$current     = isset( $settings['forms'][ $form_id ] ) ? (array) $settings['forms'][ $form_id ] : array();
		$preset_slug = sanitize_key( (string) ( $current['preset'] ?? 'minimal' ) );
		$preset_data = $presets[ $preset_slug ] ?? $defaults;
		$vars        = isset( $current['vars'] ) ? (array) $current['vars'] : array();

		return array(
			'enabled'      => ! empty( $current['enabled'] ),
			'preset'       => $preset_slug,
			'vars'         => wp_parse_args( $vars, $preset_data['vars'] ),
			'wrappers'     => isset( $current['wrappers'] ) && is_array( $current['wrappers'] ) ? $current['wrappers'] : array(),
			'advanced_css' => (string) ( $current['advanced_css'] ?? '' ),
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'gf-style-kits-internal' ) );
		}

		$settings = self::get_settings();
		$presets  = GFSK_Presets::merged_presets( $settings );
		$forms    = class_exists( 'GFAPI' ) ? GFAPI::get_forms() : array();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'GF Style Kits', 'gf-style-kits-internal' ); ?></h1>
			<p><?php esc_html_e( 'Selecciona uno o más formularios para editar su Style Kit.', 'gf-style-kits-internal' ); ?></p>

			<?php settings_errors( 'gfsk_messages' ); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="gfsk_save_settings" />
				<?php wp_nonce_field( 'gfsk_save_settings', 'gfsk_nonce' ); ?>

				<div class="postbox" style="padding:12px 16px; margin-bottom:16px;">
					<h2 class="hndle" style="margin:0 0 12px;"><span><?php esc_html_e( 'Selector de formularios', 'gf-style-kits-internal' ); ?></span></h2>
					<select id="gfsk-form-filter" class="regular-text" multiple="multiple" style="min-width:380px; min-height:120px;">
						<?php foreach ( $forms as $form ) : ?>
							<option value="<?php echo esc_attr( (string) $form['id'] ); ?>"><?php echo esc_html( sprintf( '#%d — %s', (int) $form['id'], $form['title'] ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'No se mostrarán paneles hasta seleccionar al menos un formulario.', 'gf-style-kits-internal' ); ?></p>
				</div>

				<div id="gfsk-empty-state" class="notice notice-info inline"><p><?php esc_html_e( 'Selecciona un formulario para editar.', 'gf-style-kits-internal' ); ?></p></div>

				<div id="gfsk-form-panels">
					<?php foreach ( $forms as $form ) : ?>
						<?php
						$form_id      = (int) $form['id'];
						$form_setting = self::get_form_settings( $form_id, $settings );
						?>
						<div class="postbox gfsk-panel" data-form-id="<?php echo esc_attr( (string) $form_id ); ?>" style="display:none; padding: 8px 16px 16px;">
							<h2 class="hndle"><span><?php echo esc_html( sprintf( '%s (Form ID %d)', $form['title'], $form_id ) ); ?></span></h2>
							<div class="inside">
								<?php $this->render_form_panel( $form_id, $form_setting, $presets ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<p><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Guardar cambios', 'gf-style-kits-internal' ); ?></button></p>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Presets (Exportar/Importar)', 'gf-style-kits-internal' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:12px;">
				<input type="hidden" name="action" value="gfsk_export_preset" />
				<?php wp_nonce_field( 'gfsk_export_preset', 'gfsk_nonce' ); ?>
				<select name="preset_slug" required>
					<?php foreach ( $presets as $slug => $preset ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $preset['label'] . ' (' . $slug . ')' ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="button"><?php esc_html_e( 'Exportar preset', 'gf-style-kits-internal' ); ?></button>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="display:inline-block;">
				<input type="hidden" name="action" value="gfsk_import_preset" />
				<?php wp_nonce_field( 'gfsk_import_preset', 'gfsk_nonce' ); ?>
				<input type="file" name="preset_file" accept="application/json" required />
				<button type="submit" class="button"><?php esc_html_e( 'Importar preset', 'gf-style-kits-internal' ); ?></button>
			</form>
		</div>
		<?php
	}

	private function render_form_panel( int $form_id, array $row, array $presets ): void {
		$vars     = (array) $row['vars'];
		$wrappers = (array) $row['wrappers'];
		?>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Habilitar estilos', 'gf-style-kits-internal' ); ?></th>
				<td><label><input type="checkbox" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][enabled]" value="1" <?php checked( $row['enabled'] ); ?> /> <?php esc_html_e( 'Activar para este formulario', 'gf-style-kits-internal' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Preset base', 'gf-style-kits-internal' ); ?></th>
				<td>
					<select name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][preset]">
						<?php foreach ( $presets as $slug => $preset ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $row['preset'], $slug ); ?>><?php echo esc_html( $preset['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Colores', 'gf-style-kits-internal' ); ?></th>
				<td>
					<input class="gfsk-color" type="text" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][vars][primary]" value="<?php echo esc_attr( (string) $vars['primary'] ); ?>" />
					<input class="gfsk-color" type="text" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][vars][secondary]" value="<?php echo esc_attr( (string) $vars['secondary'] ); ?>" />
					<input class="gfsk-color" type="text" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][vars][card_bg]" value="<?php echo esc_attr( (string) $vars['card_bg'] ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Escala y tipografía', 'gf-style-kits-internal' ); ?></th>
				<td>
					<input type="number" min="0" max="50" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][vars][radius]" value="<?php echo esc_attr( (string) $vars['radius'] ); ?>" /> px
					<input type="number" min="0" max="60" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][vars][padding]" value="<?php echo esc_attr( (string) $vars['padding'] ); ?>" /> px
					<input type="number" min="10" max="22" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][vars][font_size]" value="<?php echo esc_attr( (string) $vars['font_size'] ); ?>" /> px
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Secciones', 'gf-style-kits-internal' ); ?></th>
				<td>
					<label><input type="checkbox" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][vars][section_title_light]" value="1" <?php checked( ! empty( $vars['section_title_light'] ) ); ?> /> <?php esc_html_e( 'Título claro', 'gf-style-kits-internal' ); ?></label>
					<label style="margin-left:16px;"><input type="checkbox" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][vars][section_line]" value="1" <?php checked( ! empty( $vars['section_line'] ) ); ?> /> <?php esc_html_e( 'Línea decorativa', 'gf-style-kits-internal' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Wrappers', 'gf-style-kits-internal' ); ?></th>
				<td>
					<table class="widefat striped" style="max-width:860px;">
						<thead><tr><th>On</th><th>Start</th><th>End</th><th>Class</th><th>Force close</th></tr></thead>
						<tbody>
							<?php for ( $i = 0; $i < 3; $i++ ) : ?>
								<?php $w = isset( $wrappers[ $i ] ) ? (array) $wrappers[ $i ] : array(); ?>
								<tr>
									<td><input type="checkbox" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][wrappers][<?php echo esc_attr( (string) $i ); ?>][enabled]" value="1" <?php checked( ! empty( $w['enabled'] ) ); ?> /></td>
									<td><input type="number" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][wrappers][<?php echo esc_attr( (string) $i ); ?>][start]" value="<?php echo esc_attr( (string) ( $w['start'] ?? $w['start_field_id'] ?? '' ) ); ?>" /></td>
									<td><input type="number" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][wrappers][<?php echo esc_attr( (string) $i ); ?>][end]" value="<?php echo esc_attr( (string) ( $w['end'] ?? $w['end_field_id'] ?? '' ) ); ?>" /></td>
									<td><input type="text" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][wrappers][<?php echo esc_attr( (string) $i ); ?>][class]" value="<?php echo esc_attr( (string) ( $w['class'] ?? '' ) ); ?>" /></td>
									<td><input type="checkbox" name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][wrappers][<?php echo esc_attr( (string) $i ); ?>][force_close_in_footer]" value="1" <?php checked( ! empty( $w['force_close_in_footer'] ) ); ?> /></td>
								</tr>
							<?php endfor; ?>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Advanced CSS', 'gf-style-kits-internal' ); ?></th>
				<td><textarea name="gfsk_settings[forms][<?php echo esc_attr( (string) $form_id ); ?>][advanced_css]" rows="5" class="large-text code"><?php echo esc_textarea( (string) $row['advanced_css'] ); ?></textarea></td>
			</tr>
			</tbody>
		</table>
		<details>
			<summary><?php esc_html_e( 'Debug', 'gf-style-kits-internal' ); ?></summary>
			<pre><?php echo esc_html( wp_json_encode( array( 'enabled' => $row['enabled'], 'preset' => $row['preset'], 'vars' => $vars ), JSON_PRETTY_PRINT ) ); ?></pre>
		</details>
		<?php
	}

	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'gf-style-kits-internal' ) );
		}
		check_admin_referer( 'gfsk_save_settings', 'gfsk_nonce' );

		$payload  = isset( $_POST['gfsk_settings'] ) ? (array) wp_unslash( $_POST['gfsk_settings'] ) : array();
		$forms_in = isset( $payload['forms'] ) && is_array( $payload['forms'] ) ? $payload['forms'] : array();

		$current          = self::get_settings();
		$current['forms'] = $this->sanitize_forms_payload( $forms_in );
		update_option( self::OPTION_KEY, $current );

		add_settings_error( 'gfsk_messages', 'gfsk_saved', __( 'Ajustes guardados correctamente.', 'gf-style-kits-internal' ), 'updated' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=gf-style-kits' ) );
		exit;
	}

	private function sanitize_forms_payload( array $forms_in ): array {
		$sanitized = array();

		foreach ( $forms_in as $form_id => $row ) {
			$id = absint( $form_id );
			if ( $id <= 0 || ! is_array( $row ) ) {
				continue;
			}

			$vars = self::sanitize_vars( isset( $row['vars'] ) ? (array) $row['vars'] : array() );
			if ( '' === $vars['primary'] || '' === $vars['secondary'] || '' === $vars['card_bg'] ) {
				add_settings_error( 'gfsk_messages', 'gfsk_bad_color_' . $id, sprintf( 'Form %d: color inválido.', $id ), 'error' );
				continue;
			}

			$sanitized[ $id ] = array(
				'enabled'      => ! empty( $row['enabled'] ),
				'preset'       => sanitize_key( (string) ( $row['preset'] ?? 'minimal' ) ),
				'vars'         => $vars,
				'wrappers'     => self::sanitize_wrappers( isset( $row['wrappers'] ) ? (array) $row['wrappers'] : array() ),
				'advanced_css' => sanitize_textarea_field( (string) ( $row['advanced_css'] ?? '' ) ),
			);
		}

		return $sanitized;
	}

	public function handle_export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'gf-style-kits-internal' ) );
		}
		check_admin_referer( 'gfsk_export_preset', 'gfsk_nonce' );
		$slug     = isset( $_POST['preset_slug'] ) ? sanitize_key( (string) wp_unslash( $_POST['preset_slug'] ) ) : '';
		$settings = self::get_settings();
		$presets  = GFSK_Presets::merged_presets( $settings );
		if ( ! isset( $presets[ $slug ] ) ) {
			wp_die( esc_html__( 'Preset no encontrado.', 'gf-style-kits-internal' ) );
		}

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="gfsk-preset-' . $slug . '.json"' );
		echo wp_json_encode( array( $slug => $presets[ $slug ] ), JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'gf-style-kits-internal' ) );
		}
		check_admin_referer( 'gfsk_import_preset', 'gfsk_nonce' );
		if ( empty( $_FILES['preset_file']['tmp_name'] ) ) {
			add_settings_error( 'gfsk_messages', 'gfsk_no_file', 'No se seleccionó archivo JSON.', 'error' );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( admin_url( 'admin.php?page=gf-style-kits' ) );
			exit;
		}
		$tmp_name = (string) $_FILES['preset_file']['tmp_name'];
		$content  = file_get_contents( $tmp_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data     = json_decode( (string) $content, true );
		if ( ! is_array( $data ) ) {
			add_settings_error( 'gfsk_messages', 'gfsk_bad_json', 'JSON inválido.', 'error' );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( admin_url( 'admin.php?page=gf-style-kits' ) );
			exit;
		}
		$settings            = self::get_settings();
		$settings['presets'] = array_merge( $settings['presets'], GFSK_Presets::sanitize_preset_payload( $data ) );
		update_option( self::OPTION_KEY, $settings );

		add_settings_error( 'gfsk_messages', 'gfsk_import_ok', 'Preset importado.', 'updated' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=gf-style-kits' ) );
		exit;
	}

	public static function sanitize_vars( array $vars ): array {
		return array(
			'primary'             => self::normalize_hex_color( (string) ( $vars['primary'] ?? '' ) ),
			'secondary'           => self::normalize_hex_color( (string) ( $vars['secondary'] ?? '' ) ),
			'card_bg'             => self::normalize_hex_color( (string) ( $vars['card_bg'] ?? '' ) ),
			'radius'              => self::sanitize_int( $vars['radius'] ?? 10, 0, 50 ),
			'padding'             => self::sanitize_int( $vars['padding'] ?? 16, 0, 60 ),
			'font_size'           => self::sanitize_int( $vars['font_size'] ?? 15, 10, 22 ),
			'section_title_light' => ! empty( $vars['section_title_light'] ),
			'section_line'        => ! empty( $vars['section_line'] ),
		);
	}

	public static function sanitize_wrappers( array $wrappers ): array {
		$out = array();
		foreach ( $wrappers as $wrapper ) {
			if ( ! is_array( $wrapper ) ) {
				continue;
			}
			$classes = preg_split( '/\s+/', (string) ( $wrapper['class'] ?? '' ) ) ?: array();
			$classes = array_filter( array_map( 'sanitize_html_class', $classes ) );
			$out[]   = array(
				'enabled'               => ! empty( $wrapper['enabled'] ),
				'start'                 => self::sanitize_int( $wrapper['start'] ?? $wrapper['start_field_id'] ?? 0, 0, 9999 ),
				'end'                   => self::sanitize_int( $wrapper['end'] ?? $wrapper['end_field_id'] ?? 0, 0, 9999 ),
				'class'                 => implode( ' ', $classes ),
				'force_close_in_footer' => ! empty( $wrapper['force_close_in_footer'] ),
			);
		}
		return $out;
	}

	private static function sanitize_int( $value, int $min, int $max ): int {
		$number = absint( $value );
		if ( $number < $min ) {
			$number = $min;
		}
		if ( $number > $max ) {
			$number = $max;
		}
		return $number;
	}

	private static function normalize_hex_color( string $value ): string {
		$value = trim( $value );
		if ( preg_match( '/^#([a-fA-F0-9]{3})$/', $value, $matches ) ) {
			$r = $matches[1][0];
			$g = $matches[1][1];
			$b = $matches[1][2];
			return '#' . strtolower( $r . $r . $g . $g . $b . $b );
		}
		if ( preg_match( '/^#([a-fA-F0-9]{6})$/', $value ) ) {
			return strtolower( $value );
		}
		return '';
	}
}
