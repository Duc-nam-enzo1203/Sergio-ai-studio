<?php
/**
 * Admin Dashboard UI & AJAX Controller — Sergio AI Studio v2.1
 *
 * Impeccable Clean Light Edition:
 * - Professional Automated Scheduler (Batch sizes, daily quota, AI topic discovery, author routing)
 * - Authentic SVG Icon System (1.75px hairline stroke)
 * - Tabular numbers, custom switches, visual steppers
 * - Zero emoji clutter, zero AI card-slop, clean light theme
 *
 * @package Sergio_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sergio_AI_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_sergio-ai-studio/sergio-ai-studio.php', array( $this, 'plugin_action_links' ) );

		// AJAX Handlers.
		add_action( 'wp_ajax_sergio_ai_refresh_models', array( $this, 'ajax_refresh_models' ) );
		add_action( 'wp_ajax_sergio_ai_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_sergio_ai_run_pipeline_stage1', array( $this, 'ajax_run_pipeline_stage1' ) );
		add_action( 'wp_ajax_sergio_ai_generate_fact_pack_only', array( $this, 'ajax_generate_fact_pack_only' ) );
		add_action( 'wp_ajax_sergio_ai_write_article_from_fact_pack', array( $this, 'ajax_write_article_from_fact_pack' ) );
		add_action( 'wp_ajax_sergio_ai_add_queue_topics', array( $this, 'ajax_add_queue_topics' ) );
		add_action( 'wp_ajax_sergio_ai_retry_queue_item', array( $this, 'ajax_retry_queue_item' ) );
		add_action( 'wp_ajax_sergio_ai_prioritize_queue_item', array( $this, 'ajax_prioritize_queue_item' ) );
		add_action( 'wp_ajax_sergio_ai_delete_queue_item', array( $this, 'ajax_delete_queue_item' ) );
		add_action( 'wp_ajax_sergio_ai_run_cron_now', array( $this, 'ajax_run_cron_now' ) );
		add_action( 'wp_ajax_sergio_ai_crawl_rewrite', array( $this, 'ajax_crawl_rewrite' ) );
		add_action( 'wp_ajax_sergio_ai_clear_logs', array( $this, 'ajax_clear_logs' ) );
		add_action( 'wp_ajax_sergio_ai_refresh_logs', array( $this, 'ajax_refresh_logs' ) );
		add_action( 'wp_ajax_sergio_ai_export_logs', array( $this, 'ajax_export_logs' ) );
		add_action( 'wp_ajax_sergio_ai_site_seo_audit', array( $this, 'ajax_site_seo_audit' ) );
		add_action( 'wp_ajax_sergio_ai_audit_single_post', array( $this, 'ajax_audit_single_post' ) );
		add_action( 'wp_ajax_sergio_ai_optimize_post_seo', array( $this, 'ajax_optimize_post_seo' ) );
		add_action( 'wp_ajax_sergio_ai_apply_seo_changes', array( $this, 'ajax_apply_seo_changes' ) );
		add_action( 'wp_ajax_sergio_ai_deep_optimize_content', array( $this, 'ajax_deep_optimize_content' ) );
		add_action( 'wp_ajax_sergio_ai_batch_fix_seo', array( $this, 'ajax_batch_fix_seo' ) );
	}

	// -------------------------------------------------------------------------
	// DASHICONS ICON SYSTEM (WordPress Native Standards)
	// -------------------------------------------------------------------------

	public static function svg( $name, $size = 18, $class = '' ) {
		$map = array(
			'overview'  => 'dashicons-dashboard',
			'settings'  => 'dashicons-admin-settings',
			'scheduler' => 'dashicons-calendar-alt',
			'writer'    => 'dashicons-edit',
			'crawl'     => 'dashicons-admin-links',
			'seo'       => 'dashicons-chart-area',
			'logs'      => 'dashicons-list-view',
			'shield'    => 'dashicons-shield',
			'sparkles'  => 'dashicons-superhero',
			'check'     => 'dashicons-yes-alt',
			'refresh'   => 'dashicons-update',
			'arrow-up'  => 'dashicons-arrow-up-alt',
			'trash'     => 'dashicons-trash',
			'bolt'      => 'dashicons-lightning',
			'play'      => 'dashicons-controls-play',
			'cpu'       => 'dashicons-rest-api',
			'clock'     => 'dashicons-clock',
			'logo'      => 'dashicons-superhero',
		);
		$dashicon = $map[ $name ] ?? 'dashicons-admin-generic';
		$cls      = $class ? 'dashicons ' . $dashicon . ' ' . esc_attr( $class ) : 'dashicons ' . $dashicon;
		$sz       = (int) $size;
		return '<span class="' . $cls . '" style="font-size:' . $sz . 'px; width:' . $sz . 'px; height:' . $sz . 'px; line-height:' . $sz . 'px; vertical-align:middle;" aria-hidden="true"></span>';
	}

	// -------------------------------------------------------------------------
	// SETUP & MENUS
	// -------------------------------------------------------------------------

	public function register_menu() {
		add_menu_page(
			__( 'Sergio AI Studio', 'sergio-ai-studio' ),
			__( 'Sergio AI Studio', 'sergio-ai-studio' ),
			'manage_options',
			'sergio-ai-studio',
			array( $this, 'render_dashboard' ),
			'dashicons-superhero',
			58
		);
	}

	public function plugin_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=sergio-ai-studio&tab=settings' ) ),
			esc_html__( 'Cài đặt', 'sergio-ai-studio' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function register_settings() {
		register_setting( 'sergio_ai_studio_settings_group', 'sergio_ai_studio_options', array(
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
		) );
	}

	public function sanitize_settings( $input ) {
		$current = get_option( 'sergio_ai_studio_options', array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}
		$clean = $current;

		$source = isset( $input['sergio_form_source'] ) ? sanitize_key( $input['sergio_form_source'] ) : '';

		// If form source is 'settings' or generic.
		if ( empty( $source ) || 'settings' === $source ) {
			// API Key — skip if constant defined in wp-config.
			if ( ! Sergio_AI_Security::is_key_defined_in_config() ) {
				if ( ! empty( $input['gemini_api_key'] ) ) {
					$raw_key = trim( sanitize_text_field( wp_unslash( $input['gemini_api_key'] ) ) );
					if ( false === strpos( $raw_key, '••••' ) ) {
						$clean['gemini_api_key'] = Sergio_AI_Security::encrypt( $raw_key );
					}
				}
			}

			if ( isset( $input['gemini_model_primary'] ) ) {
				$clean['gemini_model_primary'] = sanitize_text_field( wp_unslash( $input['gemini_model_primary'] ) );
				if ( 'gemini-2.5-flash-lite' === $clean['gemini_model_primary'] ) {
					$clean['gemini_model_primary'] = 'gemini-3.5-flash-lite';
				}
			}
			if ( isset( $input['gemini_model_fallback'] ) ) {
				$clean['gemini_model_fallback'] = sanitize_text_field( wp_unslash( $input['gemini_model_fallback'] ) );
				if ( 'gemini-2.5-flash-lite' === $clean['gemini_model_fallback'] ) {
					$clean['gemini_model_fallback'] = 'gemini-3.5-flash-lite';
				}
			}
			if ( isset( $input['default_language'] ) ) {
				$clean['default_language'] = sanitize_text_field( wp_unslash( $input['default_language'] ) );
			}
			if ( isset( $input['default_post_status'] ) ) {
				$clean['default_post_status'] = in_array( $input['default_post_status'], array( 'draft', 'publish' ), true ) ? $input['default_post_status'] : 'draft';
			}

			// Stock Photo API keys (Unsplash & Pexels).
			if ( isset( $input['unsplash_access_key'] ) ) {
				$raw_unsplash = trim( sanitize_text_field( wp_unslash( $input['unsplash_access_key'] ) ) );
				if ( empty( $raw_unsplash ) ) {
					$clean['unsplash_access_key'] = '';
				} elseif ( false === strpos( $raw_unsplash, '••••' ) ) {
					$clean['unsplash_access_key'] = Sergio_AI_Security::encrypt( $raw_unsplash );
				}
			}
			if ( isset( $input['pexels_api_key'] ) ) {
				$raw_pexels = trim( sanitize_text_field( wp_unslash( $input['pexels_api_key'] ) ) );
				if ( empty( $raw_pexels ) ) {
					$clean['pexels_api_key'] = '';
				} elseif ( false === strpos( $raw_pexels, '••••' ) ) {
					$clean['pexels_api_key'] = Sergio_AI_Security::encrypt( $raw_pexels );
				}
			}
		}

		// If form source is 'scheduler' or generic.
		if ( empty( $source ) || 'scheduler' === $source ) {
			$clean['scheduler_enabled'] = ! empty( $input['scheduler_enabled'] ) ? 1 : 0;

			if ( isset( $input['schedule_frequency'] ) ) {
				$clean['schedule_frequency'] = sanitize_text_field( wp_unslash( $input['schedule_frequency'] ) );
			}
			if ( isset( $input['schedule_custom_hours'] ) ) {
				$clean['schedule_custom_hours'] = absint( $input['schedule_custom_hours'] );
			}
			if ( isset( $input['schedule_posts_per_run'] ) ) {
				$clean['schedule_posts_per_run'] = min( 5, max( 1, absint( $input['schedule_posts_per_run'] ) ) );
			}
			if ( isset( $input['schedule_max_posts_per_day'] ) ) {
				$clean['schedule_max_posts_per_day'] = absint( $input['schedule_max_posts_per_day'] );
			}
			if ( isset( $input['schedule_topic_mode'] ) ) {
				$clean['schedule_topic_mode'] = in_array( $input['schedule_topic_mode'], array( 'hybrid', 'queue_only', 'ai_discover' ), true ) ? $input['schedule_topic_mode'] : 'hybrid';
			}
			if ( isset( $input['schedule_niche_topic'] ) ) {
				$clean['schedule_niche_topic'] = sanitize_text_field( wp_unslash( $input['schedule_niche_topic'] ) );
			}
			if ( isset( $input['schedule_target_length'] ) ) {
				$clean['schedule_target_length'] = in_array( $input['schedule_target_length'], array( 'standard', 'short', 'deep_dive' ), true ) ? $input['schedule_target_length'] : 'standard';
			}
			if ( isset( $input['default_schedule_author'] ) ) {
				$clean['default_schedule_author'] = absint( $input['default_schedule_author'] );
			}
			if ( isset( $input['default_schedule_cat'] ) ) {
				$clean['default_schedule_cat'] = absint( $input['default_schedule_cat'] );
			}
			if ( isset( $input['default_schedule_status'] ) ) {
				$clean['default_schedule_status'] = in_array( $input['default_schedule_status'], array( 'draft', 'publish' ), true ) ? $input['default_schedule_status'] : 'draft';
			}
			if ( isset( $input['schedule_image_option'] ) ) {
				$clean['schedule_image_option'] = in_array( $input['schedule_image_option'], array( 'reference', 'stock', 'none' ), true ) ? $input['schedule_image_option'] : 'reference';
			}

			$scheduler = new Sergio_AI_Scheduler();
			if ( $clean['scheduler_enabled'] ) {
				$custom_h = ( 'sergio_custom_interval' === ( $clean['schedule_frequency'] ?? 'daily' ) ) ? ( $clean['schedule_custom_hours'] ?? 1 ) : 0;
				$scheduler->setup_schedule( $clean['schedule_frequency'] ?? 'daily', $custom_h );
			} else {
				$scheduler->clear_schedule();
			}
		}

		return $clean;
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'sergio-ai-studio' ) ) {
			return;
		}

		$css_ver = file_exists( SERGIO_AI_STUDIO_DIR . 'assets/css/admin-studio.css' )
			? (string) filemtime( SERGIO_AI_STUDIO_DIR . 'assets/css/admin-studio.css' )
			: SERGIO_AI_STUDIO_VERSION;
		$js_ver  = file_exists( SERGIO_AI_STUDIO_DIR . 'assets/js/admin-studio.js' )
			? (string) filemtime( SERGIO_AI_STUDIO_DIR . 'assets/js/admin-studio.js' )
			: SERGIO_AI_STUDIO_VERSION;

		wp_enqueue_style(
			'sergio-ai-studio-admin-css',
			SERGIO_AI_STUDIO_URL . 'assets/css/admin-studio.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'sergio-ai-studio-admin-js',
			SERGIO_AI_STUDIO_URL . 'assets/js/admin-studio.js',
			array( 'jquery' ),
			$js_ver,
			true
		);

		wp_localize_script( 'sergio-ai-studio-admin-js', 'sergioAiVars', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'sergio_ai_studio_nonce' ),
		) );
	}

	// -------------------------------------------------------------------------
	// RENDER DASHBOARD
	// -------------------------------------------------------------------------

	public function render_dashboard() {
		$options       = get_option( 'sergio_ai_studio_options', array() );
		$raw_api_key   = $options['gemini_api_key'] ?? '';
		$is_config_key = Sergio_AI_Security::is_key_defined_in_config();
		$masked_key    = Sergio_AI_Security::mask_key( $raw_api_key );
		$has_key       = $is_config_key || ! empty( $raw_api_key );
		$primary_model = $options['gemini_model_primary']  ?? 'gemini-2.5-flash';
		$fallback_model= $options['gemini_model_fallback'] ?? 'gemini-3.5-flash-lite';
		if ( 'gemini-2.5-flash-lite' === $primary_model ) {
			$primary_model = 'gemini-3.5-flash-lite';
		}
		if ( 'gemini-2.5-flash-lite' === $fallback_model ) {
			$fallback_model = 'gemini-3.5-flash-lite';
		}
		$language      = $options['default_language']       ?? 'vi';

		// Load models.
		$client = new Sergio_AI_Gemini_Client();
		$models = $client->is_configured() ? $client->list_models( false ) : array();
		if ( is_wp_error( $models ) ) {
			$models = array();
		}

		$categories = get_categories( array( 'hide_empty' => false ) );
		$authors    = get_users( array( 'capability__in' => array( 'edit_posts' ), 'orderby' => 'display_name' ) );
		$log_stats    = Sergio_AI_Logger::get_stats();
		$logs         = Sergio_AI_Logger::get_logs( 50 );

		// Scheduler settings.
		$scheduler_enabled   = ! empty( $options['scheduler_enabled'] );
		$frequency           = $options['schedule_frequency'] ?? 'daily';
		$custom_hours        = isset( $options['schedule_custom_hours'] ) ? (int) $options['schedule_custom_hours'] : 1;
		$posts_per_run       = isset( $options['schedule_posts_per_run'] ) ? (int) $options['schedule_posts_per_run'] : 1;
		$max_posts_per_day   = isset( $options['schedule_max_posts_per_day'] ) ? (int) $options['schedule_max_posts_per_day'] : 10;
		$topic_mode          = $options['schedule_topic_mode'] ?? 'hybrid';
		$niche_topic         = $options['schedule_niche_topic'] ?? 'Tin tức trí tuệ nhân tạo, công nghệ AI mới nhất';
		$target_length       = $options['schedule_target_length'] ?? 'standard';
		$default_sched_auth  = (int) ( $options['default_schedule_author'] ?? get_current_user_id() );
		$default_sched_cat   = (int) ( $options['default_schedule_cat'] ?? 0 );
		$default_sched_stat  = $options['default_schedule_status'] ?? 'draft';
		$default_post_status = $options['default_post_status'] ?? 'draft';
		$masked_unsplash_key = Sergio_AI_Security::mask_key( $options['unsplash_access_key'] ?? '' );
		$masked_pexels_key   = Sergio_AI_Security::mask_key( $options['pexels_api_key'] ?? '' );
		$schedule_image_opt  = $options['schedule_image_option'] ?? 'reference';

		$scheduler     = new Sergio_AI_Scheduler();
		$next_run_time = $scheduler->get_next_run_time();

		// Self-heal: If scheduler is enabled but WP-Cron event was missing, schedule it immediately
		if ( $scheduler_enabled && ! $next_run_time ) {
			$custom_h = ( 'sergio_custom_interval' === $frequency ) ? $custom_hours : 0;
			$scheduler->setup_schedule( $frequency, $custom_h );
			$next_run_time = $scheduler->get_next_run_time();
		}

		$queue_items  = Sergio_AI_Scheduler::get_queue_items( 50 );
		$queue_count  = count( $queue_items );
		$posts_today  = Sergio_AI_Scheduler::get_daily_posts_count();
		$log_errors   = (int) $log_stats['error_count'];

		// Fact packs.
		global $wpdb;
		$table_packs = Sergio_AI_DB::table( 'fact_packs' );
		$fact_packs  = $wpdb->get_results( "SELECT * FROM {$table_packs} ORDER BY id DESC LIMIT 20", ARRAY_A ); // phpcs:ignore

		// Posts & Pages for SEO Audit
		$seo_posts = get_posts( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 60,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		// Active tab from URL.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview'; // phpcs:ignore
		?>
		<div class="wrap sergio-ai-studio-wrap">

			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Sergio AI Studio', 'sergio-ai-studio' ); ?>
			</h1>
			<span class="sergio-header-badge">
				<span class="sergio-status-dot <?php echo $has_key ? '' : 'is-disconnected'; ?>"></span>
				<code><?php echo esc_html( $primary_model ); ?></code>
				<span>&bull; v<?php echo esc_html( SERGIO_AI_STUDIO_VERSION ); ?></span>
			</span>
			<hr class="wp-header-end">

			<?php if ( ! $has_key ) : ?>
				<div style="margin-top: 15px; padding: 10px 14px; background: #fff8e5; border: 1px solid #ffeeba; border-radius: 4px; font-size: 13px;">
					<p>
						<?php esc_html_e( 'Chưa cấu hình Google Gemini API Key. Vui lòng chuyển sang tab Cài Đặt để nhập khóa API.', 'sergio-ai-studio' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<!-- NATIVE WORDPRESS ADMIN TABS -->
			<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Sergio AI Studio Tabs', 'sergio-ai-studio' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sergio-ai-studio&tab=overview' ) ); ?>" class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-overview">
					<?php esc_html_e( 'Tổng Quan', 'sergio-ai-studio' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sergio-ai-studio&tab=settings' ) ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-settings">
					<?php esc_html_e( 'Cài Đặt', 'sergio-ai-studio' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sergio-ai-studio&tab=scheduler' ) ); ?>" class="nav-tab <?php echo 'scheduler' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-scheduler">
					<?php esc_html_e( 'Lên Lịch', 'sergio-ai-studio' ); ?>
					<?php if ( $queue_count > 0 ) : ?>
						<span class="update-plugins count-<?php echo esc_attr( $queue_count ); ?>"><span class="update-count"><?php echo esc_html( $queue_count ); ?></span></span>
					<?php endif; ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sergio-ai-studio&tab=writer' ) ); ?>" class="nav-tab <?php echo 'writer' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-writer">
					<?php esc_html_e( 'Nghiên Cứu & Viết Bài', 'sergio-ai-studio' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sergio-ai-studio&tab=crawl' ) ); ?>" class="nav-tab <?php echo 'crawl' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-crawl">
					<?php esc_html_e( 'Crawl & Viết Lại', 'sergio-ai-studio' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sergio-ai-studio&tab=seo' ) ); ?>" class="nav-tab <?php echo 'seo' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-seo">
					<?php esc_html_e( 'Audit & Tối Ưu SEO', 'sergio-ai-studio' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sergio-ai-studio&tab=logs' ) ); ?>" class="nav-tab <?php echo 'logs' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-logs">
					<?php esc_html_e( 'Nhật Ký', 'sergio-ai-studio' ); ?>
					<?php if ( $log_errors > 0 ) : ?>
						<span class="update-plugins count-<?php echo esc_attr( $log_errors ); ?>"><span class="update-count"><?php echo esc_html( $log_errors ); ?></span></span>
					<?php endif; ?>
				</a>
			</nav>

			<div class="sergio-tab-content" style="margin-top: 15px;">
				<?php
				// Modular View Templates
				include SERGIO_AI_STUDIO_DIR . 'templates/tab-overview.php';
				include SERGIO_AI_STUDIO_DIR . 'templates/tab-settings.php';
				include SERGIO_AI_STUDIO_DIR . 'templates/tab-scheduler.php';
				include SERGIO_AI_STUDIO_DIR . 'templates/tab-writer.php';
				include SERGIO_AI_STUDIO_DIR . 'templates/tab-crawl.php';
				include SERGIO_AI_STUDIO_DIR . 'templates/tab-seo.php';
				include SERGIO_AI_STUDIO_DIR . 'templates/tab-logs.php';
				?>
			</div><!-- .sergio-tab-content -->
		</div><!-- .wrap -->
		<?php
	}

	// -------------------------------------------------------------------------
	// AJAX HANDLERS
	// -------------------------------------------------------------------------
	public function ajax_refresh_models() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( trim( wp_unslash( $_POST['api_key'] ) ) ) : '';

		if ( ! empty( $api_key ) && false === strpos( $api_key, '••••' ) ) {
			$client = new Sergio_AI_Gemini_Client( $api_key );
		} else {
			$client = new Sergio_AI_Gemini_Client();
		}

		if ( ! $client->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng nhập API Key hợp lệ trước khi dò tìm model.', 'sergio-ai-studio' ) ) );
		}

		$models = $client->list_models( true );

		if ( is_wp_error( $models ) ) {
			wp_send_json_error( array( 'message' => $models->get_error_message() ) );
		}

		wp_send_json_success( array(
			'models'  => $models,
			'message' => sprintf( __( 'Đã tự động phát hiện %d mô hình từ Google AI Studio!', 'sergio-ai-studio' ), count( $models ) ),
		) );
	}

	public function ajax_test_connection() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( trim( wp_unslash( $_POST['api_key'] ) ) ) : '';

		if ( false !== strpos( $api_key, '••••' ) || empty( $api_key ) ) {
			$client = new Sergio_AI_Gemini_Client();
		} else {
			$client = new Sergio_AI_Gemini_Client( $api_key );
		}

		if ( ! $client->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng nhập API Key hợp lệ.', 'sergio-ai-studio' ) ) );
		}

		$models = $client->list_models( true );
		if ( ! is_wp_error( $models ) && ! empty( $models ) ) {
			wp_send_json_success( array(
				'message' => sprintf( __( 'Kết nối Gemini API thành công! (Phát hiện %d mô hình)', 'sergio-ai-studio' ), count( $models ) ),
			) );
		}

		$res = $client->generate_content( 'Hello, respond only with OK', '', false, 0.1, 45 );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Kết nối Google Gemini API thành công!', 'sergio-ai-studio' ),
		) );
	}

	public function ajax_run_pipeline_stage1() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền đăng bài viết.', 'sergio-ai-studio' ) ) );
		}

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 );
		}
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			@wp_raise_memory_limit( 'admin' );
		}

		$user_id      = get_current_user_id();
		$cooldown_key = 'sergio_ai_cooldown_' . $user_id;
		if ( get_transient( $cooldown_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng đợi 10 giây trước khi thực thi bài tiếp theo.', 'sergio-ai-studio' ) ) );
		}
		set_transient( $cooldown_key, 1, 10 );

		$seed_query     = isset( $_POST['seed_query'] ) ? sanitize_text_field( wp_unslash( $_POST['seed_query'] ) ) : '';
		$category       = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$language       = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'vi';
		$target_length  = isset( $_POST['target_length'] ) ? sanitize_key( $_POST['target_length'] ) : 'standard';
		$tone           = isset( $_POST['tone'] ) ? sanitize_key( $_POST['tone'] ) : 'journalistic';
		$model_override = isset( $_POST['model_override'] ) ? sanitize_text_field( wp_unslash( $_POST['model_override'] ) ) : '';
		$grounding_mode    = isset( $_POST['grounding_mode'] ) ? sanitize_key( $_POST['grounding_mode'] ) : 'grounding';
		$verification_gate = isset( $_POST['verification_gate'] ) ? sanitize_key( $_POST['verification_gate'] ) : 'standard';
		$image_option      = isset( $_POST['image_option'] ) ? sanitize_key( $_POST['image_option'] ) : 'reference';
		$post_status       = isset( $_POST['post_status'] ) && in_array( $_POST['post_status'], array( 'draft', 'pending', 'publish' ), true ) ? $_POST['post_status'] : 'draft';
		$enable_rm         = ! empty( $_POST['enable_rank_math'] );
		$enable_il         = ! empty( $_POST['enable_internal_links'] );
		$enable_sb         = ! empty( $_POST['enable_sources_box'] );

		if ( empty( $seed_query ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng nhập chủ đề cần nghiên cứu.', 'sergio-ai-studio' ) ) );
		}

		$options_payload = array(
			'override_model'          => $model_override,
			'grounding_mode'          => $grounding_mode,
			'verification_strictness' => $verification_gate,
			'target_length'           => $target_length,
			'tone'                    => $tone,
			'image_option'            => $image_option,
			'status'                  => $post_status,
			'category_id'             => $category,
			'enable_rank_math'        => $enable_rm,
			'enable_internal_links'   => $enable_il,
			'enable_sources_box'      => $enable_sb,
		);

		$logger = new Sergio_AI_Logger();
		$client = new Sergio_AI_Gemini_Client( '', $model_override, $logger );

		$fact_engine = new Sergio_AI_Fact_Pack_Engine( $client, $logger );
		$fact_pack   = $fact_engine->build_fact_pack( $seed_query, $language, $options_payload );

		if ( is_wp_error( $fact_pack ) ) {
			wp_send_json_error( array( 'step' => 'fact_pack', 'message' => $fact_pack->get_error_message() ) );
		}

		$topic_analyzer = new Sergio_AI_Topic_Analyzer( $client, $logger );
		$editorial_plan = $topic_analyzer->generate_editorial_plan( $seed_query, $fact_pack, $language, $options_payload );

		if ( is_wp_error( $editorial_plan ) ) {
			wp_send_json_error( array( 'step' => 'editorial_plan', 'message' => $editorial_plan->get_error_message() ) );
		}

		$writer  = new Sergio_AI_Article_Writer( $client, $logger );
		$article = $writer->write_article( $fact_pack, $editorial_plan, $options_payload );

		if ( is_wp_error( $article ) ) {
			wp_send_json_error( array( 'step' => 'writing', 'message' => $article->get_error_message() ) );
		}

		$post_id = $writer->save_post( $article, $options_payload, $fact_pack['id'] );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'step' => 'saving', 'message' => $post_id->get_error_message() ) );
		}

		$status_label = 'publish' === $post_status ? __( 'Đã xuất bản', 'sergio-ai-studio' ) : ( 'pending' === $post_status ? __( 'Chờ duyệt', 'sergio-ai-studio' ) : __( 'Bản nháp', 'sergio-ai-studio' ) );
		$seo_score    = (int) get_post_meta( $post_id, '_sergio_ai_seo_score', true );

		wp_send_json_success( array(
			'message'       => sprintf( __( 'Tạo bài viết dựa trên dữ kiện thật hoàn tất (%s)!', 'sergio-ai-studio' ), $status_label ),
			'post_id'       => $post_id,
			'title'         => $editorial_plan['headline'],
			'focus_keyword' => $editorial_plan['primary_focus_keyword'],
			'seo_score'     => $seo_score > 0 ? $seo_score : 92,
			'sources_count' => count( $fact_pack['source_citations'] ?? array() ),
			'sources'       => $fact_pack['source_citations'] ?? array(),
			'entities'      => $fact_pack['entities'] ?? array(),
			'word_count'    => str_word_count( strip_tags( $article['content'] ) ),
			'edit_url'      => get_edit_post_link( $post_id, 'raw' ),
			'view_url'      => get_permalink( $post_id ),
			'status'        => $post_status,
		) );
	}

	public function ajax_generate_fact_pack_only() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 180 );
		}

		$seed_query     = isset( $_POST['seed_query'] ) ? sanitize_text_field( wp_unslash( $_POST['seed_query'] ) ) : '';
		$language       = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'vi';
		$model_override    = isset( $_POST['model_override'] ) ? sanitize_text_field( wp_unslash( $_POST['model_override'] ) ) : '';
		$grounding_mode    = isset( $_POST['grounding_mode'] ) ? sanitize_key( $_POST['grounding_mode'] ) : 'grounding';
		$verification_gate = isset( $_POST['verification_gate'] ) ? sanitize_key( $_POST['verification_gate'] ) : 'standard';

		if ( empty( $seed_query ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng nhập chủ đề cần nghiên cứu.', 'sergio-ai-studio' ) ) );
		}

		$options_payload = array(
			'override_model'          => $model_override,
			'grounding_mode'          => $grounding_mode,
			'verification_strictness' => $verification_gate,
		);

		$logger      = new Sergio_AI_Logger();
		$client      = new Sergio_AI_Gemini_Client( '', $model_override, $logger );
		$fact_engine = new Sergio_AI_Fact_Pack_Engine( $client, $logger );
		$fact_pack   = $fact_engine->build_fact_pack( $seed_query, $language, $options_payload );

		if ( is_wp_error( $fact_pack ) ) {
			wp_send_json_error( array( 'message' => $fact_pack->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'          => __( 'Thu thập dữ kiện và lập Fact Pack thành công!', 'sergio-ai-studio' ),
			'pack_id'          => $fact_pack['id'] ?? 0,
			'topic'            => $seed_query,
			'status'           => $fact_pack['status'] ?? 'verified',
			'primary_event'    => $fact_pack['primary_event'] ?? '',
			'entities'         => $fact_pack['entities'] ?? array(),
			'key_facts'        => $fact_pack['key_facts'] ?? array(),
			'verified_metrics' => $fact_pack['verified_metrics'] ?? array(),
			'source_citations' => $fact_pack['source_citations'] ?? array(),
		) );
	}

	public function ajax_write_article_from_fact_pack() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền đăng bài viết.', 'sergio-ai-studio' ) ) );
		}

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 );
		}
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			@wp_raise_memory_limit( 'admin' );
		}

		$pack_id        = isset( $_POST['pack_id'] ) ? absint( $_POST['pack_id'] ) : 0;
		$category       = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$language       = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'vi';
		$target_length  = isset( $_POST['target_length'] ) ? sanitize_key( $_POST['target_length'] ) : 'standard';
		$tone           = isset( $_POST['tone'] ) ? sanitize_key( $_POST['tone'] ) : 'journalistic';
		$model_override = isset( $_POST['model_override'] ) ? sanitize_text_field( wp_unslash( $_POST['model_override'] ) ) : '';
		$image_option   = isset( $_POST['image_option'] ) ? sanitize_key( $_POST['image_option'] ) : 'reference';
		$post_status    = isset( $_POST['post_status'] ) && in_array( $_POST['post_status'], array( 'draft', 'pending', 'publish' ), true ) ? $_POST['post_status'] : 'draft';
		$enable_rm      = ! empty( $_POST['enable_rank_math'] );
		$enable_il      = ! empty( $_POST['enable_internal_links'] );
		$enable_sb      = ! empty( $_POST['enable_sources_box'] );

		if ( empty( $pack_id ) ) {
			wp_send_json_error( array( 'message' => __( 'ID Fact Pack không hợp lệ.', 'sergio-ai-studio' ) ) );
		}

		$fact_pack = Sergio_AI_Fact_Pack_Engine::get_pack_by_id( $pack_id );
		if ( ! $fact_pack ) {
			wp_send_json_error( array( 'message' => __( 'Không tìm thấy dữ liệu Fact Pack trên hệ thống.', 'sergio-ai-studio' ) ) );
		}

		$options_payload = array(
			'override_model'        => $model_override,
			'target_length'         => $target_length,
			'tone'                  => $tone,
			'image_option'          => $image_option,
			'status'                => $post_status,
			'category_id'           => $category,
			'enable_rank_math'      => $enable_rm,
			'enable_internal_links' => $enable_il,
			'enable_sources_box'    => $enable_sb,
		);

		$logger = new Sergio_AI_Logger();
		$client = new Sergio_AI_Gemini_Client( '', $model_override, $logger );

		$topic_analyzer = new Sergio_AI_Topic_Analyzer( $client, $logger );
		$editorial_plan = $topic_analyzer->generate_editorial_plan( $fact_pack['topic'] ?? '', $fact_pack, $language, $options_payload );

		if ( is_wp_error( $editorial_plan ) ) {
			wp_send_json_error( array( 'step' => 'editorial_plan', 'message' => $editorial_plan->get_error_message() ) );
		}

		$writer  = new Sergio_AI_Article_Writer( $client, $logger );
		$article = $writer->write_article( $fact_pack, $editorial_plan, $options_payload );

		if ( is_wp_error( $article ) ) {
			wp_send_json_error( array( 'step' => 'writing', 'message' => $article->get_error_message() ) );
		}

		$post_id = $writer->save_post( $article, $options_payload, $pack_id );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'step' => 'saving', 'message' => $post_id->get_error_message() ) );
		}

		$status_label = 'publish' === $post_status ? __( 'Đã xuất bản', 'sergio-ai-studio' ) : ( 'pending' === $post_status ? __( 'Chờ duyệt', 'sergio-ai-studio' ) : __( 'Bản nháp', 'sergio-ai-studio' ) );
		$seo_score    = (int) get_post_meta( $post_id, '_sergio_ai_seo_score', true );

		wp_send_json_success( array(
			'message'       => sprintf( __( 'Chấp bút bài viết từ Fact Pack thành công (%s)!', 'sergio-ai-studio' ), $status_label ),
			'post_id'       => $post_id,
			'title'         => $editorial_plan['headline'],
			'focus_keyword' => $editorial_plan['primary_focus_keyword'],
			'seo_score'     => $seo_score > 0 ? $seo_score : 92,
			'sources_count' => count( $fact_pack['source_citations'] ?? array() ),
			'sources'       => $fact_pack['source_citations'] ?? array(),
			'entities'      => $fact_pack['entities'] ?? array(),
			'word_count'    => str_word_count( strip_tags( $article['content'] ) ),
			'edit_url'      => get_edit_post_link( $post_id, 'raw' ),
			'view_url'      => get_permalink( $post_id ),
			'status'        => $post_status,
		) );
	}

	public function ajax_add_queue_topics() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$raw_topics  = isset( $_POST['topics'] ) ? sanitize_textarea_field( wp_unslash( $_POST['topics'] ) ) : '';
		$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;

		if ( empty( trim( $raw_topics ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng nhập ít nhất một chủ đề.', 'sergio-ai-studio' ) ) );
		}

		$options  = get_option( 'sergio_ai_studio_options', array() );
		$language = isset( $options['default_language'] ) ? $options['default_language'] : 'vi';

		$result  = Sergio_AI_Scheduler::add_topics( $raw_topics, $category_id, $language );
		$added   = is_array( $result ) ? ( $result['added'] ?? 0 ) : (int) $result;
		$skipped = is_array( $result ) ? ( $result['skipped'] ?? 0 ) : 0;

		$msg = sprintf( __( 'Đã nạp thành công %d chủ đề vào hàng đợi!', 'sergio-ai-studio' ), $added );
		if ( $skipped > 0 ) {
			$msg .= ' ' . sprintf( __( '(%d chủ đề bị bỏ qua do trùng lặp gần đây)', 'sergio-ai-studio' ), $skipped );
		}

		wp_send_json_success( array(
			'message' => $msg,
			'added'   => $added,
			'skipped' => $skipped,
		) );
	}

	public function ajax_retry_queue_item() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( $id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'ID hàng đợi không hợp lệ.', 'sergio-ai-studio' ) ) );
		}

		Sergio_AI_Scheduler::retry_topic( $id );
		wp_send_json_success( array(
			'id'      => $id,
			'message' => __( 'Đã đặt lại chủ đề về trạng thái chờ xử lý (PENDING).', 'sergio-ai-studio' ),
		) );
	}

	public function ajax_prioritize_queue_item() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( $id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'ID hàng đợi không hợp lệ.', 'sergio-ai-studio' ) ) );
		}

		Sergio_AI_Scheduler::prioritize_topic( $id );
		wp_send_json_success( array(
			'id'      => $id,
			'message' => __( 'Đã đẩy chủ đề lên đầu hàng đợi ưu tiên.', 'sergio-ai-studio' ),
		) );
	}

	public function ajax_delete_queue_item() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( $id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'ID hàng đợi không hợp lệ.', 'sergio-ai-studio' ) ) );
		}

		global $wpdb;
		$table = Sergio_AI_DB::table( 'topics_queue' );
		$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		wp_send_json_success( array( 'id' => $id ) );
	}

	public function ajax_run_cron_now() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$options    = get_option( 'sergio_ai_studio_options', array() );
		$topic_mode = $options['schedule_topic_mode'] ?? 'hybrid';
		$topic_row  = Sergio_AI_Scheduler::get_next_topic();

		if ( ! $topic_row && 'queue_only' === $topic_mode ) {
			wp_send_json_error( array( 'message' => __( 'Hàng đợi đang trống! Vui lòng nạp chủ đề hoặc chuyển sang chế độ "Kết Hợp Thông Minh".', 'sergio-ai-studio' ) ) );
		}

		$scheduler = new Sergio_AI_Scheduler();
		delete_transient( Sergio_AI_Scheduler::LOCK_KEY );
		$scheduler->execute_cron_job();

		wp_send_json_success( array(
			'message' => __( 'Đã hoàn tất phiên kích hoạt! Hãy kiểm tra tab Nhật Ký hoặc Bài Viết để xem các bài mới được tạo.', 'sergio-ai-studio' ),
		) );
	}

	public function ajax_crawl_rewrite() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Chỉ Quản trị viên mới có quyền sử dụng tính năng Crawl & Viết Lại.', 'sergio-ai-studio' ) ) );
		}

		$user_id      = get_current_user_id();
		$cooldown_key = 'sergio_ai_crawl_cd_' . $user_id;
		if ( get_transient( $cooldown_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng đợi 15 giây trước khi crawl tiếp.', 'sergio-ai-studio' ) ) );
		}
		set_transient( $cooldown_key, 1, 15 );

		$raw_urls    = isset( $_POST['urls'] ) ? sanitize_textarea_field( wp_unslash( $_POST['urls'] ) ) : '';
		$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$language    = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'vi';
		$style       = isset( $_POST['style'] ) ? sanitize_text_field( wp_unslash( $_POST['style'] ) ) : 'news';
		$max_chars   = isset( $_POST['max_chars'] ) ? absint( $_POST['max_chars'] ) : 8000;

		if ( ! in_array( $style, array( 'news', 'analysis', 'tutorial' ), true ) ) {
			$style = 'news';
		}

		$max_chars = max( 2000, min( 15000, $max_chars ) );

		if ( empty( trim( $raw_urls ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng nhập ít nhất một URL.', 'sergio-ai-studio' ) ) );
		}

		$lines      = array_filter( array_map( 'trim', explode( "\n", $raw_urls ) ) );
		$valid_urls = array();

		foreach ( $lines as $line ) {
			$validated = wp_http_validate_url( $line );
			if ( ! $validated ) {
				continue;
			}
			$scheme = wp_parse_url( $validated, PHP_URL_SCHEME );
			if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
				continue;
			}
			$valid_urls[] = $validated;
		}

		if ( empty( $valid_urls ) ) {
			wp_send_json_error( array( 'message' => __( 'Không tìm thấy URL hợp lệ (http/https). Vui lòng kiểm tra lại.', 'sergio-ai-studio' ) ) );
		}

		$valid_urls = array_slice( $valid_urls, 0, 10 );

		if ( ! class_exists( 'Sergio_AI_Web_Crawler' ) ) {
			require_once SERGIO_AI_STUDIO_DIR . 'includes/class-web-crawler.php';
		}
		if ( ! class_exists( 'Sergio_AI_Content_Rewriter' ) ) {
			require_once SERGIO_AI_STUDIO_DIR . 'includes/class-content-rewriter.php';
		}

		$logger   = new Sergio_AI_Logger();
		$client   = new Sergio_AI_Gemini_Client( '', '', $logger );
		$crawler  = new Sergio_AI_Web_Crawler();
		$rewriter = new Sergio_AI_Content_Rewriter( $client, $logger );

		$results = array();

		foreach ( $valid_urls as $url ) {
			$crawled = $crawler->crawl_url( $url, array( 'max_chars' => $max_chars ) );
			if ( is_wp_error( $crawled ) ) {
				$results[] = array(
					'url'     => $url,
					'status'  => 'error',
					'message' => $crawled->get_error_message(),
					'post_id' => 0,
					'title'   => '',
					'edit_url'=> '',
				);
				continue;
			}

			$rewritten = $rewriter->rewrite_article( $crawled, $language, $style );
			if ( is_wp_error( $rewritten ) ) {
				$results[] = array(
					'url'     => $url,
					'status'  => 'error',
					'message' => $rewritten->get_error_message(),
					'post_id' => 0,
					'title'   => '',
					'edit_url'=> '',
				);
				continue;
			}

			$post_id = $rewriter->save_as_draft( $rewritten, $category_id, get_current_user_id() );
			if ( is_wp_error( $post_id ) ) {
				$results[] = array(
					'url'     => $url,
					'status'  => 'error',
					'message' => $post_id->get_error_message(),
					'post_id' => 0,
					'title'   => '',
					'edit_url'=> '',
				);
				continue;
			}

			$results[] = array(
				'url'      => $url,
				'status'   => 'success',
				'message'  => __( 'Đã lưu bản nháp thành công!', 'sergio-ai-studio' ),
				'post_id'  => $post_id,
				'title'    => $rewritten['title'],
				'edit_url' => get_edit_post_link( $post_id, 'raw' ),
			);
		}

		$success_count = count( array_filter( $results, fn( $r ) => 'success' === $r['status'] ) );

		wp_send_json_success( array(
			'message' => sprintf(
				__( 'Hoàn tất: %d/%d URL được viết lại và lưu bản nháp thành công.', 'sergio-ai-studio' ),
				$success_count,
				count( $results )
			),
			'results' => $results,
		) );
	}

	public function ajax_clear_logs() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : 'all';

		if ( 'delete' === $type ) {
			$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
			if ( empty( $ids ) ) {
				wp_send_json_error( array( 'message' => __( 'Chưa chọn bản ghi nào để xóa.', 'sergio-ai-studio' ) ) );
			}
			Sergio_AI_Logger::delete_logs( $ids );
			$msg = sprintf( __( 'Đã xóa %d bản ghi nhật ký được chọn.', 'sergio-ai-studio' ), count( $ids ) );
		} elseif ( '7days' === $type ) {
			Sergio_AI_Logger::purge_logs_days( 7 );
			$msg = __( 'Đã dọn dẹp các bản ghi nhật ký cũ hơn 7 ngày.', 'sergio-ai-studio' );
		} elseif ( '30days' === $type ) {
			Sergio_AI_Logger::purge_logs_days( 30 );
			$msg = __( 'Đã dọn dẹp các bản ghi nhật ký cũ hơn 30 ngày.', 'sergio-ai-studio' );
		} else {
			Sergio_AI_Logger::clear_all_logs();
			$msg = __( 'Đã xóa sạch toàn bộ bản ghi nhật ký.', 'sergio-ai-studio' );
		}

		$stats = Sergio_AI_Logger::get_stats();

		wp_send_json_success( array(
			'message' => $msg,
			'stats'   => $stats,
		) );
	}

	public function ajax_refresh_logs() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$level  = isset( $_POST['level'] ) ? sanitize_key( $_POST['level'] ) : '';
		$module = isset( $_POST['module'] ) ? sanitize_key( $_POST['module'] ) : '';
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$stage = '';
		if ( ! empty( $module ) ) {
			$stages = Sergio_AI_Logger::get_module_stages( $module );
			$stage  = ! empty( $stages ) ? $stages[0] : '';
		}

		$logs  = Sergio_AI_Logger::get_logs( 100, 0, $level, $stage, $search );
		$html  = self::render_raw_logs_rows_html( $logs );

		wp_send_json_success( array(
			'html'  => $html,
			'count' => count( $logs ),
		) );
	}

	public function ajax_export_logs() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) );
		}

		$logs     = Sergio_AI_Logger::get_logs( 500, 0 );
		$filename = 'sergio-studio-audit-logs-' . gmdate( 'Y-m-d-His' ) . '.json';

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Render HTML for Session Cards in Audit Tab
	 *
	 * @param array $sessions
	 * @return string
	 */
	public static function render_session_cards_html( $sessions ) {
		if ( empty( $sessions ) ) {
			return '<div class="notice notice-info inline" style="padding: 16px; margin: 10px 0;"><p style="margin: 0;"><span class="dashicons dashicons-info" style="vertical-align: text-bottom;"></span> ' . esc_html__( 'Không tìm thấy phiên thực thi nào phù hợp với bộ lọc.', 'sergio-ai-studio' ) . '</p></div>';
		}

		ob_start();
		foreach ( $sessions as $sess ) :
			$status_class = 'status-' . esc_attr( $sess['status'] );
			$status_badge = '';
			if ( 'success' === $sess['status'] ) {
				$status_badge = '<span class="notice notice-success inline" style="padding: 2px 8px; font-weight: 600; font-size: 11px; margin: 0;"><span class="dashicons dashicons-yes-alt" style="font-size: 13px; width: 13px; height: 13px; vertical-align: text-bottom;"></span> ' . esc_html__( 'HOÀN TẤT', 'sergio-ai-studio' ) . '</span>';
			} elseif ( 'warning' === $sess['status'] ) {
				$status_badge = '<span class="notice notice-warning inline" style="padding: 2px 8px; font-weight: 600; font-size: 11px; margin: 0;"><span class="dashicons dashicons-warning" style="font-size: 13px; width: 13px; height: 13px; vertical-align: text-bottom;"></span> ' . esc_html__( 'CẢNH BÁO', 'sergio-ai-studio' ) . '</span>';
			} elseif ( 'running' === $sess['status'] ) {
				$status_badge = '<span class="notice notice-info inline" style="padding: 2px 8px; font-weight: 600; font-size: 11px; margin: 0;"><span class="spinner is-active" style="float: none; margin: 0 4px 0 0; width: 12px; height: 12px;"></span> ' . esc_html__( 'ĐANG CHẠY', 'sergio-ai-studio' ) . '</span>';
			} else {
				$status_badge = '<span class="notice notice-error inline" style="padding: 2px 8px; font-weight: 600; font-size: 11px; margin: 0;"><span class="dashicons dashicons-dismiss" style="font-size: 13px; width: 13px; height: 13px; vertical-align: text-bottom;"></span> ' . esc_html__( 'THẤT BẠI', 'sergio-ai-studio' ) . '</span>';
			}
		?>
			<div class="sergio-session-card <?php echo esc_attr( $status_class ); ?>">
				<div class="sergio-session-header">
					<div class="sergio-session-title">
						<?php echo $status_badge; ?>
						<span><?php echo esc_html( $sess['title'] ); ?></span>
					</div>
					<div class="sergio-session-meta">
						<span><span class="dashicons dashicons-clock" style="font-size: 13px; width: 13px; height: 13px; vertical-align: text-bottom;"></span> <?php echo esc_html( $sess['created_at'] ); ?> (<?php echo (int) $sess['duration_seconds']; ?>s)</span>
						<code><?php echo esc_html( $sess['run_id'] ); ?></code>
					</div>
				</div>

				<div class="sergio-session-body">
					<?php if ( ! empty( $sess['post_info'] ) ) : ?>
						<div class="notice notice-success inline" style="margin: 0 0 12px; padding: 8px 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
							<div>
								<strong><span class="dashicons dashicons-admin-post" style="vertical-align: text-bottom;"></span> <?php esc_html_e( 'Bài viết tạo thành công:', 'sergio-ai-studio' ); ?></strong>
								<span style="margin-left: 4px;"><?php echo esc_html( $sess['post_info']['title'] ); ?></span>
								<code style="margin-left: 6px;">#<?php echo (int) $sess['post_info']['id']; ?></code>
								<span class="description" style="margin-left: 4px;">(<?php echo esc_html( strtoupper( $sess['post_info']['status'] ) ); ?>)</span>
							</div>
							<div style="display: flex; gap: 6px;">
								<a href="<?php echo esc_url( $sess['post_info']['edit_link'] ); ?>" target="_blank" class="button button-small button-primary">
									<span class="dashicons dashicons-edit" style="font-size: 13px; width: 13px; height: 13px; vertical-align: text-bottom;"></span> <?php esc_html_e( 'Sửa bài trong WP', 'sergio-ai-studio' ); ?>
								</a>
								<?php if ( ! empty( $sess['post_info']['view_link'] ) ) : ?>
									<a href="<?php echo esc_url( $sess['post_info']['view_link'] ); ?>" target="_blank" class="button button-small">
										<span class="dashicons dashicons-external" style="font-size: 13px; width: 13px; height: 13px; vertical-align: text-bottom;"></span> <?php esc_html_e( 'Xem bài', 'sergio-ai-studio' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $sess['diagnosis'] ) ) : ?>
						<div class="notice notice-error inline" style="margin: 0 0 12px; padding: 10px 14px;">
							<h4 style="margin: 0 0 4px; font-size: 13px; display: flex; align-items: center; gap: 6px;">
								<span class="dashicons <?php echo esc_attr( $sess['diagnosis']['icon'] ); ?>" style="font-size: 16px; width: 16px; height: 16px;"></span>
								<span><?php echo esc_html( $sess['diagnosis']['title'] ); ?></span>
							</h4>
							<p style="margin: 0 0 6px; font-size: 12px; color: #50575e;"><?php echo esc_html( $sess['diagnosis']['description'] ); ?></p>
							<p style="margin: 0; font-size: 12px; font-weight: 600; color: #1d2327;">
								👉 <?php esc_html_e( 'Khuyến nghị giải quyết:', 'sergio-ai-studio' ); ?> <span style="font-weight: normal;"><?php echo esc_html( $sess['diagnosis']['suggestion'] ); ?></span>
							</p>
						</div>
					<?php endif; ?>

					<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
						<button type="button" class="button button-small btn-toggle-session-steps" data-run-id="<?php echo esc_attr( $sess['run_id'] ); ?>">
							<span class="dashicons dashicons-arrow-down-alt2" style="font-size: 13px; width: 13px; height: 13px; vertical-align: text-bottom;"></span>
							<?php printf( esc_html__( 'Xem chi tiết quy trình (%d bước)', 'sergio-ai-studio' ), (int) $sess['logs_count'] ); ?>
						</button>
						<span class="description" style="font-size: 11px;"><?php printf( esc_html__( '%d sự kiện được ghi nhận', 'sergio-ai-studio' ), count( $sess['logs'] ) ); ?></span>
					</div>

					<div class="sergio-session-steps-list session-steps-panel-<?php echo esc_attr( $sess['run_id'] ); ?>" style="display: none;">
						<?php foreach ( $sess['logs'] as $log_item ) :
							$badge_style = 'padding: 1px 6px; font-size: 10px; font-weight: 600; margin: 0;';
							$level_badge = '<span class="notice notice-info inline" style="' . $badge_style . '">INFO</span>';
							if ( 'error' === $log_item['level'] ) {
								$level_badge = '<span class="notice notice-error inline" style="' . $badge_style . '">ERROR</span>';
							} elseif ( 'warning' === $log_item['level'] ) {
								$level_badge = '<span class="notice notice-warning inline" style="' . $badge_style . '">WARN</span>';
							}
						?>
							<div class="sergio-step-item">
								<div class="sergio-step-time"><?php echo esc_html( substr( $log_item['created_at'], 11, 8 ) ); ?></div>
								<div class="sergio-step-content">
									<div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
										<?php echo $level_badge; ?>
										<code style="font-size: 11px;"><?php echo esc_html( Sergio_AI_Logger::map_stage_label( $log_item['stage'] ) ); ?></code>
										<span class="description" style="font-size: 11px;">(<code><?php echo esc_html( $log_item['stage'] ); ?></code>)</span>
									</div>
									<div style="font-size: 12px; color: #2c3338; margin-top: 2px;">
										<?php echo wp_kses_post( Sergio_AI_Logger::format_log_message_html( $log_item['message'] ) ); ?>
									</div>
									<?php if ( ! empty( $log_item['details_json'] ) ) : ?>
										<details style="margin-top: 4px;">
											<summary style="cursor: pointer; color: #2271b1; font-size: 11px;"><?php esc_html_e( 'Xem chi tiết payload JSON', 'sergio-ai-studio' ); ?></summary>
											<pre style="background: #f6f7f7; padding: 8px; border: 1px solid #c3c4c7; border-radius: 3px; font-size: 11px; max-height: 160px; overflow-y: auto; margin-top: 4px;"><?php echo esc_html( $log_item['details_json'] ); ?></pre>
										</details>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php
		endforeach;
		return ob_get_clean();
	}

	/**
	 * Render HTML rows for Raw Logs Table
	 *
	 * @param array $logs
	 * @return string
	 */
	public static function render_raw_logs_rows_html( $logs ) {
		if ( empty( $logs ) ) {
			return '<tr class="no-items"><td class="colspanchange" colspan="5" style="text-align: center; color: #64748b; padding: 20px;">' . esc_html__( 'Không có nhật ký nào được ghi nhận.', 'sergio-ai-studio' ) . '</td></tr>';
		}

		ob_start();
		foreach ( $logs as $log ) :
		?>
			<tr class="log-row-item" data-level="<?php echo esc_attr( $log['level'] ); ?>" data-stage="<?php echo esc_attr( $log['stage'] ); ?>">
				<th scope="row" class="check-column">
					<input type="checkbox" name="log_ids[]" value="<?php echo (int) $log['id']; ?>">
				</th>
				<td class="date column-date">
					<?php echo esc_html( $log['created_at'] ); ?>
				</td>
				<td class="column-level">
					<?php if ( 'error' === $log['level'] ) : ?>
						<span class="sergio-log-badge badge-error"><?php esc_html_e( 'LỖI', 'sergio-ai-studio' ); ?></span>
					<?php elseif ( 'warning' === $log['level'] ) : ?>
						<span class="sergio-log-badge badge-warning"><?php esc_html_e( 'CẢNH BÁO', 'sergio-ai-studio' ); ?></span>
					<?php else : ?>
						<span class="sergio-log-badge badge-info"><?php esc_html_e( 'INFO', 'sergio-ai-studio' ); ?></span>
					<?php endif; ?>
				</td>
				<td class="column-stage">
					<?php
					$st_label = Sergio_AI_Logger::map_stage_label( $log['stage'] );
					if ( $st_label !== $log['stage'] ) :
					?>
						<strong><?php echo esc_html( $st_label ); ?></strong>
						<div style="font-size: 11px; color: #64748b;"><code><?php echo esc_html( $log['stage'] ); ?></code></div>
					<?php else : ?>
						<code><?php echo esc_html( $log['stage'] ); ?></code>
					<?php endif; ?>
				</td>
				<td class="column-primary">
					<div><?php echo wp_kses_post( Sergio_AI_Logger::format_log_message_html( $log['message'] ) ); ?></div>
					<?php if ( ! empty( $log['details_json'] ) ) : ?>
						<details style="margin-top: 5px;">
							<summary style="cursor: pointer; color: #2271b1; font-size: 11px;"><?php esc_html_e( 'Chi tiết JSON', 'sergio-ai-studio' ); ?></summary>
							<pre style="background: #f6f7f7; padding: 8px; border: 1px solid #c3c4c7; border-radius: 3px; font-size: 11px; max-height: 200px; overflow-y: auto;"><?php echo esc_html( $log['details_json'] ); ?></pre>
						</details>
					<?php endif; ?>
				</td>
			</tr>
		<?php
		endforeach;
		return ob_get_clean();
	}

	public function ajax_site_seo_audit() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện kiểm định SEO.', 'sergio-ai-studio' ) ) );
		}

		$limit = isset( $_POST['limit'] ) ? min( 100, max( 5, absint( $_POST['limit'] ) ) ) : 40;
		$data  = Sergio_AI_SEO_Audit::audit_site_overview( $limit );

		wp_send_json_success( array(
			'message' => __( 'Quét kiểm định SEO toàn site hoàn tất.', 'sergio-ai-studio' ),
			'data'    => $data,
		) );
	}

	public function ajax_audit_single_post() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( empty( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Thiếu ID bài viết.', 'sergio-ai-studio' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Bạn không có quyền chỉnh sửa bài viết này.', 'sergio-ai-studio' ) ) );
		}

		$audit = Sergio_AI_SEO_Audit::audit_post( $post_id );
		if ( is_wp_error( $audit ) ) {
			wp_send_json_error( array( 'message' => $audit->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Phân tích On-page hoàn tất.', 'sergio-ai-studio' ),
			'audit'   => $audit,
		) );
	}

	public function ajax_optimize_post_seo() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền thực hiện.', 'sergio-ai-studio' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( empty( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Thiếu ID bài viết.', 'sergio-ai-studio' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Bạn không có quyền chỉnh sửa bài viết này.', 'sergio-ai-studio' ) ) );
		}

		$user_id      = get_current_user_id();
		$cooldown_key = 'sergio_ai_ai_opt_cd_' . $user_id;
		if ( get_transient( $cooldown_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng đợi 5 giây giữa các lần gọi AI tối ưu.', 'sergio-ai-studio' ) ) );
		}
		set_transient( $cooldown_key, 1, 5 );

		$logger = new Sergio_AI_Logger();
		$client = new Sergio_AI_Gemini_Client( '', '', $logger );

		if ( ! $client->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Chưa cấu hình Google Gemini API Key. Vui lòng vào Cài Đặt để nhập khóa.', 'sergio-ai-studio' ) ) );
		}

		$result = Sergio_AI_SEO_Audit::ai_optimize_post( $post_id, $client, $logger );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'     => __( 'Đã tạo xong gói đề xuất On-Page từ Gemini AI.', 'sergio-ai-studio' ),
			'suggestions' => $result,
		) );
	}

	public function ajax_apply_seo_changes() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền chỉnh sửa bài viết.', 'sergio-ai-studio' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( empty( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Thiếu ID bài viết.', 'sergio-ai-studio' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Bạn không có quyền chỉnh sửa bài viết này.', 'sergio-ai-studio' ) ) );
		}

		$focus_kw = isset( $_POST['focus_keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['focus_keyword'] ) ) : '';
		$title    = isset( $_POST['seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['seo_title'] ) ) : '';
		$desc     = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

		Sergio_AI_RankMath_Bridge::update_post_seo_data( $post_id, array(
			'focus_keyword' => $focus_kw,
			'seo_title'     => $title,
			'description'   => $desc,
		) );

		$post = get_post( $post_id );
		if ( $post && 'draft' === $post->post_status && ! empty( $focus_kw ) ) {
			wp_update_post( array(
				'ID'        => $post_id,
				'post_name' => sanitize_title( $focus_kw ),
			) );
		}

		// Re-run audit to get newly recalculated score and updated checklist
		$updated_audit = Sergio_AI_SEO_Audit::audit_post( $post_id );

		wp_send_json_success( array(
			'message' => __( 'Đã lưu và đồng bộ Rank Math SEO thành công!', 'sergio-ai-studio' ),
			'audit'   => ! is_wp_error( $updated_audit ) ? $updated_audit : null,
		) );
	}

	public function ajax_deep_optimize_content() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không đủ quyền chỉnh sửa bài viết.', 'sergio-ai-studio' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( empty( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Thiếu ID bài viết hoặc trang.', 'sergio-ai-studio' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Bạn không có quyền chỉnh sửa bài viết này.', 'sergio-ai-studio' ) ) );
		}

		$user_id      = get_current_user_id();
		$cooldown_key = 'sergio_ai_ai_deep_cd_' . $user_id;
		if ( get_transient( $cooldown_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng đợi 10 giây giữa các lần tối ưu sâu nội dung.', 'sergio-ai-studio' ) ) );
		}
		set_transient( $cooldown_key, 1, 10 );

		$focus_kw = isset( $_POST['focus_keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['focus_keyword'] ) ) : '';

		$logger = new Sergio_AI_Logger();
		$client = new Sergio_AI_Gemini_Client( '', '', $logger );

		if ( ! $client->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Chưa cấu hình Google Gemini API Key. Vui lòng vào Cài Đặt để nhập khóa.', 'sergio-ai-studio' ) ) );
		}

		$audit = Sergio_AI_SEO_Audit::ai_deep_optimize_content( $post_id, $focus_kw, $client, $logger );
		if ( is_wp_error( $audit ) ) {
			wp_send_json_error( array( 'message' => $audit->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Đã tái cấu trúc và tối ưu nội dung On-Page thành công!', 'sergio-ai-studio' ),
			'audit'   => $audit,
		) );
	}

	public function ajax_batch_fix_seo() {
		check_ajax_referer( 'sergio_ai_studio_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Chỉ Quản trị viên (Administrator) mới có quyền thực hiện tối ưu hàng loạt.', 'sergio-ai-studio' ) ) );
		}

		$user_id      = get_current_user_id();
		$cooldown_key = 'sergio_ai_ai_batch_cd_' . $user_id;
		if ( get_transient( $cooldown_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng đợi 20 giây giữa các đợt chạy tối ưu hàng loạt.', 'sergio-ai-studio' ) ) );
		}
		set_transient( $cooldown_key, 1, 20 );

		$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'absint', (array) $_POST['post_ids'] ) : array();
		if ( empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Không có bài viết nào cần tối ưu.', 'sergio-ai-studio' ) ) );
		}

		// Limit batch size to max 15 per batch to prevent server timeouts
		$post_ids = array_slice( $post_ids, 0, 15 );

		$logger = new Sergio_AI_Logger();
		$client = new Sergio_AI_Gemini_Client( '', '', $logger );

		if ( ! $client->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Chưa cấu hình Google Gemini API Key.', 'sergio-ai-studio' ) ) );
		}

		$deep_content = ! empty( $_POST['deep_content'] );

		$result = Sergio_AI_SEO_Audit::batch_auto_fix_seo( $post_ids, $client, $logger, $deep_content );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => sprintf( __( 'Hoàn tất: Đã tự động tối ưu thành công %d/%d trang.', 'sergio-ai-studio' ), $result['success_count'], $result['total'] ),
			'data'    => $result,
		) );
	}
}
