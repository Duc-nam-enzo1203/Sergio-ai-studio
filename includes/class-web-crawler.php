<?php
/**
 * Web Crawler — Sergio AI Studio
 *
 * Crawls external news pages using wp_safe_remote_get() (SSRF-safe),
 * extracts article body via DOMDocument, strips ads/nav/scripts,
 * and returns clean text ready for AI rewriting.
 *
 * Security hardening:
 * - Uses wp_safe_remote_get() which blocks private/internal IPs automatically.
 * - Validates URL via wp_http_validate_url() before any fetch.
 * - Limits response body to 500 KB.
 * - Sanitizes extracted text before returning.
 *
 * @package Sergio_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sergio_AI_Web_Crawler {

    /**
     * Maximum response body size in bytes (500 KB).
     */
    const MAX_BODY_SIZE = 512000;

    /**
     * Request timeout in seconds.
     */
    const TIMEOUT = 30;

    /**
     * Browser-like User-Agent for crawl requests.
     */
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    /**
     * Crawl a single URL and return structured article data.
     *
     * @param  string $url     The URL to crawl (must be http/https).
     * @param  array  $options Optional: ['max_chars' => 8000].
     * @return array|WP_Error  Array with keys: url, title, content, meta_description, published_date, source_domain.
     */
    public function crawl_url( string $url, array $options = array() ) {
        // 1. Validate URL structure.
        $validated = wp_http_validate_url( $url );
        if ( ! $validated ) {
            return new WP_Error( 'invalid_url', sprintf( __( 'URL không hợp lệ hoặc không được phép: %s', 'sergio-ai-studio' ), esc_url( $url ) ) );
        }

        // 2. Only allow http/https schemes (no file://, ftp://, data://).
        $scheme = wp_parse_url( $validated, PHP_URL_SCHEME );
        if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
            return new WP_Error( 'invalid_scheme', __( 'Chỉ cho phép crawl URL với giao thức http hoặc https.', 'sergio-ai-studio' ) );
        }

        // 3. Fetch using wp_safe_remote_get() — WP automatically blocks private IPs.
        $response = wp_safe_remote_get( $validated, array(
            'timeout'    => self::TIMEOUT,
            'user-agent' => self::USER_AGENT,
            'headers'    => array(
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'vi,en-US;q=0.9,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate',
            ),
            'redirection' => 3,
            'sslverify'   => true,
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'fetch_failed',
                sprintf( __( 'Không thể tải URL: %s', 'sergio-ai-studio' ), $response->get_error_message() )
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code < 200 || $status_code >= 400 ) {
            return new WP_Error(
                'http_error',
                sprintf( __( 'Trang trả về lỗi HTTP %d.', 'sergio-ai-studio' ), $status_code )
            );
        }

        // 4. Check Content-Type — only parse text/html.
        $content_type = wp_remote_retrieve_header( $response, 'content-type' );
        if ( ! empty( $content_type ) && false === strpos( $content_type, 'html' ) ) {
            return new WP_Error( 'not_html', __( 'URL này không phải trang HTML (có thể là PDF/JSON/binary).', 'sergio-ai-studio' ) );
        }

        $body = wp_remote_retrieve_body( $response );

        // 5. Enforce size limit.
        if ( strlen( $body ) > self::MAX_BODY_SIZE ) {
            $body = substr( $body, 0, self::MAX_BODY_SIZE );
        }

        if ( empty( $body ) ) {
            return new WP_Error( 'empty_body', __( 'Trang không có nội dung HTML.', 'sergio-ai-studio' ) );
        }

        // 6. Parse HTML.
        $max_chars = isset( $options['max_chars'] ) ? absint( $options['max_chars'] ) : 8000;

        $metadata     = $this->extract_metadata( $body );
        $article_text = $this->parse_article_content( $body, $max_chars );

        if ( empty( trim( $article_text ) ) ) {
            return new WP_Error( 'no_content', __( 'Không tìm thấy nội dung bài viết trên trang này.', 'sergio-ai-studio' ) );
        }

        return array(
            'url'              => $validated,
            'source_domain'    => wp_parse_url( $validated, PHP_URL_HOST ),
            'title'            => $metadata['title'],
            'meta_description' => $metadata['description'],
            'published_date'   => $metadata['published_date'],
            'content'          => $article_text,
        );
    }

    /**
     * Crawl multiple URLs and return array of results (skips errors).
     *
     * @param  string[] $urls
     * @param  array    $options
     * @return array[]
     */
    public function crawl_multiple( array $urls, array $options = array() ): array {
        $results = array();
        $seen    = array();

        foreach ( $urls as $url ) {
            $url = trim( $url );
            if ( empty( $url ) || isset( $seen[ $url ] ) ) {
                continue;
            }
            $seen[ $url ] = true;

            $result = $this->crawl_url( $url, $options );
            if ( ! is_wp_error( $result ) ) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Extract og:title, og:description, article:published_time from HTML.
     *
     * @param  string $html
     * @return array{title: string, description: string, published_date: string}
     */
    private function extract_metadata( string $html ): array {
        $meta = array(
            'title'          => '',
            'description'    => '',
            'published_date' => '',
        );

        // Use DOMDocument to safely parse.
        $dom = new DOMDocument();
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        @$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
        $xpath = new DOMXPath( $dom );

        // og:title
        $og_title = $xpath->query( '//meta[@property="og:title"]/@content' );
        if ( $og_title && $og_title->length > 0 ) {
            $meta['title'] = sanitize_text_field( $og_title->item( 0 )->nodeValue );
        }

        // <title> fallback
        if ( empty( $meta['title'] ) ) {
            $title_nodes = $xpath->query( '//title' );
            if ( $title_nodes && $title_nodes->length > 0 ) {
                $meta['title'] = sanitize_text_field( $title_nodes->item( 0 )->nodeValue );
            }
        }

        // og:description
        $og_desc = $xpath->query( '//meta[@property="og:description"]/@content' );
        if ( $og_desc && $og_desc->length > 0 ) {
            $meta['description'] = sanitize_text_field( $og_desc->item( 0 )->nodeValue );
        }

        // article:published_time
        $pub_time = $xpath->query( '//meta[@property="article:published_time"]/@content' );
        if ( $pub_time && $pub_time->length > 0 ) {
            $meta['published_date'] = sanitize_text_field( $pub_time->item( 0 )->nodeValue );
        }

        return $meta;
    }

    /**
     * Extract clean article text from HTML.
     * Priority: <article> → <main> → [class*=content] → <body>
     * Removes: nav, header, footer, aside, script, style, iframe, form, ads.
     *
     * @param  string $html
     * @param  int    $max_chars
     * @return string Plain text of article body, limited to $max_chars.
     */
    private function parse_article_content( string $html, int $max_chars = 8000 ): string {
        $dom = new DOMDocument();
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        @$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
        $xpath = new DOMXPath( $dom );

        // Remove noise elements.
        $noise_selectors = array(
            '//nav', '//header', '//footer', '//aside',
            '//script', '//style', '//iframe', '//form',
            '//noscript', '//figure[@class]',
            '//*[contains(@class,"ad")]',
            '//*[contains(@class,"advertisement")]',
            '//*[contains(@class,"sidebar")]',
            '//*[contains(@class,"comment")]',
            '//*[contains(@class,"related")]',
            '//*[contains(@class,"social")]',
            '//*[contains(@class,"share")]',
            '//*[contains(@class,"newsletter")]',
            '//*[contains(@class,"popup")]',
            '//*[contains(@id,"sidebar")]',
            '//*[contains(@id,"comment")]',
            '//*[contains(@id,"footer")]',
        );

        foreach ( $noise_selectors as $selector ) {
            $nodes = $xpath->query( $selector );
            if ( $nodes ) {
                foreach ( $nodes as $node ) {
                    if ( $node->parentNode ) {
                        $node->parentNode->removeChild( $node );
                    }
                }
            }
        }

        // Try to find the main content area.
        $candidates = array(
            '//article',
            '//main',
            '//*[contains(@class,"post-content")]',
            '//*[contains(@class,"entry-content")]',
            '//*[contains(@class,"article-content")]',
            '//*[contains(@class,"article-body")]',
            '//*[contains(@class,"content-body")]',
            '//*[contains(@class,"post-body")]',
            '//*[contains(@class,"story-body")]',
            '//*[contains(@id,"article")]',
            '//*[contains(@id,"content")]',
            '//body',
        );

        $content_node = null;
        foreach ( $candidates as $selector ) {
            $nodes = $xpath->query( $selector );
            if ( $nodes && $nodes->length > 0 ) {
                $content_node = $nodes->item( 0 );
                break;
            }
        }

        if ( null === $content_node ) {
            // Last resort: full body text.
            return $this->sanitize_crawled_text( $dom->textContent, $max_chars );
        }

        // Extract paragraph texts.
        $paragraphs = $xpath->query( './/p', $content_node );
        $parts      = array();

        if ( $paragraphs && $paragraphs->length > 0 ) {
            foreach ( $paragraphs as $p ) {
                $text = trim( $p->textContent );
                if ( strlen( $text ) > 40 ) { // Skip very short fragments.
                    $parts[] = $text;
                }
            }
        }

        if ( ! empty( $parts ) ) {
            $raw_text = implode( "\n\n", $parts );
        } else {
            // Fallback to full node text if no <p> found.
            $raw_text = $content_node->textContent;
        }

        return $this->sanitize_crawled_text( $raw_text, $max_chars );
    }

    /**
     * Normalize and limit crawled text.
     *
     * @param  string $raw
     * @param  int    $max_chars
     * @return string
     */
    private function sanitize_crawled_text( string $raw, int $max_chars ): string {
        // Strip remaining HTML tags.
        $text = wp_strip_all_tags( $raw );

        // Normalize whitespace.
        $text = preg_replace( '/[ \t]+/', ' ', $text );
        $text = preg_replace( '/\n{3,}/', "\n\n", $text );
        $text = trim( $text );

        // Enforce character limit.
        if ( mb_strlen( $text ) > $max_chars ) {
            $text = mb_substr( $text, 0, $max_chars );
            // Don't cut mid-word.
            $last_space = mb_strrpos( $text, ' ' );
            if ( $last_space > $max_chars - 200 ) {
                $text = mb_substr( $text, 0, $last_space );
            }
            $text .= '…';
        }

        return $text;
    }
}
