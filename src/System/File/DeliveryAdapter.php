<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\File;

use Edutiek\AssessmentService\System\Data\FileInfo;
use Edutiek\AssessmentService\System\File\Disposition;
use ILIAS\DI\Exceptions\Exception;
use ILIAS\FileDelivery\Delivery;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\HTTP\Services as HttpServices;
use ILIAS\ResourceStorage\Manager\Manager;
use ILIAS\ResourceStorage\StorageHandler\StorageHandlerFactory;

/**
 * Adapter of the ILIAS delivery functions for the assessment-service
 * Hides the complexity of the ILIAS IRSS
 * Uses performance advantage of an activated XSenfile
 */
readonly class DeliveryAdapter implements \Edutiek\AssessmentService\System\File\Delivery
{
    /**
     * todo: try consumer instead of StorageHandlerFactory
     * @see \ILIAS\Plugin\LongEssayAssessment\System\File\StorageAdapter::getReadablePath
     */
    public function __construct(
        private Manager $manager,
        private StorageHandlerFactory $handler_factory,
        private HttpServices $http
    ) {
    }

    public function asciiFilename(string $filename): string
    {
        return Delivery::returnASCIIFileName($filename);
    }

    public function sendFile(string $id, Disposition $disposition, ?FileInfo $info = null): never
    {
        try {
            $identification = $this->manager->find($id);
            $resource = $this->manager->getResource($identification);
            $handler = $this->handler_factory->getHandlerForStorageId($resource->getStorageID());

            $stream = $handler->getStream($resource->getCurrentRevision());
            $absolute_path = $stream->getMetadata('uri');

            $delivery = new Delivery($absolute_path, $this->http);
            $delivery->setDownloadFileName($info?->getFileName() ?? $resource->getCurrentRevision()->getTitle());
            $delivery->setMimeType($info?->getMimeType() ?? $resource->getCurrentRevision()->getInformation()->getMimeType());
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

    public function sendData(string $data, Disposition $disposition, ?FileInfo $info): never
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
