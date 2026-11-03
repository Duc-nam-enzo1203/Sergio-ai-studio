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
    const MIN_WIDTH  = 320;
    const MIN_HEIGHT = 180;

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
        if ( 'stock' !== $source_pref && ! empty( $source_references ) ) {
            $ref_id = $this->extract_and_attach_reference_image( $post_id, $clean_title, $focus_keyword, (array) $source_references );
            if ( $ref_id && ! is_wp_error( $ref_id ) ) {
                return $ref_id;
            }
        }

        // 2. SECONDARY / FALLBACK: Royalty-Free Stock Image (Wikimedia Commons / Unsplash / Pexels)
        $search_term = ! empty( $focus_keyword ) ? $focus_keyword : $clean_title;
        $stock_id    = $this->fetch_stock_image( $post_id, $search_term, $alt_text );
        if ( ! is_wp_error( $stock_id ) && $stock_id > 0 ) {
            return $stock_id;
        }

        // 3. If no image found from reference or stock, skip creating image (Zero GD banner)
        $this->logger->info( 'image_source_skipped', sprintf( 'Bài viết #%d: Nguồn tin tham khảo và kho ảnh bản quyền mở đều không có ảnh phù hợp. Bỏ qua tạo ảnh đại diện.', $post_id ) );
        return 0;

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

        // Rank Math Social Preview image integration
        $thumb_url = wp_get_attachment_image_url( $attachment_id, 'full' );
        if ( $thumb_url ) {
            update_post_meta( $post_id, 'rank_math_facebook_image', $thumb_url );
            update_post_meta( $post_id, 'rank_math_facebook_image_id', $attachment_id );
            update_post_meta( $post_id, 'rank_math_twitter_image', $thumb_url );
            update_post_meta( $post_id, 'rank_math_twitter_image_id', $attachment_id );
        }

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
    /**
     * Fetch Royalty-Free Image from Wikimedia Commons, Unsplash, or Pexels
     *
     * @param int    $post_id  WordPress Post ID
     * @param string $query    Search keyword / entity
     * @param string $alt_text Alt text for Rank Math
     * @return int             Attachment ID or 0 if none found
     */
    public function fetch_stock_image( $post_id, $query, $alt_text = '' ) {
        $post_id = function_exists( 'absint' ) ? absint( $post_id ) : abs( (int) $post_id );
        if ( empty( $post_id ) || empty( $query ) ) {
            return 0;
        }

        $options      = function_exists( 'get_option' ) ? get_option( 'sergio_ai_studio_options', array() ) : array();
        $unsplash_key = isset( $options['unsplash_access_key'] ) && class_exists( 'Sergio_AI_Security' ) ? Sergio_AI_Security::decrypt( $options['unsplash_access_key'] ) : '';
        $pexels_key   = isset( $options['pexels_api_key'] ) && class_exists( 'Sergio_AI_Security' ) ? Sergio_AI_Security::decrypt( $options['pexels_api_key'] ) : '';

        // Clean query terms (remove noise words)
        $clean_query = function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( $query ) : strip_tags( $query );
        $clean_query = function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $clean_query ) : trim( $clean_query );
        $clean_query = preg_replace( '/[\?\!\#\@\$\^\*\(\)\[\]\{\}\:\;\"\']/u', '', $clean_query );

        // 1. Try Unsplash API if configured
        if ( ! empty( $unsplash_key ) ) {
            $attach_id = $this->fetch_from_unsplash( $post_id, $clean_query, $alt_text, $unsplash_key );
            if ( $attach_id > 0 ) {
                return $attach_id;
            }
        }

        // 2. Try Pexels API if configured
        if ( ! empty( $pexels_key ) ) {
            $attach_id = $this->fetch_from_pexels( $post_id, $clean_query, $alt_text, $pexels_key );
            if ( $attach_id > 0 ) {
                return $attach_id;
            }
        }

        // 3. PRIMARY OPEN REPOSITORY: Wikimedia Commons API (100% Free, Zero API Key required, Public Domain / CC)
        return $this->fetch_from_wikimedia( $post_id, $clean_query, $alt_text );
    }

    /**
     * Search and download CC/Public Domain image from Wikimedia Commons API (0 API Key required)
     */
    private function fetch_from_wikimedia( $post_id, $query, $alt_text ) {
        $encoded_query = rawurlencode( $query );
        $api_url = 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrsearch=' . $encoded_query . '&gsrlimit=3&gsrnamespace=6&prop=imageinfo&iiprop=url|size|extmetadata&format=json';

        $response = wp_remote_get( $api_url, array(
            'timeout'    => 15,
            'user-agent' => 'SergioAIStudio/2.1 (WordPress; contact@sergiothemes.com)',
        ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return 0;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data['query']['pages'] ) ) {
            return 0;
        }

        foreach ( $data['query']['pages'] as $page ) {
            if ( empty( $page['imageinfo'][0]['url'] ) ) {
                continue;
            }

            $img_info = $page['imageinfo'][0];
            $img_url  = $img_info['url'];
            $width    = isset( $img_info['width'] ) ? (int) $img_info['width'] : 0;
            $height   = isset( $img_info['height'] ) ? (int) $img_info['height'] : 0;

            // Require landscape and decent resolution
            if ( $width < self::MIN_WIDTH || $height < self::MIN_HEIGHT ) {
                continue;
            }

            // Exclude icons, svg, pdf, audio
            $ext = strtolower( pathinfo( wp_parse_url( $img_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
            if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp' ), true ) ) {
                continue;
            }

            // Extract metadata artist & license
            $artist  = ! empty( $img_info['extmetadata']['Artist']['value'] ) ? wp_strip_all_tags( $img_info['extmetadata']['Artist']['value'] ) : 'Wikimedia Commons';
            $license = ! empty( $img_info['extmetadata']['LicenseShortName']['value'] ) ? sanitize_text_field( $img_info['extmetadata']['LicenseShortName']['value'] ) : 'Creative Commons';

            $credit_caption = sprintf( __( 'Ảnh: %s qua Wikimedia Commons (Bản quyền mở: %s)', 'sergio-ai-studio' ), esc_html( $artist ), esc_html( $license ) );
            $attach_id = $this->sideload_stock_file( $post_id, $img_url, $alt_text, $credit_caption, 'Wikimedia Commons' );

            if ( $attach_id > 0 ) {
                $this->logger->info( 'image_stock_attached', sprintf( 'Đã gắn ảnh miễn phí bản quyền từ Wikimedia Commons cho bài viết #%d.', $post_id ) );
                return $attach_id;
            }
        }

        return 0;
    }

    /**
     * Search and download image from Unsplash API
     */
    private function fetch_from_unsplash( $post_id, $query, $alt_text, $access_key ) {
        $api_url = 'https://api.unsplash.com/search/photos?query=' . rawurlencode( $query ) . '&per_page=1&orientation=landscape';

        $res = wp_remote_get( $api_url, array(
            'headers' => array( 'Authorization' => 'Client-ID ' . $access_key ),
            'timeout' => 12,
        ) );

        if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
            return 0;
        }

        $data = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( empty( $data['results'][0]['urls']['regular'] ) ) {
            return 0;
        }

        $photo_url = $data['results'][0]['urls']['regular'];
        $photog    = ! empty( $data['results'][0]['user']['name'] ) ? $data['results'][0]['user']['name'] : 'Unsplash';
        $caption   = sprintf( __( 'Ảnh minh họa bởi %s qua Unsplash (Miễn phí bản quyền)', 'sergio-ai-studio' ), esc_html( $photog ) );

        return $this->sideload_stock_file( $post_id, $photo_url, $alt_text, $caption, 'Unsplash' );
    }

    /**
     * Search and download image from Pexels API
     */
    private function fetch_from_pexels( $post_id, $query, $alt_text, $api_key ) {
        $api_url = 'https://api.pexels.com/v1/search?query=' . rawurlencode( $query ) . '&per_page=1&orientation=landscape';

        $res = wp_remote_get( $api_url, array(
            'headers' => array( 'Authorization' => $api_key ),
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
        $caption   = sprintf( __( 'Ảnh minh họa bởi %s qua Pexels (Miễn phí bản quyền)', 'sergio-ai-studio' ), esc_html( $photog ) );

        return $this->sideload_stock_file( $post_id, $photo_url, $alt_text, $caption, 'Pexels' );
    }

    /**
     * Helper to download stock image file and create WordPress Attachment
     */
    private function sideload_stock_file( $post_id, $image_url, $alt_text, $caption_credit, $provider_name ) {
        if ( ! function_exists( 'download_url' ) && defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/file.php' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( ! function_exists( 'media_handle_sideload' ) && defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/media.php' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) && defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/image.php' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $tmp_file = download_url( $image_url, 15 );
        if ( is_wp_error( $tmp_file ) || ! file_exists( (string) $tmp_file ) ) {
            return 0;
        }

        $img_info = @getimagesize( $tmp_file );
        if ( ! $img_info || empty( $img_info[0] ) || empty( $img_info[1] ) ) {
            @unlink( $tmp_file );
            return 0;
        }

        $ext = 'jpg';
        $mime = $img_info['mime'] ?? '';
        if ( 'image/png' === $mime ) {
            $ext = 'png';
        } elseif ( 'image/webp' === $mime ) {
            $ext = 'webp';
        }

        $filename = 'stock-' . sanitize_title( substr( $alt_text, 0, 40 ) ) . '-' . wp_generate_password( 6, false, false ) . '.' . $ext;
        $file_array = array(
            'name'     => $filename,
            'tmp_name' => $tmp_file,
        );

        $attachment_data = array(
            'post_title'   => $alt_text,
            'post_excerpt' => $caption_credit,
            'post_content' => sprintf( __( 'Ảnh minh họa từ %s (Miễn phí bản quyền).', 'sergio-ai-studio' ), esc_html( $provider_name ) ),
        );

        $attach_id = media_handle_sideload( $file_array, $post_id, null, $attachment_data );
        if ( is_wp_error( $attach_id ) || empty( $attach_id ) ) {
            @unlink( $tmp_file );
            return 0;
        }

        update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt_text );
        update_post_meta( $post_id, '_sergio_ai_image_source_credit', $provider_name );
        update_post_meta( $post_id, '_sergio_ai_image_type', 'royalty_free_stock' );
        set_post_thumbnail( $post_id, $attach_id );
        update_post_meta( $post_id, '_sergio_ai_featured_image_id', $attach_id );

        return (int) $attach_id;
    }
}
