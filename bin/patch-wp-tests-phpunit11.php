<?php
/**
 * WordPress test library still calls PHPUnit\\Util\\Test::parseTestMethodAnnotations(),
 * removed in PHPUnit 11. Patch expectDeprecated() to no-op annotations when missing.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

$plugin_dir = dirname(__DIR__);
$tests_dir  = getenv('WP_TESTS_DIR');
if (! is_string($tests_dir) || $tests_dir === '') {
	$tests_dir = $plugin_dir . '/tests/.wordpress-tests-lib';
}

$file = $tests_dir . '/includes/abstract-testcase.php';
if (! is_readable($file)) {
	fwrite(STDERR, "patch-wp-tests-phpunit11: abstract-testcase.php not found at {$file}\n");
	exit(0);
}

$contents = file_get_contents($file);
if ($contents === false) {
	fwrite(STDERR, "patch-wp-tests-phpunit11: could not read {$file}\n");
	exit(1);
}

$old_block = "\t\t} else {\n\t\t\t// PHPUnit >= 9.5.0.\n\t\t\t\$annotations = \\PHPUnit\\Util\\Test::parseTestMethodAnnotations(\n\t\t\t\tstatic::class,\n\t\t\t\t\$this->getName( false )\n\t\t\t);\n\t\t}";

$new_block = "\t\t} elseif ( method_exists( \\PHPUnit\\Util\\Test::class, 'parseTestMethodAnnotations' ) ) {\n\t\t\t// PHPUnit 9.5–10.\n\t\t\t\$annotations = \\PHPUnit\\Util\\Test::parseTestMethodAnnotations(\n\t\t\t\tstatic::class,\n\t\t\t\t\$this->getName( false )\n\t\t\t);\n\t\t} else {\n\t\t\t// PHPUnit 11+.\n\t\t\t\$annotations = array( 'class' => array(), 'method' => array() );\n\t\t}";

if (str_contains($contents, $new_block)) {
	exit(0);
}

if (! str_contains($contents, $old_block)) {
	if (str_contains($contents, 'parseTestMethodAnnotations')) {
		fwrite(STDERR, "patch-wp-tests-phpunit11: unexpected abstract-testcase.php shape; manual check needed.\n");
		exit(1);
	}
	exit(0);
}

$patched = str_replace($old_block, $new_block, $contents, $count);
if ($count !== 1) {
	fwrite(STDERR, "patch-wp-tests-phpunit11: replace count {$count}\n");
	exit(1);
}

if (file_put_contents($file, $patched) === false) {
	fwrite(STDERR, "patch-wp-tests-phpunit11: could not write {$file}\n");
	exit(1);
}

fwrite(STDOUT, "patch-wp-tests-phpunit11: patched expectDeprecated() in abstract-testcase.php\n");
