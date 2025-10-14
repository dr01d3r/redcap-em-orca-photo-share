<?php
/** @var \OrcaPhotoShare\ExternalModule\ExternalModule $this */

namespace OrcaPhotoShare\ExternalModule;

trait CronUtils {
    function cronEntryPoint() {
        // disallow any cron execution if not explicitly enabled in the Control Center
        if ($this->getSystemSetting("system-cron-enabled") !== "enabled") {
            return;
        }
        $projects = $this->getProjectsWithModuleEnabled();
        if (count($projects) > 0) {
            foreach ($projects as $project_id) {
                $Proj = new \Project($project_id);
                try {
                    // first ensure the module config has this as enabled
                    if ($this->getProjectSetting("cron-enabled", $project_id) === "enabled") {
                        $this->log("Executing Cron Job", [ "project_id" => $project_id ]);
                        // process reporting for all site codes
                        $this->handleGetPhotos($project_id);
                    }
                } catch (Exception $ex) {
                    $this->log("ERROR: " . $ex->getMessage(), [ "project_id" => $project_id ]);
                }
            }
        }
    }
}