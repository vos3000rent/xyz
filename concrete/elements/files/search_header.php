<?php
defined('C5_EXECUTE') or die("Access Denied.");

/* @var Concrete\Controller\Element\Search\Files\Header $controller */
/* @var Concrete\Core\Form\Service\Form $form */
/* @var Concrete\Core\Validation\CSRF\Token $token */
/* @var Concrete\Core\View\BasicFileView $view */
/* @var bool $includeBreadcrumb */
/* @var string|null $breadcrumbClass */
/* @var Concrete\Core\Url\UrlImmutable $addFolderAction */
/* @var int $currentFolder */
/* @var int|null $imageMaxWidth */
/* @var int|null $imageMaxHeight */
/* @var int $jpegQuality */
/* @var array $itemsPerPageOptions */
/* @var int $itemsPerPage */
?>

<div class="ccm-header-search-form ccm-ui" data-header="file-manager">
    <?php if ($includeBreadcrumb) { ?>
        <div class="ccm-search-results-breadcrumb <?= (isset($breadcrumbClass)) ? $breadcrumbClass : ''; ?>">
        </div>
    <?php } ?>

    <form method="get" class="form-inline" action="<?php echo URL::to('/ccm/system/search/files/basic')?>">

        <div class="input-group">

            <div class="ccm-header-search-form-input btn-group">
                <a class="ccm-header-reset-search" href="#" data-button-action-url="<?=URL::to('/ccm/system/search/files/clear')?>" data-button-action="clear-search"><?=t('Reset Search')?></a>
                <a class="ccm-header-launch-advanced-search" href="<?php echo URL::to('/ccm/system/dialogs/file/advanced_search')?>" data-launch-dialog="advanced-search"><?=t('Advanced')?></a>
                <input type="text" class="form-control" autocomplete="off" name="fKeywords" placeholder="<?=t('Search')?>">
            </div>

            <div class="btn-group">
                <?php
                if (!empty($itemsPerPageOptions)) { ?>
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span id="selected-option"><?= $itemsPerPage; ?></span> <span class="caret"></span></button>
                    <ul class="dropdown-menu" data-action="<?= URL::to('/ccm/system/file/folder/contents'); ?>">
                        <li class="dropdown-header"><?=t('Items per page')?></li>
                        <?php foreach ($itemsPerPageOptions as $itemsPerPageOption) { ?>
                            <li data-items-per-page="<?= $itemsPerPageOption; ?>" <?= ($itemsPerPageOption === $itemsPerPage) ? 'class="active"' : ''; ?>>
                                <a href="#"><?= $itemsPerPageOption; ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php }
                ?>
                <button class="btn btn-info" type="submit"><i class="fa fa-search"></i></button>
            </div>

            <div class="btn-group"  style="margin-left: 10px;">
                <a class="btn btn-info" data-dialog="add-files" href="#" id="ccm-file-manager-upload"><i class="fa fa-upload"></i> <?=t('Upload')?></a>
                <div class="btn-group">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-folder-open-o"></i> Folders
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a data-launch-dialog="navigate-file-manager" href="#"><?=t('Open Folder')?></a></li>
                        <li><a href="#" data-launch-dialog="add-file-manager-folder"><?=t('New Folder')?></a></li>
                    </ul>
                </div>
            </div>

        </div><!-- /input-group -->
    </form>
</div>
<div style="display: none">
    <div class="dialog-buttons"></div>
    <div data-dialog="add-file-manager-folder" class="ccm-ui">
        <form data-dialog-form="add-folder" method="post" action="<?=$addFolderAction?>">
            <?=$token->output('add_folder')?>
            <?=$form->hidden('currentFolder', $currentFolder);?>
            <div class="form-group">
                <?=$form->label('folderName', t('Folder Name'))?>
                <?=$form->text('folderName', '', array('autofocus' => true))?>
            </div>
        </form>
        <div class="dialog-buttons">
            <button class="btn btn-default pull-left" data-dialog-action="cancel"><?=t('Cancel')?></button>
            <button class="btn btn-primary pull-right" data-dialog-action="submit"><?=t('Add Folder')?></button>
        </div>
    </div>

</div>
