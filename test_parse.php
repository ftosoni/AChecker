<?php
define('AC_INCLUDE_PATH', 'C:\xampp\htdocs\AChecker\include\\');
include_once(AC_INCLUDE_PATH. "lib/simple_html_dom.php");

$res = file_get_contents('test_html.txt');

$t = microtime(true);
$dom = str_get_dom($res);
echo "Parse: " . (microtime(true) - $t) . " seconds\n";
?>
