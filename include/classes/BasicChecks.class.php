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
 * Basic Checks.class.php
 * Class for accessibility validate
 * This class contains basic functions called by BasicFunctions.class.php
 *
 * @access	public
 * @author	Cindy Qi Li
 * @package checker
 */

if (!defined("AC_INCLUDE_PATH"))
	die("Error: AC_INCLUDE_PATH is not defined.");
include_once(AC_INCLUDE_PATH . 'classes/DAO/LangCodesDAO.class.php');

define("DEFAULT_FONT_SIZE", 12);
define("DEFAULT_FONT_FORMAT", "pt");

class BasicChecks
{
	/**
	 * cut out language code from given $lang
	 * return language code
	 */
	public static function cutOutLangCode($lang)
	{
		$words = explode("-", $lang);
		return trim($words[0]);
	}

	/**
	 * return array of all the 2-letter & 3-letter language codes with direction 'rtl'
	 */
	public static function getRtlLangCodes()
	{
		$langCodesDAO = new LangCodesDAO();

		return $langCodesDAO->GetLangCodeByDirection('rtl');
	}

	/**
	 * check if the text is in one of the search string defined in $search_strings
	 * @param $text: text to check
	 *        $search_strings: array of match string. The string could be %[string]% or %[string] or [string]%
	 * @return true if in, otherwise, return false
	 */
	public static function inSearchString($text, $search_strings)
	{
		foreach ($search_strings as $str) {
			$str = trim($str);
			$prefix = substr($str, 0, 1);
			$suffix = substr($str, -1);

			if ($prefix == '%' && $suffix == '%') {  // match '%match%'
				if (stripos((string) $text, substr((string) $str, 1, -1)) > 0)
					return true;
			} else if ($prefix == '%') {  // match '%match'
				$match = substr($str, 1);
				if (substr($text, strlen($match) * (-1)) == $match)
					return true;
			} else if ($suffix == '%') {  // match 'match%'
				$match = substr($str, 0, -1);
				if (substr($text, 0, strlen($match)) == $match)
					return true;
			} else if ($text == $str) {
				return true;
			}
		}

		return false;
	}

	private static $search_str_cache = array();

	/**
	 * Pre-populates the search string cache
	 */
	public static function preloadSearchStrings($checks_data)
	{
		foreach ($checks_data as $check_id => $row) {
			if (isset($row['search_str'])) {
				self::$search_str_cache[$check_id] = explode(',', strtolower(_AC($row['search_str'])));
			}
		}
	}

	/**
	 * check if the inner text is in one of the search string defined in checks.search_str
	 * return true if in, otherwise, return false
	 */
	public static function isTextInSearchString($text, $check_id, $e)
	{
		$text = strtolower(trim($text));

		if (!isset(self::$search_str_cache[$check_id])) {
			$checksDAO = new ChecksDAO();
			$row = $checksDAO->getCheckByID($check_id);
			if ($row && isset($row['search_str'])) {
				self::$search_str_cache[$check_id] = explode(',', strtolower(_AC($row['search_str'])));
			} else {
				self::$search_str_cache[$check_id] = array();
			}
		}

		$search_strings = self::$search_str_cache[$check_id];

		if (empty($search_strings))
			return true;
		else {
			return BasicChecks::inSearchString($text, $search_strings);
		}
	}

	/**
	 * Makes a guess about the table type.
	 * Returns true if this should be a data table, false if layout table.
	 */
	public static function isDataTable($e)
	{
		if (isset($e->_cache_is_data_table))
			return $e->_cache_is_data_table;

		$is_data = false;
		foreach ($e->children() as $child) {
			if ($child->tag == "th") {
				$is_data = true;
				break;
			}
			if (BasicChecks::isDataTable($child)) {
				$is_data = true;
				break;
			}
		}
		$e->_cache_is_data_table = $is_data;
		return $is_data;
	}

	/**
	 * Check recursively to find if $global_e has a parent with tag $parent_tag
	 * return true if found, otherwise, false
	 */
	public static function hasParent($e, $parent_tag)
	{
		$curr = $e;
		while ($curr && $curr->parent()) {
			$curr = $curr->parent();
			if ($curr->tag == $parent_tag)
				return true;
		}
		return false;
	}

	/**
	 * Check recursively to find the number of children in $e with tag $child_tag
	 * return number of qualified children
	 */
	public static function getNumOfTagRecursiveInChildren($e, $tag)
	{
		$num = 0;

		foreach ($e->children() as $child)
			if ($child->tag == $tag)
				$num++;
			else
				$num += BasicChecks::getNumOfTagRecursiveInChildren($child, $tag);

		return $num;
	}

	/**
	 * Check recursively if there are duplicate $attr defined in children of $e
	 * set global var hasDuplicateAttribute to true if there is, otherwise, set it to false
	 */
	public static function hasDuplicateAttribute($e, $attr, &$id_array)
	{
		global $has_duplicate_attribute;

		foreach ($e->children() as $child) {
			$id_val = strtolower(trim((string) ((isset($child->attr) && isset($child->attr[$attr])) ? $child->attr[$attr] : '')));

			// Swap out the element line number for the duplicate ID line number,
			// This is a workaround to replace the usual lime number for body, returned
			// when duplicate IDs are found, with the line number and ID value to make
			// the offending duplicate easier to find.
			if ($id_val <> "" && in_array($id_val, $id_array)) {
				if (!is_array($has_duplicate_attribute))
					$has_duplicate_attribute = array();
				$has_duplicate_attribute[] = $child->linenumber;
				$has_duplicate_attribute[] = $id_val;
				return $has_duplicate_attribute;
			} else {
				if ($id_val <> "")
					array_push($id_array, $id_val);
				BasicChecks::hasDuplicateAttribute($child, $attr, $id_array);
			}
		}
	}

	/**
	 * Get number of header rows and number of rows that have header column
	 * return array of (num_of_header_rows, num_of_rows_with_header_col)
	 */
	public static function getNumOfHeaderRowCol($e)
	{
		$num_of_header_rows = 0;
		$num_of_rows_with_header_col = 0;

		foreach ($e->find("tr") as $row) {
			$num_of_th = count($row->find("th"));

			if ($num_of_th > 1)
				$num_of_header_rows++;
			if ($num_of_th == 1)
				$num_of_rows_with_header_col++;
		}

		return array($num_of_header_rows, $num_of_rows_with_header_col);
	}

	/**
	 * called by BasicFunctions::hasFieldsetOnMultiCheckbox()
	 * Check if form has "fieldset" and "legend" to group multiple checkbox buttons.
	 * @return true if has, otherwise, false
	 */
	public static function hasFieldsetOnMultiCheckbox($e)
	{
		// find if there are radio buttons with same name
		$children = $e->children();
		$num_of_children = count($children);

		foreach ($children as $i => $child) {
			$type = (isset($child->attr) && isset($child->attr["type"])) ? strtolower(trim((string) $child->attr["type"])) : '';
			if ($type == "checkbox") {
				$this_name = (isset($child->attr) && isset($child->attr["name"])) ? strtolower(trim((string) $child->attr["name"])) : '';

				for ($j = $i + 1; $j < $num_of_children; $j++) {
					// if there are radio buttons with same name,
					// check if they are contained in "fieldset" and "legend" elements
					$other_name = (is_array($children[$j]->attr) && isset($children[$j]->attr["name"])) ? strtolower(trim((string) $children[$j]->attr["name"])) : '';
					if ($other_name == $this_name)
						if (BasicChecks::hasParent($e, "fieldset"))
							return BasicChecks::hasParent($e, "legend");
						else
							return false;
				}
			}
		}

		return true;
	}

	/**
	 * check if value in the given attribute is a valid language code
	 * return true if valid, otherwise, return false
	 */
	public static function isValidLangCode($code)
	{
		// The allowed characters in a valid language code are letters, numbers or dash(-).
		if (!preg_match("/^[a-zA-Z0-9-]+$/", $code)) {
			return false;
		}

		$code = BasicChecks::cutOutLangCode($code);
		$langCodesDAO = new LangCodesDAO();

		if (strlen($code) == 2) {
			$rows = $langCodesDAO->GetLangCodeBy2LetterCode($code);
		} else if (strlen($code) == 3) {
			$rows = $langCodesDAO->GetLangCodeBy3LetterCode($code);
		} else {
			return false;
		}

		return (is_array($rows));
	}

	/**
	 * Return file location based on base href or uri
	 * return file itself if both base href and uri are empty.
	 */
	public static function getFile($src_file, $base_href, $uri)
	{
		if (preg_match('/http.*(\:\/\/).*/', (string) $src_file)) {
			$file = $src_file;
		} else {
			// URI that image relatively located to
			// Note: base_href is from <base href="...">
			if (isset($base_href) && $base_href <> '') {
				if (substr((string) $base_href, -1) <> '/')
					$base_href .= '/';
			} else if (isset($uri) && $uri <> '') {
				preg_match('/^(.*\:\/\/.*\/).*/', (string) $uri, $matches);
				if (!isset($matches[1]))
					$uri .= '/';
				else
					$uri = $matches[1];
			}

			if (substr((string) $src_file, 0, 2) == '//') {
				$file = $src_file;
			} else if (substr((string) $src_file, 0, 1) == '/')  //absolute path
			{
				if (isset($base_href) && $base_href <> '') {
					$prefix_uri = $base_href;
				} else if (isset($uri) && $uri <> '') {
					$prefix_uri = $uri;
				}

				if (isset($prefix_uri) && $prefix_uri <> '') {
					if (preg_match('/^(.*\:\/\/)([^\/]*)/', (string) $uri, $matches)) {
						$root_uri = $matches[1] . $matches[2];
						$file = $root_uri . $src_file;
					} else {
						$file = $prefix_uri . $src_file;
					}
				}
			} else // relative path
			{
				if (isset($base_href) && $base_href <> '') {
					$file = $base_href . $src_file;
				} else if (isset($uri) && $uri <> '') {
					$file = $uri . $src_file;
				}
			}
		}

		if (!isset($file))
			$file = $src_file;

		return $file;
	}

	/**
	Check if the luminosity contrast ratio between $color1 and $color2 is at least 5:1
	Input: color values to compare: $color1 & $color2. Color value can be one of: rgb(x,x,x), #xxxxxx, colorname
	Return: true or false
	*/
	public static function has_good_contrast_waiert($color1, $color2)
	{
		include_once(AC_INCLUDE_PATH . "classes/ColorValue.class.php");

		$color1 = new ColorValue($color1);
		$color2 = new ColorValue($color2);

		if (!$color1->isValid() || !$color2->isValid())
			return true;

		$colorR1 = $color1->getRed();
		$colorG1 = $color1->getGreen();
		$colorB1 = $color1->getBlue();

		$colorR2 = $color2->getRed();
		$colorG2 = $color2->getGreen();
		$colorB2 = $color2->getBlue();

		$brightness1 = (($colorR1 * 299) +
			($colorG1 * 587) +
			($colorB1 * 114)) / 1000;

		$brightness2 = (($colorR2 * 299) +
			($colorG2 * 587) +
			($colorB2 * 114)) / 1000;

		$difference = 0;
		if ($brightness1 > $brightness2) {
			$difference = $brightness1 - $brightness2;
		} else {
			$difference = $brightness2 - $brightness1;
		}

		if ($difference < 125) {
			return false;
		}

		// calculate the color difference
		$difference = 0;
		// red
		if ($colorR1 > $colorR2) {
			$difference = $colorR1 - $colorR2;
		} else {
			$difference = $colorR2 - $colorR1;
		}

		// green
		if ($colorG1 > $colorG2) {
			$difference += $colorG1 - $colorG2;
		} else {
			$difference += $colorG2 - $colorG1;
		}

		// blue
		if ($colorB1 > $colorB2) {
			$difference += $colorB1 - $colorB2;
		} else {
			$difference += $colorB2 - $colorB1;
		}

		return ($difference > 499);
	}

	/**
	 * Check recursively to find if $e has a parent with tag $parent_tag
	 * return true if found, otherwise, false
	 */
	public static function has_parent($e, $parent_tag)
	{
		if ($e->parent() == NULL)
			return false;

		if ($e->parent()->tag == $parent_tag)
			return true;
		else
			return BasicChecks::has_parent($e->parent(), $parent_tag);
	}


	/**
	 * cut out language code from given $lang
	 * return language code
	 */
	public static function cut_out_lang_code($lang)
	{
		$words = explode("-", $lang);
		return trim($words[0]);
	}

