<?php
/**
 * Sergio AI Studio - Tab: Crawl & Viết Lại (Crawl)
 *
 * @package Sergio_AI_Studio
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="tab-crawl" class="sergio-tab-pane <?php echo 'crawl' === $active_tab ? 'is-active' : ''; ?>">
	<div class="postbox">
		<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Crawl & Viết Lại Nội Dung', 'sergio-ai-studio' ); ?></span></h2></div>
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
					<?php esc_html_e( 'Crawl & Viết Lại', 'sergio-ai-studio' ); ?>
				</button>
			</p>
		</div>
	</div>

	<div id="crawl_progress_box" style="display: none; margin-top: 15px; padding: 12px 16px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px;">
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

