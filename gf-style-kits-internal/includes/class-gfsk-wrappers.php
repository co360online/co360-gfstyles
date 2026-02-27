<?php
/**
 * Wrapper ranges around fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSK_Wrappers {

	/**
	 * Runtime state keyed by form ID.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $states = array();

	public function register(): void {
		add_filter( 'gform_pre_render', array( $this, 'prepare_form_state' ) );
	}

	public function prepare_form_state( array $form ): array {
		$form_id  = isset( $form['id'] ) ? absint( $form['id'] ) : 0;
		$settings = GFSK_Admin::get_settings();
		if ( $form_id <= 0 || empty( $settings['forms'][ $form_id ]['enabled'] ) ) {
			return $form;
		}

		$wrappers = isset( $settings['forms'][ $form_id ]['wrappers'] ) ? (array) $settings['forms'][ $form_id ]['wrappers'] : array();
		$indexes  = $this->build_field_index_map( $form );
		$valid    = $this->validate_wrappers( $wrappers, $indexes );

		$this->states[ $form_id ] = array(
			'wrappers'     => $valid,
			'open_wrappers' => array(),
			'last_field_id' => $this->get_last_field_id( $form ),
		);

		add_filter( 'gform_field_container_' . $form_id, array( $this, 'inject_wrapper_markup' ), 10, 6 );

		return $form;
	}

	private function build_field_index_map( array $form ): array {
		$map = array();
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return $map;
		}

		foreach ( $form['fields'] as $index => $field ) {
			if ( isset( $field->id ) ) {
				$map[ (int) $field->id ] = $index;
			}
		}

		return $map;
	}

	private function get_last_field_id( array $form ): int {
		$last_id = 0;
		if ( ! empty( $form['fields'] ) && is_array( $form['fields'] ) ) {
			$last = end( $form['fields'] );
			if ( is_object( $last ) && isset( $last->id ) ) {
				$last_id = (int) $last->id;
			}
		}

		return $last_id;
	}

	private function validate_wrappers( array $wrappers, array $indexes ): array {
		$valid = array();

		foreach ( $wrappers as $wrapper ) {
			if ( empty( $wrapper['enabled'] ) ) {
				continue;
			}
			$start = absint( $wrapper['start'] ?? $wrapper['start_field_id'] ?? 0 );
			$end   = absint( $wrapper['end'] ?? $wrapper['end_field_id'] ?? 0 );
			$class_parts = preg_split( '/\s+/', (string) ( $wrapper['class'] ?? '' ) ) ?: array();
			$class_parts = array_filter( array_map( 'sanitize_html_class', $class_parts ) );
			$class       = implode( ' ', $class_parts );

			if ( $start <= 0 || $end <= 0 || '' === $class ) {
				continue;
			}
			if ( ! isset( $indexes[ $start ], $indexes[ $end ] ) ) {
				continue;
			}
			if ( $indexes[ $start ] > $indexes[ $end ] ) {
				continue;
			}

			$valid[] = array(
				'start'                 => $start,
				'end'                   => $end,
				'class'                 => $class,
				'force_close_in_footer' => ! empty( $wrapper['force_close_in_footer'] ),
			);
		}

		return $valid;
	}

	public function inject_wrapper_markup( string $field_container, $field, array $form, string $css_class, string $style, string $field_content ): string {
		unset( $css_class, $style, $field_content );
		$form_id  = isset( $form['id'] ) ? absint( $form['id'] ) : 0;
		$field_id = isset( $field->id ) ? (int) $field->id : 0;

		if ( $form_id <= 0 || $field_id <= 0 || empty( $this->states[ $form_id ]['wrappers'] ) ) {
			return $field_container;
		}

		$open_markup  = '';
		$close_markup = '';

		foreach ( $this->states[ $form_id ]['wrappers'] as $index => $wrapper ) {
			if ( $field_id === (int) $wrapper['start'] ) {
				$open_markup .= '<div class="' . esc_attr( $wrapper['class'] ) . '">';
				$this->states[ $form_id ]['open_wrappers'][ $index ] = $wrapper;
			}
		}

		foreach ( $this->states[ $form_id ]['open_wrappers'] as $index => $wrapper ) {
			if ( $field_id === (int) $wrapper['end'] ) {
				$close_markup .= '</div>';
				unset( $this->states[ $form_id ]['open_wrappers'][ $index ] );
			}
		}

		if ( $field_id === (int) $this->states[ $form_id ]['last_field_id'] && ! empty( $this->states[ $form_id ]['open_wrappers'] ) ) {
			foreach ( $this->states[ $form_id ]['open_wrappers'] as $index => $wrapper ) {
				if ( ! empty( $wrapper['force_close_in_footer'] ) ) {
					$close_markup .= '</div>';
					unset( $this->states[ $form_id ]['open_wrappers'][ $index ] );
				}
			}
		}

		return $open_markup . $field_container . $close_markup;
	}
}
