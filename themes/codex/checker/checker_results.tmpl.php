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

global $congrats_msg_for_likely, $congrats_msg_for_potential;;

include_once(AC_INCLUDE_PATH.'classes/Utility.class.php');
include_once(AC_INCLUDE_PATH.'classes/DAO/UserLinksDAO.class.php');
?>
<div id="AC_seals_div" class="validator-output-form">

<?php 
// display seals
if (isset($seals) && is_array($seals))
{
?>
<h3><?php echo _AC('valid_icons');?></h3>
<p><?php echo _AC('valid_icons_text');?></p>
<?php 
	$user_link_url = '';
	
	if (isset($user_link_id))
		$user_link_url = '&amp;id='.$user_link_id;

	foreach ($seals as $seal)
	{
?>
	<img class="inline-badge" src="<?php echo SEAL_ICON_FOLDER . $seal['seal_icon_name'];?>"
    alt="<?php echo $seal['title']; ?>" height="32" width="102"/>
    <pre class="badgeSnippet">
  &lt;p&gt;
    &lt;a href="<?php echo AC_BASE_HREF; ?>checker/index.php?uri=referer&amp;gid=<?php echo $seal['guideline'].$user_link_url;?>"&gt;
      &lt;img src="<?php echo AC_BASE_HREF.SEAL_ICON_FOLDER . $seal['seal_icon_name'];?>" alt="<?php echo $seal['title']; ?>" height="32" width="102" /&gt;
    &lt;/a&gt;
  &lt;/p&gt;
	</pre>

<?php 
	} // end of foreach (display seals)
} // end of if (display seals)
?>
</div>

<div id="output_div" style="margin-top: 24px;">

