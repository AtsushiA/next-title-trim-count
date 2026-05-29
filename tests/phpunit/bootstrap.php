<?php
/**
 * PHPUnit bootstrap for Integration tests (runs inside wp-env tests container).
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

require getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __DIR__, 2 ) . '/next-title-trim-count.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';
