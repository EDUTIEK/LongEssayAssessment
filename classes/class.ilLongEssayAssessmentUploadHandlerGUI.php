<?php

use Edutiek\AssessmentService\System\File\Storage as FileStorage;
use Edutiek\AssessmentService\System\Data\FileInfo;
use ILIAS\Plugin\LongEssayAssessment\Common\Upload\UploadTempFile;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo as FileInfoModel;
use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Handler\AbstractCtrlAwareUploadHandler;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;
use ILIAS\FileUpload\Handler\BasicHandlerResult;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\HandlerResult as HandlerResultInterface;

/**
 *  Handler for file uploads
 *  This handler is used for the Input\File component of the UI framework
 *  It gets the info about an existing file asynchronously
 *  It stores the asynchronous upload in a tempory file
 *  It deletes an uploaded temporary file asynchronously
 *
 *  If no temporary file is uploaded yet, then the existing file info is shown
 *  When a temorary file is uploaded asynchronoursly then its file info is shown
 *
 *  A stored file has to be created, replaced or deleted in the calling GUI when the UI form is saved
 *  The value of the Input\File component may be the identifier of a stored or a temporary file
 *  The parent GUI has to compare this value with the stored value and decide on the action
 *
 * @ilCtrl_isCalledBy ilLongEssayAssessmentUploadHandlerGUI: ilObjLongEssayAssessmentGUI
 */
class ilLongEssayAssessmentUploadHandlerGUI extends AbstractCtrlAwareUploadHandler
{
    public function __construct(
        private readonly FileStorage $file_storage,
        private readonly UploadTempFile $temp_file
    ) {
        parent::__construct();
    }

    /**
     * Get the info about the file for the assesment services system api
     */
    public function getApiInfo($identifier): ?FileInfo
    {
        if ($this->temp_file->has($identifier)) {
            return (new FileInfoModel())
                ->setId(null)
                ->setFileName($this->temp_file->getName($identifier))
                ->setMimeType($this->temp_file->getMimeType($identifier))
                ->setSize(
                    $this->temp_file->getSize($identifier)
                );
        }
        return $this->file_storage->getFileInfo($identifier);
    }

    /**
     * Get the stream of an uploaded file for the assesment services system api
     */
    public function getApiStream(string $identifier): mixed
    {
        return $this->temp_file->stream($identifier)->detach();
    }

    public function getUploadURL(): string
    {
        return $this->ctrl->getLinkTargetByClass([ilLongEssayAssessmentDispatchGUI::class, ilObjLongEssayAssessmentGUI::class, self::class], self::CMD_UPLOAD);
    }

    public function getExistingFileInfoURL(): string
    {
        return $this->ctrl->getLinkTargetByClass([ilLongEssayAssessmentDispatchGUI::class, ilObjLongEssayAssessmentGUI::class, self::class], self::CMD_INFO);
    }

    public function getFileRemovalURL(): string
    {
        return $this->ctrl->getLinkTargetByClass([ilLongEssayAssessmentDispatchGUI::class, ilObjLongEssayAssessmentGUI::class, self::class], self::CMD_REMOVE);
    }

    protected function getUploadResult(): HandlerResultInterface
    {
        $this->upload->process();
        $array = $this->upload->getResults();
        $result = end($array);
        if ($result instanceof UploadResult && $result->isOK()) {
            $identifier = $this->temp_file->store($result);
            $status = HandlerResultInterface::STATUS_OK;
            $message = 'Upload ok';
        } else {
            $status = HandlerResultInterface::STATUS_FAILED;
            $identifier = '';
            $message = $result->getStatus()->getMessage();
        }

        return new BasicHandlerResult($this->getFileIdentifierParameterName(), $status, $identifier, $message);
    }

    protected function getRemoveResult(string $identifier): HandlerResultInterface
    {
        if ($this->temp_file->has($identifier)) {
            $this->temp_file->delete($identifier);
            return new BasicHandlerResult(
                $this->getFileIdentifierParameterName(),
                HandlerResultInterface::STATUS_OK,
                $identifier,
                'Temporary file deleted'
            );
        }
        return new BasicHandlerResult(
            $this->getFileIdentifierParameterName(),
            HandlerResultInterface::STATUS_FAILED,
            $identifier,
            'Temporary file not found'
        );
    }

    public function getInfoResult(string $identifier): FileInfoResult
    {
        if ($this->temp_file->has($identifier)) {
            return new BasicFileInfoResult(
                $this->getFileIdentifierParameterName(),
                $identifier,
                $this->temp_file->getName($identifier),
                $this->temp_file->getSize($identifier),
                $this->temp_file->getMimeType($identifier)
            );
        }
        $info = $this->file_storage->getFileInfo($identifier);

        if ($info !== null) {
            return new BasicFileInfoResult(
                $this->getFileIdentifierParameterName(),
                $identifier,
                $info->getFileName(),
                $info->getSize(),
                $info->getMimeType()
            );
        }

        return new BasicFileInfoResult(
            $this->getFileIdentifierParameterName(),
            'unknown',
            'unknown',
            0,
            'unknown'
        );
    }

    public function getInfoForExistingFiles(array $file_ids): array
    {
        return array_map(function ($file_id): FileInfoResult {
            return $this->getInfoResult($file_id);
        }, $file_ids);
    }
}
