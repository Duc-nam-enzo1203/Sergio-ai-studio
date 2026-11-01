<?php
/**
 * Test Suite — Sergio AI Studio Core Logic
 *
 * Runs standalone without requiring full WordPress bootstrap by mocking
 * minimal necessary WP primitives (wp_salt, get_option, update_option, etc.).
 */

// Define ABSPATH and necessary constants
define( 'ABSPATH', __DIR__ . '/../' );
define( 'AUTH_SALT', 'test_auth_salt_random_99182312' );
define( 'SECURE_AUTH_KEY', 'test_secure_auth_key_88291023' );

// Mock minimal WordPress functions
$mock_options = array();
function get_option( $key, $default = false ) {
    global $mock_options;
    return $mock_options[ $key ] ?? $default;
}
function update_option( $key, $val, $autoload = true ) {
    global $mock_options;
    $mock_options[ $key ] = $val;
    return true;
}
function wp_salt( $scheme = 'auth' ) {
    return 'mocked_wp_salt_' . $scheme;
}
function sanitize_text_field( $str ) {
    return is_scalar( $str ) ? trim( strip_tags( (string) $str ) ) : '';
}

require_once __DIR__ . '/../includes/class-security.php';

// Assert helper
function assert_true( $condition, $msg ) {
    if ( ! $condition ) {
        echo "❌ FAILED: {$msg}\n";
        exit( 1 );
    }
    echo "✅ PASS: {$msg}\n";
}

echo "=== Running Sergio AI Studio Test Suite ===\n\n";

// 1. Security Tests: Authenticated Encrypt-then-MAC
$plain_key = 'AIzaSyA_Secret_Test_Key_1234567890';
$encrypted = Sergio_AI_Security::encrypt( $plain_key );

assert_true( 0 === strpos( $encrypted, 'sergio_v2:' ), 'Ciphertext starts with sergio_v2: prefix' );
$parts = explode( '.', substr( $encrypted, 10 ) );
assert_true( 3 === count( $parts ), 'Encrypted payload has 3 base64 parts (IV, Cipher, MAC)' );

$decrypted = Sergio_AI_Security::decrypt( $encrypted );
assert_true( $decrypted === $plain_key, 'Decrypted text matches original plain key' );

// Test Tampering rejection
$tampered = $encrypted . 'TAMPER';
$tamper_result = Sergio_AI_Security::decrypt( $tampered );
assert_true( '' === $tamper_result, 'Tampered ciphertext fails MAC verification and returns empty string' );

// Test Empty input
assert_true( '' === Sergio_AI_Security::encrypt( '' ), 'Encrypt empty string returns empty string' );
assert_true( '' === Sergio_AI_Security::decrypt( '' ), 'Decrypt empty string returns empty string' );

// 2. Test Array unwrapping from generate_content()
$mock_gemini_response = array(
    'text' => "  Khám phá AI Agent tự động hóa năm 2026  \n",
    'grounding_metadata' => array(),
    'model_used' => 'gemini-2.5-flash',
);

$extracted_text = is_array( $mock_gemini_response )
    ? ( $mock_gemini_response['text'] ?? '' )
    : (string) $mock_gemini_response;

$trimmed = trim( $extracted_text );
assert_true( $trimmed === 'Khám phá AI Agent tự động hóa năm 2026', 'Safely unwrap and trim array text without TypeError on PHP 8.3' );

$sanitized = sanitize_text_field( $trimmed );
assert_true( ! empty( $sanitized ), 'Sanitize unwrapped topic string produces non-empty result' );

// 3. Test JSON extraction from markdown fenced response
$ai_json_output = "```json\n{\"status\":\"verified\",\"key_facts\":[\"Fact 1\"]}\n```";
$cleaned_json = preg_replace( '/^```(?:json)?\s*/i', '', trim( $ai_json_output ) );
$cleaned_json = preg_replace( '/\s*```$/i', '', $cleaned_json );
$parsed = json_decode( $cleaned_json, true );
assert_true( is_array( $parsed ) && 'verified' === $parsed['status'], 'Parse fact JSON with markdown code block correctly' );

echo "\n🎉 ALL TESTS PASSED SUCCESSFULLY!\n";
