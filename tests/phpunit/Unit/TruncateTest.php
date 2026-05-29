<?php

namespace NextTitleTrimCount\Tests\Unit;

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

class TruncateTest extends TestCase {

	public function set_up(): void {
		parent::set_up();
		// プラグイン本体（WP 関数はモック済みのタイミングで require する）
		require_once dirname( __DIR__, 3 ) . '/next-title-trim-count.php';
	}

	/**
	 * 文字数以内の文字列はそのまま返る。
	 */
	public function test_under_limit_returns_unchanged(): void {
		Functions\when( 'wp_strip_all_tags' )->returnArg();
		$this->assertSame( 'Hello', next_title_trim_count_truncate( 'Hello', 10 ) );
	}

	/**
	 * 文字数超過時に「…」でトリムされる。
	 */
	public function test_over_limit_is_trimmed(): void {
		Functions\when( 'wp_strip_all_tags' )->returnArg();
		$result = next_title_trim_count_truncate( 'あいうえおかきくけこ', 5 );
		$this->assertSame( 'あいうえお…', $result );
	}

	/**
	 * 既存 style 属性の先頭に CSS が追記される。
	 */
	public function test_inject_style_prepends_to_existing(): void {
		Functions\when( 'esc_attr' )->returnArg();
		$attrs  = ' class="wp-block" style="color:red;"';
		$result = next_title_trim_count_inject_style( $attrs, 'overflow:hidden;' );
		$this->assertStringContainsString( 'style="overflow:hidden;color:red;"', $result );
	}

	/**
	 * style 属性が無い場合は新規追加される。
	 */
	public function test_inject_style_adds_new_attribute(): void {
		Functions\when( 'esc_attr' )->returnArg();
		$attrs  = ' class="wp-block"';
		$result = next_title_trim_count_inject_style( $attrs, 'overflow:hidden;' );
		$this->assertStringContainsString( 'style="overflow:hidden;"', $result );
	}
}
