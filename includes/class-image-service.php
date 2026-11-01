<?php
/**
 * Image Service — Sergio AI Studio
 *
 * Implements a resilient image creation and thumbnail assignment pipeline:
 * 1. Template Image (Built-in GD / Imagick banner generator with headline & branding, 100% free, 0 API keys, zero copyright risk).
 * 2. Stock Photography (Pexels / Unsplash API if configured).
 * 3. AI Generated Image (Gemini Imagen prompt if enabled).
 *
 * Fully integrated with Rank Math SEO PRO:
 * - Automatically attaches featured image to post.
 * - Sets image ALT attribute containing the Rank Math Focus Keyword.
 * - Sets image Title and Caption with source attribution.
 *
 * @package Sergio_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sergio_AI_Image_Service {

    /**
     * Standard OpenGraph / Rank Math social banner dimensions (1200x630).
     */
    const BANNER_WIDTH  = 1200;
    const BANNER_HEIGHT = 630;

    /**
     * Logger
     *
     * @var Sergio_AI_Logger
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct( $logger = null ) {
        $this->logger = $logger ? $logger : new Sergio_AI_Logger();
    }

    /**
     * Generate or fetch featured image and attach to post
     *
     * Pipeline priority:
     * 1. Reference Source Images (extract representative photo from source URLs with clear attribution)
     * 2. Stock Photography (if enabled in settings)
     * 3. GD Template Banner (1200x630, 100% resilient zero-failure fallback)
     *
     * @param int          $post_id           WordPress Post ID
     * @param string       $headline          Article Title / Headline
     * @param string       $focus_keyword     Rank Math Focus Keyword for Alt Text
     * @param string       $image_prompt      Optional English visual prompt from AI
     * @param array|string $source_references Source citations / articles with URLs
     * @return int|WP_Error                   Attachment ID or WP_Error
     */
    public function attach_featured_image( $post_id, $headline, $focus_keyword = '', $image_prompt = '', $source_references = array() ) {
        $post_id = absint( $post_id );
        if ( empty( $post_id ) ) {
            return new WP_Error( 'invalid_post_id', __( 'ID bài viết không hợp lệ để tạo ảnh.', 'sergio-ai-studio' ) );
        }

        // Avoid overwriting existing thumbnail
        if ( has_post_thumbnail( $post_id ) ) {
            return get_post_thumbnail_id( $post_id );
        }

        $options     = get_option( 'sergio_ai_studio_options', array() );
        $source_pref = isset( $options['image_source_provider'] ) ? $options['image_source_provider'] : 'reference';

        $attachment_id = 0;
        $clean_title   = sanitize_text_field( wp_strip_all_tags( $headline ) );
        $has_mb        = function_exists( 'mb_substr' );
        $alt_text      = ! empty( $focus_keyword )
            ? sanitize_text_field( $focus_keyword ) . ' - ' . ( $has_mb ? mb_substr( $clean_title, 0, 60, 'UTF-8' ) : substr( $clean_title, 0, 60 ) )
            : ( $has_mb ? mb_substr( $clean_title, 0, 80, 'UTF-8' ) : substr( $clean_title, 0, 80 ) );

        // 1. PRIMARY: Extract from Reference Sources (Ghi rõ nguồn tác giả / báo chí)
        if ( ! empty( $source_references ) && 'template_only' !== $source_pref ) {
            $ref_id = $this->extract_and_attach_reference_image( $post_id, $clean_title, $focus_keyword, (array) $source_references );
            if ( $ref_id && ! is_wp_error( $ref_id ) ) {
                return $ref_id;
            }
        }

        // 2. SECONDARY: Attempt Stock provider if configured
        if ( 'stock' === $source_pref ) {
            $stock_id = $this->fetch_stock_image( $post_id, $focus_keyword ? $focus_keyword : $clean_title, $alt_text );
            if ( ! is_wp_error( $stock_id ) && $stock_id > 0 ) {
                $attachment_id = $stock_id;
            }
        }

        // 3. FALLBACK: High Quality GD Template Banner (1200x630)
        if ( empty( $attachment_id ) ) {
            $attachment_id = $this->generate_template_banner( $post_id, $clean_title, $alt_text );
            update_post_meta( $post_id, '_sergio_ai_image_type', 'gd_template' );
        }

        if ( is_wp_error( $attachment_id ) || empty( $attachment_id ) ) {
            $this->logger->warning( 'image_service_warning', 'Không thể tạo ảnh đại diện: ' . ( is_wp_error( $attachment_id ) ? $attachment_id->get_error_message() : 'Unknown error' ) );
            return $attachment_id;
        }

        // Assign as Featured Image
        set_post_thumbnail( $post_id, $attachment_id );

        // Rank Math SEO PRO Compatibility: Set ALT, Title, Caption
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
        wp_update_post( array(
            'ID'           => $attachment_id,
            'post_title'   => $alt_text,
            'post_excerpt' => sprintf( __( 'Hình ảnh minh họa: %s (Sergio AI Studio)', 'sergio-ai-studio' ), $clean_title ),
        ) );

        // Store attachment meta link
        update_post_meta( $post_id, '_sergio_ai_featured_image_id', $attachment_id );

        $this->logger->info( 'image_service_attached', sprintf( 'Đã gắn ảnh đại diện #%d đạt chuẩn Rank Math cho bài viết #%d.', $attachment_id, $post_id ) );

        return $attachment_id;
    }

    /**
     * Extract representative image from reference sources and attach to post
     * Credits the publisher/source clearly in Caption, Alt Text, and Meta.
     *
     * @param int    $post_id           WordPress Post ID
     * @param string $headline          Article Title
     * @param string $focus_keyword     Rank Math Focus Keyword
     * @param array  $source_references Array of source items or URLs
     * @return int|false Attachment ID on success, false if no source image found
     */
    public function extract_and_attach_reference_image( $post_id, $headline, $focus_keyword, array $source_references ) {
        if ( empty( $source_references ) ) {
            return false;
        }

        // Include WordPress media / attachment admin functions if missing
        if ( ! function_exists( 'download_url' ) && defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/file.php' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( ! function_exists( 'media_handle_sideload' ) && defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/media.php' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) && defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/image.php' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // Check up to 3 candidate sources
        $candidates = array_slice( $source_references, 0, 3 );

        foreach ( $candidates as $src ) {
            $src_url       = '';
            $src_image_url = '';
            $publisher     = '';

            if ( is_string( $src ) ) {
                $src_url = trim( $src );
            } elseif ( is_array( $src ) ) {
                $src_url       = $src['url'] ?? '';
                $src_image_url = $src['image_url'] ?? '';
                $publisher     = $src['publisher'] ?? ( $src['source_domain'] ?? '' );
            }

            if ( empty( $src_url ) && empty( $src_image_url ) ) {
                continue;
            }

            $src_domain = ! empty( $src_url ) ? (string) wp_parse_url( $src_url, PHP_URL_HOST ) : '';
            if ( empty( $publisher ) && ! empty( $src_domain ) ) {
                $publisher = preg_replace( '/^www\./i', '', $src_domain );
            }

            // 1. If image URL is not already known, crawl the source webpage header for og:image
            if ( empty( $src_image_url ) && ! empty( $src_url ) ) {
                $src_image_url = $this->scrape_image_from_webpage( $src_url );
            }

            if ( empty( $src_image_url ) ) {
                continue;
            }

            // Validate image URL
            $validated_img_url = wp_http_validate_url( $src_image_url );
            if ( ! $validated_img_url ) {
                // If relative URL, try to resolve with base domain
                if ( 0 === strpos( $src_image_url, '/' ) && ! empty( $src_url ) ) {
                    $parsed_base = wp_parse_url( $src_url );
                    $base_scheme = $parsed_base['scheme'] ?? 'https';
                    $base_host   = $parsed_base['host'] ?? '';
                    $resolved_url = $base_scheme . '://' . $base_host . $src_image_url;
                    $validated_img_url = wp_http_validate_url( $resolved_url );
                }
            }

            if ( ! $validated_img_url ) {
                continue;
            }

            // Download image to temp file
            $tmp_file = function_exists( 'download_url' )
                ? download_url( $validated_img_url, 15 )
                : $this->download_image_fallback( $validated_img_url );

            if ( is_wp_error( $tmp_file ) || ! file_exists( (string) $tmp_file ) ) {
                $this->logger->info(
                    'image_download_failed',
                    sprintf( 'Không thể tải ảnh từ %s: %s', $validated_img_url, is_wp_error( $tmp_file ) ? $tmp_file->get_error_message() : 'File not found' )
                );
                continue;
            }

            // Validate image integrity and dimensions
            $img_info = @getimagesize( $tmp_file );
            if ( ! $img_info || empty( $img_info[0] ) || empty( $img_info[1] ) ) {
                @unlink( $tmp_file );
                continue;
            }

            $width  = (int) $img_info[0];
            $height = (int) $img_info[1];
            $mime   = $img_info['mime'] ?? '';

            // Reject tiny icons/avatars (require at least 320x180)
            if ( $width < 320 || $height < 180 ) {
                @unlink( $tmp_file );
                continue;
            }

            // Allowed mime types
            $allowed_mimes = array( 'image/jpeg', 'image/png', 'image/webp', 'image/avif' );
            if ( ! in_array( $mime, $allowed_mimes, true ) ) {
                @unlink( $tmp_file );
                continue;
            }

            // Prepare extension
            $ext = 'jpg';
            if ( 'image/png' === $mime ) {
                $ext = 'png';
            } elseif ( 'image/webp' === $mime ) {
                $ext = 'webp';
            } elseif ( 'image/avif' === $mime ) {
                $ext = 'avif';
            }

            // Create clean filename based on headline and focus keyword
            $base_slug = ! empty( $focus_keyword ) ? sanitize_title( $focus_keyword ) : sanitize_title( $headline );
            $filename  = substr( $base_slug, 0, 50 ) . '-' . wp_generate_password( 6, false, false ) . '.' . $ext;

            $file_array = array(
                'name'     => $filename,
                'tmp_name' => $tmp_file,
            );

            // Ghi rõ nguồn trích dẫn tác giả / báo chí
            $source_credit_name = ! empty( $publisher ) ? $publisher : $src_domain;
            $caption_credit     = sprintf( __( 'Ảnh: %s (Nguồn: %s)', 'sergio-ai-studio' ), esc_html( $source_credit_name ), esc_html( $src_domain ? $src_domain : $source_credit_name ) );
            $alt_text           = ! empty( $focus_keyword )
                ? sprintf( '%s - Nguồn: %s', sanitize_text_field( $focus_keyword ), sanitize_text_field( $source_credit_name ) )
                : sprintf( '%s - Nguồn: %s', sanitize_text_field( $headline ), sanitize_text_field( $source_credit_name ) );
            $description        = sprintf(
                __( 'Hình ảnh minh họa được dẫn từ nguồn tham khảo: %s (Bản quyền tác phẩm thuộc về %s).', 'sergio-ai-studio' ),
                esc_url( $src_url ),
                esc_html( $source_credit_name )
            );

            $attachment_data = array(
                'post_title'   => $alt_text,
                'post_excerpt' => $caption_credit, // Caption in WordPress
                'post_content' => $description,    // Description in WordPress
            );

            $attachment_id = 0;
            if ( function_exists( 'media_handle_sideload' ) ) {
                $attachment_id = media_handle_sideload( $file_array, $post_id, null, $attachment_data );
            }

            if ( is_wp_error( $attachment_id ) || empty( $attachment_id ) ) {
                @unlink( $tmp_file );
                if ( is_wp_error( $attachment_id ) ) {
                    $this->logger->warning( 'media_sideload_failed', $attachment_id->get_error_message() );
                }
                continue;
            }

            // Set Rank Math Focus Keyword Alt Text
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );

            // Save source provenance meta on post
            update_post_meta( $post_id, '_sergio_ai_image_source_credit', $source_credit_name );
            if ( ! empty( $src_url ) ) {
                update_post_meta( $post_id, '_sergio_ai_image_source_url', esc_url_raw( $src_url ) );
            }
            update_post_meta( $post_id, '_sergio_ai_image_type', 'source_reference' );

            // Attach thumbnail to post
            set_post_thumbnail( $post_id, $attachment_id );
            update_post_meta( $post_id, '_sergio_ai_featured_image_id', $attachment_id );

            $this->logger->info(
                'image_source_attached',
                sprintf( 'Đã lấy và gắn ảnh thành công từ nguồn tham khảo "%s" (URL: %s) cho bài viết #%d', $source_credit_name, $validated_img_url, $post_id )
            );

            return (int) $attachment_id;
        }

        return false;
    }

    /**
     * Scrape lead image (og:image, twitter:image, JSON-LD) from webpage HTML
     *
     * @param string $url Webpage URL
     * @return string
     */
    public function scrape_image_from_webpage( string $url ): string {
        if ( ! wp_http_validate_url( $url ) ) {
            return '';
        }

        $response = wp_safe_remote_get( $url, array(
            'timeout'             => 7,
            'limit_response_size' => 150000, // Read only first 150KB (covers <head>)
            'headers'             => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml',
            ),
        ) );

        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return '';
        }

        $html = (string) wp_remote_retrieve_body( $response );
        if ( empty( $html ) ) {
            return '';
        }

        // 1. og:image or og:image:secure_url
        if ( preg_match( '/<meta[^>]+property=[\'"]og:image(?:(?::secure_url))?[\'"][^>]+content=[\'"]([^\'"]+)[\'"]/i', $html, $m ) ) {
            return trim( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
        }
        if ( preg_match( '/<meta[^>]+content=[\'"]([^\'"]+)[\'"][^>]+property=[\'"]og:image(?:(?::secure_url))?[\'"]/i', $html, $m ) ) {
            return trim( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
        }

        // 2. twitter:image
        if ( preg_match( '/<meta[^>]+(?:name|property)=[\'"]twitter:image(?::src)?[\'"][^>]+content=[\'"]([^\'"]+)[\'"]/i', $html, $m ) ) {
            return trim( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
        }
        if ( preg_match( '/<meta[^>]+content=[\'"]([^\'"]+)[\'"][^>]+(?:name|property)=[\'"]twitter:image(?::src)?[\'"]/i', $html, $m ) ) {
            return trim( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
        }

        // 3. JSON-LD Image
        if ( preg_match_all( '/<script\s+[^>]*type=[\'"]application\/ld\+json[\'"][^>]*>(.*?)<\/script>/is', $html, $matches ) ) {
            foreach ( $matches[1] as $json_raw ) {
                $data = json_decode( trim( $json_raw ), true );
                if ( ! is_array( $data ) ) {
                    continue;
                }
                $items = isset( $data['@graph'] ) && is_array( $data['@graph'] ) ? $data['@graph'] : array( $data );
                foreach ( $items as $item ) {
                    if ( ! empty( $item['image'] ) ) {
                        if ( is_string( $item['image'] ) ) {
                            return trim( $item['image'] );
                        } elseif ( is_array( $item['image'] ) ) {
                            if ( isset( $item['image']['url'] ) && is_string( $item['image']['url'] ) ) {
                                return trim( $item['image']['url'] );
                            } elseif ( isset( $item['image'][0] ) && is_string( $item['image'][0] ) ) {
                                return trim( $item['image'][0] );
                            }
                        }
                    }
                }
            }
        }

        return '';
    }

    /**
     * Fallback file download when download_url is not available
     */
    private function download_image_fallback( $url ) {
        $response = wp_safe_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array( 'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' ),
        ) );

        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return new WP_Error( 'download_failed', 'Không thể tải file ảnh.' );
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return new WP_Error( 'empty_body', 'Dữ liệu ảnh rỗng.' );
        }

        $tmp_file = wp_tempnam();
        if ( ! $tmp_file ) {
            $tmp_file = tempnam( sys_get_temp_dir(), 'srg_img_' );
        }

        file_put_contents( $tmp_file, $body );
        return $tmp_file;
    }

    /**
     * Generate elegant brand template banner using PHP GD library (1200x630)
     *
     * @param int    $post_id   Post ID
     * @param string $headline  Title text to draw on image
     * @param string $alt_text  Alt text
     * @return int|WP_Error Attachment ID
     */
    public function generate_template_banner( $post_id, $headline, $alt_text = '' ) {
        $upload_dir = wp_upload_dir();
        $filename   = 'studio-banner-' . $post_id . '-' . wp_generate_password( 6, false, false ) . '.png';
        $file_path  = trailingslashit( $upload_dir['path'] ) . $filename;

        $created = $this->generate_gd_image_file( $headline, $file_path );
        if ( is_wp_error( $created ) ) {
            return $created;
        }

        // Insert into WordPress Media Library
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment = array(
            'post_mime_type' => 'image/png',
            'post_title'     => ! empty( $alt_text ) ? $alt_text : $headline,
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );
        if ( is_wp_error( $attach_id ) ) {
            return $attach_id;
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return $attach_id;
    }

    /**
     * Create raw GD PNG banner file (1200x630)
     *
     * @param string $headline    Title text to draw on canvas
     * @param string $target_path Destination file path
     * @return string|WP_Error   File path on success or WP_Error
     */
    public function generate_gd_image_file( $headline, $target_path = '' ) {
        if ( ! extension_loaded( 'gd' ) ) {
            return new WP_Error( 'gd_missing', __( 'Máy chủ PHP chưa kích hoạt thư viện GD để tạo ảnh.', 'sergio-ai-studio' ) );
        }

        $width  = self::BANNER_WIDTH;
        $height = self::BANNER_HEIGHT;

        $im = imagecreatetruecolor( $width, $height );
        if ( ! $im ) {
            return new WP_Error( 'gd_create_failed', __( 'Không thể khởi tạo canvas GD.', 'sergio-ai-studio' ) );
        }

        // Modern Slate Gradient Background (#0f172a to #1e293b)
        for ( $y = 0; $y < $height; $y++ ) {
            $factor = $y / $height;
            $r = (int) ( 15 + $factor * ( 30 - 15 ) );
            $g = (int) ( 23 + $factor * ( 41 - 23 ) );
            $b = (int) ( 42 + $factor * ( 59 - 42 ) );
            $color = imagecolorallocate( $im, $r, $g, $b );
            imageline( $im, 0, $y, $width, $y, $color );
        }

        // Draw Ambient Indigo Glow Arc (Top Right)
        $indigo = imagecolorallocatealpha( $im, 99, 102, 241, 95 ); // #6366f1 subtle
        imagefilledellipse( $im, 1050, 80, 500, 500, $indigo );

        // Draw Accent Indigo hairline strip on the left border
        $accent_bar = imagecolorallocate( $im, 99, 102, 241 ); // #6366f1
        imagefilledrectangle( $im, 60, 80, 68, 160, $accent_bar );

        // Color allocations
        $white       = imagecolorallocate( $im, 255, 255, 255 );
        $text_muted  = imagecolorallocate( $im, 148, 163, 184 ); // #94a3b8
        $border_col  = imagecolorallocate( $im, 51, 65, 85 );    // #334155
        $badge_bg    = imagecolorallocate( $im, 30, 41, 59 );    // #1e293b

        // Header Category / Brand Badge
        $badge_text = 'SERGIO AI STUDIO • INSIGHTS 2026';
        imagefilledrectangle( $im, 85, 95, 420, 145, $badge_bg );
        imagerectangle( $im, 85, 95, 420, 145, $border_col );
        imagestring( $im, 4, 105, 112, $badge_text, $white );

        // Word-wrap Headline into lines (Max 3 lines, max 42 chars per line)
        $words = explode( ' ', $headline );
        $lines = array();
        $curr_line = '';

        foreach ( $words as $w ) {
            $test_line = empty( $curr_line ) ? $w : $curr_line . ' ' . $w;
            $len = function_exists( 'mb_strlen' ) ? mb_strlen( $test_line, 'UTF-8' ) : strlen( $test_line );
            if ( $len <= 40 ) {
                $curr_line = $test_line;
            } else {
                if ( ! empty( $curr_line ) ) {
                    $lines[] = $curr_line;
                }
                $curr_line = $w;
                if ( count( $lines ) >= 3 ) {
                    break;
                }
            }
        }
        if ( ! empty( $curr_line ) && count( $lines ) < 3 ) {
            $lines[] = $curr_line;
        }

        // Draw Headline text using built-in font
        $start_y = 220;
        foreach ( $lines as $idx => $line_str ) {
            imagestring( $im, 5, 85, $start_y + ( $idx * 52 ), $line_str, $white );
        }

        // Footer Metadata & Provenance
        $site_name = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : 'Sergio Themes';
        $footer_text = sprintf( '%s  |  Verified Knowledge & Research', $site_name );
        imageline( $im, 85, 520, 1115, 520, $border_col );
        imagestring( $im, 4, 85, 545, $footer_text, $text_muted );

        if ( empty( $target_path ) ) {
            $upload_dir  = function_exists( 'wp_upload_dir' ) ? wp_upload_dir() : array( 'path' => sys_get_temp_dir() );
            $target_path = rtrim( $upload_dir['path'], '/\\' ) . '/studio-banner-' . time() . '.png';
        }

        imagepng( $im, $target_path, 8 );
        imagedestroy( $im );

        if ( ! file_exists( $target_path ) ) {
            return new WP_Error( 'image_save_failed', __( 'Không thể lưu file ảnh vào thư mục đích.', 'sergio-ai-studio' ) );
        }

        return $target_path;
    }

    /**
     * Optional Stock Image Provider (e.g. Unsplash / Pexels)
     */
    private function fetch_stock_image( $post_id, $query, $alt_text ) {
        $options   = get_option( 'sergio_ai_studio_options', array() );
        $stock_key = isset( $options['stock_api_key'] ) ? Sergio_AI_Security::decrypt( $options['stock_api_key'] ) : '';

        if ( empty( $stock_key ) ) {
            return 0; // Fallback to template banner
        }

        // Example: Pexels API Query
        $clean_query = rawurlencode( wp_strip_all_tags( $query ) );
        $api_url     = 'https://api.pexels.com/v1/search?query=' . $clean_query . '&per_page=1&orientation=landscape';

        $res = wp_remote_get( $api_url, array(
            'headers' => array( 'Authorization' => $stock_key ),
            'timeout' => 12,
        ) );

        if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
            return 0;
        }

        $data = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( empty( $data['photos'][0]['src']['large2x'] ) ) {
            return 0;
        }

        $photo_url = $data['photos'][0]['src']['large2x'];
        $photog    = ! empty( $data['photos'][0]['photographer'] ) ? $data['photos'][0]['photographer'] : 'Pexels';

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $desc = sprintf( __( 'Ảnh minh họa bởi %s qua Pexels (Có bản quyền miễn phí)', 'sergio-ai-studio' ), $photog );
        $attach_id = media_sideload_image( $photo_url, $post_id, $desc, 'id' );

        return is_wp_error( $attach_id ) ? 0 : $attach_id;
    }
}
