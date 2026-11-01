<?php
/**
 * Detailed Execution Logger Engine
 *
 * Persists every single stage of research, generation, verification, and SEO
 * into the dedicated database table.
 *
 * @package Sergio_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sergio_AI_Logger {

    /**
     * Active Run ID
     *
     * @var string
     */
    private $run_id;

    /**
     * Constructor
     *
     * @param string $run_id Optional specific run ID
     */
    public function __construct( $run_id = '' ) {
        $this->run_id = ! empty( $run_id ) ? sanitize_text_field( $run_id ) : self::generate_run_id();
    }

    /**
     * Generate unique run ID
     */
    public static function generate_run_id() {
        return 'run_' . gmdate( 'Ymd_His' ) . '_' . wp_generate_password( 6, false, false );
    }

    /**
     * Get current run ID
     */
    public function get_run_id() {
        return $this->run_id;
    }

    /**
     * Log an event
     *
     * @param string $stage   Stage name (e.g. 'fact_collection', 'outline', 'writing', 'verify', 'seo', 'cron')
     * @param string $level   Level ('info', 'warning', 'error')
     * @param string $message Descriptive human-readable message
     * @param mixed  $details Optional array or object to store as JSON
     */
    public function log( $stage, $level, $message, $details = null ) {
        global $wpdb;

        $table = Sergio_AI_DB::table( 'logs' );

        $details_json = null;
        if ( null !== $details ) {
            $details_json = wp_json_encode( $details, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        }

        $wpdb->insert(
            $table,
            array(
                'run_id'       => $this->run_id,
                'stage'        => sanitize_text_field( $stage ),
                'level'        => in_array( $level, array( 'info', 'warning', 'error' ), true ) ? $level : 'info',
                'message'      => sanitize_textarea_field( $message ),
                'details_json' => $details_json,
                'created_at'   => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    public function info( $stage, $message, $details = null ) {
        $this->log( $stage, 'info', $message, $details );
    }

    public function warning( $stage, $message, $details = null ) {
        $this->log( $stage, 'warning', $message, $details );
    }

    public function error( $stage, $message, $details = null ) {
        $this->log( $stage, 'error', $message, $details );
    }

    /**
     * Retrieve logs for Admin UI
     *
     * @param int    $limit  Items per page
     * @param int    $offset Offset
     * @param string $level  Filter level
     * @param string $stage  Filter stage
     * @return array
     */
    public static function get_logs( $limit = 50, $offset = 0, $level = '', $stage = '' ) {
        global $wpdb;
        $table = Sergio_AI_DB::table( 'logs' );

        $where = array( '1=1' );
        $params = array();

        if ( ! empty( $level ) ) {
            $where[]  = 'level = %s';
            $params[] = $level;
        }

        if ( ! empty( $stage ) ) {
            $where[]  = 'stage = %s';
            $params[] = $stage;
        }

        $where_sql = implode( ' AND ', $where );
        $sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
        $params[] = (int) $limit;
        $params[] = (int) $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
    }

    /**
     * Clear old logs (> 30 days)
     */
    public static function purge_old_logs() {
        global $wpdb;
        $table = Sergio_AI_DB::table( 'logs' );
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) );
    }
}

