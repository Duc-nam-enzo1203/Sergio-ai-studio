<?php
/**
 * SEO Audit & AI On-Page Optimizer
 *
 * Provides site-wide SEO audits for both Posts and Pages, deep on-page inspection,
 * AI-driven metadata generation, automated content enhancement, and bulk auto-fix.
 *
 * @package Sergio_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sergio_AI_SEO_Audit {

	/**
	 * Run a comprehensive On-Page audit on a single post or page
	 *
	 * @param int $post_id Post/Page ID
	 * @return array|WP_Error
	 */
	public static function audit_post( $post_id ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return new WP_Error( 'invalid_post', __( 'Không tìm thấy bài viết hoặc trang hợp lệ.', 'sergio-ai-studio' ) );
		}

		$seo_data  = Sergio_AI_RankMath_Bridge::get_post_seo_data( $post_id );
		$focus_kw  = trim( $seo_data['focus_keyword'] );
		$title     = ! empty( $seo_data['seo_title'] ) ? $seo_data['seo_title'] : $post->post_title;
		$meta_desc = trim( $seo_data['description'] );
		$raw_html  = $post->post_content;
		$text      = wp_strip_all_tags( $raw_html );

		// Word count (Unicode/multilingual aware)
		preg_match_all( '/\p{L}+/u', $text, $word_matches );
		$word_count = ! empty( $word_matches[0] ) ? count( $word_matches[0] ) : 0;

		// Headings extraction
		preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $raw_html, $heading_matches );
		$headings_levels = ! empty( $heading_matches[1] ) ? $heading_matches[1] : array();
		$headings_texts  = ! empty( $heading_matches[2] ) ? array_map( 'wp_strip_all_tags', $heading_matches[2] ) : array();

		$h2_count = 0;
		$h3_count = 0;
		foreach ( $headings_levels as $lvl ) {
			if ( '2' === $lvl ) {
				$h2_count++;
			} elseif ( '3' === $lvl ) {
				$h3_count++;
			}
		}

		// Images and ALT extraction
		preg_match_all( '/<img[^>]+>/is', $raw_html, $img_tags );
		$total_images    = ! empty( $img_tags[0] ) ? count( $img_tags[0] ) : 0;
		$images_with_alt = 0;
		if ( $total_images > 0 ) {
			foreach ( $img_tags[0] as $tag ) {
				if ( preg_match( '/alt=([\'"])(.*?)\1/is', $tag, $alt_match ) && ! empty( trim( $alt_match[2] ) ) ) {
					$images_with_alt++;
				}
			}
		}

		// Links extraction
		$site_domain    = wp_parse_url( home_url(), PHP_URL_HOST );
		preg_match_all( '/<a[^>]+href=([\'"])(.*?)\1[^>]*>/is', $raw_html, $link_matches );
		$internal_links = 0;
		$external_links = 0;
		if ( ! empty( $link_matches[2] ) ) {
			foreach ( $link_matches[2] as $href ) {
				$href_domain = wp_parse_url( $href, PHP_URL_HOST );
				if ( empty( $href_domain ) || $href_domain === $site_domain ) {
					$internal_links++;
				} else {
					$external_links++;
				}
			}
		}

		// Focus keyword analysis
		$kw_in_title    = false;
		$kw_in_meta_desc= false;
		$kw_in_url      = false;
		$kw_in_first_10 = false;
		$kw_in_headings = false;
		$kw_occurrences = 0;
		$kw_density     = 0.0;

		if ( ! empty( $focus_kw ) ) {
			$kw_lower = mb_strtolower( $focus_kw, 'UTF-8' );

			// Title check
			if ( false !== mb_stripos( $title, $focus_kw, 0, 'UTF-8' ) ) {
				$kw_in_title = true;
			}

			// Meta description check
			if ( ! empty( $meta_desc ) && false !== mb_stripos( $meta_desc, $focus_kw, 0, 'UTF-8' ) ) {
				$kw_in_meta_desc = true;
			}

			// URL slug check
			$slug_clean = str_replace( '-', ' ', $post->post_name );
			if ( false !== mb_stripos( $slug_clean, sanitize_title( $focus_kw ), 0, 'UTF-8' ) || false !== mb_stripos( $slug_clean, $kw_lower, 0, 'UTF-8' ) ) {
				$kw_in_url = true;
			}

			// First 10% content check
			$first_10_len = max( 120, (int) ( mb_strlen( $text, 'UTF-8' ) * 0.10 ) );
			$first_10_str = mb_substr( $text, 0, $first_10_len, 'UTF-8' );
			if ( false !== mb_stripos( $first_10_str, $focus_kw, 0, 'UTF-8' ) ) {
				$kw_in_first_10 = true;
			}

			// Headings check
			foreach ( $headings_texts as $h_text ) {
				if ( false !== mb_stripos( $h_text, $focus_kw, 0, 'UTF-8' ) ) {
					$kw_in_headings = true;
					break;
				}
			}

			// Keyword density
			$kw_occurrences = mb_substr_count( mb_strtolower( $text, 'UTF-8' ), $kw_lower, 'UTF-8' );
			if ( $word_count > 0 && $kw_occurrences > 0 ) {
				$kw_density = round( ( $kw_occurrences / $word_count ) * 100, 2 );
			}
		}

		// Length calculations
		$title_char_count = mb_strlen( $title, 'UTF-8' );
		$desc_char_count  = mb_strlen( $meta_desc, 'UTF-8' );

		// Build Checklist with weights (Total = 100)
		$checklist = array();
		$score     = 0;

		// 1. Basic SEO Group (Total 44 pts)
		if ( ! empty( $focus_kw ) ) {
			$score += 8;
			$checklist[] = array(
				'category' => 'basic',
				'key'      => 'has_focus_kw',
				'title'    => __( 'Đã thiết lập Focus Keyword', 'sergio-ai-studio' ),
				'status'   => 'pass',
				'message'  => sprintf( __( 'Từ khóa chính: "%s"', 'sergio-ai-studio' ), $focus_kw ),
				'points'   => 8,
			);
		} else {
			$checklist[] = array(
				'category' => 'basic',
				'key'      => 'has_focus_kw',
				'title'    => __( 'Chưa có Focus Keyword', 'sergio-ai-studio' ),
				'status'   => 'fail',
				'message'  => __( 'Hãy thiết lập từ khóa mục tiêu để đo lường độ chuẩn SEO.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		if ( $kw_in_title ) {
			$score += 10;
			$checklist[] = array(
				'category' => 'basic',
				'key'      => 'kw_in_title',
				'title'    => __( 'Từ khóa xuất hiện trong Tiêu Đề SEO', 'sergio-ai-studio' ),
				'status'   => 'pass',
				'message'  => __( 'Tuyệt vời! Tiêu đề chứa chính xác từ khóa mục tiêu.', 'sergio-ai-studio' ),
				'points'   => 10,
			);
		} else {
			$checklist[] = array(
				'category' => 'basic',
				'key'      => 'kw_in_title',
				'title'    => __( 'Từ khóa chưa có trong Tiêu Đề SEO', 'sergio-ai-studio' ),
				'status'   => 'fail',
				'message'  => __( 'Google đánh giá cao từ khóa nằm ở nửa đầu tiêu đề.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		if ( $kw_in_meta_desc ) {
			$score += 8;
			$checklist[] = array(
				'category' => 'basic',
				'key'      => 'kw_in_meta_desc',
				'title'    => __( 'Từ khóa có trong Thẻ Mô Tả (Meta Desc)', 'sergio-ai-studio' ),
				'status'   => 'pass',
				'message'  => __( 'Từ khóa xuất hiện tự nhiên trong đoạn tóm tắt SERP.', 'sergio-ai-studio' ),
				'points'   => 8,
			);
		} else {
			$checklist[] = array(
				'category' => 'basic',
				'key'      => 'kw_in_meta_desc',
				'title'    => __( 'Từ khóa thiếu trong Thẻ Mô Tả', 'sergio-ai-studio' ),
				'status'   => empty( $meta_desc ) ? 'fail' : 'warning',
				'message'  => empty( $meta_desc ) ? __( 'Chưa nhập mô tả SEO (Meta Description).', 'sergio-ai-studio' ) : __( 'Mô tả đã có nhưng chưa chứa từ khóa chính.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		if ( $kw_in_url ) {
			$score += 8;
			$checklist[] = array(
				'category' => 'basic',
				'key'      => 'kw_in_url',
				'title'    => __( 'Từ khóa xuất hiện trong Đường Dẫn (URL Slug)', 'sergio-ai-studio' ),
				'status'   => 'pass',
				'message'  => sprintf( __( 'Đường dẫn thân thiện: /%s/', 'sergio-ai-studio' ), $post->post_name ),
				'points'   => 8,
			);
		} else {
			$checklist[] = array(
				'category' => 'basic',
				'key'      => 'kw_in_url',
				'title'    => __( 'Đường dẫn chưa chứa từ khóa', 'sergio-ai-studio' ),
				'status'   => 'warning',
				'message'  => __( 'Nên chứa từ khóa không dấu trong URL bài viết.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		if ( $kw_in_first_10 ) {
			$score += 10;
			$checklist[] = array(
				'category' => 'basic',
				'key'      => 'kw_in_first_10',
				'title'    => __( 'Từ khóa ở đoạn mở đầu (Answer-First Hook)', 'sergio-ai-studio' ),
				'status'   => 'pass',
				'message'  => __( 'Đạt chuẩn Claude-SEO: Xác nhận chủ đề và trả lời trực diện ngay đầu bài.', 'sergio-ai-studio' ),
				'points'   => 10,
			);
		} else {
			$checklist[] = array(
				'category' => 'basic',
				'key'      => 'kw_in_first_10',
				'title'    => __( 'Từ khóa thiếu ở phần mở đầu bài', 'sergio-ai-studio' ),
				'status'   => 'warning',
				'message'  => __( 'Claude-SEO khuyến nghị đưa từ khóa và câu trả lời chính vào 1-2 câu đầu tiên.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		// 2. Content & Claude-SEO Architecture Group (Total 36 pts)
		if ( $word_count >= 1000 ) {
			$score += 10;
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'content_length',
				'title'    => sprintf( __( 'Độ dài nội dung chuyên sâu (%d từ)', 'sergio-ai-studio' ), $word_count ),
				'status'   => 'pass',
				'message'  => __( 'Độ dài đạt chuẩn Helpful Content, cung cấp giá trị E-E-A-T vượt trội.', 'sergio-ai-studio' ),
				'points'   => 10,
			);
		} elseif ( $word_count >= 500 ) {
			$score += 6;
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'content_length',
				'title'    => sprintf( __( 'Độ dài nội dung mức khá (%d từ)', 'sergio-ai-studio' ), $word_count ),
				'status'   => 'warning',
				'message'  => __( 'Nội dung ngắn gọn, nên mở rộng thêm để cạnh tranh thứ hạng cao hơn.', 'sergio-ai-studio' ),
				'points'   => 6,
			);
		} else {
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'content_length',
				'title'    => sprintf( __( 'Nội dung mỏng (%d từ)', 'sergio-ai-studio' ), $word_count ),
				'status'   => 'fail',
				'message'  => __( 'Dưới 500 từ dễ bị thuật toán Google Thin Content đánh giá thấp.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		if ( $h2_count >= 2 ) {
			$score += 6;
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'headings_structure',
				'title'    => sprintf( __( 'Cấu trúc tiêu đề phân đoạn tốt (%d thẻ H2)', 'sergio-ai-studio' ), $h2_count ),
				'status'   => 'pass',
				'message'  => __( 'Cấu trúc bài viết có phân mục rõ ràng, dễ quét thông tin.', 'sergio-ai-studio' ),
				'points'   => 6,
			);
		} else {
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'headings_structure',
				'title'    => __( 'Thiếu thẻ tiêu đề phụ H2', 'sergio-ai-studio' ),
				'status'   => 'warning',
				'message'  => __( 'Nên chia bài viết thành ít nhất 2 đến 3 mục H2 chính.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		if ( $kw_in_headings ) {
			$score += 6;
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'kw_in_headings',
				'title'    => __( 'Từ khóa xuất hiện trong Thẻ Tiêu Đề Phụ (H2/H3)', 'sergio-ai-studio' ),
				'status'   => 'pass',
				'message'  => __( 'Xác thực độ liên quan của các phân đoạn nội dung.', 'sergio-ai-studio' ),
				'points'   => 6,
			);
		} else {
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'kw_in_headings',
				'title'    => __( 'Từ khóa chưa có trong tiêu đề phụ', 'sergio-ai-studio' ),
				'status'   => 'warning',
				'message'  => __( 'Chèn từ khóa hoặc biến thể của từ khóa vào ít nhất một thẻ H2.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		if ( $kw_density >= 0.8 && $kw_density <= 2.5 ) {
			$score += 6;
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'kw_density',
				'title'    => sprintf( __( 'Mật độ từ khóa hoàn hảo (%s%%)', 'sergio-ai-studio' ), $kw_density ),
				'status'   => 'pass',
				'message'  => sprintf( __( 'Xuất hiện %d lần trên tổng số %d từ.', 'sergio-ai-studio' ), $kw_occurrences, $word_count ),
				'points'   => 6,
			);
		} elseif ( $kw_density > 2.5 ) {
			$score += 2;
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'kw_density',
				'title'    => sprintf( __( 'Cảnh báo nhồi nhét từ khóa (%s%%)', 'sergio-ai-studio' ), $kw_density ),
				'status'   => 'warning',
				'message'  => __( 'Mật độ từ khóa quá cao (>2.5%) có nguy cơ dính thuật toán Google Spam.', 'sergio-ai-studio' ),
				'points'   => 2,
			);
		} else {
			$score += 2;
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'kw_density',
				'title'    => sprintf( __( 'Mật độ từ khóa hơi thấp (%s%%)', 'sergio-ai-studio' ), $kw_density ),
				'status'   => 'warning',
				'message'  => __( 'Nên rải đều từ khóa chính và các từ đồng nghĩa trong nội dung bài.', 'sergio-ai-studio' ),
				'points'   => 2,
			);
		}

		// Claude-SEO GEO / AEO & FAQ Structure Check
		$has_faq_or_takeaway = preg_match( '/(?:faq|câu hỏi thường gặp|hỏi đáp|key takeaways|tóm tắt|điểm cốt lõi|q&a)/iu', $raw_html );
		if ( $has_faq_or_takeaway ) {
			$score += 8;
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'claude_geo_faq',
				'title'    => __( 'Chuẩn Claude-SEO: Đạt cấu trúc GEO / AEO & FAQ', 'sergio-ai-studio' ),
				'status'   => 'pass',
				'message'  => __( 'Có phân đoạn Tóm Tắt Điểm Cốt Lõi hoặc FAQ, tối ưu hóa trích xuất trực tiếp trên Google AI Overviews & Perplexity.', 'sergio-ai-studio' ),
				'points'   => 8,
			);
		} else {
			$checklist[] = array(
				'category' => 'content',
				'key'      => 'claude_geo_faq',
				'title'    => __( 'Thiếu khối Tóm Tắt Nhanh hoặc FAQ (GEO / AEO)', 'sergio-ai-studio' ),
				'status'   => 'warning',
				'message'  => __( 'Claude-SEO khuyến nghị có bảng tóm tắt mở đầu hoặc mục FAQ cuối bài để đón đầu tìm kiếm AI.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		// 3. Media & Links Group (Total 14 pts)
		if ( $total_images > 0 && $images_with_alt === $total_images ) {
			$score += 6;
			$checklist[] = array(
				'category' => 'media',
				'key'      => 'images_alt',
				'title'    => sprintf( __( 'Hình ảnh tối ưu hoàn toàn (%d/%d ảnh có ALT)', 'sergio-ai-studio' ), $images_with_alt, $total_images ),
				'status'   => 'pass',
				'message'  => __( '100% hình ảnh có văn bản thay thế (alt) thân thiện với SEO.', 'sergio-ai-studio' ),
				'points'   => 6,
			);
		} elseif ( $total_images > 0 ) {
			$score += 3;
			$checklist[] = array(
				'category' => 'media',
				'key'      => 'images_alt',
				'title'    => sprintf( __( 'Hình ảnh còn thiếu thẻ ALT (%d/%d ảnh có ALT)', 'sergio-ai-studio' ), $images_with_alt, $total_images ),
				'status'   => 'warning',
				'message'  => __( 'Cần bổ sung thuộc tính alt cho các ảnh còn lại.', 'sergio-ai-studio' ),
				'points'   => 3,
			);
		} else {
			$checklist[] = array(
				'category' => 'media',
				'key'      => 'images_alt',
				'title'    => __( 'Chưa có hình ảnh minh họa', 'sergio-ai-studio' ),
				'status'   => 'warning',
				'message'  => __( 'Nên thêm ít nhất 1 hình ảnh đại diện hoặc đồ thị minh họa.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		if ( $internal_links > 0 ) {
			$score += 4;
			$checklist[] = array(
				'category' => 'media',
				'key'      => 'internal_links',
				'title'    => sprintf( __( 'Có liên kết nội bộ (%d links)', 'sergio-ai-studio' ), $internal_links ),
				'status'   => 'pass',
				'message'  => __( 'Giúp Google thu thập dữ liệu và giữ chân người đọc tốt.', 'sergio-ai-studio' ),
				'points'   => 4,
			);
		} else {
			$checklist[] = array(
				'category' => 'media',
				'key'      => 'internal_links',
				'title'    => __( 'Chưa có liên kết nội bộ (Internal link)', 'sergio-ai-studio' ),
				'status'   => 'warning',
				'message'  => __( 'Hãy chèn 1-2 link liên kết tới các bài viết liên quan cùng site.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		if ( $external_links > 0 ) {
			$score += 4;
			$checklist[] = array(
				'category' => 'media',
				'key'      => 'external_links',
				'title'    => sprintf( __( 'Có nguồn dẫn chiếu bên ngoài (%d links)', 'sergio-ai-studio' ), $external_links ),
				'status'   => 'pass',
				'message'  => __( 'Dẫn nguồn tài liệu uy tín giúp nâng cao độ tin cậy E-E-A-T.', 'sergio-ai-studio' ),
				'points'   => 4,
			);
		} else {
			$checklist[] = array(
				'category' => 'media',
				'key'      => 'external_links',
				'title'    => __( 'Chưa có nguồn dẫn chứng bên ngoài', 'sergio-ai-studio' ),
				'status'   => 'warning',
				'message'  => __( 'Nên thêm link nguồn dẫn chứng tới các tài liệu uy tín.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		// 4. Snippet Length (Total 6 pts)
		if ( $title_char_count >= 40 && $title_char_count <= 65 ) {
			$score += 3;
			$checklist[] = array(
				'category' => 'snippet',
				'key'      => 'title_length',
				'title'    => sprintf( __( 'Độ dài Tiêu Đề chuẩn SERP (%d ký tự)', 'sergio-ai-studio' ), $title_char_count ),
				'status'   => 'pass',
				'message'  => __( 'Không bị cắt cụt dấu ba chấm (...) trên kết quả tìm kiếm.', 'sergio-ai-studio' ),
				'points'   => 3,
			);
		} else {
			$checklist[] = array(
				'category' => 'snippet',
				'key'      => 'title_length',
				'title'    => sprintf( __( 'Độ dài Tiêu Đề chưa tối ưu (%d ký tự)', 'sergio-ai-studio' ), $title_char_count ),
				'status'   => 'warning',
				'message'  => __( 'Độ dài lý tưởng là từ 40 đến 60 ký tự.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		if ( $desc_char_count >= 120 && $desc_char_count <= 165 ) {
			$score += 3;
			$checklist[] = array(
				'category' => 'snippet',
				'key'      => 'desc_length',
				'title'    => sprintf( __( 'Độ dài Mô Tả Meta chuẩn (%d ký tự)', 'sergio-ai-studio' ), $desc_char_count ),
				'status'   => 'pass',
				'message'  => __( 'Kích thước hiển thị hoàn hảo trên cả máy tính lẫn di động.', 'sergio-ai-studio' ),
				'points'   => 3,
			);
		} else {
			$checklist[] = array(
				'category' => 'snippet',
				'key'      => 'desc_length',
				'title'    => sprintf( __( 'Độ dài Mô Tả Meta chưa tối ưu (%d ký tự)', 'sergio-ai-studio' ), $desc_char_count ),
				'status'   => 'warning',
				'message'  => __( 'Độ dài chuẩn hiển thị đầy đủ là từ 120 đến 160 ký tự.', 'sergio-ai-studio' ),
				'points'   => 0,
			);
		}

		$final_score = min( 100, max( 0, $score ) );

		// Store internal On-Page Studio SEO score (do not overwrite Rank Math's editor-calculated score)
		update_post_meta( $post_id, '_sergio_ai_seo_score', $final_score );

		return array(
			'post_id'         => $post_id,
			'post_type'       => $post->post_type,
			'post_title'      => $post->post_title,
			'post_date'       => $post->post_date,
			'post_status'     => $post->post_status,
			'edit_url'        => function_exists( 'get_edit_post_link' ) ? get_edit_post_link( $post_id, 'raw' ) : admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
			'view_url'        => get_permalink( $post_id ),
			'score'           => $final_score,
			'score_badge'     => self::get_score_badge( $final_score ),
			'focus_keyword'   => $focus_kw,
			'seo_title'       => $title,
			'meta_desc'       => $meta_desc,
			'word_count'      => $word_count,
			'total_images'    => $total_images,
			'internal_links'  => $internal_links,
			'external_links'  => $external_links,
			'checklist'       => $checklist,
			'serp_preview'    => array(
				'title'       => $title,
				'url'         => get_permalink( $post_id ),
				'description' => ! empty( $meta_desc ) ? $meta_desc : wp_trim_words( $text, 25, '...' ),
			),
		);
	}

	/**
	 * Run an overview audit for all posts and pages on the site with actionable diagnostic plan
	 *
	 * @param int    $limit     Number of items to analyze
	 * @param string $post_type 'any', 'post', or 'page'
	 * @return array
	 */
	public static function audit_site_overview( $limit = 50, $post_type = 'any' ) {
		$types = ( 'any' === $post_type || empty( $post_type ) ) ? array( 'post', 'page' ) : (array) $post_type;

		$posts = get_posts( array(
			'post_type'      => $types,
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => absint( $limit ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		if ( empty( $posts ) ) {
			return array(
				'total_posts'       => 0,
				'average_score'     => 0,
				'good_count'        => 0,
				'warning_count'     => 0,
				'critical_count'    => 0,
				'missing_focus_kw'  => 0,
				'missing_meta_desc' => 0,
				'thin_content'      => 0,
				'missing_kw_ids'    => array(),
				'missing_desc_ids'  => array(),
				'posts'             => array(),
				'action_plan'       => array(),
			);
		}

		$total_score       = 0;
		$good_count        = 0;
		$warning_count     = 0;
		$critical_count    = 0;
		$missing_focus_kw  = 0;
		$missing_meta_desc = 0;
		$thin_content      = 0;
		$missing_kw_ids    = array();
		$missing_desc_ids  = array();
		$thin_content_ids  = array();
		$audited_posts     = array();

		foreach ( $posts as $p ) {
			$res = self::audit_post( $p->ID );
			if ( is_wp_error( $res ) ) {
				continue;
			}

			$score = $res['score'];
			$total_score += $score;

			if ( $score >= 80 ) {
				$good_count++;
			} elseif ( $score >= 50 ) {
				$warning_count++;
			} else {
				$critical_count++;
			}

			if ( empty( $res['focus_keyword'] ) ) {
				$missing_focus_kw++;
				$missing_kw_ids[] = $p->ID;
			}

			if ( empty( $res['meta_desc'] ) ) {
				$missing_meta_desc++;
				$missing_desc_ids[] = $p->ID;
			}

			if ( $res['word_count'] < 500 ) {
				$thin_content++;
				$thin_content_ids[] = $p->ID;
			}

			$audited_posts[] = array(
				'id'            => $res['post_id'],
				'post_type'     => $res['post_type'],
				'title'         => $res['post_title'],
				'status'        => $res['post_status'],
				'score'         => $score,
				'score_badge'   => $res['score_badge'],
				'focus_keyword' => $res['focus_keyword'],
				'meta_desc'     => $res['meta_desc'],
				'word_count'    => $res['word_count'],
				'edit_url'      => $res['edit_url'],
				'view_url'      => $res['view_url'],
			);
		}

		$total_count   = count( $audited_posts );
		$average_score = $total_count > 0 ? round( $total_score / $total_count ) : 0;

		// Build Actionable Site Diagnostic Plan
		$action_plan = array();

		if ( $missing_focus_kw > 0 ) {
			$action_plan[] = array(
				'type'        => 'missing_kw',
				'severity'    => 'critical',
				'title'       => sprintf( __( 'Có %d bài viết / trang chưa thiết lập Focus Keyword', 'sergio-ai-studio' ), $missing_focus_kw ),
				'description' => __( 'Thiếu từ khóa chính khiến Rank Math và Google không thể xếp hạng trang vào đúng chủ đề tìm kiếm mục tiêu.', 'sergio-ai-studio' ),
				'solution'    => __( 'Dùng công cụ Tự Động Cải Thiện AI bên dưới để sinh bộ từ khóa tối ưu cho toàn bộ các trang này chỉ với 1 click.', 'sergio-ai-studio' ),
				'affected'    => count( $missing_kw_ids ),
				'action_btn'  => __( '⚡ Tự Động Điền Từ Khóa Hàng Loạt', 'sergio-ai-studio' ),
			);
		}

		if ( $missing_meta_desc > 0 ) {
			$action_plan[] = array(
				'type'        => 'missing_desc',
				'severity'    => 'warning',
				'title'       => sprintf( __( 'Có %d bài viết / trang thiếu Thẻ Mô Tả (Meta Description)', 'sergio-ai-studio' ), $missing_meta_desc ),
				'description' => __( 'Khi thiếu mô tả, Google sẽ tự cắt ghép ngẫu nhiên văn bản trên website, làm giảm tỷ lệ click chuột (CTR) đáng kể.', 'sergio-ai-studio' ),
				'solution'    => __( 'Kích hoạt AI viết thẻ mô tả chuẩn 155 ký tự kèm lời kêu gọi hành động cho toàn bộ danh sách.', 'sergio-ai-studio' ),
				'affected'    => count( $missing_desc_ids ),
				'action_btn'  => __( '⚡ Tự Động Viết Mô Tả Hàng Loạt', 'sergio-ai-studio' ),
			);
		}

		if ( $thin_content > 0 ) {
			$action_plan[] = array(
				'type'        => 'thin_content',
				'severity'    => 'warning',
				'title'       => sprintf( __( 'Phát hiện %d trang có nội dung mỏng (< 500 từ)', 'sergio-ai-studio' ), $thin_content ),
				'description' => __( 'Nội dung ngắn không cung cấp đủ giá trị có nguy cơ bị thuật toán Google Helpful Content hạn chế hiển thị.', 'sergio-ai-studio' ),
				'solution'    => __( 'Dùng tính năng "AI Nâng Cấp Nội Dung Đạt Chuẩn Rank Math" trong bàn làm việc của từng bài để tự động mở rộng thành 800 - 1.200 từ.', 'sergio-ai-studio' ),
				'affected'    => count( $thin_content_ids ),
				'affected_ids'=> $thin_content_ids,
			);
		}

		$low_score_ids = array();
		foreach ( $audited_posts as $ap ) {
			if ( $ap['score'] < 50 ) {
				$low_score_ids[] = $ap['id'];
			}
		}

		if ( ! empty( $low_score_ids ) ) {
			$action_plan[] = array(
				'type'        => 'low_score',
				'severity'    => 'critical',
				'title'       => sprintf( __( 'Có %d bài viết / trang đang ở thang điểm Kém (< 50 điểm)', 'sergio-ai-studio' ), count( $low_score_ids ) ),
				'description' => __( 'Các trang này chưa đáp ứng các tiêu chuẩn Rank Math (thiếu từ khóa mở đầu, thiếu H2 hoặc mật độ từ khóa quá thấp).', 'sergio-ai-studio' ),
				'solution'    => __( 'Nhấp vào nút bên dưới để AI tự động nâng cấp toàn bộ nội dung và metadata đạt chuẩn Rank Math 85+ điểm.', 'sergio-ai-studio' ),
				'affected'    => count( $low_score_ids ),
				'affected_ids'=> $low_score_ids,
				'action_btn'  => __( '⚡ Tự Động Nâng Cấp Nội Dung Hàng Loạt (85+ Điểm)', 'sergio-ai-studio' ),
			);
		}

		if ( empty( $action_plan ) ) {
			$action_plan[] = array(
				'type'        => 'healthy',
				'severity'    => 'success',
				'title'       => __( 'Sức khỏe On-Page toàn site đang ở mức rất tốt!', 'sergio-ai-studio' ),
				'description' => __( 'Tất cả các bài viết và trang đều đã có từ khóa chính và thẻ mô tả chuẩn. Hãy tiếp tục tối ưu liên kết nội bộ.', 'sergio-ai-studio' ),
				'solution'    => __( 'Duy trì kiểm định định kỳ sau mỗi lần xuất bản nội dung mới.', 'sergio-ai-studio' ),
			);
		}

		return array(
			'total_posts'       => $total_count,
			'average_score'     => $average_score,
			'good_count'        => $good_count,
			'warning_count'     => $warning_count,
			'critical_count'    => $critical_count,
			'missing_focus_kw'  => $missing_focus_kw,
			'missing_meta_desc' => $missing_meta_desc,
			'thin_content'      => $thin_content,
			'missing_kw_ids'    => $missing_kw_ids,
			'missing_desc_ids'  => $missing_desc_ids,
			'low_score_ids'     => $low_score_ids,
			'posts'             => $audited_posts,
			'action_plan'       => $action_plan,
		);
	}

	/**
	 * Run AI-powered SEO Optimization on a single post using Gemini
	 *
	 * @param int $post_id Post/Page ID
	 * @param Sergio_AI_Gemini_Client $client Gemini client instance
	 * @param Sergio_AI_Logger $logger Logger instance
	 * @return array|WP_Error
	 */
	public static function ai_optimize_post( $post_id, $client, $logger ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return new WP_Error( 'invalid_post', __( 'Bài viết hoặc trang không tồn tại.', 'sergio-ai-studio' ) );
		}

		$seo_data   = Sergio_AI_RankMath_Bridge::get_post_seo_data( $post_id );
		$clean_body = wp_strip_all_tags( $post->post_content );
		$body_snip  = mb_substr( $clean_body, 0, 3500, 'UTF-8' );

		$prompt = "Bạn là Giám Đốc Kỹ Thuật SEO & Chuyên Gia Tối Ưu Tìm Kiếm AI (theo Claude-SEO Framework, Google Search Quality Rater Guidelines, E-E-A-T và AEO/GEO - Generative Engine Optimization).\n"
			. "Hãy phân tích sâu sắc bài viết/trang dưới đây và thiết lập bộ chiến lược SEO On-Page hoàn hảo (mục tiêu 85-100 điểm Rank Math và tối ưu trích dẫn AI Overviews/ChatGPT/Perplexity).\n\n"
			. "--- THÔNG TIN NỘI DUNG ---\n"
			. "Loại: " . strtoupper( $post->post_type ) . "\n"
			. "Tiêu đề hiện tại: " . $post->post_title . "\n"
			. "Từ khóa mục tiêu hiện tại: " . ( ! empty( $seo_data['focus_keyword'] ) ? $seo_data['focus_keyword'] : '(Chưa có)' ) . "\n"
			. "Mô tả hiện tại: " . ( ! empty( $seo_data['description'] ) ? $seo_data['description'] : '(Chưa có)' ) . "\n"
			. "Trích đoạn bài viết:\n" . $body_snip . "\n\n"
			. "--- NGUYÊN TẮC CLAUDE-SEO ---\n"
			. "1. Ý Định Tìm Kiếm (Search Intent): Xác định đúng loại intent (Thông tin / Nghiên cứu thương mại / Giao dịch) và đề xuất Focus Keyword đắt giá nhất (2-4 từ, có lượng tìm kiếm cao).\n"
			. "2. Tiêu Đề CTR Đỉnh Cao: Sinh 3 biến thể tiêu đề (45-60 ký tự) chứa từ khóa chính ở nửa đầu, kết hợp Power Words, mốc năm 2026 hoặc con số/dấu ngoặc vuông.\n"
			. "3. Mô Tả Meta Thu Hút: Sinh 2 biến thể mô tả (145-160 ký tự) chứa từ khóa chính tự nhiên, nêu bật lợi ích và có lời kêu gọi hành động (CTA) rõ ràng.\n"
			. "4. LSI & Topical Entities: Cung cấp 3-5 thực thể ngữ nghĩa/từ khóa LSI để xây dựng topical authority.\n"
			. "5. Nâng Cấp E-E-A-T & AEO: Đề xuất các câu hỏi PAA (People Also Ask) chuẩn Claude-SEO để chuẩn bị cho AI Overviews.\n\n"
			. "--- YÊU CẦU ĐẦU RA (JSON THUẦN TÚY) ---\n"
			. "Trả về kết quả DUY NHẤT một chuỗi JSON hợp lệ (không markdown code fences):\n"
			. "{\n"
			. "  \"search_intent\": \"Ý định tìm kiếm (ví dụ: Thông tin chuyên sâu / Hướng dẫn thực hành)\",\n"
			. "  \"recommended_focus_keyword\": \"Từ khóa chính tối ưu nhất (2-4 từ)\",\n"
			. "  \"secondary_keywords\": [\"Từ khóa LSI 1\", \"Từ khóa LSI 2\", \"Từ khóa LSI 3\", \"Từ khóa LSI 4\"],\n"
			. "  \"suggested_titles\": [\n"
			. "    {\n"
			. "      \"title\": \"Tiêu đề 1 (chứa từ khóa ở đầu, 45-60 ký tự)\",\n"
			. "      \"angle\": \"Góc tiếp cận\"\n"
			. "    },\n"
			. "    {\n"
			. "      \"title\": \"Tiêu đề 2 (hấp dẫn, 45-60 ký tự)\",\n"
			. "      \"angle\": \"Góc tiếp cận\"\n"
			. "    },\n"
			. "    {\n"
			. "      \"title\": \"Tiêu đề 3 (hấp dẫn, 45-60 ký tự)\",\n"
			. "      \"angle\": \"Góc tiếp cận\"\n"
			. "    }\n"
			. "  ],\n"
			. "  \"suggested_descriptions\": [\n"
			. "    {\n"
			. "      \"description\": \"Mô tả 1 (đúng 145-160 ký tự, có từ khóa và CTA)\",\n"
			. "      \"style\": \"Kích thích click\"\n"
			. "    },\n"
			. "    {\n"
			. "      \"description\": \"Mô tả 2 (đúng 145-160 ký tự, súc tích uy tín)\",\n"
			. "      \"style\": \"Chuyên gia tin cậy\"\n"
			. "    }\n"
			. "  ],\n"
			. "  \"content_enhancements\": [\n"
			. "    \"Gợi ý mục H2 hoặc bảng số liệu nâng cao E-E-A-T\",\n"
			. "    \"Gợi ý FAQ theo chuẩn Claude-SEO GEO/AEO đón đầu AI Overviews\"\n"
			. "  ]\n"
			. "}";

		if ( $logger ) {
			$logger->info( 'seo_audit', sprintf( 'Bắt đầu gửi yêu cầu Gemini tối ưu SEO cho %s ID #%d...', $post->post_type, $post_id ) );
		}

		$system_instruction = 'Bạn là chuyên gia Claude-SEO và Rank Math On-page cao cấp. Chỉ trả về kết quả JSON hợp lệ theo đúng cấu trúc được yêu cầu.';
		$response           = $client->generate_content( $prompt, $system_instruction, false, 0.4, 60 );

		if ( is_wp_error( $response ) ) {
			if ( $logger ) {
				$logger->error( 'seo_audit', 'Lỗi Gemini khi tối ưu SEO: ' . $response->get_error_message() );
			}
			return $response;
		}

		$text_res = trim( $response['text'] );

		// Strip markdown fences if present
		if ( preg_match( '/```(?:json)?\s*([\s\S]*?)\s*```/i', $text_res, $m ) ) {
			$text_res = trim( $m[1] );
		}

		$decoded = json_decode( $text_res, true );
		if ( ! is_array( $decoded ) || empty( $decoded['recommended_focus_keyword'] ) ) {
			if ( preg_match( '/\{[\s\S]*\}/u', $text_res, $json_match ) ) {
				$decoded = json_decode( $json_match[0], true );
			}
		}

		if ( ! is_array( $decoded ) || empty( $decoded['recommended_focus_keyword'] ) ) {
			return new WP_Error( 'json_parse_error', __( 'Không thể phân tích kết quả tối ưu từ Gemini API.', 'sergio-ai-studio' ) );
		}

		if ( $logger ) {
			$logger->info( 'seo_audit', sprintf( 'Đã tạo xong gói đề xuất tối ưu SEO cho %s #%d.', $post->post_type, $post_id ) );
		}

		return array(
			'post_id'                => $post_id,
			'post_type'              => $post->post_type,
			'search_intent'          => ! empty( $decoded['search_intent'] ) ? sanitize_text_field( $decoded['search_intent'] ) : '',
			'recommended_focus_kw'   => sanitize_text_field( $decoded['recommended_focus_keyword'] ),
			'secondary_keywords'     => ! empty( $decoded['secondary_keywords'] ) ? array_map( 'sanitize_text_field', (array) $decoded['secondary_keywords'] ) : array(),
			'suggested_titles'       => ! empty( $decoded['suggested_titles'] ) ? (array) $decoded['suggested_titles'] : array(),
			'suggested_descriptions' => ! empty( $decoded['suggested_descriptions'] ) ? (array) $decoded['suggested_descriptions'] : array(),
			'content_enhancements'   => ! empty( $decoded['content_enhancements'] ) ? array_map( 'sanitize_text_field', (array) $decoded['content_enhancements'] ) : array(),
		);
	}

	/**
	 * Run Deep AI Content Optimization (Rewriting & Enriching Post Content to hit 85-95+ Rank Math score)
	 *
	 * @param int $post_id Post/Page ID
	 * @param string $focus_kw Target Focus Keyword
	 * @param Sergio_AI_Gemini_Client $client Gemini client instance
	 * @param Sergio_AI_Logger $logger Logger instance
	 * @return array|WP_Error Updated audit result
	 */
	public static function ai_deep_optimize_content( $post_id, $focus_kw, $client, $logger ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return new WP_Error( 'invalid_post', __( 'Không tìm thấy bài viết hoặc trang hợp lệ.', 'sergio-ai-studio' ) );
		}

		$focus_kw = trim( sanitize_text_field( $focus_kw ) );
		if ( empty( $focus_kw ) ) {
			$seo_data = Sergio_AI_RankMath_Bridge::get_post_seo_data( $post_id );
			$focus_kw = trim( $seo_data['focus_keyword'] );
		}
		if ( empty( $focus_kw ) ) {
			return new WP_Error( 'missing_keyword', __( 'Vui lòng nhập hoặc chọn một Focus Keyword trước khi nâng cấp nội dung.', 'sergio-ai-studio' ) );
		}

		$raw_html = $post->post_content;
		$title    = $post->post_title;

		$prompt = "Bạn là Kỹ Sư Trưởng SEO & Chuyên Gia Biên Tập Nội Dung Cao Cấp theo Claude-SEO Framework (Google Helpful Content System, E-E-A-T, và Generative Engine Optimization - GEO/AEO).\n\n"
			. "Nhiệm vụ: Tái cấu trúc, biên tập và làm sâu sắc toàn bộ nội dung HTML của bài viết/trang dưới đây để đạt điểm On-Page tối đa (85 đến 100 điểm Rank Math), đồng thời tối ưu hóa trích xuất trực tiếp (Passage Citability) cho Google AI Overviews, Perplexity và ChatGPT Search.\n\n"
			. "--- THÔNG TIN BÀI VIẾT ---\n"
			. "Loại nội dung: " . strtoupper( $post->post_type ) . "\n"
			. "Tiêu đề: " . $title . "\n"
			. "Focus Keyword mục tiêu: \"" . $focus_kw . "\"\n"
			. "Nội dung gốc hiện tại:\n" . $raw_html . "\n\n"
			. "--- QUY TẮC CỐT LÕI CLAUDE-SEO & RANK MATH 100 ĐIỂM ---\n"
			. "1. ANSWER-FIRST HOOK (GEO / AEO):\n"
			. "   - Ngay ở đoạn <p> đầu tiên, cung cấp câu trả lời trực diện, cô đọng nhất (2-3 câu) cho vấn đề cốt lõi của người đọc, chứa chính xác cụm từ khóa mục tiêu \"" . $focus_kw . "\".\n"
			. "   - Ngay sau đó, chèn một khối tóm tắt nhanh dạng danh sách: <h3>Điểm Cốt Lõi (Key Takeaways)</h3><ul><li>...</li><li>...</li><li>...</li></ul>.\n"
			. "2. PHÂN CẤP TIÊU ĐỀ SEMANTIC (H2, H3):\n"
			. "   - Có ít nhất 3 đến 4 thẻ <h2> và các thẻ <h3> trực thuộc. Tuyệt đối không nhảy cóc cấp độ tiêu đề.\n"
			. "   - Thẻ <h2> đầu tiên PHẢI chứa chính xác Focus Keyword \"" . $focus_kw . "\".\n"
			. "   - Các thẻ <h2> và <h3> tiếp theo phải bao quát các thực thể ngữ nghĩa liên quan (LSI Entities).\n"
			. "3. MẬT ĐỘ TỪ KHÓA TỰ NHIÊN (1.0% - 1.8%):\n"
			. "   - Rải đều cụm từ \"" . $focus_kw . "\" tự nhiên trong các đoạn văn và danh sách. Tuyệt đối không nhồi nhét thô thiển.\n"
			. "4. ĐỘ SÂU NỘI DUNG & BẢO TOÀN DỮ KIỆN THỰC TẾ:\n"
			. "   - Dựa trên dữ liệu và bối cảnh của nội dung gốc, tổ chức lại mạch bài viết chuyên sâu, rõ ràng và mạch lạc.\n"
			. "   - TUYỆT ĐỐI KHÔNG BỊA ĐẶT số liệu thống kê, tên công ty, nghiên cứu hoặc số đo không có trong bài gốc.\n"
			. "   - Loại bỏ các câu mở đầu sáo rỗng vô nghĩa (ví dụ: 'Trong kỷ nguyên số ngày nay...').\n"
			. "5. THẺ ALT HÌNH ẢNH:\n"
			. "   - Nếu nội dung có thẻ <img>, hãy bổ sung thuộc tính alt đầy đủ, giàu ngữ cảnh và chứa từ khóa liên quan (ví dụ: alt=\"Hình ảnh minh họa cho " . $focus_kw . "\").\n"
			. "6. MỤC CÂU HỎI THƯỜNG GẶP (CLAUDE-SEO FAQ SECTION):\n"
			. "   - Bắt buộc chèn ở cuối bài một mục: <h2>Câu Hỏi Thường Gặp Về " . $focus_kw . " (FAQ)</h2> với 3-4 câu hỏi dạng <h3> kèm câu trả lời súc tích 2-3 câu, tối ưu cho Google People Also Ask & AI Search.\n"
			. "7. ĐỊNH DẠNG ĐẦU RA:\n"
			. "   - Trả về DUY NHẤT mã HTML hoàn chỉnh của thân bài viết (bắt đầu bằng <p>, <h2>, <ul>...). KHÔNG bọc thẻ <html>, <body>. KHÔNG dùng markdown code block fences (```html). KHÔNG có lời giải thích ngoài HTML.";

		$system_instruction = 'Bạn là Kỹ sư trưởng Claude-SEO và biên tập viên nội dung chuẩn Rank Math 100 điểm. Trả về DUY NHẤT mã HTML thân bài viết đã được nâng cấp.';

		if ( $logger ) {
			$logger->info( 'seo_audit', sprintf( 'Bắt đầu dùng Gemini AI tối ưu sâu nội dung %s #%d với từ khóa [%s] theo Claude-SEO...', $post->post_type, $post_id, $focus_kw ) );
		}

		$response = $client->generate_content( $prompt, $system_instruction, false, 0.4, 90 );

		if ( is_wp_error( $response ) ) {
			if ( $logger ) {
				$logger->error( 'seo_audit', 'Lỗi Gemini khi tối ưu nội dung: ' . $response->get_error_message() );
			}
			return $response;
		}

		$optimized_html = trim( $response['text'] );

		// Strip markdown fences
		if ( preg_match( '/```(?:html)?\s*([\s\S]*?)\s*```/i', $optimized_html, $m ) ) {
			$optimized_html = trim( $m[1] );
		}

		// Security sanitize
		$sanitized_html = method_exists( 'Sergio_AI_Security', 'sanitize_article_html' )
			? Sergio_AI_Security::sanitize_article_html( $optimized_html )
			: wp_kses_post( $optimized_html );

		if ( empty( trim( wp_strip_all_tags( $sanitized_html ) ) ) ) {
			return new WP_Error( 'empty_content', __( 'Nội dung tối ưu bị rỗng hoặc không hợp lệ.', 'sergio-ai-studio' ) );
		}

		// Update post content
		$update_result = wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => $sanitized_html,
		), true );

		if ( is_wp_error( $update_result ) ) {
			return $update_result;
		}

		// Ensure Metadata and Title are synced cleanly with focus keyword
		$seo_data  = Sergio_AI_RankMath_Bridge::get_post_seo_data( $post_id );
		$cur_title = ! empty( $seo_data['seo_title'] ) ? $seo_data['seo_title'] : $post->post_title;
		if ( false === mb_stripos( $cur_title, $focus_kw, 0, 'UTF-8' ) ) {
			$cur_title = mb_substr( $focus_kw . ': ' . $post->post_title, 0, 60, 'UTF-8' );
		}

		$cur_desc = ! empty( $seo_data['description'] ) ? $seo_data['description'] : '';
		if ( empty( $cur_desc ) || false === mb_stripos( $cur_desc, $focus_kw, 0, 'UTF-8' ) ) {
			$excerpt_text = wp_strip_all_tags( $sanitized_html );
			$excerpt_text = trim( preg_replace( '/\s+/', ' ', $excerpt_text ) );
			$cur_desc     = mb_substr( $excerpt_text, 0, 155, 'UTF-8' );
		}

		Sergio_AI_RankMath_Bridge::update_post_seo_data( $post_id, array(
			'focus_keyword' => $focus_kw,
			'seo_title'     => $cur_title,
			'description'   => $cur_desc,
		) );

		if ( 'draft' === $post->post_status ) {
			wp_update_post( array(
				'ID'        => $post_id,
				'post_name' => sanitize_title( $focus_kw ),
			) );
		}

		if ( $logger ) {
			$logger->info( 'seo_audit', sprintf( 'Đã cập nhật nội dung tối ưu Claude-SEO cho %s #%d thành công.', $post->post_type, $post_id ) );
		}

		// Re-run audit to get the newly elevated score!
		return self::audit_post( $post_id );
	}

	/**
	 * Run Batch AI Auto-Fix on posts/pages missing SEO metadata or needing content uplift
	 *
	 * @param array $post_ids Array of Post/Page IDs
	 * @param Sergio_AI_Gemini_Client $client Gemini client instance
	 * @param Sergio_AI_Logger $logger Logger instance
	 * @param bool $deep_content Whether to rewrite and expand content
	 * @return array
	 */
	public static function batch_auto_fix_seo( $post_ids, $client, $logger, $deep_content = false ) {
		if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
			return new WP_Error( 'invalid_ids', __( 'Danh sách bài viết rỗng.', 'sergio-ai-studio' ) );
		}

		$results       = array();
		$success_count = 0;

		foreach ( $post_ids as $pid ) {
			$pid  = absint( $pid );
			$post = get_post( $pid );
			if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
				continue;
			}

			// Generate SEO suggestions
			$seo_pack = self::ai_optimize_post( $pid, $client, $logger );
			if ( is_wp_error( $seo_pack ) ) {
				$results[] = array(
					'post_id' => $pid,
					'title'   => $post->post_title,
					'status'  => 'error',
					'message' => $seo_pack->get_error_message(),
				);
				continue;
			}

			$rec_kw     = $seo_pack['recommended_focus_kw'];
			$best_title = ! empty( $seo_pack['suggested_titles'][0]['title'] ) ? $seo_pack['suggested_titles'][0]['title'] : $post->post_title;
			$best_desc  = ! empty( $seo_pack['suggested_descriptions'][0]['description'] ) ? $seo_pack['suggested_descriptions'][0]['description'] : '';

			// Save to Rank Math
			Sergio_AI_RankMath_Bridge::update_post_seo_data( $pid, array(
				'focus_keyword' => $rec_kw,
				'seo_title'     => $best_title,
				'description'   => $best_desc,
			) );

			if ( 'draft' === $post->post_status ) {
				wp_update_post( array(
					'ID'        => $pid,
					'post_name' => sanitize_title( $rec_kw ),
				) );
			}

			if ( $deep_content ) {
				self::ai_deep_optimize_content( $pid, $rec_kw, $client, $logger );
			}

			// Re-audit to get new score
			$new_audit = self::audit_post( $pid );
			$new_score = ! is_wp_error( $new_audit ) ? $new_audit['score'] : 0;

			$success_count++;
			$results[] = array(
				'post_id'       => $pid,
				'post_type'     => $post->post_type,
				'title'         => $post->post_title,
				'status'        => 'success',
				'focus_keyword' => $rec_kw,
				'seo_title'     => $best_title,
				'description'   => $best_desc,
				'new_score'     => $new_score,
			);
		}

		return array(
			'total'         => count( $post_ids ),
			'success_count' => $success_count,
			'results'       => $results,
		);
	}

	/**
	 * Helper: Return score badge object
	 *
	 * @param int $score Score 0-100
	 * @return array
	 */
	public static function get_score_badge( $score ) {
		if ( $score >= 80 ) {
			return array(
				'class' => 'score-great',
				'label' => __( 'Tốt (Đạt Chuẩn On-Page)', 'sergio-ai-studio' ),
				'color' => '#10b981',
			);
		} elseif ( $score >= 50 ) {
			return array(
				'class' => 'score-fair',
				'label' => __( 'Cần Cải Thiện', 'sergio-ai-studio' ),
				'color' => '#f59e0b',
			);
		}
		return array(
			'class' => 'score-poor',
			'label' => __( 'Kém (Cần Tối Ưu Gấp)', 'sergio-ai-studio' ),
			'color' => '#f43f5e',
		);
	}
}
