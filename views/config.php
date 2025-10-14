<?php
/** @var \OrcaPhotoShare\ExternalModule\ExternalModule $module */

$module->addTime();
$module->initializeJavascriptModuleObject();
?>
    <div id="GOOGLE_PHOTOS_CONFIG"></div>
    <script>
        const OrcaGooglePhotoShare = function() {
            return {
                jsmo: <?=$module->getJavascriptModuleObjectName()?>
            }
        };
    </script>
    <script type="module" src="<?=$module->getUrl('dist/config.js')?>"></script>
    <link rel="stylesheet" href="<?=$module->getUrl('dist/assets/config.css')?>">
<?php
$module->outputModuleVersionJS();