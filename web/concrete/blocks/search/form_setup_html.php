<?php
defined('C5_EXECUTE') or die("Access Denied.");
$form = Loader::helper('form');
$searchWithinOther=($searchObj->baseSearchPath!=Page::getCurrentPage()->getCollectionPath() && $searchObj->baseSearchPath!='' && strlen($searchObj->baseSearchPath)>0)?true:false;

/**
 * Post to another page, get page object.
 */
$basePostPage = Null;
if (isset($searchObj->postTo_cID) && intval($searchObj->postTo_cID) > 0) {
    $basePostPage = Page::getById($searchObj->postTo_cID);
} else if ($searchObj->pagePath != Page::getCurrentPage()->getCollectionPath() && strlen($searchObj->pagePath)) {
    $basePostPage = Page::getByPath($searchObj->pagePath);
}
/**
 * Verify object.
 */
if (is_object($basePostPage) && $basePostPage->isError()) {
    $basePostPage = NULL;
}
?>

<? if (!$controller->indexExists()) { ?>
    <div class="ccm-error"><?=t('The search index does not appear to exist. This block will not function until the reindex job has been run at least once in the dashboard.')?></div>
<? } ?>

<fieldset>

    <div class='form-group'>
        <label for='title'><?=t('Title')?>:</label>
        <?=$form->text('title',$searchObj->title);?>
    </div>

    <div class='form-group'>
        <label for='buttonText'><?=t('Button Text')?>:</label>
        <?=$form->text('buttonText',$searchObj->buttonText);?>
    </div>
    <div class='form-group'>
        <label for='title' style="margin-bottom: 0px;"><?=t('Search for Pages')?>:</label>
        <div class="radio">
            <label for="baseSearchPathEverywhere">
                <input type="radio" name="baseSearchPath" id="baseSearchPathEverywhere" value="" <?=($searchObj->baseSearchPath=='' || !$searchObj->baseSearchPath)?'checked':''?> onchange="searchBlock.pathSelector(this)" />
                <?=t('Everywhere')?>
            </label>
        </div>
        <div class="radio">
            <label for="baseSearchPathThis">
                <input type="radio" name="baseSearchPath" id="baseSearchPathThis" value="<?=Page::getCurrentPage()->getCollectionPath()?>" <?=( $searchObj->baseSearchPath != '' && $searchObj->baseSearchPath==Page::getCurrentPage()->getCollectionPath() )?'checked':''?> onchange="searchBlock.pathSelector(this)" >
                <?=t('Beneath this Page')?>
            </label>
        </div>
        <div class="radio">
            <label for="baseSearchPathOther">
                <input type="radio" name="baseSearchPath" id="baseSearchPathOther" value="OTHER" onchange="searchBlock.pathSelector(this)" <?=($searchWithinOther)?'checked':''?>>
                <?=t('Beneath Another Page')?>
                <div id="basePathSelector" style="display:<?=($searchWithinOther)?'block':'none'?>" >

                    <? $select_page = Loader::helper('form/page_selector');
                    if ($searchWithinOther) {
                        $cpo = Page::getByPath($baseSearchPath);
                        if (is_object($cpo)) {
                            print $select_page->selectPage('searchUnderCID', $cpo->getCollectionID());
                        } else {
                            print $select_page->selectPage('searchUnderCID');
                        }
                    } else {
                        print $select_page->selectPage('searchUnderCID');
                    }
                    ?>
                </div>
            </label>
        </div>
    </div>
    <div class='form-group'>
        <label for='title' style="margin-bottom: 0px;"><?=t('Results Page')?>:</label>
        <div class="checkbox">
            <label for="ccm-searchBlock-externalTarget">
                <input id="ccm-searchBlock-externalTarget" name="externalTarget" type="checkbox" value="1" <?=(strlen($searchObj->resultsURL) || $basePostPage !== NULL)?'checked':''?> />
                <?=t('Post Results to a Different Page')?>
            </label>
        </div>
        <div id="ccm-searchBlock-resultsURL-wrap" class="input" style=" <?=(strlen($searchObj->resultsURL) || $basePostPage !== NULL)?'':'display:none'?>" >
            <?
            if ($basePostPage !== NULL) {
                print $select_page->selectPage('postTo_cID', $basePostPage->getCollectionID());
            } else {
                print $select_page->selectPage('postTo_cID');
            }
            ?>
            <?=t('OR Path')?>:
            <?=$form->text('resultsURL',$searchObj->resultsURL);?>
        </div>
    </div>
    <div class='form-group'>
        <label for='buttonText'><?=t('Fulltext Search')?>:</label>
        <br>
        <?=$form->checkbox('isFulltextSearch', 1, $searchObj->isFulltextSearch);?> Enable
    </div>

</fieldset>
