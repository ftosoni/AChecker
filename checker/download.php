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

	$path = $_GET['path'];
	$pattern_csv = '/achecker_(.*?)\.csv/';
	$pattern_rdf = '/achecker_(.*?)\.rdf/';
	$pattern_pdf = '/achecker_(.*?)\.pdf/';
	$pattern_html = '/achecker_(.*?)\.html/';
	$pattern_txt = '/achecker_(.*?)\.txt/';
	if (preg_match($pattern_csv, $path, $match)) {
		$filename = $match[0];
	} else if (preg_match($pattern_rdf, $path, $match)) {
		$filename = $match[0];
	} else if (preg_match($pattern_pdf, $path, $match)) {
		$filename = $match[0];
	} else if (preg_match($pattern_html, $path, $match)) {
		$filename = $match[0];
	} else if (preg_match($pattern_txt, $path, $match)) {
		$filename = $match[0];
	}

	$path = str_replace('\\', '/', trim($_GET['path']));
	$export_dir = str_replace('\\', '/', AC_EXPORT_RPT_DIR);

	if(strstr($path, $export_dir)){
        header('Content-Type: application/force-download');
        header('Content-transfer-encoding: binary');
        header('Content-Disposition: attachment; filename='.$filename);
        readfile($path);
	} else {
	    echo "nothing to download";
	}
?>
