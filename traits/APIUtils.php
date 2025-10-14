<?php
namespace OrcaPhotoShare\ExternalModule;

use Exception;

trait APIUtils {
    function call($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    /**
     * @throws Exception
     */
    public function apiGetRecordFile($url, $token, $record, $field) {
        $api_result = $this->call($url, [
            "token" => $token,
            "content" => "file",
            "action" => "export",
            "record" => $record,
            "field" => $field,
            "returnFormat" => "json",
        ]);
        return $api_result;
    }

    /**
     * @param $url
     * @param $token
     * @param $data
     * @param string $overwriteBehavior
     * @return mixed
     * @throws Exception
     */
    public function apiImportRecords($url, $token, $data, string $overwriteBehavior = "normal")
    {
        $api_result = $this->call($url, [
            "token" => $token,
            "content" => "record",
            "format" => "json",
            "returnContent" => "count",
            "returnFormat" => "json",
            "type" => "flat",
            "overwriteBehavior" => $overwriteBehavior,
            "forceAutoNumber" => "false",
            "data" => json_encode($data)
        ]);
        $json_result = json_decode($api_result,true);
        if ($json_result === null) {
            throw new Exception("Unable to parse API response");
        }
        return $json_result;
    }

    /**
     * @param $url
     * @param $token
     * @return mixed
     * @throws Exception
     */
    public function apiGetProjectInfo($url, $token) {
        $api_result = $this->call($url, [
            "token" => $token,
            "content" => "project",
            "format" => "json",
            "returnFormat" => "json",
        ]);
        $json_result = json_decode($api_result,true);
        if ($json_result === null) {
            throw new Exception("Unable to parse API response");
        }
        return $json_result;
    }

    /**
     * @param $url
     * @param $token
     * @param array $fields
     * @param array $records
     * @param null $filterLogic
     * @return mixed
     * @throws Exception
     */
    public function apiGetRecords($url, $token, array $fields = [], array $records = [], $filterLogic = null)
    {
        $api_result = $this->call($url, [
            "token" => $token,
            "content" => "record",
            "format" => "json",
            "returnFormat" => "json",
            "type" => "flat",
            "csvDelimiter" => "",
            "records" => $records,
            "fields" => $fields,
            "rawOrLabel" => "raw",
            "rawOrLabelHeaders" => "raw",
            "exportCheckboxLabel" => "false",
            "exportSurveyFields" => "false",
            "exportDataAccessGroups" => "false",
            "filterLogic" => $filterLogic
        ]);

        $json_result = json_decode($api_result,true);
        if ($json_result === null) {
            throw new Exception("Unable to parse API response");
        }
        return $json_result;
    }
}