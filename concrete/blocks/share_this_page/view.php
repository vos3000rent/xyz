<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="ccm-block-share-this-page">
    <ul class="list-inline">
    <?php foreach ($selected as $service) {
    ?>
        <li><a href="<?= h($service->getServiceLink()) ?>" aria-label="<?= h($service->getDisplayName()) ?>"><?=$service->getServiceIconHTML()?></a></li>
    <?php 
} ?>
    </ul>
</div>