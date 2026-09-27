<?php
/**
 * Construtor de templates Elementor.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Elementor_Builder {

    /**
     * Instância singleton.
     *
     * @var BA_Elementor_Builder
     */
    private static $instance = null;

    /**
     * Construtor.
     */
    public function __construct() {}

    /**
     * Retorna instância singleton.
     *
     * @return BA_Elementor_Builder
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Verifica se o Elementor Pro está ativo.
     *
     * @return bool
     */
    public function is_elementor_active() {
        return defined( 'ELEMENTOR_VERSION' ) && is_plugin_active( 'elementor/elementor.php' );
    }

    /**
     * Verifica se o Elementor Pro está ativo.
     *
     * @return bool
     */
    public function is_elementor_pro_active() {
        return defined( 'ELEMENTOR_PRO_VERSION' ) && is_plugin_active( 'elementor-pro/elementor-pro.php' );
    }

    /**
     * Aplica um template Elementor ao post.
     *
     * @param int    $post_id       ID do post.
     * @param array  $content       Conteúdo gerado.
     * @param array  $image_ids     IDs dos attachments de imagens.
     * @param string $template_name Nome do template.
     * @return bool|WP_Error
     */
    public function apply_template( $post_id, $content, $image_ids = array(), $template_name = 'default' ) {
        if ( ! $this->is_elementor_active() ) {
            return new WP_Error(
                'ba_elementor_inactive',
                __( 'Elementor não está ativo. O conteúdo será salvo como HTML padrão.', 'blog-automatico' )
            );
        }

        // Carregar template JSON
        $template_data = $this->load_template( $template_name );
        if ( is_wp_error( $template_data ) ) {
            return $template_data;
        }

        // Preparar dados para substituição
        $replacements = $this->prepare_replacements( $content, $image_ids );

        // Construir elementos Elementor
        $elementor_data = $this->build_elementor_data( $content, $image_ids );

        // Salvar dados do Elementor no post
        $this->save_elementor_data( $post_id, $elementor_data );

        return true;
    }

    /**
     * Constrói os dados Elementor dinamicamente.
     *
     * @param array $content   Conteúdo gerado.
     * @param array $image_ids IDs das imagens.
     * @return array
     */
    private function build_elementor_data( $content, $image_ids ) {
        $elements = array();

        // --- Seção: Hero / Imagem Destacada ---
        if ( ! empty( $image_ids['featured'] ) && ! is_wp_error( $image_ids['featured'] ) ) {
            $featured_url = wp_get_attachment_url( $image_ids['featured'] );
            $elements[]   = $this->create_section( array(
                $this->create_column( '100', array(
                    $this->create_widget( 'image', array(
                        'image'      => array(
                            'url' => $featured_url,
                            'id'  => $image_ids['featured'],
                        ),
                        'image_size' => 'full',
                        'align'      => 'center',
                        '_css_classes' => 'ba-featured-image',
                    )),
                )),
            ), array(
                'padding' => array(
                    'top'    => '0',
                    'right'  => '0',
                    'bottom' => '40',
                    'left'   => '0',
                    'unit'   => 'px',
                ),
            ));
        }

        // --- Seção: Introdução ---
        $elements[] = $this->create_section( array(
            $this->create_column( '100', array(
                $this->create_widget( 'text-editor', array(
                    'editor' => wpautop( wp_kses_post( $content['introducao'] ) ),
                    '_css_classes' => 'ba-introduction',
                )),
            )),
        ), array(
            'content_width' => array( 'size' => 800, 'unit' => 'px' ),
        ));

        // --- Seções do conteúdo ---
        $internal_image_index = 0;
        foreach ( $content['secoes'] as $index => $secao ) {
            $section_elements = array();

            // Título H2
            $section_elements[] = $this->create_widget( 'heading', array(
                'title'       => $secao['titulo_h2'],
                'header_size' => 'h2',
                'align'       => 'left',
                '_css_classes' => 'ba-section-heading',
            ));

            // Conteúdo da seção
            $section_elements[] = $this->create_widget( 'text-editor', array(
                'editor' => wpautop( wp_kses_post( $secao['conteudo'] ) ),
            ));

            // Imagem interna (se disponível)
            if ( isset( $image_ids['internal'][ $internal_image_index ] ) && ! is_wp_error( $image_ids['internal'][ $internal_image_index ] ) ) {
                $img_id  = $image_ids['internal'][ $internal_image_index ];
                $img_url = wp_get_attachment_url( $img_id );
                if ( $img_url ) {
                    $section_elements[] = $this->create_widget( 'image', array(
                        'image'      => array(
                            'url' => $img_url,
                            'id'  => $img_id,
                        ),
                        'image_size' => 'large',
                        'align'      => 'center',
                    ));
                }
                $internal_image_index++;
            }

            // Subseções H3
            if ( isset( $secao['subsecoes'] ) && is_array( $secao['subsecoes'] ) ) {
                foreach ( $secao['subsecoes'] as $sub ) {
                    $section_elements[] = $this->create_widget( 'heading', array(
                        'title'       => $sub['titulo_h3'],
                        'header_size' => 'h3',
                        'align'       => 'left',
                    ));
                    $section_elements[] = $this->create_widget( 'text-editor', array(
                        'editor' => wpautop( wp_kses_post( $sub['conteudo'] ) ),
                    ));
                }
            }

            // Divisor entre seções
            $section_elements[] = $this->create_widget( 'divider', array(
                'style' => 'solid',
                'color' => '#e0e0e0',
                'gap'   => array( 'size' => 30, 'unit' => 'px' ),
            ));

            $elements[] = $this->create_section( array(
                $this->create_column( '100', $section_elements ),
            ), array(
                'content_width' => array( 'size' => 800, 'unit' => 'px' ),
            ));
        }

        // --- Seção: Conclusão ---
        $elements[] = $this->create_section( array(
            $this->create_column( '100', array(
                $this->create_widget( 'heading', array(
                    'title'       => __( 'Conclusão', 'blog-automatico' ),
                    'header_size' => 'h2',
                    'align'       => 'left',
                )),
                $this->create_widget( 'text-editor', array(
                    'editor' => wpautop( wp_kses_post( $content['conclusao'] ) ),
                    '_css_classes' => 'ba-conclusion',
                )),
            )),
        ), array(
            'content_width' => array( 'size' => 800, 'unit' => 'px' ),
        ));

        // --- Seção: FAQ ---
        if ( isset( $content['faq'] ) && is_array( $content['faq'] ) && ! empty( $content['faq'] ) ) {
            $faq_items = array();
            foreach ( $content['faq'] as $faq ) {
                $faq_items[] = array(
                    'tab_title'   => $faq['pergunta'],
                    'tab_content' => wp_kses_post( $faq['resposta'] ),
                );
            }

            $elements[] = $this->create_section( array(
                $this->create_column( '100', array(
                    $this->create_widget( 'heading', array(
                        'title'       => __( 'Perguntas Frequentes', 'blog-automatico' ),
                        'header_size' => 'h2',
                        'align'       => 'left',
                    )),
                    $this->create_widget( 'toggle', array(
                        'tabs' => $faq_items,
                        '_css_classes' => 'ba-faq-section',
                    )),
                )),
            ), array(
                'content_width' => array( 'size' => 800, 'unit' => 'px' ),
            ));
        }

        return $elements;
    }

    /**
     * Cria um elemento section do Elementor.
     *
     * @param array $columns  Colunas da seção.
     * @param array $settings Configurações extras.
     * @return array
     */
    private function create_section( $columns, $settings = array() ) {
        return array(
            'id'       => $this->generate_element_id(),
            'elType'   => 'section',
            'settings' => wp_parse_args( $settings, array(
                'structure' => '10', // Uma coluna
            )),
            'elements' => $columns,
        );
    }

    /**
     * Cria um elemento column do Elementor.
     *
     * @param string $width    Largura em porcentagem.
     * @param array  $widgets  Widgets da coluna.
     * @param array  $settings Configurações extras.
     * @return array
     */
    private function create_column( $width, $widgets, $settings = array() ) {
        return array(
            'id'       => $this->generate_element_id(),
            'elType'   => 'column',
            'settings' => wp_parse_args( $settings, array(
                '_column_size' => intval( $width ),
            )),
            'elements' => $widgets,
        );
    }

    /**
     * Cria um widget do Elementor.
     *
     * @param string $type     Tipo do widget.
     * @param array  $settings Configurações do widget.
     * @return array
     */
    private function create_widget( $type, $settings = array() ) {
        return array(
            'id'         => $this->generate_element_id(),
            'elType'     => 'widget',
            'widgetType' => $type,
            'settings'   => $settings,
            'elements'   => array(),
        );
    }

    /**
     * Gera um ID único para elementos Elementor.
     *
     * @return string
     */
    private function generate_element_id() {
        return substr( bin2hex( random_bytes( 4 ) ), 0, 7 );
    }

    /**
     * Carrega um template JSON.
     *
     * @param string $template_name Nome do template.
     * @return array|WP_Error
     */
    private function load_template( $template_name ) {
        $file = BA_PLUGIN_DIR . 'templates/elementor/' . sanitize_file_name( $template_name ) . '-blog-template.json';

        if ( ! file_exists( $file ) ) {
            // Se o template específico não existe, tentar o default
            $file = BA_PLUGIN_DIR . 'templates/elementor/default-blog-template.json';
        }

        if ( ! file_exists( $file ) ) {
            // Retornar array vazio - será construído dinamicamente
            return array();
        }

        $json = file_get_contents( $file );
        $data = json_decode( $json, true );

        if ( null === $data ) {
            return new WP_Error(
                'ba_template_error',
                __( 'Template Elementor inválido.', 'blog-automatico' )
            );
        }

        return $data;
    }

    /**
     * Prepara os dados de substituição para o template.
     *
     * @param array $content   Conteúdo.
     * @param array $image_ids IDs das imagens.
     * @return array
     */
    private function prepare_replacements( $content, $image_ids ) {
        $replacements = array(
            '{{TITULO}}'       => esc_html( $content['titulo'] ),
            '{{INTRODUCAO}}'   => wpautop( wp_kses_post( $content['introducao'] ) ),
            '{{CONCLUSAO}}'    => wpautop( wp_kses_post( $content['conclusao'] ) ),
        );

        // Imagem destacada
        if ( ! empty( $image_ids['featured'] ) && ! is_wp_error( $image_ids['featured'] ) ) {
            $replacements['{{IMAGEM_DESTAQUE_URL}}'] = wp_get_attachment_url( $image_ids['featured'] );
            $replacements['{{IMAGEM_DESTAQUE_ID}}']  = $image_ids['featured'];
        }

        return $replacements;
    }

    /**
     * Salva os dados do Elementor no post meta.
     *
     * @param int   $post_id       ID do post.
     * @param array $elementor_data Dados do Elementor.
     */
    private function save_elementor_data( $post_id, $elementor_data ) {
        // Salvar dados do Elementor
        update_post_meta( $post_id, '_elementor_data', wp_json_encode( $elementor_data ) );

        // Marcar que o post usa Elementor
        update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );

        // Definir template do Elementor
        update_post_meta( $post_id, '_wp_page_template', 'elementor_header_footer' );

        // Versão do Elementor
        if ( defined( 'ELEMENTOR_VERSION' ) ) {
            update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
        }

        // CSS customizado inline (será regenerado pelo Elementor)
        update_post_meta( $post_id, '_elementor_css', '' );

        // Forçar regeneração do CSS do Elementor
        if ( class_exists( '\Elementor\Plugin' ) ) {
            $post_css = \Elementor\Core\Files\CSS\Post::create( $post_id );
            if ( $post_css ) {
                $post_css->update();
            }
        }
    }

    /**
     * Aplica conteúdo como HTML padrão (fallback sem Elementor).
     *
     * @param int   $post_id ID do post.
     * @param array $content Conteúdo.
     * @param array $image_ids IDs das imagens.
     * @return bool
     */
    public function apply_html_fallback( $post_id, $content, $image_ids = array() ) {
        $generator = BA_Content_Generator::get_instance();
        $html      = $generator->content_to_html( $content );

        // Inserir imagens internas no HTML
        if ( isset( $image_ids['internal'] ) && is_array( $image_ids['internal'] ) ) {
            $sections = explode( '</h2>', $html );
            $new_html = '';
            $img_idx  = 0;

            foreach ( $sections as $idx => $section ) {
                $new_html .= $section;
                if ( $idx < count( $sections ) - 1 ) {
                    $new_html .= '</h2>';

                    // Adicionar imagem após H2 se disponível
                    if ( isset( $image_ids['internal'][ $img_idx ] ) && ! is_wp_error( $image_ids['internal'][ $img_idx ] ) ) {
                        $img_url   = wp_get_attachment_url( $image_ids['internal'][ $img_idx ] );
                        $img_alt   = get_post_meta( $image_ids['internal'][ $img_idx ], '_wp_attachment_image_alt', true );
                        $new_html .= "\n" . '<figure class="ba-internal-image"><img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $img_alt ) . '" loading="lazy" />' . '</figure>' . "\n";
                        $img_idx++;
                    }
                }
            }

            $html = $new_html;
        }

        wp_update_post( array(
            'ID'           => $post_id,
            'post_content' => $html,
        ));

        return true;
    }
}
