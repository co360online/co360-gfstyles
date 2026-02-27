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
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_gfsk_save_settings', array( $this, 'handle_save' ) );
		add_action( 'admin_post_gfsk_export_preset', array( $this, 'handle_export' ) );
		add_action( 'admin_post_gfsk_import_preset', array( $this, 'handle_import' ) );
	}

	public function register_menu(): void {
		$parent_slug = class_exists( 'GFForms' ) ? 'gf_edit_forms' : 'options-general.php';
		add_submenu_page(
			$parent_slug,
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
			return array(
				'forms'   => array(),
				'presets' => array(),
			);
		}

		$settings['forms']   = isset( $settings['forms'] ) && is_array( $settings['forms'] ) ? $settings['forms'] : array();
		$settings['presets'] = isset( $settings['presets'] ) && is_array( $settings['presets'] ) ? $settings['presets'] : array();

		return $settings;
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'gf-style-kits-internal' ) );
		}

		$settings      = self::get_settings();
		$presets       = GFSK_Presets::merged_presets( $settings );
		$forms         = GFAPI::get_forms();
		$selected_form = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap gfsk-wrap">
			<h1><?php esc_html_e( 'GF Style Kits', 'gf-style-kits-internal' ); ?></h1>
			<p><?php esc_html_e( 'Configura estilos visuales por formulario Gravity Forms (Gravity Theme).', 'gf-style-kits-internal' ); ?></p>

			<?php settings_errors( 'gfsk_messages' ); ?>

			<h2><?php esc_html_e( 'Seleccionar formularios', 'gf-style-kits-internal' ); ?></h2>
			<select id="gfsk-form-filter" multiple="multiple" style="min-width:320px; min-height:120px;">
				<?php foreach ( $forms as $form ) : ?>
					<option value="<?php echo esc_attr( $form['id'] ); ?>" <?php selected( $selected_form, (int) $form['id'] ); ?>>
						<?php echo esc_html( $form['id'] . ' - ' . $form['title'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="gfsk_save_settings" />
				<?php wp_nonce_field( 'gfsk_save_settings', 'gfsk_nonce' ); ?>

				<table class="widefat striped" style="margin-top:20px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Habilitar', 'gf-style-kits-internal' ); ?></th>
							<th><?php esc_html_e( 'Form ID', 'gf-style-kits-internal' ); ?></th>
							<th><?php esc_html_e( 'Título', 'gf-style-kits-internal' ); ?></th>
							<th><?php esc_html_e( 'Preset', 'gf-style-kits-internal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $forms as $form ) : ?>
							<?php $form_id = (int) $form['id']; ?>
							<?php $row = isset( $settings['forms'][ $form_id ] ) ? (array) $settings['forms'][ $form_id ] : array(); ?>
							<tr class="gfsk-row" data-form-id="<?php echo esc_attr( $form_id ); ?>">
								<td><input type="checkbox" name="forms[<?php echo esc_attr( $form_id ); ?>][enabled]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?> /></td>
								<td><?php echo esc_html( (string) $form_id ); ?></td>
								<td><?php echo esc_html( $form['title'] ); ?></td>
								<td>
									<select name="forms[<?php echo esc_attr( $form_id ); ?>][preset]">
										<?php foreach ( $presets as $slug => $preset ) : ?>
											<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $row['preset'] ?? 'minimal', $slug ); ?>><?php echo esc_html( $preset['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr class="gfsk-panel" data-form-id="<?php echo esc_attr( $form_id ); ?>">
								<td colspan="4">
									<?php $this->render_form_panel( $form, $row, $presets ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Guardar', 'gf-style-kits-internal' ); ?></button></p>
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

	private function render_form_panel( array $form, array $row, array $presets ): void {
		$form_id  = (int) $form['id'];
		$vars     = isset( $row['vars'] ) ? (array) $row['vars'] : array();
		$flags    = isset( $row['flags'] ) ? (array) $row['flags'] : array();
		$wrappers = isset( $row['wrappers'] ) && is_array( $row['wrappers'] ) ? $row['wrappers'] : array();
		$preset   = isset( $row['preset'] ) ? (string) $row['preset'] : 'minimal';
		$vars     = wp_parse_args( $vars, $presets[ $preset ]['vars'] ?? GFSK_Presets::defaults()['minimal']['vars'] );
		$flags    = wp_parse_args( $flags, $presets[ $preset ]['flags'] ?? array() );
		?>
		<div class="gfsk-tabs">
			<h4><?php esc_html_e( 'Colores', 'gf-style-kits-internal' ); ?></h4>
			<p>
				<label><?php esc_html_e( 'Color principal', 'gf-style-kits-internal' ); ?></label>
				<input class="gfsk-color" type="text" name="forms[<?php echo esc_attr( $form_id ); ?>][vars][primary]" value="<?php echo esc_attr( $vars['primary'] ); ?>" />
			</p>
			<p>
				<label><?php esc_html_e( 'Color secundario', 'gf-style-kits-internal' ); ?></label>
				<input class="gfsk-color" type="text" name="forms[<?php echo esc_attr( $form_id ); ?>][vars][secondary]" value="<?php echo esc_attr( $vars['secondary'] ); ?>" />
			</p>
			<p>
				<label><?php esc_html_e( 'Fondo cards', 'gf-style-kits-internal' ); ?></label>
				<input class="gfsk-color" type="text" name="forms[<?php echo esc_attr( $form_id ); ?>][vars][card_bg]" value="<?php echo esc_attr( $vars['card_bg'] ); ?>" />
			</p>

			<h4><?php esc_html_e( 'Botones / Inputs', 'gf-style-kits-internal' ); ?></h4>
			<p><label><?php esc_html_e( 'Radio de borde (px)', 'gf-style-kits-internal' ); ?></label> <input type="number" min="0" max="60" name="forms[<?php echo esc_attr( $form_id ); ?>][vars][radius]" value="<?php echo esc_attr( (string) $vars['radius'] ); ?>" /></p>
			<p><label><?php esc_html_e( 'Padding estándar (px)', 'gf-style-kits-internal' ); ?></label> <input type="number" min="0" max="80" name="forms[<?php echo esc_attr( $form_id ); ?>][vars][padding]" value="<?php echo esc_attr( (string) $vars['padding'] ); ?>" /></p>
			<p><label><?php esc_html_e( 'Tipografía base (px)', 'gf-style-kits-internal' ); ?></label> <input type="number" min="10" max="24" name="forms[<?php echo esc_attr( $form_id ); ?>][vars][font_size]" value="<?php echo esc_attr( (string) $vars['font_size'] ); ?>" /></p>
			<p>
				<label><input type="checkbox" name="forms[<?php echo esc_attr( $form_id ); ?>][flags][section_title_light]" value="1" <?php checked( ! empty( $flags['section_title_light'] ) ); ?> /> <?php esc_html_e( 'Título de sección claro', 'gf-style-kits-internal' ); ?></label><br />
				<label><input type="checkbox" name="forms[<?php echo esc_attr( $form_id ); ?>][flags][show_section_line]" value="1" <?php checked( ! empty( $flags['show_section_line'] ) ); ?> /> <?php esc_html_e( 'Mostrar línea decorativa en secciones', 'gf-style-kits-internal' ); ?></label>
			</p>

			<h4><?php esc_html_e( 'Wrappers', 'gf-style-kits-internal' ); ?></h4>
			<?php for ( $i = 0; $i < 3; $i++ ) : ?>
				<?php $wrapper = isset( $wrappers[ $i ] ) ? (array) $wrappers[ $i ] : array(); ?>
				<p>
					<label><input type="checkbox" name="forms[<?php echo esc_attr( $form_id ); ?>][wrappers][<?php echo esc_attr( $i ); ?>][enabled]" value="1" <?php checked( ! empty( $wrapper['enabled'] ) ); ?> /> <?php esc_html_e( 'Activo', 'gf-style-kits-internal' ); ?></label>
					<input type="number" placeholder="start" name="forms[<?php echo esc_attr( $form_id ); ?>][wrappers][<?php echo esc_attr( $i ); ?>][start_field_id]" value="<?php echo esc_attr( (string) ( $wrapper['start_field_id'] ?? '' ) ); ?>" />
					<input type="number" placeholder="end" name="forms[<?php echo esc_attr( $form_id ); ?>][wrappers][<?php echo esc_attr( $i ); ?>][end_field_id]" value="<?php echo esc_attr( (string) ( $wrapper['end_field_id'] ?? '' ) ); ?>" />
					<input type="text" placeholder="class" name="forms[<?php echo esc_attr( $form_id ); ?>][wrappers][<?php echo esc_attr( $i ); ?>][class]" value="<?php echo esc_attr( (string) ( $wrapper['class'] ?? '' ) ); ?>" />
					<label><input type="checkbox" name="forms[<?php echo esc_attr( $form_id ); ?>][wrappers][<?php echo esc_attr( $i ); ?>][force_close_in_footer]" value="1" <?php checked( ! empty( $wrapper['force_close_in_footer'] ) ); ?> /> force_close_in_footer</label>
				</p>
			<?php endfor; ?>

			<h4><?php esc_html_e( 'Avanzado', 'gf-style-kits-internal' ); ?></h4>
			<p><textarea name="forms[<?php echo esc_attr( $form_id ); ?>][advanced_css]" rows="4" style="width:100%;"><?php echo esc_textarea( (string) ( $row['advanced_css'] ?? '' ) ); ?></textarea></p>
			<p><code>[gravityform id="<?php echo esc_html( (string) $form_id ); ?>" title="false" description="false"]</code></p>
		</div>
		<?php
	}

	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'gf-style-kits-internal' ) );
		}
		check_admin_referer( 'gfsk_save_settings', 'gfsk_nonce' );

		$forms_raw     = isset( $_POST['forms'] ) ? (array) wp_unslash( $_POST['forms'] ) : array();
		$current       = self::get_settings();
		$current_forms = $current['forms'];
		$new_forms     = array();

		foreach ( $forms_raw as $form_id => $row ) {
			$id = absint( $form_id );
			if ( $id <= 0 || ! is_array( $row ) ) {
				continue;
			}
			$vars = self::sanitize_vars( isset( $row['vars'] ) ? (array) $row['vars'] : array() );
			if ( empty( $vars['primary'] ) || empty( $vars['secondary'] ) || empty( $vars['card_bg'] ) ) {
				add_settings_error( 'gfsk_messages', 'gfsk_invalid_colors_' . $id, sprintf( 'Form %d: color inválido.', $id ), 'error' );
				continue;
			}

			$new_forms[ $id ] = array(
				'enabled'      => ! empty( $row['enabled'] ),
				'preset'       => sanitize_key( (string) ( $row['preset'] ?? 'minimal' ) ),
				'vars'         => $vars,
				'flags'        => self::sanitize_flags( isset( $row['flags'] ) ? (array) $row['flags'] : array() ),
				'wrappers'     => self::sanitize_wrappers( isset( $row['wrappers'] ) ? (array) $row['wrappers'] : array() ),
				'advanced_css' => self::sanitize_advanced_css( (string) ( $row['advanced_css'] ?? '' ) ),
			);
		}

		$current['forms'] = array_replace( $current_forms, $new_forms );
		update_option( self::OPTION_KEY, $current );
		add_settings_error( 'gfsk_messages', 'gfsk_saved', 'Ajustes guardados.', 'updated' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=gf-style-kits' ) );
		exit;
	}

	public function handle_export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'gf-style-kits-internal' ) );
		}
		check_admin_referer( 'gfsk_export_preset', 'gfsk_nonce' );

		$slug     = isset( $_POST['preset_slug'] ) ? sanitize_key( (string) wp_unslash( $_POST['preset_slug'] ) ) : '';
		$settings = self::get_settings();
		$presets  = GFSK_Presets::merged_presets( $settings );

		if ( empty( $slug ) || ! isset( $presets[ $slug ] ) ) {
			wp_die( esc_html__( 'Preset no encontrado.', 'gf-style-kits-internal' ) );
		}

		$data = wp_json_encode(
			array(
				$slug => $presets[ $slug ],
			),
			JSON_PRETTY_PRINT
		);

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="gfsk-preset-' . $slug . '.json"' );
		echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

		$tmp_name = isset( $_FILES['preset_file']['tmp_name'] ) ? (string) $_FILES['preset_file']['tmp_name'] : '';
		$content  = file_get_contents( $tmp_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data    = json_decode( (string) $content, true );
		if ( ! is_array( $data ) ) {
			add_settings_error( 'gfsk_messages', 'gfsk_bad_json', 'JSON inválido.', 'error' );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( admin_url( 'admin.php?page=gf-style-kits' ) );
			exit;
		}

		$settings           = self::get_settings();
		$settings['presets'] = array_merge( $settings['presets'], GFSK_Presets::sanitize_preset_payload( $data ) );
		update_option( self::OPTION_KEY, $settings );
		add_settings_error( 'gfsk_messages', 'gfsk_imported', 'Preset importado.', 'updated' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=gf-style-kits' ) );
		exit;
	}

	public static function sanitize_vars( array $vars ): array {
		$primary   = self::sanitize_hex_or_empty( (string) ( $vars['primary'] ?? '' ) );
		$secondary = self::sanitize_hex_or_empty( (string) ( $vars['secondary'] ?? '' ) );
		$card_bg   = self::sanitize_hex_or_empty( (string) ( $vars['card_bg'] ?? '' ) );

		return array(
			'primary'   => $primary,
			'secondary' => $secondary,
			'card_bg'   => $card_bg,
			'radius'    => self::sanitize_int( $vars['radius'] ?? 12, 0, 60 ),
			'padding'   => self::sanitize_int( $vars['padding'] ?? 18, 0, 100 ),
			'font_size' => self::sanitize_int( $vars['font_size'] ?? 15, 10, 32 ),
		);
	}

	public static function sanitize_flags( array $flags ): array {
		return array(
			'section_title_light' => ! empty( $flags['section_title_light'] ),
			'show_section_line'   => ! empty( $flags['show_section_line'] ),
		);
	}

	public static function sanitize_wrappers( array $wrappers ): array {
		$out = array();
		foreach ( $wrappers as $wrapper ) {
			if ( ! is_array( $wrapper ) ) {
				continue;
			}
			$out[] = array(
				'enabled'               => ! empty( $wrapper['enabled'] ),
				'start_field_id'        => self::sanitize_int( $wrapper['start_field_id'] ?? 0, 0, 9999 ),
				'end_field_id'          => self::sanitize_int( $wrapper['end_field_id'] ?? 0, 0, 9999 ),
				'class'                 => sanitize_html_class( (string) ( $wrapper['class'] ?? '' ) ),
				'force_close_in_footer' => ! empty( $wrapper['force_close_in_footer'] ),
			);
		}

		return $out;
	}

	private static function sanitize_hex_or_empty( string $value ): string {
		$hex = sanitize_hex_color( trim( $value ) );
		return $hex ? $hex : '';
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

	private static function sanitize_advanced_css( string $css ): string {
		$css = wp_strip_all_tags( $css );
		return trim( preg_replace( '/[^\w\s\-#.,:;{}()@%>+~*\[\]=\"\'\\\/]/', '', $css ) ?? '' );
	}
}
