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
use ILIAS\ResourceStorage\Consumer\Consumers;

/**
 * Adapter of the ILIAS delivery functions for the assessment-service
 * Hides the complexity of the ILIAS IRSS
 * Uses performance advantage of an activated XSenfile
 */
readonly class DeliveryAdapter implements \Edutiek\AssessmentService\System\File\Delivery
{
    public function __construct(
        private StorageAdapter $storage,
        private HttpServices $http,
        private string $temp_dir
    ) {
    }

    public function asciiFilename(string $filename): string
    {
        return Delivery::returnASCIIFileName($filename);
    }

    public function sendFile(string $id, Disposition $disposition, ?FileInfo $info = null): void
    {
        try {
            $info = $info ?? $this->storage->getFileInfo($id);
            $file_path = $this->storage->getReadablePath($id);
            if ($info === null || $file_path === null) {
                throw new Exception('File not found');
            }

            // file can be deleted - make a copy to send and delete
            if ($info->getDisposable()) {
                $temp_file = realpath(tempnam($this->temp_dir, 'xlas'));
                if ($temp_file === false) {
                    throw new Exception("Can't create temporary file to deliver");
                }
                file_put_contents($temp_file, file_get_contents($file_path));
                $this->storage->deleteFile($id);

                $file_path = $temp_file;
            }

            $delivery = new Delivery($file_path, $this->http);
            $delivery->setDownloadFileName($info?->getFileName() ?? 'download');
            $delivery->setMimeType($info?->getMimeType() ?? '');
            $delivery->setDisposition($disposition->value);
            $delivery->deliver();
            $delivery->close();

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
