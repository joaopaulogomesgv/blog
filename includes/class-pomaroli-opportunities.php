<?php
/**
 * Gerador e gerenciador de oportunidades de SEO programático para a Pomaroli.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Pomaroli_Opportunities {

    private static $instance = null;
    private $integration;
    private $settings;

    /**
     * Chave de opção para configurações do módulo SEO Pomaroli.
     */
    const OPTION_KEY = 'ba_pomaroli_settings';

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->integration = BA_Pomaroli_Integration::get_instance();
        $this->settings    = BA_Settings::get_instance();
    }

    /**
     * Retorna os 8 tipos de conteúdo estruturados com metadados e templates de títulos.
     *
     * @return array
     */
    public static function get_content_types() {
        return array(
            'guia_banca' => array(
                'label'       => __( 'Guia de Banca', 'blog-automatico' ),
                'description' => __( 'Visão geral da banca, estilo das provas, pegadinhas e estatísticas reais.', 'blog-automatico' ),
                'category'    => 'Bancas',
                'title_patterns' => array(
                    'Guia Completo da Banca {banca}: Perfil das Provas, Estilo e Dicas de Estudo',
                    'Como Funciona a Banca {banca}: Tudo Sobre as Questões e Critérios de Correção',
                ),
            ),
            'guia_concurso' => array(
                'label'       => __( 'Guia de Concurso', 'blog-automatico' ),
                'description' => __( 'Preparação focada no órgão/concurso com disciplinas e perfil.', 'blog-automatico' ),
                'category'    => 'Concursos Públicos',
                'title_patterns' => array(
                    'Concurso {instituicao}: Guia Definitivo de Preparação e Questões Mais Cobradas',
                    'Como Passar no Concurso {instituicao}: Matérias Essenciais e Estratégia de Prova',
                ),
            ),
            'guia_disciplina' => array(
                'label'       => __( 'Guia de Disciplina', 'blog-automatico' ),
                'description' => __( 'Panorama geral da disciplina para concursos e pesos nos editais.', 'blog-automatico' ),
                'category'    => 'Disciplinas',
                'title_patterns' => array(
                    '{disciplina} para Concursos Públicos: O Que Mais Cai e Como Estudar',
                    'Como Gabaritar {disciplina} em Concursos Públicos: Roteiro Prático de Estudos',
                ),
            ),
            'guia_assunto' => array(
                'label'       => __( 'Guia de Assunto', 'blog-automatico' ),
                'description' => __( 'Aprofundamento didático em um tópico específico com regras e macetes.', 'blog-automatico' ),
                'category'    => 'Estudos',
                'title_patterns' => array(
                    '{assunto} ({disciplina}): Teoria Essencial, Regras e Dicas para Concursos',
                    'Como Dominar {assunto} para Provas de Concursos Públicos',
                ),
            ),
            'questoes_comentadas' => array(
                'label'       => __( 'Questões Comentadas', 'blog-automatico' ),
                'description' => __( 'Análise aprofundada de questões com gabarito explicado e pegadinhas.', 'blog-automatico' ),
                'category'    => 'Questões',
                'title_patterns' => array(
                    'Questões Comentadas de {disciplina} da {banca}: {assunto}',
                    '{banca} e {disciplina}: Questões Resolvidas e Comentadas Passo a Passo',
                ),
            ),
            'estrategia_estudo' => array(
                'label'       => __( 'Estratégia de Estudo', 'blog-automatico' ),
                'description' => __( 'Ciclos de estudo, resolução de questões, revisão e métricas.', 'blog-automatico' ),
                'category'    => 'Estudos',
                'title_patterns' => array(
                    'Como Estudar {disciplina} para a Banca {banca}: Método Eficiente de Aprovação',
                    'Plano de Estudos de {disciplina} Focado na Banca {banca}',
                ),
            ),
            'artigo_educacional' => array(
                'label'       => __( 'Artigo Educacional', 'blog-automatico' ),
                'description' => __( 'Explicações conceituais claras com comparativos e mapas mentais.', 'blog-automatico' ),
                'category'    => 'Disciplinas',
                'title_patterns' => array(
                    'Tudo Sobre {assunto}: Guia Explicativo para Concurseiros',
                    '{assunto} na Prática: Diferenças, Regras e Exemplos de Concursos',
                ),
            ),
            'guia_preparacao' => array(
                'label'       => __( 'Guia de Preparação', 'blog-automatico' ),
                'description' => __( 'Reta final e plano tático para resolver o volume de questões.', 'blog-automatico' ),
                'category'    => 'Estudos',
                'title_patterns' => array(
                    'Principais Assuntos de {disciplina} Cobrados pela Banca {banca}',
                    'Reta Final {banca}: Os Tópicos Mais Frequentes de {disciplina}',
                ),
            ),
        );
    }

    /**
     * Retorna as configurações do módulo SEO Pomaroli com valores padrão seguros.
     *
     * @return array
     */
    public function get_settings() {
        $defaults = array(
            'min_questoes'         => 10,        // Mínimo de questões reais para ser elegível
            'auto_generate'        => '0',       // Geração automática inicialmente OFF
            'auto_publish'         => '0',       // Publicação automática inicialmente OFF
            'post_status'          => 'draft',   // Sempre Rascunho por padrão
            'posts_per_day'        => 1,         // 1 post/dia
            'enable_internal_cta'  => '1',       // Inserir box com link/CTA da Pomaroli no artigo
            'include_real_samples' => '1',       // Enviar amostras de questões reais para a IA
            'last_sync'            => current_time( 'mysql' ),
        );

        $saved = get_option( self::OPTION_KEY, array() );
        return wp_parse_args( $saved, $defaults );
    }

    /**
     * Atualiza as configurações do módulo.
     *
     * @param array $new_settings
     * @return bool
     */
    public function update_settings( $new_settings ) {
        $current = $this->get_settings();
        $updated = array_merge( $current, $new_settings );
        $updated['min_questoes']  = max( 1, intval( $updated['min_questoes'] ) );
        $updated['posts_per_day'] = max( 1, intval( $updated['posts_per_day'] ) );
        $updated['post_status']   = in_array( $updated['post_status'], array( 'draft', 'publish', 'pending' ), true ) ? $updated['post_status'] : 'draft';
        $updated['last_sync']     = current_time( 'mysql' );

        return update_option( self::OPTION_KEY, $updated );
    }

    /**
     * Gera lista de oportunidades de SEO a partir dos dados reais da Pomaroli.
     *
     * @param bool $force_refresh Forçar recálculo ignorando cache.
     * @return array
     */
    public function generate_opportunities( $force_refresh = false ) {
        if ( ! $this->integration->is_active() ) {
            return array();
        }

        $cache_key = 'ba_pomaroli_opportunities_list';
        if ( ! $force_refresh ) {
            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }
        }

        $config      = $this->get_settings();
        $min_count   = (int) $config['min_questoes'];
        $content_types = self::get_content_types();

        $bancas       = $this->integration->get_bancas( array( 'number' => 20 ) );
        $disciplinas  = $this->integration->get_disciplinas( array( 'number' => 25 ) );
        $instituicoes = $this->integration->get_instituicoes( array( 'number' => 15 ) );

        $opportunities = array();

        // 1. Oportunidades de Guia de Banca (apenas bancas com volume >= min_count)
        foreach ( $bancas as $banca ) {
            $count = (int) $banca->count;
            if ( $count < $min_count ) {
                continue;
            }

            $hash = md5( 'guia_banca_' . $banca->slug );
            $existing_post = $this->find_existing_post_by_hash( $hash );

            $title = "Guia Completo da Banca {$banca->name}: Perfil das Provas, Estilo e Dicas de Estudo";
            $idea  = "Guia da Banca {$banca->name} para concursos públicos, explicando formato das provas, pegadinhas, estilo e como resolver questões.";

            $opportunities[] = array(
                'hash'             => $hash,
                'type'             => 'guia_banca',
                'type_label'       => $content_types['guia_banca']['label'],
                'category'         => $content_types['guia_banca']['category'],
                'title'            => $title,
                'idea'             => $idea,
                'banca'            => $banca->name,
                'banca_slug'       => $banca->slug,
                'disciplina'       => '',
                'disciplina_slug'  => '',
                'assunto'          => '',
                'assunto_slug'     => '',
                'questoes_count'   => $count,
                'existing_post_id' => $existing_post ? $existing_post->ID : 0,
                'existing_status'  => $existing_post ? $existing_post->post_status : '',
            );
        }

        // 2. Oportunidades de Guia de Concurso/Instituição
        foreach ( $instituicoes as $inst ) {
            $count = (int) $inst->count;
            if ( $count < $min_count ) {
                continue;
            }

            $hash = md5( 'guia_concurso_' . $inst->slug );
            $existing_post = $this->find_existing_post_by_hash( $hash );

            $title = "Concurso {$inst->name}: Guia Definitivo de Preparação e Questões Mais Cobradas";
            $idea  = "Concurso do órgão {$inst->name}: guia completo de estudo, disciplinas mais cobradas, perfil da instituição e resolução de questões.";

            $opportunities[] = array(
                'hash'             => $hash,
                'type'             => 'guia_concurso',
                'type_label'       => $content_types['guia_concurso']['label'],
                'category'         => $content_types['guia_concurso']['category'],
                'title'            => $title,
                'idea'             => $idea,
                'instituicao'      => $inst->name,
                'instituicao_slug' => $inst->slug,
                'banca'            => '',
                'banca_slug'       => '',
                'disciplina'       => '',
                'disciplina_slug'  => '',
                'assunto'          => '',
                'assunto_slug'     => '',
                'questoes_count'   => $count,
                'existing_post_id' => $existing_post ? $existing_post->ID : 0,
                'existing_status'  => $existing_post ? $existing_post->post_status : '',
            );
        }

        // 3. Oportunidades de Guia de Disciplina
        foreach ( $disciplinas as $disc ) {
            $count = (int) $disc->count;
            if ( $count < $min_count ) {
                continue;
            }

            $hash = md5( 'guia_disciplina_' . $disc->slug );
            $existing_post = $this->find_existing_post_by_hash( $hash );

            $title = "{$disc->name} para Concursos Públicos: O Que Mais Cai e Como Estudar";
            $idea  = "Como estudar {$disc->name} para concursos públicos: panorama geral da matéria, tópicos essenciais e método de resolução de questões.";

            $opportunities[] = array(
                'hash'             => $hash,
                'type'             => 'guia_disciplina',
                'type_label'       => $content_types['guia_disciplina']['label'],
                'category'         => $content_types['guia_disciplina']['category'],
                'title'            => $title,
                'idea'             => $idea,
                'banca'            => '',
                'banca_slug'       => '',
                'disciplina'       => $disc->name,
                'disciplina_slug'  => $disc->slug,
                'assunto'          => '',
                'assunto_slug'     => '',
                'questoes_count'   => $count,
                'existing_post_id' => $existing_post ? $existing_post->ID : 0,
                'existing_status'  => $existing_post ? $existing_post->post_status : '',
            );
        }

        // 4. Cruzamento Banca + Disciplina (ex: Cebraspe + Português)
        $top_bancas = array_slice( $bancas, 0, 5 );
        $top_disc   = array_slice( $disciplinas, 0, 8 );

        foreach ( $top_bancas as $banca ) {
            foreach ( $top_disc as $disc ) {
                $count = $this->integration->get_questoes_count( array(
                    'banca'      => $banca->slug,
                    'disciplina' => $disc->slug,
                ) );

                if ( $count < $min_count ) {
                    continue;
                }

                // Tipo: Questões Comentadas
                $hash_qc = md5( 'questoes_comentadas_' . $banca->slug . '_' . $disc->slug );
                $existing_qc = $this->find_existing_post_by_hash( $hash_qc );

                $opportunities[] = array(
                    'hash'             => $hash_qc,
                    'type'             => 'questoes_comentadas',
                    'type_label'       => $content_types['questoes_comentadas']['label'],
                    'category'         => $content_types['questoes_comentadas']['category'],
                    'title'            => "Questões Comentadas de {$disc->name} da {$banca->name}",
                    'idea'             => "Questões comentadas de {$disc->name} aplicadas pela banca {$banca->name}, analisando pegadinhas recorrentes e gabarito.",
                    'banca'            => $banca->name,
                    'banca_slug'       => $banca->slug,
                    'disciplina'       => $disc->name,
                    'disciplina_slug'  => $disc->slug,
                    'assunto'          => '',
                    'assunto_slug'     => '',
                    'questoes_count'   => $count,
                    'existing_post_id' => $existing_qc ? $existing_qc->ID : 0,
                    'existing_status'  => $existing_qc ? $existing_qc->post_status : '',
                );

                // Tipo: Estratégia de Estudo
                $hash_ee = md5( 'estrategia_estudo_' . $banca->slug . '_' . $disc->slug );
                $existing_ee = $this->find_existing_post_by_hash( $hash_ee );

                $opportunities[] = array(
                    'hash'             => $hash_ee,
                    'type'             => 'estrategia_estudo',
                    'type_label'       => $content_types['estrategia_estudo']['label'],
                    'category'         => $content_types['estrategia_estudo']['category'],
                    'title'            => "Como Estudar {$disc->name} para a Banca {$banca->name}",
                    'idea'             => "Estratégia completa de estudos para {$disc->name} focada na banca {$banca->name}, incluindo cronograma e principais cobranças.",
                    'banca'            => $banca->name,
                    'banca_slug'       => $banca->slug,
                    'disciplina'       => $disc->name,
                    'disciplina_slug'  => $disc->slug,
                    'assunto'          => '',
                    'assunto_slug'     => '',
                    'questoes_count'   => $count,
                    'existing_post_id' => $existing_ee ? $existing_ee->ID : 0,
                    'existing_status'  => $existing_ee ? $existing_ee->post_status : '',
                );
            }
        }

        // 5. Cruzamento com Assuntos específicos (Banca + Disciplina + Assunto)
        $assuntos = $this->integration->get_assuntos( 0, array( 'number' => 15 ) );
        foreach ( $top_bancas as $banca ) {
            foreach ( $assuntos as $assunto ) {
                $count = $this->integration->get_questoes_count( array(
                    'banca'   => $banca->slug,
                    'assunto' => $assunto->slug,
                ) );

                if ( $count < $min_count ) {
                    continue;
                }

                $hash_ass = md5( 'guia_assunto_' . $banca->slug . '_' . $assunto->slug );
                $existing_ass = $this->find_existing_post_by_hash( $hash_ass );

                $opportunities[] = array(
                    'hash'             => $hash_ass,
                    'type'             => 'guia_assunto',
                    'type_label'       => $content_types['guia_assunto']['label'],
                    'category'         => $content_types['guia_assunto']['category'],
                    'title'            => "Questões de {$assunto->name} da {$banca->name}: Como a Banca Cobra",
                    'idea'             => "Como a banca {$banca->name} cobra {$assunto->name} em concursos públicos: análise de questões, armadilhas e teoria aplicada.",
                    'banca'            => $banca->name,
                    'banca_slug'       => $banca->slug,
                    'disciplina'       => '',
                    'disciplina_slug'  => '',
                    'assunto'          => $assunto->name,
                    'assunto_slug'     => $assunto->slug,
                    'questoes_count'   => $count,
                    'existing_post_id' => $existing_ass ? $existing_ass->ID : 0,
                    'existing_status'  => $existing_ass ? $existing_ass->post_status : '',
                );
            }
        }

        // Ordenar: primeiro as que ainda NÃO possuem post criado, depois por maior volume de questões
        usort( $opportunities, function( $a, $b ) {
            $a_has = $a['existing_post_id'] > 0 ? 1 : 0;
            $b_has = $b['existing_post_id'] > 0 ? 1 : 0;
            if ( $a_has !== $b_has ) {
                return $a_has - $b_has; // Não criados primeiro
            }
            return $b['questoes_count'] - $a['questoes_count'];
        } );

        set_transient( $cache_key, $opportunities, 21600 ); // 6 horas de cache
        return $opportunities;
    }

    /**
     * Localiza um post do blog que já tenha sido criado com o mesmo hash de combinação.
     *
     * @param string $hash
     * @return WP_Post|null
     */
    public function find_existing_post_by_hash( $hash ) {
        $posts = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'any',
            'meta_key'       => '_ba_pomaroli_combination_hash',
            'meta_value'     => $hash,
            'posts_per_page' => 1,
            'fields'         => 'all',
        ) );

        return ! empty( $posts ) ? $posts[0] : null;
    }

    /**
     * Envia uma oportunidade selecionada para a fila de agendamento (BA_Scheduler).
     *
     * @param string $hash Hash da oportunidade.
     * @return int|WP_Error ID na fila ou erro.
     */
    public function queue_opportunity( $hash ) {
        $opportunities = $this->generate_opportunities();
        $target = null;

        foreach ( $opportunities as $op ) {
            if ( $op['hash'] === $hash ) {
                $target = $op;
                break;
            }
        }

        if ( ! $target ) {
            return new WP_Error( 'ba_opportunity_not_found', __( 'Oportunidade não encontrada.', 'blog-automatico' ) );
        }

        if ( $target['existing_post_id'] > 0 ) {
            return new WP_Error( 'ba_opportunity_already_created', __( 'Já existe um artigo gerado para esta oportunidade.', 'blog-automatico' ) );
        }

        $scheduler = BA_Scheduler::get_instance();
        global $wpdb;
        $table_name = $wpdb->prefix . 'ba_scheduled_ideas';

        // Verificar se já não está na fila pelo HASH exato da oportunidade
        $hash_pattern = '%"hash":"' . $wpdb->esc_like( $target['hash'] ) . '"%';
        $exists_in_queue = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE idea LIKE %s AND status IN ('queued', 'processing')",
            $hash_pattern
        ) );

        if ( $exists_in_queue ) {
            return new WP_Error( 'ba_already_in_queue', __( 'Esta oportunidade já se encontra na fila de processamento.', 'blog-automatico' ) );
        }

        // Armazenar metadados da Pomaroli serializados na fila para passar para a geração
        $meta_data = array(
            'hash'           => $target['hash'],
            'type'           => $target['type'],
            'banca'          => $target['banca'],
            'banca_slug'     => $target['banca_slug'],
            'disciplina'     => $target['disciplina'],
            'disciplina_slug'=> $target['disciplina_slug'],
            'assunto'        => $target['assunto'],
            'assunto_slug'   => $target['assunto_slug'],
            'questoes_count' => $target['questoes_count'],
        );

        // Prepend context tag to idea
        $rich_idea = '[POMAROLI:' . wp_json_encode( $meta_data ) . '] ' . $target['title'];

        $result = $scheduler->add_to_queue( $rich_idea, '', 'default', 3 );
        return $result;
    }
}
