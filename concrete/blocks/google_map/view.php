<?php defined('C5_EXECUTE') or die('Access Denied.');

$c = Page::getCurrentPage();
if ($c->isEditMode()) {
    $loc = Localization::getInstance();
    $loc->pushActiveContext(Localization::CONTEXT_UI); ?>
	<div class="ccm-edit-mode-disabled-item" style="width: <?php echo $width; ?>; height: <?php echo $height; ?>">
		<div style="padding: 80px 0px 0px 0px"><?=t('Google Map disabled in edit mode.'); ?></div>
	</div>
    <?php
    $loc->popActiveContext();
} else { ?>
	<?php  if (strlen($title) > 0) { ?>
        <<?php echo $titleFormat; ?>><?php echo h($title); ?></<?php echo $titleFormat; ?>>
	<?php  } ?>
	<div class="googleMapCanvas"
         style="width: <?=$width; ?>; height: <?=$height; ?>"
         data-zoom="<?=$zoom; ?>"
         data-latitude="<?=$latitude; ?>"
         data-longitude="<?=$longitude; ?>"
         data-scrollwheel="<?=(bool) $scrollwheel ? 'true' : 'false'; ?>"
         data-draggable="<?=(bool) $scrollwheel ? 'true' : 'false'; ?>"
    >
    </div>
<?php  } ?>