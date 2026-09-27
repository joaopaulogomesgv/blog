<?php
/**
 * Agendador de publicações automáticas — v1.1.0.
 * Suporta fila em massa (100+ ideias) e publicação automática diária.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Scheduler {

    private $table_name;
    private static $instance = null;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ba_scheduled_ideas';

        add_action( 'ba_process_scheduled_ideas', array( $this, 'process_queue' ) );
        add_action( 'ba_daily_cleanup', array( $this, 'daily_cleanup' ) );
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function schedule_events() {
        // Verificar a cada 30 minutos
        if ( ! wp_next_scheduled( 'ba_process_scheduled_ideas' ) ) {
            wp_schedule_event( time(), 'ba_thirty_minutes', 'ba_process_scheduled_ideas' );
        }
        if ( ! wp_next_scheduled( 'ba_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'ba_daily_cleanup' );
        }
    }

    public function unschedule_events() {
        wp_clear_scheduled_hook( 'ba_process_scheduled_ideas' );
        wp_clear_scheduled_hook( 'ba_daily_cleanup' );
    }

    /**
     * Registra intervalo customizado de 30 minutos.
     */
    public static function add_cron_interval( $schedules ) {
        $schedules['ba_thirty_minutes'] = array(
            'interval' => 1800,
            'display'  => __( 'A cada 30 minutos', 'blog-automatico' ),
        );
        return $schedules;
    }

    /**
     * Adiciona múltiplas ideias de uma vez (bulk).
     *
     * @param array  $ideas       Array de ideias.
     * @param string $template_id Template.
     * @return array Resultado com quantidade inserida.
     */
    public function bulk_add( $ideas, $template_id = 'default' ) {
        global $wpdb;

        $inserted = 0;
        $errors   = 0;

        foreach ( $ideas as $index => $idea ) {
            $idea = trim( $idea );
            if ( empty( $idea ) ) {
                continue;
            }

            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'idea'           => sanitize_textarea_field( $idea ),
                    'scheduled_date' => '0000-00-00 00:00:00', // Será processado automaticamente
                    'template_id'    => sanitize_text_field( $template_id ),
                    'status'         => 'queued',
                    'priority'       => $index + 1, // Ordem de inserção
                    'created_at'     => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s', '%d', '%s' )
            );

            if ( false !== $result ) {
                $inserted++;
            } else {
                $errors++;
            }
        }

        return array(
            'inserted' => $inserted,
            'errors'   => $errors,
            'total'    => count( $ideas ),
        );
    }

    /**
     * Adiciona uma ideia única à fila.
     */
    public function add_to_queue( $idea, $scheduled_date = '', $template_id = 'default', $priority = 5 ) {
        global $wpdb;

        if ( empty( $scheduled_date ) ) {
            $scheduled_date = '0000-00-00 00:00:00';
        }

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'idea'           => sanitize_textarea_field( $idea ),
                'scheduled_date' => $scheduled_date,
                'template_id'    => sanitize_text_field( $template_id ),
                'status'         => 'queued',
                'priority'       => intval( $priority ),
                'created_at'     => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%d', '%s' )
        );

        return false !== $result ? $wpdb->insert_id : false;
    }

    /**
     * Processa a fila — motor da publicação automática.
     */
    public function process_queue() {
        $settings = BA_Settings::get_instance();

        // Verificar se auto-post está ativado
        if ( ! $settings->is_auto_post_enabled() ) {
            return;
        }

        // Verificar limite diário
        if ( $settings->is_daily_limit_reached() ) {
            return;
        }

        // Verificar se já postou o suficiente hoje
        $posts_per_day = $settings->get_posts_per_day();
        $logger        = BA_Logger::get_instance();
        $stats         = $logger->get_stats();

        if ( $stats['today_posts'] >= $posts_per_day ) {
            return;
        }

        // Verificar horário permitido
        $time_start = $settings->get( 'ba_auto_post_time_start' );
        $time_end   = $settings->get( 'ba_auto_post_time_end' );
        $current    = current_time( 'H:i' );

        if ( $current < $time_start || $current > $time_end ) {
            return;
        }

        // Buscar próxima ideia na fila
        $idea = $this->get_next_queued();

        if ( ! $idea ) {
            return;
        }

        // Marcar como processando
        $this->update_status( $idea->id, 'processing' );

        // Criar o post
        $creator = BA_Post_Creator::get_instance();
        $result  = $creator->create_from_idea( $idea->idea, array(
            'template' => $idea->template_id,
        ));

        if ( is_wp_error( $result ) ) {
            $this->update_status( $idea->id, 'failed' );
        } else {
            $this->update_status( $idea->id, 'done' );
        }
    }

    /**
     * Busca a próxima ideia na fila.
     */
    private function get_next_queued() {
        global $wpdb;

        return $wpdb->get_row(
            "SELECT * FROM {$this->table_name} 
            WHERE status = 'queued' 
            ORDER BY priority ASC, id ASC 
            LIMIT 1"
        );
    }

    /**
     * Atualiza status de uma ideia.
     */
    public function update_status( $id, $status ) {
        global $wpdb;

        $wpdb->update(
            $this->table_name,
            array( 'status' => $status ),
            array( 'id' => intval( $id ) ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Busca ideias com paginação.
     */
    public function get_scheduled( $page = 1, $per_page = 50, $status = '' ) {
        global $wpdb;

        $offset = ( $page - 1 ) * $per_page;
        $where  = '';

        if ( ! empty( $status ) ) {
            $where = $wpdb->prepare( 'WHERE status = %s', $status );
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} {$where} ORDER BY 
                CASE status 
                    WHEN 'processing' THEN 1 
                    WHEN 'queued' THEN 2 
                    WHEN 'done' THEN 3 
                    WHEN 'failed' THEN 4 
                END,
                priority ASC, id ASC 
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} {$where}"
        );

        return array(
            'items'       => $results,
            'total'       => intval( $total ),
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( intval( $total ) / $per_page ),
        );
    }

    /**
     * Contagem por status.
     */
    public function get_queue_stats() {
        global $wpdb;

        $queued = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'queued'"
        );
        $processing = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'processing'"
        );
        $done = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'done'"
        );
        $failed = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'failed'"
        );
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name}"
        );

        return array(
            'queued'     => intval( $queued ),
            'processing' => intval( $processing ),
            'done'       => intval( $done ),
            'failed'     => intval( $failed ),
            'total'      => intval( $total ),
        );
    }

    public function remove( $id ) {
        global $wpdb;
        return false !== $wpdb->delete(
            $this->table_name,
            array( 'id' => intval( $id ) ),
            array( '%d' )
        );
    }

    /**
     * Remove todas as ideias com determinado status.
     */
    public function clear_by_status( $status ) {
        global $wpdb;
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE status = %s",
                $status
            )
        );
    }

    public function daily_cleanup() {
        $logger = BA_Logger::get_instance();
        $logger->cleanup( 90 );
    }
}
