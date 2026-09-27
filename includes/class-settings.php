<?php
/**
 * Configurações do plugin — v1.1.0 com múltiplos provedores.
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_Settings {

    private static $instance = null;

    private $defaults = array(
        'ba_ai_provider'         => 'openai',
        'ba_api_key_openai'      => '',
        'ba_api_key_gemini'      => '',
        'ba_api_key_groq'        => '',
        'ba_api_key_grok'        => '',
        'ba_api_key_deepseek'    => '',
        'ba_text_model'          => 'gpt-4o',
        'ba_image_model'         => 'dall-e-3',
        'ba_image_size'          => '1792x1024',
        'ba_content_tone'        => 'informal',
        'ba_language'            => 'pt_BR',
        'ba_article_length'      => 1500,
        'ba_default_template'    => 'gutenberg',
        'ba_publish_status'      => 'draft',
        'ba_default_category'    => 0,
        'ba_seo_plugin'          => 'none',
        'ba_daily_limit'         => 10,
        'ba_notification_email'  => '',
        'ba_auto_post_enabled'   => 0,
        'ba_posts_per_day'       => 1,
        'ba_auto_post_time_start' => '08:00',
        'ba_auto_post_time_end'  => '18:00',
        // Treinamento IA
        'ba_training_writing_style'    => 'natural',
        'ba_training_persona'          => '',
        'ba_training_reference_texts'  => '',
        'ba_training_forbidden_words'  => '',
        'ba_training_preferred_words'  => '',
        'ba_training_custom_rules'     => '',
        'ba_training_humanize_level'   => 'high',
        'ba_training_sentence_variety' => 'high',
        'ba_training_paragraph_style'  => 'varied',
        'ba_training_avoid_patterns'   => '1',
    );

    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_settings() {
        $settings = array(
            'ba_ai_provider'         => 'sanitize_text_field',
            'ba_api_key_openai'      => 'sanitize_text_field',
            'ba_api_key_gemini'      => 'sanitize_text_field',
            'ba_api_key_groq'        => 'sanitize_text_field',
            'ba_api_key_grok'        => 'sanitize_text_field',
            'ba_api_key_deepseek'    => 'sanitize_text_field',
            'ba_text_model'          => 'sanitize_text_field',
            'ba_image_model'         => 'sanitize_text_field',
            'ba_image_size'          => 'sanitize_text_field',
            'ba_content_tone'        => 'sanitize_text_field',
            'ba_language'            => 'sanitize_text_field',
            'ba_article_length'      => 'absint',
            'ba_default_template'    => 'sanitize_text_field',
            'ba_publish_status'      => 'sanitize_text_field',
            'ba_default_category'    => 'absint',
            'ba_seo_plugin'          => 'sanitize_text_field',
            'ba_daily_limit'         => 'absint',
            'ba_notification_email'  => 'sanitize_email',
            'ba_auto_post_enabled'   => 'absint',
            'ba_posts_per_day'       => 'absint',
            'ba_auto_post_time_start' => 'sanitize_text_field',
            'ba_auto_post_time_end'  => 'sanitize_text_field',
        );

        foreach ( $settings as $key => $callback ) {
            register_setting( 'ba_settings_group', $key, array(
                'sanitize_callback' => $callback,
            ));
        }

        // Registrar settings do Treinamento IA
        $training_settings = array(
            'ba_training_writing_style'    => 'sanitize_text_field',
            'ba_training_persona'          => 'sanitize_textarea_field',
            'ba_training_reference_texts'  => 'sanitize_textarea_field',
            'ba_training_forbidden_words'  => 'sanitize_textarea_field',
            'ba_training_preferred_words'  => 'sanitize_textarea_field',
            'ba_training_custom_rules'     => 'sanitize_textarea_field',
            'ba_training_humanize_level'   => 'sanitize_text_field',
            'ba_training_sentence_variety' => 'sanitize_text_field',
            'ba_training_paragraph_style'  => 'sanitize_text_field',
            'ba_training_avoid_patterns'   => 'sanitize_text_field',
        );

        foreach ( $training_settings as $key => $callback ) {
            register_setting( 'ba_training_group', $key, array(
                'sanitize_callback' => $callback,
            ));
        }
    }

    public function get( $key ) {
        $default = isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : '';
        return get_option( $key, $default );
    }

    public function get_all() {
        $settings = array();
        foreach ( $this->defaults as $key => $default ) {
            $settings[ $key ] = get_option( $key, $default );
        }
        return $settings;
    }

    public function has_api_key() {
        $provider = $this->get( 'ba_ai_provider' );
        $key      = $this->get( 'ba_api_key_' . $provider );
        return ! empty( $key );
    }

    public function get_content_tones() {
        return array(
            'formal'         => 'Formal',
            'informal'       => 'Informal',
            'tecnico'        => 'Técnico',
            'conversacional' => 'Conversacional',
            'persuasivo'     => 'Persuasivo',
        );
    }

    public function get_languages() {
        return array(
            'pt_BR' => 'Português (Brasil)',
            'en_US' => 'English (US)',
            'es_ES' => 'Español',
        );
    }

    public function get_publish_statuses() {
        return array(
            'draft'   => 'Rascunho',
            'publish' => 'Publicado',
            'pending' => 'Pendente de revisão',
        );
    }

    public function get_image_sizes() {
        return array(
            '1024x1024' => '1024×1024 (Quadrado)',
            '1792x1024' => '1792×1024 (Paisagem)',
            '1024x1792' => '1024×1792 (Retrato)',
        );
    }

    public function get_seo_plugins() {
        $plugins = array( 'none' => 'Nenhum' );
        if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
            $plugins['yoast'] = 'Yoast SEO';
        }
        if ( is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) {
            $plugins['rankmath'] = 'Rank Math';
        }
        return $plugins;
    }

    public function get_elementor_templates() {
        $templates_dir = BA_PLUGIN_DIR . 'templates/elementor/';
        $templates     = array();
        if ( is_dir( $templates_dir ) ) {
            $files = glob( $templates_dir . '*.json' );
            foreach ( $files as $file ) {
                $name               = basename( $file, '.json' );
                $label              = ucwords( str_replace( array( '-', '_' ), ' ', $name ) );
                $templates[ $name ] = $label;
            }
        }
        return $templates;
    }

    public function is_daily_limit_reached() {
        $logger = BA_Logger::get_instance();
        $stats  = $logger->get_stats();
        $limit  = intval( $this->get( 'ba_daily_limit' ) );
        if ( 0 === $limit ) {
            return false;
        }
        return $stats['today_posts'] >= $limit;
    }

    /**
     * Verifica se a publicação automática está ativa.
     */
    public function is_auto_post_enabled() {
        return (bool) $this->get( 'ba_auto_post_enabled' );
    }

    /**
     * Retorna posts por dia configurado.
     */
    public function get_posts_per_day() {
        $ppd = intval( $this->get( 'ba_posts_per_day' ) );
        return max( 1, min( 10, $ppd ) );
    }

    /**
     * Retorna todos os dados de treinamento para construção de prompts.
     *
     * @return array
     */
    public function get_training_data() {
        return array(
            'writing_style'    => $this->get( 'ba_training_writing_style' ),
            'persona'          => $this->get( 'ba_training_persona' ),
            'reference_texts'  => $this->get( 'ba_training_reference_texts' ),
            'forbidden_words'  => $this->get( 'ba_training_forbidden_words' ),
            'preferred_words'  => $this->get( 'ba_training_preferred_words' ),
            'custom_rules'     => $this->get( 'ba_training_custom_rules' ),
            'humanize_level'   => $this->get( 'ba_training_humanize_level' ),
            'sentence_variety' => $this->get( 'ba_training_sentence_variety' ),
            'paragraph_style'  => $this->get( 'ba_training_paragraph_style' ),
            'avoid_patterns'   => $this->get( 'ba_training_avoid_patterns' ),
        );
    }

    /**
     * Verifica se o treinamento está configurado.
     *
     * @return bool
     */
    public function has_training() {
        $persona   = $this->get( 'ba_training_persona' );
        $forbidden = $this->get( 'ba_training_forbidden_words' );
        return ! empty( $persona ) || ! empty( $forbidden );
    }
}
