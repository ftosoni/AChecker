<?php
/************************************************************************/
/* AChecker                                                             */
/************************************************************************/
/* Copyright (c) 2008 - 2018                                            */
/* Inclusive Design Institute                                           */
/*                                                                      */
/* This program is free software. You can redistribute it and/or        */
/* modify it under the terms of the GNU General Public License          */
/* as published by the Free Software Foundation.                        */
/************************************************************************/
// $Id:

// Called by js request; forces downloading by sending headers and file content
// @ see checker/js/checker.js
define('AC_INCLUDE_PATH', '../include/');
require (AC_INCLUDE_PATH.'config.inc.php');
require (AC_INCLUDE_PATH.'constants.inc.php');

if (session_id() == "") {
    session_start();
}

if (isset($_GET['id']) && isset($_SESSION['last_export']) && $_SESSION['last_export']['id'] === $_GET['id']) {
	$filename = $_SESSION['last_export']['filename'];
	$mime = $_SESSION['last_export']['mime'];
	$content = $_SESSION['last_export']['content'];
	
	header('Content-Type: ' . $mime);
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	header('Content-Length: ' . strlen($content));
	echo $content;
	exit;
}

$path = $_GET['path'] ?? '';

if ($path != '') {
	$export_dir = realpath(AC_EXPORT_RPT_DIR);
	$real_path = realpath($path);

	// security check: ensure the path is within the export directory
	if ($real_path && strpos($real_path, $export_dir) === 0 && file_exists($real_path)) {
		$filename = basename($real_path);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($real_path));
		readfile($real_path);
		exit;
	}
}

echo "nothing to download";
exit;
?>
