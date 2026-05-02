<?php
define('AC_INCLUDE_PATH', 'C:\xampp\htdocs\AChecker\include\\');
include_once('C:\xampp\htdocs\AChecker\include\vitals.inc.php');
include_once(AC_INCLUDE_PATH. 'classes/Utility.class.php');
include_once(AC_INCLUDE_PATH. "classes/AccessibilityValidator.class.php");

$res = "<html><body><h1>Test</h1></body></html>";
$_gids = array(1); 
$t = microtime(true);
$aValidator = new AccessibilityValidator($res, $_gids, 'http://localhost');
$aValidator->validate();
echo "Validation small HTML: " . (microtime(true) - $t) . " seconds\n";
?>
