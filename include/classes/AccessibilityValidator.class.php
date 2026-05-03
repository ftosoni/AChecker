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

/**
* AccessibilityValidator
* Class for accessibility validate
* This class checks the accessibility of the given html based on requested guidelines. 
* @access	public
* @author	Cindy Qi Li
* @package checker
*/
if (!defined("AC_INCLUDE_PATH")) die("Error: AC_INCLUDE_PATH is not defined.");

include (AC_INCLUDE_PATH . "lib/simple_html_dom.php");
include_once (AC_INCLUDE_PATH . "classes/BasicChecks.class.php");
include_once (AC_INCLUDE_PATH . "classes/BasicFunctions.class.php");
include_once (AC_INCLUDE_PATH . "classes/CheckFuncUtility.class.php");
include_once (AC_INCLUDE_PATH . "classes/DAO/ChecksDAO.class.php");

define("SUCCESS_RESULT", "success");
define("FAIL_RESULT", "fail");
define("DISPLAY_PREVIEW_HTML_LENGTH", 100);

class AccessibilityValidator {

	// all private
	var $num_success;
	var $num_of_errors = 0;              // number of errors
	
	var $validate_content;               // html content to check
	var $guidelines;                     // array, guidelines to check on
	var $uri;                            // the URI that $validate_content is from, used in check image size in BasicFunctions
	
	// structure: line_number, check_id, result (success, fail)
	var $result = array();               // all check results, including success ones and failed ones
	private $result_map = array();       // optimized lookup map for results
	
	var $check_for_all_elements_array = array(); // array of the to-be-checked check_ids 
	public $checks_data = array();           // cache for check definitions
	var $check_for_tag_array = array();          // array of the to-be-checked check_ids 
	var $prerequisite_check_array = array();     // array of prerequisite check_ids of the to-be-checked check_ids 
	var $content_dom;                    // dom of $validate_content
	private $merged_check_cache = array(); // cache for tag-specific + all-elements check arrays

	var $line_offset;                    // 1. ignore the problems on the lines before the line of $line_offset
	                                     // 2. report line_number = real_line_number - $line_offset
	                                     
	var $col_offset;                     // The number of characters that are added internally at the first line to deal with the 
	                                     // partial html. Fully private, cannot be set or get from outside
	public $is_mediawiki = false;
	public $check_func_array = array();
	private $all_elements_cache = array();
	
	/**
	* public
	* return checks data cache
	*/
	public function getChecksData()
	{
		return $this->checks_data;
	}

	/**
	 * public
	 * $content: string, html content to check
	 * $guidelines: array, guidelines to check on
	 */
	function __construct($content, $guidelines, $uri = '')
	{
		$this->validate_content = $content;
		$this->guidelines = $guidelines;
		$this->line_offset = 0;
		$this->col_offset = 0;
		$this->uri = $uri;
	}
	
	/* public
	 * Validation
	 */
	public function validate()
	{
		// dom of the content to be validated
		$this->content_dom = new simple_html_dom();
		$this->content_dom->load($this->validate_content);
		
		// set arrays of check_id, prerequisite check_id, next check_id
		$this->prepare_check_arrays($this->guidelines);
		
		// Pre-compile checks into closures
		$this->initialize_compiled_checks();

		// Single-pass global var preparation and element collection
		$this->prepare_global_vars();

		error_log("AChecker Debug: Starting flattened element validation");
		
		foreach ($this->all_elements_cache as $e) {
			// Use pre-calculated merged check array
			$tag_checks = isset($this->merged_check_cache[$e->tag]) ? $this->merged_check_cache[$e->tag] : ($this->merged_check_cache['__default__'] ?? array());
				
			foreach ($tag_checks as $check_id)
			{
				// check prerequisite ids first, if fails, report failure and don't need to proceed with $check_id
				$prerequisite_failed = false;

				if (isset($this->prerequisite_check_array[$check_id]))
				{
					foreach ($this->prerequisite_check_array[$check_id] as $prerequisite_check_id)
					{
						if ($this->check($e, $prerequisite_check_id) == FAIL_RESULT)
						{
							$prerequisite_failed = true;
							break;
						}
					}
				}

				// if prerequisite check passes, proceed with current check_id
				if (!$prerequisite_failed)
				{
					$this->check($e, $check_id);
				}
			}
		}

		error_log("AChecker Debug: Element validation complete");
		
		$this->finalize();

		// Release memory from DOM object and cache
		if (is_object($this->content_dom)) {
			$this->content_dom->clear();
			unset($this->content_dom);
		}
		$this->all_elements_cache = array();

		return true;
	}

