<?php
/**
 * Classe principal do plugin — v1.1.0.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Plugin_Core {

    private static $instance = null;

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_dependencies() {
        $files = array(
            'includes/class-logger.php',
            'includes/class-settings.php',
            'includes/class-admin-menu.php',
            'includes/class-ai-connector.php',
            'includes/class-content-generator.php',
            'includes/class-image-generator.php',
            'includes/class-seo-optimizer.php',
            'includes/class-post-creator.php',
            'includes/class-elementor-builder.php',
            'includes/class-scheduler.php',
        );

        foreach ( $files as $file ) {
            $path = BA_PLUGIN_DIR . $file;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }
    }

    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ) );

        // Registrar intervalo customizado do cron
        add_filter( 'cron_schedules', array( 'BA_Scheduler', 'add_cron_interval' ) );

        if ( is_admin() ) {
            BA_Admin_Menu::get_instance();
            BA_Settings::get_instance();

            // AJAX handlers
            add_action( 'wp_ajax_ba_generate_post', array( $this, 'ajax_generate_post' ) );
            add_action( 'wp_ajax_ba_test_connection', array( $this, 'ajax_test_connection' ) );
            add_action( 'wp_ajax_ba_bulk_add_ideas', array( $this, 'ajax_bulk_add_ideas' ) );
            add_action( 'wp_ajax_ba_remove_scheduled', array( $this, 'ajax_remove_scheduled' ) );
            add_action( 'wp_ajax_ba_clear_queue', array( $this, 'ajax_clear_queue' ) );
            add_action( 'wp_ajax_ba_get_provider_models', array( $this, 'ajax_get_provider_models' ) );
            add_action( 'wp_ajax_ba_delete_log', array( $this, 'ajax_delete_log' ) );
            add_action( 'wp_ajax_ba_clear_logs', array( $this, 'ajax_clear_logs' ) );
        }

        add_action( 'wp_head', array( $this, 'output_schema_markup' ) );
    }

    public function init() {
        load_plugin_textdomain( 'blog-automatico', false, dirname( BA_PLUGIN_BASENAME ) . '/languages' );
        BA_Scheduler::get_instance();
    }

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_logs = $wpdb->prefix . 'ba_posts_log';
        $sql_logs   = "CREATE TABLE {$table_logs} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            idea TEXT NOT NULL,
            tokens_used INT(11) NOT NULL DEFAULT 0,
            model_used VARCHAR(50) NOT NULL DEFAULT '',
            images_generated INT(11) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            error_message TEXT,
            generation_time FLOAT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $table_scheduled = $wpdb->prefix . 'ba_scheduled_ideas';
        $sql_scheduled   = "CREATE TABLE {$table_scheduled} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            idea TEXT NOT NULL,
            scheduled_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            template_id VARCHAR(50) NOT NULL DEFAULT 'default',
            status VARCHAR(20) NOT NULL DEFAULT 'queued',
            priority INT(11) NOT NULL DEFAULT 5,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY priority (priority)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_logs );
        dbDelta( $sql_scheduled );

        update_option( 'ba_db_version', BA_DB_VERSION );

        $scheduler = new BA_Scheduler();
        $scheduler->schedule_events();

        flush_rewrite_rules();
    }

    public static function deactivate() {
        $scheduler = new BA_Scheduler();
        $scheduler->unschedule_events();
        flush_rewrite_rules();
    }

    // === AJAX Handlers ===

    public function ajax_generate_post() {
        check_ajax_referer( 'ba_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'blog-automatico' ) ) );
        }

        $idea = isset( $_POST['idea'] ) ? sanitize_textarea_field( wp_unslash( $_POST['idea'] ) ) : '';
        if ( empty( $idea ) ) {
            wp_send_json_error( array( 'message' => __( 'Insira uma ideia para o post.', 'blog-automatico' ) ) );
        }

        $options = array();
        if ( isset( $_POST['tone'] ) )     $options['tone']     = sanitize_text_field( wp_unslash( $_POST['tone'] ) );
        if ( isset( $_POST['length'] ) )   $options['length']   = absint( $_POST['length'] );
        if ( isset( $_POST['template'] ) ) $options['template'] = sanitize_text_field( wp_unslash( $_POST['template'] ) );
        if ( isset( $_POST['status'] ) )   $options['status']   = sanitize_text_field( wp_unslash( $_POST['status'] ) );

        $creator = BA_Post_Creator::get_instance();
        $result  = $creator->create_from_idea( $idea, $options );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    public function ajax_test_connection() {
        check_ajax_referer( 'ba_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'blog-automatico' ) ) );
        }

        $provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : null;
        $api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : null;
        $model    = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : null;

        $ai     = BA_AI_Connector::get_instance();
        $result = $ai->test_connection( $provider, $api_key, $model );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    public function ajax_bulk_add_ideas() {
        check_ajax_referer( 'ba_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'blog-automatico' ) ) );
        }

        $ideas_raw = isset( $_POST['ideas'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ideas'] ) ) : '';
        $template  = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'default';

        if ( empty( $ideas_raw ) ) {
            wp_send_json_error( array( 'message' => __( 'Insira pelo menos uma ideia.', 'blog-automatico' ) ) );
        }

        // Separar por linhas
        $ideas = array_filter( array_map( 'trim', explode( "\n", $ideas_raw ) ) );

        if ( empty( $ideas ) ) {
            wp_send_json_error( array( 'message' => __( 'Nenhuma ideia válida encontrada.', 'blog-automatico' ) ) );
        }

        $scheduler = BA_Scheduler::get_instance();
        $result    = $scheduler->bulk_add( $ideas, $template );

        wp_send_json_success( array(
            'message'  => sprintf(
                __( '%d ideias adicionadas à fila com sucesso!', 'blog-automatico' ),
                $result['inserted']
            ),
            'inserted' => $result['inserted'],
            'errors'   => $result['errors'],
        ));
    }

    public function ajax_remove_scheduled() {
        check_ajax_referer( 'ba_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'blog-automatico' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'blog-automatico' ) ) );
        }

        $scheduler = BA_Scheduler::get_instance();
        $scheduler->remove( $id );

        wp_send_json_success( array( 'message' => __( 'Removido.', 'blog-automatico' ) ) );
    }

    public function ajax_clear_queue() {
        check_ajax_referer( 'ba_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'blog-automatico' ) ) );
        }

        $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
        if ( empty( $status ) ) {
            wp_send_json_error( array( 'message' => __( 'Status inválido.', 'blog-automatico' ) ) );
        }

        $scheduler = BA_Scheduler::get_instance();
        $scheduler->clear_by_status( $status );

        wp_send_json_success( array( 'message' => __( 'Fila limpa.', 'blog-automatico' ) ) );
    }

    public function ajax_get_provider_models() {
        check_ajax_referer( 'ba_admin_nonce', 'nonce' );

        $provider  = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
        $providers = BA_AI_Connector::get_providers();

        if ( ! isset( $providers[ $provider ] ) ) {
            wp_send_json_error( array( 'message' => 'Provider inválido.' ) );
        }

        wp_send_json_success( array(
            'models'       => $providers[ $provider ]['models'],
            'image_models' => $providers[ $provider ]['image_models'],
            'supports_images' => $providers[ $provider ]['supports_images'],
        ));
    }

    public function ajax_delete_log() {
        check_ajax_referer( 'ba_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'blog-automatico' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'blog-automatico' ) ) );
        }

        $logger = BA_Logger::get_instance();
        $logger->delete_log( $id );

        wp_send_json_success( array( 'message' => __( 'Registro apagado com sucesso.', 'blog-automatico' ) ) );
    }

    public function ajax_clear_logs() {
        check_ajax_referer( 'ba_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sem permissão.', 'blog-automatico' ) ) );
        }

        $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'all';

        $logger = BA_Logger::get_instance();
        $logger->clear_logs( $status );

        wp_send_json_success( array( 'message' => __( 'Histórico limpo com sucesso.', 'blog-automatico' ) ) );
    }

    public function output_schema_markup() {
        if ( ! is_singular( 'post' ) ) {
            return;
        }
        $post_id = get_the_ID();
        $schema  = get_post_meta( $post_id, '_ba_schema_markup', true );
        if ( empty( $schema ) || ! is_array( $schema ) ) {
            return;
        }
        foreach ( $schema as $type => $data ) {
            echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        }
    }
}
