<?php defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Block\File\Controller $controller
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Entity\File\File $bf
 * @var Concrete\Core\Block\View\BlockView $view
 * @var Concrete\Core\Application\Service\FileManager $al
 * @var string $fileLinkText
 * @var bool|null $forceDownload
 */
$forceDownload = $forceDownload ?? false;
?>
<div class="form-group">
    <?= $form->label('fID', t('File')) ?>
    <?= $al->file('ccm-b-file', 'fID', t('Choose File'), $bf) ?>
</div>
<div class="form-group">
    <?= $form->label('fileLinkText', t('Link Text')) ?>
    <?= $form->text('fileLinkText', $fileLinkText) ?>
</div>
<div class="form-group">
    <div class="form-check">
        <?= $form->checkbox('forceDownload', '1', $forceDownload) ?>
        <?= $form->label('forceDownload', t('Force file to download'), ['class' => 'form-check-label']) ?>
    </div>
</div>
