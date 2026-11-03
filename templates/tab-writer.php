<?php
/**
 * Sergio AI Studio - Tab: Nghiên Cứu & Viết Bài (Writer)
 *
 * @package Sergio_AI_Studio
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="tab-writer" class="sergio-tab-pane <?php echo 'writer' === $active_tab ? 'is-active' : ''; ?>">
	<div class="postbox">
		<div class="postbox-header"><h2 class="hndle"><span><?php esc_html_e( 'Nghiên Cứu Dữ Kiện & Viết Bài', 'sergio-ai-studio' ); ?></span></h2></div>
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
							<label for="writer_reference_sources"><?php esc_html_e( 'Trang Báo Tham Khảo Ưu Tiên', 'sergio-ai-studio' ); ?></label>
						</th>
						<td>
							<input type="text" id="writer_reference_sources" class="large-text"
								value="<?php echo esc_attr( $trusted_sources ); ?>"
								placeholder="<?php esc_attr_e( 'Ví dụ: vnexpress.net, tuoitre.vn, baomoi.com (để trống nếu muốn AI tìm toàn mạng)', 'sergio-ai-studio' ); ?>" />
							<p class="description">
								<?php esc_html_e( 'AI sẽ ưu tiên bóc tách sự kiện, số liệu và trích dẫn URL bài viết từ các trang báo này vào Fact Pack.', 'sergio-ai-studio' ); ?>
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
								<option value="reference" selected><?php esc_html_e( 'Trích xuất từ nguồn tham khảo (Ghi rõ bản quyền)', 'sergio-ai-studio' ); ?></option>
								<option value="stock"><?php esc_html_e( 'Kho ảnh miễn phí bản quyền (Wikimedia / Unsplash / Pexels)', 'sergio-ai-studio' ); ?></option>
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
					<?php esc_html_e( 'Khởi Chạy Fact Pack & Viết Bài (1-Click)', 'sergio-ai-studio' ); ?>
				</button>
				<button type="button" class="button button-secondary button-large" id="btn_step1_fact_pack_only">
					<?php esc_html_e( 'Chỉ Lập Fact Pack (Kiểm tra dữ kiện trước)', 'sergio-ai-studio' ); ?>
				</button>
			</p>
		</div>
	</div>

	<!-- Linear Progress Stepper -->
	<div id="pipeline_progress_box" style="display: none; margin-top: 15px; padding: 12px 16px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px;">
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
				<span><?php esc_html_e( 'Gói Dữ Kiện Đã Xác Thực (Fact Pack Preview)', 'sergio-ai-studio' ); ?></span>
			</h2>
		</div>
		<div class="inside">
			<p class="description"><?php esc_html_e( 'Xem trước các dữ kiện thật đã được đối chiếu từ web trước khi chấp bút bài viết:', 'sergio-ai-studio' ); ?></p>
			<div id="fact_pack_content_area" style="line-height: 1.7; margin: 15px 0;"></div>
			<p style="margin: 0; padding-top: 10px; border-top: 1px solid #c3c4c7;">
				<button type="button" class="button button-primary" id="btn_write_from_fact_pack">
					<?php esc_html_e( 'Tiến Hành Chấp Bút Bài Viết Từ Fact Pack Này', 'sergio-ai-studio' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="btn_cancel_fact_pack">
					<?php esc_html_e( 'Đóng Xem Trước', 'sergio-ai-studio' ); ?>
				</button>
			</p>
		</div>
	</div>

	<!-- Result Card -->
	<div id="pipeline_result_card" style="display: none; margin-top: 20px; padding: 14px 18px; background: #edfaef; border: 1px solid #c3e6cb; border-radius: 4px; color: #1a6828;"></div>
</div>

