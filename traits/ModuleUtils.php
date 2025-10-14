<?php
namespace OrcaPhotoShare\ExternalModule;

use Exception;

trait ModuleUtils {

    function getModuleConfig($project_id) {
        return [
            "album_name" => $this->getProjectSetting("album_name", $project_id),
            "album_id" => $this->getProjectSetting("album_id", $project_id),
            "client_id" => $this->getProjectSetting("client_id", $project_id),
            "client_secret" => $this->getProjectSetting("client_secret", $project_id),
            "refresh_token" => $this->getProjectSetting("refresh_token", $project_id),
            "refresh_token_expires" => $this->getProjectSetting("refresh_token_expires", $project_id),
            "base_domain" => $this->getBaseDomain(APP_PATH_WEBROOT_FULL),
            "redirect_uri" => $this->getUrl("callback.php", false, true),
            "scopes" => ExternalModule::AUTH_SCOPE
        ];
    }

    function validateConfig($config): bool
    {
        return !empty($config["client_id"])
            && !empty($config["client_secret"])
            && !empty($config["refresh_token"])
            && !empty($config["album_id"])
            ;
    }

    function handleInitializeConfigDashboard($project_id) {
        // get the config
        $config = $this->getModuleConfig($project_id);

        // obfuscate/remove the secrets
        if (!empty($config["client_secret"])) {
            $config["client_secret"] = str_pad("", 4, "*") . substr($config["client_secret"], -4);
        }
        if (!empty($config["refresh_token"])) {
            $config["refresh_token"] = str_pad("", 4, "*") . substr($config["refresh_token"], -4);
        }

        // send it away!
        return [
            "config" => $config
        ];
    }

    function handleInitializeMainDashboard($project_id) {
        return "Hello, handleInitializeMainDashboard!";
    }

    /**
     * @throws Exception
     */
    function handleSetClientInfo($project_id, $client_info) {
        // validate the input
        if (empty($client_info["client_id"]) || empty($client_info["client_secret"])) {
            throw new Exception("Client ID and Client Secret cannot be empty or missing!");
        }
        // save the new client info
        $this->setProjectSetting("client_id", $client_info["client_id"], $project_id);
        $this->setProjectSetting("client_secret", $client_info["client_secret"], $project_id);
        // reset the refresh_token settings
        $this->setProjectSetting("refresh_token", null, $project_id);
        $this->setProjectSetting("refresh_token_expires", null, $project_id);
        // generate a new authorization url
        $auth_url = $this->getAPIAuthorization($project_id);
        // send it back to the client, so it can handle the redirect
        return [
            "authUrl" => $auth_url
        ];
    }

    /**
     * @throws Exception
     */
    function handleSetAlbumName($project_id, $payload) {
        // validate the input
        if (empty($payload["album_name"])) {
            throw new Exception("Album Name cannot be empty or missing!");
        }
        // get the album_id from Google by album_name
        $album_id = $this->initAlbum($payload["album_name"], null);
        // set the album_id and album_name value in REDCap
        $this->setProjectSetting("album_id", $album_id, $project_id);
        $this->setProjectSetting("album_name", $payload["album_name"], $project_id);
        // send it back to the client so we can update the interface
        return $album_id;
    }

