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
// $Id$

/*
 * This is the web service interface to check accessibility on a given URI
 * Expected parameters:
 * id: to identify the user. must be given
 * uri: The URL of the document to validate. must be given
 * guide: The guidelines to validate against. 
 *        can be multiple guides, separated by comma (,)
 * output: html or rest
 * offset: The line offset on the html output from uri where the validation starts.
 */

define('AC_INCLUDE_PATH', 'include/');

include(AC_INCLUDE_PATH.'vitals.inc.php');
@set_time_limit(300);
include_once(AC_INCLUDE_PATH. 'classes/HTMLRpt.class.php');
include_once(AC_INCLUDE_PATH. 'classes/Utility.class.php');
include_once(AC_INCLUDE_PATH. 'classes/DAO/UsersDAO.class.php');
include_once(AC_INCLUDE_PATH. 'classes/DAO/GuidelinesDAO.class.php');
include_once(AC_INCLUDE_PATH. 'classes/DAO/UserLinksDAO.class.php');
include_once(AC_INCLUDE_PATH. 'classes/AccessibilityValidator.class.php');
include_once(AC_INCLUDE_PATH. 'classes/HTMLWebServiceOutput.class.php');
include_once(AC_INCLUDE_PATH. 'classes/RESTWebServiceOutput.class.php');

$uri = trim(urldecode($_REQUEST['uri'] ?? ''));
$web_service_id = trim($_REQUEST['id'] ?? '');
$guide = trim(strtolower($_REQUEST['guide'] ?? ''));
$output = trim(strtolower($_REQUEST['output'] ?? ''));
$offset = intval($_REQUEST['offset'] ?? 0);
$html = intval($_REQUEST['html'] ?? 0);
$css = intval($_REQUEST['css'] ?? 0);
$show_source = intval($_REQUEST['show_source'] ?? 0);
$mode = trim(strtolower($_REQUEST['mode'] ?? ''));
if ($mode == '') $mode = 'guideline';

$report = trim(strtolower($_REQUEST['report'] ?? ''));
if ($report == '' || !in_array($report, array('all', 'known', 'likely', 'potential', 'html', 'css'))) {
    $report = 'all';
}

// initialize defaults for the ones not set or not set right but with default values
if ($output != '' && !in_array($output, array('html', 'rest', 'pdf', 'csv', 'earl', 'wikitext'))) {
    $errors[] = 'Unaccepted output format: ' . htmlspecialchars($output);
}
if ($output == '') {
    $output = DEFAULT_WEB_SERVICE_OUTPUT;
}
// end of initialization

// Strict parameter validation
$allowed_params = array('uri', 'id', 'guide', 'output', 'offset', 'html', 'css', 'show_source', 'mode', 'report');
foreach ($_GET as $key => $value) {
    if (!in_array($key, $allowed_params)) {
        $errors[] = 'Unaccepted parameter: ' . htmlspecialchars($key);
    }
}

// validate parameters
if ($uri == '')
{
	$errors[] = 'AC_ERROR_EMPTY_URI';
}
else
{
	if (Utility::getValidURI($uri) === false) $errors[] = 'AC_ERROR_INVALID_URI';
}


if ($web_service_id == '')
{
	$user_id = 0;
}
else
{ // validate web service id
	$usersDAO = new UsersDAO();
	$user_row = $usersDAO->getUserByWebServiceID($web_service_id);

	if (!$user_row) $errors[] = 'AC_ERROR_INVALID_WEB_SERVICE_ID';
	
	if ($user_row) $user_id = $user_row['user_id'];
}

// return errors
if (is_array($errors))
{
	if ($output == 'rest') {
		header('Content-type: text/xml');
		echo RESTWebServiceOutput::generateErrorRpt($errors);
	} else {
		echo HTMLRpt::generateErrorRpt($errors);
	}
	
	exit;
}

// generate guidelines
$guides = explode(',',$guide);

$guidelinesDAO = new GuidelinesDAO();
foreach ($guides as $abbr)
{
	if ($abbr == '') continue;

	$row = $guidelinesDAO->getEnabledGuidelinesByAbbr($abbr);

	if ($row[0]['guideline_id'] <> '') $gids[] = $row[0]['guideline_id'];
}

// set to default guideline if no input guidelines
if (!is_array($gids)) $gids[] = DEFAULT_GUIDELINE;

// retrieve user link ID
$userLinksDAO = new UserLinksDAO();
$user_link_id = $userLinksDAO->getUserLinkID($user_id, $uri, $gids);

// set new session id
$userLinksDAO->setLastSessionID($user_link_id, Utility::getSessionID());

// validating uri content
$validate_content = Utility::getURLContents($uri);

