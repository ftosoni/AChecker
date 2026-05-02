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

</div> <!-- end handbook-content -->

<div class="handbook-nav" style="margin-top: 48px; border-top: 1px solid #eaecf0; border-bottom: none; padding-top: 16px;">
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

<footer style="margin-top: 48px; padding: 24px; background: #f8f9fa; border-radius: 2px; color: #72777d; font-size: 0.85em; text-align: center;">
	<p>All text is available under the terms of the <a href="https://www.gnu.org/licenses/fdl-1.3.html" style="color: #3366cc;">GNU Free Documentation License</a>.</p>
	<p style="margin-top: 8px;">Powered by AChecker | Adapted for MediaWiki by Francesco Tosoni</p>
</footer>

</body>
</html>
