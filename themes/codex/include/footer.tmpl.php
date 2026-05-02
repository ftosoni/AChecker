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

<footer
        style="margin: 4rem auto 2rem; width: 100%; max-width: 900px; text-align: center; font-size: 0.8rem; color: #72777d; border-top: 1px solid #eaecf0; display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; align-items: center; padding-top: 2rem;">
        <span>Created by: <a href="https://meta.wikimedia.org/wiki/Special:MyLanguage/User:Super_nabla" target="_blank"
                        style="color: #3366cc;">Super nabla 🪰</a> (<a
                        href="https://meta.wikimedia.org/wiki/Special:MyLanguage/Indic_MediaWiki_Developers_User_Group"
                        target="_blank" style="color: #3366cc;">Indic MediaWiki Developers UG</a>)</span>
        <span style="opacity: 0.3;">|</span>
        <span>Licence: <a href="https://github.com/ftosoni/mediawiki-accessibility-checker/blob/main/LICENSE"
                        target="_blank" style="color: inherit;">GPL 2.0</a></span>
        <span style="opacity: 0.3;">|</span>
        <span>Documentation (<a href="https://www.mediawiki.org/wiki/Special:MyLanguage/Accessibility_Checker"
                        target="_blank" style="color: inherit;">MediaWiki</a> · <a
                        href="documentation/web_service_api.php" target="_blank" style="color: inherit;">API</a>)</span>
        <span style="opacity: 0.3;">|</span>
        <span><a href="https://www.wikidata.org/wiki/Q139617094" target="_blank" style="color: inherit;">Wikidata
                        Q-item</a></span>
        <span style="opacity: 0.3;">|</span>
        <span><a href="https://toolhub.wikimedia.org/tools/toolforge-accessibility-checker" target="_blank"
                        style="color: inherit;">Toolhub</a></span>
        <span style="opacity: 0.3;">|</span>
        <span>Source (<a href="https://github.com/ftosoni/mediawiki-accessibility-checker" target="_blank"
                        style="color: inherit;">GitHub</a> · <a
                        href="https://archive.softwareheritage.org/browse/origin/directory/?origin_url=https://github.com/ftosoni/mediawiki-accessibility-checker"
                        target="_blank" style="color: inherit;">SWH</a>)</span>
        <span style="opacity: 0.3;">|</span>
        <span><a href="https://github.com/ftosoni/mediawiki-accessibility-checker/issues" target="_blank"
                        style="color: inherit;">Report an issue</a></span>

        <div
                style="width: 100%; margin-top: 1.5rem; border-top: 1px solid #eaecf0; padding-top: 1.5rem; color: #72777d; display: flex; justify-content: center; gap: 2rem;">
                <span>2026-05-02</span>
                <span>Powered by <a href="https://github.com/cg-a11y/AChecker" target="_blank"
                                style="color: inherit;">AChecker</a> by the <a href="https://idrc.ocadu.ca/"
                                target="_blank" style="color: inherit;">Inclusive Design Institute</a>.</span>
        </div>
</footer>

</body>

</html>