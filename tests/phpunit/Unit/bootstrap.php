<?php
/**
 * PHPUnit bootstrap for Unit tests. WordPress is not loaded.
 */

require_once dirname( __DIR__, 2 ) . '/../vendor/autoload.php';

// `defined('ABSPATH') || exit;` をパスさせるために定義する。
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 3 ) . '/' );
}
