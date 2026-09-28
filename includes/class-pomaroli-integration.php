<?php
/**
 * Camada de integração direta com o Sistema Pomaroli (Pomaroli Questões).
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Pomaroli_Integration {

    /**
     * Instância singleton.
     *
     * @var BA_Pomaroli_Integration
     */
    private static $instance = null;

    /**
     * Post Type das questões.
     */
    const CPT_QUESTAO = 'questao';

    /**
     * Taxonomias do Sistema Pomaroli.
     */
    const TAX_BANCA         = 'banca';
    const TAX_DISCIPLINA    = 'disciplina';
    const TAX_ASSUNTO       = 'assunto';
    const TAX_INSTITUICAO   = 'instituicao';
    const TAX_CARGO         = 'cargo';
    const TAX_ANO           = 'ano';
    const TAX_CARREIRA      = 'carreira';
    const TAX_AREA_FORMACAO = 'area_formacao';
    const TAX_ESCOLARIDADE  = 'escolaridade';
    const TAX_DIFICULDADE   = 'dificuldade';

    /**
     * Duração do cache em segundos (12 horas).
     */
    const CACHE_TTL = 43200;

    /**
     * Retorna instância singleton.
     *
     * @return BA_Pomaroli_Integration
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Construtor privado.
     */
    private function __construct() {}

    /**
     * Verifica com segurança se o Sistema Pomaroli está ativo e disponível.
     *
     * @return bool
     */
    public function is_active() {
        return (
            class_exists( 'Interage_Questoes' ) ||
            post_type_exists( self::CPT_QUESTAO ) ||
            taxonomy_exists( self::TAX_BANCA ) ||
            taxonomy_exists( self::TAX_DISCIPLINA )
        );
    }

    /**
     * Obtém resumo geral das entidades do Sistema Pomaroli.
     *
     * @param bool $force_refresh Forçar atualização do cache.
     * @return array
     */
    public function get_stats( $force_refresh = false ) {
        if ( ! $this->is_active() ) {
            return array(
                'active'            => false,
                'total_questoes'    => 0,
                'total_bancas'      => 0,
                'total_disciplinas' => 0,
                'total_assuntos'    => 0,
                'total_instituicoes'=> 0,
                'last_sync'         => null,
            );
        }

        $transient_key = 'ba_pomaroli_stats_summary';
        if ( ! $force_refresh ) {
            $cached = get_transient( $transient_key );
            if ( false !== $cached ) {
                return $cached;
            }
        }

        $total_questoes = (int) wp_count_posts( self::CPT_QUESTAO )->publish;
        $total_bancas   = wp_count_terms( array( 'taxonomy' => self::TAX_BANCA, 'hide_empty' => false ) );
        $total_disc     = wp_count_terms( array( 'taxonomy' => self::TAX_DISCIPLINA, 'hide_empty' => false ) );
        $total_assuntos = wp_count_terms( array( 'taxonomy' => self::TAX_ASSUNTO, 'hide_empty' => false ) );
        $total_inst     = wp_count_terms( array( 'taxonomy' => self::TAX_INSTITUICAO, 'hide_empty' => false ) );

        $stats = array(
            'active'            => true,
            'total_questoes'    => is_numeric( $total_questoes ) ? $total_questoes : 0,
            'total_bancas'      => is_numeric( $total_bancas ) ? (int) $total_bancas : 0,
            'total_disciplinas' => is_numeric( $total_disc ) ? (int) $total_disc : 0,
            'total_assuntos'    => is_numeric( $total_assuntos ) ? (int) $total_assuntos : 0,
            'total_instituicoes'=> is_numeric( $total_inst ) ? (int) $total_inst : 0,
            'last_sync'         => current_time( 'mysql' ),
        );

        set_transient( $transient_key, $stats, self::CACHE_TTL );
        return $stats;
    }

    /**
     * Limpa o cache de dados e contagens da integração Pomaroli.
     */
    public function clear_cache() {
        delete_transient( 'ba_pomaroli_stats_summary' );
        delete_transient( 'ba_pomaroli_opportunities_list' );

        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ba_pomaroli_%' OR option_name LIKE '_transient_timeout_ba_pomaroli_%' OR option_name LIKE '_transient_ba_pom_%' OR option_name LIKE '_transient_timeout_ba_pom_%'" );
    }

    /**
     * Retorna lista de bancas cadastradas no Sistema Pomaroli.
     *
     * @param array $args Argumentos adicionais para get_terms.
     * @return WP_Term[]
     */
    public function get_bancas( $args = array() ) {
        if ( ! $this->is_active() || ! taxonomy_exists( self::TAX_BANCA ) ) {
            return array();
        }

        $defaults = array(
            'taxonomy'   => self::TAX_BANCA,
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
        );
        $parsed = wp_parse_args( $args, $defaults );

        return get_terms( $parsed );
    }

    /**
     * Retorna uma banca por ID ou slug.
     *
     * @param int|string $id_or_slug
     * @return WP_Term|null
     */
    public function get_banca( $id_or_slug ) {
        if ( ! $this->is_active() || ! taxonomy_exists( self::TAX_BANCA ) ) {
            return null;
        }

        $field = is_numeric( $id_or_slug ) ? 'id' : 'slug';
        $term  = get_term_by( $field, $id_or_slug, self::TAX_BANCA );
        return ( $term instanceof WP_Term ) ? $term : null;
    }

    /**
     * Retorna lista de disciplinas cadastradas no Sistema Pomaroli.
     *
     * @param array $args
     * @return WP_Term[]
     */
    public function get_disciplinas( $args = array() ) {
        if ( ! $this->is_active() || ! taxonomy_exists( self::TAX_DISCIPLINA ) ) {
            return array();
        }

        $defaults = array(
            'taxonomy'   => self::TAX_DISCIPLINA,
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
        );
        $parsed = wp_parse_args( $args, $defaults );

        return get_terms( $parsed );
    }

    /**
     * Retorna uma disciplina por ID ou slug.
     *
     * @param int|string $id_or_slug
     * @return WP_Term|null
     */
    public function get_disciplina( $id_or_slug ) {
        if ( ! $this->is_active() || ! taxonomy_exists( self::TAX_DISCIPLINA ) ) {
            return null;
        }

        $field = is_numeric( $id_or_slug ) ? 'id' : 'slug';
        $term  = get_term_by( $field, $id_or_slug, self::TAX_DISCIPLINA );
        return ( $term instanceof WP_Term ) ? $term : null;
    }

    /**
     * Retorna lista de assuntos cadastrados.
     *
     * @param int|string $disciplina_id_or_slug Opcional: filtrar assuntos da disciplina.
     * @param array      $args
     * @return WP_Term[]
     */
    public function get_assuntos( $disciplina_id_or_slug = 0, $args = array() ) {
        if ( ! $this->is_active() || ! taxonomy_exists( self::TAX_ASSUNTO ) ) {
            return array();
        }

        $defaults = array(
            'taxonomy'   => self::TAX_ASSUNTO,
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
        );

        if ( ! empty( $disciplina_id_or_slug ) ) {
            $disciplina = $this->get_disciplina( $disciplina_id_or_slug );
            if ( $disciplina ) {
                // Se a taxonomia assunto tiver parentesco hierárquico
                $defaults['parent'] = $disciplina->term_id;
            }
        }

        $parsed = wp_parse_args( $args, $defaults );
        return get_terms( $parsed );
    }

    /**
     * Retorna um assunto por ID ou slug.
     *
     * @param int|string $id_or_slug
     * @return WP_Term|null
     */
    public function get_assunto( $id_or_slug ) {
        if ( ! $this->is_active() || ! taxonomy_exists( self::TAX_ASSUNTO ) ) {
            return null;
        }

        $field = is_numeric( $id_or_slug ) ? 'id' : 'slug';
        $term  = get_term_by( $field, $id_or_slug, self::TAX_ASSUNTO );
        return ( $term instanceof WP_Term ) ? $term : null;
    }

    /**
     * Retorna lista de instituições/concursos do Sistema Pomaroli.
     *
     * @param array $args
     * @return WP_Term[]
     */
    public function get_instituicoes( $args = array() ) {
        if ( ! $this->is_active() || ! taxonomy_exists( self::TAX_INSTITUICAO ) ) {
            return array();
        }

        $defaults = array(
            'taxonomy'   => self::TAX_INSTITUICAO,
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
        );
        $parsed = wp_parse_args( $args, $defaults );

        return get_terms( $parsed );
    }

    /**
     * Retorna lista de cargos.
     *
     * @param array $args
     * @return WP_Term[]
     */
    public function get_cargos( $args = array() ) {
        if ( ! $this->is_active() || ! taxonomy_exists( self::TAX_CARGO ) ) {
            return array();
        }

        $defaults = array(
            'taxonomy'   => self::TAX_CARGO,
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
        );
        $parsed = wp_parse_args( $args, $defaults );

        return get_terms( $parsed );
    }

    /**
     * Conta a quantidade REAL de questões válidas para uma combinação de termos.
     *
     * @param array $terms_by_tax Ex: ['banca' => 'cebraspe', 'disciplina' => 'portugues', 'assunto' => 'crase']
     * @return int
     */
    public function get_questoes_count( $terms_by_tax = array() ) {
        if ( ! $this->is_active() ) {
            return 0;
        }

        $terms_by_tax = array_filter( (array) $terms_by_tax );
        if ( empty( $terms_by_tax ) ) {
            $count = wp_count_posts( self::CPT_QUESTAO )->publish;
            return (int) $count;
        }

        // Chave de cache transient baseada nos parâmetros
        ksort( $terms_by_tax );
        $cache_key = 'ba_pomaroli_cnt_' . md5( wp_json_encode( $terms_by_tax ) );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return (int) $cached;
        }

        global $wpdb;

        $joins  = array();
        $wheres = array( "p.post_type = '" . self::CPT_QUESTAO . "'", "p.post_status = 'publish'" );
        $join_index = 0;

        foreach ( $terms_by_tax as $tax => $term_slug ) {
            if ( empty( $term_slug ) ) {
                continue;
            }
            $join_index++;
            $alias_tr = "tr{$join_index}";
            $alias_tt = "tt{$join_index}";
            $alias_t  = "t{$join_index}";

            $joins[] = "INNER JOIN {$wpdb->term_relationships} AS {$alias_tr} ON p.ID = {$alias_tr}.object_id";
            $joins[] = "INNER JOIN {$wpdb->term_taxonomy} AS {$alias_tt} ON {$alias_tr}.term_taxonomy_id = {$alias_tt}.term_taxonomy_id";
            $joins[] = "INNER JOIN {$wpdb->terms} AS {$alias_t} ON {$alias_tt}.term_id = {$alias_t}.term_id";

            $wheres[] = $wpdb->prepare( "{$alias_tt}.taxonomy = %s", $tax );
            if ( is_numeric( $term_slug ) ) {
                $wheres[] = $wpdb->prepare( "{$alias_t}.term_id = %d", (int) $term_slug );
            } else {
                $wheres[] = $wpdb->prepare( "{$alias_t}.slug = %s", sanitize_title( $term_slug ) );
            }
        }

        // Excluir anuladas e desatualizadas para manter precisão de conteúdo estudável
        $wheres[] = "NOT EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} AS pm_anu 
            WHERE pm_anu.post_id = p.ID 
            AND pm_anu.meta_key = '_is_anulada' 
            AND pm_anu.meta_value IN ('1', 'yes', 'true')
        )";
        $wheres[] = "NOT EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} AS pm_des 
            WHERE pm_des.post_id = p.ID 
            AND pm_des.meta_key = '_is_desatualizada' 
            AND pm_des.meta_value IN ('1', 'yes', 'true')
        )";

        $sql = "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} AS p " . implode( ' ', $joins ) . ' WHERE ' . implode( ' AND ', $wheres );

        $count = (int) $wpdb->get_var( $sql );
        set_transient( $cache_key, $count, self::CACHE_TTL );

        return $count;
    }

    /**
     * Busca uma amostra de questões reais para enriquecer o contexto da IA.
     *
     * @param array $terms_by_tax Combinação de filtros.
     * @param int   $limit Quantidade de questões reais a retornar.
     * @return array
     */
    public function get_sample_questoes( $terms_by_tax = array(), $limit = 3 ) {
        if ( ! $this->is_active() ) {
            return array();
        }

        $tax_query = array( 'relation' => 'AND' );
        foreach ( $terms_by_tax as $tax => $slug ) {
            if ( ! empty( $slug ) ) {
                $tax_query[] = array(
                    'taxonomy' => $tax,
                    'field'    => is_numeric( $slug ) ? 'term_id' : 'slug',
                    'terms'    => $slug,
                );
            }
        }

        $args = array(
            'post_type'      => self::CPT_QUESTAO,
            'post_status'    => 'publish',
            'posts_per_page' => max( 1, min( 5, (int) $limit ) ),
            'tax_query'      => $tax_query,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array( 'key' => '_is_anulada', 'value' => array( '1', 'yes', 'true' ), 'compare' => 'NOT IN' ),
                    array( 'key' => '_is_anulada', 'compare' => 'NOT EXISTS' ),
                ),
                array(
                    'relation' => 'OR',
                    array( 'key' => '_is_desatualizada', 'value' => array( '1', 'yes', 'true' ), 'compare' => 'NOT IN' ),
                    array( 'key' => '_is_desatualizada', 'compare' => 'NOT EXISTS' ),
                ),
            ),
            'orderby'        => 'rand', // Amostra variada
        );

        $query = new WP_Query( $args );
        $samples = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();

                $tipo      = get_post_meta( $post_id, '_tipo_questao', true );
                $gabarito  = get_post_meta( $post_id, '_opcao_correta', true );
                $prof_com  = get_post_meta( $post_id, '_comentario_professor', true );
                $ano_term  = wp_get_post_terms( $post_id, self::TAX_ANO, array( 'fields' => 'names' ) );
                $cargo_term= wp_get_post_terms( $post_id, self::TAX_CARGO, array( 'fields' => 'names' ) );

                $alternativas = array();
                if ( 'certo_errado' === $tipo ) {
                    $alternativas['C'] = 'Certo';
                    $alternativas['E'] = 'Errado';
                } else {
                    foreach ( array( 'A', 'B', 'C', 'D', 'E' ) as $letra ) {
                        $txt = get_post_meta( $post_id, '_opcao_' . strtolower( $letra ), true );
                        if ( ! empty( $txt ) ) {
                            $alternativas[ $letra ] = wp_strip_all_tags( $txt );
                        }
                    }
                }

                $samples[] = array(
                    'id'                   => $post_id,
                    'titulo'               => get_the_title( $post_id ),
                    'enunciado'            => wp_strip_all_tags( get_the_content( null, false, $post_id ) ),
                    'tipo'                 => $tipo ? $tipo : 'multipla_escolha',
                    'alternativas'         => $alternativas,
                    'gabarito'             => $gabarito,
                    'comentario_professor' => ! empty( $prof_com ) ? wp_strip_all_tags( $prof_com ) : '',
                    'ano'                  => ! empty( $ano_term ) ? $ano_term[0] : '',
                    'cargo'                => ! empty( $cargo_term ) ? $cargo_term[0] : '',
                    'link_questao'         => get_permalink( $post_id ),
                );
            }
            wp_reset_postdata();
        }

        return $samples;
    }

    /**
     * Retorna a URL canônica pública de um termo do Sistema Pomaroli.
     *
     * @param WP_Term|string|int $term
     * @param string             $taxonomy
     * @return string
     */
    public function get_term_public_url( $term, $taxonomy = '' ) {
        if ( is_string( $term ) || is_numeric( $term ) ) {
            $t = get_term_by( is_numeric( $term ) ? 'id' : 'slug', $term, $taxonomy );
            if ( $t instanceof WP_Term ) {
                $term = $t;
            } else {
                return home_url( '/' . $taxonomy . '/' . sanitize_title( $term ) . '/' );
            }
        }

        if ( $term instanceof WP_Term ) {
            $link = get_term_link( $term );
            if ( ! is_wp_error( $link ) ) {
                return $link;
            }
        }

        return home_url( '/' );
    }

    /**
     * Retorna a URL canônica pública da área de questões da Pomaroli.
     *
     * @param array $filters Filtros opcionais para query string.
     * @return string
     */
    public function get_questoes_page_url( $filters = array() ) {
        $base = get_post_type_archive_link( self::CPT_QUESTAO );
        if ( ! $base ) {
            $base = home_url( '/questoes/' );
        }

        if ( ! empty( $filters ) ) {
            $clean_filters = array();
            foreach ( $filters as $k => $v ) {
                if ( ! empty( $v ) ) {
                    $clean_filters[ $k ] = sanitize_text_field( $v );
                }
            }
            if ( ! empty( $clean_filters ) ) {
                $base = add_query_arg( $clean_filters, $base );
            }
        }

        return $base;
    }

    /**
     * Constrói o bloco de links internos e CTA para as questões reais da Pomaroli.
     *
     * @param array $context Contexto da entidade (banca, disciplina, assunto, count).
     * @return string Bloco HTML pronto para inclusão no artigo.
     */
    public function build_pomaroli_cta_block( $context ) {
        $banca_nome = ! empty( $context['banca_nome'] ) ? esc_html( $context['banca_nome'] ) : '';
        $disc_nome  = ! empty( $context['disciplina_nome'] ) ? esc_html( $context['disciplina_nome'] ) : '';
        $ass_nome   = ! empty( $context['assunto_nome'] ) ? esc_html( $context['assunto_nome'] ) : '';
        $count      = ! empty( $context['questoes_count'] ) ? (int) $context['questoes_count'] : 0;

        $filters = array();
        if ( ! empty( $context['banca_slug'] ) ) {
            $filters['banca'] = $context['banca_slug'];
        }
        if ( ! empty( $context['disciplina_slug'] ) ) {
            $filters['disciplina'] = $context['disciplina_slug'];
        }
        if ( ! empty( $context['assunto_slug'] ) ) {
            $filters['assunto'] = $context['assunto_slug'];
        }

        $questoes_url = $this->get_questoes_page_url( $filters );

        $links_internos = array();
        if ( ! empty( $context['banca_slug'] ) && ! empty( $banca_nome ) ) {
            $b_url = $this->get_term_public_url( $context['banca_slug'], self::TAX_BANCA );
            $links_internos[] = '<a href="' . esc_url( $b_url ) . '" target="_blank" rel="noopener">todas as questões da ' . $banca_nome . '</a>';
        }
        if ( ! empty( $context['disciplina_slug'] ) && ! empty( $disc_nome ) ) {
            $d_url = $this->get_term_public_url( $context['disciplina_slug'], self::TAX_DISCIPLINA );
            $links_internos[] = '<a href="' . esc_url( $d_url ) . '" target="_blank" rel="noopener">questões de ' . $disc_nome . '</a>';
        }
        if ( ! empty( $context['assunto_slug'] ) && ! empty( $ass_nome ) ) {
            $a_url = $this->get_term_public_url( $context['assunto_slug'], self::TAX_ASSUNTO );
            $links_internos[] = '<a href="' . esc_url( $a_url ) . '" target="_blank" rel="noopener">questões de ' . $ass_nome . '</a>';
        }

        $html  = "\n<!-- wp:group {\"className\":\"ba-pomaroli-cta-box\"} -->\n";
        $html .= '<div class="ba-pomaroli-cta-box" style="margin: 40px 0; padding: 28px; background: linear-gradient(135deg, #161b22 0%, #0d1117 100%); border: 1px solid #30363d; border-left: 4px solid #B4D443; border-radius: 10px; color: #c9d1d9; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">' . "\n";
        
        $html .= '<h3 style="margin-top:0; color: #f0f6fc; font-size: 20px; display: flex; align-items: center; gap: 8px;">';
        $html .= '<span style="color:#B4D443; font-size: 22px;">⚡</span> Pratique com Questões Reais na Plataforma Pomaroli</h3>' . "\n";

        if ( $count > 0 ) {
            $html .= '<p style="font-size: 15px; line-height: 1.6; color: #8b949e; margin-bottom: 16px;">Temos exatamente <strong style="color:#ffffff;">' . number_format_i18n( $count ) . ' questões cadastradas</strong> com filtros avançados, comentários de professores e estatísticas de resolução para acelerar sua aprovação.</p>' . "\n";
        } else {
            $html .= '<p style="font-size: 15px; line-height: 1.6; color: #8b949e; margin-bottom: 16px;">Treine com o nosso banco de questões de concursos públicos com comentários didáticos e acompanhamento de desempenho.</p>' . "\n";
        }

        if ( ! empty( $links_internos ) ) {
            $html .= '<p style="font-size: 14px; color: #8b949e; margin-bottom: 20px;">Navegue também por: ' . implode( ' • ', $links_internos ) . '.</p>' . "\n";
        }

        $html .= '<div style="margin-top: 20px;">';
        $html .= '<a href="' . esc_url( $questoes_url ) . '" class="ba-pomaroli-btn" target="_blank" rel="noopener" style="display: inline-block; background-color: #B4D443; color: #0a0a0a; font-weight: 700; font-size: 15px; padding: 12px 26px; border-radius: 6px; text-decoration: none; transition: transform 0.2s, background 0.2s;">Resolver Questões Agora →</a>';
        $html .= '</div>' . "\n";

        $html .= '</div>' . "\n";
        $html .= "<!-- /wp:group -->\n";

        return $html;
    }
}
