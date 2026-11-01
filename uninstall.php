<?php
/**
 * Uninstall Script — Sergio AI Studio
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Removes all plugin tables, options, transients, and scheduled hooks.
 *
 * @package Sergio_AI_Studio
 */

// Security: only run during a proper uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1. Drop all custom tables.
$tables = array(
    $wpdb->prefix . 'sergio_ai_fact_packs',
    $wpdb->prefix . 'sergio_ai_topics_queue',
    $wpdb->prefix . 'sergio_ai_logs',
    $wpdb->prefix . 'sergio_ai_processed_urls',
    $wpdb->prefix . 'sergio_ai_recipes',
);

foreach ( $tables as $table ) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names use $wpdb->prefix, safe.
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

// 2. Delete plugin options.
delete_option( 'sergio_ai_studio_options' );
delete_option( 'sergio_ai_options' );
delete_option( 'sergio_ai_db_version' );
delete_option( 'sergio_ai_studio_db_version' );
delete_option( 'sergio_ai_site_salt' );

// 3. Clear scheduled cron hooks.
wp_clear_scheduled_hook( 'sergio_ai_studio_cron_event' );
wp_clear_scheduled_hook( 'sergio_ai_cron_hook' );

// 4. Delete transients.
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_sergio_ai_%'
        OR option_name LIKE '_transient_timeout_sergio_ai_%'"
);
