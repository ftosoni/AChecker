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

global $onload, $_custom_head;

if (isset($_POST["validate_file"])) {
	$init_tab = "AC_by_upload";
} else if (isset($_POST["validate_paste"])) {
	$init_tab = "AC_by_paste";
} else {
	$init_tab = "AC_by_uri";
}

if ($_POST["rpt_format"] == REPORT_FORMAT_GUIDELINE) {
	$rpt_format = "by_guideline";
} else if ($_POST["rpt_format"] == REPORT_FORMAT_LINE) {
	$rpt_format = "by_line";
}

$onload = "AChecker.input.initialize('" . $init_tab . "', '" . $rpt_format . "');";
$_custom_head .= '	<script language="javascript" type="text/javascript">' . "\n" .
	'	//<!--' . "\n";

ob_start();
require_once(AC_INCLUDE_PATH . '../checker/js/checker_js.php');
$_custom_head .= ob_get_contents();
ob_end_clean();

$_custom_head .= '	//-->' . "\n" .
	'	</script>' . "\n" .
	'	<script src="' . AC_BASE_HREF . 'checker/js/checker.js?v=1.3" type="text/javascript"></script>' . "\n";

include(AC_INCLUDE_PATH . 'header.inc.php');

if (isset($error))
	echo $error;

/** return the string of a div html to display all the available guidelines
 * 2 formats: checkbox or radio button in front of the guideline
 * @param: $guideline_rows - array of available guidelines
 *         $num_of_guidelines_per_row
 *         $format: "checkbox" or "radio"
 */
