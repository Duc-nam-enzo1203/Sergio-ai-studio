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
		add_action( 'wp_ajax_sergio_ai_add_queue_topics', array( $this, 'ajax_add_queue_topics' ) );
		add_action( 'wp_ajax_sergio_ai_retry_queue_item', array( $this, 'ajax_retry_queue_item' ) );
		add_action( 'wp_ajax_sergio_ai_prioritize_queue_item', array( $this, 'ajax_prioritize_queue_item' ) );
		add_action( 'wp_ajax_sergio_ai_delete_queue_item', array( $this, 'ajax_delete_queue_item' ) );
		add_action( 'wp_ajax_sergio_ai_run_cron_now', array( $this, 'ajax_run_cron_now' ) );
		add_action( 'wp_ajax_sergio_ai_crawl_rewrite', array( $this, 'ajax_crawl_rewrite' ) );
		add_action( 'wp_ajax_sergio_ai_clear_logs', array( $this, 'ajax_clear_logs' ) );
		add_action( 'wp_ajax_sergio_ai_site_seo_audit', array( $this, 'ajax_site_seo_audit' ) );
		add_action( 'wp_ajax_sergio_ai_audit_single_post', array( $this, 'ajax_audit_single_post' ) );
		add_action( 'wp_ajax_sergio_ai_optimize_post_seo', array( $this, 'ajax_optimize_post_seo' ) );
		add_action( 'wp_ajax_sergio_ai_apply_seo_changes', array( $this, 'ajax_apply_seo_changes' ) );
		add_action( 'wp_ajax_sergio_ai_deep_optimize_content', array( $this, 'ajax_deep_optimize_content' ) );
		add_action( 'wp_ajax_sergio_ai_batch_fix_seo', array( $this, 'ajax_batch_fix_seo' ) );
	}

	// -------------------------------------------------------------------------
	// SVG ICON SYSTEM (Zero Emojis)
	// -------------------------------------------------------------------------

	public static function svg( $name, $size = 18, $class = '' ) {
		$cls = $class ? ' class="sergio-icon ' . esc_attr( $class ) . '"' : ' class="sergio-icon"';
		$w   = (int) $size;
		$h   = (int) $size;

		switch ( $name ) {
			case 'overview':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>';
			case 'settings':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>';
			case 'scheduler':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><circle cx="12" cy="15" r="2"/></svg>';
			case 'writer':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>';
			case 'crawl':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';
			case 'seo':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path d="M11 8v6"/><path d="M8 11h6"/></svg>';
			case 'logs':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>';
			case 'shield':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
			case 'sparkles':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3L12 3z"/></svg>';
			case 'check':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
			case 'refresh':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>';
			case 'arrow-up':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>';
			case 'trash':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
			case 'bolt':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>';
			case 'play':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>';
			case 'cpu':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>';
			case 'logo':
				return '<svg' . $cls . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="url(#sergio_logo_grad)"/><path d="M16 8L23 12V20L16 24L9 20V12L16 8Z" stroke="#ffffff" stroke-width="1.75" stroke-linejoin="round"/><circle cx="16" cy="16" r="2.5" fill="#ffffff"/><defs><linearGradient id="sergio_logo_grad" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse"><stop stop-color="#4f46e5"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs></svg>';
			default:
				return '';
		}
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

		wp_enqueue_style(
			'sergio-ai-studio-admin-css',
			SERGIO_AI_STUDIO_URL . 'assets/css/admin-studio.css',
			array(),
			SERGIO_AI_STUDIO_VERSION
		);

		wp_enqueue_script(
			'sergio-ai-studio-admin-js',
			SERGIO_AI_STUDIO_URL . 'assets/js/admin-studio.js',
			array( 'jquery' ),
			SERGIO_AI_STUDIO_VERSION,
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
		$logs       = Sergio_AI_Logger::get_logs( 50 );

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
		$log_errors   = count( array_filter( $logs, fn( $l ) => 'error' === $l['level'] ) );

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
		<div class="wrap sergio-studio-app">

			<!-- ===== STUDIO HEADER ===== -->
			<header class="studio-topbar">
				<div class="topbar-brand">
					<?php echo self::svg( 'logo', 28, 'topbar-logo' ); // phpcs:ignore ?>
					<div class="brand-text">
						<span class="brand-name">Sergio AI Studio</span>
						<span class="brand-version">v2.1</span>
					</div>
				</div>

				<div class="topbar-status-deck">
					<div class="status-indicator-pill <?php echo $has_key ? 'is-connected' : 'is-disconnected'; ?>">
						<span class="status-beacon"></span>
						<span class="status-label">
							<?php
							if ( $is_config_key ) {
								esc_html_e( 'API Key: wp-config.php', 'sergio-ai-studio' );
							} elseif ( $has_key ) {
								esc_html_e( 'Gemini API: Đã kết nối', 'sergio-ai-studio' );
							} else {
								esc_html_e( 'Chưa có API Key', 'sergio-ai-studio' );
							}
							?>
						</span>
					</div>

					<div class="topbar-model-badge" title="<?php esc_attr_e( 'Model chính đang hoạt động', 'sergio-ai-studio' ); ?>">
						<?php echo self::svg( 'cpu', 13 ); // phpcs:ignore ?>
						<code><?php echo esc_html( $primary_model ); ?></code>
					</div>
				</div>
			</header>

			<!-- ===== STUDIO SHELL ===== -->
			<div class="studio-shell">

				<!-- LEFT NAV BENCH -->
				<aside class="studio-nav-bench">
					<nav class="studio-nav-tree">
						<button type="button" class="nav-tree-item <?php echo 'overview' === $active_tab ? 'is-active' : ''; ?>" data-tab="tab-overview">
							<span class="item-icon"><?php echo self::svg( 'overview', 16 ); // phpcs:ignore ?></span>
							<span class="item-label"><?php esc_html_e( 'Tổng Quan', 'sergio-ai-studio' ); ?></span>
						</button>

						<button type="button" class="nav-tree-item <?php echo 'settings' === $active_tab ? 'is-active' : ''; ?>" data-tab="tab-settings">
							<span class="item-icon"><?php echo self::svg( 'settings', 16 ); // phpcs:ignore ?></span>
							<span class="item-label"><?php esc_html_e( 'Cài Đặt', 'sergio-ai-studio' ); ?></span>
						</button>

						<button type="button" class="nav-tree-item <?php echo 'scheduler' === $active_tab ? 'is-active' : ''; ?>" data-tab="tab-scheduler">
							<span class="item-icon"><?php echo self::svg( 'scheduler', 16 ); // phpcs:ignore ?></span>
							<span class="item-label"><?php esc_html_e( 'Lên Lịch', 'sergio-ai-studio' ); ?></span>
							<?php if ( $queue_count > 0 ) : ?>
								<span class="nav-pill-counter"><?php echo esc_html( $queue_count ); ?></span>
							<?php endif; ?>
						</button>

						<button type="button" class="nav-tree-item <?php echo 'writer' === $active_tab ? 'is-active' : ''; ?>" data-tab="tab-writer">
							<span class="item-icon"><?php echo self::svg( 'writer', 16 ); // phpcs:ignore ?></span>
							<span class="item-label"><?php esc_html_e( 'Nghiên Cứu & Viết Bài', 'sergio-ai-studio' ); ?></span>
						</button>

						<button type="button" class="nav-tree-item <?php echo 'crawl' === $active_tab ? 'is-active' : ''; ?>" data-tab="tab-crawl">
							<span class="item-icon"><?php echo self::svg( 'crawl', 16 ); // phpcs:ignore ?></span>
							<span class="item-label"><?php esc_html_e( 'Crawl & Viết Lại', 'sergio-ai-studio' ); ?></span>
							<span class="nav-pill-tag">Mới</span>
						</button>

						<button type="button" class="nav-tree-item <?php echo 'seo' === $active_tab ? 'is-active' : ''; ?>" data-tab="tab-seo">
							<span class="item-icon"><?php echo self::svg( 'seo', 16 ); // phpcs:ignore ?></span>
							<span class="item-label"><?php esc_html_e( 'Audit & Tối Ưu SEO', 'sergio-ai-studio' ); ?></span>
							<span class="nav-pill-tag" style="background: #e0e7ff; color: #4338ca;">AI</span>
						</button>

						<button type="button" class="nav-tree-item <?php echo 'logs' === $active_tab ? 'is-active' : ''; ?>" data-tab="tab-logs">
							<span class="item-icon"><?php echo self::svg( 'logs', 16 ); // phpcs:ignore ?></span>
							<span class="item-label"><?php esc_html_e( 'Nhật Ký', 'sergio-ai-studio' ); ?></span>
							<?php if ( $log_errors > 0 ) : ?>
								<span class="nav-pill-counter is-error"><?php echo esc_html( $log_errors ); ?></span>
							<?php endif; ?>
						</button>
					</nav>

					<!-- Guardrail Principles Card -->
					<div class="nav-principles-card">
						<span class="principles-kicker"><?php esc_html_e( 'Nguyên tắc bất biến', 'sergio-ai-studio' ); ?></span>
						<ul class="principles-list">
							<li><span class="bullet"></span><?php esc_html_e( 'Fact-first: dữ liệu thật trước', 'sergio-ai-studio' ); ?></li>
							<li><span class="bullet"></span><?php esc_html_e( 'Không bịa số liệu, sự kiện', 'sergio-ai-studio' ); ?></li>
							<li><span class="bullet"></span><?php esc_html_e( 'Luôn lưu Bản nháp an toàn', 'sergio-ai-studio' ); ?></li>
						</ul>
					</div>
				</aside>

				<!-- MAIN WORKSPACE -->
				<main class="studio-workspace">

					<!-- ===================================== -->
					<!-- TAB 1: TỔNG QUAN                     -->
					<!-- ===================================== -->
					<div id="tab-overview" class="studio-tab-panel <?php echo 'overview' === $active_tab ? 'is-active' : ''; ?>">
						<div class="panel-header-block">
							<h1 class="panel-headline"><?php esc_html_e( 'Tổng Quan Hệ Thống', 'sergio-ai-studio' ); ?></h1>
							<p class="panel-subtext"><?php esc_html_e( 'Trung tâm kiểm soát trạng thái vận hành, lịch trình xuất bản và nhật ký hoạt động.', 'sergio-ai-studio' ); ?></p>
						</div>

						<!-- System Health Deck -->
						<div class="studio-deck-strip">
							<div class="deck-cell">
								<span class="deck-micro-label"><?php esc_html_e( 'Kết Nối Gemini API', 'sergio-ai-studio' ); ?></span>
								<div class="deck-value-row">
									<span class="deck-status-dot <?php echo $has_key ? 'dot-active' : 'dot-danger'; ?>"></span>
									<span class="deck-value-text"><?php echo $has_key ? esc_html__( 'Sẵn Sàng', 'sergio-ai-studio' ) : esc_html__( 'Chưa Cấu Hình', 'sergio-ai-studio' ); ?></span>
								</div>
								<span class="deck-meta-foot"><?php echo esc_html( $primary_model ); ?></span>
							</div>

							<div class="deck-cell">
								<span class="deck-micro-label"><?php esc_html_e( 'Tự Động Hóa WP-Cron', 'sergio-ai-studio' ); ?></span>
								<div class="deck-value-row">
									<span class="deck-status-dot <?php echo $scheduler_enabled ? 'dot-active' : 'dot-muted'; ?>"></span>
									<span class="deck-value-text"><?php echo $scheduler_enabled ? esc_html__( 'Đang Chạy', 'sergio-ai-studio' ) : esc_html__( 'Tạm Dừng', 'sergio-ai-studio' ); ?></span>
								</div>
								<span class="deck-meta-foot">
									<?php echo $scheduler_enabled && $next_run_time ? esc_html( sprintf( __( 'Lần tới: %s', 'sergio-ai-studio' ), $next_run_time ) ) : esc_html__( 'Chưa lên lịch', 'sergio-ai-studio' ); ?>
								</span>
							</div>

							<div class="deck-cell">
								<span class="deck-micro-label"><?php esc_html_e( 'Hàng Đợi Chờ Xử Lý', 'sergio-ai-studio' ); ?></span>
								<div class="deck-value-row">
									<span class="deck-numeric"><?php echo (int) $queue_count; ?></span>
									<span class="deck-unit"><?php esc_html_e( 'chủ đề', 'sergio-ai-studio' ); ?></span>
								</div>
								<span class="deck-meta-foot"><?php printf( esc_html__( 'Mỗi đợt sinh %d bài', 'sergio-ai-studio' ), (int) $posts_per_run ); ?></span>
							</div>

							<div class="deck-cell">
								<span class="deck-micro-label"><?php esc_html_e( 'Hôm Nay Đã Tạo', 'sergio-ai-studio' ); ?></span>
								<div class="deck-value-row">
									<span class="deck-numeric"><?php echo (int) $posts_today; ?></span>
									<span class="deck-unit">/ <?php echo $max_posts_per_day > 0 ? (int) $max_posts_per_day : '∞'; ?> <?php esc_html_e( 'bài', 'sergio-ai-studio' ); ?></span>
								</div>
								<span class="deck-meta-foot"><?php esc_html_e( 'Đã kiểm chứng Fact Pack', 'sergio-ai-studio' ); ?></span>
							</div>
						</div>

						<!-- Two Column Operational Layout -->
						<div class="studio-grid-two">
							<section class="studio-surface-box">
								<div class="surface-header">
									<h2 class="surface-title"><?php esc_html_e( 'Khởi Chạy Nhanh Quy Trình', 'sergio-ai-studio' ); ?></h2>
								</div>
								<div class="launchpad-actions">
									<button type="button" class="studio-btn studio-btn-primary" onclick="document.querySelector('[data-tab=tab-writer]').click()">
										<?php echo self::svg( 'writer', 14 ); // phpcs:ignore ?>
										<span><?php esc_html_e( 'Nghiên Cứu & Viết Bài', 'sergio-ai-studio' ); ?></span>
									</button>
									<button type="button" class="studio-btn studio-btn-secondary" onclick="document.querySelector('[data-tab=tab-crawl]').click()">
										<?php echo self::svg( 'crawl', 14 ); // phpcs:ignore ?>
										<span><?php esc_html_e( 'Crawl & Viết Lại', 'sergio-ai-studio' ); ?></span>
									</button>
									<button type="button" class="studio-btn studio-btn-secondary" id="btn_run_cron_overview">
										<?php echo self::svg( 'play', 12 ); // phpcs:ignore ?>
										<span><?php esc_html_e( 'Kích Hoạt 1 Đợt Cron Ngay', 'sergio-ai-studio' ); ?></span>
									</button>
								</div>
								<span id="cron_overview_feedback" class="studio-feedback-inline"></span>
							</section>

							<section class="studio-surface-box">
								<div class="surface-header">
									<h2 class="surface-title"><?php esc_html_e( 'Bài Tiếp Theo Trong Hàng Đợi', 'sergio-ai-studio' ); ?></h2>
									<button type="button" class="surface-action-link" onclick="document.querySelector('[data-tab=tab-scheduler]').click()">
										<?php esc_html_e( 'Xem chi tiết →', 'sergio-ai-studio' ); ?>
									</button>
								</div>

								<?php if ( empty( $queue_items ) ) : ?>
									<div class="studio-empty-state-compact">
										<p><?php esc_html_e( 'Hàng đợi hiện đang trống. Hệ thống sẽ tự động nghiên cứu theo Lĩnh vực ngách (chế độ Hybrid).', 'sergio-ai-studio' ); ?></p>
									</div>
								<?php else : ?>
									<div class="queue-preview-item">
										<span class="queue-tag-pending">#<?php echo esc_html( $queue_items[0]['id'] ); ?></span>
										<strong class="queue-title-text"><?php echo esc_html( $queue_items[0]['topic'] ); ?></strong>
										<span class="queue-time-meta"><?php echo esc_html( $queue_items[0]['created_at'] ); ?></span>
									</div>
								<?php endif; ?>
							</section>
						</div>

						<!-- Recent Audit Timeline -->
						<section class="studio-surface-box" style="margin-top: 20px;">
							<div class="surface-header">
								<h2 class="surface-title"><?php esc_html_e( 'Nhật Ký Thực Thi Gần Đây', 'sergio-ai-studio' ); ?></h2>
								<button type="button" class="surface-action-link" onclick="document.querySelector('[data-tab=tab-logs]').click()">
									<?php esc_html_e( 'Xem tất cả log →', 'sergio-ai-studio' ); ?>
								</button>
							</div>

							<?php
							$recent_logs = array_slice( $logs, 0, 5 );
							if ( empty( $recent_logs ) ) : ?>
								<div class="studio-empty-state-compact">
									<p><?php esc_html_e( 'Chưa có nhật ký nào được ghi nhận.', 'sergio-ai-studio' ); ?></p>
								</div>
							<?php else : ?>
								<table class="studio-data-table">
									<thead>
										<tr>
											<th style="width: 140px;"><?php esc_html_e( 'Thời Gian', 'sergio-ai-studio' ); ?></th>
											<th style="width: 80px;"><?php esc_html_e( 'Cấp Độ', 'sergio-ai-studio' ); ?></th>
											<th style="width: 130px;"><?php esc_html_e( 'Giai Đoạn', 'sergio-ai-studio' ); ?></th>
											<th><?php esc_html_e( 'Thông Điệp', 'sergio-ai-studio' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $recent_logs as $log ) : ?>
											<tr>
												<td class="cell-time"><?php echo esc_html( $log['created_at'] ); ?></td>
												<td><span class="log-level-pill level-<?php echo esc_attr( $log['level'] ); ?>"><?php echo esc_html( strtoupper( $log['level'] ) ); ?></span></td>
												<td class="cell-stage"><code><?php echo esc_html( $log['stage'] ); ?></code></td>
												<td class="cell-message"><?php echo esc_html( $log['message'] ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
						</section>
					</div>

					<!-- ===================================== -->
					<!-- TAB 2: CÀI ĐẶT                       -->
					<!-- ===================================== -->
					<div id="tab-settings" class="studio-tab-panel <?php echo 'settings' === $active_tab ? 'is-active' : ''; ?>">
						<div class="panel-header-block">
							<h1 class="panel-headline"><?php esc_html_e( 'Cài Đặt Cấu Hình', 'sergio-ai-studio' ); ?></h1>
							<p class="panel-subtext"><?php esc_html_e( 'Thiết lập khóa kết nối Google Gemini API, lựa chọn mô hình ngôn ngữ và trạng thái bài viết mặc định.', 'sergio-ai-studio' ); ?></p>
						</div>

						<form method="post" action="options.php" class="studio-form-stack">
							<?php settings_fields( 'sergio_ai_studio_settings_group' ); ?>

							<section class="studio-surface-box">
								<div class="surface-header">
									<h2 class="surface-title"><?php esc_html_e( 'Khóa Google Gemini API', 'sergio-ai-studio' ); ?></h2>
								</div>

								<div class="studio-field-row">
									<label class="studio-label" for="gemini_api_key"><?php esc_html_e( 'API Key', 'sergio-ai-studio' ); ?></label>
									<div class="studio-input-composite">
										<input type="password" id="gemini_api_key" name="sergio_ai_studio_options[gemini_api_key]"
											value="<?php echo esc_attr( $masked_key ); ?>"
											class="studio-input" placeholder="AIzaSy..."
											autocomplete="off"
											<?php disabled( $is_config_key ); ?> />
										<button type="button" class="studio-btn studio-btn-secondary" id="btn_test_conn">
											<?php echo self::svg( 'bolt', 13 ); // phpcs:ignore ?>
											<span><?php esc_html_e( 'Kiểm tra kết nối', 'sergio-ai-studio' ); ?></span>
										</button>
									</div>

									<?php if ( $is_config_key ) : ?>
										<div class="studio-callout-subtle" style="margin-top: 10px;">
											<span class="callout-icon"><?php echo self::svg( 'shield', 14 ); // phpcs:ignore ?></span>
											<span><strong>wp-config.php:</strong> <?php esc_html_e( 'Khóa API đang được khóa an toàn qua hằng số SERGIO_AI_GEMINI_API_KEY.', 'sergio-ai-studio' ); ?></span>
										</div>
									<?php endif; ?>

									<span id="api_conn_feedback" class="studio-feedback-inline"></span>
									<span class="studio-hint-text">
										<?php esc_html_e( 'Khóa được mã hóa bằng AES-256. Khi bạn dán khóa mới, hệ thống tự động đồng bộ danh sách mô hình từ tài khoản của bạn.', 'sergio-ai-studio' ); ?>
									</span>
								</div>
							</section>

							<section class="studio-surface-box">
								<div class="surface-header">
									<h2 class="surface-title"><?php esc_html_e( 'Mô Hình Ngôn Ngữ (LLM)', 'sergio-ai-studio' ); ?></h2>
									<button type="button" class="studio-btn studio-btn-ghost" id="btn_refresh_models">
										<?php echo self::svg( 'refresh', 13 ); // phpcs:ignore ?>
										<span><?php esc_html_e( 'Làm mới danh sách', 'sergio-ai-studio' ); ?></span>
									</button>
								</div>

								<div class="studio-form-grid">
									<div class="studio-field-row">
										<label class="studio-label" for="gemini_model_primary"><?php esc_html_e( 'Mô Hình Chính (Primary)', 'sergio-ai-studio' ); ?></label>
										<select id="gemini_model_primary" name="sergio_ai_studio_options[gemini_model_primary]" class="studio-select">
											<?php if ( ! empty( $models ) ) : ?>
												<?php foreach ( $models as $m ) : ?>
													<option value="<?php echo esc_attr( $m['name'] ); ?>" <?php selected( $primary_model, $m['name'] ); ?>>
														<?php echo esc_html( $m['display_name'] . ' (' . $m['name'] . ')' ); ?>
													</option>
												<?php endforeach; ?>
											<?php else : ?>
												<option value="gemini-2.5-flash" <?php selected( $primary_model, 'gemini-2.5-flash' ); ?>>Gemini 2.5 Flash</option>
												<option value="gemini-3.5-flash-lite" <?php selected( $primary_model, 'gemini-3.5-flash-lite' ); ?>>Gemini 3.5 Flash Lite</option>
												<option value="gemini-2.5-pro" <?php selected( $primary_model, 'gemini-2.5-pro' ); ?>>Gemini 2.5 Pro</option>
											<?php endif; ?>
										</select>
										<span class="studio-hint-text"><?php esc_html_e( 'Dùng cho nghiên cứu dữ kiện và biên soạn nội dung.', 'sergio-ai-studio' ); ?></span>
									</div>

									<div class="studio-field-row">
										<label class="studio-label" for="gemini_model_fallback"><?php esc_html_e( 'Mô Hình Dự Phòng (Fallback)', 'sergio-ai-studio' ); ?></label>
										<select id="gemini_model_fallback" name="sergio_ai_studio_options[gemini_model_fallback]" class="studio-select">
											<?php if ( ! empty( $models ) ) : ?>
												<?php foreach ( $models as $m ) : ?>
													<option value="<?php echo esc_attr( $m['name'] ); ?>" <?php selected( $fallback_model, $m['name'] ); ?>>
														<?php echo esc_html( $m['display_name'] . ' (' . $m['name'] . ')' ); ?>
													</option>
												<?php endforeach; ?>
											<?php else : ?>
												<option value="gemini-3.5-flash-lite" <?php selected( $fallback_model, 'gemini-3.5-flash-lite' ); ?>>Gemini 3.5 Flash Lite</option>
												<option value="gemini-2.5-flash" <?php selected( $fallback_model, 'gemini-2.5-flash' ); ?>>Gemini 2.5 Flash</option>
											<?php endif; ?>
										</select>
										<span class="studio-hint-text"><?php esc_html_e( 'Tự động kích hoạt khi model chính gặp lỗi quota (429) hoặc quá tải (503).', 'sergio-ai-studio' ); ?></span>
									</div>
								</div>
							</section>

							<section class="studio-surface-box">
								<div class="surface-header">
									<h2 class="surface-title"><?php esc_html_e( 'Thiết Lập Mặc Định', 'sergio-ai-studio' ); ?></h2>
								</div>

								<div class="studio-form-grid">
									<div class="studio-field-row">
										<label class="studio-label" for="default_language"><?php esc_html_e( 'Ngôn Ngữ Toàn Site', 'sergio-ai-studio' ); ?></label>
										<select id="default_language" name="sergio_ai_studio_options[default_language]" class="studio-select">
											<option value="vi" <?php selected( $language, 'vi' ); ?>>Tiếng Việt</option>
											<option value="en" <?php selected( $language, 'en' ); ?>>English</option>
										</select>
									</div>

									<div class="studio-field-row">
										<label class="studio-label"><?php esc_html_e( 'Trạng Thái Xuất Bản Mặc Định', 'sergio-ai-studio' ); ?></label>
										<select name="sergio_ai_studio_options[default_post_status]" class="studio-select">
											<option value="draft" selected><?php esc_html_e( 'Bản nháp (Draft — Khuyên dùng)', 'sergio-ai-studio' ); ?></option>
											<option value="publish"><?php esc_html_e( 'Đăng ngay lập tức (Publish)', 'sergio-ai-studio' ); ?></option>
										</select>
										<span class="studio-hint-text" style="color: #e11d48;">
											<?php esc_html_e( 'Khuyến nghị: Giữ Bản nháp để người biên tập kiểm duyệt nội dung trước khi công khai.', 'sergio-ai-studio' ); ?>
										</span>
									</div>
								</div>
							</section>

							<div class="studio-form-actions">
								<button type="submit" class="studio-btn studio-btn-primary studio-btn-lg">
									<span><?php esc_html_e( 'Lưu Cài Đặt', 'sergio-ai-studio' ); ?></span>
								</button>
							</div>
						</form>
					</div>

					<!-- ======================================================= -->
					<!-- TAB 3: LÊN LỊCH & TỰ ĐỘNG HÓA (Professional Scheduler) -->
					<!-- ======================================================= -->
					<div id="tab-scheduler" class="studio-tab-panel <?php echo 'scheduler' === $active_tab ? 'is-active' : ''; ?>">
						<div class="panel-header-block">
							<h1 class="panel-headline"><?php esc_html_e( 'Lên Lịch Tự Động & Hàng Đợi Chủ Đề', 'sergio-ai-studio' ); ?></h1>
							<p class="panel-subtext"><?php esc_html_e( 'Thiết lập chu kỳ chạy WP-Cron, số lượng bài viết mỗi đợt, chiến lược chọn chủ đề và quy chuẩn bài viết.', 'sergio-ai-studio' ); ?></p>
						</div>

						<form method="post" action="options.php" class="studio-form-stack">
							<?php settings_fields( 'sergio_ai_studio_settings_group' ); ?>
							<input type="hidden" name="sergio_ai_studio_options[gemini_api_key]" value="<?php echo esc_attr( $masked_key ); ?>" />
							<input type="hidden" name="sergio_ai_studio_options[gemini_model_primary]" value="<?php echo esc_attr( $primary_model ); ?>" />
							<input type="hidden" name="sergio_ai_studio_options[gemini_model_fallback]" value="<?php echo esc_attr( $fallback_model ); ?>" />
							<input type="hidden" name="sergio_ai_studio_options[default_language]" value="<?php echo esc_attr( $language ); ?>" />

							<div class="studio-grid-two">

								<!-- CARD 1: CHU KỲ & SỐ LƯỢNG BÀI VIẾT (Cadence & Batch Controls) -->
								<section class="studio-surface-box">
									<div class="surface-header">
										<h2 class="surface-title">
											<?php echo self::svg( 'clock', 16 ); // phpcs:ignore ?>
											<span><?php esc_html_e( 'Chu Kỳ & Số Lượng Bài Viết', 'sergio-ai-studio' ); ?></span>
										</h2>
									</div>

									<!-- Master Toggle Switch -->
									<div class="studio-toggle-row">
										<label class="studio-switch">
											<input type="checkbox" name="sergio_ai_studio_options[scheduler_enabled]" value="1" <?php checked( $scheduler_enabled, true ); ?> />
											<span class="switch-slider"></span>
										</label>
										<div class="toggle-meta">
											<span class="toggle-label"><?php esc_html_e( 'Kích hoạt lịch trình tự động', 'sergio-ai-studio' ); ?></span>
											<span class="toggle-desc"><?php esc_html_e( 'Hệ thống tự động chạy ngầm theo chu kỳ WP-Cron.', 'sergio-ai-studio' ); ?></span>
										</div>
									</div>

									<!-- Frequency selector -->
									<div class="studio-field-row" style="margin-top: 16px;">
										<label class="studio-label" for="schedule_frequency_select"><?php esc_html_e( 'Tần Suất Kích Hoạt', 'sergio-ai-studio' ); ?></label>
										<select name="sergio_ai_studio_options[schedule_frequency]" id="schedule_frequency_select" class="studio-select">
											<option value="every_one_hour" <?php selected( $frequency, 'every_one_hour' ); ?>><?php esc_html_e( 'Mỗi 1 giờ (1 Hour)', 'sergio-ai-studio' ); ?></option>
											<option value="every_two_hours" <?php selected( $frequency, 'every_two_hours' ); ?>><?php esc_html_e( 'Mỗi 2 giờ', 'sergio-ai-studio' ); ?></option>
											<option value="every_four_hours" <?php selected( $frequency, 'every_four_hours' ); ?>><?php esc_html_e( 'Mỗi 4 giờ', 'sergio-ai-studio' ); ?></option>
											<option value="every_six_hours" <?php selected( $frequency, 'every_six_hours' ); ?>><?php esc_html_e( 'Mỗi 6 giờ', 'sergio-ai-studio' ); ?></option>
											<option value="twicedaily" <?php selected( $frequency, 'twicedaily' ); ?>><?php esc_html_e( 'Mỗi 12 giờ (2 lần/ngày)', 'sergio-ai-studio' ); ?></option>
											<option value="daily" <?php selected( $frequency, 'daily' ); ?>><?php esc_html_e( 'Hàng ngày (1 lần/ngày)', 'sergio-ai-studio' ); ?></option>
											<option value="weekly" <?php selected( $frequency, 'weekly' ); ?>><?php esc_html_e( 'Hàng tuần', 'sergio-ai-studio' ); ?></option>
											<option value="sergio_custom_interval" <?php selected( $frequency, 'sergio_custom_interval' ); ?>><?php esc_html_e( 'Tùy chỉnh số giờ cụ thể...', 'sergio-ai-studio' ); ?></option>
										</select>
									</div>

									<div class="studio-field-row" id="custom_hours_wrapper" style="display: <?php echo 'sergio_custom_interval' === $frequency ? 'block' : 'none'; ?>;">
										<label class="studio-label" for="schedule_custom_hours"><?php esc_html_e( 'Nhập Số Giờ Chu Kỳ (1 - 168 giờ)', 'sergio-ai-studio' ); ?></label>
										<input type="number" id="schedule_custom_hours" name="sergio_ai_studio_options[schedule_custom_hours]"
											value="<?php echo esc_attr( $custom_hours ); ?>" min="1" max="168" class="studio-input" style="max-width: 160px;" />
										<span class="studio-hint-text"><?php esc_html_e( 'Ví dụ: Điền 3 = cứ sau 3 giờ cron sẽ chạy 1 phiên.', 'sergio-ai-studio' ); ?></span>
									</div>

									<!-- Posts per run (Number of articles generated per execution) -->
									<div class="studio-form-grid" style="margin-top: 14px;">
										<div class="studio-field-row">
											<label class="studio-label" for="schedule_posts_per_run"><?php esc_html_e( 'Số Bài Mỗi Lần Chạy', 'sergio-ai-studio' ); ?></label>
											<select name="sergio_ai_studio_options[schedule_posts_per_run]" id="schedule_posts_per_run" class="studio-select">
												<option value="1" <?php selected( $posts_per_run, 1 ); ?>>1 bài / phiên (Chuẩn)</option>
												<option value="2" <?php selected( $posts_per_run, 2 ); ?>>2 bài / phiên</option>
												<option value="3" <?php selected( $posts_per_run, 3 ); ?>>3 bài / phiên</option>
												<option value="5" <?php selected( $posts_per_run, 5 ); ?>>5 bài / phiên</option>
											</select>
											<span class="studio-hint-text"><?php esc_html_e( 'Số bài viết được sinh ra và lưu nháp trong 1 lần kích hoạt.', 'sergio-ai-studio' ); ?></span>
										</div>

										<!-- Max posts per day limit -->
										<div class="studio-field-row">
											<label class="studio-label" for="schedule_max_posts_per_day"><?php esc_html_e( 'Trần Giới Hạn / Ngày', 'sergio-ai-studio' ); ?></label>
											<select name="sergio_ai_studio_options[schedule_max_posts_per_day]" id="schedule_max_posts_per_day" class="studio-select">
												<option value="5" <?php selected( $max_posts_per_day, 5 ); ?>>Tối đa 5 bài / ngày</option>
												<option value="10" <?php selected( $max_posts_per_day, 10 ); ?>>Tối đa 10 bài / ngày</option>
												<option value="20" <?php selected( $max_posts_per_day, 20 ); ?>>Tối đa 20 bài / ngày</option>
												<option value="50" <?php selected( $max_posts_per_day, 50 ); ?>>Tối đa 50 bài / ngày</option>
												<option value="0" <?php selected( $max_posts_per_day, 0 ); ?>>Không giới hạn</option>
											</select>
											<span class="studio-hint-text"><?php esc_html_e( 'Ngăn ngừa cháy hạn mức API Gemini khi đặt chu kỳ dày.', 'sergio-ai-studio' ); ?></span>
										</div>
									</div>

									<!-- Live Status Card -->
									<div class="cron-status-card" style="margin-top: 16px;">
										<span class="card-kicker"><?php esc_html_e( 'Trạng thái hoạt động WP-Cron', 'sergio-ai-studio' ); ?></span>
										<div class="card-detail-row">
											<?php if ( $scheduler_enabled && $next_run_time ) : ?>
												<span class="deck-status-dot dot-active"></span>
												<strong style="color: #059669;">
													<?php printf( esc_html__( 'Đang hoạt động — Lần chạy tiếp theo: %s', 'sergio-ai-studio' ), esc_html( $next_run_time ) ); ?>
												</strong>
											<?php elseif ( $scheduler_enabled ) : ?>
												<span class="deck-status-dot dot-active"></span>
												<strong style="color: #059669;">
													<?php esc_html_e( 'Đang kích hoạt (Sẽ tự động chạy theo chu kỳ)', 'sergio-ai-studio' ); ?>
												</strong>
											<?php else : ?>
												<span class="deck-status-dot dot-muted"></span>
												<strong style="color: #64748b;">
													<?php esc_html_e( 'Lịch trình hiện đang tắt', 'sergio-ai-studio' ); ?>
												</strong>
											<?php endif; ?>
										</div>
										<div style="font-size: 12px; color: #64748b; margin-top: 6px;">
											<?php printf( esc_html__( 'Thống kê hôm nay: %d bài đã hoàn thành.', 'sergio-ai-studio' ), (int) $posts_today ); ?>
											<?php
											if ( 'sergio_custom_interval' === $frequency ) {
												printf( esc_html__( ' (Quy tắc: Cứ mỗi %d giờ sẽ tự động viết %d bài).', 'sergio-ai-studio' ), (int) $custom_hours, (int) $posts_per_run );
											} else {
												printf( esc_html__( ' (Quy tắc: Mỗi phiên sẽ tự động viết %d bài).', 'sergio-ai-studio' ), (int) $posts_per_run );
											}
											?>
										</div>
									</div>
								</section>

								<!-- CARD 2: CHIẾN LƯỢC CHỦ ĐỀ & QUY CHUẨN NỘI DUNG (Topic Strategy & Editorial Rules) -->
								<section class="studio-surface-box">
									<div class="surface-header">
										<h2 class="surface-title">
											<?php echo self::svg( 'sparkles', 16 ); // phpcs:ignore ?>
											<span><?php esc_html_e( 'Chiến Lược Chủ Đề & Định Dạng', 'sergio-ai-studio' ); ?></span>
										</h2>
									</div>

									<!-- Topic Strategy Mode -->
									<div class="studio-field-row">
										<label class="studio-label" for="schedule_topic_mode"><?php esc_html_e( 'Nguồn Cung Cấp Chủ Đề', 'sergio-ai-studio' ); ?></label>
										<select name="sergio_ai_studio_options[schedule_topic_mode]" id="schedule_topic_mode" class="studio-select">
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
										<span class="studio-hint-text">
											<?php esc_html_e( 'Khuyên dùng "Kết Hợp Thông Minh": Hệ thống sẽ lấy bài từ Hàng đợi; nếu bạn quên nạp, AI sẽ tự động nghiên cứu tin tức trong Lĩnh vực bên dưới.', 'sergio-ai-studio' ); ?>
										</span>
									</div>

									<!-- Niche Topic for Auto Discovery -->
									<div class="studio-field-row" id="niche_topic_wrapper">
										<label class="studio-label" for="schedule_niche_topic"><?php esc_html_e( 'Lĩnh Vực / Từ Khóa Ngách AI Tự Động Nghiên Cứu', 'sergio-ai-studio' ); ?></label>
										<input type="text" id="schedule_niche_topic" name="sergio_ai_studio_options[schedule_niche_topic]"
											value="<?php echo esc_attr( $niche_topic ); ?>" class="studio-input"
											placeholder="<?php esc_attr_e( 'Ví dụ: Tin tức AI, ChatGPT, công cụ AI mới, WordPress, thiết kế web...', 'sergio-ai-studio' ); ?>" />
										<span class="studio-hint-text"><?php esc_html_e( 'Khi tự động nghiên cứu, Gemini sẽ tìm kiếm sự kiện và thực thể thật mới nhất trong lĩnh vực này.', 'sergio-ai-studio' ); ?></span>
									</div>

									<!-- Article format & post settings -->
									<div class="studio-form-grid" style="margin-top: 14px;">
										<div class="studio-field-row">
											<label class="studio-label" for="schedule_target_length"><?php esc_html_e( 'Độ Dài Bài Viết Mục Tiêu', 'sergio-ai-studio' ); ?></label>
											<select name="sergio_ai_studio_options[schedule_target_length]" id="schedule_target_length" class="studio-select">
												<option value="standard" <?php selected( $target_length, 'standard' ); ?>><?php esc_html_e( 'Tiêu chuẩn (~800 - 1,200 từ)', 'sergio-ai-studio' ); ?></option>
												<option value="short" <?php selected( $target_length, 'short' ); ?>><?php esc_html_e( 'Ngắn gọn súc tích (~500 - 800 từ)', 'sergio-ai-studio' ); ?></option>
												<option value="deep_dive" <?php selected( $target_length, 'deep_dive' ); ?>><?php esc_html_e( 'Phân tích chuyên sâu (~1,500 - 2,500 từ)', 'sergio-ai-studio' ); ?></option>
											</select>
										</div>

										<div class="studio-field-row">
											<label class="studio-label" for="default_schedule_author"><?php esc_html_e( 'Tác Giả Bài Viết', 'sergio-ai-studio' ); ?></label>
											<select name="sergio_ai_studio_options[default_schedule_author]" id="default_schedule_author" class="studio-select">
												<?php foreach ( $authors as $auth ) : ?>
													<option value="<?php echo esc_attr( $auth->ID ); ?>" <?php selected( $default_sched_auth, $auth->ID ); ?>>
														<?php echo esc_html( $auth->display_name ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="studio-form-grid">
										<div class="studio-field-row">
											<label class="studio-label" for="default_schedule_cat"><?php esc_html_e( 'Chuyên Mục Mặc Định', 'sergio-ai-studio' ); ?></label>
											<select name="sergio_ai_studio_options[default_schedule_cat]" id="default_schedule_cat" class="studio-select">
												<option value="0"><?php esc_html_e( '-- Chọn chuyên mục --', 'sergio-ai-studio' ); ?></option>
												<?php foreach ( $categories as $cat ) : ?>
													<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $default_sched_cat, $cat->term_id ); ?>>
														<?php echo esc_html( $cat->name ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</div>

										<div class="studio-field-row">
											<label class="studio-label"><?php esc_html_e( 'Trạng Thái Khi Sinh', 'sergio-ai-studio' ); ?></label>
											<select name="sergio_ai_studio_options[default_schedule_status]" class="studio-select">
												<option value="draft" <?php selected( $default_sched_stat, 'draft' ); ?>><?php esc_html_e( 'Bản nháp (Draft — Khuyên dùng)', 'sergio-ai-studio' ); ?></option>
												<option value="publish" <?php selected( $default_sched_stat, 'publish' ); ?>><?php esc_html_e( 'Xuất bản ngay (Publish)', 'sergio-ai-studio' ); ?></option>
											</select>
										</div>
									</div>
								</section>

							</div><!-- .studio-grid-two -->

							<div class="studio-form-actions" style="margin-top: 10px;">
								<button type="submit" class="studio-btn studio-btn-primary studio-btn-lg">
									<span><?php esc_html_e( 'Lưu Toàn Bộ Cấu Hình Lịch Trình', 'sergio-ai-studio' ); ?></span>
								</button>
							</div>
						</form>

						<!-- CARD 3: BÀN LÀM VIỆC HÀNG ĐỢI (Queue Workbench) -->
						<div class="studio-grid-two" style="margin-top: 24px;">
							<!-- Add to Queue Card -->
							<section class="studio-surface-box">
								<div class="surface-header">
									<h2 class="surface-title">
										<span><?php esc_html_e( 'Nạp Chủ Đề Vào Hàng Đợi (Queue)', 'sergio-ai-studio' ); ?></span>
									</h2>
								</div>
								<p class="surface-subtext">
									<?php esc_html_e( 'Dán danh sách chủ đề hoặc ý định tìm kiếm (mỗi dòng một chủ đề). Hệ thống sẽ xử lý tuần tự theo từng chu kỳ.', 'sergio-ai-studio' ); ?>
								</p>

								<div class="studio-field-row">
									<textarea id="queue_topics_input" rows="6" class="studio-textarea"
										placeholder="<?php esc_attr_e( "Đánh giá chi tiết mô hình Claude 3.7 Sonnet\nMeta ra mắt kính Ray-Ban thông minh tích hợp AI\nOpenAI công bố lộ trình tác nhân tự trị 2026...", 'sergio-ai-studio' ); ?>"></textarea>
								</div>

								<div class="studio-field-row">
									<label class="studio-label" for="queue_category_select"><?php esc_html_e( 'Chuyên Mục Chỉ Định Cho Đợt Nạp Này', 'sergio-ai-studio' ); ?></label>
									<div class="studio-input-composite">
										<select id="queue_category_select" class="studio-select">
											<option value="0"><?php esc_html_e( '-- Theo cài đặt mặc định --', 'sergio-ai-studio' ); ?></option>
											<?php foreach ( $categories as $cat ) : ?>
												<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
											<?php endforeach; ?>
										</select>
										<button type="button" class="studio-btn studio-btn-primary" id="btn_add_to_queue">
											<span><?php esc_html_e( 'Nạp Hàng Đợi', 'sergio-ai-studio' ); ?></span>
										</button>
									</div>
									<span id="queue_add_feedback" class="studio-feedback-inline"></span>
								</div>
							</section>

							<!-- Trigger Cron Now Card -->
							<section class="studio-surface-box">
								<div class="surface-header">
									<h2 class="surface-title"><?php esc_html_e( 'Chạy Thử Nghiệm Ngay Lập Tức', 'sergio-ai-studio' ); ?></h2>
								</div>
								<p class="surface-subtext">
									<?php printf( esc_html__( 'Kích hoạt ngay 1 đợt xử lý (sẽ tạo %d bài theo đúng quy tắc đã cấu hình). Không cần chờ đến giờ chạy tiếp theo.', 'sergio-ai-studio' ), (int) $posts_per_run ); ?>
								</p>

								<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
									<div style="font-size: 13px; color: #334155; margin-bottom: 4px;">
										<strong><?php esc_html_e( 'Chế độ hiện tại:', 'sergio-ai-studio' ); ?></strong>
										<?php
										if ( 'queue_only' === $topic_mode ) {
											esc_html_e( 'Chỉ lấy từ Hàng Đợi', 'sergio-ai-studio' );
										} elseif ( 'ai_discover' === $topic_mode ) {
											esc_html_e( 'AI tự động nghiên cứu theo Lĩnh vực ngách', 'sergio-ai-studio' );
										} else {
											esc_html_e( 'Kết Hợp Thông Minh (Hàng đợi + Lĩnh vực ngách)', 'sergio-ai-studio' );
										}
										?>
									</div>
									<div style="font-size: 12px; color: #64748b;">
										<?php printf( esc_html__( 'Quy mô đợt: %d bài viết / phiên', 'sergio-ai-studio' ), (int) $posts_per_run ); ?>
									</div>
								</div>

								<button type="button" class="studio-btn studio-btn-secondary studio-btn-lg" id="btn_run_cron_now" style="width: 100%; justify-content: center;">
									<?php echo self::svg( 'play', 14 ); // phpcs:ignore ?>
									<span><?php printf( esc_html__( 'Chạy Thử Ngay %d Bài (Run Batch Now)', 'sergio-ai-studio' ), (int) $posts_per_run ); ?></span>
								</button>
								<span id="cron_run_feedback" class="studio-feedback-inline"></span>
							</section>
						</div>

						<!-- Queue Table (Full width) -->
						<section class="studio-surface-box" style="margin-top: 20px;">
							<div class="surface-header">
								<h2 class="surface-title">
									<?php esc_html_e( 'Danh Sách Hàng Đợi Hiện Tại', 'sergio-ai-studio' ); ?>
									<span class="surface-counter-pill"><?php echo count( $queue_items ); ?></span>
								</h2>
							</div>

							<table class="studio-data-table" id="queue_items_table">
								<thead>
									<tr>
										<th style="width: 60px;">ID</th>
										<th><?php esc_html_e( 'Chủ Đề Nghiên Cứu', 'sergio-ai-studio' ); ?></th>
										<th style="width: 110px;"><?php esc_html_e( 'Trạng Thái', 'sergio-ai-studio' ); ?></th>
										<th style="width: 70px; text-align: center;"><?php esc_html_e( 'Lần Thử', 'sergio-ai-studio' ); ?></th>
										<th style="width: 150px;"><?php esc_html_e( 'Thời Gian Tạo', 'sergio-ai-studio' ); ?></th>
										<th style="width: 110px; text-align: center;"><?php esc_html_e( 'Thao Tác', 'sergio-ai-studio' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $queue_items ) ) : ?>
										<tr id="queue_empty_row">
											<td colspan="6" class="cell-empty-text">
												<?php esc_html_e( 'Hàng đợi hiện đang trống. Bạn có thể nạp chủ đề ở phía trên hoặc để AI tự động nghiên cứu theo Lĩnh vực ngách.', 'sergio-ai-studio' ); ?>
											</td>
										</tr>
									<?php else : ?>
										<?php foreach ( $queue_items as $q ) : ?>
											<tr id="queue-row-<?php echo esc_attr( $q['id'] ); ?>">
												<td class="cell-id">#<?php echo esc_html( $q['id'] ); ?></td>
												<td>
													<strong class="item-primary-text"><?php echo esc_html( $q['topic'] ); ?></strong>
													<?php if ( ! empty( $q['last_error'] ) ) : ?>
														<div class="item-error-subtext" style="color: #f43f5e; font-size: 11px; margin-top: 3px;"><?php echo esc_html( $q['last_error'] ); ?></div>
													<?php endif; ?>
												</td>
												<td><span class="status-badge-inline status-<?php echo esc_attr( $q['status'] ); ?>"><?php echo esc_html( strtoupper( $q['status'] ) ); ?></span></td>
												<td style="text-align: center; font-size: 13px; color: #64748b;"><?php echo (int) ( $q['attempts'] ?? 0 ); ?>/3</td>
												<td class="cell-time"><?php echo esc_html( $q['created_at'] ); ?></td>
												<td style="text-align: center; white-space: nowrap;">
													<?php if ( 'failed' === $q['status'] ) : ?>
														<button type="button" class="studio-icon-btn sergio-btn-retry-queue" data-id="<?php echo esc_attr( $q['id'] ); ?>" title="<?php esc_attr_e( 'Thử lại chủ đề này', 'sergio-ai-studio' ); ?>" style="color: #4f46e5;">
															<?php echo self::svg( 'refresh', 13 ); // phpcs:ignore ?>
														</button>
													<?php elseif ( 'pending' === $q['status'] ) : ?>
														<button type="button" class="studio-icon-btn sergio-btn-priority-queue" data-id="<?php echo esc_attr( $q['id'] ); ?>" title="<?php esc_attr_e( 'Đẩy lên đầu hàng đợi ưu tiên', 'sergio-ai-studio' ); ?>" style="color: #059669;">
															<?php echo self::svg( 'arrow-up', 13 ); // phpcs:ignore ?>
														</button>
													<?php endif; ?>
													<button type="button" class="studio-icon-btn sergio-btn-del-queue" data-id="<?php echo esc_attr( $q['id'] ); ?>" title="<?php esc_attr_e( 'Xóa mục này', 'sergio-ai-studio' ); ?>">
														<?php echo self::svg( 'trash', 14 ); // phpcs:ignore ?>
													</button>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</section>
					</div>

					<!-- ===================================== -->
					<!-- TAB 4: NGHIÊN CỨU & VIẾT BÀI         -->
					<!-- ===================================== -->
					<div id="tab-writer" class="studio-tab-panel <?php echo 'writer' === $active_tab ? 'is-active' : ''; ?>">
						<div class="panel-header-block">
							<h1 class="panel-headline"><?php esc_html_e( 'Nghiên Cứu Dữ Kiện & Viết Bài', 'sergio-ai-studio' ); ?></h1>
							<p class="panel-subtext"><?php esc_html_e( 'Hệ thống tra cứu dữ kiện web bằng Google Search Grounding, lập Fact Pack đối chứng và chấp bút bài viết độc bản chuẩn Rank Math SEO.', 'sergio-ai-studio' ); ?></p>
						</div>

						<section class="studio-surface-box">
							<div class="studio-form-grid">
								<div class="studio-field-row" style="grid-column: 1 / -1;">
									<label class="studio-label" for="writer_seed_query"><?php esc_html_e( 'Chủ Đề Hoặc Ý Định Tìm Kiếm (*)', 'sergio-ai-studio' ); ?></label>
									<input type="text" id="writer_seed_query" class="studio-input"
										placeholder="<?php esc_attr_e( 'Ví dụ: OpenAI ra mắt mô hình o3-mini, Meta Llama 4, chip M4 Apple...', 'sergio-ai-studio' ); ?>" />
									<span class="studio-hint-text">
										<?php esc_html_e( 'Hệ thống sẽ bóc tách các thực thể thật và xây dựng tiêu đề báo chí độc bản từ dữ kiện kiểm chứng, không dùng nguyên văn chuỗi này.', 'sergio-ai-studio' ); ?>
									</span>
								</div>

								<div class="studio-field-row">
									<label class="studio-label" for="writer_category"><?php esc_html_e( 'Chuyên Mục Đích', 'sergio-ai-studio' ); ?></label>
									<select id="writer_category" class="studio-select">
										<option value="0"><?php esc_html_e( '-- Chọn chuyên mục --', 'sergio-ai-studio' ); ?></option>
										<?php foreach ( $categories as $cat ) : ?>
											<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="studio-field-row">
									<label class="studio-label" for="writer_language"><?php esc_html_e( 'Ngôn Ngữ Bài Viết', 'sergio-ai-studio' ); ?></label>
									<select id="writer_language" class="studio-select">
										<option value="vi" <?php selected( $language, 'vi' ); ?>>Tiếng Việt (Vietnamese)</option>
										<option value="en" <?php selected( $language, 'en' ); ?>>English (Global)</option>
									</select>
								</div>
							</div>

							<div class="studio-form-actions" style="margin-top: 20px;">
								<button type="button" class="studio-btn studio-btn-primary studio-btn-lg" id="btn_run_pipeline">
									<?php echo self::svg( 'sparkles', 16 ); // phpcs:ignore ?>
									<span><?php esc_html_e( 'Khởi Chạy Fact Pack & Viết Bài', 'sergio-ai-studio' ); ?></span>
								</button>
							</div>
						</section>

						<!-- Linear Progress Stepper -->
						<div id="pipeline_progress_box" class="studio-stepper-box" style="display: none;">
							<div class="stepper-status-header">
								<span class="stepper-spinner"></span>
								<span id="pipeline_progress_msg"><?php esc_html_e( 'Đang khởi tạo kết nối...', 'sergio-ai-studio' ); ?></span>
							</div>
							<div id="pipeline_steps_list" class="stepper-items-track"></div>
						</div>

						<!-- Result Card -->
						<div id="pipeline_result_card" class="studio-result-panel" style="display: none;"></div>
					</div>

					<!-- ===================================== -->
					<!-- TAB 5: CRAWL & VIẾT LẠI             -->
					<!-- ===================================== -->
					<div id="tab-crawl" class="studio-tab-panel <?php echo 'crawl' === $active_tab ? 'is-active' : ''; ?>">
						<div class="panel-header-block">
							<h1 class="panel-headline"><?php esc_html_e( 'Crawl & Viết Lại Nội Dung', 'sergio-ai-studio' ); ?></h1>
							<p class="panel-subtext"><?php esc_html_e( 'Thu thập nội dung từ các trang tin tức bên ngoài, bóc tách bài viết sạch qua thuật toán DOM, và dùng Gemini tái bút bằng góc nhìn riêng biệt kèm trích dẫn nguồn.', 'sergio-ai-studio' ); ?></p>
						</div>

						<div class="studio-grid-two">
							<section class="studio-surface-box">
								<div class="surface-header">
									<h2 class="surface-title"><?php esc_html_e( 'Nguồn URL Cần Thu Thập', 'sergio-ai-studio' ); ?></h2>
								</div>

								<div class="studio-field-row">
									<label class="studio-label" for="crawl_urls"><?php esc_html_e( 'Danh Sách URL (Mỗi dòng một URL)', 'sergio-ai-studio' ); ?></label>
									<textarea id="crawl_urls" rows="8" class="studio-textarea studio-code-field"
										placeholder="https://techcrunch.com/2026/10/31/ai-news/&#10;https://venturebeat.com/ai/new-model/&#10;https://www.theverge.com/..."></textarea>
									<span class="studio-hint-text">
										<?php esc_html_e( 'Chỉ cho phép http/https. Hệ thống tự động kích hoạt bộ lọc SSRF chặn loopback, private IP (127.x, 192.168.x, 10.x, cloud metadata). Giới hạn tối đa 500KB/trang.', 'sergio-ai-studio' ); ?>
									</span>
								</div>

								<div class="studio-field-row">
									<label class="studio-label" for="crawl_category"><?php esc_html_e( 'Chuyên Mục Chỉ Định', 'sergio-ai-studio' ); ?></label>
									<select id="crawl_category" class="studio-select">
										<option value="0"><?php esc_html_e( '-- Chọn chuyên mục --', 'sergio-ai-studio' ); ?></option>
										<?php foreach ( $categories as $cat ) : ?>
											<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</section>

							<section class="studio-surface-box">
								<div class="surface-header">
									<h2 class="surface-title"><?php esc_html_e( 'Tham Số Tái Bút (Rewriting)', 'sergio-ai-studio' ); ?></h2>
								</div>

								<div class="studio-field-row">
									<label class="studio-label" for="crawl_language"><?php esc_html_e( 'Ngôn Ngữ Bài Viết Mới', 'sergio-ai-studio' ); ?></label>
									<select id="crawl_language" class="studio-select">
										<option value="vi" <?php selected( $language, 'vi' ); ?>>Tiếng Việt (Vietnamese)</option>
										<option value="en" <?php selected( $language, 'en' ); ?>>English (Global)</option>
									</select>
								</div>

								<div class="studio-field-row">
									<label class="studio-label" for="crawl_style"><?php esc_html_e( 'Văn Phong & Cấu Trúc', 'sergio-ai-studio' ); ?></label>
									<select id="crawl_style" class="studio-select">
										<option value="news"><?php esc_html_e( 'Tin tức báo chí chuẩn mực', 'sergio-ai-studio' ); ?></option>
										<option value="analysis"><?php esc_html_e( 'Phân tích chuyên sâu & bình luận', 'sergio-ai-studio' ); ?></option>
										<option value="tutorial"><?php esc_html_e( 'Hướng dẫn thực hành', 'sergio-ai-studio' ); ?></option>
									</select>
								</div>

								<div class="studio-field-row">
									<label class="studio-label" for="crawl_max_chars"><?php esc_html_e( 'Giới Hạn Ký Tự Nguồn', 'sergio-ai-studio' ); ?></label>
									<select id="crawl_max_chars" class="studio-select">
										<option value="4000">4,000 ký tự (~600 từ)</option>
										<option value="8000" selected>8,000 ký tự (~1,200 từ) — Chuẩn</option>
										<option value="12000">12,000 ký tự (~1,800 từ)</option>
									</select>
									<span class="studio-hint-text"><?php esc_html_e( 'Nhiều ký tự hơn giúp truyền tải đầy đủ ngữ cảnh bài báo gốc.', 'sergio-ai-studio' ); ?></span>
								</div>

								<div class="studio-callout-subtle" style="margin-top: 14px;">
									<span class="callout-icon"><?php echo self::svg( 'shield', 14 ); // phpcs:ignore ?></span>
									<span><?php esc_html_e( 'Nội dung luôn được viết lại hoàn toàn, không sao chép nguyên văn, tự động gắn trích dẫn nguồn ở cuối bài và lưu nháp an toàn.', 'sergio-ai-studio' ); ?></span>
								</div>
							</section>
						</div>

						<div class="studio-form-actions" style="margin-top: 20px;">
							<button type="button" class="studio-btn studio-btn-primary studio-btn-lg" id="btn_crawl_rewrite">
								<?php echo self::svg( 'crawl', 16 ); // phpcs:ignore ?>
								<span><?php esc_html_e( 'Crawl & Viết Lại', 'sergio-ai-studio' ); ?></span>
							</button>
						</div>

						<div id="crawl_progress_box" class="studio-stepper-box" style="display: none;">
							<div class="stepper-status-header">
								<span class="stepper-spinner"></span>
								<span id="crawl_progress_msg"><?php esc_html_e( 'Đang kết nối crawl...', 'sergio-ai-studio' ); ?></span>
							</div>
							<div id="crawl_steps_list" class="stepper-items-track"></div>
						</div>

						<div id="crawl_results_wrap" style="display: none; margin-top: 20px;">
							<section class="studio-surface-box">
								<div class="surface-header">
									<h2 class="surface-title"><?php esc_html_e( 'Kết Quả Thu Thập & Tái Bút', 'sergio-ai-studio' ); ?></h2>
								</div>
								<table class="studio-data-table" id="crawl_results_table">
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
							</section>
						</div>
					</div>

					<!-- ===================================== -->
					<!-- TAB 5: AUDIT & TỐI ƯU SEO (AI On-Page)-->
					<!-- ===================================== -->
					<div id="tab-seo" class="studio-tab-panel <?php echo 'seo' === $active_tab ? 'is-active' : ''; ?>">
						<div class="panel-header-block">
							<div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
								<div>
									<h1 class="panel-headline"><?php esc_html_e( 'Audit & Tối Ưu SEO Toàn Diện', 'sergio-ai-studio' ); ?></h1>
									<p class="panel-subtext"><?php esc_html_e( 'Quét sức khỏe SEO on-page toàn site, đo lường theo tiêu chuẩn Rank Math và kích hoạt Gemini AI tối ưu tiêu đề, mô tả, từ khóa LSI.', 'sergio-ai-studio' ); ?></p>
								</div>
								<button type="button" class="studio-btn studio-btn-secondary" id="btn_run_site_seo_audit">
									<?php echo self::svg( 'refresh', 13 ); // phpcs:ignore ?>
									<span><?php esc_html_e( 'Quét Lại Sức Khỏe Toàn Site', 'sergio-ai-studio' ); ?></span>
								</button>
							</div>
						</div>

						<!-- Site SEO Health Metric Strip -->
						<div class="studio-deck-strip" id="seo_health_strip">
							<div class="deck-cell">
								<span class="deck-micro-label"><?php esc_html_e( 'Điểm SEO Trung Bình', 'sergio-ai-studio' ); ?></span>
								<div class="deck-value-row">
									<span class="deck-numeric" id="seo_stat_avg_score">--</span>
									<span class="deck-unit">/ 100</span>
								</div>
								<span class="deck-meta-foot" id="seo_stat_avg_label"><?php esc_html_e( 'Sức khỏe tổng thể', 'sergio-ai-studio' ); ?></span>
							</div>

							<div class="deck-cell">
								<span class="deck-micro-label"><?php esc_html_e( 'Đạt Chuẩn Tốt (>80đ)', 'sergio-ai-studio' ); ?></span>
								<div class="deck-value-row">
									<span class="deck-numeric" style="color: #059669;" id="seo_stat_good_count">0</span>
									<span class="deck-unit"><?php esc_html_e( 'trang', 'sergio-ai-studio' ); ?></span>
								</div>
								<span class="deck-meta-foot"><?php esc_html_e( 'Chuẩn Rank Math xanh', 'sergio-ai-studio' ); ?></span>
							</div>

							<div class="deck-cell">
								<span class="deck-micro-label"><?php esc_html_e( 'Cần Tối Ưu (50-79đ)', 'sergio-ai-studio' ); ?></span>
								<div class="deck-value-row">
									<span class="deck-numeric" style="color: #d97706;" id="seo_stat_warning_count">0</span>
									<span class="deck-unit"><?php esc_html_e( 'trang', 'sergio-ai-studio' ); ?></span>
								</div>
								<span class="deck-meta-foot"><?php esc_html_e( 'Cần tinh chỉnh On-page', 'sergio-ai-studio' ); ?></span>
							</div>

							<div class="deck-cell">
								<span class="deck-micro-label"><?php esc_html_e( 'Thiếu Focus Keyword', 'sergio-ai-studio' ); ?></span>
								<div class="deck-value-row">
									<span class="deck-numeric" style="color: #e11d48;" id="seo_stat_missing_kw">0</span>
									<span class="deck-unit"><?php esc_html_e( 'trang', 'sergio-ai-studio' ); ?></span>
								</div>
								<span class="deck-meta-foot"><?php esc_html_e( 'Chưa xác định từ khóa', 'sergio-ai-studio' ); ?></span>
							</div>
						</div>

						<!-- Diagnostic Action Plan & Batch Optimizer -->
						<div id="seo_site_action_plan_box" class="studio-surface-box" style="margin-top: 16px; display: none;">
							<div class="surface-header" style="flex-wrap: wrap; gap: 10px;">
								<h2 class="surface-title">
									<?php echo self::svg( 'bolt', 16 ); // phpcs:ignore ?>
									<span><?php esc_html_e( 'Chẩn Đoán & Kế Hoạch Tự Động Cải Thiện Toàn Site', 'sergio-ai-studio' ); ?></span>
								</h2>
								<button type="button" class="studio-btn studio-btn-primary btn-sm" id="btn_batch_fix_all_missing">
									<?php echo self::svg( 'sparkles', 13 ); // phpcs:ignore ?>
									<span><?php esc_html_e( '⚡ Tự Động Tối Ưu Toàn Bộ Trang Thiếu Bằng AI', 'sergio-ai-studio' ); ?></span>
								</button>
							</div>
							<div id="seo_action_plan_items" style="display: flex; flex-direction: column; gap: 10px; margin-top: 12px;"></div>
							<div id="seo_batch_progress_box" class="studio-stepper-box" style="display: none; margin-top: 12px;">
								<div class="stepper-status-header">
									<span class="stepper-spinner"></span>
									<span id="seo_batch_progress_text"><?php esc_html_e( 'Đang tự động tối ưu SEO bằng Gemini AI...', 'sergio-ai-studio' ); ?></span>
								</div>
							</div>
						</div>

						<!-- SEO Interactive Workbench Layout -->
						<div class="studio-seo-layout" style="display: grid; grid-template-columns: 360px 1fr; gap: 20px; margin-top: 20px;">
							
							<!-- Left Column: Posts & Pages List -->
							<section class="studio-surface-box" style="height: fit-content;">
								<div class="surface-header">
									<h2 class="surface-title"><?php esc_html_e( 'Danh Sách Nội Dung', 'sergio-ai-studio' ); ?></h2>
								</div>

								<!-- Type Filter Tabs: All, Posts, Pages -->
								<div class="filter-pills-group" style="margin-bottom: 12px;">
									<button type="button" class="filter-pill is-active seo-type-filter" data-type=""><?php esc_html_e( 'Tất Cả', 'sergio-ai-studio' ); ?></button>
									<button type="button" class="filter-pill seo-type-filter" data-type="post"><?php esc_html_e( 'Bài Viết', 'sergio-ai-studio' ); ?></button>
									<button type="button" class="filter-pill seo-type-filter" data-type="page"><?php esc_html_e( 'Trang Tĩnh', 'sergio-ai-studio' ); ?></button>
								</div>

								<div style="margin-bottom: 12px;">
									<input type="text" id="seo_search_posts" class="studio-input" placeholder="<?php esc_attr_e( 'Tìm kiếm bài viết, trang...', 'sergio-ai-studio' ); ?>" style="font-size: 13px;" />
								</div>

								<div id="seo_posts_list_scroll" class="seo-posts-scroll-bench" style="max-height: 600px; overflow-y: auto;">
									<?php if ( ! empty( $seo_posts ) ) : ?>
										<div class="seo-post-cards-wrap">
											<?php
											foreach ( $seo_posts as $sp ) :
												$sp_score = (int) get_post_meta( $sp->ID, 'rank_math_seo_score', true );
												$sp_badge = Sergio_AI_SEO_Audit::get_score_badge( $sp_score );
												$sp_kw    = get_post_meta( $sp->ID, 'rank_math_focus_keyword', true );
											?>
												<div class="seo-post-pick-card" data-post-id="<?php echo esc_attr( $sp->ID ); ?>" data-post-type="<?php echo esc_attr( $sp->post_type ); ?>" data-title="<?php echo esc_attr( mb_strtolower( $sp->post_title ) ); ?>">
													<div class="pick-card-head">
														<span class="pick-score-badge <?php echo esc_attr( $sp_badge['class'] ); ?>">
															<?php echo $sp_score > 0 ? (int) $sp_score : '--'; ?>
														</span>
														<div style="display: flex; gap: 4px; align-items: center;">
															<span class="pick-type-tag type-<?php echo esc_attr( $sp->post_type ); ?>"><?php echo esc_html( strtoupper( $sp->post_type ) ); ?></span>
															<span class="pick-post-status"><?php echo esc_html( strtoupper( $sp->post_status ) ); ?></span>
														</div>
													</div>
													<h4 class="pick-card-title"><?php echo esc_html( $sp->post_title ); ?></h4>
													<div class="pick-card-meta">
														<span><?php echo ! empty( $sp_kw ) ? esc_html( 'KW: ' . $sp_kw ) : '<em style="color:#94a3b8;">Chưa có từ khóa</em>'; ?></span>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<p style="font-size: 13px; color: #64748b;"><?php esc_html_e( 'Chưa có bài viết hoặc trang nào.', 'sergio-ai-studio' ); ?></p>
									<?php endif; ?>
								</div>
							</section>

							<!-- Right Column: Interactive On-Page Inspection & AI Optimizer -->
							<div id="seo_workbench_pane">
								<!-- Empty state when no post selected -->
								<div id="seo_pane_empty" class="studio-surface-box" style="text-align: center; padding: 48px 24px;">
									<div style="max-width: 420px; margin: 0 auto;">
										<div style="color: #6366f1; margin-bottom: 12px;"><?php echo self::svg( 'seo', 32 ); // phpcs:ignore ?></div>
										<h3 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 6px;"><?php esc_html_e( 'Chọn Một Bài Viết Hoặc Trang Để Bắt Đầu', 'sergio-ai-studio' ); ?></h3>
										<p style="font-size: 13px; color: #64748b; line-height: 1.5;"><?php esc_html_e( 'Nhấp vào một bài viết hoặc trang từ danh sách bên trái để đo lường chi tiết thang điểm 100 của Rank Math và kích hoạt Gemini AI tối ưu hóa tiêu đề, mô tả và nội dung.', 'sergio-ai-studio' ); ?></p>
									</div>
								</div>

								<!-- Active audit workbench (Hidden initially) -->
								<div id="seo_pane_active" style="display: none;" class="studio-form-stack">
									
									<!-- Card 1: Article Header & Live Score -->
									<section class="studio-surface-box">
										<div class="surface-header" style="align-items: flex-start;">
											<div>
												<div class="item-micro-meta" style="margin-bottom: 4px;">
													<span id="audit_post_type_label" class="pick-type-tag">POST</span> &bull; 
													<span id="audit_post_id_label">#00</span> &bull; 
													<span id="audit_post_status_label">DRAFT</span>
												</div>
												<h2 class="surface-title" id="audit_post_title_heading" style="font-size: 17px; line-height: 1.35;"></h2>
											</div>
											<div style="display: flex; gap: 8px;">
												<a href="#" target="_blank" id="audit_post_edit_btn" class="studio-btn studio-btn-secondary btn-sm"><?php esc_html_e( 'Sửa bài trong WP', 'sergio-ai-studio' ); ?></a>
												<a href="#" target="_blank" id="audit_post_view_btn" class="studio-btn studio-btn-ghost btn-sm"><?php esc_html_e( 'Xem trước', 'sergio-ai-studio' ); ?></a>
											</div>
										</div>

										<!-- Score Header Strip -->
										<div class="seo-score-hero-box" style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin-top: 14px; flex-wrap: wrap; gap: 12px;">
											<div style="display: flex; align-items: center; gap: 16px;">
												<div class="seo-score-meter-disc" id="audit_score_disc">
													<span class="score-num" id="audit_score_val">0</span>
													<span class="score-den">/100</span>
												</div>
												<div>
													<h3 style="margin: 0; font-size: 15px; font-weight: 600; color: #0f172a;" id="audit_score_status_text">Đang phân tích...</h3>
													<p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;" id="audit_score_subtext">Điểm chuẩn Rank Math SEO On-Page</p>
												</div>
											</div>
											<div style="display: flex; gap: 8px; flex-wrap: wrap;">
												<button type="button" class="studio-btn studio-btn-secondary" id="btn_trigger_ai_seo">
													<?php echo self::svg( 'sparkles', 14 ); // phpcs:ignore ?>
													<span><?php esc_html_e( '1. Gemini Gợi Ý SEO', 'sergio-ai-studio' ); ?></span>
												</button>
												<button type="button" class="studio-btn studio-btn-primary" id="btn_trigger_deep_content_seo">
													<?php echo self::svg( 'bolt', 14 ); // phpcs:ignore ?>
													<span><?php esc_html_e( '2. ⚡ AI Nâng Cấp Nội Dung (85+ Điểm)', 'sergio-ai-studio' ); ?></span>
												</button>
											</div>
										</div>

										<div id="ai_seo_loading_box" class="studio-stepper-box" style="display: none; margin-top: 14px;">
											<div class="stepper-status-header">
												<span class="stepper-spinner"></span>
												<span id="ai_seo_loading_msg"><?php esc_html_e( 'Gemini đang phân tích nội dung, ý định tìm kiếm và kiến tạo bộ tiêu đề & mô tả tối ưu...', 'sergio-ai-studio' ); ?></span>
											</div>
										</div>

										<!-- AI Optimization Results Deck -->
										<div id="ai_seo_results_deck" style="display: none; margin-top: 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px;">
											<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
												<div style="display: flex; align-items: center; gap: 8px;">
													<?php echo self::svg( 'sparkles', 16 ); // phpcs:ignore ?>
													<h3 style="margin: 0; font-size: 14px; font-weight: 600; color: #1e40af;"><?php esc_html_e( 'Chiến Lược Tối Ưu Chuẩn Claude-SEO & Rank Math', 'sergio-ai-studio' ); ?></h3>
												</div>
												<span style="font-size: 12px; color: #059669; font-weight: 500;">✓ Đã tự động điền các lựa chọn tốt nhất vào khung biên tập bên dưới</span>
											</div>

											<!-- Claude-SEO Search Intent -->
											<div id="ai_rec_intent_box" style="margin-bottom: 12px; display: none;">
												<span class="studio-badge" id="ai_rec_intent_tag" style="background: #dbeafe; color: #1e40af; font-size: 12px; padding: 4px 10px; border-radius: 6px; font-weight: 500;"></span>
											</div>

											<!-- Recommended Focus Keyword -->
											<div style="margin-bottom: 12px;">
												<label class="studio-label" style="font-size: 12px; color: #1e3a8a;"><?php esc_html_e( 'Từ Khóa Chính Khuyên Dùng:', 'sergio-ai-studio' ); ?></label>
												<div style="display: flex; gap: 8px; align-items: center;">
													<input type="text" id="ai_rec_focus_kw" class="studio-input" style="font-weight: 600; background: #fff;" readonly />
													<button type="button" class="studio-btn studio-btn-secondary btn-sm" id="btn_apply_ai_kw"><?php esc_html_e( 'Chọn Từ Khóa Này', 'sergio-ai-studio' ); ?></button>
												</div>
											</div>

											<!-- Secondary / LSI Keywords -->
											<div style="margin-bottom: 12px;">
												<label class="studio-label" style="font-size: 12px; color: #1e3a8a;"><?php esc_html_e( 'Từ Khóa Phụ / LSI Keywords:', 'sergio-ai-studio' ); ?></label>
												<div id="ai_rec_secondary_kws" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>
											</div>

											<!-- Suggested Titles -->
											<div style="margin-bottom: 12px;">
												<label class="studio-label" style="font-size: 12px; color: #1e3a8a;"><?php esc_html_e( '3 Phương Án Tiêu Đề SEO (CTR Cao):', 'sergio-ai-studio' ); ?></label>
												<div id="ai_rec_titles_list" style="display: flex; flex-direction: column; gap: 8px;"></div>
											</div>

											<!-- Suggested Descriptions -->
											<div style="margin-bottom: 12px;">
												<label class="studio-label" style="font-size: 12px; color: #1e3a8a;"><?php esc_html_e( '2 Phương Án Thẻ Mô Tả Meta:', 'sergio-ai-studio' ); ?></label>
												<div id="ai_rec_descs_list" style="display: flex; flex-direction: column; gap: 8px;"></div>
											</div>

											<!-- Content Enhancements -->
											<div>
												<label class="studio-label" style="font-size: 12px; color: #1e3a8a;"><?php esc_html_e( 'Đề Xuất Mở Rộng Nội Dung (E-E-A-T):', 'sergio-ai-studio' ); ?></label>
												<ul id="ai_rec_enhancements_list" style="margin: 0; padding-left: 18px; font-size: 13px; color: #1e40af; line-height: 1.5;"></ul>
											</div>
										</div>
									</section>

									<!-- Card 2: SERP Google Preview & Rank Math Editor -->
									<section class="studio-surface-box">
										<div class="surface-header">
											<h2 class="surface-title"><?php esc_html_e( 'Xem Trước Trên Google (SERP Preview) & Biên Tập', 'sergio-ai-studio' ); ?></h2>
										</div>

										<!-- SERP Box Simulation -->
										<div class="seo-google-serp-preview" style="background: #ffffff; border: 1px solid #dadce0; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px;">
											<div class="serp-url" id="serp_preview_url" style="font-size: 12px; color: #202124; margin-bottom: 3px; display: flex; align-items: center; gap: 6px;">
												<span style="display: inline-block; width: 14px; height: 14px; background: #e2e8f0; border-radius: 50%;"></span>
												<span class="serp-url-text">https://example.com/...</span>
											</div>
											<h3 class="serp-title" id="serp_preview_title" style="font-size: 17px; line-height: 1.3; color: #1a0dab; margin: 0 0 4px 0; font-weight: 400; cursor: pointer;"></h3>
											<p class="serp-desc" id="serp_preview_desc" style="font-size: 13px; line-height: 1.45; color: #4d5156; margin: 0;"></p>
										</div>

										<!-- Editable Fields -->
										<div class="studio-form-stack">
											<div class="studio-field-row">
												<label class="studio-label" for="edit_seo_focus_kw"><?php esc_html_e( 'Focus Keyword (Từ khóa chính Rank Math)', 'sergio-ai-studio' ); ?></label>
												<input type="text" id="edit_seo_focus_kw" class="studio-input" placeholder="<?php esc_attr_e( 'Nhập từ khóa mục tiêu...', 'sergio-ai-studio' ); ?>" />
											</div>

											<div class="studio-field-row">
												<div style="display: flex; justify-content: space-between; align-items: baseline;">
													<label class="studio-label" for="edit_seo_title"><?php esc_html_e( 'Tiêu Đề SEO (SEO Title)', 'sergio-ai-studio' ); ?></label>
													<span class="studio-char-count" id="edit_title_char_counter" style="font-variant-numeric: tabular-nums; font-size: 12px; color: #64748b;">0 / 60</span>
												</div>
												<input type="text" id="edit_seo_title" class="studio-input" placeholder="<?php esc_attr_e( 'Tiêu đề hiển thị trên Google...', 'sergio-ai-studio' ); ?>" />
											</div>

											<div class="studio-field-row">
												<div style="display: flex; justify-content: space-between; align-items: baseline;">
													<label class="studio-label" for="edit_seo_description"><?php esc_html_e( 'Thẻ Mô Tả (Meta Description)', 'sergio-ai-studio' ); ?></label>
													<span class="studio-char-count" id="edit_desc_char_counter" style="font-variant-numeric: tabular-nums; font-size: 12px; color: #64748b;">0 / 160</span>
												</div>
												<textarea id="edit_seo_description" rows="3" class="studio-textarea" placeholder="<?php esc_attr_e( 'Mô tả ngắn gọn hấp dẫn...', 'sergio-ai-studio' ); ?>"></textarea>
											</div>

											<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
												<button type="button" class="studio-btn studio-btn-primary studio-btn-lg" id="btn_save_seo_to_rankmath">
													<?php echo self::svg( 'check', 14 ); // phpcs:ignore ?>
													<span><?php esc_html_e( 'Áp Dụng Vào Rank Math & Lưu Bài Viết', 'sergio-ai-studio' ); ?></span>
												</button>
												<span id="save_seo_feedback" class="studio-feedback-inline"></span>
											</div>
										</div>
									</section>

									<!-- Card 3: Detailed On-Page Checklist -->
									<section class="studio-surface-box">
										<div class="surface-header">
											<h2 class="surface-title"><?php esc_html_e( 'Bảng Kiểm Định Chi Tiết On-Page (Rank Math Standards)', 'sergio-ai-studio' ); ?></h2>
										</div>

										<div id="audit_checklist_wrap" class="seo-checklist-list">
											<!-- Items populated via JS -->
										</div>
									</section>

								</div>
							</div>

						</div>
					</div>
					<div id="tab-logs" class="studio-tab-panel <?php echo 'logs' === $active_tab ? 'is-active' : ''; ?>">
						<div class="panel-header-block">
							<h1 class="panel-headline"><?php esc_html_e( 'Nhật Ký Thực Thi (Audit Logs)', 'sergio-ai-studio' ); ?></h1>
							<p class="panel-subtext"><?php esc_html_e( 'Giám sát chi tiết từng mili-giây của pipeline. Phát hiện nhanh các mã lỗi HTTP, quota và phản hồi từ Gemini.', 'sergio-ai-studio' ); ?></p>
						</div>

						<section class="studio-surface-box">
							<div class="logs-filter-toolbar">
								<div class="filter-pills-group">
									<button type="button" class="filter-pill is-active" data-level=""><?php esc_html_e( 'Tất Cả', 'sergio-ai-studio' ); ?></button>
									<button type="button" class="filter-pill" data-level="info">INFO</button>
									<button type="button" class="filter-pill" data-level="warning">WARN</button>
									<button type="button" class="filter-pill" data-level="error">ERROR</button>
								</div>
								<button type="button" class="studio-btn studio-btn-danger btn-sm" id="btn_clear_logs">
									<?php echo self::svg( 'trash', 13 ); // phpcs:ignore ?>
									<span><?php esc_html_e( 'Dọn dẹp log > 30 ngày', 'sergio-ai-studio' ); ?></span>
								</button>
							</div>

							<?php if ( empty( $logs ) ) : ?>
								<div class="studio-empty-state-compact">
									<p><?php esc_html_e( 'Chưa có nhật ký nào được ghi nhận.', 'sergio-ai-studio' ); ?></p>
								</div>
							<?php else : ?>
								<table class="studio-data-table" id="logs_table">
									<thead>
										<tr>
											<th style="width: 150px;"><?php esc_html_e( 'Thời Gian', 'sergio-ai-studio' ); ?></th>
											<th style="width: 80px;"><?php esc_html_e( 'Cấp Độ', 'sergio-ai-studio' ); ?></th>
											<th style="width: 140px;"><?php esc_html_e( 'Giai Đoạn', 'sergio-ai-studio' ); ?></th>
											<th><?php esc_html_e( 'Thông Điệp & Dữ Liệu Chi Tiết', 'sergio-ai-studio' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $logs as $log ) : ?>
											<tr class="log-row-item" data-level="<?php echo esc_attr( $log['level'] ); ?>">
												<td class="cell-time"><?php echo esc_html( $log['created_at'] ); ?></td>
												<td><span class="log-level-pill level-<?php echo esc_attr( $log['level'] ); ?>"><?php echo esc_html( strtoupper( $log['level'] ) ); ?></span></td>
												<td class="cell-stage"><code><?php echo esc_html( $log['stage'] ); ?></code></td>
												<td class="cell-message">
													<div class="log-message-text"><?php echo esc_html( $log['message'] ); ?></div>
													<?php if ( ! empty( $log['details_json'] ) ) : ?>
														<details class="studio-details-code">
															<summary><?php esc_html_e( 'Chi tiết JSON', 'sergio-ai-studio' ); ?></summary>
															<pre><?php echo esc_html( $log['details_json'] ); ?></pre>
														</details>
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
							<span id="clear_logs_feedback" class="studio-feedback-inline"></span>
						</section>
					</div>

				</main><!-- .studio-workspace -->
			</div><!-- .studio-shell -->
		</div><!-- .sergio-studio-app -->
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

		$user_id      = get_current_user_id();
		$cooldown_key = 'sergio_ai_cooldown_' . $user_id;
		if ( get_transient( $cooldown_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng đợi 15 giây trước khi thực thi bài tiếp theo.', 'sergio-ai-studio' ) ) );
		}
		set_transient( $cooldown_key, 1, 15 );

		$seed_query = isset( $_POST['seed_query'] ) ? sanitize_text_field( wp_unslash( $_POST['seed_query'] ) ) : '';
		$category   = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$language   = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'vi';

		if ( empty( $seed_query ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng nhập chủ đề cần nghiên cứu.', 'sergio-ai-studio' ) ) );
		}

		$logger = new Sergio_AI_Logger();
		$client = new Sergio_AI_Gemini_Client( '', '', $logger );

		$fact_engine = new Sergio_AI_Fact_Pack_Engine( $client, $logger );
		$fact_pack   = $fact_engine->build_fact_pack( $seed_query, $language );

		if ( is_wp_error( $fact_pack ) ) {
			wp_send_json_error( array( 'step' => 'fact_pack', 'message' => $fact_pack->get_error_message() ) );
		}

		$topic_analyzer = new Sergio_AI_Topic_Analyzer( $client, $logger );
		$editorial_plan = $topic_analyzer->generate_editorial_plan( $seed_query, $fact_pack, $language );

		if ( is_wp_error( $editorial_plan ) ) {
			wp_send_json_error( array( 'step' => 'editorial_plan', 'message' => $editorial_plan->get_error_message() ) );
		}

		$writer  = new Sergio_AI_Article_Writer( $client, $logger );
		$article = $writer->write_article( $fact_pack, $editorial_plan );

		if ( is_wp_error( $article ) ) {
			wp_send_json_error( array( 'step' => 'writing', 'message' => $article->get_error_message() ) );
		}

		$post_id = $writer->save_post( $article, array(
			'status'      => 'draft',
			'category_id' => $category,
		), $fact_pack['id'] );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'step' => 'saving', 'message' => $post_id->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'       => __( 'Tạo bài viết dựa trên dữ kiện thật hoàn tất (Bản nháp)!', 'sergio-ai-studio' ),
			'post_id'       => $post_id,
			'title'         => $editorial_plan['headline'],
			'focus_keyword' => $editorial_plan['primary_focus_keyword'],
			'sources_count' => count( $fact_pack['source_citations'] ),
			'sources'       => $fact_pack['source_citations'],
			'entities'      => $fact_pack['entities'],
			'word_count'    => str_word_count( strip_tags( $article['content'] ) ),
			'edit_url'      => get_edit_post_link( $post_id, 'raw' ),
			'view_url'      => get_permalink( $post_id ),
			'status'        => 'draft',
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
