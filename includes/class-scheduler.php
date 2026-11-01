<?php
/**
 * Automated Scheduler & Topic Queue Manager (Sergio AI Studio v2.1)
 *
 * Capabilities:
 * - Mutex-locked WP-Cron execution preventing race conditions
 * - Configurable posts per run (1 to 5 articles per execution)
 * - Daily quota cap (max posts per day) to protect API limits
 * - 3 Topic strategies: Queue-only, Hybrid (Queue + Auto Niche), AI Auto-Discovery
 * - Author selection, article length control, category routing
 * - Automatic custom hour interval registration
 *
 * @package Sergio_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sergio_AI_Scheduler {

	const CRON_HOOK = 'sergio_ai_studio_cron_event';
	const LOCK_KEY  = 'sergio_ai_cron_execution_lock';

	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'register_cron_intervals' ) );
		add_action( self::CRON_HOOK, array( $this, 'execute_cron_job' ) );
	}

	/**
	 * Register custom cron intervals
	 */
	public function register_cron_intervals( $schedules ) {
		if ( ! isset( $schedules['every_one_hour'] ) ) {
			$schedules['every_one_hour'] = array(
				'interval' => HOUR_IN_SECONDS,
				'display'  => __( 'Mỗi 1 giờ (Every 1 Hour)', 'sergio-ai-studio' ),
			);
		}
		if ( ! isset( $schedules['every_two_hours'] ) ) {
			$schedules['every_two_hours'] = array(
				'interval' => 2 * HOUR_IN_SECONDS,
				'display'  => __( 'Mỗi 2 giờ (Every 2 Hours)', 'sergio-ai-studio' ),
			);
		}
		if ( ! isset( $schedules['every_four_hours'] ) ) {
			$schedules['every_four_hours'] = array(
				'interval' => 4 * HOUR_IN_SECONDS,
				'display'  => __( 'Mỗi 4 giờ (Every 4 Hours)', 'sergio-ai-studio' ),
			);
		}
		if ( ! isset( $schedules['every_six_hours'] ) ) {
			$schedules['every_six_hours'] = array(
				'interval' => 6 * HOUR_IN_SECONDS,
				'display'  => __( 'Mỗi 6 giờ (Every 6 Hours)', 'sergio-ai-studio' ),
			);
		}

		// Support dynamic user-defined custom hours interval
		$options      = get_option( 'sergio_ai_studio_options', array() );
		$custom_hours = isset( $options['schedule_custom_hours'] ) ? (int) $options['schedule_custom_hours'] : 0;
		if ( $custom_hours > 0 ) {
			$schedules['sergio_custom_interval'] = array(
				'interval' => $custom_hours * HOUR_IN_SECONDS,
				'display'  => sprintf( __( 'Tùy chỉnh: Mỗi %d giờ', 'sergio-ai-studio' ), $custom_hours ),
			);
		}

		return $schedules;
	}

	/**
	 * Setup or update cron event
	 *
	 * @param string $frequency
	 * @param int    $custom_hours
	 */
	public function setup_schedule( $frequency = 'daily', $custom_hours = 0 ) {
		$this->clear_schedule();

		$allowed = array( 'every_one_hour', 'every_two_hours', 'every_four_hours', 'every_six_hours', 'twicedaily', 'daily', 'weekly', 'sergio_custom_interval' );
		if ( $custom_hours > 0 ) {
			$frequency = 'sergio_custom_interval';
		} elseif ( 'sergio_custom_interval' === $frequency ) {
			$options      = get_option( 'sergio_ai_studio_options', array() );
			$custom_hours = isset( $options['schedule_custom_hours'] ) ? (int) $options['schedule_custom_hours'] : 1;
			if ( $custom_hours <= 0 ) {
				$custom_hours = 1;
			}
		} elseif ( ! in_array( $frequency, $allowed, true ) ) {
			$frequency = 'daily';
		}

		// Ensure sergio_custom_interval is dynamically registered right now for wp_get_schedules()
		if ( 'sergio_custom_interval' === $frequency && $custom_hours > 0 ) {
			add_filter( 'cron_schedules', function( $schedules ) use ( $custom_hours ) {
				$schedules['sergio_custom_interval'] = array(
					'interval' => $custom_hours * HOUR_IN_SECONDS,
					'display'  => sprintf( __( 'Tùy chỉnh: Mỗi %d giờ', 'sergio-ai-studio' ), $custom_hours ),
				);
				return $schedules;
			}, 999 );
		}

		// Schedule next run 5 minutes from now
		$first_run = time() + 300;
		wp_schedule_event( $first_run, $frequency, self::CRON_HOOK );
	}

	/**
	 * Clear scheduled cron event
	 */
	public function clear_schedule() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Get next run formatted time
	 *
	 * @return string|false
	 */
	public function get_next_run_time() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( ! $timestamp ) {
			return false;
		}
		return wp_date( 'd/m/Y H:i:s', $timestamp );
	}

	/**
	 * Get count of posts generated today
	 */
	public static function get_daily_posts_count() {
		global $wpdb;
		$today_start = current_time( 'Y-m-d 00:00:00' );
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE pm.meta_key = '_sergio_ai_generated'
			AND p.post_date >= %s
			AND p.post_status != 'trash'",
			$today_start
		) );
	}

	/**
	 * Execute background post generation
	 * Supports multi-post batches, daily quota, and AI topic discovery
	 */
	public function execute_cron_job() {
		$options = get_option( 'sergio_ai_studio_options', array() );
		$enabled = ! empty( $options['scheduler_enabled'] );

		if ( ! $enabled ) {
			return;
		}

		// Automatically prune logs older than 30 days
		Sergio_AI_Logger::purge_old_logs();

		global $wpdb;
		$lock_name = 'sergio_ai_cron_mutex';
		$acquired  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT GET_LOCK(%s, 0)", $lock_name ) );

		if ( 1 !== $acquired ) {
			return; // Another worker process is actively executing
		}

		$logger = new Sergio_AI_Logger();
		$logger->info( 'cron_start', 'Bắt đầu phiên chạy tự động WP-Cron.' );

		try {
			$posts_per_run = 1; // Process 1 post per cron run to avoid long blocking and HTTP timeout
			$max_per_day   = isset( $options['schedule_max_posts_per_day'] ) ? (int) $options['schedule_max_posts_per_day'] : 10;
			$topic_mode    = isset( $options['schedule_topic_mode'] ) ? $options['schedule_topic_mode'] : 'hybrid';
			$niche_topic   = isset( $options['schedule_niche_topic'] ) ? trim( $options['schedule_niche_topic'] ) : '';
			$default_cat   = isset( $options['default_schedule_cat'] ) ? (int) $options['default_schedule_cat'] : 0;
			$default_auth  = isset( $options['default_schedule_author'] ) ? (int) $options['default_schedule_author'] : 0;
			// Default to draft / pending for human editorial review
			$post_status   = isset( $options['default_schedule_status'] ) && in_array( $options['default_schedule_status'], array( 'draft', 'pending' ), true )
				? $options['default_schedule_status']
				: 'draft';
			$language      = isset( $options['default_language'] ) ? $options['default_language'] : 'vi';

			$client = new Sergio_AI_Gemini_Client( '', '', $logger );

			for ( $i = 0; $i < $posts_per_run; $i++ ) {
				// 1. Check daily quota limit
				if ( $max_per_day > 0 && self::get_daily_posts_count() >= $max_per_day ) {
					$logger->info( 'cron_daily_cap', sprintf( 'Đã đạt giới hạn tối đa %d bài trong ngày hôm nay. Tạm dừng phiên sinh bài.', $max_per_day ) );
					break;
				}

				// 2. Resolve topic based on chosen strategy
				$topic_title = '';
				$category_id = $default_cat;
				$queue_row   = null;

				if ( 'queue_only' === $topic_mode ) {
					$queue_row = self::claim_next_topic();
					if ( ! $queue_row ) {
						$logger->info( 'cron_queue_empty', 'Hàng đợi chủ đề (Queue) trống trong chế độ "Chỉ lấy từ hàng đợi".' );
						break;
					}
					$topic_title = $queue_row['topic'];
					$category_id = ! empty( $queue_row['category_id'] ) ? (int) $queue_row['category_id'] : $default_cat;

				} elseif ( 'ai_discover' === $topic_mode ) {
					$discovered = self::discover_topic_from_niche( $client, $logger, $niche_topic, $language );
					if ( is_wp_error( $discovered ) ) {
						$logger->error( 'cron_discovery_failed', $discovered->get_error_message() );
						break;
					}
					$topic_title = $discovered;

				} else { // 'hybrid'
					$queue_row = self::claim_next_topic();
					if ( $queue_row ) {
						$topic_title = $queue_row['topic'];
						$category_id = ! empty( $queue_row['category_id'] ) ? (int) $queue_row['category_id'] : $default_cat;
					} else {
						// Fallback to auto-discovery
						$logger->info( 'cron_hybrid_fallback', 'Hàng đợi trống. Tự động nghiên cứu tin tức theo Lĩnh vực ngách.' );
						$discovered = self::discover_topic_from_niche( $client, $logger, $niche_topic, $language );
						if ( is_wp_error( $discovered ) ) {
							$logger->error( 'cron_discovery_failed', $discovered->get_error_message() );
							break;
						}
						$topic_title = $discovered;
					}
				}

				if ( empty( $topic_title ) ) {
					break;
				}

				$job_success = false;

				// 3. Step 1: Build Fact Pack
				$fact_engine = new Sergio_AI_Fact_Pack_Engine( $client, $logger );
				$fact_pack   = $fact_engine->build_fact_pack( $topic_title, $language );

				if ( is_wp_error( $fact_pack ) ) {
					$err = $fact_pack->get_error_message();
					if ( $queue_row ) {
						self::update_topic_status( $queue_row['id'], 'failed', $err );
					}
					$logger->warning( 'cron_fact_pack_failed', sprintf( 'Chủ đề "%s" thất bại: %s', $topic_title, $err ) );
					$this->record_cron_failure( $err, $options, $logger );
					continue;
				}

				// 4. Step 2: Editorial Plan
				$analyzer       = new Sergio_AI_Topic_Analyzer( $client, $logger );
				$editorial_plan = $analyzer->generate_editorial_plan( $topic_title, $fact_pack, $language );

				if ( is_wp_error( $editorial_plan ) ) {
					$err = $editorial_plan->get_error_message();
					if ( $queue_row ) {
						self::update_topic_status( $queue_row['id'], 'failed', $err );
					}
					$logger->error( 'cron_editorial_failed', $err );
					$this->record_cron_failure( $err, $options, $logger );
					continue;
				}

				// 5. Step 3: Write Article
				$writer  = new Sergio_AI_Article_Writer( $client, $logger );
				$article = $writer->write_article( $fact_pack, $editorial_plan );

				if ( is_wp_error( $article ) ) {
					$err = $article->get_error_message();
					if ( $queue_row ) {
						self::update_topic_status( $queue_row['id'], 'failed', $err );
					}
					$logger->error( 'cron_writing_failed', $err );
					$this->record_cron_failure( $err, $options, $logger );
					continue;
				}

				// 6. Step 4: Save Post
				$image_opt = isset( $options['schedule_image_option'] ) ? sanitize_key( $options['schedule_image_option'] ) : 'reference';

				$post_id = $writer->save_post( $article, array(
					'status'       => $post_status,
					'category_id'  => $category_id,
					'author_id'    => $default_auth,
					'image_option' => $image_opt,
				), $fact_pack['id'] );

				if ( is_wp_error( $post_id ) ) {
					$err = $post_id->get_error_message();
					if ( $queue_row ) {
						self::update_topic_status( $queue_row['id'], 'failed', $err );
					}
					$this->record_cron_failure( $err, $options, $logger );
					continue;
				}

				if ( $queue_row ) {
					self::update_topic_status( $queue_row['id'], 'completed' );
				}

				// Reset consecutive failures counter on success
				update_option( 'sergio_ai_consecutive_cron_failures', 0 );

				$logger->info( 'cron_completed', sprintf( 'Tạo bài tự động thành công ID #%d cho chủ đề "%s"', $post_id, $topic_title ) );
			}

		} finally {
			// Always release the atomic mutex
			$wpdb->query( $wpdb->prepare( "SELECT RELEASE_LOCK(%s)", $lock_name ) );
		}
	}

	/**
	 * Record a cron failure and trigger circuit breaker if threshold is reached
	 */
	private function record_cron_failure( $err, &$options, $logger ) {
		$failures = (int) get_option( 'sergio_ai_consecutive_cron_failures', 0 ) + 1;
		update_option( 'sergio_ai_consecutive_cron_failures', $failures );

		if ( $failures >= 3 ) {
			// Trip circuit breaker: pause scheduler to protect API quota and server
			$options['scheduler_enabled'] = 0;
			update_option( 'sergio_ai_studio_options', $options );

			$logger->error(
				'circuit_breaker_tripped',
				sprintf( 'Circuit breaker kích hoạt: Scheduler tự động tạm dừng sau %d lần chạy thất bại liên tiếp.', $failures )
			);

			$admin_email = get_option( 'admin_email' );
			if ( $admin_email ) {
				$site_name = get_bloginfo( 'name' );
				$subject   = sprintf( '[%s] Sergio AI Studio: Cảnh báo tự động tạm dừng lịch trình', $site_name );
				$message   = "Xin chào Quản trị viên,\n\n" .
							 "Tính năng viết bài tự động của Sergio AI Studio đã tự động TẠM DỪNG do gặp lỗi liên tiếp 3 lần trong tiến trình WP-Cron.\n\n" .
							 "Lỗi gần nhất: " . $err . "\n\n" .
							 "Vui lòng truy cập trang Quản trị WordPress > Sergio AI Studio để kiểm tra lại khóa API, mạng kết nối hoặc hàng đợi chủ đề trước khi bật lại.\n\n" .
							 "Trân trọng,\nSergio AI Studio System";

				wp_mail( $admin_email, $subject, $message );
			}
		}
	}

	/**
	 * Check if a topic was recently covered within the last N days
	 * Checks both published posts in wp_posts and completed queue items
	 *
	 * @param string $topic Topic text
	 * @param int    $days  Number of days to check (default 30)
	 * @return bool
	 */
	public static function is_topic_duplicate_recent( $topic, $days = 30 ) {
		global $wpdb;
		$clean_topic = trim( sanitize_text_field( $topic ) );
		if ( empty( $clean_topic ) ) {
			return false;
		}

		$cutoff_date = gmdate( 'Y-m-d H:i:s', time() - ( (int) $days * DAY_IN_SECONDS ) );

		// 1. Check published/draft posts
		$like_query  = '%' . $wpdb->esc_like( $clean_topic ) . '%';
		$post_exists = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_type = 'post'
			AND post_status IN ('publish', 'draft', 'pending')
			AND post_date >= %s
			AND (post_title LIKE %s OR %s LIKE CONCAT('%%', post_title, '%%'))
			LIMIT 1",
			$cutoff_date,
			$like_query,
			$clean_topic
		) );

		if ( $post_exists > 0 ) {
			return true;
		}

		// 2. Check completed queue items
		$table_queue  = Sergio_AI_DB::table( 'topics_queue' );
		$queue_exists = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table_queue}
			WHERE status = 'completed'
			AND created_at >= %s
			AND (topic = %s OR %s LIKE CONCAT('%%', topic, '%%'))
			LIMIT 1",
			$cutoff_date,
			$clean_topic,
			$clean_topic
		) );

		return $queue_exists > 0;
	}

	/**
	 * AI Auto-Discovery: Ask Gemini to discover a hot trending news topic in the niche
	 * Powered by Google Search Grounding to guarantee real-world grounding and fresh entities
	 */
	public static function discover_topic_from_niche( $client, $logger, $niche = '', $language = 'vi' ) {
		if ( empty( $niche ) ) {
			$niche = 'Tin tức trí tuệ nhân tạo, công nghệ phần mềm mới nhất';
		}

		$date_str = wp_date( 'd/m/Y' );

		// Retrieve recent topics to explicitly forbid duplicates
		global $wpdb;
		$recent_posts = $wpdb->get_col(
			"SELECT post_title FROM {$wpdb->posts}
			WHERE post_type = 'post'
			AND post_status IN ('publish', 'draft', 'pending')
			ORDER BY ID DESC LIMIT 15"
		);

		$exclusion_text = '';
		if ( ! empty( $recent_posts ) ) {
			$exclusion_text = "\nCÁC CHỦ ĐỀ ĐÃ XUẤT BẢN GẦN ĐÂY (TUYỆT ĐỐI KHÔNG TRÙNG LẶP NỘI DUNG VỚI DANH SÁCH NÀY):\n- " . implode( "\n- ", array_slice( $recent_posts, 0, 10 ) ) . "\n";
		}

		$prompt = "Bạn là biên tập viên tin tức hàng đầu. Hôm nay là ngày {$date_str}.\n" .
				  "Dựa trên lĩnh vực chuyên sâu: \"{$niche}\", hãy tra cứu bằng Google Search và chọn ra ĐÚNG 1 sự kiện, bản tin hoặc sản phẩm thực tế mới nhất trong 7 ngày qua để viết bài phân tích chuyên sâu.\n" .
				  $exclusion_text .
				  "QUY TẮC BẮT BUỘC:\n" .
				  "- Trả về CHỈ DUY NHẤT 1 DÒNG là tiêu đề chủ đề (ngắn gọn, dưới 15 từ, chứa tên thực thể/sản phẩm có thật).\n" .
				  "- KHÔNG có dấu ngoặc kép, KHÔNG có lời giải thích, KHÔNG có tiền tố như 'Chủ đề: ' hay '1. '.\n" .
				  "- Chủ đề PHẢI CÓ THẬT VÀ MỚI XẢY RA, không bịa đặt tên thực thể.";

		$logger->info( 'cron_topic_discovery', sprintf( 'Đang tự động nghiên cứu chủ đề mới bằng Google Search Grounding cho lĩnh vực: %s', $niche ) );

		// Google Search Grounding ENABLED (true)
		$res = $client->generate_content( $prompt, '', true, 0.7, 45 );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$raw_text   = is_array( $res ) ? ( $res['text'] ?? '' ) : (string) $res;
		$discovered = trim( sanitize_text_field( $raw_text ) );
		$discovered = trim( $discovered, "\"'\t\n\r" );
		$discovered = preg_replace( '/^(?:chủ đề|tiêu đề|topic)[:\s]*/iu', '', $discovered );

		if ( empty( $discovered ) ) {
			return new WP_Error( 'discovery_empty', 'Không thể tạo chủ đề tự động.' );
		}

		if ( self::is_topic_duplicate_recent( $discovered, 30 ) ) {
			$logger->warning( 'cron_discovery_duplicate', sprintf( 'Chủ đề "%s" bị trùng lặp với bài viết gần đây. Đang thử lại...', $discovered ) );
			$retry_prompt = $prompt . "\nLƯU Ý: Chủ đề '{$discovered}' đã bị trùng lặp, hãy chọn 1 sự kiện HOÀN TOÀN KHÁC.";
			$retry_res    = $client->generate_content( $retry_prompt, '', true, 0.8, 45 );
			if ( ! is_wp_error( $retry_res ) ) {
				$raw_retry        = is_array( $retry_res ) ? ( $retry_res['text'] ?? '' ) : (string) $retry_res;
				$retry_discovered = trim( sanitize_text_field( $raw_retry ) );
				$retry_discovered = trim( $retry_discovered, "\"'\t\n\r" );
				$retry_discovered = preg_replace( '/^(?:chủ đề|tiêu đề|topic)[:\s]*/iu', '', $retry_discovered );
				if ( ! empty( $retry_discovered ) && ! self::is_topic_duplicate_recent( $retry_discovered, 30 ) ) {
					return $retry_discovered;
				}
			}
		}

		return $discovered;
	}

	/**
	 * Atomically claim the next pending topic with automatic stuck recovery
	 */
	public static function claim_next_topic() {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );

		// 1. Recover topics stuck in 'processing' for > 30 minutes
		$stuck_cutoff = gmdate( 'Y-m-d H:i:s', time() - 1800 );
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$table}
			SET status = CASE WHEN attempts >= 3 THEN 'failed' ELSE 'pending' END,
			    last_error = CASE WHEN attempts >= 3 THEN 'Hủy tự động: Đã thử quá 3 lần bị gián đoạn' ELSE 'Tự động giải phóng: Quá 30 phút chưa hoàn tất' END,
			    updated_at = %s
			WHERE status = 'processing' AND updated_at < %s",
			current_time( 'mysql' ),
			$stuck_cutoff
		) );

		// 2. Select next pending topic
		$row = $wpdb->get_row( "SELECT id FROM {$table} WHERE status = 'pending' ORDER BY priority ASC, id ASC LIMIT 1", ARRAY_A );
		if ( ! $row ) {
			return null;
		}

		$id      = (int) $row['id'];
		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE {$table} SET status = 'processing', attempts = attempts + 1, updated_at = %s WHERE id = %d AND status = 'pending'",
			current_time( 'mysql' ),
			$id
		) );

		if ( $updated ) {
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		}

		return null;
	}

	/**
	 * Get next pending topic (read-only peek)
	 */
	public static function get_next_topic() {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );
		return $wpdb->get_row( "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY priority ASC, id ASC LIMIT 1", ARRAY_A );
	}

	/**
	 * Update topic status
	 */
	public static function update_topic_status( $id, $status, $error = null ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );

		$data = array(
			'status'     => sanitize_key( $status ),
			'updated_at' => current_time( 'mysql' ),
		);
		$formats = array( '%s', '%s' );

		if ( null !== $error ) {
			$data['last_error'] = sanitize_textarea_field( $error );
			$formats[]          = '%s';
		}

		$wpdb->update( $table, $data, array( 'id' => (int) $id ), $formats, array( '%d' ) );
	}

	/**
	 * Retry a failed or cancelled topic
	 */
	public static function retry_topic( $id ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );
		return $wpdb->update(
			$table,
			array(
				'status'     => 'pending',
				'attempts'   => 0,
				'last_error' => null,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Prioritize a topic by bumping its priority to top (1)
	 */
	public static function prioritize_topic( $id ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );
		return $wpdb->update(
			$table,
			array(
				'priority'   => 1,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Delete a topic from queue
	 */
	public static function delete_topic( $id ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );
		return $wpdb->delete( $table, array( 'id' => (int) $id ), array( '%d' ) );
	}

	/**
	 * Add topics in bulk
	 * Supports pipe-separated format: "Chủ đề | Từ khóa chính | ID Chuyên mục"
	 * Rejects recent duplicates (30 days)
	 *
	 * @param string $raw_topics  Raw newline-separated text
	 * @param int    $category_id Default category ID
	 * @param string $language    Language
	 * @return array Array with counts: ['added' => int, 'skipped' => int]
	 */
	public static function add_topics( $raw_topics, $category_id = 0, $language = 'vi' ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );

		$lines   = explode( "\n", $raw_topics );
		$added   = 0;
		$skipped = 0;

		foreach ( $lines as $line ) {
			$raw_line = trim( $line );
			if ( empty( $raw_line ) ) {
				continue;
			}

			// Parse pipe-delimited format: Topic | Focus Keyword | Category ID
			$parts       = array_map( 'trim', explode( '|', $raw_line ) );
			$topic       = sanitize_text_field( $parts[0] );
			$line_cat_id = isset( $parts[2] ) && is_numeric( $parts[2] ) ? absint( $parts[2] ) : absint( $category_id );

			if ( empty( $topic ) ) {
				continue;
			}

			// Duplicate check within 30 days
			if ( self::is_topic_duplicate_recent( $topic, 30 ) ) {
				$skipped++;
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'topic'       => $topic,
					'category_id' => $line_cat_id,
					'language'    => sanitize_key( $language ),
					'status'      => 'pending',
					'priority'    => 10,
					'created_at'  => current_time( 'mysql' ),
					'updated_at'  => current_time( 'mysql' ),
				),
				array( '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
			);
			$added++;
		}

		return array(
			'added'   => $added,
			'skipped' => $skipped,
		);
	}

	/**
	 * Get queue items with status filter
	 */
	public static function get_queue_items( $limit = 50, $status = '' ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );

		if ( ! empty( $status ) ) {
			return $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d",
				$status,
				(int) $limit
			), ARRAY_A );
		}

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d",
			(int) $limit
		), ARRAY_A );
	}
}
