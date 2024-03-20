<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common;

use ILIAS\Plugin\LongEssayAssessment\BaseService;
use ILIAS\ResourceStorage\Manager\Manager;
use ILIAS\ResourceStorage\StorageHandler\StorageHandlerFactory;
use ILIAS\FileDelivery\Delivery;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\DI\Exceptions\Exception;

/**
 * Helper functions for file storage and delivery
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
     * Deliver a file resource given by its unique_id
     * The file is delivered by the FileDelivery dervice
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
}