<?php

use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Handler\AbstractCtrlAwareUploadHandler;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;
use ILIAS\FileUpload\Handler\BasicHandlerResult;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\HandlerResult as HandlerResultInterface;
use ILIAS\Plugin\LongEssayAssessment\ilLongEssayAssessmentUploadTempFile;
use ILIAS\ResourceStorage\Services;


/**
 * Class ResourceUploadHandlerGUI
 *
 * @author            Fabian Wolf <wolf@ilias.de>
 *
 * @ilCtrl_isCalledBy ilLongEssayAssessmentUploadHandlerGUI: ilObjLongEssayAssessmentGUI
 */
class ilLongEssayAssessmentUploadHandlerGUI extends AbstractCtrlAwareUploadHandler
{
	private Services $storage;
	private ilLongEssayAssessmentUploadTempFile $temp_file;

	/**
	 * ilUIDemoFileUploadHandlerGUI constructor.
	 */
	public function __construct(Services $storage, ilLongEssayAssessmentUploadTempFile $temp_file)
	{
		parent::__construct();
		$this->storage = $storage;
		$this->temp_file = $temp_file;

	}

	protected function getInfoResultIfExisting(string $identifier) : ?FileInfoResult
	{
		if($this->temp_file->isTempFile($identifier)){
			$title = $this->temp_file->getTempFilename($identifier);
			$size = $this->temp_file->getTempFileSize($identifier);
			$mime_type = $this->temp_file->getTempMimeType($identifier);
			return new BasicFileInfoResult($this->getFileIdentifierParameterName(), $identifier, $title, $size, $mime_type);
		}else{
			$id = $this->storage->manage()->find($identifier);
			if ($id === null) {
				return new BasicFileInfoResult($this->getFileIdentifierParameterName(), 'unknown', 'unknown', 0, 'unknown');
			}
			$revision = $this->storage->manage()->getCurrentRevision($id);
			$resource = $revision->getInformation();

			return new BasicFileInfoResult(
				$this->getFileIdentifierParameterName(),
				$identifier,
				$revision->getTitle(),
				$resource->getSize(),
				$resource->getMimeType()
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getUploadURL() : string
	{
		return $this->ctrl->getLinkTargetByClass([ilObjPluginDispatchGUI::class, ilObjLongEssayAssessmentGUI::class, self::class], self::CMD_UPLOAD);
		//return str_replace("\\", "\\\\", $this->ctrl->getLinkTargetByClass($this->getCTRLPath(), self::CMD_UPLOAD));
		// Need to double escape backslashes because UI can't handle urls otherwise in json parse in src/UI/templates/js/Input/Field/file.js
	}

	/**
	 * @inheritDoc
	 */
	public function getExistingFileInfoURL() : string
	{
		return $this->ctrl->getLinkTargetByClass([ilObjPluginDispatchGUI::class, ilObjLongEssayAssessmentGUI::class, self::class], self::CMD_INFO);
		//return str_replace("\\", "\\\\", $this->ctrl->getLinkTargetByClass($this->getCTRLPath(), self::CMD_INFO));
		// Need to double escape backslashes because UI can't handle urls otherwise in json parse in src/UI/templates/js/Input/Field/file.js
	}

	/**
	 * @inheritDoc
	 */
	public function getFileRemovalURL() : string
	{
		return $this->ctrl->getLinkTargetByClass([ilObjPluginDispatchGUI::class, ilObjLongEssayAssessmentGUI::class, self::class], self::CMD_REMOVE);
		//return str_replace("\\", "\\\\", $this->ctrl->getLinkTargetByClass($this->getCTRLPath(), self::CMD_REMOVE));
		// Need to double escape backslashes because UI can't handle urls otherwise in json parse in src/UI/templates/js/Input/Field/file.js
	}


	/**
	 * @inheritDoc
	 */
	protected function getUploadResult() : HandlerResultInterface
	{
		$this->upload->process();
		$array = $this->upload->getResults();
		$result = end($array);
		if ($result instanceof UploadResult && $result->isOK()) {
			$identifier = $this->temp_file->storeTempFile($result);
			$status = HandlerResultInterface::STATUS_OK;
			$message = 'Upload ok';
		} else {
			$status = HandlerResultInterface::STATUS_FAILED;
			$identifier = '';
			$message = $result->getStatus()->getMessage();
		}

		return new BasicHandlerResult($this->getFileIdentifierParameterName(), $status, $identifier, $message);
	}

	/**
	 * @inheritDoc
	 */
	protected function getRemoveResult(string $identifier) : HandlerResultInterface
	{
		if($this->temp_file->isTempFile($identifier)){
			$this->temp_file->removeTempFile($identifier);
			return new BasicHandlerResult($this->getFileIdentifierParameterName(),
				HandlerResultInterface::STATUS_OK, $identifier, 'file deleted');
		}else{
			$id = $this->storage->manage()->find($identifier);
			if ($id !== null) {
				return new BasicHandlerResult($this->getFileIdentifierParameterName(),
					HandlerResultInterface::STATUS_OK, $identifier, 'file deleted');
			} else {
				return new BasicHandlerResult($this->getFileIdentifierParameterName(),
					HandlerResultInterface::STATUS_FAILED, $identifier, 'file not found');
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getInfoResult(string $identifier) : FileInfoResult
	{
		return $this->getInfoResultIfExisting($identifier)
			?? new BasicFileInfoResult($this->getFileIdentifierParameterName(), 'unknown', 'unknown', 0, 'unknown');
	}

	/**
	 * @inheritDoc
	 */
	public function getInfoForExistingFiles(array $file_ids) : array
	{
		$infos = [];
		foreach ($file_ids as $file_id) {
			$fir = $this->getInfoResultIfExisting($file_id);
			if($fir !== null)
				$infos[] = $fir;
		}

		return $infos;
	}
}