<?php

namespace NextTitleTrimCount\Tests\Integration;

use WP_UnitTestCase;

class PluginTest extends WP_UnitTestCase {

	/**
	 * プラグインが有効化されている。
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
	 * 文字数制限なし（nextCharLimit=0）のブロックはコンテンツが変わらない。
	 */
	public function test_no_limit_returns_unchanged(): void {
		$block_content = '<h2 class="wp-block-heading">あいうえおかきくけこ</h2>';
		$block         = array(
			'blockName' => 'core/heading',
			'attrs'     => array( 'nextCharLimit' => 0 ),
		);
		$result = apply_filters( 'render_block', $block_content, $block );
		$this->assertSame( $block_content, $result );
	}

	/**
	 * nextCharLimit が設定されているとき見出しがトリムされる。
	 */
	public function test_heading_is_trimmed(): void {
		$block_content = '<h2 class="wp-block-heading">あいうえおかきくけこ</h2>';
		$block         = array(
			'blockName' => 'core/heading',
			'attrs'     => array( 'nextCharLimit' => 5 ),
		);
		$result = apply_filters( 'render_block', $block_content, $block );
		$this->assertStringContainsString( 'あいうえお…', $result );
		$this->assertStringNotContainsString( 'かきくけこ', $result );
	}
}
