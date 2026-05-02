<?php
define('AC_INCLUDE_PATH', 'C:\xampp\htdocs\AChecker\include\\');
include_once(AC_INCLUDE_PATH. "lib/simple_html_dom.php");
include_once(AC_INCLUDE_PATH. "classes/Utility.class.php");

$res = Utility::getURLContents('https://it.wikipedia.org/wiki/Utente:Super_nabla');

$t = microtime(true);
$dom = str_get_dom($res);
echo "Parse: " . (microtime(true) - $t) . " seconds\n";
?>
