<?php

use Edutiek\AssessmentService\System\Data\FileInfo;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Handler\AbstractCtrlAwareUploadHandler;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;
use ILIAS\FileUpload\Handler\BasicHandlerResult;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\HandlerResult as HandlerResultInterface;
use ILIAS\Plugin\LongEssayAssessment\UploadTempFile;

/**
 *  Handler for file uploads
 *  This handler is used for the Input\File component of the UI framework
 *  It gets the info about an existing file asynchronously
 *  It stores the asynchronous upload in a tempory file
 *  It deletes an uploaded temporary file asynchronously
 *
 *  The info about a stored file has to be injected by the calling class with setFileInfo
 *  If no temporary file is uploaded yet, then this info is shown
 *  When a temorary file is uploaded asynchronoursly then its file info is shown
 *
 *  A stored file has to be created, replaced or deleted in the calling GUI when the UI form is saved
 *  The value of the Input\File component may be the identifier of a stored or temporary file
 *  The parent GUI has to compare this value with the stored value and decide on the action
 *  The
 *
 * @ilCtrl_isCalledBy ilLongEssayAssessmentUploadHandlerGUI: ilObjLongEssayAssessmentGUI
 */
class ilLongEssayAssessmentUploadHandlerGUI extends AbstractCtrlAwareUploadHandler
{
    private ?FileInfo $file_info = null;

    public function __construct(
        private readonly UploadTempFile $temp_file
    ) {
        parent::__construct();
    }

    /**
     * Set the info about an already stored file, not the temp file
     */
    public function setFileInfo(?FileInfo $file_info): self
    {
        $this->file_info = $file_info;
        return $this;
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
        if ($this->file_info !== null && $this->file_info->getId() === $identifier) {
            return new BasicFileInfoResult(
                $this->getFileIdentifierParameterName(),
                $identifier,
                $this->file_info->getFileName(),
                $this->file_info->getSize(),
                $this->file_info->getMimeType()
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
