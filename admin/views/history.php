<?php
/**
 * Admin View: History (v1.1.0)
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$logger   = BA_Logger::get_instance();
$page     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$status   = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$per_page = 20;
$logs     = $logger->get_logs( $page, $per_page, $status );
$stats    = $logger->get_stats();

$base_url = admin_url( 'admin.php?page=blog-automatico-history' );
?>

<div class="ba-wrap">
    <!-- Header -->
    <div class="ba-header">
        <div class="ba-header-left">
            <div class="ba-header-icon">
                <span class="dashicons dashicons-backup" style="color:#fff;font-size:24px;width:24px;height:24px;line-height:1;"></span>
            </div>
            <div>
                <h1><?php esc_html_e( 'Histórico de Artigos Gerados', 'blog-automatico' ); ?></h1>
                <div class="ba-header-sub">
                    <?php
                    printf(
                        /* translators: %d: total de posts */
                        esc_html__( '%d posts processados no histórico do sistema', 'blog-automatico' ),
                        $stats['total_posts']
                    );
                    ?>
                </div>
            </div>
        </div>
        <div>
            <span class="ba-version">v<?php echo esc_html( BA_VERSION ); ?></span>
        </div>
    </div>

    <!-- Notices Area -->
    <div class="ba-notices-area"></div>

    <!-- Stats Cards -->
    <div class="ba-stats">
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="number"><?php echo esc_html( $stats['total_posts'] ); ?></div>
            <div class="label"><?php esc_html_e( 'Posts com Sucesso', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-warning"></span></div>
            <div class="number"><?php echo esc_html( $stats['total_errors'] ); ?></div>
            <div class="label"><?php esc_html_e( 'Erros / Falhas', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-chart-area"></span></div>
            <div class="number"><?php echo esc_html( number_format( $stats['total_tokens'] ) ); ?></div>
            <div class="label"><?php esc_html_e( 'Tokens Consumidos', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-format-image"></span></div>
            <div class="number"><?php echo esc_html( $stats['total_images'] ); ?></div>
            <div class="label"><?php esc_html_e( 'Imagens Geradas', 'blog-automatico' ); ?></div>
        </div>
    </div>

    <!-- Seção da Tabela com Filtros -->
    <div class="ba-section">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; flex-wrap:wrap; gap: 12px;">
            <h2 class="ba-section-title" style="margin:0; padding:0; border:none;">
                <span class="dashicons dashicons-list-view"></span>
                <?php esc_html_e( 'Registros Detalhados', 'blog-automatico' ); ?>
            </h2>

            <!-- Filtros Rápidos e Ações -->
            <div style="display:flex; gap: 8px; flex-wrap:wrap; align-items:center;">
                <div style="display:flex; gap: 6px;">
                    <a href="<?php echo esc_url( $base_url ); ?>" class="ba-btn ba-btn-sm <?php echo empty( $status ) ? 'ba-btn-primary' : 'ba-btn-secondary'; ?>">
                        <?php esc_html_e( 'Todos', 'blog-automatico' ); ?>
                    </a>
                    <a href="<?php echo esc_url( add_query_arg( 'status', 'success', $base_url ) ); ?>" class="ba-btn ba-btn-sm <?php echo 'success' === $status ? 'ba-btn-primary' : 'ba-btn-secondary'; ?>">
                        <?php esc_html_e( 'Sucesso', 'blog-automatico' ); ?>
                    </a>
                    <a href="<?php echo esc_url( add_query_arg( 'status', 'error', $base_url ) ); ?>" class="ba-btn ba-btn-sm <?php echo 'error' === $status ? 'ba-btn-primary' : 'ba-btn-secondary'; ?>">
                        <?php esc_html_e( 'Falhas', 'blog-automatico' ); ?>
                    </a>
                </div>

                <?php if ( ! empty( $logs['items'] ) ) : ?>
                    <div style="display:flex; gap: 6px; margin-left: 10px;">
                        <?php if ( $stats['total_errors'] > 0 ) : ?>
                            <button type="button" class="ba-btn ba-btn-danger ba-btn-sm ba-clear-logs" data-status="error">
                                <span class="dashicons dashicons-trash" style="font-size:14px;width:14px;height:14px;line-height:1.2;"></span>
                                <?php esc_html_e( 'Limpar Falhas', 'blog-automatico' ); ?>
                            </button>
                        <?php endif; ?>
                        <button type="button" class="ba-btn ba-btn-ghost ba-btn-sm ba-clear-logs" data-status="all">
                            <?php esc_html_e( 'Limpar Histórico', 'blog-automatico' ); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( empty( $logs['items'] ) ) : ?>
            <div class="ba-empty">
                <div class="icon"><span class="dashicons dashicons-archive"></span></div>
                <h3><?php esc_html_e( 'Nenhum registro encontrado', 'blog-automatico' ); ?></h3>
                <p><?php esc_html_e( 'Os artigos criados via IA (manual ou piloto automático) serão listados aqui.', 'blog-automatico' ); ?></p>
            </div>
        <?php else : ?>
            <div style="overflow-x:auto;">
                <table class="ba-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th><?php esc_html_e( 'Tópico / Ideia', 'blog-automatico' ); ?></th>
                            <th style="width: 100px;"><?php esc_html_e( 'Status', 'blog-automatico' ); ?></th>
                            <th style="width: 130px;"><?php esc_html_e( 'Modelo IA', 'blog-automatico' ); ?></th>
                            <th style="width: 90px;"><?php esc_html_e( 'Tokens', 'blog-automatico' ); ?></th>
                            <th style="width: 70px;"><?php esc_html_e( 'Imagens', 'blog-automatico' ); ?></th>
                            <th style="width: 70px;"><?php esc_html_e( 'Tempo', 'blog-automatico' ); ?></th>
                            <th style="width: 130px;"><?php esc_html_e( 'Data', 'blog-automatico' ); ?></th>
                            <th style="width: 100px; text-align:right;"><?php esc_html_e( 'Ações', 'blog-automatico' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs['items'] as $log ) : ?>
                            <tr id="ba-log-row-<?php echo esc_attr( $log->id ); ?>">
                                <td><span class="ba-code">#<?php echo esc_html( $log->id ); ?></span></td>
                                <td>
                                    <strong style="color:#f0f6fc;" title="<?php echo esc_attr( $log->idea ); ?>">
                                        <?php echo esc_html( wp_trim_words( $log->idea, 9, '...' ) ); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php if ( 'success' === $log->status ) : ?>
                                        <span class="ba-badge badge-publish">
                                            <span class="dashicons dashicons-yes" style="font-size:12px;width:12px;height:12px;"></span>
                                            <?php esc_html_e( 'Sucesso', 'blog-automatico' ); ?>
                                        </span>
                                    <?php elseif ( 'processing' === $log->status ) : ?>
                                        <span class="ba-badge badge-pending">
                                            <span class="dashicons dashicons-update" style="font-size:12px;width:12px;height:12px;"></span>
                                            <?php esc_html_e( 'Gerando', 'blog-automatico' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="ba-badge badge-failed" title="<?php echo esc_attr( $log->error_message ); ?>">
                                            <span class="dashicons dashicons-no" style="font-size:12px;width:12px;height:12px;"></span>
                                            <?php esc_html_e( 'Falha', 'blog-automatico' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="ba-code"><?php echo esc_html( $log->model_used ? $log->model_used : 'N/A' ); ?></span></td>
                                <td style="color:#8b949e;"><?php echo esc_html( number_format( $log->tokens_used ) ); ?></td>
                                <td style="color:#8b949e;"><?php echo esc_html( $log->images_generated ); ?></td>
                                <td style="color:#8b949e;"><?php echo esc_html( round( $log->generation_time, 1 ) ); ?>s</td>
                                <td style="color:#8b949e; font-size:12px;"><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $log->created_at ) ) ); ?></td>
                                <td style="text-align:right;">
                                    <?php if ( $log->post_id > 0 ) : ?>
                                        <a href="<?php echo esc_url( get_edit_post_link( $log->post_id ) ); ?>" class="ba-btn-icon" target="_blank" title="<?php esc_attr_e( 'Editar Post', 'blog-automatico' ); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo esc_url( get_permalink( $log->post_id ) ); ?>" class="ba-btn-icon" target="_blank" title="<?php esc_attr_e( 'Ver Post no Site', 'blog-automatico' ); ?>">
                                            <span class="dashicons dashicons-external"></span>
                                        </a>
                                    <?php elseif ( ! empty( $log->error_message ) ) : ?>
                                        <span class="dashicons dashicons-info" style="color:#f85149; cursor:help; vertical-align:middle; margin-right:4px;" title="<?php echo esc_attr( $log->error_message ); ?>"></span>
                                    <?php endif; ?>
                                    <button type="button" class="ba-btn-icon ba-delete-log" data-id="<?php echo esc_attr( $log->id ); ?>" title="<?php esc_attr_e( 'Excluir este registro', 'blog-automatico' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ( $logs['total_pages'] > 1 ) : ?>
                <div class="ba-pagination">
                    <?php
                    for ( $i = 1; $i <= $logs['total_pages']; $i++ ) {
                        $url = add_query_arg( array( 'paged' => $i, 'status' => $status ), $base_url );
                        if ( $i === $page ) {
                            echo '<span class="current">' . esc_html( $i ) . '</span>';
                        } else {
                            echo '<a href="' . esc_url( $url ) . '">' . esc_html( $i ) . '</a>';
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
