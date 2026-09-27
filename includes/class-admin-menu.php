<?php
/**
 * Menu e páginas do admin — v1.1.0.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Admin_Menu {

    private static $instance = null;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
    }

    public function add_admin_body_class( $classes ) {
        $screen = get_current_screen();
        if ( $screen && ( strpos( $screen->id, 'blog-automatico' ) !== false ) ) {
            $classes .= ' ba-admin-body ';
        }
        return $classes;
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_menu() {
        add_menu_page(
            __( 'Blog Automático', 'blog-automatico' ),
            __( 'Blog Automático', 'blog-automatico' ),
            'edit_posts',
            'blog-automatico',
            array( $this, 'render_dashboard' ),
            'dashicons-edit-large',
            30
        );

        add_submenu_page( 'blog-automatico', __( 'Dashboard', 'blog-automatico' ), __( 'Dashboard', 'blog-automatico' ), 'edit_posts', 'blog-automatico', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'blog-automatico', __( 'Novo Post IA', 'blog-automatico' ), __( 'Novo Post IA', 'blog-automatico' ), 'edit_posts', 'blog-automatico-new', array( $this, 'render_new_post' ) );
        add_submenu_page( 'blog-automatico', __( 'Fila de Conteúdo', 'blog-automatico' ), __( 'Fila de Conteúdo', 'blog-automatico' ), 'edit_posts', 'blog-automatico-queue', array( $this, 'render_queue' ) );
        add_submenu_page( 'blog-automatico', __( 'Histórico', 'blog-automatico' ), __( 'Histórico', 'blog-automatico' ), 'edit_posts', 'blog-automatico-history', array( $this, 'render_history' ) );
        add_submenu_page( 'blog-automatico', __( 'Treinamento IA', 'blog-automatico' ), __( 'Treinamento IA', 'blog-automatico' ), 'manage_options', 'blog-automatico-training', array( $this, 'render_training' ) );
        add_submenu_page( 'blog-automatico', __( 'Configurações', 'blog-automatico' ), __( 'Configurações', 'blog-automatico' ), 'manage_options', 'blog-automatico-settings', array( $this, 'render_settings' ) );
    }

    public function enqueue_assets( $hook_suffix ) {
        $plugin_pages = array(
            'toplevel_page_blog-automatico',
            'blog-automatico_page_blog-automatico-new',
            'blog-automatico_page_blog-automatico-queue',
            'blog-automatico_page_blog-automatico-history',
            'blog-automatico_page_blog-automatico-training',
            'blog-automatico_page_blog-automatico-settings',
        );

        if ( ! in_array( $hook_suffix, $plugin_pages, true ) ) {
            return;
        }

        wp_enqueue_style( 'ba-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap', array(), null );
        wp_enqueue_style( 'ba-admin-style', BA_PLUGIN_URL . 'admin/css/admin-style.css', array(), BA_VERSION );

        wp_enqueue_script( 'ba-admin-script', BA_PLUGIN_URL . 'admin/js/admin-script.js', array( 'jquery' ), BA_VERSION, true );

        wp_localize_script( 'ba-admin-script', 'baAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ba_admin_nonce' ),
            'strings' => array(
                'generating'       => __( 'Gerando conteúdo...', 'blog-automatico' ),
                'generatingText'   => __( 'Gerando texto do artigo...', 'blog-automatico' ),
                'generatingImages' => __( 'Gerando imagens com IA...', 'blog-automatico' ),
                'applyingTemplate' => __( 'Montando layout Elementor...', 'blog-automatico' ),
                'optimizingSeo'    => __( 'Otimizando SEO...', 'blog-automatico' ),
                'success'          => __( 'Post gerado com sucesso!', 'blog-automatico' ),
                'error'            => __( 'Erro ao gerar post.', 'blog-automatico' ),
                'confirm'          => __( 'Tem certeza?', 'blog-automatico' ),
                'testing'          => __( 'Testando conexão...', 'blog-automatico' ),
                'testSuccess'      => __( 'Conexão OK!', 'blog-automatico' ),
                'testError'        => __( 'Falha na conexão.', 'blog-automatico' ),
                'adding'           => __( 'Adicionando ideias...', 'blog-automatico' ),
            ),
        ));
    }

    public function render_dashboard() { include BA_PLUGIN_DIR . 'admin/views/dashboard.php'; }
    public function render_new_post()  { include BA_PLUGIN_DIR . 'admin/views/new-post.php'; }
    public function render_queue()     { include BA_PLUGIN_DIR . 'admin/views/queue.php'; }
    public function render_history()   { include BA_PLUGIN_DIR . 'admin/views/history.php'; }
    public function render_training()  { include BA_PLUGIN_DIR . 'admin/views/training.php'; }
    public function render_settings()  { include BA_PLUGIN_DIR . 'admin/views/settings.php'; }
}
