<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<?php if(isset($hidden_vars)): ?>
	<?php echo $hidden_vars; ?>
<?php endif; ?>

<div class="cdx-card" style="padding: 24px; border: 1px solid #a2a9b1; background: #fff; margin-bottom: 24px;">
    <div class="cdx-message cdx-message--notice" role="status" style="margin-bottom: 16px;">
        <span class="cdx-message__icon"></span>
        <div class="cdx-message__content">
            <?php if (is_array($item)) : ?>
                <?php foreach($item as $e) : ?>
                    <p><?php echo $e; ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div style="display: flex; gap: 16px; justify-content: flex-end; margin-top: 24px;">
        <button type="submit" name="submit_yes" class="cdx-button cdx-button--action-progressive cdx-button--weight-primary">
            <?php echo $button_yes_text; ?>
        </button>
        <?php if(!$hide_button_no): ?>
            <button type="submit" name="submit_no" class="cdx-button">
                <?php echo $button_no_text; ?>
            </button>
        <?php endif; ?>
    </div>
</div>
</form>
