<div class="cdx-message cdx-message--error" role="alert" style="margin-bottom: 16px;">
    <span class="cdx-message__icon"></span>
    <div class="cdx-message__content">
        <strong><?php echo _AC('the_follow_errors_occurred'); ?></strong>
        <?php if (is_array($item)) : ?>
            <ul style="margin-top: 8px;">
            <?php foreach($item as $e) : ?>
                <li><?php echo $e; ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
