<?php

namespace ILIAS\Plugin\LongEssayAssessment\System\File;

use ILIAS\ResourceStorage\Manager\Manager;
use ILIAS\ResourceStorage\StorageHandler\StorageHandlerFactory;
use ILIAS\FileDelivery\Delivery;
use ILIAS\HTTP\Services as HttpServices;
use ILIAS\DI\Exceptions\Exception;
use ILIAS\Filesystem\Stream\Streams;
use Edutiek\AssessmentService\System\File\Disposition;
use Edutiek\AssessmentService\System\Data\FileInfo;

/**
 * Adapter of the ILIAS delivery functions for the assessment-service
 * Hides the complexity of the ILIAS IRSS
 * Uses performance advantage of an activated XSenfile
 */
readonly class DeliveryAdapter implements \Edutiek\AssessmentService\System\File\Delivery
{
    public function __construct(
        private Manager $manager,
        private StorageHandlerFactory $handler_factory,
        private HttpServices $http
    ) {
    }

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
            $delivery->setMimeType($resource->getCurrentRevision()->getInformation()->getMimeType());
            $delivery->setDisposition($disposition->value);
            $delivery->deliver();
        } catch (Exception $e) {
            $response = $this->http->response()->withStatus(500);
            $stream = $response->getBody();
            $stream->write($e->getMessage());

            $this->http->saveResponse($response);
            $this->http->sendResponse();
            $this->http->close();
        }
    }

    public function sendData(string $data, Disposition $disposition, ?FileInfo $info): void
    {
        try {
            $delivery = new Delivery(Delivery::DIRECT_PHP_OUTPUT, $this->http);
            $delivery->setMimeType($info?->getMimeType() ?? 'application/octet-stream');
            $delivery->setSendMimeType(true);
            $delivery->setDisposition($disposition->value);
            $delivery->setDownloadFileName($info?->getFileName() ?? '');
            $delivery->setConvertFileNameToAsci(true);
            $response = $this->http->response()->withBody(Streams::ofString($data));
            $this->http->saveResponse($response);
            $delivery->deliver();
        } catch (Exception $e) {
            $response = $this->http->response()->withStatus(500);
            $stream = $response->getBody();
            $stream->write($e->getMessage());

            $this->http->saveResponse($response);
            $this->http->sendResponse();
            $this->http->close();
        }
    }
}
