<?php
/**
 * Sergio AI Studio - Tab: Cài Đặt (Settings)
 *
 * @package Sergio_AI_Studio
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="tab-settings" class="sergio-tab-pane <?php echo 'settings' === $active_tab ? 'is-active' : ''; ?>">
	<form method="post" action="options.php">
		<?php settings_fields( 'sergio_ai_studio_settings_group' ); ?>
		<input type="hidden" name="sergio_form_source" value="settings" />

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
									<?php esc_html_e( 'Kiểm tra kết nối', 'sergio-ai-studio' ); ?>
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
									<?php esc_html_e( 'Làm mới danh sách', 'sergio-ai-studio' ); ?>
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
			<div class="postbox-header">
				<h2 class="hndle"><span><?php esc_html_e( 'Kho Ảnh Bản Quyền Mở (Stock Photo APIs - Tùy Chọn)', 'sergio-ai-studio' ); ?></span></h2>
			</div>
			<div class="inside">
				<p class="description" style="margin-bottom: 12px;">
					<?php esc_html_e( 'Hệ thống đã tích hợp sẵn Wikimedia Commons API (100% Miễn phí, Giấy phép bản quyền công khai CC-BY / Public Domain, không yêu cầu API Key). Bạn có thể cấu hình thêm Unsplash hoặc Pexels nếu muốn AI tự động tìm ảnh chụp nghệ thuật phân giải cao.', 'sergio-ai-studio' ); ?>
				</p>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="unsplash_access_key"><?php esc_html_e( 'Unsplash Access Key', 'sergio-ai-studio' ); ?></label>
							</th>
							<td>
								<input type="password" id="unsplash_access_key" name="sergio_ai_studio_options[unsplash_access_key]"
									value="<?php echo esc_attr( $masked_unsplash_key ); ?>"
									class="regular-text" placeholder="Nhập Unsplash Access Key..." autocomplete="off" />
								<p class="description">
									<?php esc_html_e( 'Đăng ký ứng dụng miễn phí tại unsplash.com/developers để lấy Access Key (50 requests/giờ).', 'sergio-ai-studio' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="pexels_api_key"><?php esc_html_e( 'Pexels API Key', 'sergio-ai-studio' ); ?></label>
							</th>
							<td>
								<input type="password" id="pexels_api_key" name="sergio_ai_studio_options[pexels_api_key]"
									value="<?php echo esc_attr( $masked_pexels_key ); ?>"
									class="regular-text" placeholder="Nhập Pexels API Key..." autocomplete="off" />
								<p class="description">
									<?php esc_html_e( 'Đăng ký miễn phí tại pexels.com/api để lấy API Key (200 requests/giờ).', 'sergio-ai-studio' ); ?>
								</p>
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
								<label><?php esc_html_e( 'Trạng Thái Xuất Bản Mặc Định (Thủ Công)', 'sergio-ai-studio' ); ?></label>
							</th>
							<td>
								<select name="sergio_ai_studio_options[default_post_status]">
									<option value="draft" <?php selected( $default_post_status, 'draft' ); ?>><?php esc_html_e( 'Bản nháp (Draft — Khuyên dùng)', 'sergio-ai-studio' ); ?></option>
									<option value="publish" <?php selected( $default_post_status, 'publish' ); ?>><?php esc_html_e( 'Đăng ngay lập tức (Publish)', 'sergio-ai-studio' ); ?></option>
								</select>
								<p class="description" style="color: #64748b;">
									<?php esc_html_e( 'Áp dụng cho các bài viết biên soạn thủ công trong tab Nghiên Cứu & Viết Bài hoặc Crawl.', 'sergio-ai-studio' ); ?>
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
