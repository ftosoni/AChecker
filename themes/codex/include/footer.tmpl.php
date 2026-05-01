<?php
if (!defined('AC_INCLUDE_PATH')) {
    exit;
}
global $languageManager, $_my_uri;
?>

<?php if ($languageManager->getNumEnabledLanguages() > 1): ?>
    <div
        style="margin-top: 40px; padding: 24px; background: #fff; border: 1px solid var(--cdx-border-color); border-radius: 2px;">
        <div style="font-size: 0.9em; color: var(--cdx-color-subtle); margin-bottom: 12px;">
            <?php echo _AC('translate_to'); ?>
        </div>
        <?php $languageManager->printList($_SESSION['lang'], 'lang', 'lang', htmlspecialchars($_my_uri)); ?>
    </div>
<?php endif; ?>

</div> <!-- end center-content div -->

<footer class="cdx-footer">
    <div
        style="display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; font-size: 0.9em; color: var(--cdx-color-subtle);">
        <span>
            Maintained by <a href="https://meta.wikimedia.org/wiki/User:Super_nabla" target="_blank"
                style="color: #3366cc;">Super nabla 🪰</a>
            (<a href="https://meta.wikimedia.org/wiki/Indic_MediaWiki_Developers_User_Group" target="_blank"
                style="color: #3366cc;">Indic MediaWiki Developers UG</a>)
        </span>
        <span style="opacity: 0.3">|</span>
        <span>
            Powered by <a href="https://github.com/cg-a11y/AChecker" target="_blank"
                style="color: inherit;">AChecker</a> (Licensed under <a
                href="https://github.com/cg-a11y/AChecker/blob/master/LICENSE" target="_blank"
                style="color: inherit;">GNU GPL</a>) by Inclusive Design Institute
        </span>
        <span style="opacity: 0.3">|</span>
        <span>
            <a href="documentation/web_service_api.php" target="_blank" style="color: inherit;">API Documentation</a>
        </span>
        <span style="opacity: 0.3">|</span>
        <span>
            Source (<a href="https://github.com/ftosoni/AChecker" target="_blank" style="color: inherit;">GitHub</a>)
        </span>
    </div>

    <div
        style="width: 100%; margin-top: 1.5rem; border-top: 1px solid #eaecf0; padding-top: 1.5rem; color: #72777d; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; font-size: 0.85em;">
        <span>2026-05-01</span>
    </div>
</footer>

</body>

</html>