	/**
	 * find language code defined in html
	 * return language code
	 */
	public static function get_lang_code($content_dom)
	{
		// get html language
		$e_htmls = $content_dom->find("html");

		foreach ($e_htmls as $e_html) {
			if (is_array($e_html->attr) && isset($e_html->attr["xml:lang"])) {
				$lang = trim($e_html->attr["xml:lang"]);
				break;
			} else if (is_array($e_html->attr) && isset($e_html->attr["lang"])) {
				$lang = trim($e_html->attr["lang"]);
				break;
			}
		}

		return BasicChecks::cut_out_lang_code($lang);
	}


	/**
	 * Check if the luminosity contrast ratio between $color1 and $color2 is at least 5:1
	 * Input: color values to compare: $color1 & $color2. Color value can be one of: rgb(x,x,x), #xxxxxx, colorname
	 * Return: true or false
	 */
	public static function get_luminosity_contrast_ratio($color1, $color2)
	{
		include_once(AC_INCLUDE_PATH . "classes/ColorValue.class.php");

		$color1 = new ColorValue($color1);
		$color2 = new ColorValue($color2);

		if (!$color1->isValid() || !$color2->isValid())
			return true;

		$linearR1 = $color1->getRed() / 255;
		$linearG1 = $color1->getRed() / 255;
		$linearB1 = $color1->getRed() / 255;

		$lum1 = (pow($linearR1, 2.2) * 0.2126) +
			(pow($linearG1, 2.2) * 0.7152) +
			(pow($linearB1, 2.2) * 0.0722) + .05;

		$linearR2 = $color2->getRed() / 255;
		$linearG2 = $color2->getRed() / 255;
		$linearB2 = $color2->getRed() / 255;

		$lum2 = (pow($linearR2, 2.2) * 0.2126) +
			(pow($linearG2, 2.2) * 0.7152) +
			(pow($linearB2, 2.2) * 0.0722) + .05;

		$ratio = max($lum1, $lum2) / min($lum1, $lum2);

		// round the ratio to 2 decimal places
		$factor = pow(10, 2);

		// Shift the decimal the correct number of places
		// to the right.
		$val = $ratio * $factor;

		// Round to the nearest integer.
		$tmp = round($val);

		// Shift the decimal the correct number of places back to the left.
		$ratio2 = $tmp / $factor;

		return $ratio2;
	}

	/**
	 * Check recursively if there are duplicate $attr defined in children of $e
	 * set global var $has_duplicate_attribute to true if there is, otherwise, set it to false
	 */
	public static function has_duplicate_attribute($e, $attr, &$id_array)
	{
		global $has_duplicate_attribute;

		if ($has_duplicate_attribute)
			return;

		foreach ($e->children() as $child) {
			$id_val = strtolower(trim((is_array($child->attr) && isset($child->attr[$attr])) ? (string) $child->attr[$attr] : ''));

			if ($id_val <> "" && in_array($id_val, $id_array)) {
				$has_duplicate_attribute = array($child->linenumber, $id_val);
			} else {
				if ($id_val <> "")
					array_push($id_array, $id_val);
				BasicChecks::has_duplicate_attribute($child, $attr, $id_array);
			}
		}
	}


	/**
	 * check if $e has associated label
	 * return true if has, otherwise, return false
	 */
	public static function has_associated_label($e, $content_dom)
	{
		// 1. The element $e is contained by a "label" element
		// 2. The element $e has a "title" attribute
		// 3. The element $e has a "aria-label" attribute
		if ((is_object($e->parent()) && $e->parent()->tag == "label") || (is_array($e->attr) && (isset($e->attr["title"]) || isset($e->attr["aria-label"]))))
			return true;

		// 3. The element $e has an "id" attribute value that matches the "for" attribute value of a "label" element
		$input_id = (is_array($e->attr) && isset($e->attr["id"])) ? (string) $e->attr["id"] : '';

		if ($input_id == "")
			return false;  // attribute "id" must exist

		global $label_for_map;
		if (isset($label_for_map[strtolower(trim($input_id))]))
			return true;

		return false;
	}

	/**
	 * ADD CODE FOR THIS!!!
	 * check if the label for $e is closely positioned to $e
	 * return true if closely positioned, otherwise, return false
	 */
	public static function is_label_closed($e)
	{
		return true;
	}

