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
