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
     * Get encryption secret key derived from WordPress salts
     *
     * @return string
     */
    private static function get_secret_key() {
        $salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : 'sergio-ai-studio-fallback-salt-2026';
        $key  = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : 'sergio-ai-secure-key-2026';
        return hash( 'sha256', $salt . $key, true );
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
     * Encrypt a plain text string
     *
     * @param string $plain_text Text to encrypt
     * @return string Base64 encoded encrypted string or empty on failure
     */
    public static function encrypt( $plain_text ) {
        if ( empty( $plain_text ) ) {
            return '';
        }

        // If already encrypted with our prefix, return as-is
        if ( 0 === strpos( $plain_text, 'sergio_enc:' ) ) {
            return $plain_text;
        }

        $key   = self::get_secret_key();
        $ivlen = openssl_cipher_iv_length( self::CIPHER );
        try {
            $iv = random_bytes( $ivlen );
        } catch ( Exception $e ) {
            $iv = openssl_random_pseudo_bytes( $ivlen );
        }

        $cipher = openssl_encrypt( $plain_text, self::CIPHER, $key, 0, $iv );

        if ( false === $cipher ) {
            return $plain_text; // Fallback if openssl fails
        }

        return 'sergio_enc:' . base64_encode( $iv . ':::' . $cipher );
    }

    /**
     * Decrypt an encrypted string
     *
     * @param string $encrypted_text Encrypted text
     * @return string Decrypted plain text
     */
    public static function decrypt( $encrypted_text ) {
        if ( empty( $encrypted_text ) ) {
            return '';
        }

        if ( 0 !== strpos( $encrypted_text, 'sergio_enc:' ) ) {
            return $encrypted_text; // Not encrypted with our prefix, assume legacy plain text
        }

        $payload = substr( $encrypted_text, 11 );
        $decoded = base64_decode( $payload );

        if ( false === $decoded || false === strpos( $decoded, ':::' ) ) {
            return '';
        }

        list( $iv, $ciphertext ) = explode( ':::', $decoded, 2 );
        $key = self::get_secret_key();

        $plain = openssl_decrypt( $ciphertext, self::CIPHER, $key, 0, $iv );
        return ( false !== $plain ) ? $plain : '';
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

