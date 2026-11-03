<?php
/**
 * Sergio AI Studio - Tab: Tổng Quan (Overview)
 *
 * @package Sergio_AI_Studio
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="tab-overview" class="sergio-tab-pane <?php echo 'overview' === $active_tab ? 'is-active' : ''; ?>">
	<div class="sergio-overview-cards">
		<div class="card">
			<div class="card-kicker">
				<span><?php esc_html_e( 'Kết Nối Gemini API', 'sergio-ai-studio' ); ?></span>
			</div>
			<div class="card-val <?php echo $has_key ? 'val-green' : 'val-amber'; ?>">
				<?php echo $has_key ? esc_html__( 'Sẵn Sàng', 'sergio-ai-studio' ) : esc_html__( 'Chưa Cấu Hình', 'sergio-ai-studio' ); ?>
			</div>
			<p class="card-desc"><code><?php echo esc_html( $primary_model ); ?></code></p>
		</div>

		<div class="card">
			<div class="card-kicker">
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
				<span><?php esc_html_e( 'Hàng Đợi Chờ Xử Lý', 'sergio-ai-studio' ); ?></span>
			</div>
			<div class="card-val val-blue">
				<?php echo (int) $queue_count; ?> <span class="card-val-unit"><?php esc_html_e( 'chủ đề', 'sergio-ai-studio' ); ?></span>
			</div>
			<p class="card-desc"><?php printf( esc_html__( 'Mỗi đợt sinh %d bài', 'sergio-ai-studio' ), (int) $posts_per_run ); ?></p>
		</div>

		<div class="card">
			<div class="card-kicker">
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
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=sergio-ai-studio&tab=writer' ) ); ?>" class="button button-primary nav-tab-link" data-tab="tab-writer">
						<?php esc_html_e( 'Nghiên Cứu & Viết Bài', 'sergio-ai-studio' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=sergio-ai-studio&tab=scheduler' ) ); ?>" class="button button-secondary nav-tab-link" data-tab="tab-scheduler">
						<?php esc_html_e( 'Cấu Hình Lên Lịch', 'sergio-ai-studio' ); ?>
					</a>
					<button type="button" class="button button-secondary" id="btn_run_cron_overview">
						<?php esc_html_e( 'Kích Hoạt 1 Đợt Cron Ngay', 'sergio-ai-studio' ); ?>
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
					<p style="color: #64748b; margin: 0 0 12px;"><?php esc_html_e( 'Hàng đợi hiện đang trống. Hệ thống sẽ tự động nghiên cứu theo Lĩnh vực ngách (chế độ Hybrid).', 'sergio-ai-studio' ); ?></p>
				<?php else : ?>
					<div style="padding: 10px 12px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px; margin-bottom: 12px;">
						<div style="font-weight: 600; font-size: 13px; color: #1d2327; margin-bottom: 4px;">
							#<?php echo esc_html( $queue_items[0]['id'] ); ?>: <?php echo esc_html( $queue_items[0]['topic'] ); ?>
						</div>
						<div class="description" style="font-size: 12px; color: #64748b;">
							<?php printf( esc_html__( 'Nạp lúc: %s', 'sergio-ai-studio' ), esc_html( $queue_items[0]['created_at'] ) ); ?>
						</div>
					</div>
				<?php endif; ?>
				<p style="margin: 0;">
					<a href="#tab-scheduler" class="button button-secondary button-small nav-tab-link" data-tab="tab-scheduler">
						<?php esc_html_e( 'Quản lý hàng đợi &rarr;', 'sergio-ai-studio' ); ?>
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
				<p style="padding: 16px; color: #64748b; margin: 0;"><?php esc_html_e( 'Chưa có nhật ký nào được ghi nhận.', 'sergio-ai-studio' ); ?></p>
			<?php else : ?>
				<table class="widefat striped" style="border: none;">
					<thead>
						<tr>
							<th style="width: 160px;"><?php esc_html_e( 'Thời Gian', 'sergio-ai-studio' ); ?></th>
							<th style="width: 100px;"><?php esc_html_e( 'Cấp Độ', 'sergio-ai-studio' ); ?></th>
							<th style="width: 160px;"><?php esc_html_e( 'Giai Đoạn', 'sergio-ai-studio' ); ?></th>
							<th><?php esc_html_e( 'Thông Điệp', 'sergio-ai-studio' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_logs as $log ) : ?>
							<tr>
								<td style="vertical-align: middle;"><?php echo esc_html( $log['created_at'] ); ?></td>
								<td style="vertical-align: middle;">
									<?php if ( 'error' === $log['level'] ) : ?>
										<span class="sergio-log-badge badge-error"><?php esc_html_e( 'LỖI', 'sergio-ai-studio' ); ?></span>
									<?php elseif ( 'warning' === $log['level'] ) : ?>
										<span class="sergio-log-badge badge-warning"><?php esc_html_e( 'CẢNH BÁO', 'sergio-ai-studio' ); ?></span>
									<?php else : ?>
										<span class="sergio-log-badge badge-info"><?php esc_html_e( 'INFO', 'sergio-ai-studio' ); ?></span>
									<?php endif; ?>
								</td>
								<td style="vertical-align: middle;">
									<span style="display: inline-block; padding: 2px 6px; background: #f0f0f1; border-radius: 3px; font-size: 11px; color: #2c3338;">
										<?php echo esc_html( Sergio_AI_Logger::map_stage_label( $log['stage'] ) ); ?>
									</span>
								</td>
								<td style="vertical-align: middle;"><?php echo wp_kses_post( Sergio_AI_Logger::format_log_message_html( $log['message'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<div style="padding: 10px 16px; border-top: 1px solid #c3c4c7; background: #f6f7f7; display: flex; justify-content: flex-end; align-items: center;">
					<a href="#tab-logs" class="button button-secondary button-small nav-tab-link" data-tab="tab-logs">
						<?php esc_html_e( 'Xem toàn bộ nhật ký &rarr;', 'sergio-ai-studio' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

