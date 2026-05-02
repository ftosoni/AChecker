<?php
// Mock out the vitals
define('AC_INCLUDE_PATH', 'C:\xampp\htdocs\AChecker\include\\');
error_reporting(E_ALL);

include_once('C:\xampp\htdocs\AChecker\include\vitals.inc.php');
include_once(AC_INCLUDE_PATH. 'classes/Utility.class.php');
include_once(AC_INCLUDE_PATH. "classes/AccessibilityValidator.class.php");

$t = microtime(true);
$res = Utility::getURLContents('https://it.wikipedia.org/wiki/Utente:Super_nabla');
echo "Download: " . (microtime(true) - $t) . " seconds\n";

$t = microtime(true);
$_gids = array(1); // just a dummy guideline id or something
$aValidator = new AccessibilityValidator($res, $_gids, 'https://it.wikipedia.org/wiki/Utente:Super_nabla');
$aValidator->validate();
echo "Validation: " . (microtime(true) - $t) . " seconds\n";
?>
