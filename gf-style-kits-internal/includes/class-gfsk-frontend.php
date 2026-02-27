<?php
/**
 * Frontend CSS orchestration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSK_Frontend {

	public function register(): void {
		add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_for_form' ), 10, 2 );
		add_filter( 'gform_get_form_filter', array( $this, 'inject_wrapper_classes' ), 10, 2 );
	}

	public function enqueue_for_form( array $form, bool $is_ajax ): void {
		unset( $is_ajax );
		$form_id  = isset( $form['id'] ) ? absint( $form['id'] ) : 0;
		$settings = GFSK_Admin::get_settings();
		if ( empty( $settings['forms'][ $form_id ]['enabled'] ) ) {
			return;
		}

		$form_settings = (array) $settings['forms'][ $form_id ];
		$presets       = GFSK_Presets::merged_presets( $settings );
		$preset_slug   = isset( $form_settings['preset'] ) ? sanitize_key( (string) $form_settings['preset'] ) : 'minimal';
		$preset        = $presets[ $preset_slug ] ?? GFSK_Presets::defaults()['minimal'];

		$vars = isset( $form_settings['vars'] ) ? (array) $form_settings['vars'] : array();
		$vars = wp_parse_args( $vars, $preset['vars'] );

		wp_enqueue_style( 'gfsk-base', GFSK_PLUGIN_URL . 'assets/css/base.css', array(), GFSK_VERSION );
		wp_add_inline_style( 'gfsk-base', $this->build_css_variables( $form_id, $vars ) );

		$advanced_css = isset( $form_settings['advanced_css'] ) ? (string) $form_settings['advanced_css'] : '';
		if ( '' !== $advanced_css ) {
			wp_add_inline_style( 'gfsk-base', $this->scope_css( $advanced_css, '#gform_wrapper_' . $form_id ) );
		}
	}

	private function build_css_variables( int $form_id, array $vars ): string {
		$scope = '#gform_wrapper_' . $form_id;
		$css   = $scope . '{';
		$css  .= '--gfsk-primary:' . esc_attr( (string) $vars['primary'] ) . ';';
		$css  .= '--gfsk-secondary:' . esc_attr( (string) $vars['secondary'] ) . ';';
		$css  .= '--gfsk-card-bg:' . esc_attr( (string) $vars['card_bg'] ) . ';';
		$css  .= '--gfsk-radius:' . absint( $vars['radius'] ) . 'px;';
		$css  .= '--gfsk-padding:' . absint( $vars['padding'] ) . 'px;';
		$css  .= '--gfsk-font-size:' . absint( $vars['font_size'] ) . 'px;';
		$css  .= '}';

		return $css;
	}

	public function inject_wrapper_classes( string $form_markup, array $form ): string {
		$form_id = isset( $form['id'] ) ? absint( $form['id'] ) : 0;
		if ( $form_id <= 0 ) {
			return $form_markup;
		}
		$settings = GFSK_Admin::get_settings();
		if ( empty( $settings['forms'][ $form_id ]['enabled'] ) ) {
			return $form_markup;
		}
		$row      = (array) $settings['forms'][ $form_id ];
		$preset   = sanitize_key( (string) ( $row['preset'] ?? 'minimal' ) );
		$flags    = isset( $row['flags'] ) ? (array) $row['flags'] : array();
		$classes  = array( 'gfsk-preset-' . $preset );
		if ( ! empty( $flags['section_title_light'] ) ) {
			$classes[] = 'gfsk-section-title-light';
		}
		if ( ! empty( $flags['show_section_line'] ) ) {
			$classes[] = 'gfsk-show-section-line';
		}
		$class_str = implode( ' ', array_map( 'sanitize_html_class', $classes ) );

		return preg_replace(
			'/id=["\']gform_wrapper_' . $form_id . '["\']\s+class=["\']([^"\']*)["\']/',
			'id="gform_wrapper_' . $form_id . '" class="$1 ' . esc_attr( $class_str ) . '"',
			$form_markup,
			1
		) ?: $form_markup;
	}

	private function scope_css( string $css, string $scope ): string {
		if ( false !== strpos( $css, $scope ) ) {
			return $css;
		}

		$callback = static function ( array $matches ) use ( $scope ): string {
			$selectors = explode( ',', trim( $matches[2] ) );
			$prefixed  = array();
			foreach ( $selectors as $selector ) {
				$selector = trim( $selector );
				if ( '' === $selector ) {
					continue;
				}
				$prefixed[] = $scope . ' ' . $selector;
			}

			return implode( ', ', $prefixed ) . ' {';
		};

		return preg_replace_callback( '/(^|})\s*([^@}{][^{]+)\s*\{/m', $callback, $css ) ?? '';
	}
}
