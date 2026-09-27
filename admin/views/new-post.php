<?php
/**
 * Admin View: New Post (v1.1.5)
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings        = BA_Settings::get_instance();
$active_provider = $settings->get( 'ba_ai_provider' );
$providers       = BA_AI_Connector::get_providers();
$has_key         = $settings->has_api_key();
?>

<div class="ba-wrap">
    <!-- Header -->
    <div class="ba-header">
        <div class="ba-header-left">
            <div class="ba-header-icon">
                <span class="dashicons dashicons-welcome-write-blog"></span>
            </div>
            <div>
                <h1><?php esc_html_e( 'Criar Novo Artigo com IA', 'blog-automatico' ); ?></h1>
                <div class="ba-header-sub">
                    <?php 
                    printf( 
                        esc_html__( 'Provedor ativo: %s (%s). Gerando texto SEO, imagens e layout Elementor Pro.', 'blog-automatico' ),
                        '<strong>' . esc_html( isset( $providers[ $active_provider ]['name'] ) ? $providers[ $active_provider ]['name'] : 'IA' ) . '</strong>',
                        esc_html( $settings->get( 'ba_text_model' ) )
                    ); 
                    ?>
                </div>
            </div>
        </div>
        <div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-automatico-queue' ) ); ?>" class="ba-btn ba-btn-secondary ba-btn-sm">
                <span class="dashicons dashicons-list-view"></span>
                <?php esc_html_e( 'Fila de 100+ Ideias (Em Massa) →', 'blog-automatico' ); ?>
            </a>
        </div>
    </div>

    <!-- Notices Area -->
    <div class="ba-notices-area"></div>

    <?php if ( ! $has_key ) : ?>
        <div class="ba-alert ba-alert-error" style="background-color: #281215 !important; border: 1px solid #7f1d1d !important; border-left: 5px solid #ef4444 !important; color: #ffffff !important; padding: 14px 18px !important; display: flex !important; align-items: center !important; gap: 12px !important; border-radius: 6px !important; margin-bottom: 20px !important;">
            <span class="dashicons dashicons-warning" style="color: #ef4444 !important; font-size: 20px !important; width: 20px !important; height: 20px !important; flex-shrink: 0 !important;"></span>
            <span style="color: #ffffff !important; font-size: 13.5px !important; line-height: 1.5 !important;">
                <?php
                printf(
                    esc_html__( 'Nenhuma API Key configurada para o provedor selecionado (%s). %s para cadastrar sua chave.', 'blog-automatico' ),
                    '<strong style="color: #ffffff !important;">' . esc_html( $active_provider ) . '</strong>',
                    '<a href="' . esc_url( admin_url( 'admin.php?page=blog-automatico-settings' ) ) . '" style="color: #B4D443 !important; font-weight: 700 !important; text-decoration: underline !important;">' . esc_html__( 'Acesse as Configurações', 'blog-automatico' ) . '</a>'
                );
                ?>
            </span>
        </div>
    <?php endif; ?>

    <!-- Seção Principal: PROTAGONISTA DA TELA -->
    <div class="ba-section ba-main-creator-box">
        <h2 class="ba-section-title">
            <span class="dashicons dashicons-edit"></span>
            <?php esc_html_e( 'Qual é o assunto do artigo que você quer criar?', 'blog-automatico' ); ?>
        </h2>

        <form id="ba-generate-form" method="post">
            <div class="ba-form-group ba-idea-group">
                <label for="ba-idea" class="ba-idea-label">
                    <?php esc_html_e( 'Insira o tema, palavra-chave ou ideia do artigo:', 'blog-automatico' ); ?>
                    <span class="ba-required">*</span>
                </label>
                <textarea
                    id="ba-idea"
                    name="idea"
                    class="ba-textarea ba-idea-textarea"
                    rows="6"
                    placeholder="<?php esc_attr_e( 'Ex: 7 estratégias avançadas de SEO local para pequenas empresas dominarem o Google em 2026, com foco em otimização de ficha do Google Meu Negócio, avaliações de clientes e conteúdo regional...', 'blog-automatico' ); ?>"
                    required
                ></textarea>
                <div class="ba-idea-footer">
                    <span class="ba-help-tip">
                        <span class="dashicons dashicons-info-outline"></span>
                        <?php esc_html_e( 'A IA produzirá automaticamente: Título atrativo, estrutura H2/H3, artigo completo, imagens em destaque, FAQ Schema e metadados SEO.', 'blog-automatico' ); ?>
                    </span>
                </div>
            </div>

            <!-- Opções do Post em 4 colunas bem distribuídas -->
            <div class="ba-options-grid">
                <div class="ba-form-group">
                    <label for="ba-tone"><?php esc_html_e( 'Tom de Voz', 'blog-automatico' ); ?></label>
                    <select id="ba-tone" name="tone" class="ba-select">
                        <?php foreach ( $settings->get_content_tones() as $val => $lbl ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $settings->get( 'ba_content_tone' ), $val ); ?>>
                                <?php echo esc_html( $lbl ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ba-form-group">
                    <label for="ba-template"><?php esc_html_e( 'Template Elementor Pro', 'blog-automatico' ); ?></label>
                    <select id="ba-template" name="template" class="ba-select">
                        <option value="default"><?php esc_html_e( 'Padrão (Automático)', 'blog-automatico' ); ?></option>
                        <?php foreach ( $settings->get_elementor_templates() as $t_key => $t_name ) : ?>
                            <option value="<?php echo esc_attr( $t_key ); ?>" <?php selected( $settings->get( 'ba_default_template' ), $t_key ); ?>>
                                <?php echo esc_html( $t_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ba-form-group">
                    <label for="ba-status"><?php esc_html_e( 'Status de Publicação', 'blog-automatico' ); ?></label>
                    <select id="ba-status" name="status" class="ba-select">
                        <?php foreach ( $settings->get_publish_statuses() as $s_val => $s_lbl ) : ?>
                            <option value="<?php echo esc_attr( $s_val ); ?>" <?php selected( $settings->get( 'ba_publish_status' ), $s_val ); ?>>
                                <?php echo esc_html( $s_lbl ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ba-form-group">
                    <label for="ba-length"><?php esc_html_e( 'Tamanho Alvo (Palavras)', 'blog-automatico' ); ?></label>
                    <input
                        type="number"
                        id="ba-length"
                        name="length"
                        class="ba-input"
                        value="<?php echo esc_attr( $settings->get( 'ba_article_length' ) ? $settings->get( 'ba_article_length' ) : 1500 ); ?>"
                        min="500"
                        max="3500"
                        step="100"
                    />
                </div>
            </div>

            <!-- Rodapé de Ação -->
            <div class="ba-action-row">
                <button type="submit" class="ba-btn ba-btn-primary ba-btn-lg ba-btn-generate" <?php disabled( ! $has_key ); ?>>
                    <span class="ba-spinner"></span>
                    <span class="dashicons dashicons-admin-generic"></span>
                    <span class="ba-btn-text"><?php esc_html_e( 'Gerar Post Completo Agora', 'blog-automatico' ); ?></span>
                </button>

                <div class="ba-eta-info">
                    <span class="dashicons dashicons-clock"></span>
                    <span><?php esc_html_e( 'Tempo médio: 20 a 40 segundos para gerar o texto, imagem e montar o Elementor.', 'blog-automatico' ); ?></span>
                </div>
            </div>
        </form>
    </div>

    <!-- Barra de Progresso Real/Animada -->
    <div id="ba-progress" class="ba-progress-wrap">
        <div class="ba-progress-step"><?php esc_html_e( 'Preparando requisição...', 'blog-automatico' ); ?></div>
        <div class="ba-progress-bar">
            <div class="ba-progress-fill"></div>
        </div>
        <div class="ba-progress-text">0%</div>
    </div>

    <!-- Card de Resultado -->
    <div id="ba-result" class="ba-result">
        <div class="ba-result-header">
            <div class="ba-result-icon"></div>
            <div class="ba-result-title"></div>
        </div>

        <div class="ba-result-meta">
            <div class="ba-result-meta-item">
                <div class="value meta-tokens">0</div>
                <div class="label"><?php esc_html_e( 'Tokens Usados', 'blog-automatico' ); ?></div>
            </div>
            <div class="ba-result-meta-item">
                <div class="value meta-images">0</div>
                <div class="label"><?php esc_html_e( 'Imagens Geradas', 'blog-automatico' ); ?></div>
            </div>
            <div class="ba-result-meta-item">
                <div class="value meta-time">0s</div>
                <div class="label"><?php esc_html_e( 'Tempo de Execução', 'blog-automatico' ); ?></div>
            </div>
            <div class="ba-result-meta-item">
                <div class="value meta-status">-</div>
                <div class="label"><?php esc_html_e( 'Status', 'blog-automatico' ); ?></div>
            </div>
        </div>

        <div style="margin-top: 20px; display:flex; gap:12px; justify-content:flex-start;">
            <a href="#" class="ba-btn ba-btn-primary ba-link-edit" style="display:none;" target="_blank">
                <span class="dashicons dashicons-edit"></span>
                <?php esc_html_e( 'Editar no WordPress / Elementor', 'blog-automatico' ); ?>
            </a>
            <a href="#" class="ba-btn ba-btn-secondary ba-link-view" target="_blank" style="display:none;">
                <span class="dashicons dashicons-external"></span>
                <?php esc_html_e( 'Visualizar Artigo Publicado', 'blog-automatico' ); ?>
            </a>
        </div>
    </div>
</div>
