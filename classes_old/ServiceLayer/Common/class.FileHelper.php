<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common;

use ILIAS\Plugin\LongEssayAssessment\BaseService;
use ILIAS\ResourceStorage\Manager\Manager;
use ILIAS\ResourceStorage\StorageHandler\StorageHandlerFactory;
use ILIAS\FileDelivery\Delivery;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\DI\Exceptions\Exception;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\FileDelivery\FileDeliveryTypes\DeliveryMethod;
use ilUtil;

/**
 * Helper functions for file storage and delivery
 * Implements functions not provided by ilFileDelivery
 * @see ilFileDelivery
 */
class FileHelper extends BaseService
{
    protected Manager $manager;
    protected StorageHandlerFactory $handler_factory;
    protected GlobalHttpState $http;

    /**
     * Constructor
     */
    public function __construct(
        Manager $manager,
        StorageHandlerFactory $handler_factory,
        GlobalHttpState $http
    ) {
        parent::__construct();

        $this->manager = $manager;
        $this->handler_factory = $handler_factory;
        $this->http = $http;
    }

    /**
     * Get the absolute path of the temporary directory of this installation
     */
    public function getAbsoluteTempDir(): string
    {
        return ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
    }


    /**
     * Deliver a file resource given by its unique_id
     * The file is delivered by the FileDelivery service
     * This takes advantage of an activated XSenfile instead of the streaming of the ResourceStorage service
     *
     * @param string $unique_id  the uuid of the resource
     * @param string $disposition 'inline' or 'attachment'
     */
    public function deliverResource(string $unique_id, string $disposition = 'inline')
    {
        try {
            $identification = $this->manager->find($unique_id);
            $resource = $this->manager->getResource($identification);
            $handler = $this->handler_factory->getHandlerForStorageId($resource->getStorageID());

            // relative path to the revision directory from storage filesystem
            // $path = $handler->getRevisionPath($resource->getCurrentRevision());

            $stream = $handler->getStream($resource->getCurrentRevision());
            $abslute_path = $stream->getMetadata('uri');

            $delivery = new Delivery($abslute_path, $this->http);
            $delivery->setDownloadFileName($resource->getCurrentRevision()->getTitle());
            $delivery->setDisposition($disposition);
            $delivery->deliver();
        }
        catch (Exception $e) {
            $response = $this->http->response()->withStatus(500);
            $stream = $response->getBody();
            $stream->write($e->getMessage());

            $this->http->saveResponse($response);
            $this->http->sendResponse();
            $this->http->close();
        }
    }


    /**
     * Deliver data as a file
     * Wrapper for the deprecated ilUtil::deliverData
     * @see ilUtil::deliverData()
     */
    public function deliverData(string $data, string $title, string $mime = "application/octet-stream"): void
    {
        // New Implementation of ilUtil::deliverData in ILIAS 8 does not yet work in ILIAS 7
        ilUtil::deliverData($data, $title, $mime);
    }
}