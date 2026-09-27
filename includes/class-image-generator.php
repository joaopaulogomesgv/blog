<?php
/**
 * Gerador de imagens via DALL-E.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Image_Generator {

    /**
     * Instância do conector IA.
     *
     * @var BA_AI_Connector
     */
    private $ai;

    /**
     * Instância singleton.
     *
     * @var BA_Image_Generator
     */
    private static $instance = null;

    /**
     * Construtor.
     */
    public function __construct() {
        $this->ai = BA_AI_Connector::get_instance();
    }

    /**
     * Retorna instância singleton.
     *
     * @return BA_Image_Generator
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Gera a imagem destacada e faz upload para a biblioteca de mídia.
     *
     * @param string $prompt  Prompt para geração.
     * @param string $title   Título do post (para alt text).
     * @param int    $post_id ID do post (para associar).
     * @return int|WP_Error   Attachment ID ou erro.
     */
    public function generate_featured_image( $prompt, $title, $post_id = 0 ) {
        $result = $this->ai->generate_image( $prompt, array(
            'quality' => 'standard',
            'style'   => 'natural',
        ) );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $this->upload_image_from_url(
            $result['url'],
            sanitize_title( $title ) . '-featured',
            $title,
            $post_id
        );
    }

    /**
     * Gera imagens internas do artigo.
     *
     * @param array  $prompts Array de prompts.
     * @param string $title   Título do post.
     * @param int    $post_id ID do post.
     * @return array Array de attachment IDs ou erros.
     */
    public function generate_internal_images( $prompts, $title, $post_id = 0 ) {
        $results = array();

        foreach ( $prompts as $index => $prompt ) {
            $result = $this->ai->generate_image( $prompt, array(
                'size'    => '1792x1024',
                'quality' => 'standard',
                'style'   => 'natural',
            ) );

            if ( is_wp_error( $result ) ) {
                $results[] = $result;
                continue;
            }

            $attachment_id = $this->upload_image_from_url(
                $result['url'],
                sanitize_title( $title ) . '-image-' . ( $index + 1 ),
                $title . ' - ' . __( 'Imagem', 'blog-automatico' ) . ' ' . ( $index + 1 ),
                $post_id
            );

            $results[] = $attachment_id;
        }

        return $results;
    }

    /**
     * Faz download e upload de uma imagem a partir de URL.
     *
     * @param string $url      URL da imagem.
     * @param string $filename Nome do arquivo (sem extensão).
     * @param string $alt_text Texto alternativo.
     * @param int    $post_id  ID do post para associar.
     * @return int|WP_Error    Attachment ID ou erro.
     */
    private function upload_image_from_url( $url, $filename, $alt_text, $post_id = 0 ) {
        // Incluir funções necessárias do WordPress
        if ( ! function_exists( 'media_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // Download temporário da imagem
        $tmp_file = download_url( $url, 60 );

        if ( is_wp_error( $tmp_file ) ) {
            return new WP_Error(
                'ba_download_error',
                __( 'Falha ao fazer download da imagem gerada.', 'blog-automatico' ),
                $tmp_file->get_error_message()
            );
        }

        // Preparar arquivo para sideload
        $file_array = array(
            'name'     => sanitize_file_name( $filename . '.png' ),
            'tmp_name' => $tmp_file,
        );

        // Fazer sideload (move para a biblioteca de mídia)
        $attachment_id = media_handle_sideload( $file_array, $post_id );

        // Limpar arquivo temporário se houve erro
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp_file );
            return $attachment_id;
        }

        // Definir alt text
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt_text ) );

        // Definir título e caption
        wp_update_post( array(
            'ID'           => $attachment_id,
            'post_title'   => sanitize_text_field( $alt_text ),
            'post_excerpt' => sanitize_text_field( $alt_text ), // Caption
        ) );

        return $attachment_id;
    }

    /**
     * Retorna a URL de um attachment.
     *
     * @param int $attachment_id ID do attachment.
     * @return string|false URL ou false.
     */
    public function get_image_url( $attachment_id ) {
        if ( is_wp_error( $attachment_id ) ) {
            return false;
        }
        return wp_get_attachment_url( $attachment_id );
    }
}
