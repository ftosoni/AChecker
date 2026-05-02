<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>">

<head>
	<meta charset="UTF-8" />
	<title><?php echo SITE_NAME; ?> : <?php echo $page_title; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="Generator" content="AChecker - Adapted by Francesco Tosoni" />
	<meta name="description"
		content="Accessibility checker for MediaWiki and Wikipedia. Validate web pages against WCAG standards with a modern, high-performance interface.">
	<meta name="keywords"
		content="MediaWiki, Accessibility, WCAG, Checker, Wikipedia, Section 508, Web Accessibility, Francesco Tosoni, Toolforge">
	<meta name="author" content="Francesco Tosoni">

	<meta http-equiv="X-Content-Type-Options" content="nosniff">
	<meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">

	<!-- Icons & Canonical -->
	<link rel="shortcut icon" href="https://accessibility-checker.toolforge.org/favicon.ico" type="image/x-icon" />
	<link rel="canonical" href="https://accessibility-checker.toolforge.org/" />
	<link rel="preconnect" href="https://tools-static.wmflabs.org" crossorigin>

	<!-- Social Media (Open Graph) -->
	<meta property="og:type" content="website">
	<meta property="og:url" content="https://accessibility-checker.toolforge.org/">
	<meta property="og:title" content="MediaWiki Accessibility Checker | WCAG Validation Tool">
	<meta property="og:description"
		content="AI-enhanced accessibility checker for MediaWiki and Wikipedia. Search and validate web pages for accessibility compliance.">
	<meta property="og:image" content="https://accessibility-checker.toolforge.org/themes/codex/images/og-image.png">

	<!-- Structured Data (JSON-LD) -->
	<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "SoftwareApplication",
	  "name": "MediaWiki Accessibility Checker",
	  "url": "https://accessibility-checker.toolforge.org/",
	  "codeRepository": "https://github.com/ftosoni/mediawiki-accessibility-checker",
	  "sameAs": [
		"https://www.mediawiki.org/wiki/Special:MyLanguage/Accessibility_Checker",
		"https://toolhub.wikimedia.org/tools/accessibility-checker"
	  ],
	  "license": "https://www.gnu.org/licenses/gpl-2.0.html",
	  "applicationCategory": "DeveloperApplication",
	  "operatingSystem": "All",
	  "offers": {
		"@type": "Offer",
		"price": "0",
		"priceCurrency": "INR"
	  },
	  "description": "Comprehensive accessibility validation tool for MediaWiki ecosystems, providing detailed reports and source code highlighting based on WCAG standards.",
	  "author": {
		"@type": "Person",
		"name": "Francesco Tosoni",
		"sameAs": [
			"https://www.francescotosoni.it/",
			"https://pages.di.unipi.it/tosoni/",
			"https://orcid.org/0000-0001-8457-3866",
			"https://scholar.google.com/citations?user=8-0w_KAAAAAJ",
			"https://dblp.org/pid/317/5120-1.html",
			"https://www.scopus.com/authid/detail.uri?authorId=57223035885",
			"https://www.semanticscholar.org/author/Francesco-Tosoni/2267595223",
			"https://www.researchgate.net/scientific-contributions/Francesco-Tosoni-2193508092",
			"https://www.webofscience.com/wos/author/record/OFN-4534-2025",
			"https://www.iris.sssup.it/cris/rp/rp47259",
			"https://arpi.unipi.it/cris/rp/rp146832",
			"https://github.com/ftosoni",
			"https://gitlab.com/ftosoni",
			"https://linkedin.com/in/francesco-tosoni",
			"https://www.wikidata.org/wiki/Q135913272",
			"https://qlever.scholia.wiki//author/Q135913272",
			"https://diff.wikimedia.org/author/super-nabla/",
			"https://meta.wikimedia.org/wiki/User:Super-nabla",
			"https://phabricator.wikimedia.org/p/Super_nabla/"
		],
		"affiliation": {
		  "@type": "Organization",
		  "name": "Sant'Anna School of Advanced Studies, Pisa",
		  "url": "https://www.santannapisa.it/"
		},
		"jobTitle": "Postdoctoral researcher",
		"url": "https://www.santannapisa.it/en/francesco-tosoni"
	  }
	}
	</script>

	<!-- Font Assets -->
	<link href='https://tools-static.wmflabs.org/fontcdn/css?family=Ubuntu|JetBrains+Mono' rel='stylesheet'
		type='text/css'>

	<!-- Wikimedia Codex Design System -->
	<link rel="stylesheet" href="https://doc.wikimedia.org/codex/main/codex.style.css" />
	<link rel="stylesheet" href="<?php echo $base_path . 'themes/' . $theme; ?>/index.css" type="text/css" />

	<script src="<?php echo $base_path; ?>jscripts/lib/jquery.js" type="text/javascript"></script>
	<script src="<?php echo $base_path; ?>jscripts/lib/jquery-URLEncode.js" type="text/javascript"></script>
	<script type="text/javascript">
		var AC_BASE_HREF = '<?php echo AC_BASE_HREF; ?>';
	</script>
	<script src="<?php echo $base_path; ?>jscripts/AChecker.js" type="text/javascript"></script>

	<!-- Syntax Highlighting -->
	<link rel="stylesheet" href="https://tools-static.wmflabs.org/cdnjs/ajax/libs/prism/1.29.0/themes/prism.min.css" />
	<link rel="stylesheet"
		href="https://tools-static.wmflabs.org/cdnjs/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css" />
	<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/prism/1.29.0/prism.min.js"></script>
	<script
		src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
	<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/prism/1.29.0/components/prism-markup.min.js"></script>

	<?php echo $custom_head; ?>
</head>

<body onload="<?php echo $onload; ?>" class="cdx-typography">

	<header class="cdx-header">
		<div class="cdx-header__logo">
			<a href="<?php echo AC_BASE_HREF; ?>"
				style="text-decoration: none; font-weight: bold; font-size: 1.2em; color: var(--cdx-color-primary);">
				MediaWiki Accessibility Checker
			</a>
		</div>

		<div class="cdx-header__actions">
			<?php if (isset($user_name)): ?>
				<span style="margin-right: 16px;"><strong><?php echo _AC('welcome') . ' ' . $user_name; ?></strong></span>
				<a href="<?php echo AC_BASE_HREF; ?>logout.php"
					class="cdx-button cdx-button--weight-quiet"><?php echo _AC('logout'); ?></a>
			<?php else: ?>
				<a href="<?php echo AC_BASE_HREF; ?>login.php"
					class="cdx-button cdx-button--weight-quiet"><?php echo _AC('login'); ?></a>
				<a href="<?php echo AC_BASE_HREF; ?>register.php"
					class="cdx-button cdx-button--weight-quiet"><?php echo _AC('register'); ?></a>
			<?php endif; ?>
			<a href="<?php echo AC_BASE_HREF; ?>documentation/index.php"
				class="cdx-button cdx-button--weight-quiet"><?php echo _AC('handbook'); ?></a>
		</div>
	</header>

	<div id="center-content">




		<a name="content"></a>
		<?php global $msg;
		$msg->printAll(); ?>