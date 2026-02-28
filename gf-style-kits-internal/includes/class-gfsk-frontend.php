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
		add_filter( 'gform_form_tag', array( $this, 'inject_form_tag_classes' ), 10, 2 );
	}

	public function enqueue_for_form( array $form, bool $is_ajax ): void {
		unset( $is_ajax );
		$form_id = isset( $form['id'] ) ? absint( $form['id'] ) : 0;
		if ( $form_id <= 0 ) {
			return;
		}

		$config = GFSK_Admin::get_form_settings( $form_id );
		if ( empty( $config['enabled'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[GFSK] Form ' . $form_id . ' disabled; skipping enqueue.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return;
		}

		$vars = (array) $config['vars'];
		wp_enqueue_style( 'gfsk-base', GFSK_PLUGIN_URL . 'assets/css/base.css', array(), GFSK_VERSION );
		wp_enqueue_style( 'gfsk-controls', GFSK_PLUGIN_URL . 'assets/css/controls.css', array( 'gfsk-base' ), GFSK_VERSION );

		$inline_css = $this->build_css_variables( $form_id, $vars );
		$advanced   = (string) $config['advanced_css'];
		if ( '' !== $advanced ) {
			$inline_css .= "\n" . $this->scope_css( $advanced, '#gform_wrapper_' . $form_id );
		}
		wp_add_inline_style( 'gfsk-controls', $inline_css );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'GFSK enqueue form ' . $form_id . ' preset=' . (string) $config['preset'] . ' vars=' . wp_json_encode( $vars ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	private function build_css_variables( int $form_id, array $vars ): string {
		$scope = '#gform_wrapper_' . $form_id . ' .gfsk-form';
		$css   = $scope . '{';
		$css  .= '--gfsk-primary:' . esc_attr( (string) $vars['primary'] ) . ';';
		$css  .= '--gfsk-secondary:' . esc_attr( (string) $vars['secondary'] ) . ';';
		$css  .= '--gfsk-card-bg:' . esc_attr( (string) $vars['card_bg'] ) . ';';
		$css  .= '--gfsk-radius:' . absint( $vars['radius'] ) . 'px;';
		$css  .= '--gfsk-padding:' . absint( $vars['padding'] ) . 'px;';
		$css  .= '--gfsk-font-size:' . absint( $vars['font_size'] ) . 'px;';
		$css  .= '--gfsk-choice-row-gap:' . absint( $vars['choice_row_gap'] ) . 'px;';
		$css  .= '--gfsk-choice-gap:' . absint( $vars['choice_gap'] ) . 'px;';
		$css  .= '--gfsk-choice-size:' . absint( $vars['choice_size'] ) . 'px;';
		$css  .= '--gfsk-label-size:' . absint( $vars['label_font_size'] ) . 'px;';
		$css  .= '--gfsk-label-weight:' . absint( $vars['label_font_weight'] ) . ';';
		$css  .= '--gfsk-btn-size:' . absint( $vars['button_font_size'] ) . 'px;';
		$css  .= '--gfsk-btn-py:' . absint( $vars['button_padding_y'] ) . 'px;';
		$css  .= '--gfsk-btn-px:' . absint( $vars['button_padding_x'] ) . 'px;';
		$css  .= '--gfsk-btn-radius:' . absint( $vars['button_radius'] ) . 'px;';
		$css  .= '--gfsk-btn-bg:' . esc_attr( (string) $vars['button_bg'] ) . ';';
		$css  .= '--gfsk-btn-text:' . esc_attr( (string) $vars['button_text'] ) . ';';
		$css  .= '--gfsk-btn-bg-hover:' . esc_attr( (string) $vars['button_bg_hover'] ) . ';';
		$css  .= '}';
		return $css;
	}

	public function inject_form_tag_classes( string $form_tag, array $form ): string {
		$form_id = isset( $form['id'] ) ? absint( $form['id'] ) : 0;
		if ( $form_id <= 0 ) {
			return $form_tag;
		}
		$config = GFSK_Admin::get_form_settings( $form_id );
		if ( empty( $config['enabled'] ) ) {
			return $form_tag;
		}

		$class = 'gfsk-form gfsk-preset-' . sanitize_html_class( (string) $config['preset'] );
		if ( ! empty( $config['vars']['section_title_light'] ) ) {
			$class .= ' gfsk-section-title-light';
		}
		if ( ! empty( $config['vars']['section_line'] ) ) {
			$class .= ' gfsk-show-section-line';
		}

		$updated = $this->append_classes_to_html_tag( $form_tag, $class );

		if ( defined( 'GFSK_DEBUG' ) && GFSK_DEBUG ) {
			$updated = $this->append_data_attrs_to_html_tag(
				$updated,
				array(
					'data-gfsk-form-id' => (string) $form_id,
					'data-gfsk-preset'  => (string) $config['preset'],
				)
			);
		}

		return $updated;
	}

	public function inject_wrapper_classes( string $form_markup, array $form ): string {
		$form_id = isset( $form['id'] ) ? absint( $form['id'] ) : 0;
		if ( $form_id <= 0 ) {
			return $form_markup;
		}
		$config = GFSK_Admin::get_form_settings( $form_id );
		if ( empty( $config['enabled'] ) ) {
			return $form_markup;
		}

		$classes = array( 'gfsk-preset-' . sanitize_html_class( (string) $config['preset'] ) );
		if ( ! empty( $config['vars']['section_title_light'] ) ) {
			$classes[] = 'gfsk-section-title-light';
		}
		if ( ! empty( $config['vars']['section_line'] ) ) {
			$classes[] = 'gfsk-show-section-line';
		}
		$class_str = implode( ' ', $classes );
		$updated   = $this->append_classes_to_wrapper( $form_markup, $form_id, $class_str );
		$updated   = $this->append_data_attrs_to_wrapper( $updated, $form_id, array( 'data-gfsk' => 'on', 'data-gfsk-form' => (string) $form_id ) );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $updated !== $form_markup ) {
			error_log( 'GFSK wrapper class injected for form ' . $form_id ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		return $updated;
	}

	/**
	 * Append classes to the GF wrapper div matching id=gform_wrapper_{ID} regardless attribute order.
	 */
	private function append_classes_to_wrapper( string $markup, int $form_id, string $classes ): string {
		$pattern = '/<div\b[^>]*\bid=("|\')gform_wrapper_' . $form_id . '\1[^>]*>/i';

		return preg_replace_callback(
			$pattern,
			function ( array $matches ) use ( $classes ): string {
				return $this->append_classes_to_html_tag( $matches[0], $classes );
			},
			$markup,
			1
		) ?: $markup;
	}

	/**
	 * Add classes to any single HTML opening tag by merging/creating class attribute.
	 */
	private function append_classes_to_html_tag( string $tag, string $classes_to_add ): string {
		$classes_to_add = trim( $classes_to_add );
		if ( '' === $classes_to_add ) {
			return $tag;
		}

		$existing_classes = '';
		if ( preg_match( '/\bclass=("|\')([^"\']*)\1/i', $tag, $matches ) ) {
			$existing_classes = $matches[2];
		}

		$merged = array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', trim( $existing_classes . ' ' . $classes_to_add ) ) ?: array() ) );
		$merged = array_values( array_unique( $merged ) );
		$value  = implode( ' ', $merged );

		if ( preg_match( '/\bclass=("|\')([^"\']*)\1/i', $tag ) ) {
			return preg_replace( '/\bclass=("|\')([^"\']*)\1/i', 'class="' . esc_attr( $value ) . '"', $tag, 1 ) ?: $tag;
		}

		return preg_replace( '/>$/', ' class="' . esc_attr( $value ) . '">', $tag, 1 ) ?: $tag;
	}



	/**
	 * Add/replace data-* attributes on GF wrapper div by form ID.
	 */
	private function append_data_attrs_to_wrapper( string $markup, int $form_id, array $attrs ): string {
		$pattern = '/<div\b[^>]*\bid=("|\')gform_wrapper_' . $form_id . '\1[^>]*>/i';

		return preg_replace_callback(
			$pattern,
			function ( array $matches ) use ( $attrs ): string {
				return $this->append_data_attrs_to_html_tag( $matches[0], $attrs );
			},
			$markup,
			1
		) ?: $markup;
	}

	/**
	 * Add or replace arbitrary data-* attributes in opening HTML tag.
	 */
	private function append_data_attrs_to_html_tag( string $tag, array $attrs ): string {
		foreach ( $attrs as $attr => $value ) {
			$attr  = sanitize_key( (string) $attr );
			$value = esc_attr( (string) $value );
			if ( '' === $attr ) {
				continue;
			}
			if ( preg_match( '/\b' . preg_quote( $attr, '/' ) . '=("|\')([^"\']*)\1/i', $tag ) ) {
				$tag = preg_replace( '/\b' . preg_quote( $attr, '/' ) . '=("|\')([^"\']*)\1/i', $attr . '="' . $value . '"', $tag, 1 ) ?: $tag;
			} else {
				$tag = preg_replace( '/>$/', ' ' . $attr . '="' . $value . '">', $tag, 1 ) ?: $tag;
			}
		}
		return $tag;
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
				if ( '' !== $selector ) {
					$prefixed[] = $scope . ' ' . $selector;
				}
			}
			return implode( ', ', $prefixed ) . ' {';
		};
		return preg_replace_callback( '/(^|})\s*([^@}{][^{]+)\s*\{/m', $callback, $css ) ?? '';
	}
}
