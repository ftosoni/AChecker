<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>">
<head>
	<meta charset="UTF-8" />
	<title><?php echo SITE_NAME; ?> : <?php echo $page_title; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="Generator" content="AChecker - Adapted by Super nabla" />
	<link rel="shortcut icon" href="https://codesearch.wmcloud.org/favicon.ico" type="image/x-icon" />
	
	<!-- Font Assets -->
	<link href='https://tools-static.wmflabs.org/fontcdn/css?family=Ubuntu|JetBrains+Mono' rel='stylesheet' type='text/css'>
	
	<!-- Wikimedia Codex Design System -->
	<link rel="stylesheet" href="https://doc.wikimedia.org/codex/main/codex.style.css" />
	<link rel="stylesheet" href="<?php echo $base_path.'themes/'.$theme; ?>/index.css" type="text/css" />
	
	<script src="<?php echo $base_path; ?>jscripts/lib/jquery.js" type="text/javascript"></script>
	<script src="<?php echo $base_path; ?>jscripts/lib/jquery-URLEncode.js" type="text/javascript"></script>
	<script type="text/javascript">
		var AC_BASE_HREF = '<?php echo AC_BASE_HREF; ?>';
	</script>
	<script src="<?php echo $base_path; ?>jscripts/AChecker.js" type="text/javascript"></script>

	<!-- Syntax Highlighting -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup.min.js"></script>

	<?php echo $custom_head; ?>
</head>

<body onload="<?php echo $onload; ?>" class="cdx-typography">

<header class="cdx-header">
	<div class="cdx-header__logo">
		<a href="<?php echo AC_BASE_HREF; ?>" style="text-decoration: none; font-weight: bold; font-size: 1.2em; color: var(--cdx-color-primary);">
			MediaWiki Accessibility Checker
		</a>
	</div>
	
	<div class="cdx-header__actions">
		<?php if (isset($user_name)): ?>
			<span style="margin-right: 16px;"><strong><?php echo _AC('welcome'). ' '.$user_name; ?></strong></span>
			<a href="<?php echo AC_BASE_HREF; ?>logout.php" class="cdx-button cdx-button--weight-quiet"><?php echo _AC('logout'); ?></a>
		<?php else: ?>
			<a href="<?php echo AC_BASE_HREF; ?>login.php" class="cdx-button cdx-button--weight-quiet"><?php echo _AC('login'); ?></a>
			<a href="<?php echo AC_BASE_HREF; ?>register.php" class="cdx-button cdx-button--weight-quiet"><?php echo _AC('register'); ?></a>
		<?php endif; ?>
		<a href="<?php echo AC_BASE_HREF; ?>handbook/index.php" class="cdx-button cdx-button--weight-quiet"><?php echo _AC('handbook'); ?></a>
	</div>
</header>

<div id="center-content">




	<a name="content"></a>
	<?php global $msg; $msg->printAll();?>
