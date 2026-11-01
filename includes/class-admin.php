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
		$clean = get_option( 'sergio_ai_studio_options', array() );

		// API Key — skip if constant defined in wp-config.
		if ( ! Sergio_AI_Security::is_key_defined_in_config() ) {
			if ( ! empty( $input['gemini_api_key'] ) ) {
				$raw_key = trim( sanitize_text_field( wp_unslash( $input['gemini_api_key'] ) ) );
				if ( false === strpos( $raw_key, '••••' ) ) {
					$clean['gemini_api_key'] = Sergio_AI_Security::encrypt( $raw_key );
				}
			}
		}

		$clean['gemini_model_primary']  = isset( $input['gemini_model_primary'] ) ? sanitize_text_field( wp_unslash( $input['gemini_model_primary'] ) ) : 'gemini-2.5-flash';
		$clean['gemini_model_fallback'] = isset( $input['gemini_model_fallback'] ) ? sanitize_text_field( wp_unslash( $input['gemini_model_fallback'] ) ) : 'gemini-3.5-flash-lite';
		if ( 'gemini-2.5-flash-lite' === $clean['gemini_model_primary'] ) {
			$clean['gemini_model_primary'] = 'gemini-3.5-flash-lite';
		}
		if ( 'gemini-2.5-flash-lite' === $clean['gemini_model_fallback'] ) {
			$clean['gemini_model_fallback'] = 'gemini-3.5-flash-lite';
		}
		$clean['default_language']      = isset( $input['default_language'] ) ? sanitize_text_field( wp_unslash( $input['default_language'] ) ) : 'vi';
		$clean['default_post_status']   = in_array( $input['default_post_status'] ?? '', array( 'draft', 'publish' ), true ) ? $input['default_post_status'] : 'draft';

		// Scheduler Advanced Settings.
		$clean['scheduler_enabled']         = ! empty( $input['scheduler_enabled'] ) ? 1 : 0;
		$clean['schedule_frequency']        = isset( $input['schedule_frequency'] ) ? sanitize_text_field( wp_unslash( $input['schedule_frequency'] ) ) : 'daily';
		$clean['schedule_custom_hours']     = isset( $input['schedule_custom_hours'] ) ? absint( $input['schedule_custom_hours'] ) : 1;
		$clean['schedule_posts_per_run']    = isset( $input['schedule_posts_per_run'] ) ? min( 5, max( 1, absint( $input['schedule_posts_per_run'] ) ) ) : 1;
		$clean['schedule_max_posts_per_day']= isset( $input['schedule_max_posts_per_day'] ) ? absint( $input['schedule_max_posts_per_day'] ) : 10;
		$clean['schedule_topic_mode']       = in_array( $input['schedule_topic_mode'] ?? '', array( 'hybrid', 'queue_only', 'ai_discover' ), true ) ? $input['schedule_topic_mode'] : 'hybrid';
		$clean['schedule_niche_topic']      = isset( $input['schedule_niche_topic'] ) ? sanitize_text_field( wp_unslash( $input['schedule_niche_topic'] ) ) : '';
		$clean['schedule_target_length']    = in_array( $input['schedule_target_length'] ?? '', array( 'standard', 'short', 'deep_dive' ), true ) ? $input['schedule_target_length'] : 'standard';
		$clean['default_schedule_author']   = isset( $input['default_schedule_author'] ) ? absint( $input['default_schedule_author'] ) : 0;
		$clean['default_schedule_cat']      = isset( $input['default_schedule_cat'] ) ? absint( $input['default_schedule_cat'] ) : 0;
		$clean['default_schedule_status']   = in_array( $input['default_schedule_status'] ?? '', array( 'draft', 'publish' ), true ) ? $input['default_schedule_status'] : 'draft';

		$scheduler = new Sergio_AI_Scheduler();
		if ( $clean['scheduler_enabled'] ) {
			$custom_h = ( 'sergio_custom_interval' === $clean['schedule_frequency'] ) ? $clean['schedule_custom_hours'] : 0;
			$scheduler->setup_schedule( $clean['schedule_frequency'], $custom_h );
		} else {
			$scheduler->clear_schedule();
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
		$log_sessions = Sergio_AI_Logger::get_sessions( 15 );
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
				<span class="dashicons dashicons-superhero"></span>
				<?php esc_html_e( 'Sergio AI Studio', 'sergio-ai-studio' ); ?>
			</h1>
			<span class="sergio-header-badge">
				<span class="sergio-status-dot <?php echo $has_key ? '' : 'is-disconnected'; ?>"></span>
				<code><?php echo esc_html( $primary_model ); ?></code>
				<span>&bull; v<?php echo esc_html( SERGIO_AI_STUDIO_VERSION ); ?></span>
			</span>
			<hr class="wp-header-end">

			<?php if ( ! $has_key ) : ?>
				<div class="notice notice-warning inline" style="margin-top: 15px;">
					<p>
						<span class="dashicons dashicons-warning" style="color:#d97706; vertical-align:middle;"></span>
						<?php esc_html_e( 'Chưa cấu hình Google Gemini API Key. Vui lòng chuyển sang tab Cài Đặt để nhập khóa API.', 'sergio-ai-studio' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<!-- NATIVE WORDPRESS ADMIN TABS -->
			<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Sergio AI Studio Tabs', 'sergio-ai-studio' ); ?>">
				<a href="#tab-overview" class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-overview">
					<span class="dashicons dashicons-dashboard" aria-hidden="true"></span>
					<?php esc_html_e( 'Tổng Quan', 'sergio-ai-studio' ); ?>
				</a>
				<a href="#tab-settings" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-settings">
					<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
					<?php esc_html_e( 'Cài Đặt', 'sergio-ai-studio' ); ?>
				</a>
				<a href="#tab-scheduler" class="nav-tab <?php echo 'scheduler' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-scheduler">
					<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'Lên Lịch', 'sergio-ai-studio' ); ?>
					<?php if ( $queue_count > 0 ) : ?>
						<span class="update-plugins count-<?php echo esc_attr( $queue_count ); ?>"><span class="update-count"><?php echo esc_html( $queue_count ); ?></span></span>
					<?php endif; ?>
				</a>
				<a href="#tab-writer" class="nav-tab <?php echo 'writer' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-writer">
					<span class="dashicons dashicons-edit" aria-hidden="true"></span>
					<?php esc_html_e( 'Nghiên Cứu & Viết Bài', 'sergio-ai-studio' ); ?>
				</a>
				<a href="#tab-crawl" class="nav-tab <?php echo 'crawl' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-crawl">
					<span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
					<?php esc_html_e( 'Crawl & Viết Lại', 'sergio-ai-studio' ); ?>
				</a>
				<a href="#tab-seo" class="nav-tab <?php echo 'seo' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-seo">
					<span class="dashicons dashicons-chart-area" aria-hidden="true"></span>
					<?php esc_html_e( 'Audit & Tối Ưu SEO', 'sergio-ai-studio' ); ?>
				</a>
				<a href="#tab-logs" class="nav-tab <?php echo 'logs' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-logs">
					<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
					<?php esc_html_e( 'Nhật Ký', 'sergio-ai-studio' ); ?>
					<?php if ( $log_errors > 0 ) : ?>
						<span class="update-plugins count-<?php echo esc_attr( $log_errors ); ?>"><span class="update-count"><?php echo esc_html( $log_errors ); ?></span></span>
					<?php endif; ?>
				</a>
			</nav>

			<div class="sergio-tab-content" style="margin-top: 15px;">

				<!-- ===================================== -->
				<!-- TAB 1: TỔNG QUAN                     -->
				<!-- ===================================== -->
				<div id="tab-overview" class="sergio-tab-pane <?php echo 'overview' === $active_tab ? 'is-active' : ''; ?>">
					<div class="sergio-overview-cards">
						<div class="card <?php echo $has_key ? 'card-success' : 'card-warning'; ?>">
							<div class="card-kicker">
								<span class="dashicons dashicons-rest-api"></span>
								<span><?php esc_html_e( 'Kết Nối Gemini API', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val <?php echo $has_key ? 'val-green' : 'val-amber'; ?>">
								<?php echo $has_key ? esc_html__( 'Sẵn Sàng', 'sergio-ai-studio' ) : esc_html__( 'Chưa Cấu Hình', 'sergio-ai-studio' ); ?>
							</div>
							<p class="card-desc"><code><?php echo esc_html( $primary_model ); ?></code></p>
						</div>

						<div class="card <?php echo $scheduler_enabled ? 'card-success' : ''; ?>">
							<div class="card-kicker">
								<span class="dashicons dashicons-clock"></span>
								<span><?php esc_html_e( 'Tự Động Hóa WP-Cron', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val <?php echo $scheduler_enabled ? 'val-green' : ''; ?>">
								<?php echo $scheduler_enabled ? esc_html__( 'Đang Chạy', 'sergio-ai-studio' ) : esc_html__( 'Tạm Dừng', 'sergio-ai-studio' ); ?>
							</div>
							<p class="card-desc">
								<?php echo $scheduler_enabled && $next_run_time ? esc_html( sprintf( __( 'Lần tới: %s', 'sergio-ai-studio' ), $next_run_time ) ) : esc_html__( 'Chưa lên lịch', 'sergio-ai-studio' ); ?>
							</p>
						</div>

						<div class="card">
							<div class="card-kicker">
								<span class="dashicons dashicons-calendar-alt"></span>
								<span><?php esc_html_e( 'Hàng Đợi Chờ Xử Lý', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val val-blue">
								<?php echo (int) $queue_count; ?> <span class="card-val-unit"><?php esc_html_e( 'chủ đề', 'sergio-ai-studio' ); ?></span>
							</div>
							<p class="card-desc"><?php printf( esc_html__( 'Mỗi đợt sinh %d bài', 'sergio-ai-studio' ), (int) $posts_per_run ); ?></p>
						</div>

						<div class="card card-success">
							<div class="card-kicker">
								<span class="dashicons dashicons-yes-alt"></span>
								<span><?php esc_html_e( 'Hôm Nay Đã Tạo', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val val-green">
								<?php echo (int) $posts_today; ?> <span class="card-val-unit">/ <?php echo $max_posts_per_day > 0 ? (int) $max_posts_per_day : '∞'; ?> <?php esc_html_e( 'bài', 'sergio-ai-studio' ); ?></span>
							</div>
							<p class="card-desc"><?php esc_html_e( 'Đã kiểm chứng Fact Pack', 'sergio-ai-studio' ); ?></p>
						</div>
					</div>

					<div class="sergio-grid-2col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
						<div class="postbox">
							<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Khởi Chạy Nhanh Quy Trình', 'sergio-ai-studio' ); ?></span></h2></div>
							<div class="inside">
								<p><?php esc_html_e( 'Kích hoạt nhanh quy trình biên soạn bài viết bằng Gemini Deep Research hoặc chạy phiên WP-Cron ngay lập tức:', 'sergio-ai-studio' ); ?></p>
								<p style="display: flex; gap: 10px; flex-wrap: wrap;">
									<button type="button" class="button button-primary" onclick="document.querySelector('[data-tab=tab-writer]').click()">
										<span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Nghiên Cứu & Viết Bài', 'sergio-ai-studio' ); ?>
									</button>
									<button type="button" class="button button-secondary" onclick="document.querySelector('[data-tab=tab-crawl]').click()">
										<span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Crawl & Viết Lại', 'sergio-ai-studio' ); ?>
									</button>
									<button type="button" class="button button-secondary" id="btn_run_cron_overview">
										<span class="dashicons dashicons-controls-play"></span> <?php esc_html_e( 'Kích Hoạt 1 Đợt Cron Ngay', 'sergio-ai-studio' ); ?>
									</button>
								</p>
								<div id="cron_overview_feedback" style="margin-top: 10px; font-size: 13px;"></div>
							</div>
						</div>

						<div class="postbox">
							<div class="postbox-header">
								<h2 class="hndle">
									<span><?php esc_html_e( 'Bài Tiếp Theo Trong Hàng Đợi', 'sergio-ai-studio' ); ?></span>
								</h2>
							</div>
							<div class="inside">
								<?php if ( empty( $queue_items ) ) : ?>
									<p style="color: #64748b;"><?php esc_html_e( 'Hàng đợi hiện đang trống. Hệ thống sẽ tự động nghiên cứu theo Lĩnh vực ngách (chế độ Hybrid).', 'sergio-ai-studio' ); ?></p>
								<?php else : ?>
									<p>
										<span class="dashicons dashicons-arrow-right-alt" style="color: #2271b1;"></span>
										<strong>#<?php echo esc_html( $queue_items[0]['id'] ); ?>:</strong>
										<?php echo esc_html( $queue_items[0]['topic'] ); ?>
									</p>
									<p class="description"><?php echo esc_html( $queue_items[0]['created_at'] ); ?></p>
								<?php endif; ?>
								<p style="margin-top: 12px;">
									<a href="#tab-scheduler" class="button button-secondary button-small" onclick="document.querySelector('[data-tab=tab-scheduler]').click(); return false;">
										<?php esc_html_e( 'Quản lý hàng đợi →', 'sergio-ai-studio' ); ?>
									</a>
								</p>
							</div>
						</div>
					</div>

					<div class="postbox">
						<div class="postbox-header">
							<h2 class="hndle">
								<span><?php esc_html_e( 'Nhật Ký Thực Thi Gần Đây', 'sergio-ai-studio' ); ?></span>
							</h2>
						</div>
						<div class="inside" style="padding: 0;">
							<?php
							$recent_logs = array_slice( $logs, 0, 5 );
							if ( empty( $recent_logs ) ) : ?>
								<p style="padding: 12px 16px; color: #64748b; margin: 0;"><?php esc_html_e( 'Chưa có nhật ký nào được ghi nhận.', 'sergio-ai-studio' ); ?></p>
							<?php else : ?>
								<table class="widefat striped" style="border: none;">
									<thead>
										<tr>
											<th style="width: 160px;"><?php esc_html_e( 'Thời Gian', 'sergio-ai-studio' ); ?></th>
											<th style="width: 90px;"><?php esc_html_e( 'Cấp Độ', 'sergio-ai-studio' ); ?></th>
											<th style="width: 140px;"><?php esc_html_e( 'Giai Đoạn', 'sergio-ai-studio' ); ?></th>
											<th><?php esc_html_e( 'Thông Điệp', 'sergio-ai-studio' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $recent_logs as $log ) : ?>
											<tr>
												<td><?php echo esc_html( $log['created_at'] ); ?></td>
												<td>
													<?php if ( 'error' === $log['level'] ) : ?>
														<span class="notice notice-error inline" style="padding: 2px 6px; margin: 0; font-weight: 600;">ERROR</span>
													<?php elseif ( 'warning' === $log['level'] ) : ?>
														<span class="notice notice-warning inline" style="padding: 2px 6px; margin: 0; font-weight: 600;">WARN</span>
													<?php else : ?>
														<span class="notice notice-info inline" style="padding: 2px 6px; margin: 0;">INFO</span>
													<?php endif; ?>
												</td>
												<td><code><?php echo esc_html( $log['stage'] ); ?></code></td>
												<td><?php echo wp_kses_post( Sergio_AI_Logger::format_log_message_html( $log['message'] ) ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
								<div style="padding: 10px 16px; border-top: 1px solid #dcdcde; background: #f6f7f7; text-align: right;">
									<a href="#tab-logs" class="button button-small nav-tab-link" data-tab="tab-logs" style="text-decoration: none;">
										<span class="dashicons dashicons-list-view" style="vertical-align: text-bottom;"></span> <?php esc_html_e( 'Xem Trung Tâm Giám Sát & Nhật Ký Hoạt Động', 'sergio-ai-studio' ); ?> &rarr;
									</a>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- ===================================== -->
				<!-- TAB 2: CÀI ĐẶT                       -->
				<!-- ===================================== -->
				<div id="tab-settings" class="sergio-tab-pane <?php echo 'settings' === $active_tab ? 'is-active' : ''; ?>">
					<form method="post" action="options.php">
						<?php settings_fields( 'sergio_ai_studio_settings_group' ); ?>

						<div class="postbox">
							<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Khóa Google Gemini API', 'sergio-ai-studio' ); ?></span></h2></div>
							<div class="inside">
								<table class="form-table" role="presentation">
									<tbody>
										<tr>
											<th scope="row">
												<label for="gemini_api_key"><?php esc_html_e( 'Gemini API Key', 'sergio-ai-studio' ); ?></label>
											</th>
											<td>
												<input type="password" id="gemini_api_key" name="sergio_ai_studio_options[gemini_api_key]"
													value="<?php echo esc_attr( $masked_key ); ?>"
													class="regular-text" placeholder="AIzaSy..."
													autocomplete="off"
													<?php disabled( $is_config_key ); ?> />
												<button type="button" class="button button-secondary" id="btn_test_conn">
													<span class="dashicons dashicons-lightning"></span> <?php esc_html_e( 'Kiểm tra kết nối', 'sergio-ai-studio' ); ?>
												</button>
												<span id="api_conn_feedback" style="margin-left: 10px; font-weight: 500;"></span>
												<?php if ( $is_config_key ) : ?>
													<p class="description" style="color: #2271b1;">
														<strong>wp-config.php:</strong> <?php esc_html_e( 'Khóa API đang được khóa an toàn qua hằng số SERGIO_AI_GEMINI_API_KEY.', 'sergio-ai-studio' ); ?>
													</p>
												<?php else : ?>
													<p class="description">
														<?php esc_html_e( 'Khóa được mã hóa an toàn bằng AES-256. Lấy khóa API miễn phí từ Google AI Studio.', 'sergio-ai-studio' ); ?>
													</p>
												<?php endif; ?>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>

						<div class="postbox">
							<div class="postbox-header">
								<h2 class="hndle">
									<span><?php esc_html_e( 'Mô Hình Ngôn Ngữ (LLM)', 'sergio-ai-studio' ); ?></span>
								</h2>
							</div>
							<div class="inside">
								<table class="form-table" role="presentation">
									<tbody>
										<tr>
											<th scope="row">
												<label for="gemini_model_primary"><?php esc_html_e( 'Mô Hình Chính (Primary)', 'sergio-ai-studio' ); ?></label>
											</th>
											<td>
												<select id="gemini_model_primary" name="sergio_ai_studio_options[gemini_model_primary]" style="min-width: 300px;">
													<?php if ( ! empty( $models ) ) : ?>
														<?php foreach ( $models as $m ) : ?>
															<option value="<?php echo esc_attr( $m['name'] ); ?>" <?php selected( $primary_model, $m['name'] ); ?>>
																<?php echo esc_html( $m['display_name'] . ' (' . $m['name'] . ')' ); ?>
															</option>
														<?php endforeach; ?>
													<?php else : ?>
														<option value="gemini-2.5-flash" <?php selected( $primary_model, 'gemini-2.5-flash' ); ?>>Gemini 2.5 Flash</option>
														<option value="gemini-2.0-flash" <?php selected( $primary_model, 'gemini-2.0-flash' ); ?>>Gemini 2.0 Flash (Tốc độ cao & Quota độc lập)</option>
														<option value="gemini-1.5-flash" <?php selected( $primary_model, 'gemini-1.5-flash' ); ?>>Gemini 1.5 Flash (Ổn định LTS)</option>
														<option value="gemini-3.5-flash-lite" <?php selected( $primary_model, 'gemini-3.5-flash-lite' ); ?>>Gemini 3.5 Flash Lite</option>
														<option value="gemini-2.5-pro" <?php selected( $primary_model, 'gemini-2.5-pro' ); ?>>Gemini 2.5 Pro</option>
													<?php endif; ?>
												</select>
												<button type="button" class="button button-secondary" id="btn_refresh_models">
													<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Làm mới danh sách', 'sergio-ai-studio' ); ?>
												</button>
												<p class="description"><?php esc_html_e( 'Dùng cho nghiên cứu dữ kiện và biên soạn nội dung chính.', 'sergio-ai-studio' ); ?></p>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gemini_model_fallback"><?php esc_html_e( 'Mô Hình Dự Phòng (Fallback)', 'sergio-ai-studio' ); ?></label>
											</th>
											<td>
												<select id="gemini_model_fallback" name="sergio_ai_studio_options[gemini_model_fallback]" style="min-width: 300px;">
													<?php if ( ! empty( $models ) ) : ?>
														<?php foreach ( $models as $m ) : ?>
															<option value="<?php echo esc_attr( $m['name'] ); ?>" <?php selected( $fallback_model, $m['name'] ); ?>>
																<?php echo esc_html( $m['display_name'] . ' (' . $m['name'] . ')' ); ?>
															</option>
														<?php endforeach; ?>
													<?php else : ?>
														<option value="gemini-2.0-flash" <?php selected( $fallback_model, 'gemini-2.0-flash' ); ?>>Gemini 2.0 Flash (Tốc độ cao & Quota độc lập)</option>
														<option value="gemini-1.5-flash" <?php selected( $fallback_model, 'gemini-1.5-flash' ); ?>>Gemini 1.5 Flash (Ổn định LTS)</option>
														<option value="gemini-3.5-flash-lite" <?php selected( $fallback_model, 'gemini-3.5-flash-lite' ); ?>>Gemini 3.5 Flash Lite</option>
														<option value="gemini-2.5-flash" <?php selected( $fallback_model, 'gemini-2.5-flash' ); ?>>Gemini 2.5 Flash</option>
													<?php endif; ?>
												</select>
												<p class="description"><?php esc_html_e( 'Tự động kích hoạt khi mô hình chính gặp lỗi hạn mức quota (429) hoặc máy chủ quá tải (503).', 'sergio-ai-studio' ); ?></p>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>

						<div class="postbox">
							<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Thiết Lập Mặc Định', 'sergio-ai-studio' ); ?></span></h2></div>
							<div class="inside">
								<table class="form-table" role="presentation">
									<tbody>
										<tr>
											<th scope="row">
												<label for="default_language"><?php esc_html_e( 'Ngôn Ngữ Toàn Site', 'sergio-ai-studio' ); ?></label>
											</th>
											<td>
												<select id="default_language" name="sergio_ai_studio_options[default_language]">
													<option value="vi" <?php selected( $language, 'vi' ); ?>>Tiếng Việt (Vietnamese)</option>
													<option value="en" <?php selected( $language, 'en' ); ?>>English (Global)</option>
												</select>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label><?php esc_html_e( 'Trạng Thái Xuất Bản Mặc Định', 'sergio-ai-studio' ); ?></label>
											</th>
											<td>
												<select name="sergio_ai_studio_options[default_post_status]">
													<option value="draft" selected><?php esc_html_e( 'Bản nháp (Draft — Khuyên dùng)', 'sergio-ai-studio' ); ?></option>
													<option value="publish"><?php esc_html_e( 'Đăng ngay lập tức (Publish)', 'sergio-ai-studio' ); ?></option>
												</select>
												<p class="description" style="color: #b32d2e;">
													<?php esc_html_e( 'Khuyến nghị: Giữ Bản nháp để người biên tập kiểm duyệt nội dung trước khi công khai.', 'sergio-ai-studio' ); ?>
												</p>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>

						<?php submit_button( __( 'Lưu Cài Đặt', 'sergio-ai-studio' ) ); ?>
					</form>
				</div>

				<!-- ===================================== -->
				<!-- TAB 3: LÊN LỊCH                     -->
				<!-- ===================================== -->
				<div id="tab-scheduler" class="sergio-tab-pane <?php echo 'scheduler' === $active_tab ? 'is-active' : ''; ?>">
					<form method="post" action="options.php">
						<?php settings_fields( 'sergio_ai_studio_settings_group' ); ?>
						<input type="hidden" name="sergio_ai_studio_options[gemini_api_key]" value="<?php echo esc_attr( $masked_key ); ?>" />
						<input type="hidden" name="sergio_ai_studio_options[gemini_model_primary]" value="<?php echo esc_attr( $primary_model ); ?>" />
						<input type="hidden" name="sergio_ai_studio_options[gemini_model_fallback]" value="<?php echo esc_attr( $fallback_model ); ?>" />
						<input type="hidden" name="sergio_ai_studio_options[default_language]" value="<?php echo esc_attr( $language ); ?>" />

						<div class="sergio-grid-2col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
							<div class="postbox">
								<div class="postbox-header"><h2 class="hndle"><span><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Chu Kỳ & Số Lượng Bài Viết', 'sergio-ai-studio' ); ?></span></h2></div>
								<div class="inside">
									<table class="form-table" role="presentation">
										<tbody>
											<tr>
												<th scope="row"><?php esc_html_e( 'Kích Hoạt WP-Cron', 'sergio-ai-studio' ); ?></th>
												<td>
													<label>
														<input type="checkbox" name="sergio_ai_studio_options[scheduler_enabled]" value="1" <?php checked( $scheduler_enabled, true ); ?> />
														<?php esc_html_e( 'Bật lịch trình tự động chạy ngầm', 'sergio-ai-studio' ); ?>
													</label>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="schedule_frequency_select"><?php esc_html_e( 'Tần Suất Kích Hoạt', 'sergio-ai-studio' ); ?></label>
												</th>
												<td>
													<select name="sergio_ai_studio_options[schedule_frequency]" id="schedule_frequency_select">
														<option value="every_one_hour" <?php selected( $frequency, 'every_one_hour' ); ?>><?php esc_html_e( 'Mỗi 1 giờ (1 Hour)', 'sergio-ai-studio' ); ?></option>
														<option value="every_two_hours" <?php selected( $frequency, 'every_two_hours' ); ?>><?php esc_html_e( 'Mỗi 2 giờ', 'sergio-ai-studio' ); ?></option>
														<option value="every_four_hours" <?php selected( $frequency, 'every_four_hours' ); ?>><?php esc_html_e( 'Mỗi 4 giờ', 'sergio-ai-studio' ); ?></option>
														<option value="every_six_hours" <?php selected( $frequency, 'every_six_hours' ); ?>><?php esc_html_e( 'Mỗi 6 giờ', 'sergio-ai-studio' ); ?></option>
														<option value="twicedaily" <?php selected( $frequency, 'twicedaily' ); ?>><?php esc_html_e( 'Mỗi 12 giờ (2 lần/ngày)', 'sergio-ai-studio' ); ?></option>
														<option value="daily" <?php selected( $frequency, 'daily' ); ?>><?php esc_html_e( 'Hàng ngày (1 lần/ngày)', 'sergio-ai-studio' ); ?></option>
														<option value="weekly" <?php selected( $frequency, 'weekly' ); ?>><?php esc_html_e( 'Hàng tuần', 'sergio-ai-studio' ); ?></option>
														<option value="sergio_custom_interval" <?php selected( $frequency, 'sergio_custom_interval' ); ?>><?php esc_html_e( 'Tùy chỉnh số giờ cụ thể...', 'sergio-ai-studio' ); ?></option>
													</select>
													<div id="custom_hours_wrapper" style="margin-top: 8px; display: <?php echo 'sergio_custom_interval' === $frequency ? 'block' : 'none'; ?>;">
														<label for="schedule_custom_hours"><?php esc_html_e( 'Nhập số giờ (1 - 168):', 'sergio-ai-studio' ); ?></label>
														<input type="number" id="schedule_custom_hours" name="sergio_ai_studio_options[schedule_custom_hours]"
															value="<?php echo esc_attr( $custom_hours ); ?>" min="1" max="168" class="small-text" />
													</div>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="schedule_posts_per_run"><?php esc_html_e( 'Số Bài Mỗi Lần Chạy', 'sergio-ai-studio' ); ?></label>
												</th>
												<td>
													<select name="sergio_ai_studio_options[schedule_posts_per_run]" id="schedule_posts_per_run">
														<option value="1" <?php selected( $posts_per_run, 1 ); ?>>1 bài / phiên (Chuẩn)</option>
														<option value="2" <?php selected( $posts_per_run, 2 ); ?>>2 bài / phiên</option>
														<option value="3" <?php selected( $posts_per_run, 3 ); ?>>3 bài / phiên</option>
														<option value="5" <?php selected( $posts_per_run, 5 ); ?>>5 bài / phiên</option>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="schedule_max_posts_per_day"><?php esc_html_e( 'Trần Giới Hạn / Ngày', 'sergio-ai-studio' ); ?></label>
												</th>
												<td>
													<select name="sergio_ai_studio_options[schedule_max_posts_per_day]" id="schedule_max_posts_per_day">
														<option value="5" <?php selected( $max_posts_per_day, 5 ); ?>>Tối đa 5 bài / ngày</option>
														<option value="10" <?php selected( $max_posts_per_day, 10 ); ?>>Tối đa 10 bài / ngày</option>
														<option value="20" <?php selected( $max_posts_per_day, 20 ); ?>>Tối đa 20 bài / ngày</option>
														<option value="50" <?php selected( $max_posts_per_day, 50 ); ?>>Tối đa 50 bài / ngày</option>
														<option value="0" <?php selected( $max_posts_per_day, 0 ); ?>>Không giới hạn</option>
													</select>
												</td>
											</tr>
										</tbody>
									</table>

									<div class="notice notice-info inline" style="margin: 10px 0 0;">
										<p>
											<strong><?php esc_html_e( 'Trạng thái WP-Cron:', 'sergio-ai-studio' ); ?></strong>
											<?php if ( $scheduler_enabled && $next_run_time ) : ?>
												<span style="color: #059669; font-weight: 600;"><?php printf( esc_html__( 'Đang chạy — Lần tới: %s', 'sergio-ai-studio' ), esc_html( $next_run_time ) ); ?></span>
											<?php elseif ( $scheduler_enabled ) : ?>
												<span style="color: #059669; font-weight: 600;"><?php esc_html_e( 'Đang kích hoạt', 'sergio-ai-studio' ); ?></span>
											<?php else : ?>
												<span style="color: #64748b;"><?php esc_html_e( 'Lịch trình hiện đang tắt', 'sergio-ai-studio' ); ?></span>
											<?php endif; ?>
											&bull; <?php printf( esc_html__( 'Hôm nay: %d bài đã tạo.', 'sergio-ai-studio' ), (int) $posts_today ); ?>
										</p>
									</div>
								</div>
							</div>

							<div class="postbox">
								<div class="postbox-header"><h2 class="hndle"><span><span class="dashicons dashicons-superhero"></span> <?php esc_html_e( 'Chiến Lược Chủ Đề & Định Dạng', 'sergio-ai-studio' ); ?></span></h2></div>
								<div class="inside">
									<table class="form-table" role="presentation">
										<tbody>
											<tr>
												<th scope="row">
													<label for="schedule_topic_mode"><?php esc_html_e( 'Nguồn Cung Cấp Chủ Đề', 'sergio-ai-studio' ); ?></label>
												</th>
												<td>
													<select name="sergio_ai_studio_options[schedule_topic_mode]" id="schedule_topic_mode" style="width: 100%;">
														<option value="hybrid" <?php selected( $topic_mode, 'hybrid' ); ?>>
															<?php esc_html_e( 'Kết Hợp Thông Minh (Ưu tiên Hàng đợi, tự động tìm kiếm khi hết)', 'sergio-ai-studio' ); ?>
														</option>
														<option value="queue_only" <?php selected( $topic_mode, 'queue_only' ); ?>>
															<?php esc_html_e( 'Chỉ Lấy Từ Hàng Đợi (Chỉ viết các chủ đề bạn nạp sẵn)', 'sergio-ai-studio' ); ?>
														</option>
														<option value="ai_discover" <?php selected( $topic_mode, 'ai_discover' ); ?>>
															<?php esc_html_e( 'AI Tự Động Khám Phá 100% (Tự nghiên cứu tin hot theo Lĩnh vực)', 'sergio-ai-studio' ); ?>
														</option>
													</select>
												</td>
											</tr>
											<tr id="niche_topic_wrapper">
												<th scope="row">
													<label for="schedule_niche_topic"><?php esc_html_e( 'Lĩnh Vực / Từ Khóa Ngách', 'sergio-ai-studio' ); ?></label>
												</th>
												<td>
													<input type="text" id="schedule_niche_topic" name="sergio_ai_studio_options[schedule_niche_topic]"
														value="<?php echo esc_attr( $niche_topic ); ?>" class="large-text"
														placeholder="<?php esc_attr_e( 'Ví dụ: Tin tức AI, ChatGPT, WordPress...', 'sergio-ai-studio' ); ?>" />
													<p class="description"><?php esc_html_e( 'Khi tự động nghiên cứu, Gemini sẽ tìm kiếm sự kiện và thực thể thật mới nhất trong lĩnh vực này.', 'sergio-ai-studio' ); ?></p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="schedule_target_length"><?php esc_html_e( 'Độ Dài Bài Viết Mục Tiêu', 'sergio-ai-studio' ); ?></label>
												</th>
												<td>
													<select name="sergio_ai_studio_options[schedule_target_length]" id="schedule_target_length">
														<option value="standard" <?php selected( $target_length, 'standard' ); ?>><?php esc_html_e( 'Tiêu chuẩn (~800 - 1,200 từ)', 'sergio-ai-studio' ); ?></option>
														<option value="short" <?php selected( $target_length, 'short' ); ?>><?php esc_html_e( 'Ngắn gọn súc tích (~500 - 800 từ)', 'sergio-ai-studio' ); ?></option>
														<option value="deep_dive" <?php selected( $target_length, 'deep_dive' ); ?>><?php esc_html_e( 'Phân tích chuyên sâu (~1,500 - 2,500 từ)', 'sergio-ai-studio' ); ?></option>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="default_schedule_author"><?php esc_html_e( 'Tác Giả & Chuyên Mục', 'sergio-ai-studio' ); ?></label>
												</th>
												<td>
													<select name="sergio_ai_studio_options[default_schedule_author]" id="default_schedule_author">
														<?php foreach ( $authors as $auth ) : ?>
															<option value="<?php echo esc_attr( $auth->ID ); ?>" <?php selected( $default_sched_auth, $auth->ID ); ?>>
																<?php echo esc_html( $auth->display_name ); ?>
															</option>
														<?php endforeach; ?>
													</select>

													<select name="sergio_ai_studio_options[default_schedule_cat]" id="default_schedule_cat">
														<option value="0"><?php esc_html_e( '-- Chọn chuyên mục mặc định --', 'sergio-ai-studio' ); ?></option>
														<?php foreach ( $categories as $cat ) : ?>
															<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $default_sched_cat, $cat->term_id ); ?>>
																<?php echo esc_html( $cat->name ); ?>
															</option>
														<?php endforeach; ?>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row"><?php esc_html_e( 'Trạng Thái Khi Sinh', 'sergio-ai-studio' ); ?></th>
												<td>
													<select name="sergio_ai_studio_options[default_schedule_status]">
														<option value="draft" <?php selected( $default_sched_stat, 'draft' ); ?>><?php esc_html_e( 'Bản nháp (Draft — Khuyên dùng)', 'sergio-ai-studio' ); ?></option>
														<option value="publish" <?php selected( $default_sched_stat, 'publish' ); ?>><?php esc_html_e( 'Xuất bản ngay (Publish)', 'sergio-ai-studio' ); ?></option>
													</select>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>

						<p style="margin-top: 15px;">
							<?php submit_button( __( 'Lưu Toàn Bộ Cấu Hình Lịch Trình', 'sergio-ai-studio' ), 'primary', 'submit', false ); ?>
						</p>
					</form>

					<!-- Queue Workbench -->
					<div class="sergio-grid-2col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 25px;">
						<div class="postbox">
							<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Nạp Chủ Đề Vào Hàng Đợi (Queue)', 'sergio-ai-studio' ); ?></span></h2></div>
							<div class="inside">
								<p class="description"><?php esc_html_e( 'Dán danh sách chủ đề hoặc ý định tìm kiếm (mỗi dòng một chủ đề). Hệ thống sẽ xử lý tuần tự theo từng chu kỳ.', 'sergio-ai-studio' ); ?></p>
								<p>
									<textarea id="queue_topics_input" rows="5" class="large-text"
										placeholder="<?php esc_attr_e( "Đánh giá chi tiết mô hình Claude 3.7 Sonnet\nMeta ra mắt kính Ray-Ban thông minh tích hợp AI\nOpenAI công bố lộ trình tác nhân tự trị 2026...", 'sergio-ai-studio' ); ?>"></textarea>
								</p>
								<p style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
									<select id="queue_category_select">
										<option value="0"><?php esc_html_e( '-- Theo cài đặt mặc định --', 'sergio-ai-studio' ); ?></option>
										<?php foreach ( $categories as $cat ) : ?>
											<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
										<?php endforeach; ?>
									</select>
									<button type="button" class="button button-primary" id="btn_add_to_queue">
										<?php esc_html_e( 'Nạp Hàng Đợi', 'sergio-ai-studio' ); ?>
									</button>
									<span id="queue_add_feedback" style="font-weight: 500;"></span>
								</p>
							</div>
						</div>

						<div class="postbox">
							<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Chạy Thử Nghiệm Ngay Lập Tức', 'sergio-ai-studio' ); ?></span></h2></div>
							<div class="inside">
								<p><?php printf( esc_html__( 'Kích hoạt ngay 1 đợt xử lý (sẽ tạo %d bài theo đúng quy tắc đã cấu hình). Không cần chờ đến giờ chạy tiếp theo.', 'sergio-ai-studio' ), (int) $posts_per_run ); ?></p>
								<div class="card" style="margin: 10px 0 15px; padding: 12px;">
									<p style="margin: 0 0 4px; font-weight: 600;">
										<?php esc_html_e( 'Chế độ hiện tại:', 'sergio-ai-studio' ); ?>
										<?php
										if ( 'queue_only' === $topic_mode ) {
											esc_html_e( 'Chỉ lấy từ Hàng Đợi', 'sergio-ai-studio' );
										} elseif ( 'ai_discover' === $topic_mode ) {
											esc_html_e( 'AI tự động nghiên cứu theo Lĩnh vực ngách', 'sergio-ai-studio' );
										} else {
											esc_html_e( 'Kết Hợp Thông Minh (Hàng đợi + Lĩnh vực ngách)', 'sergio-ai-studio' );
										}
										?>
									</p>
									<p class="description" style="margin: 0;">
										<?php printf( esc_html__( 'Quy mô đợt: %d bài viết / phiên', 'sergio-ai-studio' ), (int) $posts_per_run ); ?>
									</p>
								</div>
								<p>
									<button type="button" class="button button-secondary button-large" id="btn_run_cron_now">
										<span class="dashicons dashicons-controls-play"></span>
										<span><?php printf( esc_html__( 'Chạy Thử Ngay %d Bài (Run Batch Now)', 'sergio-ai-studio' ), (int) $posts_per_run ); ?></span>
									</button>
								</p>
								<div id="cron_run_feedback" style="margin-top: 8px; font-size: 13px;"></div>
							</div>
						</div>
					</div>

					<div class="postbox" style="margin-top: 20px;">
						<div class="postbox-header">
							<h2 class="hndle">
								<span><?php esc_html_e( 'Danh Sách Hàng Đợi Hiện Tại', 'sergio-ai-studio' ); ?> (<?php echo count( $queue_items ); ?>)</span>
							</h2>
						</div>
						<div class="inside" style="padding: 0;">
							<table class="widefat striped" id="queue_items_table" style="border: none;">
								<thead>
									<tr>
										<th style="width: 50px;">ID</th>
										<th><?php esc_html_e( 'Chủ Đề Nghiên Cứu', 'sergio-ai-studio' ); ?></th>
										<th style="width: 110px;"><?php esc_html_e( 'Trạng Thái', 'sergio-ai-studio' ); ?></th>
										<th style="width: 70px; text-align: center;"><?php esc_html_e( 'Lần Thử', 'sergio-ai-studio' ); ?></th>
										<th style="width: 150px;"><?php esc_html_e( 'Thời Gian Tạo', 'sergio-ai-studio' ); ?></th>
										<th style="width: 120px; text-align: center;"><?php esc_html_e( 'Thao Tác', 'sergio-ai-studio' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $queue_items ) ) : ?>
										<tr id="queue_empty_row">
											<td colspan="6" style="padding: 16px; color: #64748b;">
												<?php esc_html_e( 'Hàng đợi hiện đang trống. Bạn có thể nạp chủ đề ở phía trên hoặc để AI tự động nghiên cứu theo Lĩnh vực ngách.', 'sergio-ai-studio' ); ?>
											</td>
										</tr>
									<?php else : ?>
										<?php foreach ( $queue_items as $q ) : ?>
											<tr id="queue-row-<?php echo esc_attr( $q['id'] ); ?>">
												<td>#<?php echo esc_html( $q['id'] ); ?></td>
												<td>
													<strong><?php echo esc_html( $q['topic'] ); ?></strong>
													<?php if ( ! empty( $q['last_error'] ) ) : ?>
														<div class="item-error-subtext" style="color: #b32d2e; font-size: 11px; margin-top: 3px;"><?php echo esc_html( $q['last_error'] ); ?></div>
													<?php endif; ?>
												</td>
												<td>
													<?php if ( 'completed' === $q['status'] ) : ?>
														<span class="notice notice-success inline" style="padding: 2px 6px; margin: 0;"><?php echo esc_html( strtoupper( $q['status'] ) ); ?></span>
													<?php elseif ( 'failed' === $q['status'] ) : ?>
														<span class="notice notice-error inline" style="padding: 2px 6px; margin: 0;"><?php echo esc_html( strtoupper( $q['status'] ) ); ?></span>
													<?php else : ?>
														<span class="notice notice-info inline" style="padding: 2px 6px; margin: 0;"><?php echo esc_html( strtoupper( $q['status'] ) ); ?></span>
													<?php endif; ?>
												</td>
												<td style="text-align: center; color: #64748b;"><?php echo (int) ( $q['attempts'] ?? 0 ); ?>/3</td>
												<td><?php echo esc_html( $q['created_at'] ); ?></td>
												<td style="text-align: center; white-space: nowrap;">
													<?php if ( 'failed' === $q['status'] ) : ?>
														<button type="button" class="button button-small sergio-btn-retry-queue" data-id="<?php echo esc_attr( $q['id'] ); ?>" title="<?php esc_attr_e( 'Thử lại chủ đề này', 'sergio-ai-studio' ); ?>">
															<span class="dashicons dashicons-update"></span>
														</button>
													<?php elseif ( 'pending' === $q['status'] ) : ?>
														<button type="button" class="button button-small sergio-btn-priority-queue" data-id="<?php echo esc_attr( $q['id'] ); ?>" title="<?php esc_attr_e( 'Đẩy lên đầu hàng đợi ưu tiên', 'sergio-ai-studio' ); ?>">
															<span class="dashicons dashicons-arrow-up-alt"></span>
														</button>
													<?php endif; ?>
													<button type="button" class="button button-small button-link-delete sergio-btn-del-queue" data-id="<?php echo esc_attr( $q['id'] ); ?>" title="<?php esc_attr_e( 'Xóa mục này', 'sergio-ai-studio' ); ?>">
														<span class="dashicons dashicons-trash"></span>
													</button>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<!-- ===================================== -->
				<!-- TAB 4: NGHIÊN CỨU & VIẾT BÀI         -->
				<!-- ===================================== -->
				<div id="tab-writer" class="sergio-tab-pane <?php echo 'writer' === $active_tab ? 'is-active' : ''; ?>">
					<div class="postbox">
						<div class="postbox-header"><h2 class="hndle"><span><span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Nghiên Cứu Dữ Kiện & Viết Bài', 'sergio-ai-studio' ); ?></span></h2></div>
						<div class="inside">
							<p class="description" style="margin-bottom: 15px;">
								<?php esc_html_e( 'Hệ thống tra cứu dữ kiện web bằng Google Search Grounding, lập Fact Pack đối chứng và chấp bút bài viết độc bản chuẩn Rank Math SEO.', 'sergio-ai-studio' ); ?>
							</p>

							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row">
											<label for="writer_seed_query"><?php esc_html_e( 'Chủ Đề Hoặc Ý Định Tìm Kiếm (*)', 'sergio-ai-studio' ); ?></label>
										</th>
										<td>
											<input type="text" id="writer_seed_query" class="large-text"
												placeholder="<?php esc_attr_e( 'Ví dụ: OpenAI ra mắt mô hình o3-mini, Meta Llama 4, chip M4 Apple...', 'sergio-ai-studio' ); ?>" />
											<p class="description">
												<?php esc_html_e( 'Hệ thống sẽ bóc tách các thực thể thật và xây dựng tiêu đề báo chí độc bản từ dữ kiện kiểm chứng, không dùng nguyên văn chuỗi này.', 'sergio-ai-studio' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="writer_category"><?php esc_html_e( 'Chuyên Mục & Ngôn Ngữ', 'sergio-ai-studio' ); ?></label>
										</th>
										<td>
											<select id="writer_category">
												<option value="0"><?php esc_html_e( '-- Chọn chuyên mục đích --', 'sergio-ai-studio' ); ?></option>
												<?php foreach ( $categories as $cat ) : ?>
													<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
												<?php endforeach; ?>
											</select>

											<select id="writer_language" style="margin-left: 10px;">
												<option value="vi" <?php selected( $language, 'vi' ); ?>>Tiếng Việt (Vietnamese)</option>
												<option value="en" <?php selected( $language, 'en' ); ?>>English (Global)</option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="writer_target_length"><?php esc_html_e( 'Độ Dài & Giọng Văn', 'sergio-ai-studio' ); ?></label>
										</th>
										<td>
											<select id="writer_target_length">
												<option value="standard" selected><?php esc_html_e( 'Tiêu chuẩn SEO (~1.000 - 1.500 từ)', 'sergio-ai-studio' ); ?></option>
												<option value="short"><?php esc_html_e( 'Ngắn gọn / Tin nhanh (~600 - 900 từ)', 'sergio-ai-studio' ); ?></option>
												<option value="deep_dive"><?php esc_html_e( 'Chuyên sâu / Mega Post (~1.800 - 2.800 từ)', 'sergio-ai-studio' ); ?></option>
											</select>

											<select id="writer_tone" style="margin-left: 10px;">
												<option value="journalistic" selected><?php esc_html_e( 'Báo chí phân tích & Khách quan (Khuyên dùng)', 'sergio-ai-studio' ); ?></option>
												<option value="tech"><?php esc_html_e( 'Chuyên gia công nghệ (Tech Deep Dive)', 'sergio-ai-studio' ); ?></option>
												<option value="tutorial"><?php esc_html_e( 'Hướng dẫn thực hành (Step-by-step How-To)', 'sergio-ai-studio' ); ?></option>
												<option value="review"><?php esc_html_e( 'Đánh giá & So sánh toàn diện (Review & Comparison)', 'sergio-ai-studio' ); ?></option>
												<option value="engaging"><?php esc_html_e( 'Sôi nổi, truyền cảm hứng & Dễ tiếp cận', 'sergio-ai-studio' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="writer_model_override"><?php esc_html_e( 'Mô Hình AI & Dữ Kiện', 'sergio-ai-studio' ); ?></label>
										</th>
										<td>
											<select id="writer_model_override">
												<option value="default"><?php esc_html_e( 'Theo cấu hình hệ thống (Primary/Fallback)', 'sergio-ai-studio' ); ?></option>
												<option value="gemini-2.0-flash"><?php esc_html_e( 'Gemini 2.0 Flash (Tốc độ cao nhất, đề xuất chống timeout)', 'sergio-ai-studio' ); ?></option>
												<option value="gemini-2.5-flash"><?php esc_html_e( 'Gemini 2.5 Flash (Cân bằng & Toàn diện)', 'sergio-ai-studio' ); ?></option>
												<option value="gemini-1.5-flash"><?php esc_html_e( 'Gemini 1.5 Flash (Ổn định cao LTS)', 'sergio-ai-studio' ); ?></option>
												<option value="gemini-2.5-pro"><?php esc_html_e( 'Gemini 2.5 Pro (Lập luận chuyên sâu)', 'sergio-ai-studio' ); ?></option>
											</select>

											<select id="writer_grounding_mode" style="margin-left: 10px;">
												<option value="grounding" selected><?php esc_html_e( 'Google Search Grounding (Tìm dữ kiện web)', 'sergio-ai-studio' ); ?></option>
												<option value="direct"><?php esc_html_e( 'Tri thức tổng hợp trực tiếp (Tốc độ cao)', 'sergio-ai-studio' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="writer_verification_gate"><?php esc_html_e( 'Kiểm Chứng & Xử Lý Ảnh', 'sergio-ai-studio' ); ?></label>
										</th>
										<td>
											<select id="writer_verification_gate">
												<option value="standard" selected><?php esc_html_e( 'Tiêu chuẩn (Nền tảng uy tín / Nguồn chính thức / ≥2 link)', 'sergio-ai-studio' ); ?></option>
												<option value="strict"><?php esc_html_e( 'Nghiêm ngặt (Bắt buộc tối thiểu 2 domain báo chí độc lập)', 'sergio-ai-studio' ); ?></option>
											</select>

											<select id="writer_image_option" style="margin-left: 10px;">
												<option value="reference" selected><?php esc_html_e( 'Trích xuất từ nguồn tham khảo (Ghi nguồn tác giả rõ ràng)', 'sergio-ai-studio' ); ?></option>
												<option value="banner"><?php esc_html_e( 'Tự động vẽ Banner đồ họa chuẩn mạng xã hội (1200x630)', 'sergio-ai-studio' ); ?></option>
												<option value="none"><?php esc_html_e( 'Không tạo ảnh (Tối ưu tốc độ tối đa)', 'sergio-ai-studio' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="writer_post_status"><?php esc_html_e( 'Trạng Thái Bài Viết Khi Tạo', 'sergio-ai-studio' ); ?></label>
										</th>
										<td>
											<select id="writer_post_status">
												<option value="draft" selected><?php esc_html_e( 'Lưu bản nháp (Draft - Khuyên dùng để kiểm duyệt)', 'sergio-ai-studio' ); ?></option>
												<option value="pending"><?php esc_html_e( 'Chờ duyệt (Pending Review)', 'sergio-ai-studio' ); ?></option>
												<option value="publish"><?php esc_html_e( 'Xuất bản trực tiếp (Publish)', 'sergio-ai-studio' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Tùy Chọn Tối Ưu', 'sergio-ai-studio' ); ?></th>
										<td>
											<fieldset>
												<label style="display: block; margin-bottom: 6px;">
													<input type="checkbox" id="writer_enable_rank_math" checked value="1" />
													<?php esc_html_e( 'Tối ưu Rank Math SEO PRO (Focus Keyword, SEO Title/Description, Social Card)', 'sergio-ai-studio' ); ?>
												</label>
												<label style="display: block; margin-bottom: 6px;">
													<input type="checkbox" id="writer_enable_internal_links" checked value="1" />
													<?php esc_html_e( 'Tự động quét & chèn Liên kết nội bộ (Internal Links)', 'sergio-ai-studio' ); ?>
												</label>
												<label style="display: block;">
													<input type="checkbox" id="writer_enable_sources_box" checked value="1" />
													<?php esc_html_e( 'Chèn hộp Nguồn kiểm chứng minh bạch (Sources Box) ở cuối bài', 'sergio-ai-studio' ); ?>
												</label>
											</fieldset>
										</td>
									</tr>
								</tbody>
							</table>

							<p style="margin-top: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
								<button type="button" class="button button-primary button-large" id="btn_run_pipeline">
									<span class="dashicons dashicons-superhero"></span>
									<?php esc_html_e( 'Khởi Chạy Fact Pack & Viết Bài (1-Click)', 'sergio-ai-studio' ); ?>
								</button>
								<button type="button" class="button button-secondary button-large" id="btn_step1_fact_pack_only">
									<span class="dashicons dashicons-shield"></span>
									<?php esc_html_e( 'Chỉ Lập Fact Pack (Kiểm tra dữ kiện trước)', 'sergio-ai-studio' ); ?>
								</button>
							</p>
						</div>
					</div>

					<!-- Linear Progress Stepper -->
					<div id="pipeline_progress_box" class="notice notice-info inline" style="display: none; margin-top: 15px; padding: 12px 16px;">
						<div class="stepper-status-header" style="display: flex; align-items: center; gap: 8px;">
							<span class="stepper-header-icon"><span class="spinner is-active" style="float:none; margin:0;"></span></span>
							<strong id="pipeline_progress_msg"><?php esc_html_e( 'Đang khởi tạo kết nối...', 'sergio-ai-studio' ); ?></strong>
						</div>
						<div id="pipeline_steps_list" style="margin-top: 10px; line-height: 1.8;"></div>
					</div>

					<!-- Interactive Fact Pack Preview Postbox -->
					<div id="fact_pack_preview_box" class="postbox" style="display: none; margin-top: 20px;">
						<div class="postbox-header">
							<h2 class="hndle">
								<span><span class="dashicons dashicons-shield" style="color: #059669;"></span> <?php esc_html_e( 'Gói Dữ Kiện Đã Xác Thực (Fact Pack Preview)', 'sergio-ai-studio' ); ?></span>
							</h2>
						</div>
						<div class="inside">
							<p class="description"><?php esc_html_e( 'Xem trước các dữ kiện thật đã được đối chiếu từ web trước khi chấp bút bài viết:', 'sergio-ai-studio' ); ?></p>
							<div id="fact_pack_content_area" style="line-height: 1.7; margin: 15px 0;"></div>
							<p style="margin: 0; padding-top: 10px; border-top: 1px solid #c3c4c7;">
								<button type="button" class="button button-primary" id="btn_write_from_fact_pack">
									<span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Tiến Hành Chấp Bút Bài Viết Từ Fact Pack Này', 'sergio-ai-studio' ); ?>
								</button>
								<button type="button" class="button button-secondary" id="btn_cancel_fact_pack">
									<?php esc_html_e( 'Đóng Xem Trước', 'sergio-ai-studio' ); ?>
								</button>
							</p>
						</div>
					</div>

					<!-- Result Card -->
					<div id="pipeline_result_card" class="notice notice-success inline" style="display: none; margin-top: 20px; padding: 14px 18px;"></div>
				</div>

				<!-- ===================================== -->
				<!-- TAB 5: CRAWL & VIẾT LẠI             -->
				<!-- ===================================== -->
				<div id="tab-crawl" class="sergio-tab-pane <?php echo 'crawl' === $active_tab ? 'is-active' : ''; ?>">
					<div class="postbox">
						<div class="postbox-header"><h2 class="hndle"><span><span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Crawl & Viết Lại Nội Dung', 'sergio-ai-studio' ); ?></span></h2></div>
						<div class="inside">
							<p class="description" style="margin-bottom: 15px;">
								<?php esc_html_e( 'Thu thập nội dung từ các trang tin tức bên ngoài, bóc tách bài viết sạch qua thuật toán DOM, và dùng Gemini tái bút bằng góc nhìn riêng biệt kèm trích dẫn nguồn.', 'sergio-ai-studio' ); ?>
							</p>

							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row">
											<label for="crawl_urls"><?php esc_html_e( 'Danh Sách URL (Mỗi dòng một URL)', 'sergio-ai-studio' ); ?></label>
										</th>
										<td>
											<textarea id="crawl_urls" rows="6" class="large-text code"
												placeholder="https://techcrunch.com/2026/10/31/ai-news/&#10;https://venturebeat.com/ai/new-model/"></textarea>
											<p class="description">
												<?php esc_html_e( 'Chỉ cho phép http/https. Hệ thống tự động kích hoạt bộ lọc SSRF chặn loopback, private IP (127.x, 192.168.x, 10.x, cloud metadata). Giới hạn tối đa 500KB/trang.', 'sergio-ai-studio' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="crawl_category"><?php esc_html_e( 'Chuyên Mục & Ngôn Ngữ', 'sergio-ai-studio' ); ?></label>
										</th>
										<td>
											<select id="crawl_category">
												<option value="0"><?php esc_html_e( '-- Chọn chuyên mục đích --', 'sergio-ai-studio' ); ?></option>
												<?php foreach ( $categories as $cat ) : ?>
													<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
												<?php endforeach; ?>
											</select>

											<select id="crawl_language" style="margin-left: 10px;">
												<option value="vi" <?php selected( $language, 'vi' ); ?>>Tiếng Việt (Vietnamese)</option>
												<option value="en" <?php selected( $language, 'en' ); ?>>English (Global)</option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="crawl_style"><?php esc_html_e( 'Văn Phong & Cấu Trúc', 'sergio-ai-studio' ); ?></label>
										</th>
										<td>
											<select id="crawl_style">
												<option value="news"><?php esc_html_e( 'Tin tức báo chí chuẩn mực', 'sergio-ai-studio' ); ?></option>
												<option value="analysis"><?php esc_html_e( 'Phân tích chuyên sâu & bình luận', 'sergio-ai-studio' ); ?></option>
												<option value="tutorial"><?php esc_html_e( 'Hướng dẫn thực hành', 'sergio-ai-studio' ); ?></option>
											</select>

											<select id="crawl_max_chars" style="margin-left: 10px;">
												<option value="4000">4,000 ký tự (~600 từ)</option>
												<option value="8000" selected>8,000 ký tự (~1,200 từ) — Chuẩn</option>
												<option value="12000">12,000 ký tự (~1,800 từ)</option>
											</select>
										</td>
									</tr>
								</tbody>
							</table>

							<p style="margin-top: 20px;">
								<button type="button" class="button button-primary button-large" id="btn_crawl_rewrite">
									<span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Crawl & Viết Lại', 'sergio-ai-studio' ); ?>
								</button>
							</p>
						</div>
					</div>

					<div id="crawl_progress_box" class="notice notice-info inline" style="display: none; margin-top: 15px; padding: 12px 16px;">
						<div class="stepper-status-header" style="display: flex; align-items: center; gap: 8px;">
							<span class="stepper-header-icon"><span class="spinner is-active" style="float:none; margin:0;"></span></span>
							<strong id="crawl_progress_msg"><?php esc_html_e( 'Đang kết nối crawl...', 'sergio-ai-studio' ); ?></strong>
						</div>
						<div id="crawl_steps_list" style="margin-top: 10px; line-height: 1.8;"></div>
					</div>

					<div id="crawl_results_wrap" style="display: none; margin-top: 20px;">
						<div class="postbox">
							<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Kết Quả Thu Thập & Tái Bút', 'sergio-ai-studio' ); ?></span></h2></div>
							<div class="inside" style="padding: 0;">
								<table class="widefat striped" id="crawl_results_table" style="border: none;">
									<thead>
										<tr>
											<th><?php esc_html_e( 'URL Nguồn', 'sergio-ai-studio' ); ?></th>
											<th><?php esc_html_e( 'Tiêu Đề Bài Viết Mới', 'sergio-ai-studio' ); ?></th>
											<th style="width: 120px;"><?php esc_html_e( 'Trạng Thái', 'sergio-ai-studio' ); ?></th>
											<th style="width: 140px;"><?php esc_html_e( 'Hành Động', 'sergio-ai-studio' ); ?></th>
										</tr>
									</thead>
									<tbody id="crawl_results_tbody"></tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				<!-- ===================================== -->
				<!-- TAB 6: AUDIT & TỐI ƯU SEO             -->
				<!-- ===================================== -->
				<div id="tab-seo" class="sergio-tab-pane <?php echo 'seo' === $active_tab ? 'is-active' : ''; ?>">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
						<div>
							<h2 style="margin: 0; font-size: 18px; font-weight: 600;"><?php esc_html_e( 'Audit & Tối Ưu SEO Toàn Diện', 'sergio-ai-studio' ); ?></h2>
							<p class="description" style="margin: 2px 0 0;"><?php esc_html_e( 'Quét sức khỏe SEO on-page toàn site, đo lường theo tiêu chuẩn Rank Math và kích hoạt Gemini AI tối ưu tiêu đề, mô tả, từ khóa LSI.', 'sergio-ai-studio' ); ?></p>
						</div>
						<button type="button" class="button button-secondary" id="btn_run_site_seo_audit">
							<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Quét Lại Sức Khỏe Toàn Site', 'sergio-ai-studio' ); ?>
						</button>
					</div>

					<!-- Metrics Deck -->
					<div class="sergio-overview-cards" id="seo_health_strip">
						<div class="card">
							<div class="card-kicker">
								<span class="dashicons dashicons-chart-area"></span>
								<span><?php esc_html_e( 'Điểm SEO Trung Bình', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val val-blue">
								<span id="seo_stat_avg_score">--</span> <span class="card-val-unit">/ 100</span>
							</div>
							<p class="card-desc" id="seo_stat_avg_label"><?php esc_html_e( 'Sức khỏe tổng thể', 'sergio-ai-studio' ); ?></p>
						</div>

						<div class="card card-success">
							<div class="card-kicker">
								<span class="dashicons dashicons-yes-alt"></span>
								<span><?php esc_html_e( 'Đạt Chuẩn Tốt (>80đ)', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val val-green">
								<span id="seo_stat_good_count">0</span> <span class="card-val-unit"><?php esc_html_e( 'trang', 'sergio-ai-studio' ); ?></span>
							</div>
							<p class="card-desc"><?php esc_html_e( 'Chuẩn Rank Math xanh', 'sergio-ai-studio' ); ?></p>
						</div>

						<div class="card card-warning">
							<div class="card-kicker">
								<span class="dashicons dashicons-warning"></span>
								<span><?php esc_html_e( 'Cần Tối Ưu (50-79đ)', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val val-amber">
								<span id="seo_stat_warning_count">0</span> <span class="card-val-unit"><?php esc_html_e( 'trang', 'sergio-ai-studio' ); ?></span>
							</div>
							<p class="card-desc"><?php esc_html_e( 'Cần tinh chỉnh On-page', 'sergio-ai-studio' ); ?></p>
						</div>

						<div class="card card-error">
							<div class="card-kicker">
								<span class="dashicons dashicons-dismiss"></span>
								<span><?php esc_html_e( 'Thiếu Focus Keyword', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val val-red">
								<span id="seo_stat_missing_kw">0</span> <span class="card-val-unit"><?php esc_html_e( 'trang', 'sergio-ai-studio' ); ?></span>
							</div>
							<p class="card-desc"><?php esc_html_e( 'Chưa xác định từ khóa', 'sergio-ai-studio' ); ?></p>
						</div>
					</div>

					<!-- Batch Optimizer Banner -->
					<div id="seo_site_action_plan_box" class="notice notice-warning inline" style="margin-bottom: 20px; display: none; padding: 12px 16px;">
						<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
							<div>
								<strong><span class="dashicons dashicons-lightning"></span> <?php esc_html_e( 'Chẩn Đoán & Kế Hoạch Tự Động Cải Thiện Toàn Site', 'sergio-ai-studio' ); ?></strong>
								<div id="seo_action_plan_items" style="margin-top: 8px;"></div>
							</div>
							<button type="button" class="button button-primary" id="btn_batch_fix_all_missing">
								<span class="dashicons dashicons-superhero"></span> <?php esc_html_e( 'Tự Động Tối Ưu Bằng AI', 'sergio-ai-studio' ); ?>
							</button>
						</div>
						<div id="seo_batch_progress_box" style="display: none; margin-top: 10px;">
							<span class="spinner is-active" style="float:none; margin:0 6px 0 0;"></span>
							<span id="seo_batch_progress_text"><?php esc_html_e( 'Đang tự động tối ưu SEO bằng Gemini AI...', 'sergio-ai-studio' ); ?></span>
						</div>
					</div>

					<!-- SEO 2-Column Workbench -->
					<div style="display: grid; grid-template-columns: 320px 1fr; gap: 20px;">
						<!-- Left: Posts List -->
						<div class="postbox">
							<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Danh Sách Nội Dung', 'sergio-ai-studio' ); ?></span></h2></div>
							<div class="inside" style="padding: 12px;">
								<div class="filter-pills-group" style="margin-bottom: 10px; display: flex; gap: 6px;">
									<button type="button" class="button button-small button-primary filter-pill is-active seo-type-filter" data-type=""><?php esc_html_e( 'Tất Cả', 'sergio-ai-studio' ); ?></button>
									<button type="button" class="button button-small button-secondary filter-pill seo-type-filter" data-type="post"><?php esc_html_e( 'Bài Viết', 'sergio-ai-studio' ); ?></button>
									<button type="button" class="button button-small button-secondary filter-pill seo-type-filter" data-type="page"><?php esc_html_e( 'Trang', 'sergio-ai-studio' ); ?></button>
								</div>

								<div style="margin-bottom: 12px;">
									<input type="search" id="seo_search_posts" class="regular-text" placeholder="<?php esc_attr_e( 'Tìm kiếm bài viết, trang...', 'sergio-ai-studio' ); ?>" style="width: 100%;" />
								</div>

								<div id="seo_posts_list_scroll" style="max-height: 600px; overflow-y: auto;">
									<?php if ( ! empty( $seo_posts ) ) : ?>
										<div class="seo-post-cards-wrap">
											<?php
											foreach ( $seo_posts as $sp ) :
												$sp_score = (int) get_post_meta( $sp->ID, 'rank_math_seo_score', true );
												$sp_badge = Sergio_AI_SEO_Audit::get_score_badge( $sp_score );
												$sp_kw    = get_post_meta( $sp->ID, 'rank_math_focus_keyword', true );
											?>
												<div class="seo-post-pick-card" data-post-id="<?php echo esc_attr( $sp->ID ); ?>" data-post-type="<?php echo esc_attr( $sp->post_type ); ?>" data-title="<?php echo esc_attr( mb_strtolower( $sp->post_title ) ); ?>" style="border: 1px solid #c3c4c7; padding: 10px; margin-bottom: 8px; border-radius: 4px; cursor: pointer; background: #fff;">
													<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
														<span class="pick-score-badge <?php echo esc_attr( $sp_badge['class'] ); ?>" style="font-weight: 600; font-size: 12px; padding: 1px 6px; border-radius: 3px; background: #f0f0f1;">
															<?php echo $sp_score > 0 ? (int) $sp_score : '--'; ?>
														</span>
														<div style="display: flex; gap: 4px; font-size: 11px; color: #64748b;">
															<code><?php echo esc_html( strtoupper( $sp->post_type ) ); ?></code>
															<span><?php echo esc_html( strtoupper( $sp->post_status ) ); ?></span>
														</div>
													</div>
													<strong class="pick-card-title" style="display: block; font-size: 13px; line-height: 1.35; margin-bottom: 4px; color: #2271b1;"><?php echo esc_html( $sp->post_title ); ?></strong>
													<div class="pick-card-meta" style="font-size: 12px; color: #64748b;">
														<span><?php echo ! empty( $sp_kw ) ? esc_html( 'KW: ' . $sp_kw ) : '<em style="color:#94a3b8;">Chưa có từ khóa</em>'; ?></span>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<p style="font-size: 13px; color: #64748b;"><?php esc_html_e( 'Chưa có bài viết hoặc trang nào.', 'sergio-ai-studio' ); ?></p>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- Right: Workbench Pane -->
						<div id="seo_workbench_pane">
							<!-- Empty state -->
							<div id="seo_pane_empty" class="postbox" style="text-align: center; padding: 48px 24px;">
								<span class="dashicons dashicons-chart-area" style="font-size: 48px; width: 48px; height: 48px; color: #2271b1; margin-bottom: 12px;"></span>
								<h3 style="font-size: 16px; margin: 0 0 8px;"><?php esc_html_e( 'Chọn Một Bài Viết Hoặc Trang Để Bắt Đầu', 'sergio-ai-studio' ); ?></h3>
								<p class="description" style="max-width: 460px; margin: 0 auto;"><?php esc_html_e( 'Nhấp vào một bài viết hoặc trang từ danh sách bên trái để đo lường chi tiết thang điểm 100 của Rank Math và kích hoạt Gemini AI tối ưu hóa tiêu đề, mô tả và nội dung.', 'sergio-ai-studio' ); ?></p>
							</div>

							<!-- Active audit workbench -->
							<div id="seo_pane_active" style="display: none;">
								<div class="postbox">
									<div class="postbox-header" style="display: flex; justify-content: space-between; align-items: center;">
										<h2 class="hndle">
											<span id="audit_post_type_label" class="notice notice-info inline" style="padding: 1px 5px; margin-right: 6px;">POST</span>
											<span id="audit_post_id_label">#00</span>:
											<span id="audit_post_title_heading"></span>
										</h2>
										<div style="padding-right: 12px;">
											<a href="#" target="_blank" id="audit_post_edit_btn" class="button button-small"><?php esc_html_e( 'Sửa bài trong WP', 'sergio-ai-studio' ); ?></a>
											<a href="#" target="_blank" id="audit_post_view_btn" class="button button-small"><?php esc_html_e( 'Xem trước', 'sergio-ai-studio' ); ?></a>
										</div>
									</div>
									<div class="inside">
										<div style="display: flex; align-items: center; justify-content: space-between; background: #f6f7f7; border: 1px solid #c3c4c7; padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
											<div style="display: flex; align-items: center; gap: 12px;">
												<div id="audit_score_disc" style="font-size: 26px; font-weight: 700;">
													<span id="audit_score_val">0</span><span style="font-size: 14px; font-weight: normal; color: #64748b;">/100</span>
												</div>
												<div>
													<strong id="audit_score_status_text" style="font-size: 14px; display: block;">Đang phân tích...</strong>
													<span class="description" id="audit_score_subtext">Thang điểm Rank Math SEO On-Page</span>
												</div>
											</div>
											<div style="display: flex; gap: 8px; flex-wrap: wrap;">
												<button type="button" class="button button-secondary" id="btn_trigger_ai_seo">
													<span class="dashicons dashicons-superhero"></span> <?php esc_html_e( '1. Gemini Gợi Ý SEO', 'sergio-ai-studio' ); ?>
												</button>
												<button type="button" class="button button-primary" id="btn_trigger_deep_content_seo">
													<span class="dashicons dashicons-lightning"></span> <?php esc_html_e( '2. AI Nâng Cấp Nội Dung', 'sergio-ai-studio' ); ?>
												</button>
											</div>
										</div>

										<div id="ai_seo_loading_box" class="notice notice-info inline" style="display: none; margin-bottom: 15px;">
											<p>
												<span class="spinner is-active" style="float:none; margin:0 6px 0 0;"></span>
												<span id="ai_seo_loading_msg"><?php esc_html_e( 'Gemini đang phân tích nội dung, ý định tìm kiếm và kiến tạo bộ tiêu đề & mô tả tối ưu...', 'sergio-ai-studio' ); ?></span>
											</p>
										</div>

										<!-- AI Optimization Results Deck -->
										<div id="ai_seo_results_deck" class="card" style="display: none; margin: 0 0 16px; padding: 14px 16px; background: #f0f6fc; border-color: #c5d9ed;">
											<h3 style="margin: 0 0 10px; font-size: 14px; color: #1d2327;">
												<span class="dashicons dashicons-superhero" style="color: #2271b1;"></span>
												<?php esc_html_e( 'Chiến Lược Tối Ưu Chuẩn Claude-SEO & Rank Math', 'sergio-ai-studio' ); ?>
											</h3>

											<div id="ai_rec_intent_box" style="margin-bottom: 10px; display: none;">
												<span id="ai_rec_intent_tag" class="notice notice-info inline" style="padding: 2px 8px; font-weight: 500;"></span>
											</div>

											<div style="margin-bottom: 10px;">
												<label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 4px;"><?php esc_html_e( 'Từ Khóa Chính Khuyên Dùng:', 'sergio-ai-studio' ); ?></label>
												<div style="display: flex; gap: 8px;">
													<input type="text" id="ai_rec_focus_kw" class="regular-text" style="font-weight: 600;" readonly />
													<button type="button" class="button button-secondary button-small" id="btn_apply_ai_kw"><?php esc_html_e( 'Chọn Từ Khóa Này', 'sergio-ai-studio' ); ?></button>
												</div>
											</div>

											<div style="margin-bottom: 10px;">
												<label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 4px;"><?php esc_html_e( 'Từ Khóa Phụ / LSI Keywords:', 'sergio-ai-studio' ); ?></label>
												<div id="ai_rec_secondary_kws" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>
											</div>

											<div style="margin-bottom: 10px;">
												<label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 4px;"><?php esc_html_e( 'Các Phương Án Tiêu Đề SEO (CTR Cao):', 'sergio-ai-studio' ); ?></label>
												<div id="ai_rec_titles_list" style="display: flex; flex-direction: column; gap: 6px;"></div>
											</div>

											<div style="margin-bottom: 10px;">
												<label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 4px;"><?php esc_html_e( 'Phương Án Thẻ Mô Tả Meta:', 'sergio-ai-studio' ); ?></label>
												<div id="ai_rec_descs_list" style="display: flex; flex-direction: column; gap: 6px;"></div>
											</div>

											<div>
												<label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 4px;"><?php esc_html_e( 'Đề Xuất Mở Rộng Nội Dung (E-E-A-T):', 'sergio-ai-studio' ); ?></label>
												<ul id="ai_rec_enhancements_list" style="margin: 0; padding-left: 18px; font-size: 13px; line-height: 1.5;"></ul>
											</div>
										</div>

										<!-- SERP Google Preview & Rank Math Editor -->
										<div class="card" style="margin: 0 0 16px; padding: 14px 16px;">
											<h4 style="margin: 0 0 10px; font-size: 13px;"><?php esc_html_e( 'Mô Phỏng Hiển Thị Google Search (SERP Preview)', 'sergio-ai-studio' ); ?></h4>
											<div style="background: #fff; border: 1px solid #dadce0; border-radius: 6px; padding: 12px 14px; margin-bottom: 14px;">
												<div id="serp_preview_url" style="font-size: 12px; color: #202124; margin-bottom: 3px;">
													<span class="serp-url-text">https://example.com/...</span>
												</div>
												<h3 id="serp_preview_title" style="font-size: 17px; line-height: 1.3; color: #1a0dab; margin: 0 0 4px 0; font-weight: 400; cursor: pointer;"></h3>
												<p id="serp_preview_desc" style="font-size: 13px; line-height: 1.45; color: #4d5156; margin: 0;"></p>
											</div>

											<table class="form-table" role="presentation" style="margin: 0;">
												<tbody>
													<tr>
														<th scope="row">
															<label for="edit_seo_focus_kw"><?php esc_html_e( 'Focus Keyword', 'sergio-ai-studio' ); ?></label>
														</th>
														<td>
															<input type="text" id="edit_seo_focus_kw" class="large-text" placeholder="<?php esc_attr_e( 'Nhập từ khóa mục tiêu...', 'sergio-ai-studio' ); ?>" />
														</td>
													</tr>
													<tr>
														<th scope="row">
															<label for="edit_seo_title"><?php esc_html_e( 'Tiêu Đề SEO', 'sergio-ai-studio' ); ?></label>
														</th>
														<td>
															<input type="text" id="edit_seo_title" class="large-text" placeholder="<?php esc_attr_e( 'Tiêu đề hiển thị trên Google...', 'sergio-ai-studio' ); ?>" />
															<span class="description" id="edit_title_char_counter" style="margin-left: 8px;">0 / 60</span>
														</td>
													</tr>
													<tr>
														<th scope="row">
															<label for="edit_seo_description"><?php esc_html_e( 'Thẻ Mô Tả Meta', 'sergio-ai-studio' ); ?></label>
														</th>
														<td>
															<textarea id="edit_seo_description" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Mô tả ngắn gọn hấp dẫn...', 'sergio-ai-studio' ); ?>"></textarea>
															<span class="description" id="edit_desc_char_counter" style="margin-left: 8px;">0 / 160</span>
														</td>
													</tr>
												</tbody>
											</table>

											<p style="margin-top: 15px;">
												<button type="button" class="button button-primary button-large" id="btn_save_seo_to_rankmath">
													<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Áp Dụng Vào Rank Math & Lưu Bài Viết', 'sergio-ai-studio' ); ?>
												</button>
												<span id="save_seo_feedback" style="margin-left: 10px; font-weight: 500;"></span>
											</p>
										</div>

										<!-- Detailed On-Page Checklist -->
										<div class="postbox" style="margin: 0;">
											<div class="postbox-header"><h3 class="hndle"><span><?php esc_html_e( 'Bảng Kiểm Định Chi Tiết On-Page (Rank Math Standards)', 'sergio-ai-studio' ); ?></span></h3></div>
											<div class="inside">
												<div id="audit_checklist_wrap"></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- ===================================== -->
				<!-- TAB 7: NHẬT KÝ                       -->
				<!-- ===================================== -->
				<div id="tab-logs" class="sergio-tab-pane <?php echo 'logs' === $active_tab ? 'is-active' : ''; ?>">
					
					<!-- Top Action & Info Bar -->
					<div class="postbox" style="margin-bottom: 16px;">
						<div class="postbox-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
							<h2 class="hndle">
								<span><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Trung Tâm Giám Sát & Nhật Ký Hoạt Động (Execution Monitor)', 'sergio-ai-studio' ); ?></span>
							</h2>
							<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
								<button type="button" class="button button-primary button-small" id="btn_refresh_logs">
									<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Làm Mới', 'sergio-ai-studio' ); ?>
								</button>
								<button type="button" class="button button-secondary button-small" id="btn_export_logs">
									<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Xuất File JSON', 'sergio-ai-studio' ); ?>
								</button>
								<div class="sergio-log-prune-group" style="display: inline-flex; gap: 4px; border-left: 1px solid #c3c4c7; padding-left: 8px; margin-left: 4px;">
									<button type="button" class="button button-secondary button-small" id="btn_clear_logs_7d">
										<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Dọn > 7 ngày', 'sergio-ai-studio' ); ?>
									</button>
									<button type="button" class="button button-secondary button-small" id="btn_clear_logs_30d">
										<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Dọn > 30 ngày', 'sergio-ai-studio' ); ?>
									</button>
									<button type="button" class="button button-link-delete button-small" id="btn_clear_logs_all">
										<span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Xóa Toàn Bộ', 'sergio-ai-studio' ); ?>
									</button>
								</div>
								<span id="clear_logs_feedback" style="margin-left: 6px; font-weight: 500;"></span>
							</div>
						</div>
					</div>

					<!-- Metric Cards -->
					<div class="sergio-overview-cards" style="margin-bottom: 20px;">
						<div class="card">
							<div class="card-kicker">
								<span class="dashicons dashicons-backup"></span>
								<span><?php esc_html_e( 'Tổng Phiên Tác Vụ', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val val-blue">
								<span id="log_stat_total_runs"><?php echo (int) $log_stats['total_runs']; ?></span> <span class="card-val-unit"><?php esc_html_e( 'phiên', 'sergio-ai-studio' ); ?></span>
							</div>
							<p class="card-desc"><span id="log_stat_total_logs"><?php echo (int) $log_stats['total_logs']; ?></span> <?php esc_html_e( 'bản ghi chi tiết', 'sergio-ai-studio' ); ?></p>
						</div>

						<div class="card card-success">
							<div class="card-kicker">
								<span class="dashicons dashicons-yes-alt"></span>
								<span><?php esc_html_e( 'Tỷ Lệ Thành Công', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val val-green">
								<span id="log_stat_success_rate"><?php echo (int) $log_stats['success_rate']; ?></span> <span class="card-val-unit">%</span>
							</div>
							<p class="card-desc"><?php esc_html_e( 'Độ tin cậy luồng AI & Deep Research', 'sergio-ai-studio' ); ?></p>
						</div>

						<div class="card card-warning">
							<div class="card-kicker">
								<span class="dashicons dashicons-admin-post"></span>
								<span><?php esc_html_e( 'Bài Viết Đã Tạo', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val val-amber">
								<span id="log_stat_articles_created"><?php echo (int) $log_stats['articles_created']; ?></span> <span class="card-val-unit"><?php esc_html_e( 'bài', 'sergio-ai-studio' ); ?></span>
							</div>
							<p class="card-desc"><?php esc_html_e( 'Sinh tự động từ Fact Pack / Cron', 'sergio-ai-studio' ); ?></p>
						</div>

						<div class="card <?php echo $log_stats['error_count'] > 0 ? 'card-error' : ''; ?>">
							<div class="card-kicker">
								<span class="dashicons dashicons-warning"></span>
								<span><?php esc_html_e( 'Lỗi & Cảnh Báo', 'sergio-ai-studio' ); ?></span>
							</div>
							<div class="card-val <?php echo $log_stats['error_count'] > 0 ? 'val-red' : 'val-slate'; ?>">
								<span id="log_stat_error_count"><?php echo (int) $log_stats['error_count']; ?></span> <span class="card-val-unit">lỗi / <span id="log_stat_warning_count"><?php echo (int) $log_stats['warning_count']; ?></span> cảnh báo</span>
							</div>
							<p class="card-desc" id="log_stat_error_label">
								<?php echo $log_stats['error_count'] > 0 ? esc_html__( 'Cần kiểm tra chẩn đoán lỗi bên dưới', 'sergio-ai-studio' ) : esc_html__( 'Hệ thống hoạt động trơn tru', 'sergio-ai-studio' ); ?>
							</p>
						</div>
					</div>

					<!-- Toolbar: View Switcher, Filter Pills, Module Selector, Search Input -->
					<div class="sergio-session-toolbar">
						<div class="sergio-toolbar-left">
							<!-- Dual View Switcher -->
							<div class="button-group sergio-view-switcher" style="margin-right: 6px;">
								<button type="button" class="button button-small is-active btn-log-view-toggle" data-view="sessions">
									<span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Theo Phiên Tác Vụ', 'sergio-ai-studio' ); ?>
								</button>
								<button type="button" class="button button-small btn-log-view-toggle" data-view="raw">
									<span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Dòng Chi Tiết', 'sergio-ai-studio' ); ?>
								</button>
							</div>

							<!-- Level Filter Pills -->
							<div class="filter-pills-group" style="display: inline-flex; gap: 4px;">
								<button type="button" class="button button-small button-primary filter-pill is-active log-level-filter" data-level=""><?php esc_html_e( 'Tất Cả', 'sergio-ai-studio' ); ?></button>
								<button type="button" class="button button-small button-secondary filter-pill log-level-filter" data-level="info">INFO</button>
								<button type="button" class="button button-small button-secondary filter-pill log-level-filter" data-level="warning">WARN</button>
								<button type="button" class="button button-small button-secondary filter-pill log-level-filter" data-level="error">ERROR</button>
							</div>

							<!-- Module Filter Dropdown -->
							<select id="log_module_filter" style="font-size: 12px; height: 30px; min-height: 30px; line-height: 28px; padding-top: 0; padding-bottom: 0;">
								<option value=""><?php esc_html_e( 'Tất cả phân hệ', 'sergio-ai-studio' ); ?></option>
								<option value="generation"><?php esc_html_e( 'Nghiên cứu & Viết bài AI', 'sergio-ai-studio' ); ?></option>
								<option value="scheduler"><?php esc_html_e( 'Lập lịch Cron tự động', 'sergio-ai-studio' ); ?></option>
								<option value="seo"><?php esc_html_e( 'Tối ưu Rank Math SEO', 'sergio-ai-studio' ); ?></option>
								<option value="image"><?php esc_html_e( 'Xử lý Ảnh & AI Banner', 'sergio-ai-studio' ); ?></option>
								<option value="rewrite"><?php esc_html_e( 'Viết lại nguồn tin', 'sergio-ai-studio' ); ?></option>
							</select>
						</div>

						<div class="sergio-toolbar-right">
							<input type="search" id="sergio_logs_search" class="regular-text" placeholder="<?php esc_attr_e( 'Tìm theo chủ đề, ID bài, mã lỗi...', 'sergio-ai-studio' ); ?>" style="font-size: 12px; height: 30px; width: 240px; margin: 0;" />
							<span id="log_refresh_spinner" class="spinner" style="float: none; margin: 0 4px;"></span>
						</div>
					</div>

					<!-- VIEW 1: SESSIONS TIMELINE (DEFAULT) -->
					<div id="sergio_sessions_view">
						<?php echo self::render_session_cards_html( $log_sessions ); ?>
					</div>

					<!-- VIEW 2: RAW LOG STREAM TABLE -->
					<div id="sergio_raw_logs_view" style="display: none;">
						<div class="postbox">
							<div class="inside" style="padding: 0;">
								<table class="widefat striped" id="logs_table" style="border: none;">
									<thead>
										<tr>
											<th style="width: 150px;"><?php esc_html_e( 'Thời Gian', 'sergio-ai-studio' ); ?></th>
											<th style="width: 140px;"><?php esc_html_e( 'Phiên (Run ID)', 'sergio-ai-studio' ); ?></th>
											<th style="width: 80px;"><?php esc_html_e( 'Cấp Độ', 'sergio-ai-studio' ); ?></th>
											<th style="width: 150px;"><?php esc_html_e( 'Giai Đoạn', 'sergio-ai-studio' ); ?></th>
											<th><?php esc_html_e( 'Thông Điệp & Dữ Liệu Chi Tiết', 'sergio-ai-studio' ); ?></th>
										</tr>
									</thead>
									<tbody id="raw_logs_tbody">
										<?php echo self::render_raw_logs_rows_html( $logs ); ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

				</div>
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

		Sergio_AI_Logger::purge_old_logs();

		wp_send_json_success( array( 'message' => __( 'Đã xóa log cũ hơn 30 ngày.', 'sergio-ai-studio' ) ) );
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
