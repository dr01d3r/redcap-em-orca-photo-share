<?php
namespace OrcaPhotoShare\ExternalModule;

require_once 'vendor/autoload.php';

use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Photos\Library\V1\NewMediaItem;
use Google\Photos\Library\V1\PhotosLibraryClient;
use Google\Photos\Library\V1\PhotosLibraryResourceFactory;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

trait GooglePhotosUtils {

    private $_album_id = null;
    private $_credentials = null;
    private $_authCredentials = [];
    private $_photosLibraryClient = null;

    function getCredentials($project_id) {
        if ($this->_credentials === null) {
            $this->_credentials = array_intersect_key($this->getModuleConfig($project_id), [
                "client_id" => true,
                "client_secret" => true,
                "refresh_token" => true,
            ]);
        }
        return $this->_credentials;
    }

    function getAuthCredentials($project_id): UserRefreshCredentials
    {
        if ($this->_authCredentials[$project_id] === null) {
            $this->_authCredentials[$project_id] = new UserRefreshCredentials(
            /* Add your scope, client secret and refresh token here */
                self::AUTH_SCOPE,
                $this->getCredentials($project_id)
            );
        }
        return $this->_authCredentials[$project_id];
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

    /**
     * @throws ApiException
     * @throws ValidationException
     */
    function getAlbums($project_id) {
        // initialize the client
        $photosLibraryClient = $this->getPhotosLibraryClient(
            $this->getAuthCredentials($project_id)
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

    /**
     * @throws ApiException
     * @throws ValidationException
     */
    function initAlbum($project_id, $album_name, $album_id) {
        if (empty($this->_album_id)) {
            if (!empty($album_id)) {
                $this->_album_id = $album_id;
            } else {
                $albums = $this->getAlbums($project_id);
                // create album if it doesn't already exist
                if (isset($albums[$album_name])) {
                    $this->_album_id = $albums[$album_name];
                } else {
                    // create album
                    $this->_album_id = $this->createAlbum($project_id, $album_name);
                }
            }
        }
        return $this->_album_id;
    }

    /**
     * @param $project_id
     * @param $image_data
     * @param $file_name
     * @param $mime_type
     * @return string
     * @throws GuzzleException
     * @throws ValidationException
     */
    function uploadImage($project_id, $image_data, $file_name, $mime_type) {
        // initialize the client
        $photosLibraryClient = $this->getPhotosLibraryClient(
            $this->getAuthCredentials($project_id)
        );

        if (empty($mime_type)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_buffer($finfo, $image_data);
        }

        // Upload the image and get the upload token
        // return the token
        return $photosLibraryClient->upload(
            rawFile: $image_data,
            fileName: $file_name,
            mimeType: $mime_type
        );
    }

    /**
     * @throws ValidationException
     * @throws ApiException
     */
    function addImagesToAlbum($project_id, $upload_tokens, $album_name, $album_id) {
        // initialize the client
        $photosLibraryClient = $this->getPhotosLibraryClient(
            $this->getAuthCredentials($project_id)
        );

        // prep the new media items
        $new_media_items = [];
        foreach ($upload_tokens as $token) {
            $new_media_items[] = PhotosLibraryResourceFactory::newMediaItem($token);
        }

        // initialize the album to ensure it actually exists
        $this->initAlbum($project_id, $album_name, $album_id);

        // push to the album
        $result = $photosLibraryClient->batchCreateMediaItems(
            newMediaItems: $new_media_items,
            optionalArgs: [
                "albumId" => $this->_album_id
            ]
        );
        return $result->getNewMediaItemResults();
    }

    /**
     * @throws ApiException
     * @throws ValidationException
     */
    function createAlbum($project_id, $album_name) {
        // initialize the client
        $photosLibraryClient = $this->getPhotosLibraryClient(
            $this->getAuthCredentials($project_id)
        );
        // Create a new Album object with at title
        $newAlbum = PhotosLibraryResourceFactory::album($album_name);
        // Make the call to the Library API to create the new album
        $createdAlbum = $photosLibraryClient->createAlbum($newAlbum);
        // The creation call returns the ID of the new album
        return $createdAlbum->getId();
    }
}