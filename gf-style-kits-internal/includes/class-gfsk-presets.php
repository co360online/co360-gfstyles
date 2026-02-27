<?php
/**
 * Presets helper.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSK_Presets {

	public static function defaults(): array {
		return array(
			'minimal'   => array(
				'label' => 'Minimal',
				'vars'  => array(
					'primary'   => '#2f6cdb',
					'secondary' => '#1d4ea1',
					'card_bg'   => '#f8fafc',
					'radius'    => 8,
					'padding'   => 16,
					'font_size' => 15,
				),
				'flags' => array(
					'section_title_light' => false,
					'show_section_line'   => false,
				),
			),
			'card'      => array(
				'label' => 'Card',
				'vars'  => array(
					'primary'   => '#608269',
					'secondary' => '#4d6955',
					'card_bg'   => '#f6f8f7',
					'radius'    => 14,
					'padding'   => 22,
					'font_size' => 15,
				),
				'flags' => array(
					'section_title_light' => true,
					'show_section_line'   => true,
				),
			),
			'factura'   => array(
				'label' => 'Factura',
				'vars'  => array(
					'primary'   => '#1f7a8c',
					'secondary' => '#144d57',
					'card_bg'   => '#f3f7f8',
					'radius'    => 12,
					'padding'   => 18,
					'font_size' => 15,
				),
				'flags' => array(
					'section_title_light' => true,
					'show_section_line'   => true,
				),
			),
			'corporate' => array(
				'label' => 'Corporate',
				'vars'  => array(
					'primary'   => '#1f2937',
					'secondary' => '#4b5563',
					'card_bg'   => '#f3f4f6',
					'radius'    => 6,
					'padding'   => 14,
					'font_size' => 14,
				),
				'flags' => array(
					'section_title_light' => false,
					'show_section_line'   => true,
				),
			),
		);
	}

	public static function merged_presets( array $settings ): array {
		$defaults = self::defaults();
		$custom   = isset( $settings['presets'] ) && is_array( $settings['presets'] ) ? $settings['presets'] : array();

		return array_merge( $defaults, $custom );
	}

	public static function sanitize_preset_payload( array $payload ): array {
		$sanitized = array();

		foreach ( $payload as $slug => $preset ) {
			$key = sanitize_key( (string) $slug );
			if ( empty( $key ) || ! is_array( $preset ) ) {
				continue;
			}
			$sanitized[ $key ] = array(
				'label' => isset( $preset['label'] ) ? sanitize_text_field( (string) $preset['label'] ) : ucfirst( $key ),
				'vars'  => GFSK_Admin::sanitize_vars( isset( $preset['vars'] ) ? (array) $preset['vars'] : array() ),
				'flags' => GFSK_Admin::sanitize_flags( isset( $preset['flags'] ) ? (array) $preset['flags'] : array() ),
			);
		}

		return $sanitized;
	}
}
