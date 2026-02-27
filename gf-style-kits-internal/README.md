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