	/** private
	 * Pre-compile checks into closures and preload search strings
	 */
	private function initialize_compiled_checks()
	{
		$checksDAO = new ChecksDAO();
		$rows = $checksDAO->getAllOpenChecks();
		
		if (is_array($rows))
		{
			foreach ($rows as $row) {
				$code = CheckFuncUtility::convertCode($row['func']);
				$this->check_func_array[$row['check_id']] = $code;
				$this->checks_data[$row['check_id']] = $row; // Cache the whole row
			}
			BasicChecks::preloadSearchStrings($this->checks_data);
		}
	}
	
	/** private
	 * set global vars used in Checks.class.php and BasicFunctions.class.php
	 * to fasten the validation process.
	 * return nothing.
	 */
	private function prepare_global_vars()
	{
		global $header_array, $base_href, $has_duplicate_attribute, $is_data_table, $is_radio_buttons_grouped, $label_array, $label_for_map, $duplicate_id_map, $label_for_text_map;

		$this->all_elements_cache = array();
		$all_nodes = $this->content_dom->nodes; // Use all nodes for single pass
		$header_array = array();
		$label_array = array();
		$label_for_map = array();
		$label_for_text_map = array();
		$id_counts = array();
		$duplicate_id_map = array();

		foreach ($all_nodes as $e) {
			if ($e->nodetype !== HDOM_TYPE_ELEMENT) continue;

			// Headers
			if (strlen($e->tag) == 2 && $e->tag[0] == 'h' && is_numeric($e->tag[1])) {
				$header_array[] = $e;
			}
			// Labels
			if ($e->tag == 'label') {
				$label_array[] = $e;
				if (isset($e->attr['for'])) {
					$for_id = strtolower(trim($e->attr['for']));
					$label_for_map[$for_id] = true;
					
					// Pre-calculate if this label has text or accessible content
					$has_content = (trim($e->plaintext) !== "");
					if (!$has_content) {
						foreach ($e->children as $child) {
							if ($child->tag == 'img' && trim((string)($child->attr['alt'] ?? '')) !== "") {
								$has_content = true;
								break;
							}
						}
					}
					if ($has_content) {
						$label_for_text_map[$for_id] = true;
					}
				}
			}
			// Duplicate IDs
			$id = strtolower(trim((string)($e->attr['id'] ?? '')));
			if ($id !== "") {
				if (isset($id_counts[$id])) {
					$duplicate_id_map[$id] = true;
				}
				$id_counts[$id] = true;
			}
			
			// Table pre-calculation (is it a data table?)
			if ($e->tag == 'table') {
				BasicChecks::isDataTable($e); // This will trigger and cache the result
			}
			
			$this->all_elements_cache[] = $e;
		}

		$base_href = '';
		// find base href, used to check image size
		$base_node = $this->content_dom->find("base", 0);
		if ($base_node && isset($base_node->attr['href'])) {
			$base_href = $base_node->attr['href'];
		}

		$has_duplicate_attribute = array();
		$is_data_table = false;
		$is_radio_buttons_grouped = true;
		
		global $global_array_image_sizes;
		$global_array_image_sizes = array();
	}
	
	/** private
	 * return a simple_html_dom on the given content.
	 * Because accessibility check is based on the root html element <html>,
	 * check if dom has html tag <html>, if no, add it and the end tag to the content
	 * and return the dom on modified content.
	 */
	private function get_simple_html_dom($content)
	{
		global $msg;
		
		// Optimization: check if <html> tag exists before parsing
		if (stripos($content, '<html') === false)
		{
			$complete_html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.
			                 '<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">'.
			                 $content.
			                 '</html>';
			$this->col_offset = 175;  // The number of extra characters that are added onto the first line.
			$content = $complete_html;
		}
		
		$dom = str_get_dom($content);
		unset($content);
		return $dom;
	}
	
