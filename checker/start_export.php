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

// Called by ajax request; main file to generate files
// @ see checker/js/checker.js
ob_start();

// Increase limits for PDF generation
@ini_set('memory_limit', '512M');
@set_time_limit(300);

define('AC_INCLUDE_PATH', '../include/');
include(AC_INCLUDE_PATH.'vitals.inc.php');

// ensure export directory exists (no longer fatal since we use sessions)
if (!is_dir(AC_EXPORT_RPT_DIR)) {
	@mkdir(AC_EXPORT_RPT_DIR, 0775, true);
}

// time constants in seconds
if (!defined('MINUTE')) define('MINUTE', 60);
if (!defined('HOUR'))   define('HOUR', 3600);
if (!defined('DAY'))    define('DAY', 86400);
if (!defined('WEEK'))   define('WEEK', 604800);

if (is_dir(AC_EXPORT_RPT_DIR) && is_writable(AC_EXPORT_RPT_DIR)) {
	if ($handle = @opendir(AC_EXPORT_RPT_DIR)) {
		while (false !== ($file_name = readdir($handle))) { 
			$file_delete_pattern = '/achecker_(.*)/';
			if(preg_match($file_delete_pattern, $file_name, $match)) {
				// delete files older than 1 hour
				if (time() - HOUR > filectime(AC_EXPORT_RPT_DIR.$file_name)) {
					@unlink(AC_EXPORT_RPT_DIR.$file_name);
				}
			}
		}    
		closedir($handle); 
	}
}

// get user choice on file format
$file_format = $_POST['file'] ?? 'pdf';
$problem = $_POST['problem'] ?? 'all';
	
// content to validate	
$uri = $_SESSION['input_form']['uri'] ?? '';
$validate_content = '';
$input_content_type = '';

if ($uri != '') {
	if (isset($_SESSION['input_form']['content']) && $_SESSION['input_form']['content'] != '') {
		$validate_content = $_SESSION['input_form']['content'];
	} else {
		include_once(AC_INCLUDE_PATH . 'classes/Utility.class.php');
		$validate_content = Utility::getURLContents($uri);
	}
	$input_content_type = $uri;
} else if (isset($_SESSION['input_form']['file'])) {
	$validate_content = $_SESSION['input_form']['file'];
	$input_content_type = 'file';
} else if (isset($_SESSION['input_form']['paste'])) {
	$validate_content = $_SESSION['input_form']['paste'];
	$input_content_type = 'paste';
}

// guidelines	
$_gids = $_SESSION['input_form']['gids'] ?? array(DEFAULT_GUIDELINE);

// report mode
$mode = $_SESSION['input_form']['mode'] ?? 'guideline';

// user link id
$user_link_id = $_SESSION['input_form']['user_link_id'] ?? 0;

$html_val = '';
$error_nr_html = -1;
$html_error = '';

// validate html
if (($_SESSION['input_form']['enable_html_validation'] ?? false) == true) {
	include(AC_INCLUDE_PATH. "classes/HTMLValidator.class.php");

	if ($input_content_type == 'file' || $input_content_type == 'paste') {
		if ($file_format == 'html') {
			$htmlValidator = new HTMLValidator("fragment", $validate_content);
			$html_val = $htmlValidator->getValidationRpt();
		} else {
			$htmlValidator = new HTMLValidator("fragment", $validate_content, true);
			$html_val = $htmlValidator->getValidationRptArray();
		}
		
	} else {
		if ($file_format == 'html') {
			$htmlValidator = new HTMLValidator("uri", $input_content_type);
			$html_val = $htmlValidator->getValidationRpt();
		} else {
			$htmlValidator = new HTMLValidator("uri", $uri, true);
			$html_val = $htmlValidator->getValidationRptArray();
		}
	}
	
	if ($htmlValidator->containErrors()) {
		$html_error = $htmlValidator->getErrorMsg();
	}
		
	$error_nr_html = $htmlValidator->getNumOfValidateError();
}

$css_val = '';
$error_nr_css = -1;
$css_error = '';

// validate css
if (($_SESSION['input_form']['enable_css_validation'] ?? false) == true) {
	include(AC_INCLUDE_PATH. "classes/CSSValidator.class.php");

	if ($input_content_type == $uri) {
		$cssValidator = new CSSValidator("uri", $input_content_type, true);
		if ($file_format == 'html') $css_val = $cssValidator->getValidationRpt();
		else $css_val = $cssValidator->getValidationRptArray();
		$error_nr_css = $cssValidator->getNumOfValidateError();
		
		if ($cssValidator->containErrors())
			$css_error = $cssValidator->getErrorMsg();
	} else {
		// css validator is only available at validating url, not at validating a uploaded file or pasted html
		$css_error = _AC("css_validator_unavailable");
	}
}

