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
     * Honest Bot User-Agent for crawl requests identifying origin repository.
     */
    const USER_AGENT = 'Sergio-AI-Studio-Bot/2.1 (+https://github.com/Duc-nam-enzo1203/Sergio-ai-studio)';

    /**
     * Normalize URL by stripping tracking parameters (utm_*, fbclid, etc.)
     *
     * @param string $url
     * @return string
     */
    public static function normalize_url( string $url ): string {
        $parsed = wp_parse_url( trim( $url ) );
        if ( empty( $parsed['host'] ) ) {
            return $url;
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host   = strtolower( $parsed['host'] );
        $path   = $parsed['path'] ?? '/';
        $query_str = '';

        if ( ! empty( $parsed['query'] ) ) {
            parse_str( $parsed['query'], $query_params );
            $tracking_keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid', 'mc_cid', 'mc_eid', 'ref', 'source' );
            foreach ( $tracking_keys as $tk ) {
                unset( $query_params[ $tk ] );
            }
            if ( ! empty( $query_params ) ) {
                $query_str = '?' . http_build_query( $query_params );
            }
        }

        return $scheme . '://' . $host . $path . $query_str;
    }

    /**
     * Crawl a single URL and return structured article data.
     *
     * @param  string $url     The URL to crawl (must be http/https).
     * @param  array  $options Optional: ['max_chars' => 8000].
     * @return array|WP_Error  Array with keys: url, title, content, meta_description, published_date, source_domain.
     */
    public function crawl_url( string $url, array $options = array() ) {
        // 1. Normalize and validate URL structure.
        $normalized = self::normalize_url( $url );
        $validated  = wp_http_validate_url( $normalized );
        if ( ! $validated ) {
            return new WP_Error( 'invalid_url', sprintf( __( 'URL không hợp lệ hoặc không được phép: %s', 'sergio-ai-studio' ), esc_url( $url ) ) );
        }

        // 2. Only allow http/https schemes (no file://, ftp://, data://).
        $scheme = wp_parse_url( $validated, PHP_URL_SCHEME );
        if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
            return new WP_Error( 'invalid_scheme', __( 'Chỉ cho phép crawl URL với giao thức http hoặc https.', 'sergio-ai-studio' ) );
        }

        // 3. Respect robots.txt
        if ( ! $this->is_url_allowed_by_robots( $validated ) ) {
            return new WP_Error( 'robots_blocked', sprintf( __( 'Trang đích bị chặn bởi robots.txt: %s', 'sergio-ai-studio' ), esc_url( $validated ) ) );
        }

        // 4. Polite per-domain rate limiting
        $host = strtolower( (string) wp_parse_url( $validated, PHP_URL_HOST ) );
        $rate_key = 'sergio_crawl_host_' . md5( $host );
        if ( get_transient( $rate_key ) ) {
            usleep( 500000 ); // 0.5s polite pause
        }
        set_transient( $rate_key, 1, 2 );

        // 5. Fetch using wp_safe_remote_get() with strict body size streaming limits.
        $response = wp_safe_remote_get( $validated, array(
            'timeout'             => self::TIMEOUT,
            'user-agent'          => self::USER_AGENT,
            'limit_response_size' => self::MAX_BODY_SIZE,
            'headers'             => array(
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'vi,en-US;q=0.9,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate',
            ),
            'redirection'         => 3,
            'sslverify'           => true,
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
            'image_url'        => $metadata['image_url'] ?? '',
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
     * Extract og:title, og:description, og:image, article:published_time from HTML.
     *
     * @param  string $html
     * @return array{title: string, description: string, published_date: string, image_url: string}
     */
    private function extract_metadata( string $html ): array {
        $meta = array(
            'title'          => '',
            'description'    => '',
            'published_date' => '',
            'image_url'      => '',
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

        // og:image / twitter:image
        $og_img = $xpath->query( '//meta[@property="og:image"]/@content | //meta[@property="og:image:secure_url"]/@content | //meta[@name="twitter:image"]/@content | //meta[@name="twitter:image:src"]/@content' );
        if ( $og_img && $og_img->length > 0 ) {
            $meta['image_url'] = esc_url_raw( trim( $og_img->item( 0 )->nodeValue ) );
        }

        // JSON-LD fallback for image
        if ( empty( $meta['image_url'] ) ) {
            $meta['image_url'] = $this->extract_jsonld_image_url( $html );
        }

        return $meta;
    }

    /**
     * Extract clean article text from HTML.
     * Priority: JSON-LD (Schema.org articleBody) → <article> → <main> → [class*=content] → <body>
     * Removes: nav, header, footer, aside, script, style, iframe, form, ads.
     *
     * @param  string $html
     * @param  int    $max_chars
     * @return string Plain text of article body, limited to $max_chars.
     */
    private function parse_article_content( string $html, int $max_chars = 8000 ): string {
        // Priority 1: Extract authoritative articleBody from JSON-LD schema if available
        $jsonld_body = $this->extract_jsonld_article_body( $html );
        if ( ! empty( $jsonld_body ) && mb_strlen( $jsonld_body, 'UTF-8' ) >= 250 ) {
            return $this->sanitize_crawled_text( $jsonld_body, $max_chars );
        }

        $dom = new DOMDocument();
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        @$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
        $xpath = new DOMXPath( $dom );

        // Remove noise elements (Use precise class matching to prevent wiping "lead", "heading", "breadcrumbs")
        $noise_selectors = array(
            '//nav', '//header', '//footer', '//aside',
            '//script', '//style', '//iframe', '//form',
            '//noscript', '//figure[@class]',
            '//*[contains(concat(" ", normalize-space(@class), " "), " ad ") or contains(concat(" ", normalize-space(@class), " "), " ads ") or contains(@class,"advertisement") or contains(@class,"banner-ad") or contains(@class,"sponsored")]',
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

    /**
     * Check if a URL is permitted by destination site's robots.txt
     *
     * @param  string $url
     * @return bool
     */
    public function is_url_allowed_by_robots( string $url ): bool {
        $parsed = wp_parse_url( $url );
        if ( empty( $parsed['host'] ) ) {
            return true;
        }

        $scheme     = $parsed['scheme'] ?? 'https';
        $host       = $parsed['host'];
        $path       = $parsed['path'] ?? '/';
        $robots_url = $scheme . '://' . $host . '/robots.txt';

        $cache_key   = 'sergio_robots_' . md5( $host );
        $robots_body = get_transient( $cache_key );

        if ( false === $robots_body ) {
            $resp = wp_safe_remote_get( $robots_url, array(
                'timeout'             => 5,
                'user-agent'          => self::USER_AGENT,
                'limit_response_size' => 64000,
            ) );

            if ( is_wp_error( $resp ) || 200 !== wp_remote_retrieve_response_code( $resp ) ) {
                set_transient( $cache_key, 'ALLOW_ALL', 6 * HOUR_IN_SECONDS );
                return true;
            }

            $robots_body = wp_remote_retrieve_body( $resp );
            set_transient( $cache_key, $robots_body, 6 * HOUR_IN_SECONDS );
        }

        if ( 'ALLOW_ALL' === $robots_body || empty( $robots_body ) ) {
            return true;
        }

        $lines         = explode( "\n", $robots_body );
        $applies_to_us = false;

        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( empty( $line ) || 0 === strpos( $line, '#' ) ) {
                continue;
            }
            if ( preg_match( '/^User-agent:\s*(.+)$/i', $line, $m ) ) {
                $agent         = trim( $m[1] );
                $applies_to_us = ( '*' === $agent || stripos( self::USER_AGENT, $agent ) !== false );
                continue;
            }
            if ( $applies_to_us && preg_match( '/^Disallow:\s*(.+)$/i', $line, $m ) ) {
                $disallow = trim( $m[1] );
                if ( ! empty( $disallow ) && 0 === strpos( $path, $disallow ) ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check whether a URL was already crawled and processed
     *
     * @param  string $url
     * @return bool
     */
    public static function is_already_processed( string $url ): bool {
        global $wpdb;
        $table = Sergio_AI_DB::table( 'processed_urls' );
        $hash  = md5( $url );
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE url_hash = %s", $hash ) );
        return (int) $count > 0;
    }

    /**
     * Extract articleBody from JSON-LD Schema (Article, NewsArticle, BlogPosting, TechArticle)
     *
     * @param string $html
     * @return string
     */
    private function extract_jsonld_article_body( string $html ): string {
        if ( preg_match_all( '/<script\s+[^>]*type=[\'"]application\/ld\+json[\'"][^>]*>(.*?)<\/script>/is', $html, $matches ) ) {
            foreach ( $matches[1] as $json_raw ) {
                $data = json_decode( trim( $json_raw ), true );
                if ( ! is_array( $data ) ) {
                    continue;
                }
                $items = isset( $data['@graph'] ) && is_array( $data['@graph'] ) ? $data['@graph'] : array( $data );
                foreach ( $items as $item ) {
                    if ( ! empty( $item['@type'] ) ) {
                        $types = is_array( $item['@type'] ) ? $item['@type'] : array( $item['@type'] );
                        foreach ( $types as $t ) {
                            if ( in_array( strtolower( (string) $t ), array( 'article', 'newsarticle', 'blogposting', 'techarticle', 'report' ), true ) ) {
                                if ( ! empty( $item['articleBody'] ) && is_string( $item['articleBody'] ) && mb_strlen( $item['articleBody'], 'UTF-8' ) > 150 ) {
                                    return trim( $item['articleBody'] );
                                }
                            }
                        }
                    }
                }
            }
        }
        return '';
    }

    /**
     * Extract representative image URL from JSON-LD schema
     *
     * @param string $html
     * @return string
     */
    public function extract_jsonld_image_url( string $html ): string {
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
                            return esc_url_raw( trim( $item['image'] ) );
                        } elseif ( is_array( $item['image'] ) ) {
                            if ( isset( $item['image']['url'] ) && is_string( $item['image']['url'] ) ) {
                                return esc_url_raw( trim( $item['image']['url'] ) );
                            } elseif ( isset( $item['image'][0] ) && is_string( $item['image'][0] ) ) {
                                return esc_url_raw( trim( $item['image'][0] ) );
                            }
                        }
                    }
                    if ( ! empty( $item['thumbnailUrl'] ) && is_string( $item['thumbnailUrl'] ) ) {
                        return esc_url_raw( trim( $item['thumbnailUrl'] ) );
                    }
                }
            }
        }
        return '';
    }

    /**
     * Calculate 5-gram word sequence overlap similarity between source and generated article (0.0 to 1.0)
     * Enforces verifiable anti-plagiarism threshold.
     *
     * @param string $source_text Raw original text
     * @param string $target_text Generated article text
     * @param int    $n           N-gram size (default 5 words)
     * @return float
     */
    public static function calculate_ngram_similarity( string $source_text, string $target_text, int $n = 5 ): float {
        $tokenize = function( $text ) {
            $clean = function_exists( 'mb_strtolower' ) ? mb_strtolower( wp_strip_all_tags( $text ), 'UTF-8' ) : strtolower( wp_strip_all_tags( $text ) );
            $clean = preg_replace( '/[^\p{L}\p{N}\s]/u', '', $clean );
            return array_values( array_filter( preg_split( '/\s+/u', (string) $clean ) ) );
        };

        $src_words = $tokenize( $source_text );
        $tgt_words = $tokenize( $target_text );

        if ( count( $tgt_words ) < $n || count( $src_words ) < $n ) {
            return 0.0;
        }

        $build_ngrams = function( $words ) use ( $n ) {
            $grams = array();
            $count = count( $words );
            for ( $i = 0; $i <= $count - $n; $i++ ) {
                $grams[ implode( ' ', array_slice( $words, $i, $n ) ) ] = true;
            }
            return $grams;
        };

        $src_ngrams = $build_ngrams( $src_words );
        $tgt_ngrams = $build_ngrams( $tgt_words );

        $matches = 0;
        foreach ( $tgt_ngrams as $gram => $_ ) {
            if ( isset( $src_ngrams[ $gram ] ) ) {
                $matches++;
            }
        }

        return count( $tgt_ngrams ) > 0 ? (float) ( $matches / count( $tgt_ngrams ) ) : 0.0;
    }

    /**
     * Record processed URL into anti-duplication table
     *
     * @param  string $url
     * @param  string $title
     * @param  string $domain
     * @return int|false
     */
    public static function record_processed_url( string $url, string $title = '', string $domain = '' ) {
        global $wpdb;
        $table = Sergio_AI_DB::table( 'processed_urls' );
        return $wpdb->replace(
            $table,
            array(
                'url_hash'     => md5( $url ),
                'url'          => esc_url_raw( $url ),
                'title'        => sanitize_text_field( $title ),
                'domain'       => sanitize_text_field( $domain ),
                'processed_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );
    }
    /**
     * Fetch real-time trending repositories from GitHub.
     *
     * @param string $since 'daily', 'weekly', 'monthly'
     * @param string $language Programming language filter (e.g. 'python', 'javascript', '' for all)
     * @param int $limit Maximum number of repos to return (default 8)
     * @return array List of repositories with keys: name, full_name, url, desc, stars_today, language
     */
    public static function fetch_github_trending( string $since = 'daily', string $language = '', int $limit = 8 ): array {
        $url = 'https://github.com/trending';
        if ( ! empty( $language ) ) {
            $url .= '/' . rawurlencode( strtolower( $language ) );
        }
        $url .= '?since=' . rawurlencode( $since );

        $response = wp_safe_remote_get( $url, array(
            'timeout'    => 15,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers'    => array(
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ),
        ) );

        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return array();
        }

        $html = wp_remote_retrieve_body( $response );
        if ( empty( $html ) ) {
            return array();
        }

        $items = array();
        if ( preg_match_all( '/<article class="Box-row"[\s\S]*?<\/article>/i', $html, $articles ) ) {
            foreach ( $articles[0] as $block ) {
                if ( count( $items ) >= $limit ) {
                    break;
                }
                // Extract repository slug
                if ( ! preg_match( '/href="\/([a-zA-Z0-9_\-\.\/]+)"[^>]*class="Link"/i', $block, $repo_m ) ) {
                    continue;
                }
                $repo_slug = trim( $repo_m[1] );
                if ( str_contains( $repo_slug, '/' ) === false || str_contains( $repo_slug, '/stargazers' ) || str_contains( $repo_slug, '/network' ) ) {
                    continue;
                }

                // Extract description
                $desc = '';
                if ( preg_match( '/<p class="col-9[^\"]*"[^>]*>([\s\S]*?)<\/p>/i', $block, $desc_m ) ) {
                    $desc = trim( function_exists('wp_strip_all_tags') ? wp_strip_all_tags( $desc_m[1] ) : strip_tags( $desc_m[1] ) );
                }

                // Extract stars today
                $stars_today = '';
                if ( preg_match( '/(\d[\d,]*)\s+stars\s+today/i', $block, $stars_m ) ) {
                    $stars_today = trim( $stars_m[1] );
                }

                // Extract language
                $lang = 'Chưa xác định';
                if ( preg_match( '/itemprop="programmingLanguage">([^<]+)<\/span>/i', $block, $lang_m ) ) {
                    $lang = trim( $lang_m[1] );
                }

                $items[] = array(
                    'name'        => basename( $repo_slug ),
                    'full_name'   => $repo_slug,
                    'url'         => 'https://github.com/' . $repo_slug,
                    'description' => $desc,
                    'stars_today' => $stars_today,
                    'language'    => $lang,
                );
            }
        }

        return $items;
    }
}
