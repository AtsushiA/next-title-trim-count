<?php
/**
 * Plugin Name: Next Title TrimCount
 * Description: タイトル・見出しブロックに文字数制限を追加するプラグイン。制限を超えた部分は「…」で省略表示されます。
 * Version:     1.1.0
 * Author:		NExT-Season
 * Author URI:	https://next-season.net
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * Text Domain: next-title-trim-count
 */

defined( 'ABSPATH' ) || exit;

/**
 * エディター用スクリプトの読み込み
 */
add_action( 'enqueue_block_editor_assets', function () {
	$asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = include $asset_file;

	wp_enqueue_script(
		'next-title-trim-count-editor',
		plugin_dir_url( __FILE__ ) . 'build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);
} );

/**
 * フロントエンドでタイトル / 見出しブロックのテキストをトリムする
 */
add_filter( 'render_block', function ( $block_content, $block ) {
	$supported = array( 'core/heading', 'core/post-title' );

	if ( ! in_array( $block['blockName'], $supported, true ) ) {
		return $block_content;
	}

	$char_limit = isset( $block['attrs']['nextCharLimit'] ) ? (int) $block['attrs']['nextCharLimit'] : 0;

	if ( $char_limit <= 0 ) {
		return $block_content;
	}

	return preg_replace_callback(
		'/(<h[1-6][^>]*>)(.*?)(<\/h[1-6]>)/si',
		function ( $matches ) use ( $char_limit ) {
			$open_tag  = $matches[1];
			$inner     = $matches[2];
			$close_tag = $matches[3];

			return $open_tag . next_title_trim_count_truncate( $inner, $char_limit ) . $close_tag;
		},
		$block_content
	);
}, 10, 2 );

/**
 * HTML 構造を保持しながら、テキスト文字数が上限を超えた場合に「…」でトリムする。
 *
 * @param string $html  対象の HTML 文字列（ブロックの内側）
 * @param int    $limit 最大文字数
 * @return string
 */
function next_title_trim_count_truncate( $html, $limit ) {
	// プレーンテキストの文字数が制限内なら何もしない
	$plain_text = wp_strip_all_tags( $html );
	if ( mb_strlen( $plain_text, 'UTF-8' ) <= $limit ) {
		return $html;
	}

	$remaining = $limit;
	$result    = '';
	$truncated = false;
	$tag_stack = array();

	// HTML をタグとテキストノードに分割
	$parts = preg_split( '/(<[^>]+>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE );

	foreach ( $parts as $part ) {
		// HTML タグの処理
		if ( preg_match( '/^<(\/?[a-zA-Z][a-zA-Z0-9]*)[^>]*\/?>$/u', $part, $tag_match ) ) {
			$tag_name   = strtolower( $tag_match[1] );
			$is_closing = ( substr( $tag_name, 0, 1 ) === '/' );
			$is_void    = preg_match( '/<[^>]+\/>$/u', $part ); // 自己閉じタグ

			if ( ! $truncated ) {
				$result .= $part;
				if ( $is_closing ) {
					array_pop( $tag_stack );
				} elseif ( ! $is_void ) {
					$tag_stack[] = ltrim( $tag_name, '/' );
				}
			} elseif ( $is_closing && ! empty( $tag_stack ) ) {
				// トリム後は開いているタグの閉じタグのみ追加
				$result .= $part;
				array_pop( $tag_stack );
			}

			continue;
		}

		// テキストノードの処理
		if ( $truncated || $remaining <= 0 ) {
			$truncated = true;
			continue;
		}

		$len = mb_strlen( $part, 'UTF-8' );

		if ( $len > $remaining ) {
			$result   .= mb_substr( $part, 0, $remaining, 'UTF-8' ) . '…';
			$remaining = 0;
			$truncated = true;
		} else {
			$result   .= $part;
			$remaining -= $len;
		}
	}

	// 開いたままのタグを閉じる
	foreach ( array_reverse( $tag_stack ) as $open_tag ) {
		$result .= '</' . esc_attr( $open_tag ) . '>';
	}

	return $result;
}
