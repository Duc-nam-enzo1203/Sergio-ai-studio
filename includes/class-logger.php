<?php
/**
 * Detailed Execution Logger Engine
 *
 * Persists every single stage of research, generation, verification, and SEO
 * into the dedicated database table with analytics, session tracking,
 * and diagnostic intelligence.
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
		// Filter out info level according to user preference ("info không cần, chỉ cần ghi lại cảnh báo và lỗi")
		if ( 'info' === $level ) {
			return;
		}

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
	 * @param string $search Optional search query
	 * @return array
	 */
	public static function get_logs( $limit = 50, $offset = 0, $level = '', $stage = '', $search = '' ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'logs' );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $level ) ) {
			$where[]  = 'level = %s';
			$params[] = $level;
		} else {
			// Default to only warning and error as requested
			$where[] = "level IN ('warning', 'error')";
		}

		if ( ! empty( $stage ) ) {
			$where[]  = 'stage = %s';
			$params[] = $stage;
		}

		if ( ! empty( $search ) ) {
			$where[]  = '(message LIKE %s OR run_id LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
		$params[]  = (int) $limit;
		$params[]  = (int) $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
	}

	/**
	 * Count total logs matching filters
	 *
	 * @param string $level
	 * @param string $stage
	 * @param string $search
	 * @return int
	 */
	public static function count_logs( $level = '', $stage = '', $search = '' ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'logs' );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $level ) ) {
			$where[]  = 'level = %s';
			$params[] = $level;
		} else {
			// Default to only warning and error as requested
			$where[] = "level IN ('warning', 'error')";
		}

		if ( ! empty( $stage ) ) {
			$where[]  = 'stage = %s';
			$params[] = $stage;
		}

		if ( ! empty( $search ) ) {
			$where[]  = '(message LIKE %s OR run_id LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

		if ( ! empty( $params ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Retrieve comprehensive audit statistics
	 *
	 * @return array
	 */
	public static function get_stats() {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'logs' );

		$total_logs = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( 0 === $total_logs ) {
			return array(
				'total_logs'       => 0,
				'total_runs'       => 0,
				'error_count'      => 0,
				'warning_count'    => 0,
				'info_count'       => 0,
				'articles_created' => 0,
				'success_rate'     => 100,
			);
		}

		$error_count   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE level = 'error'" );
		$warning_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE level = 'warning'" );
		$info_count    = 0;
		$total_logs    = $error_count + $warning_count;

		$failed_runs   = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT run_id) FROM {$table} WHERE level = 'error'" );
		$success_runs  = max( 0, $total_runs - $failed_runs );
		$success_rate  = $total_runs > 0 ? (int) round( ( $success_runs / $total_runs ) * 100 ) : 100;

		$articles_created = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE (message LIKE '%Tạo bài thành công%' OR message LIKE '%Tạo bài tự động thành công%' OR message LIKE '%Hoàn tất tạo bài%' OR stage = 'post_saved') AND level != 'error'"
		);

		return array(
			'total_logs'       => $total_logs,
			'total_runs'       => $total_runs,
			'error_count'      => $error_count,
			'warning_count'    => $warning_count,
			'info_count'       => $info_count,
			'articles_created' => $articles_created,
			'success_rate'     => $success_rate,
		);
	}

	/**
	 * Retrieve grouped execution sessions
	 *
	 * @param int    $limit  Number of sessions
	 * @param int    $offset Offset
	 * @param string $search Search query
	 * @param string $level  Filter level
	 * @param string $module Filter module
	 * @return array
	 */
	public static function get_sessions( $limit = 15, $offset = 0, $search = '', $level = '', $module = '' ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'logs' );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $level ) ) {
			$where[]  = 'level = %s';
			$params[] = $level;
		} else {
			// Default to only warning and error as requested
			$where[] = "level IN ('warning', 'error')";
		}

		if ( ! empty( $module ) ) {
			$stages = self::get_module_stages( $module );
			if ( ! empty( $stages ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $stages ), '%s' ) );
				$where[]      = "stage IN ({$placeholders})";
				foreach ( $stages as $s ) {
					$params[] = $s;
				}
			}
		}

		if ( ! empty( $search ) ) {
			$where[]  = '(message LIKE %s OR run_id LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$sql_runs  = "SELECT run_id, MAX(id) as max_id FROM {$table} WHERE {$where_sql} GROUP BY run_id ORDER BY max_id DESC LIMIT %d OFFSET %d";
		$params[]  = (int) $limit;
		$params[]  = (int) $offset;

		$run_rows = $wpdb->get_results( $wpdb->prepare( $sql_runs, $params ), ARRAY_A );
		if ( empty( $run_rows ) ) {
			return array();
		}

		$run_ids = wp_list_pluck( $run_rows, 'run_id' );
		$in_runs = implode( "','", array_map( 'esc_sql', $run_ids ) );

		$logs = $wpdb->get_results(
			"SELECT * FROM {$table} WHERE run_id IN ('{$in_runs}') ORDER BY id ASC",
			ARRAY_A
		);

		// Group logs by run_id
		$grouped = array();
		foreach ( $logs as $l ) {
			$grouped[ $l['run_id'] ][] = $l;
		}

		$sessions = array();
		foreach ( $run_ids as $r_id ) {
			if ( empty( $grouped[ $r_id ] ) ) {
				continue;
			}
			$session_logs = $grouped[ $r_id ];
			$sessions[]   = self::build_session_object( $r_id, $session_logs );
		}

		return $sessions;
	}

	/**
	 * Build session details object from a list of logs
	 *
	 * @param string $run_id
	 * @param array  $logs
	 * @return array
	 */
	private static function build_session_object( $run_id, $logs ) {
		$first_log = reset( $logs );
		$last_log  = end( $logs );

		$has_error   = false;
		$has_warning = false;
		$error_msg   = '';
		$error_meta  = null;
		$title       = '';
		$post_id     = 0;

		$stages_seen = array();

		foreach ( $logs as $l ) {
			$stages_seen[] = $l['stage'];

			if ( 'error' === $l['level'] ) {
				$has_error = true;
				if ( empty( $error_msg ) ) {
					$error_msg  = $l['message'];
					$error_meta = ! empty( $l['details_json'] ) ? json_decode( $l['details_json'], true ) : null;
				}
			} elseif ( 'warning' === $l['level'] ) {
				$has_warning = true;
			}

			// Extract post ID
			if ( ! $post_id ) {
				if ( preg_match( '/(?:ID\s*#?|bài viết\s*#?)\s*(\d+)/ui', $l['message'], $pm ) ) {
					$candidate_id = (int) $pm[1];
					if ( $candidate_id > 0 && get_post_status( $candidate_id ) ) {
						$post_id = $candidate_id;
					}
				}
			}

			// Extract title
			if ( empty( $title ) ) {
				if ( preg_match( '/chủ đề\s*["\']([^"\']+)["\']/ui', $l['message'], $tm ) ) {
					$title = $tm[1];
				} elseif ( preg_match( '/nghiên cứu chủ đề:\s*([^\.]+)/ui', $l['message'], $tm ) ) {
					$title = trim( $tm[1] );
				}
			}
		}

		if ( empty( $title ) ) {
			$title = wp_trim_words( $first_log['message'], 10, '...' );
		}

		// Calculate status
		$status = 'success';
		if ( $has_error ) {
			$status = 'failed';
		} elseif ( $has_warning ) {
			$status = 'warning';
		} else {
			// Check if recent running
			$first_time = strtotime( $first_log['created_at'] );
			$is_fresh   = ( time() - $first_time ) < 180;
			if ( $is_fresh && ! in_array( 'cron_completed', $stages_seen, true ) && ! in_array( 'post_saved', $stages_seen, true ) && ! $post_id ) {
				$status = 'running';
			}
		}

		// Calculate duration
		$start_t = strtotime( $first_log['created_at'] );
		$end_t   = strtotime( $last_log['created_at'] );
		$dur_sec = max( 1, $end_t - $start_t );

		// Post info
		$post_info = null;
		if ( $post_id ) {
			$post_obj = get_post( $post_id );
			if ( $post_obj ) {
				$post_info = array(
					'id'        => $post_id,
					'title'     => $post_obj->post_title,
					'status'    => $post_obj->post_status,
					'edit_link' => get_edit_post_link( $post_id, 'raw' ),
					'view_link' => get_permalink( $post_id ),
				);
			}
		}

		// Diagnostic
		$diagnosis = null;
		if ( 'failed' === $status && ! empty( $error_msg ) ) {
			$diagnosis = self::diagnose_error( $error_msg, $error_meta );
		}

		return array(
			'run_id'           => $run_id,
			'title'            => $title,
			'status'           => $status,
			'created_at'       => $first_log['created_at'],
			'finished_at'      => $last_log['created_at'],
			'duration_seconds' => $dur_sec,
			'logs_count'       => count( $logs ),
			'post_info'        => $post_info,
			'diagnosis'        => $diagnosis,
			'logs'             => $logs,
		);
	}

	/**
	 * Intelligent error diagnosis
	 *
	 * @param string $message
	 * @param mixed  $details
	 * @return array
	 */
	public static function diagnose_error( $message, $details = null ) {
		$msg_lower   = mb_strtolower( (string) $message );
		$details_str = is_array( $details ) ? wp_json_encode( $details ) : (string) $details;
		$combined    = $msg_lower . ' ' . mb_strtolower( $details_str );

		if ( strpos( $combined, '429' ) !== false || strpos( $combined, 'quota' ) !== false || strpos( $combined, 'rate-limit' ) !== false ) {
			return array(
				'type'        => 'quota_exceeded',
				'icon'        => 'dashicons-warning',
				'title'       => __( 'Cạn kiệt Quota Gemini API (Mã lỗi 429)', 'sergio-ai-studio' ),
				'description' => __( 'Tài khoản hoặc API Key của bạn đã vượt quá giới hạn request/phút hoặc gói miễn phí đã hết lượt quota hôm nay.', 'sergio-ai-studio' ),
				'suggestion'  => __( 'Chuyển sang model nhẹ hơn (như gemini-2.0-flash) hoặc nhập API Key phụ trợ khác trong tab "Cài Đặt Chung".', 'sergio-ai-studio' ),
				'action_tab'  => 'tab-settings',
			);
		}

		if ( strpos( $combined, 'hết thời gian chờ' ) !== false || strpos( $combined, 'timeout' ) !== false || strpos( $combined, '120' ) !== false || strpos( $combined, 'timed out' ) !== false ) {
			return array(
				'type'        => 'timeout',
				'icon'        => 'dashicons-clock',
				'title'       => __( 'Hết thời gian chờ kết nối (API Timeout 120s)', 'sergio-ai-studio' ),
				'description' => __( 'Máy chủ Gemini hoặc mạng phản hồi quá chậm (> 120 giây) trong quá trình nghiên cứu hoặc tạo bài.', 'sergio-ai-studio' ),
				'suggestion'  => __( 'Khuyến nghị chọn model Flash tốc độ cao hoặc chọn độ dài bài viết tiêu chuẩn thay vì chuyên sâu dài.', 'sergio-ai-studio' ),
				'action_tab'  => 'tab-generation',
			);
		}

		if ( strpos( $combined, 'dữ kiện không đủ' ) !== false || strpos( $combined, 'cổng kiểm chứng' ) !== false || strpos( $combined, 'độ tin cậy' ) !== false || strpos( $combined, 'grounding' ) !== false ) {
			return array(
				'type'        => 'grounding_rejected',
				'icon'        => 'dashicons-search',
				'title'       => __( 'Cổng kiểm chứng dữ kiện từ chối (Chống bịa số liệu)', 'sergio-ai-studio' ),
				'description' => __( 'Hệ thống tra cứu web Google Search Grounding không tìm thấy đủ nguồn tin độc lập để xác thực dữ kiện.', 'sergio-ai-studio' ),
				'suggestion'  => __( 'Hãy sử dụng chủ đề mang tính thời sự rộng rãi hơn hoặc chứa các từ khóa sự kiện đã được nhiều nguồn tin báo chí đưa tin.', 'sergio-ai-studio' ),
				'action_tab'  => 'tab-generation',
			);
		}

		if ( strpos( $combined, 'safety' ) !== false || strpos( $combined, 'blocked' ) !== false || strpos( $combined, 'an toàn' ) !== false ) {
			return array(
				'type'        => 'safety_block',
				'icon'        => 'dashicons-shield',
				'title'       => __( 'Bộ lọc Google Safety Filter kích hoạt', 'sergio-ai-studio' ),
				'description' => __( 'Chủ đề hoặc nội dung bị chính sách an toàn của Google từ chối xử lý.', 'sergio-ai-studio' ),
				'suggestion'  => __( 'Hãy điều chỉnh từ ngữ trong chủ đề tránh các thuật ngữ nhạy cảm về chính trị, sức khỏe hoặc tài chính.', 'sergio-ai-studio' ),
				'action_tab'  => 'tab-generation',
			);
		}

		return array(
			'type'        => 'general_error',
			'icon'        => 'dashicons-dismiss',
			'title'       => __( 'Phát sinh lỗi thực thi', 'sergio-ai-studio' ),
			'description' => $message,
			'suggestion'  => __( 'Nhấp vào nút Chi tiết JSON bên dưới để kiểm tra payload lỗi kỹ thuật gửi về từ API.', 'sergio-ai-studio' ),
			'action_tab'  => '',
		);
	}

	/**
	 * Format log message with WordPress clickable post links
	 *
	 * @param string $message
	 * @return string
	 */
	public static function format_log_message_html( $message ) {
		$safe_msg = esc_html( $message );
		$seen_ids = array();

		return preg_replace_callback(
			'/(?:(?:bài viết\s*(?:ID\s*)?#|ID\s*#))(\d+)/ui',
			function( $m ) use ( &$seen_ids ) {
				$post_id = (int) $m[1];
				if ( $post_id <= 0 || isset( $seen_ids[ $post_id ] ) ) {
					return $m[0];
				}

				if ( function_exists( 'get_post' ) ) {
					$post = get_post( $post_id );
					if ( ! $post || ( ! empty( $post->post_type ) && ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) || ( ! empty( $post->post_status ) && in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) ) {
						return $m[0];
					}
				}

				$seen_ids[ $post_id ] = true;
				$edit_url = function_exists( 'get_edit_post_link' ) ? get_edit_post_link( $post_id, 'raw' ) : '';
				$view_url = function_exists( 'get_permalink' ) ? get_permalink( $post_id ) : '';

				if ( $edit_url ) {
					return $m[0] . ' <span class="sergio-log-post-actions">'
						. '<a href="' . esc_url( $edit_url ) . '" target="_blank" class="button button-small" style="padding:0 6px; height:20px; line-height:18px; font-size:11px; margin-left:4px; vertical-align:middle;" title="' . esc_attr__( 'Chỉnh sửa bài viết trong WordPress', 'sergio-ai-studio' ) . '">Sửa</a>'
						. ( $view_url ? ' <a href="' . esc_url( $view_url ) . '" target="_blank" class="button button-small" style="padding:0 6px; height:20px; line-height:18px; font-size:11px; margin-left:2px; vertical-align:middle;" title="' . esc_attr__( 'Xem bài viết ngoài trang chủ', 'sergio-ai-studio' ) . '">Xem</a>' : '' )
						. '</span>';
				}

				return $m[0];
			},
			$safe_msg
		);
	}

	/**
	 * Map internal stage slug to human-readable label
	 *
	 * @param string $stage
	 * @return string
	 */
	public static function map_stage_label( $stage ) {
		$labels = array(
			'fact_collection'               => __( 'Nghiên cứu & Đối chứng dữ kiện', 'sergio-ai-studio' ),
			'fact_pack'                     => __( 'Lập Fact Pack', 'sergio-ai-studio' ),
			'editorial_plan'                => __( 'Lập dàn ý & Tách thực thể', 'sergio-ai-studio' ),
			'topic_analysis'                => __( 'Phân tích chủ đề', 'sergio-ai-studio' ),
			'writing'                       => __( 'Biên tập bài viết AI', 'sergio-ai-studio' ),
			'post_saved'                    => __( 'Lưu bài viết vào WordPress', 'sergio-ai-studio' ),
			'post_save_failed'              => __( 'Lỗi lưu bài viết', 'sergio-ai-studio' ),
			'image_service'                 => __( 'Tạo ảnh & Banner đại diện', 'sergio-ai-studio' ),
			'image_service_failed'          => __( 'Cảnh báo tạo ảnh', 'sergio-ai-studio' ),
			'seo_audit'                     => __( 'Tối ưu Rank Math SEO', 'sergio-ai-studio' ),
			'seo_audit_failed'              => __( 'Cảnh báo kiểm định SEO', 'sergio-ai-studio' ),
			'cron_start'                    => __( 'Khởi động Cron Batch', 'sergio-ai-studio' ),
			'cron_completed'                => __( 'Hoàn thành phiên Cron', 'sergio-ai-studio' ),
			'cron_discovery'                => __( 'Tự động quét chủ đề mới', 'sergio-ai-studio' ),
			'cron_hybrid_fallback'          => __( 'Fallback nghiên cứu theo Niche', 'sergio-ai-studio' ),
			'cron_fact_pack_failed'         => __( 'Cron thất bại ở bước Fact Pack', 'sergio-ai-studio' ),
			'cron_writing_failed'           => __( 'Cron thất bại ở bước Viết bài', 'sergio-ai-studio' ),
			'cron_editorial_failed'         => __( 'Cron thất bại ở bước Dàn ý', 'sergio-ai-studio' ),
			'cron_discovery_duplicate'      => __( 'Trùng lặp chủ đề, đổi chủ đề mới', 'sergio-ai-studio' ),
			'content_rewriter'              => __( 'Viết lại nội dung nguồn tin', 'sergio-ai-studio' ),
			'plagiarism_threshold_exceeded' => __( 'Cảnh báo trùng lặp nguồn tin', 'sergio-ai-studio' ),
		);

		return isset( $labels[ $stage ] ) ? $labels[ $stage ] : $stage;
	}

	/**
	 * Get list of stages for a given module
	 *
	 * @param string $module
	 * @return array
	 */
	public static function get_module_stages( $module ) {
		switch ( $module ) {
			case 'generation':
				return array( 'fact_collection', 'fact_pack', 'editorial_plan', 'topic_analysis', 'writing', 'post_saved', 'post_save_failed' );
			case 'scheduler':
				return array( 'cron_start', 'cron_completed', 'cron_discovery', 'cron_hybrid_fallback', 'cron_fact_pack_failed', 'cron_writing_failed', 'cron_editorial_failed', 'cron_discovery_failed', 'cron_discovery_duplicate' );
			case 'seo':
				return array( 'seo_audit', 'seo_audit_failed', 'seo_batch' );
			case 'image':
				return array( 'image_service', 'image_service_failed', 'image_banner' );
			case 'rewrite':
				return array( 'content_rewriter', 'plagiarism_threshold_exceeded' );
			default:
				return array();
		}
	}

	/**
	 * Clear old logs (> 30 days)
	 */
	public static function purge_old_logs() {
		self::purge_logs_days( 30 );
	}

	/**
	 * Purge logs older than N days
	 *
	 * @param int $days
	 * @return int|false
	 */
	public static function purge_logs_days( $days = 7 ) {
		global $wpdb;
		$table  = Sergio_AI_DB::table( 'logs' );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( (int) $days * DAY_IN_SECONDS ) );
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) );
	}

	/**
	 * Clear all logs completely
	 *
	 * @return int|false
	 */
	/**
	 * Delete specific logs by IDs
	 *
	 * @param array $ids
	 * @return int|false
	 */
	public static function delete_logs( array $ids ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'logs' );
		$ids   = array_map( 'absint', $ids );
		$ids   = array_filter( $ids );
		if ( empty( $ids ) ) {
			return 0;
		}
		$in = implode( ',', $ids );
		return $wpdb->query( "DELETE FROM {$table} WHERE id IN ({$in})" );
	}

	public static function clear_all_logs() {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'logs' );
		return $wpdb->query( "TRUNCATE TABLE {$table}" );
	}
}
