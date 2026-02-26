<?php
/**
 * Plugin Name: Next Title TrimCount
 * Description: タイトル・見出しブロックに文字数制限を追加するプラグイン。制限を超えた部分は「…」で省略表示されます。
 * Version:     1.3.0
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
	$line_clamp = isset( $block['attrs']['nextLineClamp'] ) ? (int) $block['attrs']['nextLineClamp'] : 0;

	// 行数制限（line-clamp）が設定されている場合は CSS で処理（文字数制限より優先）
	if ( $line_clamp > 0 ) {
		return next_title_trim_count_apply_line_clamp( $block_content, $line_clamp );
	}

	// 文字数制限
	if ( $char_limit > 0 ) {
		return preg_replace_callback(
			'/(<h[1-6][^>]*>)(.*?)(<\/h[1-6]>)/si',
			function ( $matches ) use ( $char_limit ) {
				return $matches[1] . next_title_trim_count_truncate( $matches[2], $char_limit ) . $matches[3];
			},
			$block_content
		);
	}

	return $block_content;
}, 10, 2 );

/**
 * h タグに CSS line-clamp 用のインラインスタイルを付与する。
 *
 * ベンダープレフィックス付きと標準プロパティの両方を設定する。
 * 内側に <a> がある場合（isLink 設定時など）は <a> に直接適用する。
 * テーマが .wp-block-post-title a に display:inline-block 等を付与していても
 * インラインスタイルで上書きされるため line-clamp が正常に動作する。
 *
 * @param string $block_content レンダリング済みブロック HTML
 * @param int    $line_clamp    最大行数
 * @return string
 */
function next_title_trim_count_apply_line_clamp( $block_content, $line_clamp ) {
	$line_clamp = (int) $line_clamp;

	$css = sprintf(
		'overflow:hidden;display:-webkit-box;-webkit-line-clamp:%1$d;-webkit-box-orient:vertical;line-clamp:%1$d;',
		$line_clamp
	);

	return preg_replace_callback(
		'/(<h[1-6])([^>]*)(>)(.*?)(<\/h[1-6]>)/si',
		function ( $matches ) use ( $css ) {
			$tag       = $matches[1]; // 例: <h2
			$attrs     = $matches[2]; // 既存の属性文字列
			$close     = $matches[3]; // >
			$inner     = $matches[4]; // 内側の HTML
			$close_tag = $matches[5]; // 例: </h2>

			if ( preg_match( '/<a\b/i', $inner ) ) {
				// <a> がある場合: <h*> に overflow:hidden のみ付与し、
				// <a> に line-clamp CSS を適用して display:inline-block を上書きする
				$attrs = next_title_trim_count_inject_style( $attrs, 'overflow:hidden;' );
				$inner = preg_replace_callback(
					'/(<a)([^>]*)(>)/i',
					function ( $a ) use ( $css ) {
						return $a[1] . next_title_trim_count_inject_style( $a[2], $css ) . $a[3];
					},
					$inner
				);
			} else {
				// <a> がない場合: <h*> に直接適用
				$attrs = next_title_trim_count_inject_style( $attrs, $css );
			}

			return $tag . $attrs . $close . $inner . $close_tag;
		},
		$block_content
	);
}

/**
 * 属性文字列にインラインスタイルを追加または既存スタイルの先頭に追記する。
 *
 * @param string $attrs 既存の属性文字列
 * @param string $css   追加する CSS 文字列
 * @return string
 */
function next_title_trim_count_inject_style( $attrs, $css ) {
	if ( preg_match( '/\bstyle\s*=\s*"/i', $attrs ) ) {
		return preg_replace_callback(
			'/(\bstyle\s*=\s*")([^"]*")/i',
			function ( $m ) use ( $css ) {
				return $m[1] . $css . $m[2];
			},
			$attrs
		);
	}
	return $attrs . ' style="' . esc_attr( $css ) . '"';
}

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
