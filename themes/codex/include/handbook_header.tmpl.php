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
?>
<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE_CODE; ?>">
<head>
	<meta charset="UTF-8" />
	<title><?php echo _AC('achecker_documentation'); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="stylesheet" href="https://doc.wikimedia.org/codex/main/codex.style.css" />
	<link rel="stylesheet" href="<?php echo $base_path . 'themes/' . $theme; ?>/index.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo $base_path . 'themes/' . $theme; ?>/handbook_styles.css" type="text/css" />
	<style>
		body { padding: 24px; background: #fff; }
		.handbook-nav { 
			display: flex; 
			justify-content: space-between; 
			margin-bottom: 24px; 
			padding-bottom: 16px; 
			border-bottom: 1px solid #eaecf0;
			font-size: 0.9em;
		}
		.handbook-nav a { 
			color: #3366cc; 
			text-decoration: none; 
			font-weight: bold;
		}
		.handbook-nav a:hover { text-decoration: underline; }
		.handbook-content { line-height: 1.6; color: #202122; }
		.handbook-content h1 { border-bottom: 1px solid #a2a9b1; margin-bottom: 0.5em; padding-bottom: 0.25em; }
		.handbook-content h2 { border-bottom: 1px solid #a2a9b1; margin-top: 1.5em; }
	</style>
</head>

<body onload="doparent();" class="cdx-typography">
<script type="text/javascript">
// <!--
function doparent() {
	if (parent.toc && parent.toc.highlight) parent.toc.highlight('id<?php echo $this_page; ?>');
}
// -->
</script>

<div class="handbook-nav">
	<div>
		<?php if (isset($prev_page)): ?>
			<span style="color: #72777d;"><?php echo _AC('previous_chapter'); ?>:</span> 
			<a href="frame_content.php?p=<?php echo $prev_page; ?>" accesskey="," title="<?php echo _AC($pages[$prev_page]['title_var']); ?> Alt+,">
				&larr; <?php echo _AC($pages[$prev_page]['title_var']); ?>
			</a>
		<?php endif; ?>
	</div>
	<div>
		<?php if (isset($next_page)): ?>
			<span style="color: #72777d;"><?php echo _AC('next_chapter'); ?>:</span> 
			<a href="frame_content.php?p=<?php echo $next_page; ?>" accesskey="." title="<?php echo _AC($pages[$next_page]['title_var']); ?> Alt+.">
				<?php echo _AC($pages[$next_page]['title_var']); ?> &rarr;
			</a>
		<?php endif; ?>
	</div>
</div>

<div class="handbook-content">
