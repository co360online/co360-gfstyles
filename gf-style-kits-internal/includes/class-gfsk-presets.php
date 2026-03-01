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
					'primary'             => '#2f6cdb',
					'secondary'           => '#1d4ea1',
					'card_bg'             => '#f8fafc',
					'radius'              => 8,
					'padding'             => 16,
					'font_size'           => 15,
					'choice_row_gap'      => 6,
					'choice_gap'          => 10,
					'choice_size'         => 22,
					'label_font_size'     => 18,
					'label_font_weight'   => 700,
					'button_font_size'    => 16,
					'button_padding_y'    => 14,
					'button_padding_x'    => 22,
					'button_radius'       => 14,
					'button_bg'           => '#2f6cdb',
					'button_text'         => '#ffffff',
					'button_bg_hover'     => '#1d4ea1',
					'section_title_light' => false,
					'section_line'        => false,
				),
			),
			'card'      => array(
				'label' => 'Card',
				'vars'  => array(
					'primary'             => '#608269',
					'secondary'           => '#4d6955',
					'card_bg'             => '#f6f8f7',
					'radius'              => 14,
					'padding'             => 22,
					'font_size'           => 15,
					'choice_row_gap'      => 6,
					'choice_gap'          => 10,
					'choice_size'         => 22,
					'label_font_size'     => 18,
					'label_font_weight'   => 700,
					'button_font_size'    => 16,
					'button_padding_y'    => 14,
					'button_padding_x'    => 22,
					'button_radius'       => 14,
					'button_bg'           => '#608269',
					'button_text'         => '#ffffff',
					'button_bg_hover'     => '#4d6955',
					'section_title_light' => true,
					'section_line'        => true,
				),
			),
			'factura'   => array(
				'label' => 'Factura',
				'vars'  => array(
					'primary'             => '#1f7a8c',
					'secondary'           => '#144d57',
					'card_bg'             => '#f3f7f8',
					'radius'              => 12,
					'padding'             => 18,
					'font_size'           => 15,
					'choice_row_gap'      => 5,
					'choice_gap'          => 8,
					'choice_size'         => 20,
					'label_font_size'     => 17,
					'label_font_weight'   => 700,
					'button_font_size'    => 16,
					'button_padding_y'    => 12,
					'button_padding_x'    => 20,
					'button_radius'       => 10,
					'button_bg'           => '#1f7a8c',
					'button_text'         => '#ffffff',
					'button_bg_hover'     => '#144d57',
					'section_title_light' => true,
					'section_line'        => true,
				),
			),
			'corporate' => array(
				'label' => 'Corporate',
				'vars'  => array(
					'primary'             => '#1f2937',
					'secondary'           => '#4b5563',
					'card_bg'             => '#f3f4f6',
					'radius'              => 6,
					'padding'             => 14,
					'font_size'           => 14,
					'choice_row_gap'      => 6,
					'choice_gap'          => 9,
					'choice_size'         => 20,
					'label_font_size'     => 17,
					'label_font_weight'   => 700,
					'button_font_size'    => 15,
					'button_padding_y'    => 12,
					'button_padding_x'    => 20,
					'button_radius'       => 10,
					'button_bg'           => '#1f2937',
					'button_text'         => '#ffffff',
					'button_bg_hover'     => '#4b5563',
					'section_title_light' => false,
					'section_line'        => true,
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
			if ( '' === $key || ! is_array( $preset ) ) {
				continue;
			}

			$vars = isset( $preset['vars'] ) ? (array) $preset['vars'] : array();
			if ( isset( $preset['flags'] ) && is_array( $preset['flags'] ) ) {
				$vars['section_title_light'] = ! empty( $preset['flags']['section_title_light'] );
				$vars['section_line']        = ! empty( $preset['flags']['show_section_line'] );
			}

			$sanitized[ $key ] = array(
				'label' => isset( $preset['label'] ) ? sanitize_text_field( (string) $preset['label'] ) : ucfirst( $key ),
				'vars'  => GFSK_Admin::sanitize_vars( $vars ),
			);
		}
		return $sanitized;
	}
}
