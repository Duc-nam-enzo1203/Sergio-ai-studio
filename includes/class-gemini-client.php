<?php
/**
 * Modern Google Gemini API Client
 *
 * Implements Google Gemini v1beta API with:
 * - Dynamic model discovery via models.list
 * - Google Search Grounding with web metadata extraction
 * - URL Context ingestion
 * - Encrypted API key resolution
 * - Exponential backoff retry logic
 *
 * @package Sergio_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sergio_AI_Gemini_Client {

    const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * API Key (plain text in-memory)
     *
     * @var string
     */
    private $api_key;

    /**
     * Selected Model
     *
     * @var string
     */
    private $model;

    /**
     * Fallback Model
     *
     * @var string
     */
    private $fallback_model;

    /**
     * Logger instance
     *
     * @var Sergio_AI_Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param string           $api_key Optional API key
     * @param string           $model   Optional model name
     * @param Sergio_AI_Logger $logger  Optional logger
     */
    public function __construct( $api_key = '', $model = '', $logger = null ) {
        $options = get_option( 'sergio_ai_studio_options', array() );

        // Priority 1: wp-config.php constant (Enterprise level security)
        if ( Sergio_AI_Security::is_key_defined_in_config() ) {
            $this->api_key = SERGIO_AI_GEMINI_API_KEY;
        } elseif ( empty( $api_key ) ) {
            $saved_key = isset( $options['gemini_api_key'] ) ? $options['gemini_api_key'] : '';
            // Backward compatibility with previous theme options if not migrated
            if ( empty( $saved_key ) ) {
                $legacy_opts = get_option( 'sergio_ai_options', array() );
                $saved_key   = isset( $legacy_opts['gemini_api_key'] ) ? $legacy_opts['gemini_api_key'] : '';
            }
            $this->api_key = Sergio_AI_Security::decrypt( $saved_key );
        } else {
            $this->api_key = Sergio_AI_Security::decrypt( $api_key );
        }

        if ( empty( $model ) ) {
            $this->model = isset( $options['gemini_model_primary'] ) && ! empty( $options['gemini_model_primary'] )
                ? $options['gemini_model_primary']
                : 'gemini-2.5-flash';
        } else {
            $this->model = $model;
        }

        $this->fallback_model = isset( $options['gemini_model_fallback'] ) && ! empty( $options['gemini_model_fallback'] )
            ? $options['gemini_model_fallback']
            : 'gemini-3.5-flash-lite';

        // Auto-migrate deprecated models (Google deprecated gemini-2.5-flash-lite in favor of gemini-3.5-flash-lite)
        if ( 'gemini-2.5-flash-lite' === $this->model ) {
            $this->model = 'gemini-3.5-flash-lite';
        }
        if ( 'gemini-2.5-flash-lite' === $this->fallback_model ) {
            $this->fallback_model = 'gemini-3.5-flash-lite';
        }

        $this->logger = $logger ? $logger : new Sergio_AI_Logger();

        // Enforce IPv4 resolution on Windows/LocalWP only for Google API calls
        add_action( 'http_api_curl', array( $this, 'optimize_curl_handle' ), 10, 3 );
    }

    /**
     * Optimize cURL handle for high performance & stability on Windows / Local environments
     * Strictly targets Google Generative Language API requests to avoid polluting global WP requests.
     *
     * @param resource $handle cURL handle
     * @param array    $r      HTTP request arguments
     * @param string   $url    Request URL
     */
    public function optimize_curl_handle( $handle, $r = array(), $url = '' ) {
        if ( ! empty( $url ) && false === strpos( $url, 'googleapis.com' ) ) {
            return;
        }
        if ( defined( 'CURLOPT_IPRESOLVE' ) && defined( 'CURL_IPRESOLVE_V4' ) ) {
            curl_setopt( $handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        }
        curl_setopt( $handle, CURLOPT_TCP_NODELAY, 1 );
    }

    /**
     * Check if client has a configured API Key
     *
     * @return bool
     */
    public function is_configured() {
        return ! empty( $this->api_key );
    }

    /**
     * Get plain API key
     *
     * @return string
     */
    public function get_api_key() {
        return $this->api_key;
    }

    /**
     * Fetch dynamic list of available models from Google AI Studio API (models.list)
     * Passes credentials via secure x-goog-api-key header (never in query string)
     *
     * @param bool $force_refresh Skip transient cache
     * @return array|WP_Error Array of valid models or WP_Error
     */
    public function list_models( $force_refresh = false ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'no_api_key', __( 'Chưa cấu hình Gemini API Key.', 'sergio-ai-studio' ) );
        }

        $cache_key = 'sergio_ai_models_list_' . md5( $this->api_key );
        if ( ! $force_refresh ) {
            $cached = get_transient( $cache_key );
            if ( false !== $cached && is_array( $cached ) && ! empty( $cached ) ) {
                return $cached;
            }
        }

        $url = self::API_BASE . '/models';

        $response = wp_remote_get( $url, array(
            'timeout' => 20,
            'headers' => array(
                'Content-Type'   => 'application/json',
                'x-goog-api-key' => $this->api_key,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 !== $code || empty( $data['models'] ) ) {
            $msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Không thể lấy danh sách model từ Google AI Studio.', 'sergio-ai-studio' );
            return new WP_Error( 'api_error', $msg );
        }

        $available_models = array();
        foreach ( $data['models'] as $m ) {
            $methods = isset( $m['supportedGenerationMethods'] ) ? (array) $m['supportedGenerationMethods'] : array();
            if ( in_array( 'generateContent', $methods, true ) ) {
                $raw_name = $m['name']; // e.g. "models/gemini-2.5-flash"
                $clean_id = str_replace( 'models/', '', $raw_name );

                // Filter out non-text/experimental embeddings/aqa
                if ( preg_match( '/(embedding|aqa|imagen|medlm)/i', $clean_id ) ) {
                    continue;
                }

                $available_models[] = array(
                    'id'           => $clean_id,
                    'name'         => $clean_id,
                    'display_name' => isset( $m['displayName'] ) ? $m['displayName'] : $clean_id,
                    'description'  => isset( $m['description'] ) ? $m['description'] : '',
                    'input_tokens' => isset( $m['inputTokenLimit'] ) ? $m['inputTokenLimit'] : 0,
                );
            }
        }

        // Cache for 12 hours
        set_transient( $cache_key, $available_models, 12 * HOUR_IN_SECONDS );

        return $available_models;
    }

    /**
     * Generate content with Google Gemini API
     *
     * @param string $prompt                  Main User Prompt
     * @param string $system_prompt           System Instruction
     * @param bool   $enable_search_grounding Enable Google Search Grounding tool
     * @param float  $temperature             Creativity (0.0 to 1.0)
     * @param int    $timeout                 Timeout in seconds
     * @param string $override_model          Explicit model override
     * @return array|WP_Error Array with 'text', 'grounding_metadata', 'model_used' or WP_Error
     */
    public function generate_content(
        $prompt,
        $system_prompt = '',
        $enable_search_grounding = false,
        $temperature = 0.4,
        $timeout = 90,
        $override_model = ''
    ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'not_configured', __( 'Gemini API Key chưa được cài đặt.', 'sergio-ai-studio' ) );
        }

        // Support both positional arguments and options array passed as 2nd parameter
        if ( is_array( $system_prompt ) ) {
            $opts                    = $system_prompt;
            $system_prompt           = isset( $opts['system_prompt'] ) ? (string) $opts['system_prompt'] : '';
            $enable_search_grounding = isset( $opts['enable_search_grounding'] ) ? (bool) $opts['enable_search_grounding'] : $enable_search_grounding;
            $temperature             = isset( $opts['temperature'] ) ? (float) $opts['temperature'] : $temperature;
            $timeout                 = isset( $opts['timeout'] ) ? (int) $opts['timeout'] : $timeout;
            $override_model          = isset( $opts['override_model'] ) ? (string) $opts['override_model'] : $override_model;
        }

        $target_model = ! empty( $override_model ) ? $override_model : $this->model;
        $clean_model  = str_replace( 'models/', '', $target_model );
        if ( 'gemini-2.5-flash-lite' === $clean_model ) {
            $clean_model = 'gemini-3.5-flash-lite';
        }

        $endpoint = self::API_BASE . '/models/' . $clean_model . ':generateContent';

        // Build Payload
        $payload = array(
            'contents' => array(
                array(
                    'role'  => 'user',
                    'parts' => array(
                        array( 'text' => (string) $prompt ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'temperature'     => (float) $temperature,
                'maxOutputTokens' => 8192,
            ),
        );

        // System Instruction (Google standard format - strictly scalar string)
        if ( is_string( $system_prompt ) && '' !== trim( $system_prompt ) ) {
            $payload['system_instruction'] = array(
                'parts' => array(
                    array( 'text' => trim( $system_prompt ) ),
                ),
            );
        }

        // Search Grounding Tool
        if ( $enable_search_grounding ) {
            $payload['tools'] = array(
                array(
                    'googleSearch' => new stdClass(),
                ),
            );
        }

        $this->logger->info(
            'gemini_api_call',
            sprintf( 'Gửi yêu cầu tới model [%s] (Grounding: %s)', $clean_model, $enable_search_grounding ? 'BẬT' : 'TẮT' )
        );

        // Send HTTP Request with automatic single retry for transient failures
        $result = $this->send_request_with_retry( $endpoint, $payload, $timeout );

        // If primary model fails, attempt exactly 1 configured fallback model
        if ( is_wp_error( $result ) ) {
            $fallback = ! empty( $this->fallback_model ) && $this->fallback_model !== $clean_model
                ? $this->fallback_model
                : '';
            if ( 'gemini-2.5-flash-lite' === $fallback ) {
                $fallback = 'gemini-3.5-flash-lite';
            }

            if ( ! empty( $fallback ) ) {
                $this->logger->warning(
                    'gemini_fallback',
                    sprintf( 'Model [%s] gặp sự cố: %s. Đang thử model dự phòng duy nhất [%s]...', $clean_model, $result->get_error_message(), $fallback )
                );

                $fallback_endpoint = self::API_BASE . '/models/' . $fallback . ':generateContent';
                $fallback_result   = $this->send_request_with_retry( $fallback_endpoint, $payload, $timeout );

                if ( ! is_wp_error( $fallback_result ) ) {
                    return $this->parse_gemini_response( $fallback_result, $fallback );
                }

                $this->logger->error( 'gemini_fallback_failed', sprintf( 'Model dự phòng [%s] cũng thất bại: %s', $fallback, $fallback_result->get_error_message() ) );
                return $fallback_result;
            }

            $this->logger->error( 'gemini_api_error', $result->get_error_message() );
            return $result;
        }

        return $this->parse_gemini_response( $result, $clean_model );
    }

    /**
     * Send HTTP POST with retry
     * Sends API key via secure x-goog-api-key header with strict SSL verification
     */
    private function send_request_with_retry( $endpoint, $payload, $timeout ) {
        $json_payload = wp_json_encode( $payload );
        $max_retries  = 2;
        $attempt      = 0;
        $last_error   = null;

        while ( $attempt < $max_retries ) {
            $attempt++;

            $response = wp_remote_post( $endpoint, array(
                'headers' => array(
                    'Content-Type'   => 'application/json',
                    'x-goog-api-key' => $this->api_key,
                ),
                'body'    => $json_payload,
                'timeout' => $timeout,
            ) );

            if ( is_wp_error( $response ) ) {
                $last_error = $response;
                sleep( 2 ); // Wait before retry
                continue;
            }

            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );

            if ( 200 === $code ) {
                return $body;
            }

            $decoded = json_decode( $body, true );
            $msg = isset( $decoded['error']['message'] ) ? $decoded['error']['message'] : sprintf( 'Lỗi HTTP %d', $code );

            // Retry on temporary 503 or 429
            if ( in_array( $code, array( 429, 503, 500 ), true ) && $attempt < $max_retries ) {
                sleep( 3 );
                continue;
            }

            return new WP_Error( 'gemini_api_error', sprintf( 'Lỗi Gemini API (%d): %s', $code, $msg ) );
        }

        return $last_error ? $last_error : new WP_Error( 'gemini_timeout', __( 'Hết thời gian kết nối API sau các lần thử.', 'sergio-ai-studio' ) );
    }

    /**
     * Parse raw Gemini JSON response and extract text + Grounding Metadata
     */
    private function parse_gemini_response( $raw_json, $model_name ) {
        $data = json_decode( $raw_json, true );

        if ( ! is_array( $data ) || empty( $data['candidates'][0]['content']['parts'] ) ) {
            return new WP_Error( 'empty_response', __( 'Gemini không trả về nội dung hợp lệ.', 'sergio-ai-studio' ) );
        }

        $text_parts = array();
        foreach ( $data['candidates'][0]['content']['parts'] as $part ) {
            if ( isset( $part['text'] ) ) {
                $text_parts[] = $part['text'];
            }
        }
        $full_text = trim( implode( "\n", $text_parts ) );

        // Extract Grounding Metadata (Real Search Sources & Chunks)
        $grounding_meta = array();
        if ( isset( $data['candidates'][0]['groundingMetadata'] ) ) {
            $raw_grounding = $data['candidates'][0]['groundingMetadata'];

            // 1. Search Queries performed by Google
            if ( ! empty( $raw_grounding['webSearchQueries'] ) ) {
                $grounding_meta['search_queries'] = (array) $raw_grounding['webSearchQueries'];
            }

            // 2. Real Web Source Chunks (URLs and Titles)
            if ( ! empty( $raw_grounding['groundingChunks'] ) ) {
                $chunks = array();
                foreach ( $raw_grounding['groundingChunks'] as $chunk ) {
                    if ( ! empty( $chunk['web'] ) ) {
                        $chunks[] = array(
                            'uri'   => isset( $chunk['web']['uri'] ) ? esc_url_raw( $chunk['web']['uri'] ) : '',
                            'title' => isset( $chunk['web']['title'] ) ? sanitize_text_field( $chunk['web']['title'] ) : '',
                        );
                    }
                }
                $grounding_meta['sources'] = $chunks;
            }
        }

        return array(
            'text'               => $full_text,
            'grounding_metadata' => $grounding_meta,
            'model_used'         => $model_name,
        );
    }
}

