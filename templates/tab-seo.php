<?php
/**
 * Sergio AI Studio - Tab: Audit & Tối Ưu SEO (SEO)
 *
 * @package Sergio_AI_Studio
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="tab-seo" class="sergio-tab-pane <?php echo 'seo' === $active_tab ? 'is-active' : ''; ?>">
	<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
		<div>
			<h2 style="margin: 0; font-size: 18px; font-weight: 600;"><?php esc_html_e( 'Audit & Tối Ưu SEO Toàn Diện', 'sergio-ai-studio' ); ?></h2>
			<p class="description" style="margin: 3px 0 0;"><?php esc_html_e( 'Quét sức khỏe SEO on-page toàn site, đo lường theo tiêu chuẩn Rank Math và kích hoạt Gemini AI tối ưu tiêu đề, mô tả, từ khóa LSI.', 'sergio-ai-studio' ); ?></p>
		</div>
		<button type="button" class="button" id="btn_run_site_seo_audit">
			<?php esc_html_e( 'Quét Lại Sức Khỏe Toàn Site', 'sergio-ai-studio' ); ?>
		</button>
	</div>

	<!-- Metrics Deck -->
	<div class="sergio-overview-cards" id="seo_health_strip">
		<div class="card">
			<div class="card-kicker"><?php esc_html_e( 'Điểm SEO Trung Bình', 'sergio-ai-studio' ); ?></div>
			<div class="card-val val-blue">
				<span id="seo_stat_avg_score">--</span> <span class="card-val-unit">/ 100</span>
			</div>
			<p class="card-desc" id="seo_stat_avg_label"><?php esc_html_e( 'Sức khỏe tổng thể', 'sergio-ai-studio' ); ?></p>
		</div>

		<div class="card">
			<div class="card-kicker"><?php esc_html_e( 'Đạt Chuẩn Tốt (>80đ)', 'sergio-ai-studio' ); ?></div>
			<div class="card-val val-green">
				<span id="seo_stat_good_count">0</span> <span class="card-val-unit"><?php esc_html_e( 'trang', 'sergio-ai-studio' ); ?></span>
			</div>
			<p class="card-desc"><?php esc_html_e( 'Chuẩn Rank Math xanh', 'sergio-ai-studio' ); ?></p>
		</div>

		<div class="card">
			<div class="card-kicker"><?php esc_html_e( 'Cần Tối Ưu (50-79đ)', 'sergio-ai-studio' ); ?></div>
			<div class="card-val val-amber">
				<span id="seo_stat_warning_count">0</span> <span class="card-val-unit"><?php esc_html_e( 'trang', 'sergio-ai-studio' ); ?></span>
			</div>
			<p class="card-desc"><?php esc_html_e( 'Cần tinh chỉnh On-page', 'sergio-ai-studio' ); ?></p>
		</div>

		<div class="card">
			<div class="card-kicker"><?php esc_html_e( 'Thiếu Focus Keyword', 'sergio-ai-studio' ); ?></div>
			<div class="card-val val-red">
				<span id="seo_stat_missing_kw">0</span> <span class="card-val-unit"><?php esc_html_e( 'trang', 'sergio-ai-studio' ); ?></span>
			</div>
			<p class="card-desc"><?php esc_html_e( 'Chưa xác định từ khóa', 'sergio-ai-studio' ); ?></p>
		</div>
	</div>

	<!-- Batch Optimizer Banner -->
	<div id="seo_site_action_plan_box" style="margin-bottom: 20px; display: none; padding: 12px 16px; background: #fff8e5; border: 1px solid #ffeeba; border-radius: 4px;">
		<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
			<div>
				<strong><?php esc_html_e( 'Chẩn Đoán & Kế Hoạch Cải Thiện Toàn Site', 'sergio-ai-studio' ); ?></strong>
				<div id="seo_action_plan_items" style="margin-top: 6px;"></div>
			</div>
			<button type="button" class="button button-primary" id="btn_batch_fix_all_missing">
				<?php esc_html_e( 'Tự Động Tối Ưu Bằng AI', 'sergio-ai-studio' ); ?>
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
			<div id="seo_pane_empty" class="postbox" style="text-align: center; padding: 48px 24px; margin: 0;">
				<h3 style="font-size: 16px; margin: 0 0 8px; font-weight: 600;"><?php esc_html_e( 'Chọn Một Bài Viết Hoặc Trang Để Bắt Đầu', 'sergio-ai-studio' ); ?></h3>
				<p class="description" style="max-width: 480px; margin: 0 auto; line-height: 1.5;"><?php esc_html_e( 'Nhấp vào một bài viết hoặc trang từ danh sách bên trái để đo lường chi tiết thang điểm 100 của Rank Math và kích hoạt Gemini AI tối ưu hóa tiêu đề, mô tả và nội dung.', 'sergio-ai-studio' ); ?></p>
			</div>

			<!-- Active audit workbench -->
			<div id="seo_pane_active" style="display: none;">
				<div class="postbox">
					<div class="postbox-header" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; min-height: 42px;">
						<div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; padding-right: 16px;">
							<span id="audit_post_type_label" class="sergio-log-badge badge-info" style="font-size: 11px; font-weight: 600; flex-shrink: 0;">POST</span>
							<span id="audit_post_id_label" style="color: #64748b; font-size: 13px; font-weight: 600; flex-shrink: 0;">#00</span>
							<span style="color: #c3c4c7; flex-shrink: 0;">—</span>
							<strong id="audit_post_title_heading" style="font-size: 14px; color: #1d2327; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></strong>
						</div>
						<div style="display: flex; gap: 6px; flex-shrink: 0; align-items: center;">
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
								<button type="button" class="button" id="btn_trigger_ai_seo">
									<?php esc_html_e( '1. Gợi Ý Từ Khóa & Metadata', 'sergio-ai-studio' ); ?>
								</button>
								<button type="button" class="button button-primary" id="btn_trigger_deep_content_seo">
									<?php esc_html_e( '2. Nâng Cấp Nội Dung Bằng AI', 'sergio-ai-studio' ); ?>
								</button>
							</div>
						</div>

						<div id="ai_seo_loading_box" style="display: none; margin-bottom: 15px; padding: 10px 14px; background: #f0f6fc; border: 1px solid #c5d9ed; border-radius: 4px; font-size: 13px;">
							<p>
								<span class="spinner is-active" style="float:none; margin:0 6px 0 0;"></span>
								<span id="ai_seo_loading_msg"><?php esc_html_e( 'Gemini đang phân tích nội dung, ý định tìm kiếm và kiến tạo bộ tiêu đề & mô tả tối ưu...', 'sergio-ai-studio' ); ?></span>
							</p>
						</div>

						<!-- AI Optimization Results Deck -->
						<div id="ai_seo_results_deck" style="display: none; margin: 0 0 16px; padding: 14px 16px; background: #f0f6fc; border: 1px solid #c5d9ed; border-radius: 4px;">
							<h3 style="margin: 0 0 10px; font-size: 14px; font-weight: 600; color: #1d2327;">
								<?php esc_html_e( 'Chiến Lược Tối Ưu Từ Gemini AI & Rank Math', 'sergio-ai-studio' ); ?>
							</h3>

							<div id="ai_rec_intent_box" style="margin-bottom: 10px; display: none;">
								<span id="ai_rec_intent_tag" class="sergio-log-badge badge-info" style="font-size: 12px; font-weight: 500;"></span>
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
						<div style="margin: 0 0 16px; padding: 14px 16px; background: #ffffff; border: 1px solid #c3c4c7; border-radius: 4px;">
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
									<?php esc_html_e( 'Áp Dụng Vào Rank Math & Lưu Bài Viết', 'sergio-ai-studio' ); ?>
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
