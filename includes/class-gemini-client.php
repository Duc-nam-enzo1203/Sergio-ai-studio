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

        // Build multi-model resilient cascade chain
        $candidates       = $this->build_model_cascade( $override_model );
        $last_error       = null;
        $attempted_models = array();

        foreach ( $candidates as $model_idx => $candidate_model ) {
            $attempted_models[] = $candidate_model;
            $endpoint = self::API_BASE . '/models/' . $candidate_model . ':generateContent';

            // Base Payload
            $base_payload = array(
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
                $base_payload['system_instruction'] = array(
                    'parts' => array(
                        array( 'text' => trim( $system_prompt ) ),
                    ),
                );
            }

            // Attempt 1: With Google Search Grounding (if requested)
            $payload = $base_payload;
            if ( $enable_search_grounding ) {
                $payload['tools'] = array(
                    array(
                        'googleSearch' => new stdClass(),
                    ),
                );
            }

            $this->logger->info(
                'gemini_api_call',
                sprintf( 'Gửi yêu cầu tới model [%s] (Grounding: %s)', $candidate_model, $enable_search_grounding ? 'BẬT' : 'TẮT' )
            );

            // Send HTTP Request with automatic single retry for transient failures
            $result = $this->send_request_with_retry( $endpoint, $payload, $timeout );

            // If success, parse and return
            if ( ! is_wp_error( $result ) ) {
                if ( $model_idx > 0 ) {
                    $this->logger->info( 'gemini_fallback_success', sprintf( 'Tự động phục hồi thành công qua model dự phòng [%s]!', $candidate_model ) );
                }
                return $this->parse_gemini_response( $result, $candidate_model );
            }

            $error_code = $result->get_error_code();
            $error_msg  = $result->get_error_message();
            $last_error = $result;

            // If error is 403 (Invalid API Key / Permission Denied), do not cascade — it will fail identically
            if ( 'gemini_auth_error' === $error_code || false !== stripos( $error_msg, 'API_KEY_INVALID' ) || false !== stripos( $error_msg, 'PERMISSION_DENIED' ) ) {
                $this->logger->error( 'gemini_auth_failed', sprintf( 'Lỗi xác thực API Key: %s', $error_msg ) );
                return $result;
            }

            // Grounding Quota Circuit Fallback: If request had Search Grounding and hit 429 quota, immediately retry CURRENT model WITHOUT Grounding
            if ( $enable_search_grounding && ( 'gemini_quota_exhausted' === $error_code || 'gemini_rate_limited' === $error_code || 429 === $result->get_error_data() || false !== stripos( $error_msg, 'quota' ) || false !== stripos( $error_msg, 'tool' ) ) ) {
                $this->logger->warning(
                    'gemini_grounding_fallback',
                    sprintf( 'Model [%s] gặp giới hạn hạn mức Google Search Grounding (429). Hệ thống tự động chuyển sang chế độ suy luận tri thức trực tiếp (Grounding-Free)...', $candidate_model )
                );

                $grounding_free_payload = $base_payload; // Payload without 'tools'
                $gf_result = $this->send_request_with_retry( $endpoint, $grounding_free_payload, $timeout );

                if ( ! is_wp_error( $gf_result ) ) {
                    $this->logger->info( 'gemini_grounding_fallback_success', sprintf( 'Thành công khi chạy không dùng Search Grounding trên model [%s]!', $candidate_model ) );
                    return $this->parse_gemini_response( $gf_result, $candidate_model );
                }

                $last_error = $gf_result;
                $error_msg  = $gf_result->get_error_message();
            }

            // Log model cascade step if there is a next candidate
            if ( isset( $candidates[ $model_idx + 1 ] ) ) {
                $next_model = $candidates[ $model_idx + 1 ];
                $this->logger->warning(
                    'gemini_model_cascade',
                    sprintf( 'Model [%s] gặp sự cố (%s). Đang tự động chuyển sang model kế tiếp trong chuỗi dự phòng: [%s]...', $candidate_model, $error_msg, $next_model )
                );
            }
        }

        // All models and fallbacks in the cascade were exhausted
        $this->logger->error( 'gemini_all_fallbacks_failed', sprintf( 'Toàn bộ %d model trong chuỗi dự phòng đều thất bại. Lỗi cuối: %s', count( $candidates ), $last_error->get_error_message() ) );

        // If the error was 429 Quota Exhausted, construct actionable Vietnamese guidance
        if ( false !== stripos( $last_error->get_error_message(), '429' ) || false !== stripos( $last_error->get_error_message(), 'quota' ) || 'gemini_quota_exhausted' === $last_error->get_error_code() ) {
            $actionable_msg = sprintf(
                __( "Lỗi hạn mức Gemini API (429 - Quota Exceeded): Tài khoản Google AI Studio đã tạm thời hết hạn mức sử dụng (Quota / Rate Limit) cho các mô hình đã thử (%s).\n\nGiải pháp xử lý ngay:\n1. Đổi Model: Vào menu 'Sergio AI Studio' > 'Cài Đặt' và đổi 'Mô Hình Chính' sang model khác (ví dụ: gemini-2.0-flash hoặc gemini-1.5-flash).\n2. Nếu vừa thực hiện liên tiếp nhiều tác vụ, vui lòng chờ 1-2 phút để Google làm mới hạn mức mỗi phút (RPM).\n3. Bạn có thể kích hoạt gói Pay-As-You-Go trên console https://aistudio.google.com/ để mở rộng hạn mức (chỉ tính phí khi vượt ngưỡng miễn phí rất lớn).\n4. Hoặc thay một Google AI API Key mới nếu tài khoản hiện tại đã chạm mốc 1.500 lượt gọi miễn phí trong ngày.", 'sergio-ai-studio' ),
                implode( ', ', $attempted_models )
            );
            return new WP_Error( 'gemini_quota_exhausted', $actionable_msg, array( 'attempted_models' => $attempted_models, 'original_message' => $last_error->get_error_message() ) );
        }

        return $last_error;
    }

    /**
     * Build an ordered cascade list of models to try
     *
     * @param string $override_model Optional override model
     * @return array List of model IDs
     */
    public function build_model_cascade( $override_model = '' ) {
        $primary = ! empty( $override_model ) ? $override_model : $this->model;
        $primary = str_replace( 'models/', '', $primary );
        if ( 'gemini-2.5-flash-lite' === $primary ) {
            $primary = 'gemini-3.5-flash-lite';
        }

        $fallback = ! empty( $this->fallback_model ) ? str_replace( 'models/', '', $this->fallback_model ) : '';
        if ( 'gemini-2.5-flash-lite' === $fallback ) {
            $fallback = 'gemini-3.5-flash-lite';
        }

        $candidates = array( $primary );
        if ( ! empty( $fallback ) && ! in_array( $fallback, $candidates, true ) ) {
            $candidates[] = $fallback;
        }

        // Resilient Google AI models with independent free quotas
        $pool = array( 'gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-3.5-flash-lite', 'gemini-2.5-flash' );
        foreach ( $pool as $pm ) {
            if ( ! in_array( $pm, $candidates, true ) ) {
                $candidates[] = $pm;
            }
        }

        return $candidates;
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
                if ( $attempt < $max_retries ) {
                    sleep( 2 ); // Wait before retry
                }
                continue;
            }

            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );

            if ( 200 === $code ) {
                return $body;
            }

            $decoded = json_decode( $body, true );
            $msg     = isset( $decoded['error']['message'] ) ? $decoded['error']['message'] : sprintf( 'Lỗi HTTP %d', $code );
            $status  = isset( $decoded['error']['status'] ) ? $decoded['error']['status'] : '';

            // Check for invalid API key or permission denied (immediate break)
            if ( 400 === $code || 403 === $code ) {
                if ( false !== stripos( $msg, 'API_KEY_INVALID' ) || false !== stripos( $msg, 'PERMISSION_DENIED' ) || false !== stripos( $msg, 'API key not valid' ) ) {
                    return new WP_Error( 'gemini_auth_error', sprintf( 'Lỗi xác thực Gemini API (%d): %s', $code, $msg ), $code );
                }
            }

            // Check for hard quota exhaustion
            $is_hard_quota = ( 429 === $code && ( 'RESOURCE_EXHAUSTED' === $status || false !== stripos( $msg, 'quota' ) || false !== stripos( $msg, 'exceeded' ) ) );

            if ( $is_hard_quota ) {
                // Hard quota limit reached: do not sleep and retry on same endpoint; return immediately to let caller cascade
                return new WP_Error( 'gemini_quota_exhausted', sprintf( 'Lỗi Gemini API (429 Quota): %s', $msg ), 429 );
            }

            // Transient rate-limiting (e.g. RPM burst) or temporary 503 / 500
            if ( in_array( $code, array( 429, 500, 503 ), true ) && $attempt < $max_retries ) {
                $backoff = ( 429 === $code ) ? 3 : 2;
                sleep( $backoff );
                continue;
            }

            return new WP_Error( 'gemini_api_error', sprintf( 'Lỗi Gemini API (%d): %s', $code, $msg ), $code );
        }

        return $last_error ? $last_error : new WP_Error( 'gemini_timeout', __( 'Hết thời gian kết nối API sau các lần thử.', 'sergio-ai-studio' ) );
    }

    /**
     * Parse raw Gemini JSON response and extract text + Grounding Metadata
     */
    public function parse_gemini_response( $raw_json, $model_name ) {
        $data = json_decode( $raw_json, true );

        if ( ! is_array( $data ) ) {
            return new WP_Error( 'invalid_json', __( 'Phản hồi từ Gemini không phải là chuỗi JSON hợp lệ.', 'sergio-ai-studio' ) );
        }

        // Safety filter check
        if ( ! empty( $data['promptFeedback']['blockReason'] ) ) {
            return new WP_Error( 'safety_blocked', sprintf( __( 'Yêu cầu bị chặn bởi bộ lọc an toàn của Google: %s', 'sergio-ai-studio' ), $data['promptFeedback']['blockReason'] ) );
        }

        if ( empty( $data['candidates'][0]['content']['parts'] ) ) {
            if ( ! empty( $data['candidates'][0]['finishReason'] ) && 'STOP' !== $data['candidates'][0]['finishReason'] ) {
                return new WP_Error( 'finish_reason_stopped', sprintf( __( 'Phản hồi bị dừng bất thường: %s', 'sergio-ai-studio' ), $data['candidates'][0]['finishReason'] ) );
            }
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

