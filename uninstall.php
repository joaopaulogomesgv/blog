<?php
/**
 * Rotinas de desinstalação do plugin Blog Automático.
 *
 * @package BlogAutomatico
 */

// Se o uninstall não foi chamado pelo WordPress, abortar
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Remover opções do plugin
$options = array(
    'ba_openai_api_key',
    'ba_gpt_model',
    'ba_image_model',
    'ba_image_size',
    'ba_content_tone',
    'ba_language',
    'ba_article_length',
    'ba_default_template',
    'ba_publish_status',
    'ba_default_category',
    'ba_seo_plugin',
    'ba_daily_limit',
    'ba_notification_email',
    'ba_db_version',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Remover tabelas customizadas
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ba_posts_log" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ba_scheduled_ideas" );

// Remover cron events
wp_clear_scheduled_hook( 'ba_process_scheduled_ideas' );
wp_clear_scheduled_hook( 'ba_daily_cleanup' );

// Limpar transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ba_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ba_%'" );
