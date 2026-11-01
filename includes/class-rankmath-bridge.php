<?php
/**
 * Rank Math SEO Bridge (Free & PRO Compatible)
 *
 * Interfaces directly with Rank Math SEO metadata storage and schema.
 *
 * @package Sergio_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sergio_AI_RankMath_Bridge {

	/**
	 * Check if Rank Math is active
	 */
	public static function is_active() {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
	}

	/**
	 * Check if Rank Math PRO is active
	 */
	public static function is_pro() {
		return defined( 'RANK_MATH_PRO_VERSION' ) || class_exists( 'RankMathPro' );
	}

	/**
	 * Get SEO data for a post
	 *
	 * @param int $post_id Post ID
	 * @return array
	 */
	public static function get_post_seo_data( $post_id ) {
		$post_id = absint( $post_id );
		if ( empty( $post_id ) ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$focus_keyword = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
		$seo_title     = get_post_meta( $post_id, 'rank_math_title', true );
		$seo_desc      = get_post_meta( $post_id, 'rank_math_description', true );
		$canonical     = get_post_meta( $post_id, 'rank_math_canonical_url', true );
		$robots        = get_post_meta( $post_id, 'rank_math_robots', true );
		$rm_score      = get_post_meta( $post_id, 'rank_math_seo_score', true );
		$studio_score  = get_post_meta( $post_id, '_sergio_ai_seo_score', true );
		$display_score = ! empty( $studio_score ) ? absint( $studio_score ) : ( ! empty( $rm_score ) ? absint( $rm_score ) : 0 );

		return array(
			'post_id'       => $post_id,
			'post_title'    => $post->post_title,
			'post_name'     => $post->post_name,
			'post_status'   => $post->post_status,
			'focus_keyword' => ! empty( $focus_keyword ) ? $focus_keyword : '',
			'seo_title'     => ! empty( $seo_title ) ? $seo_title : $post->post_title,
			'description'   => ! empty( $seo_desc ) ? $seo_desc : '',
			'canonical_url' => ! empty( $canonical ) ? $canonical : get_permalink( $post_id ),
			'robots'        => ! empty( $robots ) ? (array) $robots : array( 'index', 'follow' ),
			'rm_score'      => $display_score,
			'edit_url'      => function_exists( 'get_edit_post_link' ) ? get_edit_post_link( $post_id, 'raw' ) : admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
			'view_url'      => get_permalink( $post_id ),
		);
	}

	/**
	 * Save SEO Metas for a post
	 *
	 * @param int   $post_id Post ID
	 * @param array $metas   Array containing focus_keyword, seo_title, description, canonical_url
	 */
	public static function save_metas( $post_id, $metas ) {
		if ( empty( $post_id ) ) {
			return;
		}

		$focus_kw = isset( $metas['focus_keyword'] ) ? sanitize_text_field( $metas['focus_keyword'] ) : '';
		$title    = isset( $metas['seo_title'] ) ? sanitize_text_field( $metas['seo_title'] ) : '';
		$desc     = isset( $metas['description'] ) ? sanitize_textarea_field( $metas['description'] ) : '';
		$url      = isset( $metas['canonical_url'] ) && ! empty( $metas['canonical_url'] ) ? esc_url_raw( $metas['canonical_url'] ) : '';

		$post     = get_post( $post_id );
		$is_draft = $post && in_array( $post->post_status, array( 'draft', 'auto-draft', 'pending' ), true );

		// Standard Rank Math Meta Keys (Used by both Free & PRO)
		if ( ! empty( $focus_kw ) ) {
			update_post_meta( $post_id, 'rank_math_focus_keyword', $focus_kw );
		}

		if ( ! empty( $title ) ) {
			update_post_meta( $post_id, 'rank_math_title', $title );
		}

		if ( ! empty( $desc ) ) {
			update_post_meta( $post_id, 'rank_math_description', $desc );
		}

		update_post_meta( $post_id, 'rank_math_robots', array( 'index', 'follow' ) );

		// Only store canonical URL if explicitly provided or post is already published (avoid premature ?p=ID)
		if ( ! empty( $url ) ) {
			update_post_meta( $post_id, 'rank_math_canonical_url', $url );
		} elseif ( ! $is_draft ) {
			update_post_meta( $post_id, 'rank_math_canonical_url', get_permalink( $post_id ) );
		}

		// Social Meta Tags (OpenGraph Facebook & Twitter for Rank Math)
		if ( ! empty( $title ) ) {
			update_post_meta( $post_id, 'rank_math_facebook_title', $title );
			update_post_meta( $post_id, 'rank_math_twitter_title', $title );
		}
		if ( ! empty( $desc ) ) {
			update_post_meta( $post_id, 'rank_math_facebook_description', $desc );
			update_post_meta( $post_id, 'rank_math_twitter_description', $desc );
		}
		update_post_meta( $post_id, 'rank_math_twitter_use_facebook', 'on' );

		// Fallback for native/Yoast/AIOSEO if Rank Math is not the primary plugin
		update_post_meta( $post_id, '_sergio_ai_seo_focus_kw', $focus_kw );
		update_post_meta( $post_id, '_sergio_ai_seo_title', $title );
		update_post_meta( $post_id, '_sergio_ai_seo_desc', $desc );

		// If Rank Math PRO Schema is active, configure standard Article schema
		if ( self::is_pro() ) {
			update_post_meta( $post_id, 'rank_math_rich_snippet', 'article' );
			update_post_meta( $post_id, 'rank_math_snippet_article_type', 'TechArticle' );
		}
	}

	/**
	 * Update specific SEO fields and recalculate score
	 *
	 * @param int   $post_id Post ID
	 * @param array $data    Array of fields to update
	 * @return bool
	 */
	public static function update_post_seo_data( $post_id, $data ) {
		$post_id = absint( $post_id );
		if ( empty( $post_id ) ) {
			return false;
		}

		if ( isset( $data['focus_keyword'] ) ) {
			$kw = sanitize_text_field( $data['focus_keyword'] );
			update_post_meta( $post_id, 'rank_math_focus_keyword', $kw );
			update_post_meta( $post_id, '_sergio_ai_seo_focus_kw', $kw );
		}

		if ( isset( $data['seo_title'] ) ) {
			$title = sanitize_text_field( $data['seo_title'] );
			update_post_meta( $post_id, 'rank_math_title', $title );
			update_post_meta( $post_id, '_sergio_ai_seo_title', $title );
		}

		if ( isset( $data['description'] ) ) {
			$desc = sanitize_textarea_field( $data['description'] );
			update_post_meta( $post_id, 'rank_math_description', $desc );
			update_post_meta( $post_id, '_sergio_ai_seo_desc', $desc );
		}

		if ( isset( $data['score'] ) ) {
			$score = min( 100, max( 0, absint( $data['score'] ) ) );
			update_post_meta( $post_id, '_sergio_ai_seo_score', $score );
		}

		return true;
	}
}
