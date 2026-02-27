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

		$inline_css = $this->build_css_variables( $form_id, $vars );
		$advanced   = (string) $config['advanced_css'];
		if ( '' !== $advanced ) {
			$inline_css .= "\n" . $this->scope_css( $advanced, '#gform_wrapper_' . $form_id );
		}
		wp_add_inline_style( 'gfsk-base', $inline_css );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[GFSK] Enqueued styles for form ' . $form_id . ' preset=' . (string) $config['preset'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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

		if ( preg_match( '/class=["\']([^"\']*)["\']/', $form_tag ) ) {
			return preg_replace( '/class=["\']([^"\']*)["\']/', 'class="$1 ' . esc_attr( $class ) . '"', $form_tag, 1 ) ?: $form_tag;
		}
		return str_replace( '<form ', '<form class="' . esc_attr( $class ) . '" ', $form_tag );
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

		$pattern = '/(<div[^>]*id=["\']gform_wrapper_' . $form_id . '["\'][^>]*)(>)/i';
		return preg_replace_callback(
			$pattern,
			static function ( array $matches ) use ( $class_str ): string {
				$tag = $matches[1];
				if ( preg_match( '/class=["\']([^"\']*)["\']/', $tag ) ) {
					$tag = preg_replace( '/class=["\']([^"\']*)["\']/', 'class="$1 ' . esc_attr( $class_str ) . '"', $tag, 1 ) ?: $tag;
				} else {
					$tag .= ' class="' . esc_attr( $class_str ) . '"';
				}
				return $tag . $matches[2];
			},
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
				if ( '' !== $selector ) {
					$prefixed[] = $scope . ' ' . $selector;
				}
			}
			return implode( ', ', $prefixed ) . ' {';
		};
		return preg_replace_callback( '/(^|})\s*([^@}{][^{]+)\s*\{/m', $callback, $css ) ?? '';
	}
}
