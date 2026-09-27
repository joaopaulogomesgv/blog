<?php
/**
 * Admin View: Fila de Conteúdo (Queue & Bulk Add)
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$scheduler = BA_Scheduler::get_instance();
$settings  = BA_Settings::get_instance();
$templates = $settings->get_elementor_templates();
$queue     = $scheduler->get_queue_stats();

$current_page   = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$current_filter = isset( $_GET['status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['status_filter'] ) ) : '';
$items_data     = $scheduler->get_scheduled( $current_page, 20, $current_filter );
$items          = $items_data['items'];
$total_pages    = $items_data['pages'];

$auto_enabled   = $settings->is_auto_post_enabled();
$posts_per_day  = $settings->get_posts_per_day();
?>

<div class="ba-wrap">
    <!-- Header -->
    <div class="ba-header">
        <div class="ba-header-left">
            <div class="ba-header-icon">
                <span class="dashicons dashicons-list-view"></span>
            </div>
            <div>
                <h1><?php esc_html_e( 'Fila de Conteúdo e Postagem Automática', 'blog-automatico' ); ?></h1>
                <div class="ba-header-sub"><?php esc_html_e( 'Adicione até centenas de ideias em massa. O plugin gera e publica automaticamente no piloto automático.', 'blog-automatico' ); ?></div>
            </div>
        </div>
        <div>
            <span class="ba-version">v<?php echo esc_html( BA_VERSION ); ?></span>
        </div>
    </div>

    <!-- Notices Area -->
    <div class="ba-notices-area"></div>

    <!-- Status do Piloto Automático -->
    <div class="ba-auto-banner <?php echo $auto_enabled ? 'on' : 'off'; ?>">
        <div class="status-dot"></div>
        <div class="info">
            <strong>
                <?php if ( $auto_enabled ) : ?>
                    <?php printf( esc_html__( 'Piloto Automático Ativo — Postando %d post(s) por dia', 'blog-automatico' ), $posts_per_day ); ?>
                <?php else : ?>
                    <?php esc_html_e( 'Piloto Automático Pausado', 'blog-automatico' ); ?>
                <?php endif; ?>
            </strong>
            <span>
                <?php if ( $auto_enabled ) : ?>
                    <?php esc_html_e( 'Os posts da fila abaixo serão gerados e publicados automaticamente nos horários programados.', 'blog-automatico' ); ?>
                <?php else : ?>
                    <?php esc_html_e( 'Ative o piloto automático nas Configurações para publicar os tópicos da fila sem intervenção manual.', 'blog-automatico' ); ?>
                <?php endif; ?>
            </span>
        </div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-automatico-settings' ) ); ?>" class="ba-btn ba-btn-secondary ba-btn-sm">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e( 'Configurar Automação', 'blog-automatico' ); ?>
        </a>
    </div>

    <!-- Stats da Fila -->
    <div class="ba-stats">
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="number"><?php echo esc_html( $queue['queued'] ); ?></div>
            <div class="label"><?php esc_html_e( 'Na Fila (Aguardando)', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-update"></span></div>
            <div class="number"><?php echo esc_html( $queue['processing'] ); ?></div>
            <div class="label"><?php esc_html_e( 'Processando Agora', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="number"><?php echo esc_html( $queue['done'] ); ?></div>
            <div class="label"><?php esc_html_e( 'Publicados com Sucesso', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-warning"></span></div>
            <div class="number"><?php echo esc_html( $queue['failed'] ); ?></div>
            <div class="label"><?php esc_html_e( 'Com Falha', 'blog-automatico' ); ?></div>
        </div>
    </div>

    <div class="ba-row" style="display:grid; grid-template-columns: 1fr 1.6fr; gap: 20px;">
        <!-- Coluna Esquerda: Adicionar em Massa -->
        <div class="ba-section">
            <h2 class="ba-section-title">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e( 'Adicionar Tópicos em Massa (100+ Ideias)', 'blog-automatico' ); ?>
            </h2>

            <form id="ba-bulk-form">
                <div class="ba-form-group">
                    <label for="ba-bulk-ideas">
                        <?php esc_html_e( 'Cole os Assuntos / Ideias (1 por linha)', 'blog-automatico' ); ?>
                        <span class="ba-required">*</span>
                    </label>
                    <textarea
                        id="ba-bulk-ideas"
                        class="ba-textarea large"
                        rows="12"
                        placeholder="Exemplo:
Como escolher o melhor ar-condicionado inverter para quarto
10 tendências de design de interiores para 2026
Guia definitivo de automação residencial inteligente
Os maiores erros ao instalar energia solar fotovoltaica"
                        required
                    ></textarea>
                    <div class="ba-bulk-counter">
                        <span class="dashicons dashicons-editor-ol"></span>
                        <span><?php esc_html_e( 'Linhas identificadas:', 'blog-automatico' ); ?> <strong id="ba-bulk-line-count">0</strong></span>
                    </div>
                    <span class="help"><?php esc_html_e( 'Cole até centenas de ideias. Cada linha será um post independente na fila.', 'blog-automatico' ); ?></span>
                </div>

                <div class="ba-form-group">
                    <label for="ba-bulk-template"><?php esc_html_e( 'Template Elementor Pro', 'blog-automatico' ); ?></label>
                    <select id="ba-bulk-template" class="ba-select">
                        <option value="default"><?php esc_html_e( 'Padrão (Automático)', 'blog-automatico' ); ?></option>
                        <?php foreach ( $templates as $key => $name ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="help"><?php esc_html_e( 'Layout com que cada artigo será montado.', 'blog-automatico' ); ?></span>
                </div>

                <div class="ba-form-actions" style="margin-top: 20px;">
                    <button type="submit" id="ba-btn-bulk-add" class="ba-btn ba-btn-primary" style="width: 100%;">
                        <span class="ba-spinner"></span>
                        <span class="dashicons dashicons-database-add"></span>
                        <span class="ba-btn-text"><?php esc_html_e( 'Inserir Ideias na Fila', 'blog-automatico' ); ?></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Coluna Direita: Gerenciamento da Fila -->
        <div class="ba-section">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; flex-wrap:wrap; gap: 10px;">
                <h2 class="ba-section-title" style="margin:0; padding:0; border:none;">
                    <span class="dashicons dashicons-editor-ul"></span>
                    <?php esc_html_e( 'Itens na Fila de Postagem', 'blog-automatico' ); ?>
                </h2>

                <div style="display:flex; gap:8px; align-items:center;">
                    <!-- Filtro -->
                    <select id="ba-filter-queue" class="ba-select" style="width:auto; padding: 6px 30px 6px 12px; font-size:12px;">
                        <option value="" <?php selected( $current_filter, '' ); ?>><?php esc_html_e( 'Todos os status', 'blog-automatico' ); ?></option>
                        <option value="queued" <?php selected( $current_filter, 'queued' ); ?>><?php esc_html_e( 'Na Fila', 'blog-automatico' ); ?></option>
                        <option value="processing" <?php selected( $current_filter, 'processing' ); ?>><?php esc_html_e( 'Processando', 'blog-automatico' ); ?></option>
                        <option value="done" <?php selected( $current_filter, 'done' ); ?>><?php esc_html_e( 'Publicados', 'blog-automatico' ); ?></option>
                        <option value="failed" <?php selected( $current_filter, 'failed' ); ?>><?php esc_html_e( 'Com Falha', 'blog-automatico' ); ?></option>
                    </select>

                    <?php if ( $queue['queued'] > 0 || $queue['failed'] > 0 ) : ?>
                        <button type="button" id="ba-clear-done" class="ba-btn ba-btn-secondary ba-btn-sm" data-status="done" title="<?php esc_attr_e( 'Limpar itens já concluídos', 'blog-automatico' ); ?>">
                            <?php esc_html_e( 'Limpar Concluídos', 'blog-automatico' ); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( empty( $items ) ) : ?>
                <div class="ba-empty">
                    <div class="icon"><span class="dashicons dashicons-clipboard"></span></div>
                    <h3><?php esc_html_e( 'A fila está vazia', 'blog-automatico' ); ?></h3>
                    <p><?php esc_html_e( 'Cole seus tópicos no formulário ao lado para começar o abastecimento.', 'blog-automatico' ); ?></p>
                </div>
            <?php else : ?>
                <div style="overflow-x:auto;">
                    <table class="ba-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th><?php esc_html_e( 'Tópico / Ideia', 'blog-automatico' ); ?></th>
                                <th style="width: 110px;"><?php esc_html_e( 'Status', 'blog-automatico' ); ?></th>
                                <th style="width: 140px;"><?php esc_html_e( 'Data de Entrada', 'blog-automatico' ); ?></th>
                                <th style="width: 70px; text-align:right;"><?php esc_html_e( 'Ação', 'blog-automatico' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $items as $item ) : ?>
                                <tr>
                                    <td><span class="ba-code">#<?php echo esc_html( $item->id ); ?></span></td>
                                    <td>
                                        <strong style="color:#f0f6fc;"><?php echo esc_html( $item->idea ); ?></strong>
                                        <?php if ( ! empty( $item->template_id ) && 'default' !== $item->template_id ) : ?>
                                            <span style="font-size:11px; color:#8b949e; display:block;">
                                                <?php esc_html_e( 'Template:', 'blog-automatico' ); ?> <?php echo esc_html( $item->template_id ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( 'queued' === $item->status ) : ?>
                                            <span class="ba-badge badge-pending">
                                                <span class="dashicons dashicons-clock" style="font-size:12px;width:12px;height:12px;"></span>
                                                <?php esc_html_e( 'Na fila', 'blog-automatico' ); ?>
                                            </span>
                                        <?php elseif ( 'processing' === $item->status ) : ?>
                                            <span class="ba-badge badge-publish">
                                                <span class="dashicons dashicons-update" style="font-size:12px;width:12px;height:12px;"></span>
                                                <?php esc_html_e( 'Gerando', 'blog-automatico' ); ?>
                                            </span>
                                        <?php elseif ( 'done' === $item->status ) : ?>
                                            <span class="ba-badge badge-publish">
                                                <span class="dashicons dashicons-yes" style="font-size:12px;width:12px;height:12px;"></span>
                                                <?php esc_html_e( 'Publicado', 'blog-automatico' ); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="ba-badge badge-failed">
                                                <span class="dashicons dashicons-no" style="font-size:12px;width:12px;height:12px;"></span>
                                                <?php esc_html_e( 'Falhou', 'blog-automatico' ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:#8b949e; font-size:12px;">
                                        <?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $item->created_at ) ) ); ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <button
                                            type="button"
                                            class="ba-btn-icon ba-remove-scheduled"
                                            data-id="<?php echo esc_attr( $item->id ); ?>"
                                            title="<?php esc_attr_e( 'Remover da fila', 'blog-automatico' ); ?>"
                                        >
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ( $total_pages > 1 ) : ?>
                    <div class="ba-pagination">
                        <?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
                            <?php if ( $p === $current_page ) : ?>
                                <span class="current"><?php echo esc_html( $p ); ?></span>
                            <?php else : ?>
                                <a href="<?php echo esc_url( add_query_arg( array( 'paged' => $p, 'status_filter' => $current_filter ) ) ); ?>">
                                    <?php echo esc_html( $p ); ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
