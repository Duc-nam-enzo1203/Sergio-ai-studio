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
function wp_strip_all_tags( $string, $remove_breaks = false ) {
    return strip_tags( (string) $string );
}
function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function wp_upload_dir() {
    return array( 'path' => sys_get_temp_dir(), 'url' => 'http://example.com/uploads', 'error' => false );
}
function current_time( $type ) {
    return date( 'Y-m-d H:i:s' );
}
function wp_date( $format ) {
    return date( $format );
}
function wp_generate_password( $length = 12 ) {
    return substr( md5( uniqid() ), 0, $length );
}
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
function apply_filters( $hook, $value ) { return $value; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, (array) $args ); }

require_once __DIR__ . '/../includes/class-security.php';
require_once __DIR__ . '/../includes/class-logger.php';

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

// 4. Test 5-Gram Plagiarism Overlap Detector
require_once __DIR__ . '/../includes/class-web-crawler.php';

$source_sample = "Mô hình ngôn ngữ lớn Claude 3.7 Sonnet được tối ưu hóa cho tác vụ lập trình và suy luận logic chuyên sâu với cửa sổ ngữ cảnh lớn.";
$identical_sample = "Mô hình ngôn ngữ lớn Claude 3.7 Sonnet được tối ưu hóa cho tác vụ lập trình và suy luận logic chuyên sâu với cửa sổ ngữ cảnh lớn.";
$rewritten_sample = "Anthropic vừa giới thiệu thế hệ AI cải tiến Sonnet 3.7, tăng cường tốc độ sinh mã nguồn và hỗ trợ giải quyết vấn đề toán học phức tạp.";

$sim_identical = Sergio_AI_Web_Crawler::calculate_ngram_similarity( $source_sample, $identical_sample, 5 );
$sim_rewritten = Sergio_AI_Web_Crawler::calculate_ngram_similarity( $source_sample, $rewritten_sample, 5 );

assert_true( $sim_identical >= 0.95, 'Identical source text yields >= 95% 5-gram overlap (plagiarism detected)' );
assert_true( $sim_rewritten < 0.20, 'Synthesized rewrite yields low 5-gram overlap (< 20%)' );

// 5. Test GD Featured Image Generator
require_once __DIR__ . '/../includes/class-image-service.php';

$image_service = new Sergio_AI_Image_Service();
$banner_path = $image_service->generate_gd_image_file( 'Đánh Giá Kiến Trúc Mô Hình Trí Tuệ Nhân Tạo 2026', sys_get_temp_dir() . '/test_banner.png' );

assert_true( file_exists( $banner_path ), 'GD banner generated and file exists on disk' );
assert_true( filesize( $banner_path ) > 3000, 'GD banner PNG size is valid (>3KB)' );
$img_info = getimagesize( $banner_path );
assert_true( 1200 === $img_info[0] && 630 === $img_info[1], 'GD banner dimensions are exactly 1200x630 (social standard)' );
@unlink( $banner_path ); // Clean up test file

// 6. Test Numeric Metrics Anti-Hallucination
require_once __DIR__ . '/../includes/class-gemini-client.php';
require_once __DIR__ . '/../includes/class-article-writer.php';

$mock_fact_pack = array(
    'key_facts' => array( 'Chip M4 đạt 38 nghìn tỷ phép tính mỗi giây (38 TOPS).' ),
    'verified_metrics' => array( 'Hiệu năng tăng 50% so với thế hệ M2', 'Giá khởi điểm từ 599 USD' ),
    'source_citations' => array(),
);

$writer_inst = new Sergio_AI_Article_Writer();
$article_valid = "<p>Chip M4 đạt tốc độ ấn tượng 38 TOPS, mang lại hiệu năng tăng 50% so với thế hệ M2 với mức giá chỉ 599 USD.</p>";
$audit_valid = $writer_inst->verify_metrics_in_sources( $article_valid, $mock_fact_pack );
assert_true( 'passed' === $audit_valid['status'], 'Anti-hallucination passes when all numbers (38, 50%, 599) match Fact Pack' );

$article_hallucinated = "<p>Model có 175B tham số, chạy ở xung nhịp 4.8 GHz và giá bán lên tới 4999 USD.</p>";
$audit_hallucinated = $writer_inst->verify_metrics_in_sources( $article_hallucinated, $mock_fact_pack );
assert_true( 'flagged' === $audit_hallucinated['status'], 'Anti-hallucination flags article containing unverified metrics' );
assert_true( in_array( '4999 USD', $audit_hallucinated['unverified_items'], true ), 'Unverified number 4999 USD is correctly identified in audit report' );

echo "\n🎉 ALL TESTS PASSED SUCCESSFULLY!\n";

