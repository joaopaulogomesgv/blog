<?php
/**
 * Criador de posts no WordPress.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Post_Creator {

    /**
     * Instâncias das dependências.
     */
    private $settings;
    private $content_generator;
    private $image_generator;
    private $seo_optimizer;
    private $elementor_builder;
    private $logger;

    /**
     * Instância singleton.
     *
     * @var BA_Post_Creator
     */
    private static $instance = null;

    /**
     * Construtor.
     */
    public function __construct() {
        $this->settings          = BA_Settings::get_instance();
        $this->content_generator = BA_Content_Generator::get_instance();
        $this->image_generator   = BA_Image_Generator::get_instance();
        $this->seo_optimizer     = BA_SEO_Optimizer::get_instance();
        $this->elementor_builder = BA_Elementor_Builder::get_instance();
        $this->logger            = BA_Logger::get_instance();
    }

    /**
     * Retorna instância singleton.
     *
     * @return BA_Post_Creator
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Cria um post completo a partir de uma ideia.
     * Este é o método principal que orquestra todo o processo.
     *
     * @param string $idea    Ideia do usuário.
     * @param array  $options Opções de criação.
     * @return array|WP_Error Resultado da criação.
     */
    public function create_from_idea( $idea, $options = array() ) {
        $start_time = microtime( true );

        // Verificar limite diário
        if ( $this->settings->is_daily_limit_reached() ) {
            return new WP_Error(
                'ba_daily_limit',
                __( 'Limite diário de posts atingido. Tente novamente amanhã.', 'blog-automatico' )
            );
        }

        // Registrar log inicial
        $log_id = $this->logger->log_generation( array(
            'idea'   => $idea,
            'status' => 'processing',
        ));

        // === ETAPA 1: Gerar conteúdo via IA ===
        $content = $this->content_generator->generate( $idea, $options );

        if ( is_wp_error( $content ) ) {
            $this->logger->update_log( $log_id, array(
                'status'        => 'error',
                'error_message' => $content->get_error_message(),
            ));
            return $content;
        }

        // === ETAPA 2: Criar o post no WordPress ===
        $post_status = isset( $options['status'] ) ? $options['status'] : $this->settings->get( 'ba_publish_status' );
        $template    = isset( $options['template'] ) ? $options['template'] : $this->settings->get( 'ba_default_template' );

        // Gerar HTML do conteúdo para o post_content
        $html_content = $this->content_generator->content_to_html( $content );

        // Processar categorias e tags
        $category_ids = array();
        $tag_ids      = array();

        if ( isset( $content['categorias_sugeridas'] ) ) {
            $category_ids = $this->seo_optimizer->process_categories( $content['categorias_sugeridas'] );
        }

        // Adicionar categoria padrão se configurada
        $default_cat = intval( $this->settings->get( 'ba_default_category' ) );
        if ( $default_cat > 0 && ! in_array( $default_cat, $category_ids, true ) ) {
            array_unshift( $category_ids, $default_cat );
        }

        if ( isset( $content['tags_sugeridas'] ) ) {
            $tag_ids = $this->seo_optimizer->process_tags( $content['tags_sugeridas'] );
        }

        $post_data = array(
            'post_title'    => sanitize_text_field( $content['titulo'] ),
            'post_name'     => sanitize_title( $content['slug'] ),
            'post_content'  => $html_content,
            'post_status'   => $post_status,
            'post_type'     => 'post',
            'post_author'   => get_current_user_id(),
            'post_category' => $category_ids,
            'tags_input'    => array(),
        );

        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            $this->logger->update_log( $log_id, array(
                'status'        => 'error',
                'error_message' => $post_id->get_error_message(),
            ));
            return $post_id;
        }

        // Definir tags
        if ( ! empty( $tag_ids ) ) {
            wp_set_post_tags( $post_id, $tag_ids );
        }

        // === ETAPA 3: Gerar e definir imagens ===
        $image_ids = array(
            'featured' => null,
            'internal' => array(),
        );

        $images_count = 0;

        // Imagem destacada
        if ( ! empty( $content['prompt_imagem_destaque'] ) ) {
            $featured_id = $this->image_generator->generate_featured_image(
                $content['prompt_imagem_destaque'],
                $content['titulo'],
                $post_id
            );

            if ( ! is_wp_error( $featured_id ) ) {
                set_post_thumbnail( $post_id, $featured_id );
                $image_ids['featured'] = $featured_id;
                $images_count++;
            }
        }

        // Imagens internas
        if ( isset( $content['prompts_imagens_internas'] ) && is_array( $content['prompts_imagens_internas'] ) ) {
            $internal_ids = $this->image_generator->generate_internal_images(
                $content['prompts_imagens_internas'],
                $content['titulo'],
                $post_id
            );

            foreach ( $internal_ids as $img_id ) {
                if ( ! is_wp_error( $img_id ) ) {
                    $image_ids['internal'][] = $img_id;
                    $images_count++;
                }
            }
        }

        // === ETAPA 4: Aplicar template Elementor ===
        if ( $this->elementor_builder->is_elementor_active() ) {
            $elementor_result = $this->elementor_builder->apply_template(
                $post_id,
                $content,
                $image_ids,
                $template
            );

            if ( is_wp_error( $elementor_result ) ) {
                // Fallback: usar HTML puro
                $this->elementor_builder->apply_html_fallback( $post_id, $content, $image_ids );
            }
        } else {
            // Sem Elementor: usar HTML com imagens
            $this->elementor_builder->apply_html_fallback( $post_id, $content, $image_ids );
        }

        // === ETAPA 5: Otimização SEO ===
        $this->seo_optimizer->optimize( $post_id, $content );

        // === ETAPA 6: Salvar metadados ===
        update_post_meta( $post_id, '_ba_generated', true );
        update_post_meta( $post_id, '_ba_idea', sanitize_textarea_field( $idea ) );
        update_post_meta( $post_id, '_ba_content_data', $content );
        update_post_meta( $post_id, '_ba_generation_date', current_time( 'mysql' ) );

        // Calcular tempo de geração
        $generation_time = microtime( true ) - $start_time;

        // Atualizar log
        $ai_connector = BA_AI_Connector::get_instance();
        $this->logger->update_log( $log_id, array(
            'post_id'          => $post_id,
            'tokens_used'      => $ai_connector->get_total_tokens_used(),
            'model_used'       => $this->settings->get( 'ba_gpt_model' ),
            'images_generated' => $images_count,
            'status'           => 'success',
            'generation_time'  => $generation_time,
        ));

        // Enviar notificação por email
        $this->send_notification( $post_id, $content, $generation_time );

        return array(
            'success'         => true,
            'post_id'         => $post_id,
            'post_url'        => get_permalink( $post_id ),
            'edit_url'        => get_edit_post_link( $post_id, 'raw' ),
            'title'           => $content['titulo'],
            'status'          => $post_status,
            'images_count'    => $images_count,
            'tokens_used'     => $ai_connector->get_total_tokens_used(),
            'generation_time' => round( $generation_time, 2 ),
        );
    }

    /**
     * Envia notificação por email.
     *
     * @param int    $post_id         ID do post.
     * @param array  $content         Conteúdo gerado.
     * @param float  $generation_time Tempo de geração.
     */
    private function send_notification( $post_id, $content, $generation_time ) {
        $email = $this->settings->get( 'ba_notification_email' );

        if ( empty( $email ) ) {
            return;
        }

        $subject = sprintf(
            /* translators: %s: título do post */
            __( '[Blog Automático] Novo post gerado: %s', 'blog-automatico' ),
            $content['titulo']
        );

        $message  = sprintf( __( 'Um novo post foi gerado com sucesso!', 'blog-automatico' ) ) . "\n\n";
        $message .= sprintf( __( 'Título: %s', 'blog-automatico' ), $content['titulo'] ) . "\n";
        $message .= sprintf( __( 'URL: %s', 'blog-automatico' ), get_permalink( $post_id ) ) . "\n";
        $message .= sprintf( __( 'Editar: %s', 'blog-automatico' ), get_edit_post_link( $post_id, 'raw' ) ) . "\n";
        $message .= sprintf( __( 'Tempo de geração: %s segundos', 'blog-automatico' ), round( $generation_time, 2 ) ) . "\n";

        wp_mail( $email, $subject, $message );
    }
}
