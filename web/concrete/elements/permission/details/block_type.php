<?
defined('C5_EXECUTE') or die("Access Denied.");
?>

<?php $pk = PermissionKey::getByID($_REQUEST['pkID']);?>

<?php Loader::element("permission/detail", array('permissionKey' => $pk)); ?>

<script type="text/javascript">
var ccm_permissionDialogURL = '<?=REL_DIR_FILES_TOOLS_REQUIRED?>/permissions/dialogs/block_type'; 
</script>
