<?php
namespace OrcaPhotoShare\ExternalModule;

trait REDCapUtils {

    private $timers = [];

    public function addTime($key = null) {
        if ($key == null) {
            $key = "STEP " . count($this->timers);
        }
        $this->timers[] = [
            "label" => $key,
            "value" => microtime(true)
        ];
    }

    public function outputTimerInfo($showAll = false) {
        $initTime = null;
        $preTime = null;
        $curTime = null;
        foreach ($this->timers as $index => $timeInfo) {
            $curTime = $timeInfo;
            if ($preTime == null) {
                $initTime = $timeInfo;
            } else {
                $calcTime = round($curTime["value"] - $preTime["value"], 4);
                if ($showAll) {
                    echo "<p><i>{$timeInfo["label"]}: {$calcTime}</i></p>";
                }
            }
            $preTime = $curTime;
        }
        $calcTime = round($curTime["value"] - $initTime["value"], 4);
        echo "<p><i>Total Processing Time: {$calcTime} seconds</i></p>";
    }

    public function preout($content) {
        if (is_array($content) || is_object($content)) {
            echo "<pre>" . print_r($content, true) . "</pre>";
        } else {
            echo "<pre>$content</pre>";
        }
    }

    /**
     * Outputs the module directory folder name into the page footer, for easy reference.
     * @return void
     */
    public function outputModuleVersionJS() {
        $module_info = $this->getModuleName() . " (" . $this->VERSION . ")";
        echo "<script>$(function() { $('div#south table tr:first td:last, #footer').prepend('<span>$module_info</span>&nbsp;|&nbsp;'); });</script>";
    }

    public function generateTempFileName($timestampXmin=0)
    {
        $xMinFromNow = date("YmdHis", mktime(date("H"),date("i")+$timestampXmin,date("s"),date("m"),date("d"),date("Y")));
        return APP_PATH_TEMP . $xMinFromNow . "_" . substr(sha1(rand()), 0, 10);
    }
}