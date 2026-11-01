<?php
/**
 * Security & Encryption Engine
 *
 * Provides AES-256-CBC encryption for sensitive credentials (API keys)
 * utilizing WordPress salts.
 *
 * @package Sergio_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sergio_AI_Security {

    /**
     * Cipher method
     */
    private const CIPHER = 'aes-256-cbc';

    /**
     * Get or create persistent secret salt for encryption
     *
     * @return string 32-byte binary secret
     */
    private static function get_master_secret() {
        $salt = function_exists( 'wp_salt' ) ? wp_salt( 'auth' ) . wp_salt( 'secure_auth' ) : '';
        if ( empty( $salt ) ) {
            $salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : '';
            $salt .= defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '';
        }

        // If WP salts are not set or generic, combine with a site-persistent unique random token
        $site_salt = get_option( 'sergio_ai_site_salt' );
        if ( empty( $site_salt ) ) {
            try {
                $site_salt = bin2hex( random_bytes( 32 ) );
            } catch ( Exception $e ) {
                $site_salt = wp_generate_password( 64, true, true );
            }
            update_option( 'sergio_ai_site_salt', $site_salt, false );
        }

        return hash( 'sha256', $salt . '::' . $site_salt, true );
    }

    /**
     * Check if Gemini API Key is defined via wp-config.php constant
     *
     * @return bool
     */
    public static function is_key_defined_in_config() {
        return defined( 'SERGIO_AI_GEMINI_API_KEY' ) && ! empty( SERGIO_AI_GEMINI_API_KEY );
    }

    /**
     * Encrypt a plain text string using Encrypt-then-MAC (AES-256-CBC + HMAC-SHA256)
     *
     * @param string $plain_text Text to encrypt
     * @return string Encrypted string or empty string on failure (never plaintext)
     */
    public static function encrypt( $plain_text ) {
        if ( empty( $plain_text ) ) {
            return '';
        }

        // If already encrypted with v2 or legacy prefix, return as-is
        if ( 0 === strpos( $plain_text, 'sergio_v2:' ) || 0 === strpos( $plain_text, 'sergio_enc:' ) ) {
            return $plain_text;
        }

        $master_secret = self::get_master_secret();
        $enc_key       = hash_hmac( 'sha256', 'sergio_encryption_key', $master_secret, true );
        $mac_key       = hash_hmac( 'sha256', 'sergio_mac_key', $master_secret, true );

        $ivlen = openssl_cipher_iv_length( self::CIPHER );
        try {
            $iv = random_bytes( $ivlen );
        } catch ( Exception $e ) {
            $iv = openssl_random_pseudo_bytes( $ivlen );
        }

        $cipher = openssl_encrypt( $plain_text, self::CIPHER, $enc_key, OPENSSL_RAW_DATA, $iv );

        if ( false === $cipher ) {
            return ''; // Fail-closed: Never leak plaintext on cryptographic failure
        }

        $mac = hash_hmac( 'sha256', $iv . $cipher, $mac_key, true );

        return 'sergio_v2:' . base64_encode( $iv ) . '.' . base64_encode( $cipher ) . '.' . base64_encode( $mac );
    }

    /**
     * Decrypt an encrypted string (supports v2 Encrypt-then-MAC and legacy v1)
     *
     * @param string $encrypted_text Encrypted text
     * @return string Decrypted plain text or empty on failure
     */
    public static function decrypt( $encrypted_text ) {
        if ( empty( $encrypted_text ) ) {
            return '';
        }

        // Handle modern v2 format (Authenticated Encrypt-then-MAC)
        if ( 0 === strpos( $encrypted_text, 'sergio_v2:' ) ) {
            $payload = substr( $encrypted_text, 10 );
            $parts   = explode( '.', $payload );
            if ( 3 !== count( $parts ) ) {
                return '';
            }

            $iv     = base64_decode( $parts[0] );
            $cipher = base64_decode( $parts[1] );
            $mac    = base64_decode( $parts[2] );

            if ( false === $iv || false === $cipher || false === $mac ) {
                return '';
            }

            $master_secret  = self::get_master_secret();
            $enc_key        = hash_hmac( 'sha256', 'sergio_encryption_key', $master_secret, true );
            $mac_key        = hash_hmac( 'sha256', 'sergio_mac_key', $master_secret, true );
            $calculated_mac = hash_hmac( 'sha256', $iv . $cipher, $mac_key, true );

            if ( ! hash_equals( $calculated_mac, $mac ) ) {
                return ''; // Integrity verification failed
            }

            $plain = openssl_decrypt( $cipher, self::CIPHER, $enc_key, OPENSSL_RAW_DATA, $iv );
            return ( false !== $plain ) ? $plain : '';
        }

        // Backward compatibility: Handle legacy v1 format
        if ( 0 === strpos( $encrypted_text, 'sergio_enc:' ) ) {
            $payload = substr( $encrypted_text, 11 );
            $decoded = base64_decode( $payload );

            if ( false === $decoded || false === strpos( $decoded, ':::' ) ) {
                return '';
            }

            list( $iv, $ciphertext ) = explode( ':::', $decoded, 2 );
            $salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : 'sergio-ai-studio-fallback-salt-2026';
            $key  = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : 'sergio-ai-secure-key-2026';
            $legacy_key = hash( 'sha256', $salt . $key, true );

            $plain = openssl_decrypt( $ciphertext, self::CIPHER, $legacy_key, 0, $iv );
            return ( false !== $plain ) ? $plain : '';
        }

        // Raw text (legacy unencrypted setting)
        return $encrypted_text;
    }

    /**
     * Mask API key for secure UI presentation (e.g. AIzaSy...9xYz)
     *
     * @param string $api_key API key (plain or encrypted)
     * @return string Masked key
     */
    public static function mask_key( $api_key ) {
        if ( self::is_key_defined_in_config() ) {
            $plain = SERGIO_AI_GEMINI_API_KEY;
            $len   = strlen( $plain );
            if ( $len <= 8 ) {
                return '•••••••• (wp-config.php)';
            }
            return substr( $plain, 0, 6 ) . '••••••••••••••••' . substr( $plain, -4 ) . ' (wp-config.php)';
        }

        $plain = self::decrypt( $api_key );
        $len   = strlen( $plain );
        if ( $len <= 8 ) {
            return empty( $plain ) ? '' : '••••••••';
        }
        $start = substr( $plain, 0, 6 );
        $end   = substr( $plain, -4 );
        return $start . '••••••••••••••••' . $end;
    }

    /**
     * Sanitize post HTML content while preserving rich formatting, tables, lists, and embeds
     *
     * @param string $html Raw HTML content
     * @return string Sanitized safe HTML
     */
    public static function sanitize_article_html( $html ) {
        if ( empty( $html ) ) {
            return '';
        }

        // Use WordPress core wp_kses_post which permits safe styling, structural tags, and media
        return wp_kses_post( $html );
    }
}