<?php
if (isset($aValidator) && $a_rpt->getAllowSetDecisions() == 'true')
{
	$sessionID = Utility::getSessionID();
	
	$userLinksDAO = new UserLinksDAO();
	$userLinksDAO->setLastSessionID($a_rpt->getUserLinkID(), $sessionID);
	
	echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'">'."\n\r";
	echo '<input type="hidden" name="jsessionid" value="'.$sessionID.'" />'."\n\r";
	echo '<input type="hidden" name="uri" value="'.filter_var($_POST["uri"], FILTER_SANITIZE_URL).'" />'."\n\r";
	echo '<input type="hidden" name="output" value="html" />'."\n\r";
	echo '<input type="hidden" name="validate_uri" value="1" />'."\n\r";
	echo '<input type="hidden" name="rpt_format" value="'.htmlspecialchars($_POST['rpt_format'], ENT_QUOTES, 'utf-8').'" />'."\n\r";

	if (isset($referer_report)) echo '<input type="hidden" name="referer_report" value="'.$referer_report.'" />'."\n\r";
	if (isset($referer_user_link_id)) echo '<input type="hidden" name="referer_user_link_id" value="'.$referer_user_link_id.'" />'."\n\r";
	
	foreach ($_POST as $post_name => $value) {
		if (substr($post_name, -4) == "_gid") {
			foreach ($_POST[$post_name] as $gid_value) {
				echo '<input type="hidden" name="'.$post_name.'[]" value="'.$gid_value.'" />'."\n\r";
			}
		}
	}
}
?>

	<section class="cdx-card">
		<a name="report"></a>
		<h2 class="cdx-typography-h2">
			<?php echo _AC("accessibility_review"); ?> (<?php echo _AC("guidelines"); ?>: <span class="guidelines-label"><?php echo $guidelines_text; ?></span>)
		</h2>

		<!-- Export Options -->
		<div style="background: #f8f9fa; padding: 16px; border-radius: 2px; margin: 16px 0; border: 1px solid #eaecf0;">
			<form name="file_form" enctype="multipart/form-data" method="post" style="display: flex; flex-wrap: wrap; align-items: center; gap: 16px;">
				<div class="cdx-field" style="margin-bottom: 0;">
					<label class="cdx-label" for="fileselect" style="display: inline-block; margin-right: 8px;"><?php echo _AC('file_type'); ?>:</label>
					<select name="file_menu" id="fileselect" class="cdx-text-input" style="width: auto;">
						<option value="pdf" selected="selected">PDF</option>
						<option value="earl">EARL</option>
						<option value="csv">CSV</option>
						<option value="html">HTML</option>
						<option value="wikitext">Wikitext</option>
					</select>
				</div>
				
				<div class="cdx-field" style="margin-bottom: 0;">
					<label class="cdx-label" for="problemselect" style="display: inline-block; margin-right: 8px;"><?php echo _AC('problem_type'); ?>:</label>
					<select name="problem_menu" id="problemselect" class="cdx-text-input" style="width: auto;">
						<option value="all" selected="selected"><?php echo _AC('all'); ?></option>
						<option value="known" ><?php echo _AC('known'); ?></option>
						<option value="likely"><?php echo _AC('likely'); ?></option>
						<option value="potential"><?php echo _AC('potential'); ?></option>
						<option value="html"><?php echo _AC('html_validation_result'); ?></option>
						<option value="css"><?php echo _AC('css_validation_result'); ?></option>
					</select>
				</div>

				<button class="cdx-button" type="button" onclick="return AChecker.input.validateFile('spinner_export');">
					<?php echo _AC("get_file"); ?>
				</button>
				<span id="spinner_export" class="cdx-spinner" role="alert" aria-live="assertive" style="display:none; margin-left: 12px;">
					<span class="cdx-spinner__dot"></span><span class="cdx-spinner__dot"></span><span class="cdx-spinner__dot"></span>
				</span>
				<iframe id="downloadFrame" src="" style="display:none;"></iframe>
			</form>
		</div>

		<!-- Result Tabs -->
		<div class="cdx-tabs" style="margin-top: 32px;">
			<a href="javascript:void(0);" id="AC_menu_errors" class="cdx-tabs__item cdx-tabs__item--active" onclick="AChecker.output.onClickTab('AC_errors');">
				<?php echo _AC("known_problems"); ?> (<?php echo $num_of_errors; ?>)
			</a>
			<a href="javascript:void(0);" id="AC_menu_likely_problems" class="cdx-tabs__item" onclick="AChecker.output.onClickTab('AC_likely_problems');">
				<?php echo _AC("likely_problems"); ?> (<?php echo $num_of_likely_problems_no_decision; ?>)
			</a>
			<a href="javascript:void(0);" id="AC_menu_potential_problems" class="cdx-tabs__item" onclick="AChecker.output.onClickTab('AC_potential_problems');">
				<?php echo _AC("potential_problems"); ?> (<?php echo $num_of_potential_problems_no_decision; ?>)
			</a>
			<a href="javascript:void(0);" id="AC_menu_html_validation_result" class="cdx-tabs__item" onclick="AChecker.output.onClickTab('AC_html_validation_result');">
				<?php echo _AC("html_validation_result"); ?> <?php if (isset($_POST["enable_html_validation"])) echo '('.$num_of_html_errors.')'; ?>
			</a>
			<a href="javascript:void(0);" id="AC_menu_css_validation_result" class="cdx-tabs__item" onclick="AChecker.output.onClickTab('AC_css_validation_result');">
				<?php echo _AC("css_validation_result"); ?> <?php if (isset($cssValidator)) echo '('.$num_of_css_errors.')'; ?>
			</a>
		</div>

		<div id="AC_errors" style="padding-top: 24px;">
			<?php if (isset($aValidator) && $num_of_errors == 0): ?>
				<div class="cdx-message cdx-message--success">
					<span class="cdx-report-marker cdx-report-marker--success">✅</span>
					<?php echo _AC("congrats_no_known"); ?>
				</div>
			<?php elseif (isset($aValidator)): ?>
				<?php echo $a_rpt->getErrorRpt(); ?>
			<?php endif; ?>
		</div>

		<div id="AC_likely_problems" style="display:none; padding-top: 24px;">
			<?php if (isset($aValidator) && $num_of_likely_problems_no_decision == 0): ?>
				<div class="cdx-message cdx-message--success">
					<?php echo $congrats_msg_for_likely; ?>
				</div>
			<?php elseif (isset($aValidator)): ?>
				<?php echo $a_rpt->getLikelyProblemRpt(); ?>
			<?php endif; ?>
		</div>

		<div id="AC_potential_problems" style="display:none; padding-top: 24px;">
			<?php if (isset($aValidator) && $num_of_potential_problems_no_decision == 0): ?>
				<div class="cdx-message cdx-message--success">
					<?php echo $congrats_msg_for_potential; ?>
				</div>
			<?php elseif (isset($aValidator)): ?>
				<?php echo $a_rpt->getPotentialProblemRpt(); ?>
			<?php endif; ?>
		</div>

		<div id="AC_html_validation_result" style="display:none; padding-top: 24px;">
			<?php if (isset($htmlValidator)): ?>
				<p style="color: #72777d; font-size: 0.9em; margin-bottom: 16px;"><?php echo _AC("html_validator_provided_by"); ?></p>
				<?php if ($htmlValidator->containErrors()): ?>
					<?php echo $htmlValidator->getErrorMsg(); ?>
				<?php elseif ($num_of_html_errors > 0): ?>
					<?php echo $htmlValidator->getValidationRpt(); ?>
				<?php else: ?>
					<div class="cdx-message cdx-message--success">
						<?php echo _AC("congrats_html_validation"); ?>
					</div>
				<?php endif; ?>
			<?php else: ?>
				<p style="color: #72777d;"><?php echo _AC("html_validator_disabled"); ?></p>
			<?php endif; ?>
		</div>

		<div id="AC_css_validation_result" style="display:none; padding-top: 24px;">
			<?php if (isset($_POST['validate_file']) || isset($_POST['validate_paste'])): ?>
				<p style="color: #72777d;"><?php echo _AC("css_validator_unavailable"); ?></p>
			<?php elseif (isset($cssValidator)): ?>
				<p style="color: #72777d; font-size: 0.9em; margin-bottom: 16px;"><?php echo _AC("css_validator_provided_by"); ?></p>
				<?php if ($cssValidator->containErrors()): ?>
					<?php echo $cssValidator->getErrorMsg(); ?>
				<?php elseif ($num_of_css_errors > 0): ?>
					<?php echo $cssValidator->getValidationRpt(); ?>
				<?php else: ?>
					<div class="cdx-message cdx-message--success">
						<?php echo _AC("congrats_css_validation"); ?>
					</div>
				<?php endif; ?>
			<?php else: ?>
				<p style="color: #72777d;"><?php echo _AC("css_validator_disabled"); ?></p>
			<?php endif; ?>
		</div>

		<?php if (isset($aValidator) && $a_rpt->getAllowSetDecisions() == 'true' && $a_rpt->getNumOfNoDecisions() > 0): ?>
			<div style="text-align: center; margin-top: 32px;">
				<button type="submit" name="make_decision" class="cdx-button" style="padding: 12px 32px;">
					<?php echo _AC('make_decision'); ?>
				</button>
			</div>
		<?php endif; ?>
	</section>

	<?php if (isset($_POST['show_source']) && isset($aValidator)): ?>
		<section class="cdx-card">
			<h2 class="cdx-typography-h2"><?php echo _AC('source');?></h2>
			<p style="color: #72777d; margin-bottom: 16px;"><?php echo _AC('source_note');?></p>
			<div style="background: #f8f9fa; padding: 16px; border: 1px solid #eaecf0; border-radius: 2px;">
				<?php echo $a_rpt->getSourceRpt();?>
			</div>
		</section>
	<?php endif; ?>

	<?php if (isset($seals) && is_array($seals)): ?>
		<section class="cdx-card">
			<h3 class="cdx-typography-h3"><?php echo _AC('valid_icons');?></h3>
			<p style="color: #72777d; margin-bottom: 16px;"><?php echo _AC('valid_icons_text');?></p>
			<div style="display: flex; flex-wrap: wrap; gap: 24px;">
				<?php foreach ($seals as $seal): ?>
					<div style="text-align: center;">
						<img src="<?php echo SEAL_ICON_FOLDER . $seal['seal_icon_name'];?>" alt="<?php echo $seal['title']; ?>" height="32" width="102" style="display: block; margin-bottom: 8px;"/>
						<pre style="font-size: 0.8em; background: #f8f9fa; padding: 8px; border: 1px solid #eaecf0; text-align: left; max-width: 400px; overflow: auto;">&lt;p&gt;
  &lt;a href="<?php echo AC_BASE_HREF; ?>checker/index.php?uri=referer&amp;gid=<?php echo $seal['guideline'].(isset($user_link_id) ? '&amp;id='.$user_link_id : '');?>"&gt;
    &lt;img src="<?php echo AC_BASE_HREF.SEAL_ICON_FOLDER . $seal['seal_icon_name'];?>" alt="<?php echo $seal['title']; ?>" height="32" width="102" /&gt;
  &lt;/a&gt;
&lt;/p&gt;</pre>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>
<br />