	/**
	 * private
	 * generate arrays of check ids, prerequisite check ids, next check ids
	 * array structure:
	 check_array
	 (
	 [html_tag] => Array
	 (
	 [0] => check_id 1
	 [1] => check_id 2
	 ...
	 )
	 ...
	 )

	 prerequisite_check_array
	 (
	 [check_id] => Array
	 (
	 [0] => prerequisite_check_id 1
	 [1] => prerequisite_check_id 2
	 ...
	 )
	 ...
	 )

//	 next_check_array
//	 (
//	 [check_id] => Array
//	 (
//	 [0] => next_check_id 1
//	 [1] => next_check_id 2
//	 ...
//	 )
	 ...
	 )
	 */
	private function prepare_check_arrays($guidelines)
	{
		if (!is_array($guidelines))
			return false;
		// validation process
		else  
		{
			$checksDAO = new ChecksDAO();
			
			// generate array of "all element"
			$rows = $checksDAO->getOpenChecksForAllByGuidelineIDs($guidelines);
			
			$count = 0;
			if (is_array($rows))
			{
				foreach ($rows as $id => $row)
					$this->check_for_all_elements_array[$count++] = $row["check_id"];
				
				$this->check_for_all_elements_array = array_unique($this->check_for_all_elements_array);
			}
			
			// generate array of check_id
			$rows = $checksDAO->getOpenChecksNotForAllByGuidelineIDs($guidelines);
			$prev_html_tag = "";

			if (is_array($rows))
			{
				foreach ($rows as $id => $row)
				{
					if ($row["html_tag"] <> $prev_html_tag && $prev_html_tag <> "") $count = 0;
					
					$this->check_for_tag_array[$row["html_tag"]][$count++] = $row["check_id"];
					
					$prev_html_tag = $row["html_tag"];
				}

				// Deduplicate tag-specific checks
				foreach ($this->check_for_tag_array as $tag => $checks) {
					$this->check_for_tag_array[$tag] = array_unique($checks);
				}
			}
			
			// generate array of prerequisite check_ids
			$rows = $checksDAO->getOpenPreChecksByGuidelineIDs($guidelines);
			$prev_check_id = "";

			if (is_array($rows))
			{
				foreach ($rows as $id => $row)
				{
					if ($row["check_id"] <> $prev_check_id)  $prerequisite_check_array[$row["check_id"]] = array();
					
					array_push($prerequisite_check_array[$row["check_id"]], $row["prerequisite_check_id"]);
					
					$prev_check_id = $row["check_id"];
				}

				// Deduplicate prerequisite checks
				foreach ($prerequisite_check_array as $check_id => $pres) {
					$prerequisite_check_array[$check_id] = array_unique($pres);
				}
			}
			$this->prerequisite_check_array = $prerequisite_check_array;

			// Pre-calculate merged check arrays for each tag we have checks for
			$tags = array_keys($this->check_for_tag_array);
			foreach ($tags as $tag) {
				$this->merged_check_cache[$tag] = array_unique(array_merge(
					$this->check_for_tag_array[$tag], 
					$this->check_for_all_elements_array
				));
			}
			// Default for tags with no specific checks
			$this->merged_check_cache['__default__'] = array_unique($this->check_for_all_elements_array);

			return true;
		}
	}


	/**
	 * private
	 * check given html dom node for given check_id, save result into $this->result
	 * parameters:
	 * $e: simple html dom node
	 * $check_id: check id
	 *
	 * return "success" or "fail"
	 */
	private function check($e, $check_id)
	{
		global $msg, $base_href, $tag_size;
		// don't check the lines before $line_offset
		if ($e->linenumber <= $this->line_offset) return;

		if ($e->linenumber == 1 && $this->col_offset > 0) {
		    $col_number = $e->colnumber - $this->col_offset;
		} else {
		    $col_number = $e->colnumber;
		}
		
		$line_number = $e->linenumber-$this->line_offset;
		
		$result = $this->get_check_result($e, $check_id);

		// has not been checked
		if (!$result)
		{
			try {
				$code = $this->check_func_array[$check_id];
				$check_result = eval($code);
			} catch (Throwable $e_eval) {
				error_log("AChecker Error in check $check_id: " . $e_eval->getMessage());
				$check_result = null;
			}
			
			//CSS code variable
			$css_code = BasicChecks::getCssOutput();
								
			$row = $this->checks_data[$check_id];
			
			if (is_null($check_result))
			{ // when $check_result is not true/false, must be something wrong with the check function.
				$msg->addError(array('CHECK_FUNC', $row['html_tag'].': '._AC($row['name'])));
				$check_result = true; // skip
			}
			
			$result = ($check_result === true) ? SUCCESS_RESULT : FAIL_RESULT;

			if ($result === SUCCESS_RESULT) {
				if(isset($this->num_success[$check_id]))
					$this->num_success[$check_id]++;
				else 
					$this->num_success[$check_id]=1;
				
				// CRITICAL: Cache success results too!
				$this->save_result($e, $line_number, $col_number, '', $check_id, $result, '', '', $css_code);
			}
			
			if ($result == FAIL_RESULT)
			{
				$image = '';
				$image_alt = '';
				
				$preview_html = $e->outertext;
				if (strlen($preview_html) > DISPLAY_PREVIEW_HTML_LENGTH) 
					$html_code = substr($preview_html, 0, DISPLAY_PREVIEW_HTML_LENGTH) . " ...";
				else 
					$html_code = $preview_html;

				// find out preview images for validation on <img>
				if (strtolower(trim($row['html_tag'])) == 'img')
				{
					$image = isset($e->attr['src']) ? BasicChecks::getFile($e->attr['src'], $base_href, $this->uri) : '';

					// The lines below to check the existence of the image slows down the validation process.
					// So commented out.
					//$handle = @fopen($image, 'r');
				    //if (!$handle) $image = '';
				    //else @fclose($handle);
				    
				    // find out image alt text for preview image
				    if (!isset($e->attr['alt'])) $image_alt = '_NOT_DEFINED';
				    else if ($e->attr['alt'] == '') $image_alt = '_EMPTY';
				    else $image_alt = $e->attr['alt'];
				}
				
				global $has_duplicate_attribute;
				if(is_array($has_duplicate_attribute) && isset($has_duplicate_attribute[0]) && isset($has_duplicate_attribute[1])){
					$line_number = $has_duplicate_attribute[0];
					$html_code .= "(".$has_duplicate_attribute[1].")";
				}
				$this->save_result($e, $line_number, $col_number, $html_code, $check_id, $result, $image, $image_alt, $css_code);
			}
		}
		
		return $result;
	}
	
