<?php
/**
 * Conector multi-provider de IA.
 * Suporta: OpenAI (GPT), Google Gemini, xAI (Grok), DeepSeek
 *
 * @package BlogAutomatico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BA_AI_Connector {

    /**
     * Configurações dos provedores.
     */
    private static $providers = array(
        'openai' => array(
            'name'     => 'OpenAI (ChatGPT)',
            'base_url' => 'https://api.openai.com/v1',
            'models'   => array(
                'gpt-4o'      => 'GPT-4o (Recomendado)',
                'gpt-4o-mini' => 'GPT-4o Mini (Econômico)',
                'gpt-4-turbo' => 'GPT-4 Turbo',
            ),
            'image_models' => array(
                'dall-e-3' => 'DALL-E 3',
                'dall-e-2' => 'DALL-E 2',
            ),
            'supports_images' => true,
        ),
        'gemini' => array(
            'name'     => 'Google Gemini',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'models'   => array(
                'gemini-3.8-flash'       => 'Gemini 3.8 Flash (Recomendado)',
                'gemini-3.1-pro-preview' => 'Gemini 3.1 Pro Preview (Avançado)',
                'gemini-flash-latest'    => 'Gemini Flash Latest',
                'gemini-pro-latest'      => 'Gemini Pro Latest',
            ),
            'image_models'     => array(),
            'supports_images'  => false,
        ),
        'grok' => array(
            'name'     => 'xAI (Grok)',
            'base_url' => 'https://api.x.ai/v1',
            'models'   => array(
                'grok-3'      => 'Grok 3',
                'grok-3-mini' => 'Grok 3 Mini (Econômico)',
            ),
            'image_models'     => array(
                'grok-2-image' => 'Grok 2 Image',
            ),
            'supports_images'  => true,
        ),
        'deepseek' => array(
            'name'     => 'DeepSeek',
            'base_url' => 'https://api.deepseek.com',
            'models'   => array(
                'deepseek-chat'     => 'DeepSeek Chat (V3)',
                'deepseek-reasoner' => 'DeepSeek Reasoner (R1)',
            ),
            'image_models'     => array(),
            'supports_images'  => false,
        ),
    );

    private $settings;
    private $provider;
    private $api_key;
    private $total_tokens_used = 0;

    private static $instance = null;

    public function __construct() {
        $this->settings = BA_Settings::get_instance();
        $this->provider = $this->settings->get( 'ba_ai_provider' );
        $this->api_key  = $this->settings->get( 'ba_api_key_' . $this->provider );
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna a lista de provedores.
     */
    public static function get_providers() {
        return self::$providers;
    }

    /**
     * Retorna modelos de texto do provider atual.
     */
    public function get_models() {
        return isset( self::$providers[ $this->provider ]['models'] )
            ? self::$providers[ $this->provider ]['models']
            : array();
    }

    /**
     * Retorna modelos de imagem do provider atual.
     */
    public function get_image_models() {
        return isset( self::$providers[ $this->provider ]['image_models'] )
            ? self::$providers[ $this->provider ]['image_models']
            : array();
    }

    /**
     * Verifica se o provider suporta geração de imagens.
     */
    public function supports_images() {
        return isset( self::$providers[ $this->provider ]['supports_images'] )
            && self::$providers[ $this->provider ]['supports_images'];
    }

    /**
     * Envia chat completion — roteia para o provider correto.
     */
    public function chat_completion( $system_prompt, $user_prompt, $options = array() ) {
        switch ( $this->provider ) {
            case 'gemini':
                return $this->gemini_completion( $system_prompt, $user_prompt, $options );
            case 'openai':
            case 'grok':
            case 'deepseek':
            default:
                return $this->openai_compatible_completion( $system_prompt, $user_prompt, $options );
        }
    }

    /**
     * Completion para APIs compatíveis com OpenAI (OpenAI, Grok, DeepSeek).
     */
    private function openai_compatible_completion( $system_prompt, $user_prompt, $options = array() ) {
        $model    = isset( $options['model'] ) ? $options['model'] : $this->settings->get( 'ba_text_model' );
        $base_url = self::$providers[ $this->provider ]['base_url'];

        $body = array(
            'model'       => $model,
            'messages'    => array(
                array( 'role' => 'system', 'content' => $system_prompt ),
                array( 'role' => 'user', 'content' => $user_prompt ),
            ),
            'temperature' => isset( $options['temperature'] ) ? $options['temperature'] : 0.7,
            'max_tokens'  => isset( $options['max_tokens'] ) ? $options['max_tokens'] : 4096,
        );

        if ( isset( $options['json_mode'] ) && $options['json_mode'] ) {
            $body['response_format'] = array( 'type' => 'json_object' );
        }

        $response = $this->make_request( $base_url . '/chat/completions', $body );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( isset( $response['usage']['total_tokens'] ) ) {
            $this->total_tokens_used += $response['usage']['total_tokens'];
        }

        if ( isset( $response['choices'][0]['message']['content'] ) ) {
            return array(
                'content'     => $response['choices'][0]['message']['content'],
                'tokens_used' => isset( $response['usage']['total_tokens'] ) ? $response['usage']['total_tokens'] : 0,
                'model'       => $model,
                'provider'    => $this->provider,
            );
        }

        return new WP_Error( 'ba_invalid_response', __( 'Resposta inválida da API.', 'blog-automatico' ) );
    }

    /**
     * Completion para Google Gemini (API diferente).
     */
    private function gemini_completion( $system_prompt, $user_prompt, $options = array() ) {
        $model = isset( $options['model'] ) ? $options['model'] : $this->settings->get( 'ba_text_model' );

        // Fallback automático para modelos legados descontinuados ou não configurados
        if ( empty( $model ) || in_array( $model, array( 'gemini-2.0-flash', 'gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-1.5-flash', 'gemini-1.5-pro' ), true ) || ! array_key_exists( $model, self::$providers['gemini']['models'] ) ) {
            $model = 'gemini-3.8-flash';
        }

        $base_url = self::$providers['gemini']['base_url'];

        $url = $base_url . '/models/' . $model . ':generateContent?key=' . $this->api_key;

        $body = array(
            'system_instruction' => array(
                'parts' => array( array( 'text' => $system_prompt ) ),
            ),
            'contents' => array(
                array(
                    'parts' => array( array( 'text' => $user_prompt ) ),
                ),
            ),
            'generationConfig' => array(
                'temperature'   => isset( $options['temperature'] ) ? $options['temperature'] : 0.7,
                'maxOutputTokens' => isset( $options['max_tokens'] ) ? $options['max_tokens'] : 4096,
            ),
        );

        if ( isset( $options['json_mode'] ) && $options['json_mode'] ) {
            $body['generationConfig']['responseMimeType'] = 'application/json';
        }

        // Gemini usa API key na URL, não no header
        $response = $this->make_request( $url, $body, 0, false );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $tokens = 0;
        if ( isset( $response['usageMetadata']['totalTokenCount'] ) ) {
            $tokens = $response['usageMetadata']['totalTokenCount'];
            $this->total_tokens_used += $tokens;
        }

        if ( isset( $response['candidates'][0]['content']['parts'] ) && is_array( $response['candidates'][0]['content']['parts'] ) ) {
            $text = '';
            foreach ( $response['candidates'][0]['content']['parts'] as $part ) {
                if ( isset( $part['text'] ) ) {
                    $text .= $part['text'];
                }
            }

            if ( ! empty( $text ) ) {
                return array(
                    'content'     => $text,
                    'tokens_used' => $tokens,
                    'model'       => $model,
                    'provider'    => 'gemini',
                );
            }
        }

        return new WP_Error( 'ba_gemini_error', __( 'Resposta inválida do Gemini.', 'blog-automatico' ) );
    }

    /**
     * Gera imagem — usa OpenAI DALL-E ou fallback para placeholder.
     */
    public function generate_image( $prompt, $options = array() ) {
        // Se o provider atual não suporta imagens, usar OpenAI se disponível
        $image_provider = $this->provider;
        $image_api_key  = $this->api_key;

        if ( ! $this->supports_images() ) {
            // Tentar fallback para OpenAI
            $openai_key = $this->settings->get( 'ba_api_key_openai' );
            if ( ! empty( $openai_key ) ) {
                $image_provider = 'openai';
                $image_api_key  = $openai_key;
            } else {
                return new WP_Error( 'ba_no_image_support',
                    __( 'O provedor atual não suporta geração de imagens. Configure a API key da OpenAI para usar DALL-E.', 'blog-automatico' )
                );
            }
        }

        $image_model = isset( $options['model'] ) ? $options['model'] : $this->settings->get( 'ba_image_model' );
        $size        = isset( $options['size'] ) ? $options['size'] : $this->settings->get( 'ba_image_size' );
        $base_url    = self::$providers[ $image_provider ]['base_url'];

        $body = array(
            'model'  => $image_model,
            'prompt' => $prompt,
            'n'      => 1,
            'size'   => $size,
        );

        if ( 'dall-e-3' === $image_model ) {
            $body['quality'] = isset( $options['quality'] ) ? $options['quality'] : 'standard';
            $body['style']   = isset( $options['style'] ) ? $options['style'] : 'natural';
        }

        // Temporariamente usar a key do provider de imagem
        $original_key  = $this->api_key;
        $this->api_key = $image_api_key;

        $response = $this->make_request( $base_url . '/images/generations', $body );

        $this->api_key = $original_key;

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( isset( $response['data'][0]['url'] ) ) {
            return array(
                'url'            => $response['data'][0]['url'],
                'revised_prompt' => isset( $response['data'][0]['revised_prompt'] ) ? $response['data'][0]['revised_prompt'] : '',
            );
        }

        return new WP_Error( 'ba_image_error', __( 'Falha ao gerar imagem.', 'blog-automatico' ) );
    }

    /**
     * Faz requisição HTTP.
     */
    private function make_request( $url, $body, $retry = 0, $use_bearer = true ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'ba_no_api_key',
                sprintf( __( 'API Key não configurada para %s. Vá em Blog Automático > Configurações.', 'blog-automatico' ),
                    self::$providers[ $this->provider ]['name'] )
            );
        }

        $headers = array( 'Content-Type' => 'application/json' );

        if ( $use_bearer ) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }

        $args = array(
            'method'  => 'POST',
            'timeout' => 120,
            'headers' => $headers,
            'body'    => wp_json_encode( $body ),
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            if ( $retry < 3 ) {
                sleep( pow( 2, $retry ) );
                return $this->make_request( $url, $body, $retry + 1, $use_bearer );
            }
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_raw    = wp_remote_retrieve_body( $response );
        $data        = json_decode( $body_raw, true );

        if ( 429 === $status_code && $retry < 3 ) {
            $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
            $wait        = $retry_after ? intval( $retry_after ) : pow( 2, $retry + 1 );
            sleep( min( $wait, 30 ) );
            return $this->make_request( $url, $body, $retry + 1, $use_bearer );
        }

        if ( $status_code >= 400 ) {
            $error_msg = '';
            // OpenAI / Grok / DeepSeek format
            if ( isset( $data['error']['message'] ) ) {
                $error_msg = $data['error']['message'];
            }
            // Gemini format
            elseif ( isset( $data['error']['status'] ) ) {
                $error_msg = $data['error']['status'] . ': ' . ( $data['error']['message'] ?? '' );
            }
            else {
                $error_msg = sprintf( __( 'Erro da API (HTTP %d)', 'blog-automatico' ), $status_code );
            }

            return new WP_Error( 'ba_api_error', $error_msg, array( 'status' => $status_code ) );
        }

        if ( null === $data ) {
            return new WP_Error( 'ba_json_error', __( 'Resposta inválida (JSON inválido).', 'blog-automatico' ) );
        }

        return $data;
    }

    /**
     * Testa conexão com a API.
     */
    public function test_connection( $provider = null, $api_key = null, $model = null ) {
        $orig_provider = $this->provider;
        $orig_api_key  = $this->api_key;

        if ( ! empty( $provider ) ) {
            $this->provider = $provider;
        }
        if ( ! empty( $api_key ) ) {
            $this->api_key = $api_key;
        }

        $options = array( 'max_tokens' => 200 );
        if ( ! empty( $model ) ) {
            $options['model'] = $model;
        }

        $result = $this->chat_completion(
            'Você é um assistente.',
            'Responda apenas com: "Conexão OK"',
            $options
        );

        $this->provider = $orig_provider;
        $this->api_key  = $orig_api_key;

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return array(
            'success'  => true,
            'message'  => $result['content'],
            'provider' => $result['provider'],
            'model'    => $result['model'],
        );
    }

    public function get_total_tokens_used() {
        return $this->total_tokens_used;
    }

    public function reset_token_count() {
        $this->total_tokens_used = 0;
    }
}
