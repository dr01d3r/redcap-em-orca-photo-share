<?php
namespace OrcaPhotoShare\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once __DIR__ . '/vendor/autoload.php';

require_once 'traits\APIUtils.php';
require_once 'traits\CronUtils.php';
require_once 'traits\ModuleUtils.php';
require_once 'traits\REDCapUtils.php';
require_once 'traits\RequestHandlers.php';
require_once 'traits\GooglePhotosUtils.php';

class ExternalModule extends AbstractExternalModule {
    use APIUtils;
    use CronUtils;
    use ModuleUtils;
    use REDCapUtils;
    use RequestHandlers;
    use GooglePhotosUtils;

    const ALBUM_TITLE = "REDCapCon 2025 Photobooth";
}