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
        <div class="cdx-footer__row">
                <span>Developed by: <a href="https://meta.wikimedia.org/wiki/Special:MyLanguage/User:Super_nabla" target="_blank"
                                style="color: #3366cc; font-weight: 500;">Super nabla 🪰</a> (<a
                                href="https://meta.wikimedia.org/wiki/Special:MyLanguage/Indic_MediaWiki_Developers_User_Group"
                                target="_blank" style="color: #3366cc; font-weight: 500;">Indic MediaWiki Developers UG</a>)</span>
        </div>
        
        <div class="cdx-footer__row cdx-footer__links">
                <span>Licence: <a href="https://github.com/ftosoni/mediawiki-accessibility-checker/blob/main/LICENSE"
                                target="_blank">GPL 2.0</a></span>
                <span class="cdx-footer__pipe">|</span>
                <span>Documentation (<a href="https://www.mediawiki.org/wiki/Special:MyLanguage/Accessibility_Checker"
                                target="_blank">MediaWiki</a> · <a
                                href="documentation/web_service_api.php" target="_blank">API</a>)</span>
                <span class="cdx-footer__pipe">|</span>
                <span><a href="https://www.wikidata.org/wiki/Q139617094" target="_blank">Wikidata
                                Q-item</a></span>
                <span class="cdx-footer__pipe">|</span>
                <span><a href="https://toolhub.wikimedia.org/tools/toolforge-accessibility-checker" target="_blank">Toolhub</a></span>
                <span class="cdx-footer__pipe">|</span>
                <span>Source (<a href="https://github.com/ftosoni/mediawiki-accessibility-checker" target="_blank">GitHub</a> · <a
                                href="https://archive.softwareheritage.org/browse/origin/directory/?origin_url=https://github.com/ftosoni/mediawiki-accessibility-checker"
                                target="_blank">SWH</a>)</span>
                <span class="cdx-footer__pipe">|</span>
                <span><a href="https://github.com/ftosoni/mediawiki-accessibility-checker/issues" target="_blank">Report an issue</a></span>
        </div>

        <div class="cdx-footer__info">
                <span>2026-05-02</span>
                <span class="cdx-footer__pipe">|</span>
                <span>Powered by <a href="https://github.com/cg-a11y/AChecker" target="_blank">AChecker</a> by the <a href="https://idrc.ocadu.ca/"
                                target="_blank">Inclusive Design Institute</a>.</span>
        </div>
</footer>

</body>

</html>