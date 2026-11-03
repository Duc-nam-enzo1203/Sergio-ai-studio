<?php
/**
 * Sergio AI Studio - Tab: Nhật Ký (Logs)
 *
 * @package Sergio_AI_Studio
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="tab-logs" class="sergio-tab-pane <?php echo 'logs' === $active_tab ? 'is-active' : ''; ?>">

	<!-- Subsubsub Filter Links -->
	<ul class="subsubsub">
		<li class="all"><a href="#tab-logs" class="current log-subsubsub-link" data-level="" aria-current="page"><?php esc_html_e( 'Tất cả', 'sergio-ai-studio' ); ?> <span class="count">(<?php echo (int) $log_stats['total_logs']; ?>)</span></a> |</li>
		<li class="warning"><a href="#tab-logs" class="log-subsubsub-link" data-level="warning"><?php esc_html_e( 'Cảnh báo', 'sergio-ai-studio' ); ?> <span class="count">(<?php echo (int) $log_stats['warning_count']; ?>)</span></a> |</li>
		<li class="error"><a href="#tab-logs" class="log-subsubsub-link" data-level="error"><?php esc_html_e( 'Lỗi', 'sergio-ai-studio' ); ?> <span class="count">(<?php echo (int) $log_stats['error_count']; ?>)</span></a></li>
	</ul>

	<!-- Search Box -->
	<p class="search-box">
		<label class="screen-reader-text" for="log-search-input"><?php esc_html_e( 'Tìm kiếm nhật ký:', 'sergio-ai-studio' ); ?></label>
		<input type="search" id="log-search-input" name="s" value="" placeholder="<?php esc_attr_e( 'Tìm theo từ khóa, ID...', 'sergio-ai-studio' ); ?>">
		<input type="button" id="search-submit" class="button" value="<?php esc_attr_e( 'Tìm nhật ký', 'sergio-ai-studio' ); ?>">
	</p>

	<!-- Top Navigation Bar: Bulk Actions & Dropdown Filters (Native WP Style) -->
	<div class="tablenav top">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Chọn hành động hàng loạt', 'sergio-ai-studio' ); ?></label>
			<select name="action" id="bulk-action-selector-top">
				<option value="-1"><?php esc_html_e( 'Hành động', 'sergio-ai-studio' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Xóa', 'sergio-ai-studio' ); ?></option>
				<option value="clear_all"><?php esc_html_e( 'Xóa tất cả nhật ký', 'sergio-ai-studio' ); ?></option>
			</select>
			<input type="button" id="doaction" class="button action" value="<?php esc_attr_e( 'Áp dụng', 'sergio-ai-studio' ); ?>">
		</div>

		<div class="alignleft actions">
			<label for="filter-by-level" class="screen-reader-text"><?php esc_html_e( 'Lọc theo cấp độ', 'sergio-ai-studio' ); ?></label>
			<select name="level" id="filter-by-level">
				<option value=""><?php esc_html_e( 'Tất cả cấp độ', 'sergio-ai-studio' ); ?></option>
				<option value="warning"><?php esc_html_e( 'Cảnh báo (WARN)', 'sergio-ai-studio' ); ?></option>
				<option value="error"><?php esc_html_e( 'Lỗi (ERROR)', 'sergio-ai-studio' ); ?></option>
			</select>

			<label for="filter-by-stage" class="screen-reader-text"><?php esc_html_e( 'Lọc theo giai đoạn', 'sergio-ai-studio' ); ?></label>
			<select name="stage" id="filter-by-stage">
				<option value=""><?php esc_html_e( 'Tất cả giai đoạn', 'sergio-ai-studio' ); ?></option>
				<option value="generation"><?php esc_html_e( 'Viết bài & Nghiên cứu', 'sergio-ai-studio' ); ?></option>
				<option value="scheduler"><?php esc_html_e( 'Lập lịch tự động', 'sergio-ai-studio' ); ?></option>
				<option value="seo"><?php esc_html_e( 'Tối ưu SEO', 'sergio-ai-studio' ); ?></option>
				<option value="image"><?php esc_html_e( 'Xử lý ảnh', 'sergio-ai-studio' ); ?></option>
				<option value="rewrite"><?php esc_html_e( 'Viết lại bài', 'sergio-ai-studio' ); ?></option>
			</select>

			<input type="button" id="post-query-submit" class="button" value="<?php esc_attr_e( 'Lọc', 'sergio-ai-studio' ); ?>">
			<span id="log_refresh_spinner" class="spinner" style="float: none; margin: 0 4px; vertical-align: middle;"></span>
			<span id="clear_logs_feedback" style="margin-left: 6px; font-size: 13px;"></span>
		</div>

		<div class="tablenav-pages">
			<span class="displaying-num" id="log_displaying_num"><?php printf( esc_html__( '%d mục', 'sergio-ai-studio' ), count( $logs ) ); ?></span>
		</div>
		<br class="clear">
	</div>

	<!-- WP List Table -->
	<table class="wp-list-table widefat fixed striped posts" id="logs_table">
		<thead>
			<tr>
				<th scope="col" id="cb" class="manage-column column-cb check-column">
					<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Chọn tất cả', 'sergio-ai-studio' ); ?></label>
					<input id="cb-select-all-1" type="checkbox">
				</th>
				<th scope="col" id="date" class="manage-column column-date" style="width: 160px;"><?php esc_html_e( 'Thời Gian', 'sergio-ai-studio' ); ?></th>
				<th scope="col" id="level" class="manage-column column-level" style="width: 90px;"><?php esc_html_e( 'Cấp Độ', 'sergio-ai-studio' ); ?></th>
				<th scope="col" id="stage" class="manage-column column-stage" style="width: 180px;"><?php esc_html_e( 'Giai Đoạn', 'sergio-ai-studio' ); ?></th>
				<th scope="col" id="message" class="manage-column column-primary"><?php esc_html_e( 'Thông Điệp & Dữ Liệu', 'sergio-ai-studio' ); ?></th>
			</tr>
		</thead>
		<tbody id="raw_logs_tbody">
			<?php echo self::render_raw_logs_rows_html( $logs ); ?>
		</tbody>
		<tfoot>
			<tr>
				<th scope="col" class="manage-column column-cb check-column">
					<label class="screen-reader-text" for="cb-select-all-2"><?php esc_html_e( 'Chọn tất cả', 'sergio-ai-studio' ); ?></label>
					<input id="cb-select-all-2" type="checkbox">
				</th>
				<th scope="col" class="manage-column column-date"><?php esc_html_e( 'Thời Gian', 'sergio-ai-studio' ); ?></th>
				<th scope="col" class="manage-column column-level"><?php esc_html_e( 'Cấp Độ', 'sergio-ai-studio' ); ?></th>
				<th scope="col" class="manage-column column-stage"><?php esc_html_e( 'Giai Đoạn', 'sergio-ai-studio' ); ?></th>
				<th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Thông Điệp & Dữ Liệu', 'sergio-ai-studio' ); ?></th>
			</tr>
		</tfoot>
	</table>

	<!-- Bottom Navigation Bar -->
	<div class="tablenav bottom">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_html_e( 'Chọn hành động hàng loạt', 'sergio-ai-studio' ); ?></label>
			<select name="action2" id="bulk-action-selector-bottom">
				<option value="-1"><?php esc_html_e( 'Hành động', 'sergio-ai-studio' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Xóa', 'sergio-ai-studio' ); ?></option>
				<option value="clear_all"><?php esc_html_e( 'Xóa tất cả nhật ký', 'sergio-ai-studio' ); ?></option>
			</select>
			<input type="button" id="doaction2" class="button action" value="<?php esc_attr_e( 'Áp dụng', 'sergio-ai-studio' ); ?>">
		</div>
		<div class="tablenav-pages">
			<span class="displaying-num" id="log_displaying_num_bottom"><?php printf( esc_html__( '%d mục', 'sergio-ai-studio' ), count( $logs ) ); ?></span>
		</div>
		<br class="clear">
	</div>

</div>

