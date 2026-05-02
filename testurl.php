<?php
define('AC_INCLUDE_PATH', 'C:\xampp\htdocs\AChecker\include\\');
include_once(AC_INCLUDE_PATH. 'classes/Utility.class.php');
$t = microtime(true);
$res = Utility::getURLContents('https://it.wikipedia.org/wiki/Utente:Super_nabla');
echo "Size: " . strlen($res) . "\n";
echo (microtime(true) - $t) . " seconds\n";
?>