    function getAPIAuthorization($project_id) {
        $config = $this->getModuleConfig($project_id);
        $client = new \Google_Client();
        // Path to the downloaded client_secret.json file
        $client->setAuthConfig([
            "client_id" => $config["client_id"],
            "client_secret" => $config["client_secret"],
            "redirect_uris" => [
                $config["redirect_uri"]
            ]
        ]);
        // Request offline access to obtain a refresh token
        $client->setAccessType('offline');
        // Force the consent prompt so a refresh token is always returned on the first authorization.
        $client->setPrompt('consent');
        // Set the scopes for the APIs you need to access
        $client->setScopes(self::AUTH_SCOPE);
        // Create the authorization URL and redirect the user
        return filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL);
    }

    /**
     * @throws \Exception
     */
    function handleAuthorizationCallback($project_id) {
        $config = $this->getModuleConfig($project_id);
        $client = new \Google_Client();
        $client->setAuthConfig([
            "client_id" => $config["client_id"],
            "client_secret" => $config["client_secret"],
            "redirect_uris" => [
                $config["redirect_uri"]
            ]
        ]);
        $msgType = "error";
        $message = "";
        if (isset($_GET['code'])) {
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            // Check for errors
            if (isset($token['error'])) {
                $message = 'Authorization failed with error: ' . $token['error_description'];
            } else {
                // A refresh token is only returned on the *first* authorization for a user
                // Check if it exists and store it
                if (isset($token['refresh_token'])) {
                    $refreshToken = $token['refresh_token'];
                    // store the refresh_token
                    $this->setProjectSetting("refresh_token", $refreshToken, $project_id);
                    // calculate and store the expiration timestamp [created]+[refresh_token_expires_in] (UTC)
                    $dt_expires = $this->getLocalDateTimeFromUtcTimestamp($token["created"], $token["refresh_token_expires_in"]);
                    $this->setProjectSetting("refresh_token_expires", $dt_expires, $project_id);
                    // set some success messages
                    $message = "Authorization succeeded and a refresh_token was obtained and saved!";
                    $msgType = "message";
                } else {
                    $message = "Authorization succeeded and a code was found, but no refresh_token was found in the response!";
                }
            }
        } else {
            $message = "Initial authorization step succeeded; however, no authorization code was found in the response!";
        }
        $url = $this->getUrl("views/config.php") . "&" . http_build_query([
            $msgType => $message
        ]);
        header('Location: ' . filter_var($url, FILTER_SANITIZE_URL));
    }

    function handleGetPhotos($project_id) {
        // pull images directly from the project
        try {
            $result = [];
            $proj = new \Project($project_id);
            // get your token and url from the system settings
            $config = $this->getModuleConfig($project_id);
            // exit early if the api url or token is empty
            if (!$this->validateConfig($config)) {
                // log a message or just exit silently
                return;
            }
            // get eligible records with the image name ([file_picture])
            $eligible_data = \REDCap::getData([
                "project_id" => $project_id,
                "fields" => [
                    "file_picture",
                    "google_photo"
                ],
                "filterLogic" => "[file_picture] <> '' AND [approve] = '1' AND [share] <> '0' AND [google_photo] <> '1'"
            ]);
            // if eligible records isn't empty, get records we've already processed
            $save_data = [];
            if (!empty($eligible_data)) {
                $upload_tokens = [];
                // REDCap API file loop per record
                foreach ($eligible_data as $record_id => $record) {
                    $r = $record[$proj->firstEventId];
                    // get file from REDCap source
                    list ($mime_type, $file_name, $file_data) = \REDCap::getFile($r["file_picture"]);
                    // upload to google and cache the upload token result
                    $upload_tokens[] = $this->uploadImage($file_data, $file_name, $mime_type);
                    // prep for local save if upload was successful
                    $record[$proj->firstEventId]["google_photo"] = "1";
                    $save_data[$record_id] = $record;
                }
                if (!empty($upload_tokens)) {
                    // now that we have all the upload tokens, let's push to the album
                    // Google Photos API Upload (batch size = 50)
                    $this->addImagesToAlbum($upload_tokens, $config["album_name"], $config["album_id"]);
                    // save the local cache of images already processed, so I don't process them again!
                    if (!empty($save_data)) {
                        $save_result = \REDCap::saveData($project_id, "array", $save_data, "overwrite");
                        if (!empty($save_result["errors"])) {
                            $result["errors"] = $save_result["errors"];
                        }
                        $this->log("Successfully uploaded " . count($save_data) . " images to the Google Photos Album '" . $config["album_name"] . "'.");
                    }
                }
            }
        } catch (Exception $ex) {
            $result["errors"] = $ex->getMessage();
            $this->log($ex->getMessage());
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    function getLocalDateTimeFromUtcTimestamp($timestamp, $offset = 0) {
        $utcTimestamp = $timestamp + $offset;
        // Create a DateTime object from the UTC timestamp, specifying UTC timezone
        $dateTime = new \DateTime('@' . $utcTimestamp, new \DateTimeZone('UTC'));
        // Set the desired local timezone
        $localTimezone = new \DateTimeZone(date_default_timezone_get());
        $dateTime->setTimezone($localTimezone);
        // Output the local time in a specified format
        return $dateTime->format('Y-m-d H:i:s');
    }

    function getBaseDomain($url) {
        // Parse the URL to get the host
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === false || $host === null) {
            return null;
        }

        // Split the host into parts
        $parts = explode('.', $host);
        $num_parts = count($parts);

        // If the host is an IP address, return it directly
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }

        // Handle TLDs like ".co.uk" that have more than one part.
        // This is not foolproof without a list of all public suffixes,
        // but it works for many common cases.
        if ($num_parts > 2) {
            // Assume the last two parts form the base domain for most cases
            $base_domain = $parts[$num_parts - 2] . '.' . $parts[$num_parts - 1];

            // Special handling for multi-part TLDs (e.g., .co.uk, .com.au)
            // Check if the second-to-last part is a common multi-level TLD part
            $common_multi_level = ['com', 'co', 'org', 'net', 'gov'];
            if ($num_parts > 3 && in_array($parts[$num_parts - 2], $common_multi_level)) {
                $base_domain = $parts[$num_parts - 3] . '.' . $base_domain;
            }

            return $base_domain;
        }

        return $host;
    }
}