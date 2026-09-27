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

        // Parse do JSON retornado
        $content = json_decode( $result['content'], true );

        if ( null === $content ) {
            return new WP_Error(
                'ba_parse_error',
                __( 'Falha ao interpretar a resposta da IA. Tente novamente.', 'blog-automatico' )
            );
        }

        // Normalizar encoding de caracteres acentuados e quebras de linha
        $content = $this->normalize_content_encoding( $content );

        // Validar campos obrigatórios
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
        $base = "Você é um redator profissional especialista em SEO e marketing de conteúdo. 
Seu trabalho é criar artigos de blog completos, otimizados para mecanismos de busca e altamente engajadores.

Regras importantes:
- Conteúdo 100% original e livre de plágio
- Usar linguagem natural e fluida em {$language}
- Tom: {$tone}
- Densidade de palavra-chave principal entre 1-2%
- Estruturar com H2 e H3 de forma hierárquica
- Cada seção deve ter conteúdo substancial e informativo
- SEMPRE use caracteres acentuados reais em UTF-8 nativo (ex: é, ã, ç, ó). NUNCA use sequências de escape como \u00e9 ou \u00e3
- Gerar prompts de imagem EM INGLÊS e detalhados para DALL-E 3
- RESPONDER APENAS COM JSON VÁLIDO, sem texto adicional fora do JSON";

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
    private function validate_content( $content ) {
        $required_fields = array(
            'titulo',
            'meta_description',
            'slug',
            'introducao',
            'secoes',
            'conclusao',
            'prompt_imagem_destaque',
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
