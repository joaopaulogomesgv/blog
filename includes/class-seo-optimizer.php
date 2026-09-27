<?php
/**
 * Otimizador de SEO.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_SEO_Optimizer {

    /**
     * Instância de settings.
     *
     * @var BA_Settings
     */
    private $settings;

    /**
     * Instância singleton.
     *
     * @var BA_SEO_Optimizer
     */
    private static $instance = null;

    /**
     * Construtor.
     */
    public function __construct() {
        $this->settings = BA_Settings::get_instance();
    }

    /**
     * Retorna instância singleton.
     *
     * @return BA_SEO_Optimizer
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Aplica todas as otimizações SEO a um post.
     *
     * @param int   $post_id ID do post.
     * @param array $content Conteúdo gerado pela IA.
     * @return bool
     */
    public function optimize( $post_id, $content ) {
        $seo_plugin = $this->settings->get( 'ba_seo_plugin' );

        // Aplicar meta tags conforme o plugin de SEO
        switch ( $seo_plugin ) {
            case 'yoast':
                $this->apply_yoast_seo( $post_id, $content );
                break;
            case 'rankmath':
                $this->apply_rankmath_seo( $post_id, $content );
                break;
            default:
                $this->apply_basic_seo( $post_id, $content );
                break;
        }

        // Aplicar Schema markup
        $this->apply_schema_markup( $post_id, $content );

        return true;
    }

    /**
     * Aplica SEO via Yoast SEO.
     *
     * @param int   $post_id ID do post.
     * @param array $content Conteúdo.
     */
    private function apply_yoast_seo( $post_id, $content ) {
        // Meta title
        update_post_meta( $post_id, '_yoast_wpseo_title', sanitize_text_field( $content['titulo'] ) );

        // Meta description
        update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( $content['meta_description'] ) );

        // Focus keyword
        if ( isset( $content['palavra_chave_principal'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( $content['palavra_chave_principal'] ) );
        }

        // Canonical URL (deixar em branco para usar o padrão)
        update_post_meta( $post_id, '_yoast_wpseo_canonical', '' );

        // Open Graph title
        update_post_meta( $post_id, '_yoast_wpseo_opengraph-title', sanitize_text_field( $content['titulo'] ) );

        // Open Graph description
        update_post_meta( $post_id, '_yoast_wpseo_opengraph-description', sanitize_text_field( $content['meta_description'] ) );

        // Twitter title
        update_post_meta( $post_id, '_yoast_wpseo_twitter-title', sanitize_text_field( $content['titulo'] ) );

        // Twitter description
        update_post_meta( $post_id, '_yoast_wpseo_twitter-description', sanitize_text_field( $content['meta_description'] ) );
    }

    /**
     * Aplica SEO via RankMath.
     *
     * @param int   $post_id ID do post.
     * @param array $content Conteúdo.
     */
    private function apply_rankmath_seo( $post_id, $content ) {
        // Meta title
        update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( $content['titulo'] ) );

        // Meta description
        update_post_meta( $post_id, 'rank_math_description', sanitize_text_field( $content['meta_description'] ) );

        // Focus keyword
        if ( isset( $content['palavra_chave_principal'] ) ) {
            update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( $content['palavra_chave_principal'] ) );
        }

        // Keywords secundárias
        if ( isset( $content['palavras_chave_secundarias'] ) && is_array( $content['palavras_chave_secundarias'] ) ) {
            $secondary = implode( ',', array_map( 'sanitize_text_field', $content['palavras_chave_secundarias'] ) );
            update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( $content['palavra_chave_principal'] ) . ',' . $secondary );
        }

        // Open Graph
        update_post_meta( $post_id, 'rank_math_og_title', sanitize_text_field( $content['titulo'] ) );
        update_post_meta( $post_id, 'rank_math_og_description', sanitize_text_field( $content['meta_description'] ) );

        // Twitter
        update_post_meta( $post_id, 'rank_math_twitter_title', sanitize_text_field( $content['titulo'] ) );
        update_post_meta( $post_id, 'rank_math_twitter_description', sanitize_text_field( $content['meta_description'] ) );

        // Pillar content
        update_post_meta( $post_id, 'rank_math_pillar_content', 'on' );
    }

    /**
     * Aplica SEO básico via post meta padrão.
     *
     * @param int   $post_id ID do post.
     * @param array $content Conteúdo.
     */
    private function apply_basic_seo( $post_id, $content ) {
        // Meta description básica (sem plugin SEO)
        update_post_meta( $post_id, '_ba_meta_description', sanitize_text_field( $content['meta_description'] ) );

        // Palavras-chave
        if ( isset( $content['palavra_chave_principal'] ) ) {
            update_post_meta( $post_id, '_ba_focus_keyword', sanitize_text_field( $content['palavra_chave_principal'] ) );
        }

        if ( isset( $content['palavras_chave_secundarias'] ) ) {
            update_post_meta( $post_id, '_ba_secondary_keywords', array_map( 'sanitize_text_field', $content['palavras_chave_secundarias'] ) );
        }
    }

    /**
     * Aplica Schema markup ao post.
     *
     * @param int   $post_id ID do post.
     * @param array $content Conteúdo.
     */
    private function apply_schema_markup( $post_id, $content ) {
        $schema = array();

        // Schema Article
        $schema['article'] = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'Article',
            'headline'    => $content['titulo'],
            'description' => $content['meta_description'],
            'datePublished'  => get_the_date( 'c', $post_id ),
            'dateModified'   => get_the_modified_date( 'c', $post_id ),
        );

        // Schema FAQ se existir
        if ( isset( $content['faq'] ) && is_array( $content['faq'] ) && ! empty( $content['faq'] ) ) {
            $faq_entities = array();
            foreach ( $content['faq'] as $item ) {
                $faq_entities[] = array(
                    '@type'          => 'Question',
                    'name'           => $item['pergunta'],
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => $item['resposta'],
                    ),
                );
            }

            $schema['faq'] = array(
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => $faq_entities,
            );
        }

        update_post_meta( $post_id, '_ba_schema_markup', $schema );
    }

    /**
     * Categorias oficiais e controladas do blog.
     *
     * @return array
     */
    public static function get_controlled_categories() {
        return array(
            'Concursos Públicos',
            'Bancas',
            'Disciplinas',
            'Estudos',
            'Questões',
            'Carreiras',
            'Notícias',
        );
    }

    /**
     * Processa categorias utilizando estritamente a lista de categorias controladas.
     * A IA NÃO cria categorias WordPress aleatórias automaticamente.
     *
     * @param array $categories Nomes de categorias sugeridas pela IA ou módulo.
     * @return array IDs das categorias válidas.
     */
    public function process_categories( $categories ) {
        $category_ids = array();
        $controlled   = self::get_controlled_categories();

        if ( ! is_array( $categories ) ) {
            $categories = ! empty( $categories ) ? array( $categories ) : array();
        }

        // Garantir que as categorias controladas existam no WordPress
        foreach ( $controlled as $ctrl_cat ) {
            if ( ! term_exists( $ctrl_cat, 'category' ) ) {
                wp_insert_term( $ctrl_cat, 'category' );
            }
        }

        foreach ( $categories as $cat_name ) {
            $cat_name = sanitize_text_field( trim( $cat_name ) );
            if ( empty( $cat_name ) ) {
                continue;
            }

            // Normalização e busca na lista controlada
            $matched_cat = '';
            foreach ( $controlled as $ctrl_cat ) {
                if ( 0 === strcasecmp( $ctrl_cat, $cat_name ) || false !== stripos( $cat_name, $ctrl_cat ) || false !== stripos( $ctrl_cat, $cat_name ) ) {
                    $matched_cat = $ctrl_cat;
                    break;
                }
            }

            // Mapeamentos semânticos comuns
            if ( empty( $matched_cat ) ) {
                $lower = strtolower( $cat_name );
                if ( str_contains( $lower, 'concurso' ) || str_contains( $lower, 'edital' ) || str_contains( $lower, 'vaga' ) ) {
                    $matched_cat = 'Concursos Públicos';
                } elseif ( str_contains( $lower, 'banca' ) || str_contains( $lower, 'cebraspe' ) || str_contains( $lower, 'fcc' ) || str_contains( $lower, 'fgv' ) || str_contains( $lower, 'vunesp' ) ) {
                    $matched_cat = 'Bancas';
                } elseif ( str_contains( $lower, 'direito' ) || str_contains( $lower, 'portugu' ) || str_contains( $lower, 'matem' ) || str_contains( $lower, 'inform' ) || str_contains( $lower, 'disciplina' ) ) {
                    $matched_cat = 'Disciplinas';
                } elseif ( str_contains( $lower, 'quest' ) || str_contains( $lower, 'gabarito' ) || str_contains( $lower, 'simulado' ) ) {
                    $matched_cat = 'Questões';
                } elseif ( str_contains( $lower, 'estud' ) || str_contains( $lower, 'prepara' ) || str_contains( $lower, 'revis' ) || str_contains( $lower, 'planejamento' ) ) {
                    $matched_cat = 'Estudos';
                } elseif ( str_contains( $lower, 'polic' ) || str_contains( $lower, 'fiscal' ) || str_contains( $lower, 'tribunal' ) || str_contains( $lower, 'carreira' ) ) {
                    $matched_cat = 'Carreiras';
                }
            }

            if ( ! empty( $matched_cat ) ) {
                $term = term_exists( $matched_cat, 'category' );
                if ( $term ) {
                    $term_id = is_array( $term ) ? intval( $term['term_id'] ) : intval( $term );
                    if ( ! in_array( $term_id, $category_ids, true ) ) {
                        $category_ids[] = $term_id;
                    }
                }
            }
        }

        // Se nenhuma categoria controlada deu match, atribuir uma padrão segura
        if ( empty( $category_ids ) ) {
            $default_term = term_exists( 'Concursos Públicos', 'category' );
            if ( $default_term ) {
                $category_ids[] = is_array( $default_term ) ? intval( $default_term['term_id'] ) : intval( $default_term );
            }
        }

        return $category_ids;
    }

    /**
     * Cria ou busca tags sugeridas.
     *
     * @param array $tags Nomes de tags.
     * @return array IDs das tags.
     */
    public function process_tags( $tags ) {
        $tag_ids = array();

        if ( ! is_array( $tags ) ) {
            return $tag_ids;
        }

        foreach ( $tags as $tag_name ) {
            $tag_name = sanitize_text_field( trim( $tag_name ) );
            if ( empty( $tag_name ) ) {
                continue;
            }

            $term = term_exists( $tag_name, 'post_tag' );

            if ( $term ) {
                $tag_ids[] = intval( $term['term_id'] );
            } else {
                $new_term = wp_insert_term( $tag_name, 'post_tag' );
                if ( ! is_wp_error( $new_term ) ) {
                    $tag_ids[] = intval( $new_term['term_id'] );
                }
            }
        }

        return $tag_ids;
    }
}
