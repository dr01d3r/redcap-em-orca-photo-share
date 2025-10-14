<?php
namespace OrcaPhotoShare\ExternalModule;

require_once 'vendor/autoload.php';

use Google\ApiCore\ValidationException;
use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Photos\Library\V1\NewMediaItem;
use Google\Photos\Library\V1\PhotosLibraryClient;
use Google\Photos\Library\V1\PhotosLibraryResourceFactory;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

trait GooglePhotosUtils {

    const AUTH_SCOPE = [
        "https://www.googleapis.com/auth/photoslibrary.edit.appcreateddata",
        "https://www.googleapis.com/auth/photoslibrary.readonly.appcreateddata",
        "https://www.googleapis.com/auth/photoslibrary.appendonly",
//        "https://www.googleapis.com/auth/photoslibrary",
//        "https://www.googleapis.com/auth/photoslibrary.sharing",
//        "https://www.googleapis.com/auth/photoslibrary.readonly"
    ];

    private $_album_id = null;
    private $_credentials = null;
    private $_authCredentials = null;
    private $_photosLibraryClient = null;

    /**
     * @throws JsonException
     */
    function getCredentials() {
        if ($this->_credentials === null) {
            $this->_credentials = array_intersect_key($this->getModuleConfig($this->getProjectId()), [
                "client_id" => true,
                "client_secret" => true,
                "refresh_token" => true,
            ]);
        }
        return $this->_credentials;
    }

    /**
     * @throws JsonException
     */
    function getAuthCredentials(): UserRefreshCredentials
    {
        if ($this->_authCredentials === null) {
            $this->_authCredentials = new UserRefreshCredentials(
            /* Add your scope, client secret and refresh token here */
                self::AUTH_SCOPE,
                $this->getCredentials()
            );
        }
        return $this->_authCredentials;
    }

    /**
     * @throws ValidationException
     */
    function getPhotosLibraryClient($authCredentials): PhotosLibraryClient
    {
        if ($this->_photosLibraryClient === null) {
            $this->_photosLibraryClient = new PhotosLibraryClient(['credentials' => $authCredentials]);
        }
        return $this->_photosLibraryClient;
    }

    function getAlbums() {
        // initialize the client
        $photosLibraryClient = $this->getPhotosLibraryClient(
            $this->getAuthCredentials()
        );

        $response = $photosLibraryClient->listAlbums([
            "excludeNonAppCreatedData" => true
        ]);

        $albums = [];
        foreach ($response->iterateAllElements() as $album) {
            $albums[$album->getTitle()] = $album->getId();
        }
        return $albums;
    }

    function initAlbum($album_name, $album_id) {
        if (empty($this->_album_id)) {
            if (!empty($album_id)) {
                $this->_album_id = $album_id;
            } else {
                $albums = $this->getAlbums();
                // create album if it doesn't already exist
                if (isset($albums[$album_name])) {
                    $this->_album_id = $albums[$album_name];
                } else {
                    // create album
                    $this->_album_id = $this->createAlbum($album_name);
                }
            }
        }
        return $this->_album_id;
    }

    /**
     * @param $image_data
     * @param $file_name
     * @return string
     * @throws GuzzleException
     * @throws ValidationException
     * @throws JsonException
     */
    function uploadImage($image_data, $file_name, $mime_type) {
        // initialize the client
        $photosLibraryClient = $this->getPhotosLibraryClient(
            $this->getAuthCredentials()
        );

        if (empty($mime_type)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_buffer($finfo, $image_data);
            finfo_close($finfo);
        }

        // Upload the image and get the upload token
        $upload_token = $photosLibraryClient->upload(
            rawFile: $image_data,
            fileName: $file_name,
            mimeType: $mime_type
        );

        // return the token
        return $upload_token;
    }

    function addImagesToAlbum($upload_tokens, $album_name, $album_id) {
        try {
            // initialize the client
            $photosLibraryClient = $this->getPhotosLibraryClient(
                $this->getAuthCredentials()
            );

            // prep the new media items
            $new_media_items = [];
            foreach ($upload_tokens as $token) {
                $new_media_items[] = PhotosLibraryResourceFactory::newMediaItem($token);
            }

            // initialize the album to ensure it actually exists
            $this->initAlbum($album_name, $album_id);

            // push to the album
            $result = $photosLibraryClient->batchCreateMediaItems(
                newMediaItems: $new_media_items,
                optionalArgs: [
                    "albumId" => $this->_album_id
                ]
            );
            return $result->getNewMediaItemResults();
        } catch (\Google\ApiCore\ApiException $exception) {
            // Error during album creation
            throw $exception;
        } catch (\Google\ApiCore\ValidationException $e) {
            // Error during client creation
            throw $e;
        } catch (JsonException $e) {
            throw $e;
        }
    }

    function createAlbum($album_name) {
        try {
            // initialize the client
            $photosLibraryClient = $this->getPhotosLibraryClient(
                $this->getAuthCredentials()
            );

            // Create a new Album object with at title
            $newAlbum = PhotosLibraryResourceFactory::album($album_name);

            // Make the call to the Library API to create the new album
            $createdAlbum = $photosLibraryClient->createAlbum($newAlbum);

            // The creation call returns the ID of the new album
            return $createdAlbum->getId();
        } catch (\Google\ApiCore\ApiException $exception) {
            // Error during album creation
            throw $exception;
        } catch (\Google\ApiCore\ValidationException $e) {
            // Error during client creation
            throw $e;
        }
    }

    /**
     * @throws JsonException
     */
    function handleRefreshTokenRenewal() {
        // 4. Export the current state of the credentials object
        $current_credentials_data = $this->getAuthCredentials()->toArray();

        // 5. Check if a refresh token exists and save the entire array securely
        if (isset($current_credentials_data['refresh_token'])) {

            // IMPORTANT: Overwrite your stored data with the fresh configuration.
            /*
            file_put_contents(
                'my_stored_credentials.json',
                json_encode($current_credentials_data)
            );
            */
            echo "\nCredentials (including refresh token) have been updated and saved.";

        } else {
            echo "\nNo refresh token found in credentials.";
        }
    }
}