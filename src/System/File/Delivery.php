<?php

namespace ILIAS\Plugin\LongEssayAssessment\Common;

use ILIAS\Plugin\LongEssayAssessment\BaseService;
use ILIAS\ResourceStorage\Manager\Manager;
use ILIAS\ResourceStorage\StorageHandler\StorageHandlerFactory;
use ILIAS\FileDelivery\Delivery;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\DI\Exceptions\Exception;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\FileDelivery\FileDeliveryTypes\DeliveryMethod;
use ilUtil;
use Edutiek\AssessmentService\System\File\Disposition;
use Edutiek\AssessmentService\System\Data\FileInfo;

/**
 * Wrapper of the ILIAS delivery functions for the assessment-service
 */
class DeliveryWrapper implements \Edutiek\AssessmentService\System\File\Delivery
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
        $this->manager = $manager;
        $this->handler_factory = $handler_factory;
        $this->http = $http;
    }

    /**
     * Deliver a file resource given by its id
     * The file is delivered by the FileDelivery service
     * This implementation takes advantage of an activated XSenfile instead of the streaming of the ResourceStorage service
     *
     * @param string $unique_id  the uuid of the resource
     * @param string $disposition 'inline' or 'attachment'
     */
    public function sendFile(string $id, Disposition $disposition): void
    {
        try {
            $identification = $this->manager->find($id);
            $resource = $this->manager->getResource($identification);
            $handler = $this->handler_factory->getHandlerForStorageId($resource->getStorageID());

            $stream = $handler->getStream($resource->getCurrentRevision());
            $absolute_path = $stream->getMetadata('uri');

            $delivery = new Delivery($absolute_path, $this->http);
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
    public function sendData(string $data, Disposition $disposition, ?FileInfo $info): void
    {
        try {
            $delivery = new Delivery(Delivery::DIRECT_PHP_OUTPUT, $this->http);
            $delivery->setMimeType($info?->getMime() ?? 'application/octet-stream');
            $delivery->setSendMimeType(true);
            $delivery->setDisposition(Delivery::DISP_ATTACHMENT);
            $delivery->setDownloadFileName($info?->getName() ?? '');
            $delivery->setConvertFileNameToAsci(true);
            $reponse = $this->http->response()->withBody(Streams::ofString($data));
            $this->http->saveResponse($reponse);
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