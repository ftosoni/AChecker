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

define('AC_INCLUDE_PATH', '../include/');
include(AC_INCLUDE_PATH.'vitals.inc.php');
include(AC_INCLUDE_PATH.'handbook_pages.inc.php');

global $handbook_pages, $_pages;

if (isset($_GET['p'])) {
	$p = htmlentities($_GET['p']);
} else {
	// go to first handbook page defined in $handbook_pages
	foreach ($handbook_pages as $page_key => $page_value)
	{
		if (is_array($page_key))
		{
			if (isset($_pages[$page_key])) $p = $page_key;
		}
		else
		{
			if (isset($_pages[$page_value])) $p = $page_value;
		}
		if (isset($p)) break;
	}
}

// Prepare data for the theme
$page_title = _AC('achecker_handbook');
if (isset($_pages[$p])) {
    $page_title .= ' - ' . _AC($_pages[$p]['title_var']);
}

$_custom_head = '<link rel="stylesheet" href="' . AC_BASE_HREF . 'themes/' . $_SESSION['prefs']['PREF_THEME'] . '/handbook_styles.css" type="text/css" />';
include(AC_INCLUDE_PATH.'header.inc.php');

/**
 * Modern handbook TOC printer for Codex
 */
function hb_print_modern_toc($handbook_pages, $current_p) {
	global $_pages;
	echo '<ul class="handbook-sidebar-list">';
	foreach ($handbook_pages as $page_key => $page_value) {
		$id = is_array($page_value) ? $page_key : $page_value;
		if (isset($_pages[$id])) {
            $active_class = ($id == $current_p) ? ' handbook-sidebar-item--active' : '';
			echo '<li class="handbook-sidebar-item' . $active_class . '">';
			echo '<a href="index.php?p='.$id.'">'._AC($_pages[$id]['title_var']).'</a>';
			if (is_array($page_value)) {
				hb_print_modern_toc($page_value, $current_p);
			}
			echo '</li>';
		}
	}
	echo '</ul>';
}
?>

<div class="handbook-container">
    <!-- Sidebar -->
    <aside class="handbook-sidebar">
        <h2 class="handbook-sidebar-title">
            <?php echo _AC('handbook_toc'); ?>
        </h2>
        <nav>
            <?php hb_print_modern_toc($handbook_pages, $p); ?>
        </nav>
    </aside>

    <!-- Content Area -->
    <main class="handbook-main">
        <?php
        $this_page = $p;
        // Logic from handbook_header.inc.php to get prev/next
        $merged_array = array();
        function merge_page_array_local($pages_array, &$merged) {
            foreach ($pages_array as $page_key => $page_value) {
                if (is_array($page_value)) {
                    $merged[] = $page_key;
                    merge_page_array_local($page_value, $merged);
                } else {
                    $merged[] = $page_value;
                }
            }
        }
        merge_page_array_local($handbook_pages, $merged_array);
        
        $prev_page = null;
        $next_page = null;
        foreach ($merged_array as $key => $page) {
            if (strcmp($page, $p) == 0) {
                if ($key >= 1) $prev_page = $merged_array[$key - 1];
                if ($key < count($merged_array) - 1) $next_page = $merged_array[$key + 1];
                break;
            }
        }
        ?>

        <!-- Top Navigation -->
        <div class="handbook-nav-top">
            <div>
                <?php if ($prev_page): ?>
                    <a href="index.php?p=<?php echo $prev_page; ?>" class="handbook-nav-link">
                        &larr; <?php echo _AC($_pages[$prev_page]['title_var']); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($next_page): ?>
                    <a href="index.php?p=<?php echo $next_page; ?>" class="handbook-nav-link">
                        <?php echo _AC($_pages[$next_page]['title_var']); ?> &rarr;
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <article class="handbook-article">
            <?php
            if (isset($_pages[$p]['guide'])) {
                echo _AC($_pages[$p]['guide']);
            } else {
                echo '<p>' . _AC('page_not_found') . '</p>';
            }
            ?>
        </article>

        <!-- Bottom Navigation -->
        <div class="handbook-nav-bottom">
            <div>
                <?php if ($prev_page): ?>
                    <span class="handbook-nav-label"><?php echo _AC('previous_chapter'); ?></span>
                    <a href="index.php?p=<?php echo $prev_page; ?>" class="handbook-nav-link handbook-nav-link--large">
                        &larr; <?php echo _AC($_pages[$prev_page]['title_var']); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div style="text-align: right;">
                <?php if ($next_page): ?>
                    <span class="handbook-nav-label"><?php echo _AC('next_chapter'); ?></span>
                    <a href="index.php?p=<?php echo $next_page; ?>" class="handbook-nav-link handbook-nav-link--large">
                        <?php echo _AC($_pages[$next_page]['title_var']); ?> &rarr;
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="handbook-license">
            <p>All text is available under the terms of the <a href="https://www.gnu.org/licenses/fdl-1.3.html">GNU Free Documentation License</a>.</p>
        </div>
    </main>
</div>

<?php include(AC_INCLUDE_PATH.'footer.inc.php'); ?>
