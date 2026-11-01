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
		$table = Sergio_AI_DB::table( 'logs' );
		$today = current_time( 'Y-m-d' );
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE stage = 'cron_completed' AND created_at >= %s",
			$today . ' 00:00:00'
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

		global $wpdb;
		$lock_name = 'sergio_ai_cron_mutex';
		$acquired  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT GET_LOCK(%s, 0)", $lock_name ) );

		if ( 1 !== $acquired ) {
			return; // Another worker process is actively executing
		}

		$logger = new Sergio_AI_Logger();
		$logger->info( 'cron_start', 'Bắt đầu phiên chạy tự động WP-Cron.' );

		try {
			$posts_per_run = isset( $options['schedule_posts_per_run'] ) ? max( 1, min( 5, (int) $options['schedule_posts_per_run'] ) ) : 1;
			$max_per_day   = isset( $options['schedule_max_posts_per_day'] ) ? (int) $options['schedule_max_posts_per_day'] : 10;
			$topic_mode    = isset( $options['schedule_topic_mode'] ) ? $options['schedule_topic_mode'] : 'hybrid';
			$niche_topic   = isset( $options['schedule_niche_topic'] ) ? trim( $options['schedule_niche_topic'] ) : '';
			$default_cat   = isset( $options['default_schedule_cat'] ) ? (int) $options['default_schedule_cat'] : 0;
			$default_auth  = isset( $options['default_schedule_author'] ) ? (int) $options['default_schedule_author'] : 0;
			$post_status   = isset( $options['default_schedule_status'] ) ? $options['default_schedule_status'] : 'draft';
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

				// 3. Step 1: Build Fact Pack
				$fact_engine = new Sergio_AI_Fact_Pack_Engine( $client, $logger );
				$fact_pack   = $fact_engine->build_fact_pack( $topic_title, $language );

				if ( is_wp_error( $fact_pack ) ) {
					$err = $fact_pack->get_error_message();
					if ( $queue_row ) {
						self::update_topic_status( $queue_row['id'], 'failed', $err );
					}
					$logger->warning( 'cron_fact_pack_failed', sprintf( 'Chủ đề "%s" thất bại: %s', $topic_title, $err ) );
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
					continue;
				}

				// 6. Step 4: Save Post
				$post_id = $writer->save_post( $article, array(
					'status'      => $post_status,
					'category_id' => $category_id,
					'author_id'   => $default_auth,
				), $fact_pack['id'] );

				if ( is_wp_error( $post_id ) ) {
					$err = $post_id->get_error_message();
					if ( $queue_row ) {
						self::update_topic_status( $queue_row['id'], 'failed', $err );
					}
					continue;
				}

				if ( $queue_row ) {
					self::update_topic_status( $queue_row['id'], 'completed' );
				}

				$logger->info( 'cron_completed', sprintf( 'Tạo bài tự động thành công ID #%d cho chủ đề "%s"', $post_id, $topic_title ) );
			}

		} finally {
			// Always release the atomic mutex
			$wpdb->query( $wpdb->prepare( "SELECT RELEASE_LOCK(%s)", $lock_name ) );
		}
	}

	/**
	 * AI Auto-Discovery: Ask Gemini to discover a hot trending news topic in the niche
	 */
	public static function discover_topic_from_niche( $client, $logger, $niche = '', $language = 'vi' ) {
		if ( empty( $niche ) ) {
			$niche = 'Tin tức trí tuệ nhân tạo, công nghệ phần mềm mới nhất';
		}

		$date_str = wp_date( 'd/m/Y' );
		$prompt   = "Bạn là biên tập viên tin tức công nghệ hàng đầu. Hôm nay là ngày {$date_str}.\n" .
					"Dựa trên lĩnh vực: \"{$niche}\", hãy chọn ra ĐÚNG 1 chủ đề hoặc sự kiện tin tức công nghệ thực tế mới nhất trong 7 ngày qua để viết bài phân tích chuyên sâu.\n" .
					"QUY TẮC BẮT BUỘC:\n" .
					"- Trả về CHỈ DUY NHẤT 1 DÒNG là tiêu đề chủ đề (ngắn gọn, dưới 15 từ, chứa tên thực thể/sản phẩm có thật).\n" .
					"- KHÔNG có dấu ngoặc kép, KHÔNG có lời giải thích, KHÔNG có tiền tố.";

		$logger->info( 'cron_topic_discovery', sprintf( 'Đang tự động nghiên cứu chủ đề mới cho lĩnh vực: %s', $niche ) );

		$res = $client->generate_content( $prompt, '', false, 0.7, 30 );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$discovered = trim( sanitize_text_field( $res ) );
		$discovered = trim( $discovered, "\"'\t\n\r" );
		if ( empty( $discovered ) ) {
			return new WP_Error( 'discovery_empty', 'Không thể tạo chủ đề tự động.' );
		}

		return $discovered;
	}

	/**
	 * Atomically claim the next pending topic
	 */
	public static function claim_next_topic() {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );

		$row = $wpdb->get_row( "SELECT id FROM {$table} WHERE status = 'pending' ORDER BY priority ASC, id ASC LIMIT 1", ARRAY_A );
		if ( ! $row ) {
			return null;
		}

		$id      = (int) $row['id'];
		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE {$table} SET status = 'processing', updated_at = %s WHERE id = %d AND status = 'pending'",
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
	 * Add topics in bulk
	 */
	public static function add_topics( $raw_topics, $category_id = 0, $language = 'vi' ) {
		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );

		$lines = explode( "\n", $raw_topics );
		$added = 0;

		foreach ( $lines as $line ) {
			$topic = trim( sanitize_text_field( $line ) );
			if ( empty( $topic ) ) {
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'topic'       => $topic,
					'category_id' => absint( $category_id ),
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

		return $added;
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
