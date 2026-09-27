<?php
/**
 * Gerador de conteúdo via IA.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Content_Generator {

    /**
     * Instância do conector IA.
     *
     * @var BA_AI_Connector
     */
    private $ai;

    /**
     * Instância de settings.
     *
     * @var BA_Settings
     */
    private $settings;

    /**
     * Instância singleton.
     *
     * @var BA_Content_Generator
     */
    private static $instance = null;

    /**
     * Construtor.
     */
    public function __construct() {
        $this->ai       = BA_AI_Connector::get_instance();
        $this->settings = BA_Settings::get_instance();
    }

    /**
     * Retorna instância singleton.
     *
     * @return BA_Content_Generator
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Gera conteúdo completo a partir de uma ideia.
     *
     * @param string $idea    Ideia do usuário.
     * @param array  $options Opções de geração.
     * @return array|WP_Error Conteúdo estruturado ou erro.
     */
    public function generate( $idea, $options = array() ) {
        $tone     = isset( $options['tone'] ) ? $options['tone'] : $this->settings->get( 'ba_content_tone' );
        $language = isset( $options['language'] ) ? $options['language'] : $this->settings->get( 'ba_language' );
        $length   = isset( $options['length'] ) ? intval( $options['length'] ) : intval( $this->settings->get( 'ba_article_length' ) );

        $language_label = $this->get_language_label( $language );
        $tone_label     = $this->get_tone_label( $tone );

        $system_prompt = $this->build_system_prompt( $tone_label, $language_label, $length );
        $user_prompt   = $this->build_user_prompt( $idea, $tone_label, $language_label, $length );

        $result = $this->ai->chat_completion(
            $system_prompt,
            $user_prompt,
            array(
                'json_mode'  => true,
                'max_tokens' => $this->calculate_max_tokens( $length ),
                'temperature' => 0.7,
            )
        );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Parse do JSON retornado com higienização e reparo inteligente
        $content = $this->parse_json_response( $result['content'] );

        if ( is_wp_error( $content ) ) {
            return $content;
        }

        // Normalizar encoding de caracteres acentuados e quebras de linha
        $content = $this->normalize_content_encoding( $content );

        // Validar e normalizar campos obrigatórios
        $validation = $this->validate_content( $content );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        // Adicionar metadados
        $content['_meta'] = array(
            'tokens_used' => $result['tokens_used'],
            'model'       => $result['model'],
            'idea'        => $idea,
            'generated_at' => current_time( 'mysql' ),
        );

        return $content;
    }

    /**
     * Constrói o prompt do sistema.
     *
     * @param string $tone     Tom do conteúdo.
     * @param string $language Idioma.
     * @param int    $length   Comprimento do artigo.
     * @return string
     */
    private function build_system_prompt( $tone, $language, $length ) {
        $base = "Você é um redator profissional especialista em SEO tradicional e GEO (Generative Engine Optimization / Otimização para Citação em IAs como ChatGPT, Perplexity e Google AI Overviews). 
Seu trabalho é criar artigos de blog completos, altamente engajadores, ricos em dados e desenhados para que mecanismos de IA citem este artigo como FONTE AUTORIDADE quando usuários fizerem buscas sobre o assunto.

Regras importantes de redação e GEO (Citação por IAs):
- Conteúdo 100% original, aprofundado, com informações ricas e sem enrolação
- Otimizado para citação por IAs (GEO): inclua respostas diretas e conceituais de 1-2 frases no início de cada seção H2/H3
- Usar dados específicos, nomes de órgãos, leis/decretos, números e fatos verificáveis para passar autoridade máxima
- Estruturar H2 e H3 usando perguntas reais de busca em linguagem natural
- Usar listas com marcadores e resumos diretos que facilitam a extração de trechos por IAs e robôs de busca
- Usar linguagem natural e fluida em {$language}
- Tom: {$tone}
- Densidade de palavra-chave principal entre 1-2%
- SEMPRE use caracteres acentuados reais em UTF-8 nativo (ex: é, ã, ç, ó). NUNCA use sequências de escape como \u00e9 ou \u00e3
- Gerar prompts de imagem EM INGLÊS e detalhados para DALL-E 3
- RESPONDER APENAS COM JSON VÁLIDO e bem formatado, sem texto ou blocos markdown (```json) fora do objeto JSON
- Em valores de texto com múltiplos parágrafos, use apenas a sequência \\n para separar parágrafos. NUNCA insira quebras de linha brutas (Enter) dentro das aspas do JSON";

        // Integrar Treinamento de IA
        $training = $this->settings->get_training_data();
        $training_prompt = $this->build_training_prompt( $training );

        if ( ! empty( $training_prompt ) ) {
            $base .= "\n\n" . $training_prompt;
        }

        return $base;
    }

    /**
     * Constrói instruções de treinamento para humanização do texto.
     *
     * @param array $training Dados de treinamento.
     * @return string Instruções adicionais para o prompt.
     */
    private function build_training_prompt( $training ) {
        $parts = array();

        // Estilo de escrita
        $style_map = array(
            'natural'      => 'Escreva de forma natural e fluida, como se estivesse explicando algo para um amigo. Use linguagem do dia-a-dia.',
            'jornalistico' => 'Escreva como um jornalista: objetivo, factual e direto ao ponto. Use a pirâmide invertida.',
            'storytelling'  => 'Use narrativas e histórias para envolver o leitor. Comece seções com mini-histórias ou cenários reais.',
            'academico'    => 'Escreva com rigor acadêmico, citando fontes e usando terminologia técnica quando necessário.',
            'blogueiro'    => 'Escreva como um blogueiro experiente: com opinião, personalidade e um toque pessoal. Fale na primeira pessoa.',
            'copywriting'  => 'Use técnicas de copywriting: gere curiosidade, destaque benefícios e guie o leitor para uma ação.',
        );
        if ( ! empty( $training['writing_style'] ) && isset( $style_map[ $training['writing_style'] ] ) ) {
            $parts[] = "ESTILO DE ESCRITA: " . $style_map[ $training['writing_style'] ];
        }

        // Persona
        if ( ! empty( $training['persona'] ) ) {
            $parts[] = "PERSONA DO ESCRITOR (adote esta identidade na escrita):\n" . $training['persona'];
        }

        // Humanização
        $humanize_rules = array();
        $level = ! empty( $training['humanize_level'] ) ? $training['humanize_level'] : 'high';

        if ( 'medium' === $level || 'high' === $level ) {
            $humanize_rules[] = "Varie o tamanho das frases: misture frases curtas (5-8 palavras) com médias (12-18 palavras) e ocasionalmente longas (25+ palavras)";
            $humanize_rules[] = "NÃO comece parágrafos consecutivos com a mesma estrutura gramatical";
            $humanize_rules[] = "Use perguntas retóricas de vez em quando para engajar o leitor";
        }
        if ( 'high' === $level ) {
            $humanize_rules[] = "Inclua opiniões pessoais sutis e posicionamentos (ex: 'na minha experiência', 'o que eu vejo acontecer muito é')";
            $humanize_rules[] = "Use ocasionalmente frases incompletas ou interrupções naturais (ex: 'E sabe o que aconteceu? Nada.')";
            $humanize_rules[] = "Varie o início dos parágrafos: às vezes com dado, às vezes com pergunta, às vezes com afirmação direta, às vezes com exemplo";
            $humanize_rules[] = "NÃO use conectivos no início de TODOS os parágrafos — deixe alguns começarem abruptamente";
            $humanize_rules[] = "Alterne entre explicar, exemplificar e questionar dentro de cada seção";
        }

        // Variedade de frases
        if ( ! empty( $training['sentence_variety'] ) && 'high' === $training['sentence_variety'] ) {
            $humanize_rules[] = "VARIE RADICALMENTE o ritmo: após uma frase longa explicativa, coloque uma frase curta e impactante";
        }

        // Estilo de parágrafo
        $para_map = array(
            'short'  => "Use parágrafos curtos de 1 a 3 frases. Facilite a leitura em tela.",
            'varied' => "Varie o tamanho dos parágrafos: alguns com 1-2 frases, outros com 3-5 frases. Nunca mantenha um padrão fixo.",
            'long'   => "Use parágrafos mais densos de 4-6 frases para aprofundar cada ponto.",
        );
        if ( ! empty( $training['paragraph_style'] ) && isset( $para_map[ $training['paragraph_style'] ] ) ) {
            $humanize_rules[] = $para_map[ $training['paragraph_style'] ];
        }

        // Evitar padrões de IA
        if ( ! empty( $training['avoid_patterns'] ) && '1' === $training['avoid_patterns'] ) {
            $humanize_rules[] = "NUNCA use estrutura previsível (intro genérica → lista → conclusão genérica)";
            $humanize_rules[] = "NÃO faça listas com exatamente o mesmo número de itens em todas as seções";
            $humanize_rules[] = "EVITE introduções que começam com 'Neste artigo vamos...' ou 'Você já se perguntou...'";
            $humanize_rules[] = "NÃO use conclusões que começam com 'Em conclusão' ou 'Em resumo'";
            $humanize_rules[] = "EVITE excesso de palavras de transição — humanos não usam conectivos em toda frase";
        }

        if ( ! empty( $humanize_rules ) ) {
            $parts[] = "REGRAS DE HUMANIZAÇÃO (ESSENCIAL — siga rigorosamente):\n- " . implode( "\n- ", $humanize_rules );
        }

        // Palavras proibidas
        if ( ! empty( $training['forbidden_words'] ) ) {
            $words = array_filter( array_map( 'trim', explode( "\n", $training['forbidden_words'] ) ) );
            if ( ! empty( $words ) ) {
                $parts[] = "PALAVRAS E EXPRESSÕES PROIBIDAS (NUNCA use nenhuma destas):\n\"" . implode( "\", \"", array_slice( $words, 0, 40 ) ) . "\"";
            }
        }

        // Palavras preferidas
        if ( ! empty( $training['preferred_words'] ) ) {
            $words = array_filter( array_map( 'trim', explode( "\n", $training['preferred_words'] ) ) );
            if ( ! empty( $words ) ) {
                $parts[] = "EXPRESSÕES PREFERIDAS (use estas naturalmente ao longo do texto):\n\"" . implode( "\", \"", array_slice( $words, 0, 30 ) ) . "\"";
            }
        }

        // Textos de referência
        if ( ! empty( $training['reference_texts'] ) ) {
            $ref = mb_substr( $training['reference_texts'], 0, 2000 );
            $parts[] = "TEXTOS DE REFERÊNCIA DO ESCRITOR (imite este estilo, vocabulário e ritmo):\n---\n" . $ref . "\n---\nAnalise o estilo acima e replique: tamanho médio de frases, nível de formalidade, uso de gírias, estrutura de argumentação e tom emocional.";
        }

        // Regras personalizadas
        if ( ! empty( $training['custom_rules'] ) ) {
            $parts[] = "INSTRUÇÕES ADICIONAIS DO USUÁRIO:\n" . $training['custom_rules'];
        }

        return implode( "\n\n", $parts );
    }


    /**
     * Constrói o prompt do usuário.
     *
     * @param string $idea     Ideia do artigo.
     * @param string $tone     Tom do conteúdo.
     * @param string $language Idioma.
     * @param int    $length   Comprimento.
     * @return string
     */
    private function build_user_prompt( $idea, $tone, $language, $length ) {
        $min_sections = max( 4, intval( $length / 300 ) );

        return "Com base na ideia: \"{$idea}\"

Gere um artigo completo para blog com aproximadamente {$length} palavras.

Responda com o seguinte JSON:

{
  \"titulo\": \"Título otimizado para SEO (máximo 60 caracteres)\",
  \"meta_description\": \"Meta description atrativa (máximo 160 caracteres)\",
  \"slug\": \"slug-otimizado-para-url\",
  \"palavra_chave_principal\": \"palavra-chave principal do artigo\",
  \"palavras_chave_secundarias\": [\"keyword2\", \"keyword3\", \"keyword4\"],
  \"categorias_sugeridas\": [\"Categoria 1\", \"Categoria 2\"],
  \"tags_sugeridas\": [\"tag1\", \"tag2\", \"tag3\", \"tag4\", \"tag5\"],
  \"introducao\": \"Parágrafo introdutório envolvente (150-200 palavras). Deve captar a atenção do leitor e apresentar o que será abordado.\",
  \"secoes\": [
    {
      \"titulo_h2\": \"Subtítulo da seção otimizado para SEO\",
      \"conteudo\": \"Conteúdo completo desta seção (200-400 palavras)\",
      \"subsecoes\": [
        {
          \"titulo_h3\": \"Sub-subtítulo\",
          \"conteudo\": \"Conteúdo da subseção (100-200 palavras)\"
        }
      ]
    }
  ],
  \"conclusao\": \"Parágrafo de conclusão com CTA (call to action) (100-150 palavras)\",
  \"faq\": [
    {
      \"pergunta\": \"Pergunta frequente relevante?\",
      \"resposta\": \"Resposta objetiva e útil (50-100 palavras)\"
    }
  ],
  \"prompt_imagem_destaque\": \"Detailed English prompt for DALL-E 3 to generate a featured image that represents the article topic. Professional, high quality, no text in the image.\",
  \"prompts_imagens_internas\": [
    \"Detailed English prompt for an internal article image related to section 1\",
    \"Detailed English prompt for an internal article image related to section 2\"
  ]
}

Requisitos:
- Mínimo de {$min_sections} seções H2
- Cada seção deve ter pelo menos 1 subseção H3
- Incluir pelo menos 3 FAQs relevantes
- O FAQ deve usar perguntas que as pessoas realmente pesquisam
- Prompts de imagem devem ser em inglês, detalhados e específicos
- Tom: {$tone}
- Idioma do conteúdo: {$language}";
    }

    /**
     * Valida o conteúdo gerado.
     *
     * @param array $content Conteúdo a validar.
     * @return true|WP_Error
     */
    private function validate_content( &$content ) {
        if ( ! is_array( $content ) ) {
            return new WP_Error( 'ba_invalid_content_array', __( 'Conteúdo retornado não é um array válido.', 'blog-automatico' ) );
        }

        // Normalização de sinônimos/aliases comuns que provedores de IA podem gerar
        $aliases = array(
            'titulo'                 => array( 'title', 'headline', 'heading', 'titulo_artigo' ),
            'meta_description'       => array( 'description', 'meta', 'seo_description', 'metadescription' ),
            'slug'                   => array( 'url_slug', 'permalink', 'post_slug' ),
            'introducao'             => array( 'introduction', 'intro', 'lead', 'paragrafo_inicial' ),
            'secoes'                 => array( 'sections', 'chapters', 'body', 'conteudo_secoes' ),
            'conclusao'              => array( 'conclusion', 'summary', 'outro', 'consideracoes_finais' ),
            'prompt_imagem_destaque' => array( 'featured_image_prompt', 'image_prompt', 'prompts_imagem_destaque' ),
        );

        foreach ( $aliases as $canonical => $synonyms ) {
            if ( empty( $content[ $canonical ] ) ) {
                foreach ( $synonyms as $synonym ) {
                    if ( ! empty( $content[ $synonym ] ) ) {
                        $content[ $canonical ] = $content[ $synonym ];
                        break;
                    }
                }
            }
        }

        // Se slug não foi fornecido, gerar a partir do título
        if ( empty( $content['slug'] ) && ! empty( $content['titulo'] ) ) {
            $content['slug'] = sanitize_title( $content['titulo'] );
        }

        // Se prompt_imagem_destaque não foi fornecido, criar um a partir do título
        if ( empty( $content['prompt_imagem_destaque'] ) && ! empty( $content['titulo'] ) ) {
            $content['prompt_imagem_destaque'] = 'Professional featured image representing: ' . $content['titulo'] . ', high quality 8k, modern blog hero visual';
        }

        $required_fields = array(
            'titulo',
            'meta_description',
            'introducao',
            'secoes',
            'conclusao',
        );

        foreach ( $required_fields as $field ) {
            if ( ! isset( $content[ $field ] ) || empty( $content[ $field ] ) ) {
                return new WP_Error(
                    'ba_missing_field',
                    sprintf(
                        /* translators: %s: nome do campo */
                        __( 'Campo obrigatório ausente na resposta da IA: %s', 'blog-automatico' ),
                        $field
                    )
                );
            }
        }

        // Validar que secoes é um array
        if ( ! is_array( $content['secoes'] ) || empty( $content['secoes'] ) ) {
            return new WP_Error(
                'ba_invalid_sections',
                __( 'O conteúdo precisa ter pelo menos uma seção.', 'blog-automatico' )
            );
        }

        // Normalizar cada seção
        foreach ( $content['secoes'] as $i => $secao ) {
            if ( is_string( $secao ) ) {
                $content['secoes'][ $i ] = array(
                    'titulo_h2' => __( 'Seção ', 'blog-automatico' ) . ( $i + 1 ),
                    'conteudo'  => $secao,
                );
            } else if ( is_array( $secao ) ) {
                if ( empty( $secao['titulo_h2'] ) ) {
                    if ( ! empty( $secao['h2'] ) ) {
                        $content['secoes'][ $i ]['titulo_h2'] = $secao['h2'];
                    } else if ( ! empty( $secao['title'] ) ) {
                        $content['secoes'][ $i ]['titulo_h2'] = $secao['title'];
                    } else if ( ! empty( $secao['subtitulo'] ) ) {
                        $content['secoes'][ $i ]['titulo_h2'] = $secao['subtitulo'];
                    }
                }

                if ( empty( $secao['conteudo'] ) ) {
                    if ( ! empty( $secao['content'] ) ) {
                        $content['secoes'][ $i ]['conteudo'] = $secao['content'];
                    } else if ( ! empty( $secao['text'] ) ) {
                        $content['secoes'][ $i ]['conteudo'] = $secao['text'];
                    } else if ( ! empty( $secao['body'] ) ) {
                        $content['secoes'][ $i ]['conteudo'] = $secao['body'];
                    }
                }
            }
        }

        return true;
    }

    /**
     * Normaliza recursivamente os dados decodificados para garantir UTF-8 limpo sem sequências de escape.
     *
     * @param mixed $data Dados a normalizar.
     * @return mixed
     */
    private function normalize_content_encoding( $data ) {
        if ( is_array( $data ) ) {
            foreach ( $data as $k => $v ) {
                $data[ $k ] = $this->normalize_content_encoding( $v );
            }
            return $data;
        }

        if ( is_string( $data ) ) {
            // Decodifica sequências Unicode escapadas literais (ex: \u00e9, \u00e3) se existirem
            if ( false !== strpos( $data, '\u' ) ) {
                $converted = preg_replace_callback( '/\\\\u([0-9a-fA-F]{4})/', function ( $match ) {
                    return mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2BE' );
                }, $data );

                if ( null !== $converted ) {
                    $data = $converted;
                }
            }

            // Normaliza quebras de linha literais residuais (\r\n e \n)
            $data = str_replace( array( "\\r\\n", "\\n" ), "\n", $data );
            return $data;
        }

        return $data;
    }

    /**
     * Converte o conteúdo estruturado em HTML.
     *
     * @param array $content Conteúdo estruturado.
     * @return string HTML do artigo.
     */
    public function content_to_html( $content ) {
        $html = '';

        // Introdução
        $html .= '<div class="ba-introduction">' . "\n";
        $html .= wpautop( wp_kses_post( $content['introducao'] ) );
        $html .= '</div>' . "\n\n";

        // Seções
        foreach ( $content['secoes'] as $secao ) {
            $html .= '<h2>' . esc_html( $secao['titulo_h2'] ) . '</h2>' . "\n";
            $html .= wpautop( wp_kses_post( $secao['conteudo'] ) ) . "\n";

            // Subseções
            if ( isset( $secao['subsecoes'] ) && is_array( $secao['subsecoes'] ) ) {
                foreach ( $secao['subsecoes'] as $sub ) {
                    $html .= '<h3>' . esc_html( $sub['titulo_h3'] ) . '</h3>' . "\n";
                    $html .= wpautop( wp_kses_post( $sub['conteudo'] ) ) . "\n";
                }
            }
        }

        // Conclusão
        $html .= '<div class="ba-conclusion">' . "\n";
        $html .= '<h2>' . esc_html__( 'Conclusão', 'blog-automatico' ) . '</h2>' . "\n";
        $html .= wpautop( wp_kses_post( $content['conclusao'] ) );
        $html .= '</div>' . "\n\n";

        // FAQ
        if ( isset( $content['faq'] ) && is_array( $content['faq'] ) && ! empty( $content['faq'] ) ) {
            $html .= '<div class="ba-faq">' . "\n";
            $html .= '<h2>' . esc_html__( 'Perguntas Frequentes', 'blog-automatico' ) . '</h2>' . "\n";

            foreach ( $content['faq'] as $item ) {
                $html .= '<div class="ba-faq-item">' . "\n";
                $html .= '<h3>' . esc_html( $item['pergunta'] ) . '</h3>' . "\n";
                $html .= wpautop( wp_kses_post( $item['resposta'] ) );
                $html .= '</div>' . "\n";
            }

            $html .= '</div>' . "\n";
        }

        return $html;
    }

    /**
     * Calcula o max_tokens baseado no comprimento do artigo.
     *
     * @param int $length Comprimento em palavras.
     * @return int
     */
    private function calculate_max_tokens( $length ) {
        // Aproximação: 1 palavra ≈ 1.5 tokens + overhead do JSON
        return min( intval( $length * 2.5 ) + 1000, 16384 );
    }

    /**
     * Interpreta e limpa a resposta JSON da IA de forma altamente robusta.
     *
     * @param string $raw_response Resposta bruta da IA.
     * @return array|WP_Error Dados estruturados ou erro.
     */
    private function parse_json_response( $raw_response ) {
        if ( empty( $raw_response ) || ! is_string( $raw_response ) ) {
            return new WP_Error(
                'ba_empty_response',
                __( 'Resposta da IA veio vazia.', 'blog-automatico' )
            );
        }

        // 1. Remover BOM e caracteres nulos/invisíveis
        $cleaned = preg_replace( '/^[\xEF\xBB\xBF\xFE\xFF]/', '', $raw_response );
        $cleaned = str_replace( array( "\xEF\xBB\xBF", "\xE2\x80\x8B" ), '', $cleaned );
        $cleaned = trim( $cleaned );

        // 2. Extrair bloco JSON se estiver envelopado por markdown code fences (```json ... ```)
        if ( preg_match( '/```(?:json)?\s*([\s\S]*?)\s*```/i', $cleaned, $matches ) ) {
            $cleaned = trim( $matches[1] );
        }

        // 3. Localizar delimitadores principais de objeto JSON ({ ... })
        $first_brace = strpos( $cleaned, '{' );
        $last_brace  = strrpos( $cleaned, '}' );

        if ( false !== $first_brace && false !== $last_brace && $last_brace > $first_brace ) {
            $json_str = substr( $cleaned, $first_brace, $last_brace - $first_brace + 1 );
        } else {
            $json_str = $cleaned;
        }

        // Tentativa 1: Decode direto do JSON limpo
        $content = json_decode( $json_str, true );

        // Tentativa 2: Higienizar quebras de linha brutas, tabulações e vírgulas sobressalentes
        if ( null === $content ) {
            // Remover vírgulas sobressalentes antes de fechar objetos ou arrays (ex: { "a": 1, })
            $json_sanitized = preg_replace( '/,\s*([\}\]])/', '$1', $json_str );

            // Converter quebras de linha brutas (CR/LF) dentro de valores de string JSON para \n escapado
            $json_sanitized = preg_replace_callback( '/"(?:[^"\\\\]|\\\\.)*"/s', function( $match ) {
                $str = $match[0];
                $str = str_replace( array( "\r\n", "\r", "\n" ), '\n', $str );
                $str = str_replace( "\t", '\t', $str );
                return $str;
            }, $json_sanitized );

            $content = json_decode( $json_sanitized, true );
        }

        // Tentativa 3: Reparo de JSON truncado (se chaves ou colchetes foram cortados por limite de tokens)
        if ( null === $content ) {
            $json_repaired = $this->repair_truncated_json( $json_str );
            if ( ! empty( $json_repaired ) ) {
                $content = json_decode( $json_repaired, true );
            }
        }

        if ( null === $content || ! is_array( $content ) ) {
            $last_error = json_last_error_msg();
            if ( function_exists( 'error_log' ) ) {
                error_log( '[Blog Automático] Erro JSON IA: ' . $last_error );
                error_log( '[Blog Automático] Resposta bruta: ' . substr( $raw_response, 0, 1000 ) );
            }

            return new WP_Error(
                'ba_parse_error',
                sprintf(
                    /* translators: %s: mensagem de erro do JSON */
                    __( 'Falha ao interpretar a resposta da IA (%s). Tente novamente.', 'blog-automatico' ),
                    $last_error
                ),
                array( 'raw_response' => $raw_response )
            );
        }

        return $content;
    }

    /**
     * Tenta reparar um JSON que foi truncado por limite de tokens da API.
     *
     * @param string $json String JSON truncada.
     * @return string JSON reparado.
     */
    private function repair_truncated_json( $json ) {
        $json = trim( $json );

        if ( 0 !== strpos( $json, '{' ) ) {
            return $json;
        }

        // Recuar até o último ponto de encerramento de elemento ou aspas
        $last_valid = max(
            strrpos( $json, '"' ),
            strrpos( $json, '}' ),
            strrpos( $json, ']' ),
            strrpos( $json, ',' )
        );

        if ( false !== $last_valid && $last_valid > 10 ) {
            $json = substr( $json, 0, $last_valid + 1 );
        }

        $json = rtrim( $json, " \t\n\r\0\x0B," );

        // Balancear chaves e colchetes
        $open_braces   = substr_count( $json, '{' ) - substr_count( $json, '}' );
        $open_brackets = substr_count( $json, '[' ) - substr_count( $json, ']' );

        while ( $open_brackets > 0 ) {
            $json .= ']';
            $open_brackets--;
        }
        while ( $open_braces > 0 ) {
            $json .= '}';
            $open_braces--;
        }

        return $json;
    }

    /**
     * Retorna o label do idioma.
     *
     * @param string $code Código do idioma.
     * @return string
     */
    private function get_language_label( $code ) {
        $map = array(
            'pt_BR' => 'português brasileiro',
            'en_US' => 'inglês',
            'es_ES' => 'espanhol',
        );
        return isset( $map[ $code ] ) ? $map[ $code ] : 'português brasileiro';
    }

    /**
     * Retorna o label do tom.
     *
     * @param string $code Código do tom.
     * @return string
     */
    private function get_tone_label( $code ) {
        $map = array(
            'formal'         => 'formal e profissional',
            'informal'       => 'informal e amigável',
            'tecnico'        => 'técnico e detalhado',
            'conversacional' => 'conversacional e próximo',
            'persuasivo'     => 'persuasivo e envolvente',
        );
        return isset( $map[ $code ] ) ? $map[ $code ] : 'informal e amigável';
    }
}
