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
                'gemini-3.8-flash'    => 'Gemini 3.8 Flash (Recomendado / Cota Gratuita)',
                'gemini-flash-latest' => 'Gemini Flash Latest (Cota Gratuita)',
                'gemini-2.5-flash'    => 'Gemini 2.5 Flash (Cota Gratuita)',
                'gemini-3.5-flash'    => 'Gemini 3.5 Flash (Cota Gratuita)',
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
        $selected_model = isset( $options['model'] ) ? $options['model'] : $this->settings->get( 'ba_text_model' );

        // Lista ordenada de modelos a tentar (o selecionado primeiro, seguido por alternativas estáveis)
        $models_to_try = array();
        if ( ! empty( $selected_model ) && array_key_exists( $selected_model, self::$providers['gemini']['models'] ) ) {
            $models_to_try[] = $selected_model;
        }

        // Alternativas gratuitas para fallback automático
        $fallback_pool = array(
            'gemini-3.8-flash',
            'gemini-flash-latest',
            'gemini-2.5-flash',
            'gemini-3.5-flash',
        );

        foreach ( $fallback_pool as $fb_m ) {
            if ( ! in_array( $fb_m, $models_to_try, true ) && array_key_exists( $fb_m, self::$providers['gemini']['models'] ) ) {
                $models_to_try[] = $fb_m;
            }
        }

        if ( empty( $models_to_try ) ) {
            $models_to_try = array( 'gemini-3.8-flash' );
        }

        $base_url   = self::$providers['gemini']['base_url'];
        $last_error = null;

        foreach ( $models_to_try as $model ) {
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
                    'temperature'     => isset( $options['temperature'] ) ? $options['temperature'] : 0.7,
                    'maxOutputTokens' => isset( $options['max_tokens'] ) ? $options['max_tokens'] : 4096,
                ),
            );

            if ( isset( $options['json_mode'] ) && $options['json_mode'] ) {
                $body['generationConfig']['responseMimeType'] = 'application/json';
            }

            // Gemini usa API key na URL, não no header
            $response = $this->make_request( $url, $body, 0, false );

            if ( is_wp_error( $response ) ) {
                $last_error = $response;
                $err_msg    = $response->get_error_message();
                $err_data   = $response->get_error_data();
                $status     = ( is_array( $err_data ) && isset( $err_data['status'] ) ) ? intval( $err_data['status'] ) : 0;

                // Se for erro de sobrecarga temporária (503/429), cota zero em modelo anterior, ou modelo descontinuado, tenta o próximo modelo
                $is_temporary_or_model_error = (
                    503 === $status ||
                    429 === $status ||
                    404 === $status ||
                    stripos( $err_msg, 'high demand' ) !== false ||
                    stripos( $err_msg, 'overloaded' ) !== false ||
                    stripos( $err_msg, 'no longer available' ) !== false ||
                    stripos( $err_msg, 'not found' ) !== false ||
                    stripos( $err_msg, 'limit: 0' ) !== false ||
                    stripos( $err_msg, 'deprecated' ) !== false
                );

                if ( $is_temporary_or_model_error ) {
                    continue;
                }

                // Se for outro erro definitivo (ex: chave de API inválida), retorna logo
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
        }

        if ( is_wp_error( $last_error ) ) {
            return $last_error;
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

        // Trata apenas sobrecarga temporária dos servidores (503 / 502 / 504)
        if ( ( 503 === $status_code || 502 === $status_code || 504 === $status_code ) && $retry < 2 ) {
            $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
            $wait        = $retry_after ? intval( $retry_after ) : pow( 2, $retry + 1 );
            sleep( min( $wait, 10 ) );
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
                $error_msg = ( ! empty( $data['error']['message'] ) ) ? $data['error']['message'] : $data['error']['status'];
            }
            else {
                $error_msg = sprintf( __( 'Erro da API (HTTP %d)', 'blog-automatico' ), $status_code );
            }

            // Tradução amigável para mensagens de pico de demanda do Google
            if ( stripos( $error_msg, 'high demand' ) !== false || stripos( $error_msg, 'overloaded' ) !== false || 503 === $status_code ) {
                $error_msg = __( 'Os servidores do Google Gemini estão enfrentando pico temporário de demanda ("High Demand"). Tentamos alternativas automáticas. Por favor, aguarde alguns instantes ou selecione outro modelo/provedor nas Configurações.', 'blog-automatico' );
            }

            // Diagnóstico detalhado para Quota / Rate Limit do Google
            if ( stripos( $error_msg, 'quota exceeded' ) !== false || stripos( $error_msg, 'RESOURCE_EXHAUSTED' ) !== false ) {
                if ( stripos( $error_msg, 'limit: 0' ) !== false ) {
                    $error_msg = __( 'A chave da API do Google AI Studio está com cota ZERO (limit: 0) para este projeto/modelo. O Google bloqueia o Free Tier se o projeto não estiver inicializado. Acesse aistudio.google.com/app/apikey e crie uma nova chave em um "Novo Projeto" (Create in new project), ou use OpenAI / DeepSeek em Configurações.', 'blog-automatico' );
                } else {
                    $wait_sec = 30;
                    if ( preg_match( '/retry in ([\d\.]+)s/i', $error_msg, $matches ) ) {
                        $wait_sec = ceil( floatval( $matches[1] ) );
                    }
                    $error_msg = sprintf(
                        __( 'A API do Google AI Studio recusou a chamada por cota/recursos (RESOURCE_EXHAUSTED). Tempo sugerido de espera: %d segundos. Se este erro persistir mesmo após aguardar, a chave do Google está restrita no Google Cloud: crie uma nova chave em aistudio.google.com (Create in new project) ou alterne para OpenAI / DeepSeek em Blog Automático > Configurações.', 'blog-automatico' ),
                        $wait_sec
                    );
                }
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

    /**
     * Realiza um diagnóstico aprofundado da API para identificar limites de cota,
     * modelos autorizados e status de autenticação.
     *
     * @param string|null $provider Provedor opcional.
     * @param string|null $api_key  Chave opcional.
     * @param string|null $model    Modelo opcional.
     * @return array Resultado do diagnóstico.
     */
    public function diagnose_api( $provider = null, $api_key = null, $model = null ) {
        $p = ! empty( $provider ) ? $provider : $this->provider;
        $k = ! empty( $api_key ) ? $api_key : $this->settings->get( 'ba_api_key_' . $p );
        $m = ! empty( $model ) ? $model : $this->settings->get( 'ba_text_model' );

        $provider_name = isset( self::$providers[ $p ]['name'] ) ? self::$providers[ $p ]['name'] : ucfirst( $p );

        if ( empty( $k ) ) {
            return array(
                'success'       => false,
                'provider'      => $p,
                'provider_name' => $provider_name,
                'auth_status'   => 'missing',
                'quota_status'  => 'error',
                'title'         => __( 'Chave de API não informada', 'blog-automatico' ),
                'message'       => sprintf( __( 'Nenhuma chave de API configurada para %s. Insira sua chave nas Configurações.', 'blog-automatico' ), $provider_name ),
                'models'        => array(),
                'raw'           => null,
            );
        }

        $masked_key = ( strlen( $k ) > 8 ) ? substr( $k, 0, 4 ) . '...' . substr( $k, -4 ) : '***';

        if ( 'gemini' === $p ) {
            return $this->diagnose_gemini( $k, $m, $masked_key );
        } else {
            return $this->diagnose_openai_compatible( $p, $k, $m, $masked_key );
        }
    }

    /**
     * Diagnóstico específico para o Google Gemini.
     */
    private function diagnose_gemini( $api_key, $model, $masked_key ) {
        $start_time = microtime( true );
        $base_url   = self::$providers['gemini']['base_url'];

        // Passo 1: Listar modelos disponíveis para a chave
        $models_url = $base_url . '/models?key=' . $api_key;
        $response   = wp_remote_get( $models_url, array( 'timeout' => 30 ) );
        $latency_ms = round( ( microtime( true ) - $start_time ) * 1000 );

        if ( is_wp_error( $response ) ) {
            return array(
                'success'       => false,
                'provider'      => 'gemini',
                'provider_name' => 'Google Gemini',
                'api_key'       => $masked_key,
                'auth_status'   => 'network_error',
                'quota_status'  => 'unknown',
                'latency_ms'    => $latency_ms,
                'title'         => __( 'Erro de Conexão com os Servidores do Google', 'blog-automatico' ),
                'message'       => $response->get_error_message(),
                'models'        => array(),
                'raw'           => null,
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Chave inválida ou não autorizada
        if ( 200 !== $code ) {
            $err_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : sprintf( 'HTTP %d', $code );
            return array(
                'success'       => false,
                'provider'      => 'gemini',
                'provider_name' => 'Google Gemini',
                'api_key'       => $masked_key,
                'auth_status'   => 'invalid',
                'quota_status'  => 'blocked',
                'latency_ms'    => $latency_ms,
                'title'         => __( 'Chave de API Inválida ou Rejeitada pelo Google', 'blog-automatico' ),
                'message'       => $err_msg,
                'models'        => array(),
                'raw'           => $data,
            );
        }

        // Extrair modelos autorizados
        $available_models = array();
        if ( isset( $data['models'] ) && is_array( $data['models'] ) ) {
            foreach ( $data['models'] as $mod ) {
                if ( isset( $mod['name'] ) ) {
                    $m_id = str_replace( 'models/', '', $mod['name'] );
                    $disp = isset( $mod['displayName'] ) ? $mod['displayName'] : $m_id;
                    $methods = isset( $mod['supportedGenerationMethods'] ) ? $mod['supportedGenerationMethods'] : array();
                    if ( in_array( 'generateContent', $methods, true ) ) {
                        $available_models[] = array(
                            'id'   => $m_id,
                            'name' => $disp,
                        );
                    }
                }
            }
        }

        // Passo 2: Micro-teste de geração de conteúdo para verificar cota/limite
        // Testa o modelo selecionado e outros modelos estáveis para encontrar qual tem cota ativa
        $test_models_pool = array_unique( array_filter( array(
            $model,
            'gemini-2.5-flash',
            'gemini-flash-latest',
            'gemini-3.5-flash',
            'gemini-3.8-flash',
        ) ) );

        $working_model = null;
        $last_gen_response = null;
        $last_gen_code = null;
        $last_gen_data = null;
        $gen_latency = 0;

        foreach ( $test_models_pool as $m_candidate ) {
            $gen_url   = $base_url . '/models/' . $m_candidate . ':generateContent?key=' . $api_key;
            $test_body = array(
                'contents' => array(
                    array( 'parts' => array( array( 'text' => 'ping' ) ) ),
                ),
                'generationConfig' => array( 'maxOutputTokens' => 5 ),
            );

            $gen_start    = microtime( true );
            $gen_response = wp_remote_post( $gen_url, array(
                'timeout' => 20,
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => wp_json_encode( $test_body ),
            ) );
            $gen_latency = round( ( microtime( true ) - $gen_start ) * 1000 );

            if ( ! is_wp_error( $gen_response ) ) {
                $last_gen_code = wp_remote_retrieve_response_code( $gen_response );
                $last_gen_body = wp_remote_retrieve_body( $gen_response );
                $last_gen_data = json_decode( $last_gen_body, true );

                if ( 200 === $last_gen_code ) {
                    $working_model = $m_candidate;
                    break;
                }
            }
        }

        // Se encontrou algum modelo com cota liberada!
        if ( ! empty( $working_model ) ) {
            return array(
                'success'       => true,
                'provider'      => 'gemini',
                'provider_name' => 'Google Gemini',
                'api_key'       => $masked_key,
                'auth_status'   => 'valid',
                'quota_status'  => 'ok',
                'latency_ms'    => $gen_latency,
                'tested_model'  => $working_model,
                'title'         => __( 'API Operacional e com Cota Liberada! ✅', 'blog-automatico' ),
                'message'       => sprintf(
                    __( 'Sua chave de API está funcional no Google AI Studio. O modelo "%s" respondeu com sucesso em %dms.', 'blog-automatico' ),
                    $working_model,
                    $gen_latency
                ),
                'models'        => $available_models,
                'raw'           => $last_gen_data,
            );
        }

        // Se todos os modelos retornaram erro de cota (RESOURCE_EXHAUSTED)
        $error_detail = isset( $last_gen_data['error']['message'] ) ? $last_gen_data['error']['message'] : '';
        $is_limit_zero = ( stripos( $error_detail, 'limit: 0' ) !== false );

        $title = __( 'Bloqueio Persistente de Projeto no Google Cloud (RESOURCE_EXHAUSTED)', 'blog-automatico' );
        $user_advice = __( 'Mesmo sem usar a API por minutos, o Google continua recusando chamadas nesta chave. Isso ocorre quando o projeto atual do Google Cloud entra em restrição automática de Free Tier. Solução rápida: acesse aistudio.google.com/app/apikey, clique em "Create API key" e selecione "Create in new project" (Criar em um NOVO projeto). Cole a nova chave aqui para reativar o uso instantaneamente.', 'blog-automatico' );

        return array(
            'success'       => false,
            'provider'      => 'gemini',
            'provider_name' => 'Google Gemini',
            'api_key'       => $masked_key,
            'auth_status'   => 'valid',
            'quota_status'  => 'zero_quota',
            'latency_ms'    => $gen_latency,
            'tested_model'  => ! empty( $model ) ? $model : 'gemini-3.8-flash',
            'title'         => $title,
            'message'       => $user_advice,
            'models'        => $available_models,
            'raw'           => $last_gen_data,
        );
    }

    /**
     * Diagnóstico para provedores padrão OpenAI / Grok / DeepSeek.
     */
    private function diagnose_openai_compatible( $provider, $api_key, $model, $masked_key ) {
        $start_time    = microtime( true );
        $provider_info = self::$providers[ $provider ];
        $base_url      = $provider_info['base_url'];

        // Passo 1: Listar modelos
        $models_url = $base_url . '/models';
        $response   = wp_remote_get( $models_url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        ) );
        $latency_ms = round( ( microtime( true ) - $start_time ) * 1000 );

        if ( is_wp_error( $response ) ) {
            return array(
                'success'       => false,
                'provider'      => $provider,
                'provider_name' => $provider_info['name'],
                'api_key'       => $masked_key,
                'auth_status'   => 'network_error',
                'quota_status'  => 'unknown',
                'latency_ms'    => $latency_ms,
                'title'         => __( 'Erro de Conexão com a API', 'blog-automatico' ),
                'message'       => $response->get_error_message(),
                'models'        => array(),
                'raw'           => null,
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 !== $code ) {
            $err_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : sprintf( 'HTTP %d', $code );
            return array(
                'success'       => false,
                'provider'      => $provider,
                'provider_name' => $provider_info['name'],
                'api_key'       => $masked_key,
                'auth_status'   => 'invalid',
                'quota_status'  => ( 429 === $code ) ? 'limited' : 'blocked',
                'latency_ms'    => $latency_ms,
                'title'         => ( 429 === $code ) ? __( 'Cota Esgotada / Saldo Insuficiente', 'blog-automatico' ) : __( 'Chave Inválida', 'blog-automatico' ),
                'message'       => $err_msg,
                'models'        => array(),
                'raw'           => $data,
            );
        }

        $available_models = array();
        if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
            foreach ( $data['data'] as $mod ) {
                if ( isset( $mod['id'] ) ) {
                    $available_models[] = array(
                        'id'   => $mod['id'],
                        'name' => $mod['id'],
                    );
                }
            }
        }

        // Micro-teste de completion
        $test_model = ! empty( $model ) ? $model : key( $provider_info['models'] );
        $comp_start = microtime( true );
        $comp_resp  = wp_remote_post( $base_url . '/chat/completions', array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => wp_json_encode( array(
                'model'      => $test_model,
                'messages'   => array( array( 'role' => 'user', 'content' => 'ping' ) ),
                'max_tokens' => 5,
            ) ),
        ) );
        $comp_latency = round( ( microtime( true ) - $comp_start ) * 1000 );

        if ( is_wp_error( $comp_resp ) ) {
            return array(
                'success'       => false,
                'provider'      => $provider,
                'provider_name' => $provider_info['name'],
                'api_key'       => $masked_key,
                'auth_status'   => 'valid',
                'quota_status'  => 'error',
                'latency_ms'    => $comp_latency,
                'tested_model'  => $test_model,
                'title'         => __( 'Autenticado, mas Falhou no Envio', 'blog-automatico' ),
                'message'       => $comp_resp->get_error_message(),
                'models'        => $available_models,
                'raw'           => null,
            );
        }

        $comp_code = wp_remote_retrieve_response_code( $comp_resp );
        $comp_body = wp_remote_retrieve_body( $comp_resp );
        $comp_data = json_decode( $comp_body, true );

        if ( 200 === $comp_code ) {
            return array(
                'success'       => true,
                'provider'      => $provider,
                'provider_name' => $provider_info['name'],
                'api_key'       => $masked_key,
                'auth_status'   => 'valid',
                'quota_status'  => 'ok',
                'latency_ms'    => $comp_latency,
                'tested_model'  => $test_model,
                'title'         => __( 'API Operacional e com Saldo/Cota Ativa! ✅', 'blog-automatico' ),
                'message'       => sprintf(
                    __( 'A chave está perfeitamente funcional. O modelo "%s" respondeu em %dms.', 'blog-automatico' ),
                    $test_model,
                    $comp_latency
                ),
                'models'        => $available_models,
                'raw'           => $comp_data,
            );
        }

        $err_msg = isset( $comp_data['error']['message'] ) ? $comp_data['error']['message'] : sprintf( 'HTTP %d', $comp_code );
        return array(
            'success'       => false,
            'provider'      => $provider,
            'provider_name' => $provider_info['name'],
            'api_key'       => $masked_key,
            'auth_status'   => 'valid',
            'quota_status'  => ( 429 === $comp_code ) ? 'limited' : 'error',
            'latency_ms'    => $comp_latency,
            'tested_model'  => $test_model,
            'title'         => ( 429 === $comp_code ) ? __( 'Limite de Cota / Saldo Insuficiente', 'blog-automatico' ) : __( 'Falha na Chamada da API', 'blog-automatico' ),
            'message'       => $err_msg,
            'models'        => $available_models,
            'raw'           => $comp_data,
        );
    }

    public function get_total_tokens_used() {
        return $this->total_tokens_used;
    }

    public function reset_token_count() {
        $this->total_tokens_used = 0;
    }
}