function get_guideline_div($guideline_rows, $num_of_guidelines_per_row, $format = "checkbox")
{
	$output = '				<div id="guideline_in_' . $format . '"';
	if ($format == "checkbox")
		$output .= ' style="display:none"';
	$output .= '>' . "\n";
	$output .= '				<table width="100%">' . "\n";

	$count_guidelines_in_current_row = 0;

	if (is_array($guideline_rows)) {
		foreach ($guideline_rows as $id => $row) {
			if ($count_guidelines_in_current_row == 0 || $count_guidelines_in_current_row == $num_of_guidelines_per_row) {
				$count_guidelines_in_current_row = 0;
				$output .= "					<tr>\n";
			}

			$output .= '						<td class="one_third_width">' . "\n";
			$output .= '							<input type="';

			if ($format == "checkbox")
				$output .= "checkbox";
			else
				$output .= "radio";

			$output .= '" name="' . $format . '_gid[]" id="' . $format . '_gid_' . $row["guideline_id"] . '" value="' . $row["guideline_id"] . '"';

			// the name of the array for the selected guidelines in the post value are different.
			// "radio_gids" at guideline view and "checkbox_gids" at line view.
			$gid_name = $format . "_gid";
			foreach ($_POST[$gid_name] as $gid) {
				if ($gid == $row["guideline_id"])
					$output .= ' checked="checked"';
			}
			$output .= ' />' . "\n";

			$output .= '							<label for="' . $format . '_gid_' . $row["guideline_id"] . '">' . htmlspecialchars($row["title"]) . '</label>' . "\n";
			$output .= "						</td>\n";
			$count_guidelines_in_current_row++;

			if ($count_guidelines_in_current_row == $num_of_guidelines_per_row)
				$output .= "					</tr>\n";

		}
	}
	$output .= "				</table>\n";
	$output .= "			</div>\n";

	return $output;
}
?>
<div class="center-input-form" style="margin-top: 24px;">
	<div class="cdx-message cdx-message--notice" style="margin-bottom: 24px;">
		<span class="cdx-message__icon"></span>
		<div class="cdx-message__content">
			<strong>Welcome to the MediaWiki Accessibility Checker.</strong> This tool checks single HTML pages for conformance with accessibility standards to ensure the content can be accessed by everyone. See the <strong>Handbook</strong> link to the upper right for more about the Web Accessibility Checker.
		</div>
	</div>

	<form name="input_form" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">

		<section class="cdx-card" style="padding: 24px; border: 1px solid #a2a9b1; background: #fff;">


			<div class="cdx-tabs" style="margin-top: 24px;">
				<a href="javascript:void(0)" id="AC_menu_by_uri"
					class="cdx-tabs__item <?php if (!isset($_POST["validate_paste"]) && !isset($_POST["validate_file"]))
						echo 'cdx-tabs__item--active'; ?>"
					onclick="return AChecker.input.onClickTab('AC_by_uri');">
					<?php echo _AC("check_by_uri"); ?>
				</a>
				<a href="javascript:void(0)" id="AC_menu_by_upload"
					class="cdx-tabs__item <?php if (isset($_POST["validate_file"]))
						echo 'cdx-tabs__item--active'; ?>"
					onclick="return AChecker.input.onClickTab('AC_by_upload');">
					<?php echo _AC("check_by_upload"); ?>
				</a>
				<a href="javascript:void(0)" id="AC_menu_by_paste"
					class="cdx-tabs__item <?php if (isset($_POST["validate_paste"]))
						echo 'cdx-tabs__item--active'; ?>"
					onclick="return AChecker.input.onClickTab('AC_by_paste');">
					<?php echo _AC("check_by_paste"); ?>
				</a>
			</div>

			<div id="AC_by_uri" class="input_tab"
				style="<?php if (!isset($_POST["validate_file"]) && !isset($_POST["validate_paste"]))
					echo "display:block";
				else
					echo "display:none"; ?>; padding: 24px 0;">
				<div class="cdx-field">
					<label class="cdx-label" for="checkuri"><?php echo _AC('URL'); ?>:</label>
					<input type="text" class="cdx-text-input" name="uri" id="checkuri"
						value="<?php if (isset($_POST['uri']))
							echo $_POST['uri'];
						else
							$v($default_uri_value); ?>"
						placeholder="Insert URL here (e.g. https://example.org)" />
				</div>
				<div style="display: flex; align-items: center; gap: 16px;">
					<button class="cdx-button cdx-button--action-progressive cdx-button--weight-primary" type="submit"
						name="validate_uri" id="validate_uri" value="1" onclick="return AChecker.input.validateURI();">
						<?php echo _AC("check_it"); ?>
					</button>
					<span id="AC_spinner_by_uri" class="cdx-spinner" role="alert" aria-live="assertive"
						style="display:none; margin-left: 12px;">
						<span class="cdx-spinner__dot"></span><span class="cdx-spinner__dot"></span><span
							class="cdx-spinner__dot"></span>
					</span>
				</div>
			</div>

			<div id="AC_by_upload" class="input_tab"
				style="<?php if (isset($_POST["validate_file"]))
					echo "display:block";
				else
					echo "display:none"; ?>; padding: 24px 0;">
				<div class="cdx-field">
					<label class="cdx-label" for="checkfile"><?php echo _AC('file'); ?>:</label>
					<input type="hidden" name="MAX_FILE_SIZE" value="52428800" />
					<input type="file" id="checkfile" name="uploadfile" class="cdx-text-input" style="padding: 4px;" />
				</div>
				<div style="display: flex; align-items: center; gap: 16px;">
					<button class="cdx-button" type="submit" name="validate_file" id="validate_file" value="1"
						onclick="return AChecker.input.validateUpload();">
						<?php echo _AC("check_it"); ?>
					</button>
					<span id="AC_spinner_by_upload" class="cdx-spinner" role="alert" aria-live="assertive"
						style="display:none; margin-left: 12px;">
						<span class="cdx-spinner__dot"></span><span class="cdx-spinner__dot"></span><span
							class="cdx-spinner__dot"></span>
					</span>
				</div>
			</div>

			<div id="AC_by_paste" class="input_tab"
				style="<?php if (isset($_POST["validate_paste"]))
					echo "display:block";
				else
					echo "display:none"; ?>; padding: 24px 0;">
				<div class="cdx-field">
					<label class="cdx-label" for="checkpaste"><?php echo _AC('enter'); ?>:</label>
					<textarea rows="15" class="cdx-text-input" name="pastehtml" id="checkpaste"
						style="font-family: monospace;"><?php if (isset($_POST['pastehtml']))
							echo htmlspecialchars($_POST['pastehtml']); ?></textarea>
				</div>
				<div style="display: flex; align-items: center; gap: 16px;">
					<button class="cdx-button" type="submit" name="validate_paste" id="validate_paste" value="1"
						onclick="return AChecker.input.validatePaste();">
						<?php echo _AC("check_it"); ?>
					</button>
					<span id="AC_spinner_by_paste" class="cdx-spinner" role="alert" aria-live="assertive"
						style="display:none; margin-left: 12px;">
						<span class="cdx-spinner__dot"></span><span class="cdx-spinner__dot"></span><span
							class="cdx-spinner__dot"></span>
					</span>
				</div>
			</div>

			<div style="margin-top: 24px;">
				<h2 style="font-size: 1.1em; border-top: 1px solid #eaecf0; padding-top: 16px; display: flex; align-items: center; cursor: pointer;"
					onclick="AChecker.toggleDiv('div_options', 'toggle_image');">
					<img src="images/arrow-open.png" alt="" id="toggle_image" style="margin-right: 8px;" />
					<span style="color: #1966d3;"><?php echo _AC("options"); ?></span>
				</h2>
			</div>

			<div id="div_options"
				style="display:block; padding: 16px; background: #f8f9fa; border-radius: 2px; margin-top: 8px;">
				<div style="display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 24px;">
					<div class="cdx-field">
						<input type="checkbox" name="enable_html_validation" id="enable_html_validation" value="1" <?php if (isset($_POST["enable_html_validation"]))
							echo 'checked="checked"'; ?> />
						<label for="enable_html_validation"><?php echo _AC("enable_html_validator"); ?></label>
					</div>
					<div class="cdx-field">
						<input type="checkbox" name="enable_css_validation" id="enable_css_validation" value="1" <?php if (isset($_POST["enable_css_validation"]))
							echo 'checked="checked"'; ?> />
						<label for="enable_css_validation"><?php echo _AC("enable_css_validation"); ?></label>
					</div>
					<div class="cdx-field">
						<input type="checkbox" name="show_source" id="show_source" value="1" <?php if (isset($_POST["show_source"]))
							echo 'checked="checked"'; ?> />
						<label for="show_source"><?php echo _AC("show_source"); ?></label>
					</div>
				</div>

				<div class="cdx-field">
					<label class="cdx-label" style="font-size: 1.1em;"><?php echo _AC("guidelines_to_check"); ?></label>
					<div style="margin-top: 12px; padding: 12px; background: #fff; border: 1px solid #eaecf0;">
						<?php
						echo get_guideline_div($rows, $num_of_guidelines_per_row, "radio");  // used at "view by guideline"
						echo get_guideline_div($rows, $num_of_guidelines_per_row, "checkbox");  // used at "view by line"
						?>
					</div>
				</div>

				<div class="cdx-field" style="margin-top: 24px;">
					<label class="cdx-label" style="font-size: 1.1em;"><?php echo _AC("report_format"); ?></label>
					<div style="display: flex; gap: 24px; margin-top: 12px;">
						<div>
							<input type="radio" name="rpt_format" value="<?php echo REPORT_FORMAT_GUIDELINE; ?>"
								id="option_rpt_gdl" <?php if ($_POST["rpt_format"] == REPORT_FORMAT_GUIDELINE)
									echo 'checked="checked"'; ?> />
							<label for="option_rpt_gdl"><?php echo _AC("view_by_guideline"); ?></label>
						</div>
						<div>
							<input type="radio" name="rpt_format" value="<?php echo REPORT_FORMAT_LINE; ?>"
								id="option_rpt_line" <?php if ($_POST["rpt_format"] == REPORT_FORMAT_LINE)
									echo 'checked="checked"'; ?> />
							<label for="option_rpt_line"><?php echo _AC("view_by_line"); ?></label>
						</div>
					</div>
				</div>
			</div>
		</section>
	</form>
</div>