if (isset($validate_content))
{
	$aValidator = new AccessibilityValidator($validate_content, $gids, $uri);
	$aValidator->setLineOffset($offset);
	$aValidator->validate();
	$errors = $aValidator->getValidationErrorRpt();

	// HTML validation
	$error_nr_html = -1; $html_val = array(); $html_error = '';
	if ($html) {
		include_once(AC_INCLUDE_PATH.'classes/HTMLValidator.class.php');
		$htmlValidator = new HTMLValidator($validate_content);
		$htmlValidator->validate();
		$html_val = $htmlValidator->getValidationErrorRpt();
		$error_nr_html = count($html_val);
	}

	// CSS validation
	$error_nr_css = -1; $css_val = array(); $css_error = '';
	if ($css) {
		include_once(AC_INCLUDE_PATH.'classes/CSSValidator.class.php');
		$cssValidator = new CSSValidator($uri);
		$cssValidator->validate();
		$css_val = $cssValidator->getValidationErrorRpt();
		$error_nr_css = count($css_val);
	}

	// save errors into user_decisions 
//	$userDecisionsDAO = new UserDecisionsDAO();
//	$userDecisionsDAO->saveErrors($user_link_id, $errors);
	
	if ($output == 'html')
	{ // generate html output
		$htmlWebServiceOutput = new HTMLWebServiceOutput($aValidator, $user_link_id, $gids, $mode);
		echo $htmlWebServiceOutput->getWebServiceOutput();
	}
	else if ($output == 'rest')
	{ // generate rest output
		$restWebServiceOutput = new RESTWebServiceOutput($errors, $user_link_id, $gids);
		header('Content-type: text/xml');
		echo $restWebServiceOutput->getWebServiceOutput();
	}
	else if (in_array($output, array('pdf', 'csv', 'earl', 'wikitext')))
	{
		if ($mode == 'guideline') {
			include_once(AC_INCLUDE_PATH. 'classes/FileExportRptGuideline.class.php');
			$known = array(); $likely = array(); $potential = array();
			$error_nr_known = 0; $error_nr_likely = 0; $error_nr_potential = 0;
			
			foreach ($gids as $gid) {
				$a_rpt = new FileExportRptGuideline($errors, $gid, $user_link_id);
				if ($show_source) $a_rpt->setShowSource('true');
				list($g_known, $g_likely, $g_potential) = $a_rpt->generateRpt();
				list($g_nr_known, $g_nr_likely, $g_nr_potential) = $a_rpt->getErrorNr();
				
				$known = array_merge($known, $g_known);
				$likely = array_merge($likely, $g_likely);
				$potential = array_merge($potential, $g_potential);
				$error_nr_known += $g_nr_known;
				$error_nr_likely += $g_nr_likely;
				$error_nr_potential += $g_nr_potential;
			}
		} else {
			include_once(AC_INCLUDE_PATH. 'classes/FileExportRptLine.class.php');
			$a_rpt = new FileExportRptLine($errors, $user_link_id);
			if ($show_source) $a_rpt->setShowSource('true');
			list($known, $likely, $potential) = $a_rpt->generateRpt();
			list($error_nr_known, $error_nr_likely, $error_nr_potential) = $a_rpt->getErrorNr();
		}

		// Get page title for the report
		$title = '';
		if (preg_match("/<title>(.+)<\/title>/siU", $validate_content, $matches)) $title = $matches[1];

		// Mock empty validation results for HTML/CSS as they are not currently supported in web service


		if ($output == 'pdf') {
			include_once(AC_INCLUDE_PATH. 'classes/exportRpt/exportTFPDF.class.php');
			$pdf = new acheckerTFPDF($known, $likely, $potential, $html_val, $css_val, 
				$error_nr_known, $error_nr_likely, $error_nr_potential, $error_nr_html, $error_nr_css, $css_error, $html_error);
			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment; filename="achecker_report.pdf"');
			echo $pdf->getPDF($title, $uri, $report, $mode, $gids, false);
			exit;
		} else if ($output == 'csv') {
			include_once(AC_INCLUDE_PATH. 'classes/exportRpt/exportCSV.class.php');
			$csv = new acheckerCSV($known, $likely, $potential, $html_val, $css_val, 
				$error_nr_known, $error_nr_likely, $error_nr_potential, $error_nr_html, $error_nr_css, $css_error, $html_error);
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename="achecker_report.csv"');
			echo $csv->getCSV($report, $uri, $title, $gids, false);
			exit;
		} else if ($output == 'earl') {
			include_once(AC_INCLUDE_PATH. 'classes/exportRpt/exportEARL.class.php');
			$earl = new acheckerEARL($known, $likely, $potential, $html_val, $css_val, 
				$error_nr_known, $error_nr_likely, $error_nr_potential, $error_nr_html, $error_nr_css, $css_error, $html_error);
			header('Content-Type: text/xml');
			header('Content-Disposition: attachment; filename="achecker_report.xml"');
			echo $earl->getEARL($report, $uri, $title, $gids, false);
			exit;
		} else if ($output == 'wikitext') {
			include_once(AC_INCLUDE_PATH. 'classes/exportRpt/exportWikitext.class.php');
			$wikitext = new acheckerWikitext($known, $likely, $potential, $html_val, $css_val, 
				$error_nr_known, $error_nr_likely, $error_nr_potential, $error_nr_html, $error_nr_css, $css_error, $html_error);
			header('Content-Type: text/plain');
			echo $wikitext->getWikitext($report, $uri, $title, $gids, false);
			exit;
		}
	}
}

?>
