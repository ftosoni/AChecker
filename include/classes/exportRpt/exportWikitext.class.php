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

/**
* acheckerWikitext
* Class to generate error report in Wikitext format
* @access	public
*/
if (!defined("AC_INCLUDE_PATH")) exit;
include_once(AC_INCLUDE_PATH. "classes/DAO/GuidelinesDAO.class.php");

class acheckerWikitext {

	var $known = array();
	var $likely = array();
	var $potential = array();
	var $html = array();
	var $css = array();
	
	var $error_nr_known = 0;
	var $error_nr_likely = 0;
	var $error_nr_potential = 0;
	var $error_nr_html = 0;
	var $error_nr_css = 0;
	
	var $css_error = '';
	var $html_error = '';
	
	function __construct($known, $likely, $potential, $html, $css, 
		$error_nr_known, $error_nr_likely, $error_nr_potential, $error_nr_html, $error_nr_css, $css_error, $html_error)
	{				
		$this->known = $known;
		$this->likely = $likely;
		$this->potential = $potential;
		$this->html = $html;	
		$this->css = $css;	
		
		$this->error_nr_known = $error_nr_known;
		$this->error_nr_likely = $error_nr_likely;
		$this->error_nr_potential = $error_nr_potential;
		$this->error_nr_html = $error_nr_html;
		$this->error_nr_css = $error_nr_css;
		
		$this->css_error = $css_error;
		$this->html_error = $html_error;
	}
	
	public function	getWikitext($problem, $input_content_type, $title, $_gids) 
	{	
		$date = AC_date('%Y-%m-%d');
		$time = AC_date('%H:%M:%S');
		$filename = 'achecker_'.$date.'_'.str_replace(':', '-', $time);
		
		$content = "== Accessibility Review ==\n";
		$content .= "* '''Date:''' " . $date . " " . $time . "\n";
		if ($input_content_type != 'file' && $input_content_type != 'paste') {
			$content .= "* '''Source:''' [" . $input_content_type . " " . ($title ? $title : $input_content_type) . "]\n";
		}
		
		$guidelinesDAO = new GuidelinesDAO();
		$guideline_rows = $guidelinesDAO->getGuidelineByIDs($_gids);
		if (is_array($guideline_rows)) {
			$content .= "* '''Guidelines:''' ";
			$g_list = array();
			foreach ($guideline_rows as $row) {
				$g_list[] = $row["abbr"];
			}
			$content .= implode(', ', $g_list) . "\n";
		}
		$content .= "\n";

		if ($problem == 'all') {
			$content .= $this->getResultSection('known');
			$content .= $this->getResultSection('likely');
			$content .= $this->getResultSection('potential');
			if ($this->error_nr_html != -1) $content .= $this->getHTML();
			if ($this->error_nr_css != -1) $content .= $this->getCSS();
		} else if ($problem == 'css') {
			$content .= $this->getCSS();
		} else if ($problem == 'html') {
			$content .= $this->getHTML();
		} else {
			$content .= $this->getResultSection($problem);
		}	

		$path = AC_EXPORT_RPT_DIR.$filename.'.txt';  
		$handle = fopen($path, 'w');	
		fwrite($handle, $content); 
		fclose($handle);
		
		return $path;		
	}
	
	private function getResultSection($problem_type) 
	{		
		$content = "";
		if ($problem_type == 'known') {
			$array = $this->known;
			$nr = $this->error_nr_known;
			$content .= "=== Known Problems (" . $nr . ") ===\n";
		} else if ($problem_type == 'likely') {
			$array = $this->likely;
			$nr = $this->error_nr_likely;
			$content .= "=== Likely Problems (" . $nr . ") ===\n";
		} else if ($problem_type == 'potential') {
			$array = $this->potential;
			$nr = $this->error_nr_potential;
			$content .= "=== Potential Problems (" . $nr . ") ===\n";
		}
		
		if ($nr == 0) {
			$content .= ": " . _AC("congrats_no_$problem_type") . "\n\n";
			return $content;
		} 

		if (is_array($array) && count($array) > 0) {
			if ($problem_type == 'known') {
				foreach ($array as $error) {
					$content .= "* '''Line " . $error['line_nr'] . ", Col " . $error['col_nr'] . "''': " . strip_tags($error['error']) . "\n";
					$content .= "** ''Repair'': " . strip_tags($error['repair']['label'] . ': ' . $error['repair']['detail']) . "\n";
					$content .= "** ''HTML'': <code>" . str_replace("\n", " ", html_entity_decode($error['html_code'], ENT_COMPAT, 'UTF-8')) . "</code>\n";
				}
			} else { 
				foreach ($array as $category) {
					foreach ($category as $error) {
						$content .= "* '''Line " . $error['line_nr'] . ", Col " . $error['col_nr'] . "''': " . strip_tags($error['error']) . "\n";
						$content .= "** ''HTML'': <code>" . str_replace("\n", " ", html_entity_decode($error['html_code'], ENT_COMPAT, 'UTF-8')) . "</code>\n";
						if (isset($_SESSION['user_id'])) {
							$decision = _AC('file_no_decision');
							if ($error['decision'] == 'true') $decision = _AC('file_passed');
							else if ($error['decision'] == false) $decision = _AC('file_failed');
							$content .= "** ''Decision'': " . $decision . "\n";
						}
					}
				}
			}
		}
		$content .= "\n";
		return $content;
	}

	private function getHTML() 
	{	
		$content = "=== HTML Validation Result (" . $this->error_nr_html . ") ===\n";
		if ($this->error_nr_html == -1) {			
			$content .= ": " . _AC("html_validator_disabled") . "\n\n";
		} else if ($this->error_nr_html == 0 && $this->html_error == '') {
			$content .= ": " . _AC("congrats_html_validation") . "\n\n";
		} else if($this->error_nr_html == 0 && $this->html_error != '') {
			$content .= ": " . $this->html_error . "\n\n";
		} else {
			foreach ($this->html as $error) {				
				$content .= "* '''Line " . $error['line'] . ", Col " . $error['col'] . "''': " . html_entity_decode(strip_tags($error['err'])) . "\n";
				$content .= "** ''HTML'': <code>" . str_replace("\n", " ", html_entity_decode($error['html_1'].$error['html_2'].$error['html_3'], ENT_COMPAT, 'UTF-8')) . "</code>\n";
			}
		}
		$content .= "\n";
		return $content;
	}
	
	private function getCSS() 
	{		
		$content = "=== CSS Validation Result (" . $this->error_nr_css . ") ===\n";
		if ($this->css_error == '' && $this->error_nr_css == -1) {
			$content .= ": " . _AC("css_validator_disabled") . "\n\n";
		} else if ($this->css_error != '') {
			$content .= ": " . $this->css_error . "\n\n";
		} else if ($this->error_nr_css == 0) {
			$content .= ": " . _AC("congrats_css_validation") . "\n\n";
		} else {	
			foreach($this->css as $uri => $group) {
				$content .= "; URI: " . $uri . "\n";
				foreach($group as $error) {
					$content .= "* '''Line " . $error['line'] . "''': " . strip_tags(html_entity_decode($error['parse'])) . "\n";
					if ($error['code'] != '') {
						$content .= "** ''Code'': <code>" . str_replace("\n", " ", $error['code']) . "</code>\n";
					}
				}
			}
		}
		$content .= "\n";
		return $content;
	}
}
?>
