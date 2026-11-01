<?php
/**
 * Plugin Name: Sergio AI Studio
 * Plugin URI:  https://sergiothemes.com
 * Description: Bộ máy AI nghiên cứu dữ kiện thật, crawl & viết lại bài, tối ưu Rank Math SEO và tự động hóa lịch đăng bài.
 * Version:     2.1.0
 * Author:      Sergio Official Team
 * Author URI:  https://sergiothemes.com
 * License:     GPL-2.0+
 * Text Domain: sergio-ai-studio
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SERGIO_AI_STUDIO_VERSION', '2.1.0' );
define( 'SERGIO_AI_STUDIO_DIR', plugin_dir_path( __FILE__ ) );
define( 'SERGIO_AI_STUDIO_URL', plugin_dir_url( __FILE__ ) );

// 1. Require Core Classes
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-security.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-db.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-logger.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-gemini-client.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-fact-pack-engine.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-topic-analyzer.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-article-writer.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-rankmath-bridge.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-scheduler.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-web-crawler.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-content-rewriter.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-seo-audit.php';
require_once SERGIO_AI_STUDIO_DIR . 'includes/class-image-service.php';

if ( is_admin() ) {
	require_once SERGIO_AI_STUDIO_DIR . 'includes/class-admin.php';
}

/**
 * Activation Hook - Run DB Migrations
 */
register_activation_hook( __FILE__, array( 'Sergio_AI_DB', 'migrate' ) );

/**
 * Deactivation Hook - Clear Scheduled Cron
 */
function sergio_ai_studio_deactivate() {
	$scheduler = new Sergio_AI_Scheduler();
	$scheduler->clear_schedule();
}
register_deactivation_hook( __FILE__, 'sergio_ai_studio_deactivate' );

/**
 * Initialize Plugin
 */
function sergio_ai_studio_init() {
	// Load plugin textdomain for i18n
	load_plugin_textdomain( 'sergio-ai-studio', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Initialize Scheduler
	new Sergio_AI_Scheduler();

	// Check migrations if needed
	if ( is_admin() ) {
		Sergio_AI_DB::check_migration();
		new Sergio_AI_Admin();
	}
}
add_action( 'plugins_loaded', 'sergio_ai_studio_init' );