	//MB
	/**
	 * public 
	 * get number of success errors
	 */
	public function get_num_success()
	{
		return $this->num_success;
	}
	
	/**
	 * private
	 * get check result from $result. Return false if the result is not found.
	 * Parameters:
	 * $line_number: line number in the content for this check
	 * $check_id: check id
	 */
	private function get_check_result($e, $check_id)
	{
		$key = spl_object_hash($e) . "_{$check_id}";
		if (isset($this->result_map[$key])) {
			return $this->result_map[$key];
		}
		
		return false;
	}

	/**
	 * private
	 * save each check result
	 * Parameters:
	 * $line_number: line number in the content for this check
	 * $check_id: check id
	 * $result: result to save
	 */
	private function save_result($e, $line_number, $col_number, $html_code, $check_id, $result, $image, $image_alt, $css_code)
	{
		$key = spl_object_hash($e) . "_{$check_id}";
		
		// Strict duplicate check
		if (isset($this->result_map[$key])) {
			return true;
		}

		// Only save details for failures in the result array (for reports)
		if ($result === FAIL_RESULT) {
			array_push($this->result, array("line_number"=>$line_number, "col_number"=>$col_number, "html_code"=>$html_code, "check_id"=>$check_id, "result"=>$result, "image"=>$image, "image_alt"=>$image_alt, "css_code"=>$css_code));
		}
		
		// ALWAYS update lookup map (including successes) to avoid re-validation
		$this->result_map[$key] = $result;

		return true;
	}
	
	/**
	 * private
	 * convert the given array to a string of the array elements separated by the given delimiter.
	 * For example:
	 * array ([0] => 7, [1] => 8)
	 * delimiter: ,
	 * is converted to string "7, 8"
	 */
	private function convert_array_to_string($in_array, $delimiter)
	{
		$count = 0;
		if (is_array($in_array))
		{
			foreach ($in_array as $element)
			{
				if ($count == 0) $str = $element;
				else $str .= $delimiter . $element;
				
				$count++;
			}
			return $str;
		}
		else
			return false;
	}
	
	/**
	 * private 
	 * generate class value: array of error results, number of errors
	 */
	private function finalize()
	{
		$this->num_of_errors = count($this->result);
	}
	
	/**
	 * public 
	 * set line offset
	 */
	public function setLineOffset($lineOffset)
	{
		$this->line_offset = $lineOffset;
	}
	
	/**
	 * public 
	 * return line offset
	 */
	public function getLineOffset()
	{
		return $this->line_offset;
	}
	
	/**
	 * public 
	 * return array of all checks that have been done, including successful and failed ones
	 */
	public function getValidationErrorRpt()
	{
		return $this->result;
	}
	
	/**
	 * public 
	 * return number of errors
	 */
	public function getNumOfValidateError()
	{
		return $this->num_of_errors;
	}

	/**
	 * public 
	 * return array of all checks that have been done by check id, including successful and failed ones
	 */
	public function getResultsByCheckID($check_id)
	{
		$rtn = array();
		foreach ($this->result as $oneResult)
			if ($oneResult["check_id"] == $check_id)
				array_push($rtn, array("line_number"=>$oneResult["line_number"], "col_number"=>$oneResult["col_number"], "check_id"=>$oneResult["check_id"], "result"=>$oneResult["result"]));
	
		return $rtn;
	}

	/**
	 * public 
	 * return array of all checks that have been done by line number, including successful and failed ones
	 */
	public function getResultsByLine($line_number)
	{
		$rtn = array();
		foreach ($this->result as $oneResult)
			if ($oneResult["line_number"] == $line_number)
				array_push($rtn, array("line_number"=>$oneResult["line_number"], "col_number"=>$oneResult["col_number"], "check_id"=>$oneResult["check_id"], "result"=>$oneResult["result"]));
	
		return $rtn;
	}
}
?>
