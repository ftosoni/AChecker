<?php
define('AC_INCLUDE_PATH', 'C:/xampp/htdocs/AChecker/include/');
include(AC_INCLUDE_PATH.'vitals.inc.php');
include_once(AC_INCLUDE_PATH. 'classes/Utility.class.php');

$uri = 'https://it.wikipedia.org/wiki/Utente:Super_nabla';
$content = Utility::getURLContents($uri);

echo "Length: " . strlen($content) . "\n";
if ($content) {
    echo "First 100 chars: " . substr($content, 0, 100) . "\n";
} else {
    echo "FAIL\n";
}
?>
