<?php
/**
 * Sergio AI Studio - Tab: Lên Lịch (Scheduler)
 *
 * @package Sergio_AI_Studio
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="tab-scheduler" class="sergio-tab-pane <?php echo 'scheduler' === $active_tab ? 'is-active' : ''; ?>">
	<form method="post" action="options.php">
		<?php settings_fields( 'sergio_ai_studio_settings_group' ); ?>
		<input type="hidden" name="sergio_form_source" value="scheduler" />

		<div class="sergio-grid-2col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Chu Kỳ & Số Lượng Bài Viết', 'sergio-ai-studio' ); ?></span></h2></div>
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

					<div style="margin-top: 15px; padding: 10px 14px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px; font-size: 13px; line-height: 1.5;">
						<strong><?php esc_html_e( 'Trạng thái WP-Cron:', 'sergio-ai-studio' ); ?></strong>
						<?php if ( $scheduler_enabled && $next_run_time ) : ?>
							<span style="color: #007017; font-weight: 600;"><?php printf( esc_html__( 'Đang chạy — Lần tới: %s', 'sergio-ai-studio' ), esc_html( $next_run_time ) ); ?></span>
						<?php elseif ( $scheduler_enabled ) : ?>
							<span style="color: #007017; font-weight: 600;"><?php esc_html_e( 'Đang kích hoạt', 'sergio-ai-studio' ); ?></span>
						<?php else : ?>
							<span style="color: #64748b;"><?php esc_html_e( 'Lịch trình hiện đang tắt', 'sergio-ai-studio' ); ?></span>
						<?php endif; ?>
						<span style="color: #8c8f94; margin: 0 6px;">&bull;</span>
						<span><?php printf( esc_html__( 'Hôm nay: %d bài đã tạo.', 'sergio-ai-studio' ), (int) $posts_today ); ?></span>
					</div>
				</div>
			</div>

			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Chiến Lược Chủ Đề & Định Dạng', 'sergio-ai-studio' ); ?></span></h2></div>
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
											<?php esc_html_e( 'Kết Hợp Thông Minh (Ưu tiên hàng đợi, tự tìm khi hết)', 'sergio-ai-studio' ); ?>
										</option>
										<option value="queue_only" <?php selected( $topic_mode, 'queue_only' ); ?>>
											<?php esc_html_e( 'Chỉ Lấy Từ Hàng Đợi (Chỉ viết chủ đề nạp sẵn)', 'sergio-ai-studio' ); ?>
										</option>
										<option value="ai_discover" <?php selected( $topic_mode, 'ai_discover' ); ?>>
											<?php esc_html_e( 'AI Tự Khám Phá (Tự nghiên cứu theo lĩnh vực)', 'sergio-ai-studio' ); ?>
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
									<select name="sergio_ai_studio_options[default_schedule_status]" id="default_schedule_status">
										<option value="draft" <?php selected( $default_sched_stat, 'draft' ); ?>><?php esc_html_e( 'Bản nháp (Draft — Khuyên dùng)', 'sergio-ai-studio' ); ?></option>
										<option value="publish" <?php selected( $default_sched_stat, 'publish' ); ?>><?php esc_html_e( 'Xuất bản ngay (Publish)', 'sergio-ai-studio' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="schedule_image_option"><?php esc_html_e( 'Ảnh Đại Diện Bài Viết', 'sergio-ai-studio' ); ?></label>
								</th>
								<td>
									<select name="sergio_ai_studio_options[schedule_image_option]" id="schedule_image_option">
										<option value="reference" <?php selected( $schedule_image_opt, 'reference' ); ?>>
											<?php esc_html_e( 'Trích xuất từ nguồn tham khảo (Ghi rõ bản quyền tác giả)', 'sergio-ai-studio' ); ?>
										</option>
										<option value="stock" <?php selected( $schedule_image_opt, 'stock' ); ?>>
											<?php esc_html_e( 'Kho ảnh miễn phí bản quyền (Wikimedia Commons / Unsplash / Pexels)', 'sergio-ai-studio' ); ?>
										</option>
										<option value="none" <?php selected( $schedule_image_opt, 'none' ); ?>>
											<?php esc_html_e( 'Không tạo ảnh đại diện (Tối ưu tốc độ tối đa)', 'sergio-ai-studio' ); ?>
										</option>
									</select>
									<p class="description">
										<?php esc_html_e( 'Phương thức tự động gắn ảnh đại diện (Featured Image) chuẩn Rank Math khi tiến trình lập lịch chạy.', 'sergio-ai-studio' ); ?>
									</p>
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
				<div style="margin: 12px 0 16px; padding: 10px 14px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px; font-size: 13px;">
					<p style="margin: 0 0 6px; font-weight: 600;">
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
						<span style="color: #8c8f94; margin: 0 6px;">&bull;</span>
						<?php
						if ( 'stock' === $schedule_image_opt ) {
							$img_opt_label = __( 'Kho ảnh bản quyền mở (Stock)', 'sergio-ai-studio' );
						} elseif ( 'none' === $schedule_image_opt ) {
							$img_opt_label = __( 'Không tạo ảnh', 'sergio-ai-studio' );
						} else {
							$img_opt_label = __( 'Ảnh từ nguồn tham khảo', 'sergio-ai-studio' );
						}
						printf( esc_html__( 'Ảnh: %s', 'sergio-ai-studio' ), esc_html( $img_opt_label ) );
						?>
					</p>
				</div>
				<p>
					<button type="button" class="button button-primary button-large" id="btn_run_cron_now">
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
						<th style="width: 120px;"><?php esc_html_e( 'Trạng Thái', 'sergio-ai-studio' ); ?></th>
						<th style="width: 80px; text-align: center;"><?php esc_html_e( 'Lần Thử', 'sergio-ai-studio' ); ?></th>
						<th style="width: 160px;"><?php esc_html_e( 'Thời Gian Tạo', 'sergio-ai-studio' ); ?></th>
						<th style="width: 130px; text-align: center;"><?php esc_html_e( 'Thao Tác', 'sergio-ai-studio' ); ?></th>
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
								<td style="vertical-align: middle;">#<?php echo esc_html( $q['id'] ); ?></td>
								<td style="vertical-align: middle;">
									<strong><?php echo esc_html( $q['topic'] ); ?></strong>
									<?php if ( ! empty( $q['last_error'] ) ) : ?>
										<div class="item-error-subtext" style="color: #b32d2e; font-size: 11px; margin-top: 3px;"><?php echo esc_html( $q['last_error'] ); ?></div>
									<?php endif; ?>
								</td>
								<td style="vertical-align: middle;">
									<?php if ( 'completed' === $q['status'] ) : ?>
										<span class="sergio-queue-badge sergio-log-badge badge-success"><?php esc_html_e( 'HOÀN TẤT', 'sergio-ai-studio' ); ?></span>
									<?php elseif ( 'failed' === $q['status'] ) : ?>
										<span class="sergio-queue-badge sergio-log-badge badge-error"><?php esc_html_e( 'THẤT BẠI', 'sergio-ai-studio' ); ?></span>
									<?php else : ?>
										<span class="sergio-queue-badge sergio-log-badge badge-info"><?php esc_html_e( 'CHỜ XỬ LÝ', 'sergio-ai-studio' ); ?></span>
									<?php endif; ?>
								</td>
								<td style="text-align: center; color: #64748b; vertical-align: middle;"><?php echo (int) ( $q['attempts'] ?? 0 ); ?>/3</td>
								<td style="vertical-align: middle;"><?php echo esc_html( $q['created_at'] ); ?></td>
								<td style="text-align: center; white-space: nowrap; vertical-align: middle;">
									<?php if ( 'failed' === $q['status'] ) : ?>
										<button type="button" class="button button-small sergio-btn-retry-queue" data-id="<?php echo esc_attr( $q['id'] ); ?>" title="<?php esc_attr_e( 'Thử lại chủ đề này', 'sergio-ai-studio' ); ?>">
											<?php esc_html_e( 'Thử lại', 'sergio-ai-studio' ); ?>
										</button>
									<?php elseif ( 'pending' === $q['status'] ) : ?>
										<button type="button" class="button button-small sergio-btn-priority-queue" data-id="<?php echo esc_attr( $q['id'] ); ?>" title="<?php esc_attr_e( 'Đẩy lên đầu hàng đợi ưu tiên', 'sergio-ai-studio' ); ?>">
											<?php esc_html_e( 'Ưu tiên', 'sergio-ai-studio' ); ?>
										</button>
									<?php endif; ?>
									<button type="button" class="button button-small button-link-delete sergio-btn-del-queue" data-id="<?php echo esc_attr( $q['id'] ); ?>" title="<?php esc_attr_e( 'Xóa mục này', 'sergio-ai-studio' ); ?>">
										<?php esc_html_e( 'Xóa', 'sergio-ai-studio' ); ?>
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