	/**
	 * Check radio button groups are marked using "fieldset" and "legend" elements
	 * Return: use global variable $is_radio_buttons_grouped to return true (grouped properly) or false (not grouped)
	 */
	public static function is_radio_buttons_grouped($e)
	{
		$radio_buttons = array();

		foreach ($e->find("input") as $e_input) {
			if (strtolower(trim(is_array($e_input->attr) && isset($e_input->attr["type"]) ? (string) $e_input->attr["type"] : '')) == "radio")
				array_push($radio_buttons, $e_input);
		}

		for ($i = 0; $i < count($radio_buttons); $i++) {
			for ($j = 0; $j < count($radio_buttons); $j++) {
				if (
					$i <> $j && strtolower(trim(is_array($radio_buttons[$i]->attr) && isset($radio_buttons[$i]->attr["name"]) ? (string) $radio_buttons[$i]->attr["name"] : '')) == strtolower(trim(is_array($radio_buttons[$j]->attr) && isset($radio_buttons[$j]->attr["name"]) ? (string) $radio_buttons[$j]->attr["name"] : ''))
					&& !BasicChecks::has_parent($radio_buttons[$i], "fieldset") && !BasicChecks::has_parent($radio_buttons[$i], "legend")
				) {

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Makes a guess about the table type.
	 * Returns true if this should be a data table, false if layout table.
	 */
	public static function is_data_table($e)
	{
		global $is_data_table;

		// "table" element containing <th> is considered a data table
		if ($is_data_table)
			return;

		foreach ($e->children() as $child) {
			if ($child->tag == "th")
				$is_data_table = true;
			else
				BasicChecks::is_data_table($child);
		}
	}


	/**
	 * check if associated label of $e has text
	 * return true if has, otherwise, return false
	 */
	public static function associated_label_has_text($e, $content_dom)
	{
		// 1. The element $e has a "title" or "aria-label" attribute
		if (!empty(is_array($e->attr) && isset($e->attr["title"]) ? $e->attr["title"] : '') && !empty(is_array($e->attr) && isset($e->attr["aria-label"]) ? $e->attr["aria-label"] : ''))
			return true;

		// 2. The element $e is contained by a "label" element
		if ($e->parent()->tag == "label") {
			// Check if label contains any text besides tags
			if (trim($e->parent()->plaintext) !== '')
				return true;
		}

		// 3. The element $e has an "id" attribute value that matches the "for" attribute value of a "label" element
		$input_id = is_array($e->attr) && isset($e->attr["id"]) ? (string) $e->attr["id"] : '';

		if ($input_id == "")
			return false;  // attribute "id" must exist

		global $label_array;
		if (is_array($label_array))
			foreach ($label_array as $e_label) {
				if ((is_array($e_label->attr) && isset($e_label->attr["for"]) ? (string) $e_label->attr["for"] : '') == $input_id) {
					// label contains text
					if (trim($e_label->plaintext) <> "")
						return true;

					// label contains an image with alt text
					foreach ($e_label->children as $e_label_child)
						if ($e_label_child->tag == "img" && strlen(trim(is_array($e_label_child->attr) && isset($e_label_child->attr["alt"]) ? (string) $e_label_child->attr["alt"] : '')) > 0)
							return true;
				}
			}

		return false;
	}


	public static function check_next_header_not_in($content_dom, $line_number, $col_number, $not_in_array)
	{
		global $header_array;

		// find the next header after $line_number, $col_number
		foreach ($header_array as $e) {
			if ($e->linenumber > $line_number || ($e->linenumber == $line_number && $e->colnumber > $col_number)) {
				if (!isset($next_header))
					$next_header = $e;
				else if ($e->linenumber < $next_header->line_number || ($e->linenumber == $next_header->line_number && $e->colnumber > $next_header->col_number))
					$next_header = $e;
			}
		}

		if (isset($next_header) && !in_array($next_header->tag, $not_in_array))
			return false;
		else
			return true;
	}

	public static function find_all_headers($elements, &$header_array)
	{
		foreach ($elements as $e) {
			if (substr($e->tag, 0, 1) == "h" and intval(substr($e->tag, 1)) <> 0)
				array_push($header_array, $e);

			BasicChecks::find_all_headers($e->children(), $header_array);
		}

		return $header_array;
	}



	/**
	 * Check recursively to find the number of children in $e with tag $child_tag
	 * return number of qualified children
	 */
	public static function count_children_by_tag($e, $tag)
	{
		$num = 0;

		foreach ($e->children() as $child)
			if ($child->tag == $tag)
				$num++;
			else
				$num += BasicChecks::count_children_by_tag($child, $tag);

		return $num;
	}



	/**
	 * Get number of header rows and number of rows that have header column
	 * return array of (num_of_header_rows, num_of_rows_with_header_col)
	 */
	public static function get_num_of_header_row_col($e)
	{
		$num_of_header_rows = 0;
		$num_of_rows_with_header_col = 0;

		foreach ($e->find("tr") as $row) {
			$num_of_th = count($row->find("th"));

			if ($num_of_th > 1)
				$num_of_header_rows++;
			if ($num_of_th == 1)
				$num_of_rows_with_header_col++;
		}

		return array($num_of_header_rows, $num_of_rows_with_header_col);
	}


	//CSS basic checks
	public static function getSiteUri($uri)
	{


		if (stripos((string) $uri, ".php") !== false || stripos((string) $uri, ".html") !== false || stripos((string) $uri, ".asp") !== false || stripos((string) $uri, ".htm") !== false || stripos((string) $uri, ".xhtml") !== false || stripos((string) $uri, ".xhtm") !== false) {
			// remove the part after the last /
			$uri = strrev($uri);
			$position = stripos((string) $uri, "/");
			$uri = strrev($uri);
			$uri = substr($uri, 0, -$position);
		}
		// if present, remove the / at the end of the URL
		if (substr($uri, -1) == "/")
			$uri = substr($uri, 0, -1);

		return $uri;
	}
	// removes all children of $e and returns the content as "plaintext"
	public static function remove_children($e)
	{

		$content = $e->plaintext;
		$children = $e->children();

		foreach ($children as $obj) {

			$text = $obj->plaintext;
			if ($text != null || $text != '') {
				$segments = explode($text, $content, 2);
				$content = implode($segments);
			}
		}

		return $content;
	}


	/*
	 * Search and returns the value of a property CSS (the value between ":" and ";")
	 * Searches in style and inline style sheet (id, class, property name)
	 * It takes as parameters the item and the name of the property
	 */
	public static function get_p_css($e, $p)
	{
		if ($e === null) return null;

		$inline = "";

		// check for inline style
		if (is_array($e->attr) && isset($e->attr["style"])) {
			$inline = BasicChecks::GetElementStyleInline((string) $e->attr["style"], $p);
			// verify "!important"
			$position = stripos((string) $inline, "!important");
			if ($position !== false) {
				// remove "!important" and return the property value
				$inline = str_ireplace($p, "", $inline);
				$inline = str_ireplace(":", "", $inline);
				$inline = str_ireplace("!important", "", $inline);
				return $inline;
			}
		}

		$best = null;

		// id
		if (is_array($e->attr) && isset($e->attr["id"])) {
			$id = BasicChecks::GetElementStyleId($e, (string) $e->attr["id"], $p);
			$best = BasicChecks::getPriorityInfo($best, $id);
		}
		// class
		if (is_array($e->attr) && isset($e->attr["class"])) {
			$class = BasicChecks::GetElementStyleClass($e, (string) $e->attr["class"], $p);
			$best = BasicChecks::getPriorityInfo($best, $class);
		}
		//tag
		$tag = BasicChecks::GetElementStyle($e, (string) $e->tag, $p);

		$best = BasicChecks::getPriorityInfo($best, $tag);

		// Search for * in the internal/external style sheet
		// Apply the property of * if:
		// 1. The property $p is not declared for element $e in the internal or external style
		// 2. The property declared in * is !important, but the one in the style sheet is not


		$best_all = BasicChecks::GetElementStyle($e, '*', $p);

		$v_best = (is_array($best) && isset($best["value"])) ? (string) $best["value"] : "";
		$v_best_all = (is_array($best_all) && isset($best_all["value"])) ? (string) $best_all["value"] : "";

		if ($best == null || (stripos($v_best, "!important") === false && stripos($v_best_all, "!important") !== false))
			$best = $best_all;

		// if coming here was not the style inline! important since the early control
		// inline style has always priority rule, unless a rule in a style indoor / outdoor does not contain! important

		if ($inline != null && $inline != "") {
			if (is_array($best) && isset($best["value"]) && stripos($best["value"], "!important") === false) // !important is not present in the style sheet
				return $inline;
		}

		global $css_array;

		if (isset($best["css_rule"])) {
			$same = false;
			if (sizeof($css_array) > 0) {
				$size_of_best = sizeof($best["css_rule"]["prev"]);
				foreach ($css_array as $rule) {
					$size_of_prev_rules = sizeof($rule["prev"]);
					if ($size_of_prev_rules == $size_of_best) {
						for ($i = 0; $i < $size_of_prev_rules; $i++) {
							if ($rule["prev"][$i] == $best["css_rule"]["prev"][$i])
								$same = true;
							else {
								$same = false;
								break;
							}
						}
						if ($same == true)
							break;
					}
				}
			}

			if ($same == false)
				array_push($css_array, $best["css_rule"]);
		}
		return (is_array($best) && isset($best["value"])) ? $best["value"] : null;

	}

	public static function get_p_css_a($e, $p, $link_sel)
	{

		// $best: will store the value of the priority rule that has more contained in the high-style, indoor / outdoor
		// relative to the element $e $p
		$best = null;

		// id
		if (is_array($e->attr) && isset($e->attr["id"])) {
			$id = BasicChecks::GetElementStyleId($e, (string) $e->attr["id"] . ":" . $link_sel, $p);
			$best = BasicChecks::getPriorityInfo($best, $id);
		}
		// class
		if (is_array($e->attr) && isset($e->attr["class"])) {
			$class = BasicChecks::GetElementStyleClass($e, (string) $e->attr["class"] . ":" . $link_sel, $p);
			$best = BasicChecks::getPriorityInfo($best, $class);
		}
		//tag
		$tag = BasicChecks::GetElementStyle($e, $e->tag . ":" . $link_sel, $p);
		$best = BasicChecks::getPriorityInfo($best, $tag);

		global $css_array;

		if (isset($best["css_rule"])) {
			$same = false;
			if (sizeof($css_array) > 0) {
				$size_of_best = sizeof($best["css_rule"]["prev"]);
				foreach ($css_array as $rule) {
					$size_of_prev_rules = sizeof($rule["prev"]);
					if ($size_of_prev_rules == $size_of_best) {
						for ($i = 0; $i < $size_of_prev_rules; $i++) {
							if ($rule["prev"][$i] == $best["css_rule"]["prev"][$i])
								$same = true;
							else {
								$same = false;
								break;
							}
						}
						if ($same == true)
							break;
					}
				}
			}

			if ($same == false)
				array_push($css_array, $best["css_rule"]);
		}

		return (is_array($best) && isset($best["value"])) ? $best["value"] : null;

	}

	/*
	It takes in input two data structures representing two css rules
	(every frame contains the value of property and the number of id, class, and tag content in the selector)
	Returns the rule that has highest priority according to the type of selectors
	If the two rules have the same priority, it returns the position with more
	*/
	public static function getPriorityInfo($info1, $info2)
	{

		if (!is_array($info1))
			return is_array($info2) ? $info2 : null;
		if (!is_array($info2))
			return $info1;

		$v1 = isset($info1["value"]) ? (string) $info1["value"] : "";
		$v2 = isset($info2["value"]) ? (string) $info2["value"] : "";

		if (stripos($v1, "!important") !== false && stripos($v2, "!important") === false) {
			$best = $info1;
		} elseif (stripos($v1, "!important") === false && stripos($v2, "!important") !== false) {
			$best = $info2;
		} else {
			$id1 = isset($info1["id_count"]) ? (int) $info1["id_count"] : 0;
			$id2 = isset($info2["id_count"]) ? (int) $info2["id_count"] : 0;

			if ($id1 > $id2) {
				$best = $info1;
			} elseif ($id1 < $id2) {
				$best = $info2;
			} else {
				$c1 = isset($info1["class_count"]) ? (int) $info1["class_count"] : 0;
				$c2 = isset($info2["class_count"]) ? (int) $info2["class_count"] : 0;

				if ($c1 > $c2) {
					$best = $info1;
				} elseif ($c1 < $c2) {
					$best = $info2;
				} else {
					$t1 = isset($info1["tag_count"]) ? (int) $info1["tag_count"] : 0;
					$t2 = isset($info2["tag_count"]) ? (int) $info2["tag_count"] : 0;

					if ($t1 > $t2) {
						$best = $info1;
					} elseif ($t1 < $t2) {
						$best = $info2;
					} else {
						$css1 = (isset($info1["css_rule"]) && isset($info1["css_rule"]["css_id"])) ? (int) $info1["css_rule"]["css_id"] : 0;
						$css2 = (isset($info2["css_rule"]) && isset($info2["css_rule"]["css_id"])) ? (int) $info2["css_rule"]["css_id"] : 0;

						if ($css1 > $css2)
							$best = $info1;
						elseif ($css1 < $css2)
							$best = $info2;
						else {
							$pos1 = (isset($info1["css_rule"]) && isset($info1["css_rule"]["position"])) ? (int) $info1["css_rule"]["position"] : 0;
							$pos2 = (isset($info2["css_rule"]) && isset($info2["css_rule"]["position"])) ? (int) $info2["css_rule"]["position"] : 0;

							if ($pos1 > $pos2)
								$best = $info1;
							else
								$best = $info2;
						}
					}
				}
			}
		}
		return $best;
	}

	/*
	 * Check for text-decoration: blink
	 */
	public static function check_blink($e, $content_dom)
	{

		$inline_style = BasicChecks::get_p_css($e, "text-decoration", $b);
		if (strpos($inline_style, "blink") !== false)
			return false;

		return true;
	}

	/**
	 * Function that tries to separate the structure of a style (internal or external) and derives from the selectors and attributes
	 */
	public static function GetCSSDom($css_content, $css_index)
	{

		global $selectors;
		global $attributes;
		global $selector_attribute;

		// remove comments
		$css_content = preg_replace('/\/\*(.|\s)*?\*\//', '', $css_content);

		/* Inserted at the beginning of the CSS code brace '}' to facilitate
				the extraction of elements: each reading taken from '}' to '}' */
		$css_content = '}' . $css_content;
		$i = 0;
		$attribute = array();
		while (preg_match('/}([^}]*)}/i', $css_content, $element)) {
			$element[1] = $element[1] . '}';
			$css_content = substr($css_content, strlen($element[1]));
			$element[$i] = trim($element[1]);
			$selector = substr($element[1], 0, strpos($element[1], '{'));
			$selectors[$css_index][$i] = trim($selector) . "{";

			$replaced = preg_replace('/(.*)\{/', '', $element[1]);
			$replaced = preg_replace('/(.*)\}/', '', $replaced);
			$attribute[0] = "{" . $replaced . "}";
			$attribute[1] = $replaced;

			if (count($attribute) > 0) {
				$attribute[1] = trim($attribute[1]);
				$attributes[$css_index][$i] = $attribute[1];
			}
			$count = 0;
			while (preg_match('/^([^;]*);/i', $attributes[$css_index][$i], $single)) {
				$attributes[$css_index][$i] = substr($attributes[$css_index][$i], strlen($single[1]) + 1);
				$selector_attribute[$css_index][$i][$count] = trim($single[1]);
				// controls to eliminate the white spaces by the selectors
				$space_position = strpos($selector_attribute[$css_index][$i][$count], ':');
				$string_before = substr($selector_attribute[$css_index][$i][$count], 0, $space_position);
				$string_before = trim($string_before);
				$string_after = substr($selector_attribute[$css_index][$i][$count], $space_position + 1, strlen($selector_attribute[$css_index][$i][$count]) - strlen($string_before) - 1);
				$string_after = trim($string_after);
				$selector_attribute[$css_index][$i][$count] = $string_before . ':' . $string_after;
				$selector_attribute[$css_index][$i][$count] = trim($selector_attribute[$css_index][$i][$count]);

				$count++;
			}
			$i++;
		}

	}
	// return the property value of $val in inline style $style
	public static function GetElementStyleInline($style, $val)
	{
		// create an array containing all the rules are separated by ";"
		$rules_raw = explode(";", $style);
		$rules_parsed = array();
		$property_value = "";

		$i = 0;
		foreach ($rules_raw as $rule) {
			// break every rule, separated by ':' in: property => value
			$rule_trimmed = is_null($rule) ? '' : trim((string) $rule);
			if ($rule_trimmed === '')
				continue;

			$parts = explode(":", $rule_trimmed, 2);
			$prop = isset($parts[0]) ? trim($parts[0]) : '';
			$value = isset($parts[1]) ? trim($parts[1]) : '';

			if ($prop === '')
				continue;

			if (isset($rules_parsed[$prop]) && is_array($rules_parsed[$prop]) && isset($rules_parsed[$prop]["val"]) && stripos((string) $rules_parsed[$prop]["val"], "!important") !== false) {
				if (stripos((string) $value, "!important") !== false) {
					$rules_parsed[$prop]["val"] = $value;
					$rules_parsed[$prop]["pos"] = trim((string) $i);
				}

			} else {
				$rules_parsed[$prop]["val"] = $value;
				$rules_parsed[$prop]["pos"] = trim((string) $i);
			}
			$i++;
		}
		// Find if the property $val is defined and returned
		switch ($val) {
			case "margin-top":
				if (isset($rules_parsed[$val]) || isset($rules_parsed["margin"]))
					$property_value = BasicChecks::getTop(BasicChecks::get_priority_prop($rules_parsed[$val], $rules_parsed["margin"]));
				break;

			case "margin-bottom":
				if (isset($rules_parsed[$val]) || isset($rules_parsed["margin"]))
					$property_value = BasicChecks::getBottom(BasicChecks::get_priority_prop($rules_parsed[$val], $rules_parsed["margin"]));
				break;

			case "margin-left":
				if (isset($rules_parsed[$val]) || isset($rules_parsed["margin"]))
					$property_value = BasicChecks::getLeft(BasicChecks::get_priority_prop($rules_parsed[$val], $rules_parsed["margin"]));
				break;

			case "margin-right":
				if (isset($rules_parsed[$val]) || isset($rules_parsed["margin"]))
					$property_value = BasicChecks::getRight(BasicChecks::get_priority_prop($rules_parsed[$val], $rules_parsed["margin"]));
				break;

			case "padding-top":
				if (isset($rules_parsed[$val]) || isset($rules_parsed["padding"]))
					$property_value = BasicChecks::getTop(BasicChecks::get_priority_prop($rules_parsed[$val], $rules_parsed["padding"]));
				break;

			case "padding-bottom":
				if (isset($rules_parsed[$val]) || isset($rules_parsed["padding"]))
					$property_value = BasicChecks::getBottom(BasicChecks::get_priority_prop($rules_parsed[$val], $rules_parsed["padding"]));
				break;

			case "padding-left":
				if (isset($rules_parsed[$val]) || isset($rules_parsed["padding"]))
					$property_value = BasicChecks::getLeft(BasicChecks::get_priority_prop($rules_parsed[$val], $rules_parsed["padding"]));
				break;

			case "padding-right":
				if (isset($rules_parsed[$val]) || isset($rules_parsed["padding"]))
					$property_value = BasicChecks::getRight(BasicChecks::get_priority_prop($rules_parsed[$val], $rules_parsed["padding"]));
				break;

			case "background-color":
				// Check if there is a background image, if the property exists set to -1
				if (isset($rules_parsed["background-image"]))
					$property_value = "-1";
				elseif (isset($rules_parsed["background"]) && is_array($rules_parsed["background"]) && isset($rules_parsed["background"]["val"]) && stripos((string) $rules_parsed["background"]["val"], "url") !== false)
					$property_value = "-1";
				elseif (isset($rules_parsed[$val]) || isset($rules_parsed["background"])) {
					$v1 = isset($rules_parsed[$val]) ? $rules_parsed[$val] : null;
					$v2 = isset($rules_parsed["background"]) ? $rules_parsed["background"] : null;
					$property_value = BasicChecks::getBgColor(BasicChecks::get_priority_prop($v1, $v2));
				}
				break;

			default:
				if (isset($rules_parsed[$val]))
					$property_value = $rules_parsed[$val]["val"];
				break;

		}

		return $property_value;

	}
	// gets the contents of the 'background / background-color
	// and returns the background color if defined
	public static function getBgColor($value_string)
	{

		$color_names = array('black', 'silver', 'gray', 'white', 'maroon', 'red', 'purple', 'fuchsia', 'green', 'lime', 'olive', 'yellow', 'navy', 'blue', 'teal', 'aqua', 'gold', 'navy');

		$values_array = explode(" ", $value_string);
		foreach ($values_array as $val) {
			if (stripos((string) $val, "#") !== false || stripos((string) $val, "rgb(") !== false) {
				return $val;
			} else // check color names
			{
				foreach ($color_names as $color)
					if (stripos((string) $val, (string) $color) !== false)
						return $color;
			}
		}

	}
	// gets the contents of the property 'margin / padding and returns the value of the margin / padding left
	public static function getLeft($value_string)
	{

		$has_important = stripos((string) $value_string, "!important");
		// remove if there is! important and attach at the end
		if ($has_important !== false) {
			$value_string = str_ireplace("!important", "", $value_string);
			$value_string = trim($value_string);
		}
		$values_array = explode(" ", $value_string);
		$size = sizeof($values_array);
		if ($size <= 0)
			return "";
		else
			$return_value = $values_array[$size - 1]; // last value, then left


		if ($has_important === false)
			return $return_value;
		else
			return "" . $return_value . " !important";

	}
	// gets the contents of the property margin / padding and returns the value of the margin / padding right
	public static function getRight($value_string)
	{
		$has_important = stripos((string) $value_string, "!important");
		// if there is !important, remove it
		if ($has_important !== false) {
			$value_string = str_ireplace("!important", "", $value_string);
			$value_string = trim($value_string);
		}

		$values_array = explode(" ", $value_string);
		$size = sizeof($values_array);
		if ($size <= 0)
			return "";
		else {
			if ($size >= 2)
				$return_value = $values_array[1]; // second value, then right
			else

				$return_value = $values_array[0]; // first value
		}

		if ($has_important === false)
			return $return_value;
		else
			return "" . $return_value . " !important";

	}
	// gets the contents of the property margin / padding and returns the value of the margin / padding top
	public static function getTop($value_string)
	{

		$has_important = stripos((string) $value_string, "!important");
		// if there 'remove !important
		if ($has_important !== false) {
			$value_string = str_ireplace("!important", "", $value_string);
			$value_string = trim($value_string);
		}

		$values_array = explode(" ", $value_string);
		if (sizeof($values_array) <= 0)
			return "";
		else
			$return_value = $values_array[0]; // first value, then top


		if ($has_important === false)
			return $return_value;
		else
			return "" . $return_value . " !important";

	}
	// gets the contents of the property margin / padding and returns the value of the margin / padding bottom
	public static function getBottom($value_string)
	{

		$has_important = stripos((string) $value_string, "!important");
		// if there 'remove !important
		if ($has_important !== false) {
			$value_string = str_ireplace("!important", "", $value_string);
			$value_string = trim($value_string);
		}

		$values_array = explode(" ", $value_string);
		$size = sizeof($values_array);
		if ($size <= 0)
			return "";
		else {
			if ($size >= 3)
				$return_value = $values_array[2]; // third value, then bottom
			else
				$return_value = $values_array[$size - 1]; // second or first value
		}

		if ($has_important === false)
			return $return_value;
		else
			return "" . $return_value . " !important";

	}
	// function to parameterize the search in the style sheets id, class or generic elements (tags).
	// $marker contains "#", ". " or "" for id, respectively, classes, or generics.
	public static function getElementStyleGeneric($e, $marker, $tag, $val)
	{

		global $selector_storage;
		$property_info = null;
		$element = $marker . $tag;

		if (isset($selector_storage[$marker . $tag])) {
			$selector_subset = $selector_storage[$marker . $tag];
			$property_info = BasicChecks::get_proprieta($selector_subset, $val, $e, $marker . $tag);
		}

		return $property_info;

	}
	// returns the value of the property priority of higher based on location or "! important"
	// for example is used to those rules that contain both the definition of margin and margin-top
	public static function get_priority_prop($rule1, $rule2)
	{

		if (!is_array($rule1) && !is_array($rule2))
			return "";

		if (!is_array($rule1) || !isset($rule1["val"]))
			$new_property_value = (is_array($rule2) && isset($rule2["val"])) ? $rule2["val"] : "";
		elseif (!is_array($rule2) || !isset($rule2["val"]))
			$new_property_value = (is_array($rule1) && isset($rule1["val"])) ? $rule1["val"] : "";
		elseif (stripos((string) $rule1["val"], "!important") === false && stripos((string) $rule2["val"], "!important") === false) {
			if (isset($rule1["pos"]) && isset($rule2["pos"]) && $rule1["pos"] > $rule2["pos"])
				$new_property_value = $rule1["val"];
			else
				$new_property_value = (is_array($rule2) && isset($rule2["val"])) ? $rule2["val"] : "";
		} elseif (stripos((string) $rule1["val"], "!important") !== false) {
			$new_property_value = $rule1["val"];
		} else {
			$new_property_value = (is_array($rule2) && isset($rule2["val"])) ? $rule2["val"] : "";
		}

		return (string) $new_property_value;
	}
	/*
		$selector_subset contains all the rules (simple and compound) that ultimately
		position of the selectors of the rule (eg for elem p: p {} div> p {}. class {p}), the element
		$root_element (ie, a tag, id or class)
		$val = property to search
		original_element = $item itself, necessary to verify the association of rules made,
		checking the children ($ e-> parent () for "or "> ", $ e-> prev_sibling () for" + ")
	*/
	public static function get_proprieta($selector_subset, $val, $original_element, $root_element)
	{

		global $selector_storage;
		$property_value = null;
		$id_count = 0;
		$class_count = 0;
		$tag_count = 0;
		$rule_count = 0;
		// use the foreach to track the location of the rule priority associated with $root_element

		$space = "{_}"; // used for cases in which a space between the two is significant. eg: "div.class" and "div .class"
		foreach ($selector_subset as $rules_array) {
			// Check if [$rules_array]['regole'] contained the property' $val and store it in $new_property_value
			// use a case for special properties like margin and padding
			// for these properties' function BasicChecks: get_priority_prop consider what property has priority more
			// eg between margin and margin-top (that is, if one then overwrite the other)

			$new_id_count = 0;
			$new_class_count = 0;
			$new_tag_count = 0;
			$new_property_value = null;
			// NOTE: This switch may be included in a function also reused getElementStyleInline
			switch ($val) {

				case "margin-top":
					if (isset($rules_array["regole"][$val]) || isset($rules_array["regole"]["margin"])) {
						$rv1 = isset($rules_array["regole"][$val]) ? $rules_array["regole"][$val] : null;
						$rv2 = isset($rules_array["regole"]["margin"]) ? $rules_array["regole"]["margin"] : null;
						$new_property_value = BasicChecks::getTop(BasicChecks::get_priority_prop($rv1, $rv2));
					}
					break;

				case "margin-bottom":
					if (isset($rules_array["regole"][$val]) || isset($rules_array["regole"]["margin"])) {
						$rv1 = isset($rules_array["regole"][$val]) ? $rules_array["regole"][$val] : null;
						$rv2 = isset($rules_array["regole"]["margin"]) ? $rules_array["regole"]["margin"] : null;
						$new_property_value = BasicChecks::getBottom(BasicChecks::get_priority_prop($rv1, $rv2));
					}
					break;

				case "margin-left":
					if (isset($rules_array["regole"][$val]) || isset($rules_array["regole"]["margin"])) {
						$rv1 = isset($rules_array["regole"][$val]) ? $rules_array["regole"][$val] : null;
						$rv2 = isset($rules_array["regole"]["margin"]) ? $rules_array["regole"]["margin"] : null;
						$new_property_value = BasicChecks::getLeft(BasicChecks::get_priority_prop($rv1, $rv2));
					}
					break;

				case "margin-right":
					if (isset($rules_array["regole"][$val]) || isset($rules_array["regole"]["margin"])) {
						$rv1 = isset($rules_array["regole"][$val]) ? $rules_array["regole"][$val] : null;
						$rv2 = isset($rules_array["regole"]["margin"]) ? $rules_array["regole"]["margin"] : null;
						$new_property_value = BasicChecks::getRight(BasicChecks::get_priority_prop($rv1, $rv2));
					}
					break;

				case "padding-top":
					if (isset($rules_array["regole"][$val]) || isset($rules_array["regole"]["padding"])) {
						$rv1 = isset($rules_array["regole"][$val]) ? $rules_array["regole"][$val] : null;
						$rv2 = isset($rules_array["regole"]["padding"]) ? $rules_array["regole"]["padding"] : null;
						$new_property_value = BasicChecks::getTop(BasicChecks::get_priority_prop($rv1, $rv2));
					}
					break;

				case "padding-bottom":
					if (isset($rules_array["regole"][$val]) || isset($rules_array["regole"]["padding"])) {
						$rv1 = isset($rules_array["regole"][$val]) ? $rules_array["regole"][$val] : null;
						$rv2 = isset($rules_array["regole"]["padding"]) ? $rules_array["regole"]["padding"] : null;
						$new_property_value = BasicChecks::getBottom(BasicChecks::get_priority_prop($rv1, $rv2));
					}
					break;

				case "padding-left":
					if (isset($rules_array["regole"][$val]) || isset($rules_array["regole"]["padding"])) {
						$rv1 = isset($rules_array["regole"][$val]) ? $rules_array["regole"][$val] : null;
						$rv2 = isset($rules_array["regole"]["padding"]) ? $rules_array["regole"]["padding"] : null;
						$new_property_value = BasicChecks::getLeft(BasicChecks::get_priority_prop($rv1, $rv2));
					}
					break;

				case "padding-right":
					if (isset($rules_array["regole"][$val]) || isset($rules_array["regole"]["padding"])) {
						$rv1 = isset($rules_array["regole"][$val]) ? $rules_array["regole"][$val] : null;
						$rv2 = isset($rules_array["regole"]["padding"]) ? $rules_array["regole"]["padding"] : null;
						$new_property_value = BasicChecks::getRight(BasicChecks::get_priority_prop($rv1, $rv2));
					}
					break;

				case "background-color":
					// Check if there is a background image, if the property exists set to -1
					if (isset($rules_array["regole"]["background-image"]))
						$new_property_value = "-1";
					elseif (isset($rules_array["regole"]["background"]) && stripos((string) $rules_array["regole"]["background"]["val"], "url") !== false)
						$new_property_value = "-1";
					elseif (isset($rules_array["regole"][$val]) || isset($rules_array["regole"]["background"])) {
						$rv1 = isset($rules_array["regole"][$val]) ? $rules_array["regole"][$val] : null;
						$rv2 = isset($rules_array["regole"]["background"]) ? $rules_array["regole"]["background"] : null;
						$new_property_value = BasicChecks::getBgColor(BasicChecks::get_priority_prop($rv1, $rv2));
					}
					break;

				default:
					if (isset($rules_array["regole"][$val]))
						$new_property_value = $rules_array["regole"][$val]["val"];
					break;

			}

			$result = null;
			// if the value of a property was found, confirm it can be applied to the element considered
			if ($new_property_value != null) {

				if (stripos($root_element, "#") !== false)
					$new_id_count = 1;
				elseif (stripos($root_element, ".") !== false)
					$new_class_count = 1;
				else
					$new_tag_count = 1;

				$num_rules = sizeof($rules_array["prev"]);
				if ($num_rules == 1) // the current rule is "simple", there are no predecessors
				{
					$result = true;

				} else  // the rule is "compound" (ie: div > p a)
				{

					// verification takes into account that a compound rule takes precedence over a simple rule, even if it follows!
					// eg: "div > p {}" & "{p}" => to <div><p></p></ div> wins over "div > p {}"
					// check whether the item falls under the "compound"
					// if so, I check if [$ rule] ['rules'] contained the $ val

					$i = 1; // start from the first parent of the current element
					$e = $original_element;

					while ($i < $num_rules && $result !== false) {
						// NOTE: This series of if / elseif and switch could be next
						// be merged into a single set of if / else
						// $ element can 'contain' '>', '+', id, class, a tag
						if ($rules_array["prev"][$i] == ">") {
							$type = ">";
						} elseif ($rules_array["prev"][$i] == "+") {
							$type = "+";

						} elseif ($rules_array["prev"][$i] == $space) {
							$type = "space";
						} elseif (stripos($rules_array["prev"][$i], ".") !== false) // class
						{
							$type = "class";

						} elseif (stripos($rules_array["prev"][$i], "#") !== false) // id
						{
							$type = "id";
						} else // tag
						{
							$type = "tag";

						}

						switch ($type) {
							case ">":
								// cases: div > p, #id > p, .class > p
								if (stripos($rules_array["prev"][$i + 1], "#") !== false) {
									$e = $e->parent();
									// id: check that the predecessor has the id of the rule


									if ($e != null && $e->id == str_replace('#', '', $rules_array["prev"][$i + 1])) {
										$result = true;
										$new_id_count++;
									} else
										$result = false;
								} elseif (stripos($rules_array["prev"][$i + 1], ".") !== false) {
									$e = $e->parent();
									// class: check that the predecessor has the class rule
									if ($e != null && $e->class == str_replace('.', '', $rules_array["prev"][$i + 1])) {
										$result = true;
										$new_class_count++;
									} else
										$result = false;
								} else {
									$e = $e->parent();
									// tag: check that the predecessor is the tag of the rule
									if ($e != null && $e->tag == $rules_array["prev"][$i + 1]) {
										$result = true;
										$new_tag_count++;
									} else
										$result = false;
								}
								$i++;
								break;

							case "+":
								if (stripos($rules_array["prev"][$i + 1], "#") !== false) {
									$e->prev_sibling();
									// id: check that the predecessor has the id of the rule
									if ($e != null && $e->id == str_replace('#', '', $rules_array["prev"][$i + 1])) {
										$result = true;
										$new_id_count++;
									} else
										$result = false;
								} elseif (stripos($rules_array["prev"][$i + 1], ".") !== false) {
									$e->prev_sibling();
									// class: check that his predecessor has the class rule
									if ($e != null && $e->class == str_replace('.', '', $rules_array["prev"][$i + 1])) {
										$result = true;
										$new_class_count++;
									} else
										$result = false;
								} else {
									$e->prev_sibling();
									// tag: check that the predecessor is the tag of the rule
									if ($e != null && $e->tag == $rules_array["prev"][$i + 1]) {
										$result = true;
										$new_tag_count++;
									} else
										$result = false;
								}
								$i++;
								break;

							case "space":
								// cases: div #id, #id #id, .class #id, div .class, #id .class, .class .class
								if (stripos($rules_array["prev"][$i + 1], "#") !== false) {

									$e = $e->parent();
									while ($e != null && $e->id != str_replace('#', '', $rules_array["prev"][$i + 1]))
										$e = $e->parent();
									// id: check that the predecessor has the id of the rule
									if ($e != null && $e->id == str_replace('#', '', $rules_array["prev"][$i + 1])) {
										$result = true;
										$new_id_count++;
									} else
										$result = false;
								} elseif (stripos($rules_array["prev"][$i + 1], ".") !== false) {
									$e = $e->parent();
									while ($e != null && $e->class != str_replace('.', '', $rules_array["prev"][$i + 1]))
										$e = $e->parent();
									// class: check that his predecessor has the class rule
									if ($e != null && $e->class == str_replace('.', '', $rules_array["prev"][$i + 1])) {
										$result = true;
										$new_class_count++;
									} else
										$result = false;
								} else {
									$e = $e->parent();
									while ($e != null && $e->tag != $rules_array["prev"][$i + 1])
										$e = $e->parent();
									// tag: check that the predecessor is the tag of the rule
									if ($e != null && $e->tag == $rules_array["prev"][$i + 1]) {
										$result = true;
										$new_tag_count++;
									} else
										$result = false;
								}
								$i++;
								break;

							case "tag":
								// cases: p.class, p#id, div p
								if (stripos($rules_array["prev"][$i - 1], ".") !== false) // p.class
								{
									if ($e->tag == $rules_array["prev"][$i]) {
										$result = true;
										$new_tag_count++;
									} else
										$result = false;

								} elseif (stripos($rules_array["prev"][$i - 1], "#") !== false) // p#id
								{
									if ($e->tag == $rules_array["prev"][$i]) {
										$result = true;
										$new_tag_count++;
									} else
										$result = false;
								} else // div p
								{
									while ($e != null && $e->tag != $rules_array["prev"][$i]) {

										$e = $e->parent();
									}
									// tag: check that the predecessor is the tag of the rule
									if ($e != null) {
										$result = true;
										$new_tag_count++;
									} else
										$result = false;

								}

								break;

							case "id":
								// cases: #id p, #id .class, #id #id
								while ($e != null && $e->id != str_replace('#', '', $rules_array["prev"][$i])) {

									$e = $e->parent();
								}
								// tag: check that the predecessor is the tag of the rule
								if ($e != null) {
									$result = true;
									$new_tag_count++;
								} else
									$result = false;

								break;

							case "class":
								// cases: .id p, .id .class, .id #id
								while ($e != null && $e->class != str_replace('.', '', $rules_array["prev"][$i])) {
									$e = $e->parent();
								}
								// tag: check that the predecessor is the tag of the rule
								if ($e != null) {
									$result = true;
									$new_tag_count++;

								} else
									$result = false;

								break;

						} // end case
						$i++;

					} // end while


				} //end else regola composta - compound rule


				if ($result == true) { // analyze and apply the new rule
					// check if the priority of the new greater than previous
					$new_important = stripos((string)$new_property_value, "!important") !== false;
					$old_important = stripos((string)$property_value, "!important") !== false;

					if ($new_important && !$old_important) {
						// $property_value is not !important while $new_property_value is, then override $property_value
						$property_value = $new_property_value;
						$id_count = $new_id_count;
						$class_count = $new_class_count;
						$tag_count = $new_tag_count;
						$best_rule_index = $rule_count;
					} elseif ((!$new_important && !$old_important) || ($new_important && $old_important))

					// have both are !important or niether is, then check the id
					{

						if ($new_id_count > $id_count) {
							$property_value = $new_property_value;
							$id_count = $new_id_count;
							$class_count = $new_class_count;
							$tag_count = $new_tag_count;
							$best_rule_index = $rule_count;
						} elseif ($new_id_count == $id_count) { // same ID number, control the number of class


							if ($new_class_count > $class_count) {
								$property_value = $new_property_value;
								$id_count = $new_id_count;
								$class_count = $new_class_count;
								$tag_count = $new_tag_count;
								$best_rule_index = $rule_count;
							} elseif ($new_class_count == $class_count) { // same id and class number, check number of tags


								if ($new_tag_count >= $tag_count) { // same or greater number of id, class and tags: is the priority of the new rule
									$property_value = $new_property_value;
									$id_count = $new_id_count;
									$class_count = $new_class_count;
									$tag_count = $new_tag_count;
									$best_rule_index = $rule_count;
								}

							}
						}
					}

					$new_property_value = null;
				}

			}

			$rule_count++;
		}

		if ($property_value == null)
			return null;

		// create the structure property_info
		// store the id number, class and tag necessary to verify the priority
		// rules are starting from an id, a class or a tag
		// it is not always a rule that has the selectors as the last (or only) a descendant of id or a class that takes
		// ends with a tag

		$property_info = array("value" => $property_value, "id_count" => $id_count, "class_count" => $class_count, "tag_count" => $tag_count, "css_rule" => $selector_subset[$best_rule_index]);

		return $property_info;
	}
	// reorganize the style sheets into a structured data format
	public static function setCssSelectors($content_dom)
	{

		global $selectors;
		global $selector_attribute;
		global $selector_storage;
		global $selector_storage_flag;

		if (isset($selector_storage_flag)) {
			return;
		} else {
			$selector_storage_flag = true;
		}

		$space = "{_}";

		$css_list = BasicChecks::get_style_external($content_dom);
		$internal_css = BasicChecks::get_style_internal($content_dom);
		// Create the data structure containing the CSS information
		BasicChecks::prepare_css_arrays($css_list, $internal_css);

		$num_selectors = is_array($selectors) ? sizeof($selectors) : 0;
		for ($css_id = 0; $css_id < $num_selectors; $css_id++) {

			$selector_count = is_array($selectors[$css_id]) ? count($selectors[$css_id]) : 0;
			for ($i = 0; $i < $selector_count; $i++) {
				$selector_string = str_ireplace('{', '', $selectors[$css_id][$i]); // remove "{"

				$selector_string = str_ireplace('>', ' > ', $selector_string); // put spaces around ">"
				$selector_string = str_ireplace('+', ' + ', $selector_string); // put spaces around "+"
				
				// use the $space symbol to indicate that an id or class is preceded by a space
				$selector_string = str_ireplace(' .', ' ' . $space . '.', $selector_string); // put a space before "."
				$selector_string = str_ireplace(' #', ' ' . $space . '#', $selector_string); // put a space before "#"

				$selector_string = str_ireplace('.', ' .', $selector_string); // put a space before "."
				$selector_string = str_ireplace('#', ' #', $selector_string); // put a space before "#"
				
				while (stripos($selector_string, '  ') !== false) // remove multiple spaces
				{
					$selector_string = str_ireplace('  ', ' ', $selector_string);
				}

				// remove redundant space markers
				$selector_string = str_ireplace('> ' . $space, '>', $selector_string);

				$selectors_array = explode(',', $selector_string);  // create an array of selectors separated by ","

				foreach ($selectors_array as $sel) {
					$sel = trim($sel);
					// remove space markers at beginning of string
					$sel = preg_replace("/^" . $space . "/", "", $sel);
					// remove space markers at end of string
					$sel = preg_replace("/" . $space . "$/", "", $sel);
					$sel = trim($sel);
					$selector_array = explode(" ", $sel);
					
					// in the final position of $selector_array is the rightmost selector before a "," or "{"
					$size_of_selector = sizeof($selector_array) - 1;
					$last = $selector_array[$size_of_selector]; // rightmost element, eg: "div > p br" ---> br

					$temp_array = array();
					$temp_array["css_id"] = $css_id;
					$temp_array["position"] = $i;
					
					// "regole" contains: property => value
					$rules = (is_array($selector_attribute) && isset($selector_attribute[$css_id][$i])) ? $selector_attribute[$css_id][$i] : null;

					$temp_array["regole"] = array();
					if (is_array($rules)) {
						$count_rule = 0;
						foreach ($rules as $rule) {
							$property_value = explode(":", $rule, 2);
							$property = isset($property_value[0]) ? trim($property_value[0]) : '';
							$value = isset($property_value[1]) ? trim($property_value[1]) : '';
							if ($property !== '') {
								$temp_array["regole"][$property]["val"] = $value;
								$temp_array["regole"][$property]["pos"] = $count_rule;
							}
							$count_rule++;
						}
					}
					
					// predecessors: store from right to left
					$temp_array["prev"] = array();
					$j = 0;
					for ($count = $size_of_selector; $count >= 0; $count--) {
						$temp_array["prev"][$j] = $selector_array[$count];
						$j++;
					}

					if (!isset($selector_storage[$last]))
						$selector_storage[$last] = array();

					array_push($selector_storage[$last], $temp_array);
				}
			}
		}
	}

	// Function to search within a particular attribute associated with an id tag
	public static function GetElementStyleId($e, $id, $val)
	{

		return BasicChecks::getElementStyleGeneric($e, '#', $id, $val);
	}
	// A function that searches for a particular attribute within the class associated with a tag in an external style sheet
	public static function GetElementStyleClass($e, $class, $val)
	{

		return BasicChecks::getElementStyleGeneric($e, '.', $class, $val);
	}
	// A function that searches for a particular attribute in a tag identified by the selector in an external style sheet
	public static function GetElementStyle($e, $child, $val)
	{
		return BasicChecks::getElementStyleGeneric($e, '', $child, $val);
	}
	// Function for requirement 21 which retrieves the vertical distance values of a list item
	public static function GetVerticalDistance($e)
	{

		global $m_bottom;
		global $p_bottom;
		global $m_top;
		global $p_top;

		$m_bottom = "";
		$p_bottom = "";
		$m_top = "";
		$p_top = "";

		$m_bottom = BasicChecks::get_p_css($e->prev_sibling(), "margin-bottom");
		$p_bottom = BasicChecks::get_p_css($e->prev_sibling(), "padding-bottom");
		$m_top = BasicChecks::get_p_css($e, "margin-top");
		$p_top = BasicChecks::get_p_css($e, "padding-top");

		$m_bottom = trim(str_ireplace("!important", "", $m_bottom));
		$p_bottom = trim(str_ireplace("!important", "", $p_bottom));
		$m_top = trim(str_ireplace("!important", "", $m_top));
		$p_top = trim(str_ireplace("!important", "", $p_top));

	}
	// Function for requirement 21 which retrieves the horizontal distance values of a list item
	public static function GetHorizontalDistance($e)
	{

		global $m_left;
		global $p_left;
		global $m_right;
		global $p_right;

		$m_left = "";
		$p_left = "";
		$m_right = "";
		$p_right = "";

		$m_right = BasicChecks::get_p_css($e->prev_sibling(), "margin-right");
		$p_right = BasicChecks::get_p_css($e->prev_sibling(), "padding-right");
		$m_left = BasicChecks::get_p_css($e->prev_sibling(), "margin-left");
		$p_left = BasicChecks::get_p_css($e->prev_sibling(), "padding-left");

		$m_right = trim(str_ireplace("!important", "", $m_right));
		$p_right = trim(str_ireplace("!important", "", $p_right));
		$m_left = trim(str_ireplace("!important", "", $m_left));
		$p_left = trim(str_ireplace("!important", "", $p_left));

	}
	// Function for requirement 21 which retrieves the values of distance down the listings vetical
	//Funzione per il requsitio 21 che recupera i valori di distanza veticali basso delle liste
	public static function GetVerticalListBottomDistance($tag)
	{

		global $m_bottom;
		global $p_bottom;
		$m_bottom = "";
		$p_bottom = "";

		$m_bottom = BasicChecks::get_p_css($tag, "margin-bottom");
		$p_bottom = BasicChecks::get_p_css($tag, "padding-bottom");
		$m_bottom = trim(str_ireplace("!important", "", $m_bottom));
		$p_bottom = trim(str_ireplace("!important", "", $p_bottom));

	}
	// Function for requirement  21 which retrieves the values of distance Vetical top of the lists
	//Funzione per il requsitio 21 che recupera i valori di distanza veticali alto delle liste
	public static function GetVerticalListTopDistance($tag)
	{

		global $m_top;
		global $p_top;
		$m_top = "";
		$p_top = "";
		$m_top = BasicChecks::get_p_css($tag, "margin-top");
		$p_top = BasicChecks::get_p_css($tag, "padding-top");
		$m_top = trim(str_ireplace("!important", "", $m_top));
		$p_top = trim(str_ireplace("!important", "", $p_top));

	}
	// Function for requirment 21 which retrieves the values of horizontal distance from the left of the lists
	//Funzione per il requsitio 21 che recupera i valori di distanza orizzontale sinistra delle liste
	public static function GetHorizontalListLeftDistance($tag)
	{

		global $m_left;
		global $p_left;
		$m_left = "";
		$p_left = "";
		$m_left = BasicChecks::get_p_css($tag, "margin-left");
		$p_left = BasicChecks::get_p_css($tag, "padding-left");
		$m_left = trim(str_ireplace("!important", "", $m_left));
		$p_left = trim(str_ireplace("!important", "", $p_left));
	}
	// Function for requirment 21 which retrieves the values of horizontal distance right of the list
	//Funzione per il requsitio 21 che recupera i valori di distanza orizzontale destra delle liste
	public static function GetHorizontalListRightDistance($tag)
	{

		global $m_right;
		global $p_right;
		$m_right = "";
		$p_right = "";
		$m_right = BasicChecks::get_p_css($tag, "margin-right");
		$p_right = BasicChecks::get_p_css($tag, "padding-right");
		$m_right = trim(str_ireplace("!important", "", $m_right));
		$p_right = trim(str_ireplace("!important", "", $p_right));
	}

	public static function getForegroundA($e, $link_sel)
	{
		// Find the value of foreground explicitly defined for the link element $e
		//cerco il valore di foreground esplicitamente definito per l'elemento link $e
		$foreground = BasicChecks::get_p_css_a($e, "color", $link_sel);
		if ($foreground == null) {
			$foreground = "";
		}

		if ($foreground == "" || $foreground == null) {
			$foreground = BasicChecks::getForeground($e);
		}

		$foreground = str_replace("'", "", (string) $foreground);
		$foreground = str_replace("\"", "", (string) $foreground);
		$foreground = str_replace("!important", "", (string) $foreground);
		return $foreground;

	}

	public static function getBackgroundA($e, $link_sel)
	{
		// Find the value of explicitly defined background for the element $e
		$background = BasicChecks::get_p_css_a($e, "background-color", $link_sel);
		if ($background == null) {
			$background = "";
		}

		if ($background == "" || $background == null) {
			$background = BasicChecks::getBackground($e);
		}

		$background = str_replace("'", "", $background);
		$background = str_replace("\"", "", $background);
		$background = str_replace("!important", "", $background);
		return $background;

	}

	public static function getForeground($e)
	{
		// Find the value of foreground explicitly defined for the element $e
		$foreground = BasicChecks::get_p_css($e, "color");

		// If it's a link and no color is defined, use browser default (usually blue)
		if ($foreground == "" && $e->tag == "a") {
			$foreground = "#0000ee";
		}

		// for the normal elements if foreground == "" means that the value is not defined for $e: Searches its parents
		while (($foreground == "" || $foreground == null) && $e->tag != null && $e->tag != "body" && $e->tag != "html") {
			$e = $e->parent();
			$foreground = BasicChecks::get_p_css($e, "color");
		}
		// if a foreground, is found, check if it is defined in the body, if not check the if it is  black
		// NOTE: must be added to the control link, alink, ...
		if ($foreground == "" || $foreground == null) {
			if (($e->tag == "body" || $e->tag == "html") && is_array($e->attr) && isset($e->attr["text"]))
				$foreground = (string) $e->attr["text"];
			else
				$foreground = "#000000";
		}

		$foreground = str_replace("'", "", (string) $foreground);
		$foreground = str_replace("\"", "", (string) $foreground);
		$foreground = str_replace("!important", "", (string) $foreground);
		return $foreground;

	}

	public static function getBackground($e)
	{
		// Find the value of explicitly defined background for the element $ e
		$background = BasicChecks::get_p_css($e, "background-color");

		// if background == "" or "transparent" means that the value is not defined for $e: Searches its parents
		while (($background == "" || $background == null || $background == "transparent") && $e->tag != null && $e->tag != "body" && $e->tag != "html") {
			$e = $e->parent();
			if (!$e)
				break;

			$background = BasicChecks::get_p_css($e, "background-color");
			if ($background == "" || $background == null || $background == "transparent") {
				if (($e->tag == "table" || $e->tag == "tr" || $e->tag == "td") && is_array($e->attr) && isset($e->attr["bgcolor"]))
					$background = (string) $e->attr["bgcolor"];
			}
			// if the element has an absolute position and background not defined (default: transparent)
			if (BasicChecks::get_p_css($e, "position") == "absolute" && ($background == "" || $background == null || $background == "transparent"))
				$background = -1;
		}

		// if I find any background check that is defined within the body, if not assign white
		if ($background == "" || $background == null || $background == "transparent") {

			if (($e->tag == "body" || $e->tag == "html") && is_array($e->attr) && isset($e->attr["bgcolor"]))
				$background = (string) $e->attr["bgcolor"];
			else
				$background = "#ffffff";
		}

		$background = str_replace("'", "", (string) $background);
		$background = str_replace("\"", "", (string) $background);
		$background = str_replace("!important", "", (string) $background);
		return $background;

	}
	// traverse the tree until you find a parent element that has the $property style value $value
	public static function isPropertyInherited($e, $property, $value)
	{
		// Find the value of $property explicitly defined for the element $e
		$p = BasicChecks::get_p_css($e, $property);
		// if value is not defined for $e: Searches the parents
		while (($p == "" || $p == null || $p !== $value) && $e->tag != null && $e->tag != "html") {
			$e = $e->parent();
			$p = BasicChecks::get_p_css($e, $property);
		}

		if ($p == "" || $p == null)
			return false;
		else
			return true;
	}

	// check if the item is contained in an element with absolute position outside the page
	public static function isPositionOutOfPage($e)
	{
		$p = BasicChecks::get_p_css($e, "display");
		while (($p == "" || $p == null || $p !== "none") && $e->tag != null && $e->tag != "html") {
			$e = $e->parent();
			$p = BasicChecks::get_p_css($e, "display");
		}

		if ($p == "" || $p == null)
			return false;
		else if ($p == "none") {
			$top = BasicChecks::get_p_css($e, "top");
			if (stripos($top, "-") === 0)
				return true;

			$left = BasicChecks::get_p_css($e, "left");
			if (stripos($left, "-") === 0)
				return true;

		}

		return false;
	}

	// check that the element is visible
	public static function isElementVisible($e)
	{
		// visibility:hidden or display:none
		if (BasicChecks::isPropertyInherited($e, "visibility", "hidden") || BasicChecks::isPropertyInherited($e, "display", "none"))
			return false;
		// check if the item is within an element with absolute position outside the page
		if (BasicChecks::isPositionOutOfPage($e))
			return false;

		return true;

	}
	// Return true if the value is relative (em or %)
	public static function isRelative($value)
	{

		$value = trim(str_ireplace("!important", "", $value));

		$a_value = explode(' ', $value);
		//print_r($a_value);


		foreach ($a_value as $value) {
			if ($value == "auto" || $value == ' ' || $value == 0)
				; //ok
			elseif ((substr($value, strlen($value) - 2, 2) != "em") && (substr($value, strlen($value) - 1, 1) != "%") && (substr($value, strlen($value) - 2, 2) != "px"))
				return false;
			//else
			//	return true;
		}
		return true;
	}
	// Check if the property $val associated with the element $e has a relative measurement
	public static function checkRelative($e, $val)
	{

		$fs = BasicChecks::get_p_css($e, $val);
		if ($fs != "" && $fs != null) {

			return BasicChecks::isRelative($fs);
		} else
			return true;
	}
	// Return true if the value is in pixels (px)
	public static function isPx($value)
	{

		$value = trim(str_ireplace("!important", "", $value));

		$a_value = explode(" ", $value);

		$ret = false;
		foreach ($a_value as $value) {
			if (substr($value, strlen($value) - 2, 2) == "px")
				$ret = true;
			//else
			//	return false;
		}
		return $ret;
	}
	// Check for the presence of pixels (px) in the property $val relative to element $e
	public static function checkPx($e, $val)
	{

		$fs = BasicChecks::get_p_css($e, $val);
		if ($fs != "" && $fs != null) {

			return !BasicChecks::isPx($fs);
		} else
			return true;
	}
	// Function to calculate the brightness ratio
	public static function CalculateBrightness($color1, $color2)
	{

		include_once(AC_INCLUDE_PATH . "classes/ColorValue.class.php");

		//echo("<p>CalcolateBrightness</p>");
		//echo("<p>Colori prima di ColorValue: color1=".$color1.  "color2=".$color2. "</p>");


		$color1 = new ColorValue($color1);
		$color2 = new ColorValue($color2);

		//echo("<p>Colori dopo ColorValue: color1=".$color1.  "color2=".$color2. "</p>");


		if (!$color1->isValid() || !$color2->isValid())
			return true;

		$colorR1 = $color1->getRed();
		$colorG1 = $color1->getGreen();
		$colorB1 = $color1->getBlue();

		$colorR2 = $color2->getRed();
		$colorG2 = $color2->getGreen();
		$colorB2 = $color2->getBlue();

		$brightness1 = (($colorR1 * 299) + ($colorG1 * 587) + ($colorB1 * 114)) / 1000;

		$brightness2 = (($colorR2 * 299) + ($colorG2 * 587) + ($colorB2 * 114)) / 1000;

		$difference = 0;
		if ($brightness1 > $brightness2) {
			$difference = $brightness1 - $brightness2;
		} else {
			$difference = $brightness2 - $brightness1;
		}

		return $difference;
	}

	//TOSI e VIRRUSO WCAG2
	public static function ContrastRatio($color1, $color2)
	{
		include_once(AC_INCLUDE_PATH . "classes/ColorValue.class.php");

		$color1 = new ColorValue($color1);
		$color2 = new ColorValue($color2);

		if (!$color1->isValid() || !$color2->isValid()) {
			return true;
		}

		$colorR1 = $color1->getRed() / 255;
		$colorG1 = $color1->getGreen() / 255;
		$colorB1 = $color1->getBlue() / 255;

		$colorR2 = $color2->getRed() / 255;
		$colorG2 = $color2->getGreen() / 255;
		$colorB2 = $color2->getBlue() / 255;

		if ($colorR1 <= 0.03928)
			$colorR1 = $colorR1 / 12.92;
		else
			$colorR1 = pow((($colorR1 + 0.055) / 1.055), 2.4);

		if ($colorG1 <= 0.03928)
			$colorG1 = $colorG1 / 12.92;
		else
			$colorG1 = pow((($colorG1 + 0.055) / 1.055), 2.4);

		if ($colorB1 <= 0.03928)
			$colorB1 = $colorB1 / 12.92;
		else
			$colorB1 = pow((($colorB1 + 0.055) / 1.055), 2.4);

		if ($colorR2 <= 0.03928)
			$colorR2 = $colorR2 / 12.92;
		else
			$colorR2 = pow((($colorR2 + 0.055) / 1.055), 2.4);

		if ($colorG2 <= 0.03928)
			$colorG2 = $colorG2 / 12.92;
		else
			$colorG2 = pow((($colorG2 + 0.055) / 1.055), 2.4);

		if ($colorB2 <= 0.03928)
			$colorB2 = $colorB2 / 12.92;
		else
			$colorB2 = pow((($colorB2 + 0.055) / 1.055), 2.4);

		$Lum1 = ($colorR1 * 0.2126) + ($colorG1 * 0.7152) + ($colorB1 * 0.0722);
		$Lum2 = ($colorR2 * 0.2126) + ($colorG2 * 0.7152) + ($colorB2 * 0.0722);

		$ContrastRatio = 0;
		if ($Lum1 > $Lum2) {
			$ContrastRatio = ($Lum1 + 0.05) / ($Lum2 + 0.05);
		} else {
			$ContrastRatio = ($Lum2 + 0.05) / ($Lum1 + 0.05);
		}

		return $ContrastRatio;
	}

	// resolves the font-size and converts it to pt
	public static function fontSizeToPt($e)
	{
		global $tag_size;
		$tag_size = BasicChecks::get_p_css($e, "font-size");
		while ($tag_size == null && ($e->tag != "body" && $e->tag != "html")) {
			$h = BasicChecks::checkHeadingLevel($e);
			if ($h != null && $tag_size == null) {
				$tag_size = $h * BasicChecks::fontSizeToPt($e->parent());
				// heading tag found
				return $tag_size;
			} else {
				// not a heading
				if ($e != null) {
					$e = $e->parent();
					$tag_size = BasicChecks::get_p_css($e, "font-size");
				}
			}
		}
		if ($tag_size == null) {
			$tag_size = DEFAULT_FONT_SIZE;
			return $tag_size;
		} else {
			if (substr($tag_size, -1) == "%") {
				// percent
				$s = substr($tag_size, 0, (strlen($tag_size) - 1)) / 100;

				if ($e->tag == "body" || $e->tag == "html") {
					$tag_size = DEFAULT_FONT_SIZE * $s;
					return $tag_size;
				} else {
					$tag_size = $s * BasicChecks::fontSizeToPt($e->parent());
					return $tag_size;
				}
			} else {
				// em,px,pt
				$format = substr($tag_size, -2);
				$s = substr($tag_size, 0, (strlen($tag_size) - 2));
				switch ($format) {
					case "pt":
						$tag_size = $s;
						return $tag_size;
						break;
					case "px":
						$tag_size = $s / 1.32;
						return $tag_size;
						break;
					case "em":
						if ($e->tag == "body" || $e->tag == "html") {
							$tag_size = DEFAULT_FONT_SIZE * $s;
							return $tag_size;
						} else {
							$tag_size = $s * BasicChecks::fontSizeToPt($e->parent());
							return $tag_size;
						}
						break;
					default:
						// format not supported
						$tag_size = -1;
						return -1;
				}
			}
		}
	}
	// Return the multiplication factor of heading tags
	private static function checkHeadingLevel($e)
	{
		switch ($e->tag) {
			case "h1": //def 24pt
				return 2;
			case "h2": //def 18pt
				return 1.5;
			case "h3": //def 14pt
				return 1.17;
			case "h4": //def 12pt
				return 1;
			case "h5": //def 10pt
				return 0.83;
			case "h6": //def 8pt
				return 0.67;
			default:
				return null;
		}

	}
	// Function to convert color to hex
	public static function convert_color_to_hex($color)
	{
		if ($color === null || $color === "")
			return "000000";
		$color = trim((string) $color);

		/* RGBA/RGB support */
		if (preg_match('/rgba?\s*\(\s*(\d+%?)\s*,\s*(\d+%?)\s*,\s*(\d+%?)(?:\s*,\s*[\d.]+%?)?\s*\)/i', $color, $matches)) {
			$r = $matches[1];
			$g = $matches[2];
			$b = $matches[3];

			if (strpos($r, '%') !== false)
				$r = round(intval($r) * 255 / 100);
			if (strpos($g, '%') !== false)
				$g = round(intval($g) * 255 / 100);
			if (strpos($b, '%') !== false)
				$b = round(intval($b) * 255 / 100);

			return sprintf("%02x%02x%02x", intval($r), intval($g), intval($b));
		}
		/* HSL/HSLA support */ elseif (preg_match('/hsla?\s*\(\s*(\d+)\s*,\s*(\d+)%\s*,\s*(\d+)%(?:\s*,\s*[\d.]+%?)?\s*\)/i', $color, $matches)) {
			$h = intval($matches[1]);
			$s = intval($matches[2]);
			$l = intval($matches[3]);

			return BasicChecks::hslToRgb($h, $s, $l);
		}
		/* CSS Variables support (basic Wikimedia/Codex and general fallbacks) */ elseif (preg_match('/var\s*\(\s*([^,)]+)(?:\s*,\s*([^)]+))?\s*\)/i', $color, $matches)) {
			$var_name = trim($matches[1]);
			$fallback = isset($matches[2]) ? trim($matches[2]) : "";

			// Hardcoded common Wikimedia/Codex variables for "Universal" support in this context
			$vars = [
				'--color-base' => '#202122',
				'--color-emphasized' => '#101418',
				'--color-subtle' => '#54595d',
				'--color-placeholder' => '#72777d',
				'--color-disabled' => '#a2a9b1',
				'--color-inverted' => '#fff',
				'--color-progressive' => '#36c',
				'--color-progressive--hover' => '#3056a9',
				'--color-destructive' => '#bf3c2c',
				'--background-color-base' => '#fff',
				'--background-color-subtle' => '#f8f9fa',
				'--background-color-interactive-subtle' => '#f8f9fa'
			];

			if (isset($vars[$var_name]))
				return BasicChecks::convert_color_to_hex($vars[$var_name]);
			if ($fallback !== "")
				return BasicChecks::convert_color_to_hex($fallback);

			return "000000"; // Default to black if unknown variable
		}
		/* If the color is indicated in hexadecimal, I return it as it is */ elseif (strpos($color, "#") !== false) {
			$color = str_replace("#", "", $color);
			if (strlen($color) == 3) {
				$color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
			}
			return $color;
		}
		/* The same thing I do if it is indicated with its own name */ else {
			switch (strtolower($color)) {

				case 'black':
					return '000000';
				case 'silver':
					return 'c0c0c0';
				case 'gray':
					return '808080';
				case 'white':
					return 'ffffff';
				case 'maroon':
					return '800000';
				case 'red':
					return 'ff0000';
				case 'purple':
					return '800080';
				case 'fuchsia':
					return 'ff00ff';
				case 'green':
					return '008800';
				case 'lime':
					return '00ff00';
				case 'olive':
					return '808000';
				case 'yellow':
					return 'ffff00';
				case 'navy':
					return '000080';
				case 'blue':
					return '0000ff';
				case 'teal':
					return '008080';
				case 'aqua':
					return '00ffff';
				case 'gold':
					return 'ffd700';
				case 'orange':
					return 'ffa500';
				case 'transparent':
					return 'ffffff'; // Default transparent to white for contrast calculation
				default:
					return '000000'; // Default fallback
			}
		}
	}

	public static function hslToRgb($h, $s, $l)
	{
		$h /= 360;
		$s /= 100;
		$l /= 100;

		if ($s == 0) {
			$r = $g = $b = $l;
		} else {
			$q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
			$p = 2 * $l - $q;
			$r = BasicChecks::hue2rgb($p, $q, $h + 1 / 3);
			$g = BasicChecks::hue2rgb($p, $q, $h);
			$b = BasicChecks::hue2rgb($p, $q, $h - 1 / 3);
		}

		return sprintf("%02x%02x%02x", round($r * 255), round($g * 255), round($b * 255));
	}

	private static function hue2rgb($p, $q, $t)
	{
		if ($t < 0)
			$t += 1;
		if ($t > 1)
			$t -= 1;
		if ($t < 1 / 6)
			return $p + ($q - $p) * 6 * $t;
		if ($t < 1 / 2)
			return $q;
		if ($t < 2 / 3)
			return $p + ($q - $p) * (2 / 3 - $t) * 6;
		return $p;
	}
	// takes an element as input and returns its table
	public static function getTable($e)
	{

		while ($e->parent()->tag != "table" && $e->parent()->tag != null)
			$e = $e->parent();

		if ($e->parent()->tag == "html")
			return null;
		else
			return $e->parent();

	}
	// takes an array of ids (headers attribute of a td element) and verifies that each id is associated with a th
	public static function checkIdInTable($table, $ids)
	{

		$th_nodes = $table->find("th");
		$found_count = 0;
		$ids_size = is_array($ids) ? sizeof($ids) : 0;

		foreach ($ids as $id) {
			foreach ($th_nodes as $th) {
				if (is_array($th->attr) && isset($th->attr['id']) && (string) $th->attr['id'] == $id) {
					$found_count++;
					break;
				}
			}
		}

		if ($found_count == $ids_size) // found a th id for every td id
			return true;
		else
			return false;
	}
	// verify the existence of a row header for a td element
	public static function getRowHeader($e)
	{

		while ($e->prev_sibling() != null && $e->prev_sibling()->tag != "th") {

			$e = $e->prev_sibling();
		}

		if ($e->prev_sibling() == null)
			return null;
		else

			return $e->prev_sibling();

	}
	// checks for the existence of a column header for a td element
	public static function getColHeader($e)
	{

		$position = 0;
		$current_node = $e;
		// find the position in the row of td
		while ($current_node->prev_sibling() != null) {
			$position++;
			$current_node = $current_node->prev_sibling();
		}

		$table = BasicChecks::getTable($e);
		// there isn't a <table> tag
		if ($table == null) {
			return true; // malformed table
		}

		$rows = $table->find("tr");
		$rows_size = is_array($rows) ? sizeof($rows) : 0;

		if ($rows == null || $rows_size == 0)
			return true; // malformed table


		for ($i = 0; $i < $rows_size - 1; $i++) {
			$header_nodes = $rows[$i + 1]->find("th");
			if ($header_nodes == null || (is_array($header_nodes) && sizeof($header_nodes) == 0))
				break; // current tr contains the innermost header
		}

		$headers = $rows[$i]->childNodes();
		// Verify that the header box in position $position is actually a header
		if (isset($headers[$position]) && $headers[$position]->tag == "th")
			return $headers[$position];
		else
			return null;

	}

	// recursive check for scripts and events
	public static function rec_check_15005($e)
	{
		if (
			$e->tag == 'script' || $e->tag == 'object' || $e->tag == 'applet' ||
			(is_array($e->attr) && (
				isset($e->attr['onload']) || isset($e->attr['onunload']) || isset($e->attr['onclick']) || isset($e->attr['ondblclick']) || isset($e->attr['onmousedown']) || isset($e->attr['onmouseup']) || isset($e->attr['onmouseover']) || isset($e->attr['onmousemove']) || isset($e->attr['onmouse']) || isset($e->attr['onblur']) || isset($e->attr['onkeypress']) || isset($e->attr['onkeydown']) || isset($e->attr['onkeyup']) || isset($e->attr['onsubmit']) || isset($e->attr['onreset']) || isset($e->attr['onselect']) || isset($e->attr['onchange'])
			))
		)
			return false;
		else
			$children = $e->children();
		$result = true;
		foreach ($children as $child) {
			$result = BasicChecks::rec_check_15005($child);
			if ($result == false)
				return $result;
		}
		return $result;

	}

	// CSS functions

	/* returns the list of external styles on the page */
	public static function get_style_external($content_dom)
	{

		global $css_list;

		$dom_object = $content_dom;
		$link_nodes = $dom_object->find('link');
		$counter = 0;
		foreach ($link_nodes as $link_tag) {
			$link_type = (is_array($link_tag->attr) && isset($link_tag->attr["type"])) ? (string) $link_tag->attr["type"] : '';
			$link_rel = (is_array($link_tag->attr) && isset($link_tag->attr["rel"])) ? (string) $link_tag->attr["rel"] : '';
			$link_media = (is_array($link_tag->attr) && isset($link_tag->attr["media"])) ? (string) $link_tag->attr["media"] : 'all';
			$link_href = (is_array($link_tag->attr) && isset($link_tag->attr["href"])) ? (string) $link_tag->attr["href"] : '';

			if ($link_type == "text/css" && $link_rel == "stylesheet" && ($link_media == "all" || $link_media == "screen")) {
				$css_list[$counter] = $link_href;
				$counter++;
			}
		}

		if ($css_list == "")
			return $css_list;
		// clean up CSS URLs
		global $uri;
		$base_uri = BasicChecks::getSiteUri($uri);

		$counter = 0;
		// change the relative addresses of style sheets
		foreach ($css_list as $sheet_url) {

			$sheet_url = str_replace('"', '', $sheet_url);

			if (stripos($sheet_url, "http://") === false && stripos($sheet_url, "https://") === false && substr($sheet_url, 0, 2) !== "//") // relative address
			{
				if (substr($sheet_url, 0, 1) == "/")
					$sheet_url = $base_uri . $sheet_url;
				else
					$sheet_url = $base_uri . "/" . $sheet_url;
			}

			$css_list[$counter] = $sheet_url;
			$counter++;
		}

		return $css_list;
	}
	// The function returns the internal CSS of a page
	public static function get_style_internal($content_dom)
	{

		$dom_object = $content_dom;
		// change the URL of the site to be validated in order to set add the address of a relative CSS
		global $uri;
		$base_uri = BasicChecks::getSiteUri($uri);

		$style_nodes = $dom_object->find('style');
		$internal_css = "";
		foreach ($style_nodes as $style_node) {
			if (is_array($style_node->attr) && (!isset($style_node->attr["media"]) || (string) $style_node->attr["media"] == "all" || (string) $style_node->attr["media"] == "screen")) {
				$internal_css = $internal_css . $style_node->innertext;
				$internal_css = trim($internal_css);
				$import_limit = 10;
				while ($import_limit > 0 && substr(trim($internal_css), 0, 7) == "@import") {
					$internal_css = trim($internal_css);
					$semicolon_pos = stripos($internal_css, ";");
					if ($semicolon_pos === false)
						break;

					$import_statement = substr($internal_css, 0, $semicolon_pos + 1);
					$import_params = substr($import_statement, 7); // remove @import

					$import_url = '';
					if (preg_match('/url\s*\(([\'"]?)(.*?)\1\)/i', $import_params, $matches)) {
						$import_url = $matches[2];
					} else if (preg_match('/([\'"])(.*?)\1/', $import_params, $matches)) {
						$import_url = $matches[2];
					}

					if ($import_url) {
						if (stripos($import_url, "http://") === false && stripos($import_url, "https://") === false && substr($import_url, 0, 2) !== "//") {
							if (substr($import_url, 0, 1) == "/")
								$import_url = $base_uri . $import_url;
							else
								$import_url = $base_uri . "/" . $import_url;
						}

						$external_css_content = Utility::getURLContents($import_url);
						$internal_css = substr($internal_css, $semicolon_pos + 1) . "\n" . $external_css_content;
					} else {
						$internal_css = substr($internal_css, $semicolon_pos + 1);
					}
					$import_limit--;
				}
			}
		}
		return $internal_css;
	}
	// The function creates an array of styles (internal and external) to be submitted for validation.
	public static function prepare_css_arrays($external_css_urls, $internal_css_content)
	{

		for ($index = 0; $index < (is_array($external_css_urls) ? count($external_css_urls) : 0); $index++) {
			$fetched_css = Utility::getURLContents($external_css_urls[$index]);
			BasicChecks::GetCSSDom($fetched_css, $index);
		}

		// last position: add internal style
		if ($internal_css_content != "") {

			BasicChecks::GetCSSDom($internal_css_content, $index);
		}
	}
	// Get the CSS code that caused an error for the last CSS check performed.
	// Returns the CSS code if errors were found, otherwise returns an empty string.
	//restituisce il codice css che ha provocato un errore, relativamente all'ultimo check sui css che � stato eseguito
	//viene richiamata in AccessibilityValidator dopo l'esecuizione di ogni check
	//restituisce $css_code che, nel caso in cui il check non abbia riscontrato errori su un css interno/esterno o
	//non sia un check sui css, viene impostata a ""
	public static function getCssOutput()
	{
		// MB: To print the check on the rules of CSS
		// CSS rules relating to the error
		// CSS: default font size and default font format
		global $tag_size;
		global $css_list;
		global $css_array;
		global $global_e;
		global $background, $foreground;
		$background_color = $background;
		$foreground_color = $foreground;
		$background = $foreground = '';

		$space_marker = "{_}";
		$css_code = "";
		if (isset($css_array) && $css_array != null) {

			if (is_numeric($tag_size)) {
				$tag_size = round($tag_size, 2);
			}
			$css_code = $css_code .
				"<p>" . _AC("fixed_size_example_text") . ": <span style='font-size:20px;background-color:#" .
				$background_color . ";color:#" . $foreground_color . "'>" . _AC("color_contrast_example") . "</span></p>";

			$bold = BasicChecks::get_p_css($global_e, "font-weight");

			if ($bold == "bold" || $bold >= 700 || ($bold == "" && ($global_e->tag == "h1" || $global_e->tag == "h2" || $global_e->tag == "h3" || $global_e->tag == "h4" || $global_e->tag == "h5" || $global_e->tag == "h6"))) {
				$real_size_text = "<p>" . _AC("real_size_example_text") . " (" . $tag_size . " " . _AC("points") . " " . _AC("bold") . "): <span style='font-weight:bold;font-size:" . $tag_size . "pt;background-color:#" .
					$background_color . ";color:#" . $foreground_color . "'>" . _AC("color_contrast_example") . "</span></p>";
			} else {
				$real_size_text = "<p>" . _AC("real_size_example_text") . " (" . $tag_size . " " . _AC("points") . "): <span style='font-size:" . $tag_size . "pt;background-color:#" .
					$background_color . ";color:#" . $foreground_color . "'>" . _AC("color_contrast_example") . "<span></p>";
			}
			$css_code = $css_code . $real_size_text;
			$css_code .= "<p style='padding:1em'>" . _AC("element_CSS_rules") . ": </p>\n\t\n\t<pre>\n\t\n\t";
			$int_css = '';
			$ext_css = array();
			$size_of_css_list = sizeof($css_list);

			foreach ($css_array as $rule) {

				$temp_css_code = '';
				$num_to_end = sizeof($rule["prev"]) - 1;

				for ($i = $num_to_end; $i >= 0; $i--) {
					$temp_css_code .= " " . $rule["prev"][$i];
				}
				$temp_css_code = str_ireplace(" .", ".", $temp_css_code);
				$temp_css_code = str_ireplace(" #", "#", $temp_css_code);
				$temp_css_code = str_ireplace(">.", "> .", $temp_css_code);
				$temp_css_code = str_ireplace(">#", "> #", $temp_css_code);
				$temp_css_code = str_ireplace("+.", "+ .", $temp_css_code);
				$temp_css_code = str_ireplace("+#", "+ #", $temp_css_code);
				$temp_css_code = str_ireplace(" " . $space_marker, "", $temp_css_code);

				$temp_css_code = $temp_css_code . "{\n\t\n\t";

				foreach ($rule["regole"] as $prop => $value) {
					$temp_css_code = $temp_css_code . "            " . $prop . ":" . $value["val"] . ";\n\t";
				}
				$temp_css_code = $temp_css_code . "      }\n\t\n\t";

				$rule_idcss = isset($rule["idcss"]) ? $rule["idcss"] : null;

				if ($rule_idcss === null || $rule_idcss == $size_of_css_list) // last position, internal style
					$int_css .= $temp_css_code;
				else {
					$url = isset($css_list[$rule_idcss]) ? (string)$css_list[$rule_idcss] : '';
					if ($url !== '') {
						if (!isset($ext_css[$url])) {
							$ext_css[$url] = '';
						}
						$ext_css[$url] .= $temp_css_code;
					} else {
						$int_css .= $temp_css_code; // Fallback
					}
				}
			}
			if ($int_css != '')
				$css_code .= _AC("internal_CSS") . ":\n\t\n\t " . $int_css;

			if (!empty($ext_css))
				foreach ($ext_css as $url => $val) {
					$css_code .= _AC("external_CSS") . " (<a title='external CSS link' href='" . $url . "'>" . $url . "</a>):\n\t\n\t      " . $val;
				}

			$css_code .= "</pre>\n\t";
		}
		$css_array = array();
		return $css_code;
	}

	public static function checkLinkContrastWcag2AA($css_property_name, $body_attribute_name)
	{

		global $background, $foreground;
		global $global_e, $global_content_dom;
		$e = $global_e;
		$content_dom = $global_content_dom;

		BasicChecks::setCssSelectors($content_dom);

		if (!BasicChecks::isElementVisible($e))
			return true;

		$background = '';
		$foreground = '';

		if (trim(BasicChecks::remove_children($e)) == "" || trim(BasicChecks::remove_children($e)) == "&nbsp;") // the element has no visible text: Do not run the contrast control
			return true;

		$foreground = BasicChecks::getForegroundA($e, $css_property_name);
		if ($foreground == "undetermined")
			return true;
		if (($foreground == "" || $foreground == null) && $body_attribute_name != null) {
			$parent_element = $e->parent();
			while ($parent_element->tag != "body" && $parent_element->tag != "html" && $parent_element->tag != null)
				$parent_element = $parent_element->parent();
			if ($parent_element != null && is_array($parent_element->attr) && isset($parent_element->attr[$body_attribute_name]))
				$foreground = (string) $parent_element->attr[$body_attribute_name];

		}
		if ($foreground == "undetermined")
			return true;

		if ($foreground == "" || $foreground == null)
			return true;

		$background = BasicChecks::getBackgroundA($e, $css_property_name);
		if ($background == "undetermined")
			return true;

		if ($background == "" || $background == null)
			$background = BasicChecks::getBackground($e);

		if ($background == "" || $background == null || $background == "-1")
			return true;

		if ($background == "undetermined")
			return true;

		$background_hex = BasicChecks::convert_color_to_hex($background);
		$foreground_hex = BasicChecks::convert_color_to_hex($foreground);

		$contrast_ratio_val = BasicChecks::ContrastRatio(strtolower($background_hex), strtolower($foreground_hex));

		$font_size_value = BasicChecks::fontSizeToPt($e);
		$font_weight_val = BasicChecks::get_p_css($e, "font-weight");

		if ($font_size_value < 0) // format not supported
			return true;
		elseif ($font_size_value >= 18 || ($font_weight_val == "bold" && $font_size_value >= 14))
			$contrast_threshold = 3;
		else
			$contrast_threshold = 4.5;
		if ($contrast_ratio_val < $contrast_threshold) {
			return false;
		} else {
			return true;
		}

		return true;
	}

	public static function checkLinkContrastWcag2AAA($css_property_name, $body_attribute_name)
	{
		global $background, $foreground;
		global $global_e, $global_content_dom;
		$e = $global_e;
		$content_dom = $global_content_dom;

		BasicChecks::setCssSelectors($content_dom);

		if (!BasicChecks::isElementVisible($e))
			return true;

		$background = '';
		$foreground = '';

		if (trim(BasicChecks::remove_children($e)) == "" || trim(BasicChecks::remove_children($e)) == "&nbsp;") {
			return true;
		}

		$foreground = BasicChecks::getForegroundA($e, $css_property_name);
		$background = BasicChecks::getBackgroundA($e, $css_property_name);

		if ($background == "-1") {
			return true;
		}

		$background = BasicChecks::convert_color_to_hex($background);
		$foreground = BasicChecks::convert_color_to_hex($foreground);

		$contrast_ratio_val = BasicChecks::ContrastRatio(strtolower($background), strtolower($foreground));

		$font_size_value = BasicChecks::fontSizeToPt($e);
		$font_weight_val = BasicChecks::get_p_css($e, "font-weight");

		if ($font_size_value < 0)
			return true;
		elseif ($font_size_value >= 18 || ($font_weight_val == "bold" && $font_size_value >= 14))
			$contrast_threshold = 4.5;
		else
			$contrast_threshold = 7;

		if ($contrast_ratio_val < $contrast_threshold) {
			return false;
		}

		return true;
	}

}
?>