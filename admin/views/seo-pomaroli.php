<?php
/**
 * View: Painel SEO Pomaroli (Motor de SEO Programático).
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$integration   = BA_Pomaroli_Integration::get_instance();
$opportunities_mgr = BA_Pomaroli_Opportunities::get_instance();

$is_active     = $integration->is_active();
$stats         = $integration->get_stats();
$settings      = $opportunities_mgr->get_settings();
$content_types = BA_Pomaroli_Opportunities::get_content_types();

// Buscar contadores de artigos gerados vinculados à Pomaroli
global $wpdb;
$total_artigos = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_ba_pomaroli_combination_hash'" );
$publicados    = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE pm.meta_key = '_ba_pomaroli_combination_hash' AND p.post_status = 'publish'" );
$rascunhos     = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE pm.meta_key = '_ba_pomaroli_combination_hash' AND p.post_status = 'draft'" );

// Artigos em fila
$tabela_fila   = $wpdb->prefix . 'ba_scheduled_ideas';
$em_fila       = 0;
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tabela_fila}'" ) === $tabela_fila ) {
    $em_fila = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$tabela_fila} WHERE idea LIKE '%[POMAROLI:%' AND status = 'queued'" );
}

// Oportunidades disponíveis
$opportunities = $is_active ? $opportunities_mgr->generate_opportunities() : array();
$total_oportunidades = count( $opportunities );
?>

<div class="wrap ba-wrap">

    <!-- Header Principal -->
    <div class="ba-header">
        <div class="ba-header-left">
            <div class="ba-logo-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div>
                <h1><?php esc_html_e( 'SEO Pomaroli — Motor de Tráfego Orgânico', 'blog-automatico' ); ?></h1>
                <p><?php esc_html_e( 'Gere artigos otimizados utilizando os dados reais de questões, bancas e disciplinas do Sistema Pomaroli.', 'blog-automatico' ); ?></p>
            </div>
        </div>
        <div class="ba-header-right">
            <span class="ba-version">v<?php echo esc_html( BA_VERSION ); ?></span>
            <button type="button" class="ba-btn ba-btn-secondary ba-btn-sm" id="ba-pomaroli-sync-btn">
                <span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;line-height:1.2;"></span>
                <?php esc_html_e( 'Sincronizar Dados / Limpar Cache', 'blog-automatico' ); ?>
            </button>
        </div>
    </div>

    <?php if ( ! $is_active ) : ?>
        <div class="ba-alert ba-alert-warning" style="margin-bottom: 25px; padding: 18px 20px; border-left: 4px solid #e3b341; background: #261f12; border-radius: 6px;">
            <div style="display:flex; align-items:center; gap: 10px;">
                <span class="dashicons dashicons-warning" style="color:#e3b341; font-size:24px; width:24px; height:24px;"></span>
                <div>
                    <strong style="color:#f0f6fc; font-size:15px;"><?php esc_html_e( 'Sistema Pomaroli não detectado ou inativo', 'blog-automatico' ); ?></strong>
                    <p style="margin: 4px 0 0; color:#8b949e; font-size:13px;">
                        <?php esc_html_e( 'O plugin Pomaroli Questões (interage-questoes) não está ativo neste site. Ative o plugin para carregar as questões e gerar oportunidades de SEO reais.', 'blog-automatico' ); ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Grid de Métricas do Sistema Pomaroli -->
    <div class="ba-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 25px;">
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-clipboard"></span></div>
            <div class="number"><?php echo esc_html( number_format_i18n( $stats['total_questoes'] ) ); ?></div>
            <div class="label"><?php esc_html_e( 'Questões Disponíveis', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-welcome-widgets-menus"></span></div>
            <div class="number"><?php echo esc_html( number_format_i18n( $stats['total_bancas'] ) ); ?></div>
            <div class="label"><?php esc_html_e( 'Bancas Ativas', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-category"></span></div>
            <div class="number"><?php echo esc_html( number_format_i18n( $stats['total_disciplinas'] ) ); ?></div>
            <div class="label"><?php esc_html_e( 'Disciplinas', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-tag"></span></div>
            <div class="number"><?php echo esc_html( number_format_i18n( $stats['total_assuntos'] ) ); ?></div>
            <div class="label"><?php esc_html_e( 'Assuntos Mapeados', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-lightbulb"></span></div>
            <div class="number" style="color:#B4D443;"><?php echo esc_html( $total_oportunidades ); ?></div>
            <div class="label"><?php esc_html_e( 'Oportunidades SEO', 'blog-automatico' ); ?></div>
        </div>
    </div>

    <!-- Grid de Métricas de Artigos SEO -->
    <div class="ba-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 25px;">
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-media-document"></span></div>
            <div class="number"><?php echo esc_html( $total_artigos ); ?></div>
            <div class="label"><?php esc_html_e( 'Artigos Gerados', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-yes-alt" style="color:#81c784;"></span></div>
            <div class="number" style="color:#81c784;"><?php echo esc_html( $publicados ); ?></div>
            <div class="label"><?php esc_html_e( 'Artigos Publicados', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-edit"></span></div>
            <div class="number" style="color:#e3b341;"><?php echo esc_html( $rascunhos ); ?></div>
            <div class="label"><?php esc_html_e( 'Rascunhos para Revisão', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="number"><?php echo esc_html( $em_fila ); ?></div>
            <div class="label"><?php esc_html_e( 'Artigos na Fila', 'blog-automatico' ); ?></div>
        </div>
        <div class="ba-stat-card">
            <div class="icon"><span class="dashicons dashicons-calendar-alt"></span></div>
            <div class="number" style="font-size:14px; line-height: 2.2;"><?php echo ! empty( $settings['last_sync'] ) ? esc_html( date_i18n( 'd/m H:i', strtotime( $settings['last_sync'] ) ) ) : 'N/A'; ?></div>
            <div class="label"><?php esc_html_e( 'Última Sincronização', 'blog-automatico' ); ?></div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

        <!-- Coluna Esquerda: Oportunidades Identificadas -->
        <div class="ba-section" style="margin-bottom:0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; flex-wrap:wrap; gap: 12px;">
                <h2 class="ba-section-title" style="margin:0; padding:0; border:none;">
                    <span class="dashicons dashicons-star-filled" style="color:#B4D443;"></span>
                    <?php esc_html_e( 'Oportunidades de SEO Programático', 'blog-automatico' ); ?>
                </h2>

                <div style="display:flex; gap: 8px; align-items:center;">
                    <select id="ba-filter-type" class="ba-form-control" style="font-size:12px; padding: 4px 8px; width:auto;">
                        <option value=""><?php esc_html_e( 'Todos os Tipos', 'blog-automatico' ); ?></option>
                        <?php foreach ( $content_types as $k => $info ) : ?>
                            <option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $info['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="ba-filter-status" class="ba-form-control" style="font-size:12px; padding: 4px 8px; width:auto;">
                        <option value=""><?php esc_html_e( 'Status: Todos', 'blog-automatico' ); ?></option>
                        <option value="nao_criado"><?php esc_html_e( 'Disponíveis (Não Criados)', 'blog-automatico' ); ?></option>
                        <option value="criado"><?php esc_html_e( 'Já Gerados', 'blog-automatico' ); ?></option>
                    </select>
                </div>
            </div>

            <?php if ( empty( $opportunities ) ) : ?>
                <div class="ba-empty">
                    <div class="icon"><span class="dashicons dashicons-search"></span></div>
                    <h3><?php esc_html_e( 'Nenhuma oportunidade encontrada com os critérios atuais', 'blog-automatico' ); ?></h3>
                    <p><?php esc_html_e( 'Tente diminuir a quantidade mínima de questões no painel lateral de configurações ou cadastre novas questões no Sistema Pomaroli.', 'blog-automatico' ); ?></p>
                </div>
            <?php else : ?>
                <div style="overflow-x:auto;">
                    <table class="ba-table" id="ba-opportunities-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Título Sugerido / Ideia', 'blog-automatico' ); ?></th>
                                <th style="width: 130px;"><?php esc_html_e( 'Tipo', 'blog-automatico' ); ?></th>
                                <th style="width: 90px; text-align:center;"><?php esc_html_e( 'Questões', 'blog-automatico' ); ?></th>
                                <th style="width: 100px; text-align:center;"><?php esc_html_e( 'Situação', 'blog-automatico' ); ?></th>
                                <th style="width: 120px; text-align:right; white-space:nowrap;"><?php esc_html_e( 'Ações', 'blog-automatico' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $opportunities as $op ) : ?>
                                <tr data-type="<?php echo esc_attr( $op['type'] ); ?>" data-status="<?php echo $op['existing_post_id'] > 0 ? 'criado' : 'nao_criado'; ?>">
                                    <td>
                                        <strong style="color:#f0f6fc; display:block; font-size:13px; margin-bottom: 2px;">
                                            <?php echo esc_html( $op['title'] ); ?>
                                        </strong>
                                        <div style="font-size:11px; color:#8b949e; display:flex; gap: 10px; flex-wrap:wrap;">
                                            <?php if ( ! empty( $op['banca'] ) ) : ?>
                                                <span><strong>Banca:</strong> <?php echo esc_html( $op['banca'] ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $op['disciplina'] ) ) : ?>
                                                <span><strong>Matéria:</strong> <?php echo esc_html( $op['disciplina'] ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $op['assunto'] ) ) : ?>
                                                <span><strong>Assunto:</strong> <?php echo esc_html( $op['assunto'] ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $op['instituicao'] ) ) : ?>
                                                <span><strong>Instituição:</strong> <?php echo esc_html( $op['instituicao'] ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="ba-badge badge-pending" style="font-size:10px;">
                                            <?php echo esc_html( $op['type_label'] ); ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center; color:#B4D443; font-weight:700;">
                                        <?php echo esc_html( number_format_i18n( $op['questoes_count'] ) ); ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ( $op['existing_post_id'] > 0 ) : ?>
                                            <?php if ( 'publish' === $op['existing_status'] ) : ?>
                                                <span class="ba-badge badge-publish" title="ID #<?php echo esc_attr( $op['existing_post_id'] ); ?>">Publicado</span>
                                            <?php else : ?>
                                                <span class="ba-badge badge-pending" title="ID #<?php echo esc_attr( $op['existing_post_id'] ); ?>">Rascunho</span>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span style="font-size:11px; color:#8b949e;">Disponível</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <div class="ba-actions-group">
                                            <?php if ( $op['existing_post_id'] > 0 ) : ?>
                                                <a href="<?php echo esc_url( get_edit_post_link( $op['existing_post_id'] ) ); ?>" class="ba-btn-icon" target="_blank" title="<?php esc_attr_e( 'Editar Artigo', 'blog-automatico' ); ?>">
                                                    <span class="dashicons dashicons-edit"></span>
                                                </a>
                                                <a href="<?php echo esc_url( get_permalink( $op['existing_post_id'] ) ); ?>" class="ba-btn-icon" target="_blank" title="<?php esc_attr_e( 'Ver no Site', 'blog-automatico' ); ?>">
                                                    <span class="dashicons dashicons-external"></span>
                                                </a>
                                            <?php else : ?>
                                                <button type="button" class="ba-btn ba-btn-sm ba-btn-secondary ba-queue-op-btn" data-hash="<?php echo esc_attr( $op['hash'] ); ?>" title="<?php esc_attr_e( 'Adicionar à Fila de Geração', 'blog-automatico' ); ?>">
                                                    + Fila
                                                </button>
                                                <button type="button" class="ba-btn ba-btn-sm ba-btn-primary ba-generate-now-btn" data-hash="<?php echo esc_attr( $op['hash'] ); ?>" title="<?php esc_attr_e( 'Gerar Artigo Imediatamente (Rascunho)', 'blog-automatico' ); ?>">
                                                    Gerar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Coluna Direita: Painel de Controle e Modo Seguro -->
        <div>
            <div class="ba-section">
                <h3 class="ba-section-title" style="margin-top:0;">
                    <span class="dashicons dashicons-shield"></span>
                    <?php esc_html_e( 'Configurações de Segurança', 'blog-automatico' ); ?>
                </h3>

                <form id="ba-pomaroli-settings-form">
                    <div class="ba-form-group" style="margin-bottom:16px;">
                        <label class="ba-form-label" style="font-size:12px;"><?php esc_html_e( 'Quantidade mínima de questões:', 'blog-automatico' ); ?></label>
                        <input type="number" name="min_questoes" class="ba-form-control" value="<?php echo esc_attr( $settings['min_questoes'] ); ?>" min="1" max="1000" style="width:100%;">
                        <span class="ba-form-desc" style="font-size:11px;"><?php esc_html_e( 'Não criar oportunidades para combinações sem conteúdo suficiente.', 'blog-automatico' ); ?></span>
                    </div>

                    <div class="ba-form-group" style="margin-bottom:16px;">
                        <label class="ba-form-label" style="font-size:12px;"><?php esc_html_e( 'Geração automática:', 'blog-automatico' ); ?></label>
                        <select name="auto_generate" class="ba-form-control" style="width:100%;">
                            <option value="0" <?php selected( $settings['auto_generate'], '0' ); ?>><?php esc_html_e( 'DESATIVADA (OFF - Seguro)', 'blog-automatico' ); ?></option>
                            <option value="1" <?php selected( $settings['auto_generate'], '1' ); ?>><?php esc_html_e( 'ATIVADA (ON)', 'blog-automatico' ); ?></option>
                        </select>
                    </div>

                    <div class="ba-form-group" style="margin-bottom:16px;">
                        <label class="ba-form-label" style="font-size:12px;"><?php esc_html_e( 'Publicação automática:', 'blog-automatico' ); ?></label>
                        <select name="auto_publish" class="ba-form-control" style="width:100%;">
                            <option value="0" <?php selected( $settings['auto_publish'], '0' ); ?>><?php esc_html_e( 'DESATIVADA (OFF - Seguro)', 'blog-automatico' ); ?></option>
                            <option value="1" <?php selected( $settings['auto_publish'], '1' ); ?>><?php esc_html_e( 'ATIVADA (ON)', 'blog-automatico' ); ?></option>
                        </select>
                    </div>

                    <div class="ba-form-group" style="margin-bottom:16px;">
                        <label class="ba-form-label" style="font-size:12px;"><?php esc_html_e( 'Modo de Publicação Padrão:', 'blog-automatico' ); ?></label>
                        <select name="post_status" class="ba-form-control" style="width:100%;">
                            <option value="draft" <?php selected( $settings['post_status'], 'draft' ); ?>><?php esc_html_e( 'Rascunho (Revisão Manual)', 'blog-automatico' ); ?></option>
                            <option value="publish" <?php selected( $settings['post_status'], 'publish' ); ?>><?php esc_html_e( 'Publicado Direto', 'blog-automatico' ); ?></option>
                        </select>
                    </div>

                    <div class="ba-form-group" style="margin-bottom:16px;">
                        <label class="ba-form-label" style="font-size:12px;"><?php esc_html_e( 'Posts por dia (Limite):', 'blog-automatico' ); ?></label>
                        <input type="number" name="posts_per_day" class="ba-form-control" value="<?php echo esc_attr( $settings['posts_per_day'] ); ?>" min="1" max="50" style="width:100%;">
                    </div>

                    <div class="ba-form-group" style="margin-bottom:20px;">
                        <label style="display:flex; align-items:center; gap: 8px; cursor:pointer; font-size:12px; color:#c9d1d9;">
                            <input type="checkbox" name="enable_internal_cta" value="1" <?php checked( $settings['enable_internal_cta'], '1' ); ?>>
                            <?php esc_html_e( 'Inserir CTA e Links Internos para a Pomaroli', 'blog-automatico' ); ?>
                        </label>
                    </div>

                    <div class="ba-form-group" style="margin-bottom:20px;">
                        <label style="display:flex; align-items:center; gap: 8px; cursor:pointer; font-size:12px; color:#c9d1d9;">
                            <input type="checkbox" name="include_real_samples" value="1" <?php checked( $settings['include_real_samples'], '1' ); ?>>
                            <?php esc_html_e( 'Fornecer questões reais para a IA analisar', 'blog-automatico' ); ?>
                        </label>
                    </div>

                    <button type="submit" class="ba-btn ba-btn-primary" style="width:100%;">
                        <?php esc_html_e( 'Salvar Configurações SEO Pomaroli', 'blog-automatico' ); ?>
                    </button>
                </form>
            </div>

            <!-- Card Informativo de Integridade -->
            <div class="ba-section" style="border-left: 4px solid #B4D443;">
                <h4 style="margin:0 0 10px; color:#f0f6fc; font-size:13px; display:flex; align-items:center; gap: 6px;">
                    <span class="dashicons dashicons-lock" style="color:#B4D443; font-size:16px;"></span>
                    <?php esc_html_e( 'Regras de Integridade Ativas', 'blog-automatico' ); ?>
                </h4>
                <ul style="margin:0; padding-left:16px; font-size:11px; line-height:1.7; color:#8b949e;">
                    <li>Zero duplicação de tabelas ou questões.</li>
                    <li>Fonte de verdade exclusiva: Sistema Pomaroli.</li>
                    <li>A IA recebe dados reais exatos e é proibida de inventar números.</li>
                    <li>Categorias restritas e controladas (sem criação aleatória).</li>
                    <li>Prevenção de duplicidade por hash de entidades.</li>
                </ul>
            </div>
        </div>

    </div>

</div>

<!-- Modal / Toast de Notificação -->
<div id="ba-pomaroli-modal" class="ba-modal" style="display:none;">
    <div class="ba-modal-content" style="max-width:480px; text-align:center; padding: 30px;">
        <div class="ba-spinner" style="display:inline-block; width:30px; height:30px; border-width:3px; margin-bottom:15px;"></div>
        <h3 id="ba-pomaroli-modal-title" style="color:#ffffff; margin:0 0 10px; font-size:16px;"></h3>
        <p id="ba-pomaroli-modal-desc" style="color:#8b949e; font-size:13px; margin:0;"></p>
    </div>
</div>
