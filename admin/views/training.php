<?php
/**
 * Admin View: Treinamento de IA — Personalização de Escrita Humanizada.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = BA_Settings::get_instance();

// Campos do treinamento
$writing_style     = $settings->get( 'ba_training_writing_style' );
$custom_persona    = $settings->get( 'ba_training_persona' );
$reference_texts   = $settings->get( 'ba_training_reference_texts' );
$forbidden_words   = $settings->get( 'ba_training_forbidden_words' );
$preferred_words   = $settings->get( 'ba_training_preferred_words' );
$custom_rules      = $settings->get( 'ba_training_custom_rules' );
$humanize_level    = $settings->get( 'ba_training_humanize_level' );
$sentence_variety  = $settings->get( 'ba_training_sentence_variety' );
$paragraph_style   = $settings->get( 'ba_training_paragraph_style' );
$avoid_patterns    = $settings->get( 'ba_training_avoid_patterns' );

// Defaults
if ( empty( $writing_style ) )    $writing_style    = 'natural';
if ( empty( $humanize_level ) )   $humanize_level   = 'high';
if ( empty( $sentence_variety ) ) $sentence_variety  = 'high';
if ( empty( $paragraph_style ) )  $paragraph_style   = 'varied';
if ( empty( $avoid_patterns ) )   $avoid_patterns    = '1';

// Palavras e expressões que a IA tipicamente usa (sugestões pré-preenchidas)
$default_forbidden = "No entanto\nAlém disso\nVale ressaltar\nÉ importante destacar\nEm conclusão\nNeste artigo\nVamos explorar\nNeste contexto\nÉ fundamental\nEm suma\nPortanto\nDessa forma\nDiante disso\nConvém salientar\nCabe destacar\nNão podemos deixar de mencionar\nÉ imprescindível\nInquestionavelmente\nInegavelmente\nEm linhas gerais";
?>

<div class="ba-wrap">
    <!-- Header -->
    <div class="ba-header">
        <div class="ba-header-left">
            <div class="ba-header-icon">
                <span class="dashicons dashicons-welcome-learn-more"></span>
            </div>
            <div>
                <h1><?php esc_html_e( 'Treinamento de IA', 'blog-automatico' ); ?></h1>
                <div class="ba-header-sub"><?php esc_html_e( 'Personalize a escrita da IA para que os textos pareçam 100% humanos e indetectáveis por ferramentas de detecção.', 'blog-automatico' ); ?></div>
            </div>
        </div>
        <div>
            <span class="ba-version">v<?php echo esc_html( BA_VERSION ); ?></span>
        </div>
    </div>

    <!-- Notices Area -->
    <div class="ba-notices-area"></div>

    <form method="post" action="options.php" id="ba-training-form">
        <?php settings_fields( 'ba_training_group' ); ?>

        <!-- SEÇÃO 1: PERSONA DO ESCRITOR -->
        <div class="ba-section">
            <h2 class="ba-section-title">
                <span class="dashicons dashicons-admin-users"></span>
                <?php esc_html_e( 'Persona do Escritor', 'blog-automatico' ); ?>
            </h2>
            <p class="ba-section-description"><?php esc_html_e( 'Defina quem é o "escritor" por trás dos textos. A IA vai adotar essa identidade na forma de escrever.', 'blog-automatico' ); ?></p>

            <div class="ba-field-group">
                <label for="ba_training_writing_style"><?php esc_html_e( 'Estilo de Escrita', 'blog-automatico' ); ?></label>
                <select name="ba_training_writing_style" id="ba_training_writing_style">
                    <option value="natural" <?php selected( $writing_style, 'natural' ); ?>><?php esc_html_e( 'Natural e Fluido — Como uma conversa entre amigos', 'blog-automatico' ); ?></option>
                    <option value="jornalistico" <?php selected( $writing_style, 'jornalistico' ); ?>><?php esc_html_e( 'Jornalístico — Objetivo, direto ao ponto', 'blog-automatico' ); ?></option>
                    <option value="storytelling" <?php selected( $writing_style, 'storytelling' ); ?>><?php esc_html_e( 'Storytelling — Narrativo, conta histórias', 'blog-automatico' ); ?></option>
                    <option value="academico" <?php selected( $writing_style, 'academico' ); ?>><?php esc_html_e( 'Acadêmico — Formal, com referências', 'blog-automatico' ); ?></option>
                    <option value="blogueiro" <?php selected( $writing_style, 'blogueiro' ); ?>><?php esc_html_e( 'Blogueiro — Casual, com opinião e personalidade', 'blog-automatico' ); ?></option>
                    <option value="copywriting" <?php selected( $writing_style, 'copywriting' ); ?>><?php esc_html_e( 'Copywriting — Persuasivo, voltado à ação', 'blog-automatico' ); ?></option>
                </select>
            </div>

            <div class="ba-field-group">
                <label for="ba_training_persona"><?php esc_html_e( 'Instruções de Persona (quem é o escritor?)', 'blog-automatico' ); ?></label>
                <textarea name="ba_training_persona" id="ba_training_persona" rows="5" placeholder="<?php esc_attr_e( 'Ex: Sou um advogado com 15 anos de experiência em direito do consumidor. Escrevo para leigos de forma simples e acessível, usando exemplos do dia-a-dia. Gosto de fazer perguntas retóricas e usar analogias. Tenho um tom amigável mas profissional.', 'blog-automatico' ); ?>"><?php echo esc_textarea( $custom_persona ); ?></textarea>
                <span class="ba-field-hint"><?php esc_html_e( 'Quanto mais detalhado, mais personalizado será o resultado. Descreva experiência, tom de voz, público-alvo, manias de escrita.', 'blog-automatico' ); ?></span>
            </div>
        </div>

        <!-- SEÇÃO 2: NÍVEL DE HUMANIZAÇÃO -->
        <div class="ba-section">
            <h2 class="ba-section-title">
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e( 'Nível de Humanização', 'blog-automatico' ); ?>
            </h2>
            <p class="ba-section-description"><?php esc_html_e( 'Configure o quanto a IA deve variar a escrita para parecer mais humana e indetectável.', 'blog-automatico' ); ?></p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <div class="ba-field-group">
                    <label for="ba_training_humanize_level"><?php esc_html_e( 'Intensidade da Humanização', 'blog-automatico' ); ?></label>
                    <select name="ba_training_humanize_level" id="ba_training_humanize_level">
                        <option value="low" <?php selected( $humanize_level, 'low' ); ?>><?php esc_html_e( 'Leve — Poucas variações', 'blog-automatico' ); ?></option>
                        <option value="medium" <?php selected( $humanize_level, 'medium' ); ?>><?php esc_html_e( 'Moderada — Bom equilíbrio', 'blog-automatico' ); ?></option>
                        <option value="high" <?php selected( $humanize_level, 'high' ); ?>><?php esc_html_e( 'Alta — Máxima variação e naturalidade', 'blog-automatico' ); ?></option>
                    </select>
                </div>

                <div class="ba-field-group">
                    <label for="ba_training_sentence_variety"><?php esc_html_e( 'Variedade de Frases', 'blog-automatico' ); ?></label>
                    <select name="ba_training_sentence_variety" id="ba_training_sentence_variety">
                        <option value="low" <?php selected( $sentence_variety, 'low' ); ?>><?php esc_html_e( 'Frases uniformes (mais previsível)', 'blog-automatico' ); ?></option>
                        <option value="medium" <?php selected( $sentence_variety, 'medium' ); ?>><?php esc_html_e( 'Mistura moderada de tamanhos', 'blog-automatico' ); ?></option>
                        <option value="high" <?php selected( $sentence_variety, 'high' ); ?>><?php esc_html_e( 'Alta variação — frases curtas, médias e longas', 'blog-automatico' ); ?></option>
                    </select>
                </div>

                <div class="ba-field-group">
                    <label for="ba_training_paragraph_style"><?php esc_html_e( 'Estilo de Parágrafos', 'blog-automatico' ); ?></label>
                    <select name="ba_training_paragraph_style" id="ba_training_paragraph_style">
                        <option value="short" <?php selected( $paragraph_style, 'short' ); ?>><?php esc_html_e( 'Curtos (1-3 frases) — Leitura rápida', 'blog-automatico' ); ?></option>
                        <option value="varied" <?php selected( $paragraph_style, 'varied' ); ?>><?php esc_html_e( 'Variados — Mistura de curtos e longos', 'blog-automatico' ); ?></option>
                        <option value="long" <?php selected( $paragraph_style, 'long' ); ?>><?php esc_html_e( 'Longos (4-6 frases) — Mais denso', 'blog-automatico' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="ba-field-group" style="margin-top: 16px;">
                <label>
                    <input type="checkbox" name="ba_training_avoid_patterns" value="1" <?php checked( $avoid_patterns, '1' ); ?> />
                    <?php esc_html_e( 'Evitar padrões detectáveis de IA (recomendado)', 'blog-automatico' ); ?>
                </label>
                <span class="ba-field-hint"><?php esc_html_e( 'Instrui a IA a evitar: estrutura previsível, listas com mesmo tamanho, introduções genéricas e excesso de conectivos.', 'blog-automatico' ); ?></span>
            </div>
        </div>

        <!-- SEÇÃO 3: TEXTOS DE REFERÊNCIA -->
        <div class="ba-section">
            <h2 class="ba-section-title">
                <span class="dashicons dashicons-text-page"></span>
                <?php esc_html_e( 'Textos de Referência (Seu Estilo Real)', 'blog-automatico' ); ?>
            </h2>
            <p class="ba-section-description"><?php esc_html_e( 'Cole aqui trechos de textos que VOCÊ escreveu. A IA vai analisar e copiar seu estilo pessoal de escrita, vocabulário e ritmo.', 'blog-automatico' ); ?></p>

            <div class="ba-field-group">
                <label for="ba_training_reference_texts"><?php esc_html_e( 'Seus Textos de Exemplo (quanto mais, melhor)', 'blog-automatico' ); ?></label>
                <textarea name="ba_training_reference_texts" id="ba_training_reference_texts" rows="12" placeholder="<?php esc_attr_e( "Cole aqui trechos de artigos, posts ou e-mails que você escreveu pessoalmente. A IA vai aprender seu vocabulário, ritmo e estilo.\n\nDica: cole pelo menos 3 parágrafos para melhores resultados.", 'blog-automatico' ); ?>"><?php echo esc_textarea( $reference_texts ); ?></textarea>
                <span class="ba-field-hint"><?php esc_html_e( 'A IA extrai padrões como: tamanho médio de frases, palavras favoritas, uso de gírias, nível de formalidade e mais.', 'blog-automatico' ); ?></span>
            </div>
        </div>

        <!-- SEÇÃO 4: PALAVRAS E EXPRESSÕES -->
        <div class="ba-section">
            <h2 class="ba-section-title">
                <span class="dashicons dashicons-editor-spellcheck"></span>
                <?php esc_html_e( 'Vocabulário e Expressões', 'blog-automatico' ); ?>
            </h2>
            <p class="ba-section-description"><?php esc_html_e( 'Controle as palavras que a IA pode ou não usar. Isso é fundamental para evitar expressões típicas de IA.', 'blog-automatico' ); ?></p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div class="ba-field-group">
                    <label for="ba_training_forbidden_words" style="color: #ff6b6b;">
                        <span class="dashicons dashicons-no" style="font-size: 16px; margin-right: 4px;"></span>
                        <?php esc_html_e( 'Palavras/Expressões PROIBIDAS (uma por linha)', 'blog-automatico' ); ?>
                    </label>
                    <textarea name="ba_training_forbidden_words" id="ba_training_forbidden_words" rows="12" placeholder="<?php echo esc_attr( $default_forbidden ); ?>"><?php echo esc_textarea( $forbidden_words ); ?></textarea>
                    <span class="ba-field-hint"><?php esc_html_e( 'A IA nunca usará essas expressões. Inclua frases que denunciam texto de IA.', 'blog-automatico' ); ?></span>
                </div>

                <div class="ba-field-group">
                    <label for="ba_training_preferred_words" style="color: #B4D443;">
                        <span class="dashicons dashicons-yes-alt" style="font-size: 16px; margin-right: 4px;"></span>
                        <?php esc_html_e( 'Palavras/Expressões PREFERIDAS (uma por linha)', 'blog-automatico' ); ?>
                    </label>
                    <textarea name="ba_training_preferred_words" id="ba_training_preferred_words" rows="12" placeholder="<?php esc_attr_e( "Ex:\nOlha só\nNa prática\nPor exemplo\nIsso acontece porque\nA real é que\nFicou claro?\nVamos lá\nBom, vamos ao que interessa", 'blog-automatico' ); ?>"><?php echo esc_textarea( $preferred_words ); ?></textarea>
                    <span class="ba-field-hint"><?php esc_html_e( 'A IA vai priorizar essas expressões no texto, tornando-o mais pessoal.', 'blog-automatico' ); ?></span>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 5: REGRAS PERSONALIZADAS -->
        <div class="ba-section">
            <h2 class="ba-section-title">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e( 'Regras Personalizadas (Instruções Livres)', 'blog-automatico' ); ?>
            </h2>
            <p class="ba-section-description"><?php esc_html_e( 'Adicione qualquer instrução especial que a IA deve seguir ao gerar conteúdo.', 'blog-automatico' ); ?></p>

            <div class="ba-field-group">
                <label for="ba_training_custom_rules"><?php esc_html_e( 'Instruções adicionais para a IA', 'blog-automatico' ); ?></label>
                <textarea name="ba_training_custom_rules" id="ba_training_custom_rules" rows="8" placeholder="<?php esc_attr_e( "Ex:\n- Sempre começar o artigo com uma pergunta provocativa\n- Usar no máximo 1 emoji por seção\n- Incluir dados estatísticos quando possível\n- Nunca usar a palavra 'jornada'\n- Fazer referência a situações cotidianas brasileiras\n- Incluir pelo menos 1 opinião pessoal por artigo\n- Variar o formato: às vezes começar com história, às vezes com dado", 'blog-automatico' ); ?>"><?php echo esc_textarea( $custom_rules ); ?></textarea>
            </div>
        </div>

        <!-- BOTÃO SALVAR -->
        <div class="ba-section" style="text-align: right; padding: 16px 24px;">
            <?php submit_button( __( 'Salvar Treinamento', 'blog-automatico' ), 'primary', 'submit', false ); ?>
        </div>
    </form>
</div>
