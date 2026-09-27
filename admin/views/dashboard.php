<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$logger    = BA_Logger::get_instance();
$stats     = $logger->get_stats();
$settings  = BA_Settings::get_instance();
$scheduler = BA_Scheduler::get_instance();
$queue     = $scheduler->get_queue_stats();
$provider  = $settings->get( 'ba_ai_provider' );
$providers = BA_AI_Connector::get_providers();
$provider_name = isset( $providers[ $provider ] ) ? $providers[ $provider ]['name'] : $provider;
$auto_on   = $settings->is_auto_post_enabled();
$ppd       = $settings->get_posts_per_day();
$recent    = $logger->get_logs( 1, 5 );
?>
<div class="ba-wrap">
<div class="ba-notices-area"></div>

<!-- Header -->
<div class="ba-header">
    <div class="ba-header-left">
        <div class="ba-header-icon"><span class="dashicons dashicons-edit-large" style="color:#fff;margin-top:2px;"></span></div>
        <div>
            <h1>Blog Automático</h1>
            <div class="ba-header-sub">Painel de controle &middot; <?php echo esc_html( $provider_name ); ?></div>
        </div>
    </div>
    <span class="ba-version">v<?php echo esc_html( BA_VERSION ); ?></span>
</div>

<?php if ( ! $settings->has_api_key() ) : ?>
<div class="ba-alert ba-alert-warning" style="background-color: #271805 !important; border: 1px solid #78350f !important; border-left: 5px solid #f59e0b !important; color: #ffffff !important; padding: 14px 18px !important; display: flex !important; align-items: center !important; gap: 12px !important; border-radius: 6px !important; margin-bottom: 20px !important;">
    <span class="dashicons dashicons-warning" style="color: #f59e0b !important; font-size: 20px !important; width: 20px !important; height: 20px !important; flex-shrink: 0 !important;"></span>
    <span style="color: #ffffff !important; font-size: 13.5px !important; line-height: 1.5 !important;">
        <strong style="color: #ffffff !important;"><?php esc_html_e( 'Atenção:', 'blog-automatico' ); ?></strong>
        <?php printf( esc_html__( 'Nenhuma API Key configurada para o provedor ativo (%s).', 'blog-automatico' ), '<strong style="color: #ffffff !important;">' . esc_html( $active_provider ) . '</strong>' ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-automatico-settings' ) ); ?>" style="color: #B4D443 !important; font-weight: 700 !important; text-decoration: underline !important;"><?php esc_html_e( 'Cadastre uma chave aqui →', 'blog-automatico' ); ?></a>
    </span>
</div>
<?php endif; ?>

<!-- Auto-Post Banner -->
<div class="ba-auto-banner <?php echo $auto_on ? 'on' : 'off'; ?>">
    <div class="status-dot"></div>
    <div class="info">
        <?php if ( $auto_on ) : ?>
            <strong>Publicação automática ativa</strong>
            <span><?php printf( '%d post(s)/dia &middot; %d ideias na fila &middot; Horário: %s às %s',
                $ppd, $queue['queued'],
                esc_html( $settings->get( 'ba_auto_post_time_start' ) ),
                esc_html( $settings->get( 'ba_auto_post_time_end' ) )
            ); ?></span>
        <?php else : ?>
            <strong>Publicação automática desativada</strong>
            <span><?php printf( '%d ideias na fila aguardando', $queue['queued'] ); ?></span>
        <?php endif; ?>
    </div>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-automatico-settings' ) ); ?>" class="ba-btn ba-btn-ghost ba-btn-sm">Configurar</a>
</div>

<!-- Stats -->
<div class="ba-stats">
    <div class="ba-stat-card">
        <div class="icon"><span class="dashicons dashicons-media-text"></span></div>
        <div class="number"><?php echo esc_html( $stats['total_posts'] ); ?></div>
        <div class="label">Posts Gerados</div>
    </div>
    <div class="ba-stat-card">
        <div class="icon"><span class="dashicons dashicons-list-view"></span></div>
        <div class="number"><?php echo esc_html( $queue['queued'] ); ?></div>
        <div class="label">Na Fila</div>
    </div>
    <div class="ba-stat-card">
        <div class="icon"><span class="dashicons dashicons-format-image"></span></div>
        <div class="number"><?php echo esc_html( $stats['total_images'] ); ?></div>
        <div class="label">Imagens</div>
    </div>
    <div class="ba-stat-card">
        <div class="icon"><span class="dashicons dashicons-clock"></span></div>
        <div class="number"><?php echo esc_html( $stats['avg_time'] ); ?>s</div>
        <div class="label">Tempo Médio</div>
    </div>
    <div class="ba-stat-card">
        <div class="icon"><span class="dashicons dashicons-calendar-alt"></span></div>
        <div class="number"><?php echo esc_html( $stats['today_posts'] ); ?>/<?php echo esc_html( $ppd ); ?></div>
        <div class="label">Posts Hoje</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="ba-section">
    <div class="ba-btn-group">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-automatico-new' ) ); ?>" class="ba-btn ba-btn-primary ba-btn-lg">
            <span class="dashicons dashicons-plus-alt2" style="margin-top:2px;"></span> Criar Post Agora
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=blog-automatico-queue' ) ); ?>" class="ba-btn ba-btn-ghost ba-btn-lg">
            <span class="dashicons dashicons-upload" style="margin-top:2px;"></span> Adicionar Ideias em Massa
        </a>
    </div>
</div>

<!-- Recent -->
<div class="ba-section">
    <h2 class="ba-section-title"><span class="dashicons dashicons-backup"></span> Últimas Gerações</h2>
    <?php if ( empty( $recent['items'] ) ) : ?>
        <div class="ba-empty">
            <div class="icon"><span class="dashicons dashicons-portfolio" style="font-size:40px;width:40px;height:40px;"></span></div>
            <h3>Nenhum post gerado ainda</h3>
            <p>Crie seu primeiro post ou adicione ideias à fila.</p>
        </div>
    <?php else : ?>
        <div class="ba-table-wrap">
            <table class="ba-table">
                <thead><tr>
                    <th>Ideia</th><th>Status</th><th>Tokens</th><th>Imagens</th><th>Tempo</th><th>Data</th><th style="text-align:right; width: 120px; white-space:nowrap;">Ações</th>
                </tr></thead>
                <tbody>
                <?php foreach ( $recent['items'] as $log ) : ?>
                <tr id="ba-log-row-<?php echo esc_attr( $log->id ); ?>">
                    <td><?php echo esc_html( wp_trim_words( $log->idea, 10, '…' ) ); ?></td>
                    <td><span class="ba-badge <?php echo esc_attr( $log->status ); ?>" title="<?php echo esc_attr( $log->error_message ); ?>"><?php echo esc_html( $log->status ); ?></span></td>
                    <td><?php echo esc_html( number_format( $log->tokens_used ) ); ?></td>
                    <td><?php echo esc_html( $log->images_generated ); ?></td>
                    <td><?php echo esc_html( round( $log->generation_time, 1 ) ); ?>s</td>
                    <td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $log->created_at ) ) ); ?></td>
                    <td style="text-align:right; white-space:nowrap;">
                        <div class="ba-actions-group">
                            <?php if ( $log->post_id > 0 ) : ?>
                                <a href="<?php echo esc_url( get_edit_post_link( $log->post_id ) ); ?>" class="ba-btn-icon" target="_blank" title="<?php esc_attr_e( 'Editar Post', 'blog-automatico' ); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="<?php echo esc_url( get_permalink( $log->post_id ) ); ?>" class="ba-btn-icon" target="_blank" title="<?php esc_attr_e( 'Ver Post no Site', 'blog-automatico' ); ?>">
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            <?php elseif ( ! empty( $log->error_message ) ) : ?>
                                <span class="ba-btn-icon" style="color:#f85149; cursor:help;" title="<?php echo esc_attr( $log->error_message ); ?>">
                                    <span class="dashicons dashicons-info"></span>
                                </span>
                            <?php endif; ?>
                            <button type="button" class="ba-btn-icon ba-delete-log" data-id="<?php echo esc_attr( $log->id ); ?>" title="<?php esc_attr_e( 'Apagar este registro', 'blog-automatico' ); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>
