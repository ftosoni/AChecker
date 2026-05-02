<?php
mysqli_report(MYSQLI_REPORT_OFF);
$db1 = mysqli_connect('127.0.0.1', 'root', '');
echo "127.0.0.1: " . ($db1 ? "Success\n" : mysqli_connect_error() . "\n");

$db2 = mysqli_connect('localhost', 'root', '');
echo "localhost: " . ($db2 ? "Success\n" : mysqli_connect_error() . "\n");
?>
