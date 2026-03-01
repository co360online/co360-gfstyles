# GF Style Kits (Internal)

Plugin interno para WordPress + Gravity Forms (Gravity Theme) que aplica estilos visuales por `Form ID`.

## Cómo usar
1. Activa el plugin `GF Style Kits (Internal)`.
2. Ve a **Forms > GF Style Kits**.
3. Selecciona uno o más formularios con el multi-select.
4. Habilita el formulario, elige preset, ajusta variables y guarda.
5. Inserta el formulario con shortcode: `[gravityform id="X" title="false" description="false"]`.
6. Si usas caché/CDN, limpia caché después de guardar.

## Estructura de settings (`gfsk_settings`)
```php
[
  'forms' => [
    18 => [
      'enabled' => true,
      'preset' => 'card',
      'vars' => [
        'primary' => '#608269',
        'secondary' => '#DF9394',
        'card_bg' => '#f6f8f7',
        'radius' => 14,
        'padding' => 22,
        'font_size' => 15,
      ],
      'flags' => [
        'section_title_light' => true,
        'show_section_line' => true,
      ],
      'wrappers' => [
        [
          'enabled' => true,
          'start_field_id' => 46,
          'end_field_id' => 33,
          'class' => 'bloque-resumen-pago',
          'force_close_in_footer' => true,
        ],
      ],
      'advanced_css' => '',
    ],
  ],
  'presets' => [
    'custom_slug' => [
      'label' => 'Mi preset',
      'vars' => [...],
      'flags' => [...],
    ],
  ],
]
```

## Añadir un preset nuevo
- Opción 1: Exporta un preset, edita JSON e impórtalo.
- Opción 2: Guarda manualmente en `gfsk_settings['presets']` con `label`, `vars` y `flags`.

## Wrappers (limitación)
Los wrappers por rango de fields usan `gform_field_container_{FORM_ID}`.
Si un field de cierre no se imprime por lógica condicional, el wrapper puede quedar abierto.
Usa `force_close_in_footer` para intentar cerrarlo al final del último field renderizado.

## Nuevos settings de controles y tipografía
En `gfsk_settings['forms'][ID]['vars']` se incluyen además:
- `choice_row_gap`, `choice_gap`, `choice_size`
- `label_font_size`, `label_font_weight`
- `button_font_size`, `button_padding_y`, `button_padding_x`, `button_radius`
- `button_bg`, `button_text`, `button_bg_hover`

## Variables CSS emitidas por formulario
El frontend inyecta variables en `#gform_wrapper_{ID}` (solo forms activos):
- `--gfsk-choice-row-gap`, `--gfsk-choice-gap`, `--gfsk-choice-size`
- `--gfsk-label-size`, `--gfsk-label-weight`
- `--gfsk-btn-size`, `--gfsk-btn-py`, `--gfsk-btn-px`, `--gfsk-btn-radius`
- `--gfsk-btn-bg`, `--gfsk-btn-text`, `--gfsk-btn-bg-hover`

## CSS del plugin
- `assets/css/base.css`: presets, secciones, tarjetas/layout.
- `assets/css/controls.css`: radios/checkbox modernos, spacing choices, submit, Likert Survey.
