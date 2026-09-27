<?php
/**
 * Classe de logging do plugin.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Logger {

    /**
     * Nome da tabela de logs.
     *
     * @var string
     */
    private $table_name;

    /**
     * Instância singleton.
     *
     * @var BA_Logger
     */
    private static $instance = null;

    /**
     * Construtor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ba_posts_log';
    }

    /**
     * Retorna instância singleton.
     *
     * @return BA_Logger
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registra um log de geração de post.
     *
     * @param array $data Dados do log.
     * @return int|false ID do registro ou false em caso de erro.
     */
    public function log_generation( $data ) {
        global $wpdb;

        $defaults = array(
            'post_id'          => 0,
            'idea'             => '',
            'tokens_used'      => 0,
            'model_used'       => '',
            'images_generated' => 0,
            'status'           => 'pending',
            'error_message'    => '',
            'generation_time'  => 0,
            'created_at'       => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'post_id'          => intval( $data['post_id'] ),
                'idea'             => sanitize_textarea_field( $data['idea'] ),
                'tokens_used'      => intval( $data['tokens_used'] ),
                'model_used'       => sanitize_text_field( $data['model_used'] ),
                'images_generated' => intval( $data['images_generated'] ),
                'status'           => sanitize_text_field( $data['status'] ),
                'error_message'    => sanitize_textarea_field( $data['error_message'] ),
                'generation_time'  => floatval( $data['generation_time'] ),
                'created_at'       => $data['created_at'],
            ),
            array( '%d', '%s', '%d', '%s', '%d', '%s', '%s', '%f', '%s' )
        );

        if ( false === $result ) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Atualiza um log existente.
     *
     * @param int   $log_id ID do log.
     * @param array $data   Dados a atualizar.
     * @return bool
     */
    public function update_log( $log_id, $data ) {
        global $wpdb;

        $update_data   = array();
        $update_format = array();

        if ( isset( $data['post_id'] ) ) {
            $update_data['post_id'] = intval( $data['post_id'] );
            $update_format[]        = '%d';
        }
        if ( isset( $data['tokens_used'] ) ) {
            $update_data['tokens_used'] = intval( $data['tokens_used'] );
            $update_format[]            = '%d';
        }
        if ( isset( $data['images_generated'] ) ) {
            $update_data['images_generated'] = intval( $data['images_generated'] );
            $update_format[]                 = '%d';
        }
        if ( isset( $data['status'] ) ) {
            $update_data['status'] = sanitize_text_field( $data['status'] );
            $update_format[]       = '%s';
        }
        if ( isset( $data['error_message'] ) ) {
            $update_data['error_message'] = sanitize_textarea_field( $data['error_message'] );
            $update_format[]              = '%s';
        }
        if ( isset( $data['generation_time'] ) ) {
            $update_data['generation_time'] = floatval( $data['generation_time'] );
            $update_format[]                = '%f';
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array( 'id' => intval( $log_id ) ),
            $update_format,
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Busca logs com paginação.
     *
     * @param int    $page     Página atual.
     * @param int    $per_page Itens por página.
     * @param string $status   Filtro de status (opcional).
     * @return array
     */
    public function get_logs( $page = 1, $per_page = 20, $status = '' ) {
        global $wpdb;

        $offset = ( $page - 1 ) * $per_page;
        $where  = '';

        if ( ! empty( $status ) ) {
            $where = $wpdb->prepare( 'WHERE status = %s', $status );
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} {$where}"
        );

        return array(
            'items'      => $results,
            'total'      => intval( $total ),
            'page'       => $page,
            'per_page'   => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        );
    }

    /**
     * Retorna estatísticas gerais.
     *
     * @return array
     */
    public function get_stats() {
        global $wpdb;

        $total_posts = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'success'"
        );

        $total_tokens = $wpdb->get_var(
            "SELECT SUM(tokens_used) FROM {$this->table_name} WHERE status = 'success'"
        );

        $total_images = $wpdb->get_var(
            "SELECT SUM(images_generated) FROM {$this->table_name} WHERE status = 'success'"
        );

        $avg_time = $wpdb->get_var(
            "SELECT AVG(generation_time) FROM {$this->table_name} WHERE status = 'success'"
        );

        $today_posts = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'success' AND DATE(created_at) = %s",
                current_time( 'Y-m-d' )
            )
        );

        $total_errors = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'error'"
        );

        return array(
            'total_posts'   => intval( $total_posts ),
            'total_tokens'  => intval( $total_tokens ),
            'total_images'  => intval( $total_images ),
            'avg_time'      => round( floatval( $avg_time ), 2 ),
            'today_posts'   => intval( $today_posts ),
            'total_errors'  => intval( $total_errors ),
        );
    }

    /**
     * Limpa logs antigos.
     *
     * @param int $days Dias para manter.
     * @return int Número de registros removidos.
     */
    public function cleanup( $days = 90 ) {
        global $wpdb;

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }

    /**
     * Remove um log específico por ID.
     *
     * @param int $log_id ID do log.
     * @return bool
     */
    public function delete_log( $log_id ) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array( 'id' => intval( $log_id ) ),
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Limpa logs por status ou todos.
     *
     * @param string $status Status a limpar ('error', 'all', etc).
     * @return int|bool
     */
    public function clear_logs( $status = '' ) {
        global $wpdb;

        if ( empty( $status ) || 'all' === $status ) {
            return $wpdb->query( "TRUNCATE TABLE {$this->table_name}" );
        }

        return $wpdb->delete(
            $this->table_name,
            array( 'status' => sanitize_text_field( $status ) ),
            array( '%s' )
        );
    }
}
