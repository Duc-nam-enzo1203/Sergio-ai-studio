<?php
/**
 * Database Schema & Migration Manager
 *
 * Handles creation and upgrades of dedicated plugin tables:
 * - Fact Packs
 * - Detailed Execution Logs
 * - Topics Queue
 * - Processed URLs (anti-duplication)
 * - Content Recipes
 *
 * @package Sergio_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sergio_AI_DB {

    const DB_VERSION = '2.0.0';

    /**
     * Table names helper
     */
    public static function table( $name ) {
        global $wpdb;
        return $wpdb->prefix . 'sergio_ai_' . $name;
    }

    /**
     * Run migrations using dbDelta
     */
    public static function migrate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // 1. Table: Fact Packs (Real data grounding packs)
        $table_fact_packs = self::table( 'fact_packs' );
        $sql_fact_packs = "CREATE TABLE {$table_fact_packs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            topic varchar(255) NOT NULL,
            focus_intent text DEFAULT NULL,
            language varchar(10) NOT NULL DEFAULT 'vi',
            sources_count int(11) NOT NULL DEFAULT 0,
            sources_json longtext DEFAULT NULL,
            facts_json longtext DEFAULT NULL,
            entities_json longtext DEFAULT NULL,
            status varchar(30) NOT NULL DEFAULT 'verified',
            post_id bigint(20) unsigned DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY topic (topic(191)),
            KEY status (status)
        ) {$charset_collate};";
        dbDelta( $sql_fact_packs );

        // 2. Table: Detailed Execution Logs (Real-time tracking of every stage)
        $table_logs = self::table( 'logs' );
        $sql_logs = "CREATE TABLE {$table_logs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            run_id varchar(64) NOT NULL,
            stage varchar(50) NOT NULL,
            level varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            details_json longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY run_id (run_id),
            KEY stage (stage),
            KEY level (level),
            KEY created_at (created_at)
        ) {$charset_collate};";
        dbDelta( $sql_logs );

        // 3. Table: Topics Queue (Structured Queue with status and priorities)
        $table_topics = self::table( 'topics_queue' );
        $sql_topics = "CREATE TABLE {$table_topics} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            topic varchar(255) NOT NULL,
            recipe_id bigint(20) unsigned DEFAULT 0,
            category_id bigint(20) unsigned DEFAULT 0,
            language varchar(10) NOT NULL DEFAULT 'vi',
            status varchar(30) NOT NULL DEFAULT 'pending',
            priority int(11) NOT NULL DEFAULT 10,
            attempts int(11) NOT NULL DEFAULT 0,
            last_error text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY priority (priority)
        ) {$charset_collate};";
        dbDelta( $sql_topics );

        // 4. Table: Processed URLs (Anti-duplication cache)
        $table_urls = self::table( 'processed_urls' );
        $sql_urls = "CREATE TABLE {$table_urls} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url_hash char(32) NOT NULL,
            url text NOT NULL,
            title varchar(255) DEFAULT '',
            domain varchar(100) DEFAULT '',
            processed_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY url_hash (url_hash),
            KEY domain (domain)
        ) {$charset_collate};";
        dbDelta( $sql_urls );

        // 5. Table: Content Recipes (Formula recipes for Phase 2)
        $table_recipes = self::table( 'recipes' );
        $sql_recipes = "CREATE TABLE {$table_recipes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            mode varchar(30) NOT NULL DEFAULT 'curate_combined',
            post_type_recipe varchar(50) NOT NULL DEFAULT 'daily_roundup',
            category_id bigint(20) unsigned DEFAULT 0,
            posts_per_run int(11) NOT NULL DEFAULT 1,
            target_status varchar(20) NOT NULL DEFAULT 'draft',
            seo_threshold int(11) NOT NULL DEFAULT 85,
            schedule_hours varchar(100) DEFAULT '08:00,14:00',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            settings_json longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY is_active (is_active)
        ) {$charset_collate};";
        dbDelta( $sql_recipes );

        update_option( 'sergio_ai_db_version', self::DB_VERSION );
    }

    /**
     * Check if migration is needed on admin_init
     */
    public static function check_migration() {
        if ( get_option( 'sergio_ai_db_version' ) !== self::DB_VERSION ) {
            self::migrate();
        }
    }
}

