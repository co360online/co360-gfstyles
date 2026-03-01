<?php
/**
 * Plugin Name: CO360 GF Styles
 * Description: Aplica estilos personalizados a formularios de Gravity Forms en frontend.
 * Version: 1.0.0
 * Author: CO360
 * Text Domain: co360-gfstyles
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('CO360_GF_Styles')) {
    class CO360_GF_Styles
    {
        private const VERSION = '1.0.0';
        private const HANDLE_STYLE = 'co360-gfstyles-frontend';
        private const HANDLE_SCRIPT = 'co360-gfstyles-frontend';

        public function __construct()
        {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 20);
            add_filter('gform_form_tag', [$this, 'add_form_class'], 10, 2);
        }

        public function enqueue_frontend_assets(): void
        {
            if (is_admin()) {
                return;
            }

            $css_file = plugin_dir_path(__FILE__) . 'assets/css/co360-gfstyles.css';
            $js_file = plugin_dir_path(__FILE__) . 'assets/js/co360-gfstyles.js';

            wp_enqueue_style(
                self::HANDLE_STYLE,
                plugin_dir_url(__FILE__) . 'assets/css/co360-gfstyles.css',
                [],
                file_exists($css_file) ? (string) filemtime($css_file) : self::VERSION
            );

            wp_enqueue_script(
                self::HANDLE_SCRIPT,
                plugin_dir_url(__FILE__) . 'assets/js/co360-gfstyles.js',
                [],
                file_exists($js_file) ? (string) filemtime($js_file) : self::VERSION,
                true
            );
        }

        public function add_form_class(string $form_tag, array $form): string
        {
            if (str_contains($form_tag, 'class=')) {
                return preg_replace('/class=("|\')(.*?)(\1)/', 'class=$1$2 co360-gfstyles-form$3', $form_tag, 1) ?: $form_tag;
            }

            return str_replace('<form', '<form class="co360-gfstyles-form"', $form_tag);
        }
    }

    new CO360_GF_Styles();
}
