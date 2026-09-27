<?php
/**
 * Plugin Name: Blog Automático com IA
 * Plugin URI: https://github.com/seu-usuario/blog-automatico
 * Description: Cria posts de blog automaticamente usando Inteligência Artificial com templates Elementor Pro e otimização SEO. Suporta OpenAI, Gemini, Grok e DeepSeek.
 * Version: 1.3.9
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Blog Automático
 * Author URI: https://seu-site.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: blog-automatico
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BA_VERSION', '1.3.9' );
define( 'BA_PLUGIN_FILE', __FILE__ );
define( 'BA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'BA_DB_VERSION', '1.3.0' );

// Autoload das classes
spl_autoload_register( function ( $class_name ) {
    $prefix = 'BA_';
    if ( strpos( $class_name, $prefix ) !== 0 ) {
        return;
    }

    $class_file = str_replace( $prefix, '', $class_name );
    $class_file = strtolower( str_replace( '_', '-', $class_file ) );
    $file       = BA_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
});

register_activation_hook( __FILE__, array( 'BA_Plugin_Core', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BA_Plugin_Core', 'deactivate' ) );

function blog_automatico() {
    return BA_Plugin_Core::get_instance();
}

blog_automatico();