if ($problem != 'html' && $problem != 'css') {
	include_once(AC_INCLUDE_PATH. 'classes/AccessibilityValidator.class.php');
	include_once(AC_INCLUDE_PATH. 'classes/FileExportRptGuideline.class.php');
	include_once(AC_INCLUDE_PATH. 'classes/FileExportRptLine.class.php');

	$aValidator = new AccessibilityValidator($validate_content, $_gids, $uri);
	$aValidator->validate();
	$errors = $aValidator->getValidationErrorRpt();
}

// get page title
$title = '';
if (preg_match("/<title>(.+)<\/title>/siU", $validate_content, $matches)) $title = $matches[1];

$known = array();
$likely = array();
$potential = array();
$error_nr_known = 0;
$error_nr_likely = 0;
$error_nr_potential = 0;

$export_content = '';
$export_filename = '';
$export_mime = '';

// create file depending on user choice
try {
	if ($file_format == 'pdf') {	
		if ($problem != 'html' && $problem != 'css') {
			$a_rpt = null;
			if ($mode == 'guideline') $a_rpt = new FileExportRptGuideline($errors, $_gids[0], $user_link_id);
			else if ($mode == 'line') $a_rpt = new FileExportRptLine($errors, $user_link_id);
		
			if ($a_rpt) {
				list($known, $likely, $potential) = $a_rpt->generateRpt();
				list($error_nr_known, $error_nr_likely, $error_nr_potential) = $a_rpt->getErrorNr();
			}
		}
		include_once(AC_INCLUDE_PATH. 'classes/exportRpt/exportTFPDF.class.php');
		
		$pdf = new acheckerTFPDF($known, $likely, $potential, $html_val, $css_val, 
			$error_nr_known, $error_nr_likely, $error_nr_potential, $error_nr_html, $error_nr_css, $css_error, $html_error);
		$export_content = $pdf->getPDF($title, $uri, $problem, $mode, $_gids, false);
		$export_filename = 'achecker_report.pdf';
		$export_mime = 'application/pdf';
				
	} else {	
		if ($problem != 'html' && $problem != 'css') {
			$a_rpt = new FileExportRptLine($errors, $user_link_id);
			list($known, $likely, $potential) = $a_rpt->generateRpt();
			list($error_nr_known, $error_nr_likely, $error_nr_potential) = $a_rpt->getErrorNr();
		}
		
		if ($file_format == 'earl') {
			include_once(AC_INCLUDE_PATH. 'classes/exportRpt/exportEARL.class.php');
			$earl = new acheckerEARL($known, $likely, $potential, $html_val, $css_val, 
				$error_nr_known, $error_nr_likely, $error_nr_potential, $error_nr_html, $error_nr_css, $css_error, $html_error);
			$export_content = $earl->getEARL($problem, $input_content_type, $title, $_gids, false);
			$export_filename = 'achecker_report.rdf';
			$export_mime = 'application/rdf+xml';
			
		} else if ($file_format == 'csv') {	
			include_once(AC_INCLUDE_PATH. 'classes/exportRpt/exportCSV.class.php');		
			$csv = new acheckerCSV($known, $likely, $potential, $html_val, $css_val, 
				$error_nr_known, $error_nr_likely, $error_nr_potential, $error_nr_html, $error_nr_css, $css_error, $html_error);
			$export_content = $csv->getCSV($problem, $input_content_type, $title, $_gids, false);
			$export_filename = 'achecker_report.csv';
			$export_mime = 'text/csv';
			
		} else if ($file_format == 'html') {	
			include_once(AC_INCLUDE_PATH. 'classes/exportRpt/exportHTML.class.php');		
			$html_file = new acheckerHTML($known, $likely, $potential, $html_val, $css_val, 
				$error_nr_known, $error_nr_likely, $error_nr_potential, $error_nr_html, $error_nr_css, $css_error, $html_error);
			$export_content = $html_file->getHTMLfile($problem, $_gids, $errors, $user_link_id, false);
			$export_filename = 'achecker_report.html';
			$export_mime = 'text/html';

		} else if ($file_format == 'wikitext') {
			include_once(AC_INCLUDE_PATH. 'classes/exportRpt/exportWikitext.class.php');
			$wikitext = new acheckerWikitext($known, $likely, $potential, $html_val, $css_val, 
				$error_nr_known, $error_nr_likely, $error_nr_potential, $error_nr_html, $error_nr_css, $css_error, $html_error);
			$export_content = $wikitext->getWikitext($problem, $input_content_type, $title, $_gids, false);
			$export_filename = 'achecker_report.txt';
			$export_mime = 'text/plain';
		}
	}

	if (empty($export_content)) {
		throw new Exception("Export content is empty for format: $file_format");
	}

	$export_id = md5(uniqid(rand(), true));
	$_SESSION['last_export'] = array(
		'id' => $export_id,
		'content' => $export_content,
		'filename' => $export_filename,
		'mime' => $export_mime
	);

	ob_clean();
	echo "session:" . $export_id;
	exit();

} catch (Throwable $e) {
	error_log("AChecker Error: $file_format export failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
	header("HTTP/1.1 500 Internal Server Error");
	echo "Export Error ($file_format): " . $e->getMessage();
	exit;
}
?>
