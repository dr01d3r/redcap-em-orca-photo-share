<?php
/** @var \OrcaPhotoShare\ExternalModule\ExternalModule $module */

$module->addTime();
$module->initializeJavascriptModuleObject();

$module->preout($module->handleGetPhotos($project_id));
?>
    <div id="GOOGLE_PHOTOS_INDEX"></div>
    <script>
        const OrcaGooglePhotoShare = function() {
            return {
                jsmo: <?=$module->getJavascriptModuleObjectName()?>
            }
        };
    </script>
    <script type="module" src="<?=$module->getUrl('dist/index.js')?>"></script>
    <link rel="stylesheet" href="<?=$module->getUrl('dist/assets/index.css')?>">
<?php
$module->outputModuleVersionJS();