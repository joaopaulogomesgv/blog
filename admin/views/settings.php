<?php
/**
 * Admin View: Settings (v1.1.3 Multi-Provedores & Automação Refinada)
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings        = BA_Settings::get_instance();
$active_provider = $settings->get( 'ba_ai_provider' );
$providers       = BA_AI_Connector::get_providers();
$templates       = $settings->get_elementor_templates();
$tones           = $settings->get_content_tones();
$languages       = $settings->get_languages();
$statuses        = $settings->get_publish_statuses();
$image_sizes     = $settings->get_image_sizes();
$seo_plugins     = $settings->get_seo_plugins();
$categories      = get_categories( array( 'hide_empty' => false ) );
?>

<div class="ba-wrap">
    <!-- Header -->
    <div class="ba-header">
        <div class="ba-header-left">
            <div class="ba-header-icon">
                <span class="dashicons dashicons-admin-settings"></span>
            </div>
            <div>
                <h1><?php esc_html_e( 'Configurações do Blog Automático', 'blog-automatico' ); ?></h1>
                <div class="ba-header-sub"><?php esc_html_e( 'Conecte suas chaves de IA, configure a publicação diária no piloto automático e preferências de SEO.', 'blog-automatico' ); ?></div>
            </div>
        </div>
        <div>
            <span class="ba-version">v<?php echo esc_html( BA_VERSION ); ?></span>
        </div>
    </div>

    <!-- Notices Area -->
    <div class="ba-notices-area"></div>

    <form method="post" action="options.php" id="ba-settings-form">
        <?php settings_fields( 'ba_settings_group' ); ?>

        <!-- SEÇÃO: PILOTO AUTOMÁTICO (DESTAQUE MÁXIMO) -->
        <div class="ba-section ba-section-highlight">
            <h2 class="ba-section-title">
                <span class="dashicons dashicons-controls-play"></span>
                <?php esc_html_e( 'Piloto Automático (Publicação Diária Sem Trabalho Manual)', 'blog-automatico' ); ?>
            </h2>

            <div style="display: grid; grid-template-columns: 1.25fr 1fr; gap: 24px; align-items: start;">
                <div>
                    <!-- Switch Card Isolado Sem Sobreposição -->
                    <div class="ba-switch-card">
                        <div class="ba-switch-card-info">
                            <strong><?php esc_html_e( 'Ativar Publicação Automática Diária', 'blog-automatico' ); ?></strong>
                            <span><?php esc_html_e( 'Quando ativado, o robô consome os tópicos da sua Fila de Conteúdo e publica novos artigos todos os dias de forma 100% autônoma.', 'blog-automatico' ); ?></span>
                        </div>
                        <label class="ba-switch" for="ba_auto_post_enabled">
                            <input
                                type="checkbox"
                                name="ba_auto_post_enabled"
                                value="1"
                                id="ba_auto_post_enabled"
                                <?php checked( 1, (int) $settings->get( 'ba_auto_post_enabled' ) ); ?>
                            />
                            <span class="ba-switch-track">
                                <span class="ba-switch-thumb"></span>
                            </span>
                        </label>
                    </div>

                    <div class="ba-form-group" style="margin-top: 20px;">
                        <label for="ba_posts_per_day">
                            <?php esc_html_e( 'Quantos posts publicar por dia?', 'blog-automatico' ); ?>
                        </label>
                        <select id="ba_posts_per_day" name="ba_posts_per_day" class="ba-select" style="max-width: 280px;">
                            <option value="1" <?php selected( (int) $settings->get( 'ba_posts_per_day' ), 1 ); ?>>1 post por dia</option>
                            <option value="2" <?php selected( (int) $settings->get( 'ba_posts_per_day' ), 2 ); ?>>2 posts por dia (Recomendado)</option>
                            <option value="3" <?php selected( (int) $settings->get( 'ba_posts_per_day' ), 3 ); ?>>3 posts por dia</option>
                            <option value="4" <?php selected( (int) $settings->get( 'ba_posts_per_day' ), 4 ); ?>>4 posts por dia</option>
                            <option value="5" <?php selected( (int) $settings->get( 'ba_posts_per_day' ), 5 ); ?>>5 posts por dia</option>
                        </select>
                        <span class="help"><?php esc_html_e( 'Quantidade de blogs produzidos e publicados diariamente pela rotina automática.', 'blog-automatico' ); ?></span>
                    </div>
                </div>

                <!-- Box de Horários e Status -->
                <div class="ba-card-inner">
                    <div class="ba-form-group">
                        <label><?php esc_html_e( 'Janela de Horário para Publicação', 'blog-automatico' ); ?></label>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <input
                                type="time"
                                name="ba_auto_post_time_start"
                                class="ba-input ba-time-input"
                                value="<?php echo esc_attr( $settings->get( 'ba_auto_post_time_start' ) ? $settings->get( 'ba_auto_post_time_start' ) : '08:00' ); ?>"
                            />
                            <span style="color:#94a3b8; font-weight:600;"><?php esc_html_e( 'até', 'blog-automatico' ); ?></span>
                            <input
                                type="time"
                                name="ba_auto_post_time_end"
                                class="ba-input ba-time-input"
                                value="<?php echo esc_attr( $settings->get( 'ba_auto_post_time_end' ) ? $settings->get( 'ba_auto_post_time_end' ) : '19:00' ); ?>"
                            />
                        </div>
                        <span class="help"><?php esc_html_e( 'Os artigos serão disparados de maneira natural dentro desse intervalo.', 'blog-automatico' ); ?></span>
                    </div>

                    <div class="ba-form-group" style="margin-bottom:0;">
                        <label for="ba_publish_status"><?php esc_html_e( 'Status Inicial do Post', 'blog-automatico' ); ?></label>
                        <select id="ba_publish_status" name="ba_publish_status" class="ba-select">
                            <?php foreach ( $statuses as $key => $lbl ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings->get( 'ba_publish_status' ), $key ); ?>>
                                    <?php echo esc_html( $lbl ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="help"><?php esc_html_e( 'Use "Publicado" para entrar no ar direto ou "Rascunho" para revisar antes.', 'blog-automatico' ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="ba-settings-grid" style="display:grid; grid-template-columns: 1.15fr 1fr; gap: 24px;">
            <!-- COLUNA ESQUERDA: PROVEDORES DE IA -->
            <div class="ba-section">
                <h2 class="ba-section-title">
                    <span class="dashicons dashicons-rest-api"></span>
                    <?php esc_html_e( 'Provedores de Inteligência Artificial', 'blog-automatico' ); ?>
                </h2>

                <div class="ba-form-group">
                    <label for="ba_ai_provider">
                        <?php esc_html_e( 'Provedor de IA Ativo', 'blog-automatico' ); ?>
                    </label>
                    <select id="ba_ai_provider" name="ba_ai_provider" class="ba-select">
                        <?php foreach ( $providers as $pid => $pinfo ) : ?>
                            <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $active_provider, $pid ); ?>>
                                <?php echo esc_html( $pinfo['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="help"><?php esc_html_e( 'Selecione qual IA criará o conteúdo dos seus artigos de blog.', 'blog-automatico' ); ?></span>
                </div>

                <!-- Abas dos Provedores -->
                <div class="ba-provider-tabs" id="ba-provider-tabs">
                    <?php foreach ( $providers as $pid => $pinfo ) : 
                        $has_key = ! empty( $settings->get( 'ba_api_key_' . $pid ) );
                    ?>
                        <div class="ba-provider-tab <?php echo ( $active_provider === $pid ) ? 'active' : ''; ?>" data-target="tab-<?php echo esc_attr( $pid ); ?>">
                            <span class="dot <?php echo $has_key ? 'configured' : 'missing'; ?>"></span>
                            <span><?php echo esc_html( $pinfo['name'] ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Aba OpenAI -->
                <div class="ba-provider-content" id="tab-openai" style="<?php echo ( 'openai' === $active_provider ) ? '' : 'display:none;'; ?>">
                    <!-- Card de link direto para pegar a chave -->
                    <div class="ba-key-help-card">
                        <div class="ba-key-help-info">
                            <strong><?php esc_html_e( 'Chave de API da OpenAI (ChatGPT & DALL-E)', 'blog-automatico' ); ?></strong>
                            <span><?php esc_html_e( 'Crie sua chave de acesso oficial no painel de desenvolvedores da OpenAI.', 'blog-automatico' ); ?></span>
                        </div>
                        <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer" class="ba-btn-get-key">
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e( 'Pegar Chave na OpenAI ↗', 'blog-automatico' ); ?>
                        </a>
                    </div>

                    <div class="ba-form-group">
                        <label for="ba_api_key_openai">
                            <?php esc_html_e( 'API Key OpenAI', 'blog-automatico' ); ?>
                            <span class="ba-required">*</span>
                        </label>
                        <div class="ba-api-key-field">
                            <input
                                type="password"
                                id="ba_api_key_openai"
                                name="ba_api_key_openai"
                                class="ba-input"
                                value="<?php echo esc_attr( $settings->get( 'ba_api_key_openai' ) ); ?>"
                                placeholder="sk-proj-..."
                            />
                            <button type="button" class="ba-toggle-visibility" title="<?php esc_attr_e( 'Mostrar/Ocultar', 'blog-automatico' ); ?>"><span class="dashicons dashicons-visibility"></span></button>
                        </div>
                    </div>
                </div>

                <!-- Aba Google Gemini -->
                <div class="ba-provider-content" id="tab-gemini" style="<?php echo ( 'gemini' === $active_provider ) ? '' : 'display:none;'; ?>">
                    <!-- Card de link direto para pegar a chave -->
                    <div class="ba-key-help-card">
                        <div class="ba-key-help-info">
                            <strong><?php esc_html_e( 'Google AI Studio (Gemini 2.0 Flash / Pro)', 'blog-automatico' ); ?></strong>
                            <span><?php esc_html_e( 'O Google oferece chaves de API com cota gratuita para desenvolvedores no Google AI Studio.', 'blog-automatico' ); ?></span>
                        </div>
                        <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="ba-btn-get-key">
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e( 'Pegar Chave no Google AI Studio (Grátis) ↗', 'blog-automatico' ); ?>
                        </a>
                    </div>

                    <div class="ba-form-group">
                        <label for="ba_api_key_gemini">
                            <?php esc_html_e( 'API Key Google Gemini', 'blog-automatico' ); ?>
                            <span class="ba-required">*</span>
                        </label>
                        <div class="ba-api-key-field">
                            <input
                                type="password"
                                id="ba_api_key_gemini"
                                name="ba_api_key_gemini"
                                class="ba-input"
                                value="<?php echo esc_attr( $settings->get( 'ba_api_key_gemini' ) ); ?>"
                                placeholder="AIzaSy..."
                            />
                            <button type="button" class="ba-toggle-visibility" title="<?php esc_attr_e( 'Mostrar/Ocultar', 'blog-automatico' ); ?>"><span class="dashicons dashicons-visibility"></span></button>
                        </div>
                    </div>
                </div>

                <!-- Aba Grok (xAI) -->
                <div class="ba-provider-content" id="tab-grok" style="<?php echo ( 'grok' === $active_provider ) ? '' : 'display:none;'; ?>">
                    <div class="ba-key-help-card">
                        <div class="ba-key-help-info">
                            <strong><?php esc_html_e( 'xAI Developer Console (Grok 3)', 'blog-automatico' ); ?></strong>
                            <span><?php esc_html_e( 'Gere sua chave de API para o modelo Grok 3 no console da xAI.', 'blog-automatico' ); ?></span>
                        </div>
                        <a href="https://console.x.ai/" target="_blank" rel="noopener noreferrer" class="ba-btn-get-key">
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e( 'Pegar Chave na xAI (Grok) ↗', 'blog-automatico' ); ?>
                        </a>
                    </div>

                    <div class="ba-form-group">
                        <label for="ba_api_key_grok">
                            <?php esc_html_e( 'API Key xAI (Grok)', 'blog-automatico' ); ?>
                            <span class="ba-required">*</span>
                        </label>
                        <div class="ba-api-key-field">
                            <input
                                type="password"
                                id="ba_api_key_grok"
                                name="ba_api_key_grok"
                                class="ba-input"
                                value="<?php echo esc_attr( $settings->get( 'ba_api_key_grok' ) ); ?>"
                                placeholder="xai-..."
                            />
                            <button type="button" class="ba-toggle-visibility" title="<?php esc_attr_e( 'Mostrar/Ocultar', 'blog-automatico' ); ?>"><span class="dashicons dashicons-visibility"></span></button>
                        </div>
                    </div>
                </div>

                <!-- Aba DeepSeek -->
                <div class="ba-provider-content" id="tab-deepseek" style="<?php echo ( 'deepseek' === $active_provider ) ? '' : 'display:none;'; ?>">
                    <div class="ba-key-help-card">
                        <div class="ba-key-help-info">
                            <strong><?php esc_html_e( 'DeepSeek Open Platform', 'blog-automatico' ); ?></strong>
                            <span><?php esc_html_e( 'Modelo de altíssima performance e inteligência com custos extremamente reduzidos.', 'blog-automatico' ); ?></span>
                        </div>
                        <a href="https://platform.deepseek.com/api_keys" target="_blank" rel="noopener noreferrer" class="ba-btn-get-key">
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e( 'Pegar Chave na DeepSeek ↗', 'blog-automatico' ); ?>
                        </a>
                    </div>

                    <div class="ba-form-group">
                        <label for="ba_api_key_deepseek">
                            <?php esc_html_e( 'API Key DeepSeek', 'blog-automatico' ); ?>
                            <span class="ba-required">*</span>
                        </label>
                        <div class="ba-api-key-field">
                            <input
                                type="password"
                                id="ba_api_key_deepseek"
                                name="ba_api_key_deepseek"
                                class="ba-input"
                                value="<?php echo esc_attr( $settings->get( 'ba_api_key_deepseek' ) ); ?>"
                                placeholder="sk-..."
                            />
                            <button type="button" class="ba-toggle-visibility" title="<?php esc_attr_e( 'Mostrar/Ocultar', 'blog-automatico' ); ?>"><span class="dashicons dashicons-visibility"></span></button>
                        </div>
                    </div>
                </div>

                <!-- Botão Testar Conexão -->
                <div style="margin-top: 18px; margin-bottom: 24px;">
                    <button type="button" id="ba-test-connection" class="ba-btn ba-btn-secondary ba-btn-sm">
                        <span class="ba-spinner"></span>
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <span class="ba-btn-text"><?php esc_html_e( 'Testar Conexão com Provedor Ativo', 'blog-automatico' ); ?></span>
                    </button>
                </div>

                <hr style="border:0; border-top:1px solid rgba(255,255,255,0.08); margin: 24px 0;">

                <div class="ba-form-group">
                    <label for="ba_text_model"><?php esc_html_e( 'Modelo de Texto Principal', 'blog-automatico' ); ?></label>
                    <select id="ba_text_model" name="ba_text_model" class="ba-select">
                        <?php 
                        $cur_models = isset( $providers[ $active_provider ]['models'] ) ? $providers[ $active_provider ]['models'] : array();
                        foreach ( $cur_models as $m_val => $m_label ) : ?>
                            <option value="<?php echo esc_attr( $m_val ); ?>" <?php selected( $settings->get( 'ba_text_model' ), $m_val ); ?>>
                                <?php echo esc_html( $m_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ba-form-group">
                    <label for="ba_image_model"><?php esc_html_e( 'Modelo de Geração de Imagem', 'blog-automatico' ); ?></label>
                    <select id="ba_image_model" name="ba_image_model" class="ba-select">
                        <option value="dall-e-3" <?php selected( $settings->get( 'ba_image_model' ), 'dall-e-3' ); ?>>DALL-E 3 (OpenAI)</option>
                        <option value="dall-e-2" <?php selected( $settings->get( 'ba_image_model' ), 'dall-e-2' ); ?>>DALL-E 2 (OpenAI)</option>
                    </select>
                    <span class="help"><?php esc_html_e( 'Caso utilize Gemini ou DeepSeek para texto, imagens são criadas via DALL-E com sua chave OpenAI.', 'blog-automatico' ); ?></span>
                </div>

                <div class="ba-form-group">
                    <label for="ba_image_size"><?php esc_html_e( 'Resolução da Imagem Destacada', 'blog-automatico' ); ?></label>
                    <select id="ba_image_size" name="ba_image_size" class="ba-select">
                        <?php foreach ( $image_sizes as $val => $lbl ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $settings->get( 'ba_image_size' ), $val ); ?>>
                                <?php echo esc_html( $lbl ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- COLUNA DIREITA: CONTEÚDO, SEO & ELEMENTOR -->
            <div>
                <!-- Elementor & Design -->
                <div class="ba-section">
                    <h2 class="ba-section-title">
                        <span class="dashicons dashicons-layout"></span>
                        <?php esc_html_e( 'Design & Elementor Pro', 'blog-automatico' ); ?>
                    </h2>

                    <div class="ba-form-group">
                        <label for="ba_default_template"><?php esc_html_e( 'Template Padrão Elementor Pro', 'blog-automatico' ); ?></label>
                        <select id="ba_default_template" name="ba_default_template" class="ba-select">
                            <option value="default"><?php esc_html_e( 'Padrão (Automático)', 'blog-automatico' ); ?></option>
                            <?php foreach ( $templates as $t_key => $t_name ) : ?>
                                <option value="<?php echo esc_attr( $t_key ); ?>" <?php selected( $settings->get( 'ba_default_template' ), $t_key ); ?>>
                                    <?php echo esc_html( $t_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="help"><?php esc_html_e( 'Template que servirá de base visual com containers e tipografia do Elementor Pro.', 'blog-automatico' ); ?></span>
                    </div>

                    <div class="ba-form-group">
                        <label for="ba_default_category"><?php esc_html_e( 'Categoria Padrão do WordPress', 'blog-automatico' ); ?></label>
                        <select id="ba_default_category" name="ba_default_category" class="ba-select">
                            <option value="0"><?php esc_html_e( 'Automático (A IA define a melhor categoria)', 'blog-automatico' ); ?></option>
                            <?php foreach ( $categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( (int) $settings->get( 'ba_default_category' ), $cat->term_id ); ?>>
                                    <?php echo esc_html( $cat->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Escrita & SEO -->
                <div class="ba-section">
                    <h2 class="ba-section-title">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e( 'Otimização de SEO e Redação', 'blog-automatico' ); ?>
                    </h2>

                    <div class="ba-form-group">
                        <label for="ba_content_tone"><?php esc_html_e( 'Tom de Voz do Artigo', 'blog-automatico' ); ?></label>
                        <select id="ba_content_tone" name="ba_content_tone" class="ba-select">
                            <?php foreach ( $tones as $t_val => $t_lbl ) : ?>
                                <option value="<?php echo esc_attr( $t_val ); ?>" <?php selected( $settings->get( 'ba_content_tone' ), $t_val ); ?>>
                                    <?php echo esc_html( $t_lbl ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ba-form-group">
                        <label for="ba_language"><?php esc_html_e( 'Idioma do Conteúdo', 'blog-automatico' ); ?></label>
                        <select id="ba_language" name="ba_language" class="ba-select">
                            <?php foreach ( $languages as $l_val => $l_lbl ) : ?>
                                <option value="<?php echo esc_attr( $l_val ); ?>" <?php selected( $settings->get( 'ba_language' ), $l_val ); ?>>
                                    <?php echo esc_html( $l_lbl ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ba-form-group">
                        <label for="ba_article_length">
                            <?php esc_html_e( 'Tamanho Alvo (Palavras)', 'blog-automatico' ); ?>
                        </label>
                        <div class="ba-range-wrap">
                            <input
                                type="range"
                                id="ba_article_length"
                                name="ba_article_length"
                                class="ba-range-input"
                                min="600"
                                max="3500"
                                step="100"
                                value="<?php echo esc_attr( $settings->get( 'ba_article_length' ) ? $settings->get( 'ba_article_length' ) : 1500 ); ?>"
                            />
                            <span class="ba-range-value"><?php echo esc_html( $settings->get( 'ba_article_length' ) ? $settings->get( 'ba_article_length' ) : 1500 ); ?> palavras</span>
                        </div>
                    </div>

                    <div class="ba-form-group">
                        <label for="ba_seo_plugin"><?php esc_html_e( 'Plugin de SEO Detectado', 'blog-automatico' ); ?></label>
                        <select id="ba_seo_plugin" name="ba_seo_plugin" class="ba-select">
                            <?php foreach ( $seo_plugins as $s_val => $s_lbl ) : ?>
                                <option value="<?php echo esc_attr( $s_val ); ?>" <?php selected( $settings->get( 'ba_seo_plugin' ), $s_val ); ?>>
                                    <?php echo esc_html( $s_lbl ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="help"><?php esc_html_e( 'Preenchimento autônomo de meta title, meta description e Schema Markup.', 'blog-automatico' ); ?></span>
                    </div>

                    <div class="ba-form-group">
                        <label for="ba_daily_limit"><?php esc_html_e( 'Limite de Segurança Máximo Diário', 'blog-automatico' ); ?></label>
                        <input
                            type="number"
                            id="ba_daily_limit"
                            name="ba_daily_limit"
                            class="ba-input"
                            style="max-width: 140px;"
                            value="<?php echo esc_attr( $settings->get( 'ba_daily_limit' ) ? $settings->get( 'ba_daily_limit' ) : 10 ); ?>"
                            min="0"
                            max="50"
                        />
                        <span class="help"><?php esc_html_e( 'Trava diária contra consumo inesperado de tokens. 0 = sem trava.', 'blog-automatico' ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 28px; padding-top: 16px;">
            <button type="submit" class="ba-btn ba-btn-primary" style="padding: 13px 32px; font-size: 15px;">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e( 'Salvar Todas as Configurações', 'blog-automatico' ); ?>
            </button>
        </div>
    </form>
</div>
