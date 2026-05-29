<?php

namespace NextTitleTrimCount\Tests\Integration;

use WP_UnitTestCase;

class PluginTest extends WP_UnitTestCase {

	/**
	 * プラグイン関数が定義されている。
	 */
	public function test_plugin_is_loaded(): void {
		$this->assertTrue( function_exists( 'next_title_trim_count_truncate' ) );
		$this->assertTrue( function_exists( 'next_title_trim_count_inject_style' ) );
		$this->assertTrue( function_exists( 'next_title_trim_count_apply_line_clamp' ) );
	}

	/**
	 * render_block フィルターが登録されている。
	 */
	public function test_render_block_filter_is_registered(): void {
		$this->assertGreaterThan( 0, has_filter( 'render_block' ) );
	}

	/**
	 * 制限内のテキストはそのまま返る（実 WP 環境で wp_strip_all_tags が使用される）。
	 */
	public function test_truncate_no_trim_needed(): void {
		$result = next_title_trim_count_truncate( '<strong>短い</strong>', 10 );
		$this->assertSame( '<strong>短い</strong>', $result );
	}

	/**
	 * 制限超過で「…」トリムされる（実 WP の wp_strip_all_tags 使用）。
	 */
	public function test_truncate_trims_with_ellipsis(): void {
		$result = next_title_trim_count_truncate( 'あいうえおかきくけこ', 5 );
		$this->assertSame( 'あいうえお…', $result );
	}

	/**
	 * line-clamp スタイルが見出し要素に付与される（実 WP の esc_attr 使用）。
	 */
	public function test_apply_line_clamp_adds_style(): void {
		$html   = '<h2 class="wp-block-heading">テスト</h2>';
		$result = next_title_trim_count_apply_line_clamp( $html, 3 );
		$this->assertStringContainsString( '-webkit-line-clamp:3', $result );
		$this->assertStringContainsString( 'テスト', $result );
	}

	/**
	 * line-clamp: <a> がある場合は <a> にスタイルが付与される。
	 */
	public function test_apply_line_clamp_on_linked_title(): void {
		$html   = '<h2 class="wp-block-post-title"><a href="#">テスト</a></h2>';
		$result = next_title_trim_count_apply_line_clamp( $html, 2 );
		$this->assertStringContainsString( 'overflow:hidden', $result );
		$this->assertStringContainsString( '-webkit-line-clamp:2', $result );
	}
}
