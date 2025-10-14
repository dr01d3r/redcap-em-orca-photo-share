<?php
/** @var \OrcaPhotoShare\ExternalModule\ExternalModule $module */

$module->preout("HELLO!");

try {
//    $module->preout($module->handleGetPhotos($project_id));
//    $module->preout($module->getAlbums($project_id));
} catch (Exception $ex) {
    $module->preout($ex);
}