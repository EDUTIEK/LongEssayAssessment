<?php

use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Handler\AbstractCtrlAwareUploadHandler;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\HandlerResult as HandlerResultInterface;
use ILIAS\FileUpload\Handler\BasicHandlerResult;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;
use ILIAS\Plugin\LongEssayAssessment\Task\ResourceResourceStakeholder;
use ILIAS\ResourceStorage\Services;

/**
 * Class ResourceUploadHandlerGUI
 *
 * @author            Fabian Wolf <wolf@ilias.de>
 *
 * @ilCtrl_isCalledBy ResourceUploadHandlerGUI: ilUIPluginRouterGUI
 */
class ResourceUploadHandlerGUI extends AbstractCtrlAwareUploadHandler
{
    /**
     * @var Services
     */
    private $storage;
    /**
     * @var ResourceResourceStakeholder
     */
    private $stakeholder;


    /**
     * ilUIDemoFileUploadHandlerGUI constructor.
     */
    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->storage = $DIC['resource_storage'];
        $this->stakeholder = new ResourceResourceStakeholder();
    }

    public function getUploadURL() : string
    {
        return $this->ctrl->getLinkTargetByClass(
            [ilUIPluginRouterGUI::class, ResourceUploadHandlerGUI::class],
            self::CMD_UPLOAD
        );
    }


    public function getExistingFileInfoURL() : string
    {
        return $this->ctrl->getLinkTargetByClass(
            [ilUIPluginRouterGUI::class, ResourceUploadHandlerGUI::class],
            self::CMD_INFO
        );
    }

    public function getFileRemovalURL() : string
    {
        return $this->ctrl->getLinkTargetByClass(
            [ilUIPluginRouterGUI::class, ResourceUploadHandlerGUI::class],
            self::CMD_REMOVE
        );
    }


    /**
     * @inheritDoc
     */
    protected function getUploadResult() : HandlerResultInterface
    {
        $this->upload->process();
        /**
         * @var $result UploadResult
         */
        $array = $this->upload->getResults();
        $result = end($array);
        if ($result instanceof UploadResult && $result->isOK()) {
            $i = $this->storage->manage()->upload($result, $this->stakeholder);
            $status = HandlerResultInterface::STATUS_OK;
            $identifier = $i->serialize();
            $message = 'Upload ok';
        } else {
            $status = HandlerResultInterface::STATUS_FAILED;
            $identifier = '';
            $message = $result->getStatus()->getMessage();
        }

        return new BasicHandlerResult($this->getFileIdentifierParameterName(), $status, $identifier, $message);
    }


    protected function getRemoveResult(string $identifier) : HandlerResultInterface
    {
        $id = $this->storage->manage()->find($identifier);
        if ($id !== null) {
            $this->storage->manage()->remove($id, $this->stakeholder);

            return new BasicHandlerResult($this->getFileIdentifierParameterName(), HandlerResultInterface::STATUS_OK, $identifier, 'file deleted');
        } else {
            return new BasicHandlerResult($this->getFileIdentifierParameterName(), HandlerResultInterface::STATUS_FAILED, $identifier, 'file not found');
        }
    }


    protected function getInfoResult(string $identifier) : FileInfoResult
    {
        $id = $this->storage->manage()->find($identifier);
        if ($id === null) {
            return new BasicFileInfoResult($this->getFileIdentifierParameterName(), 'unknown', 'unknown', 0, 'unknown');
        }
        $r = $this->storage->manage()->getCurrentRevision($id)->getInformation();

        return new BasicFileInfoResult($this->getFileIdentifierParameterName(), $identifier, $r->getTitle(), $r->getSize(), $r->getMimeType());
    }


    public function getInfoForExistingFiles(array $file_ids) : array
    {
        $infos = [];
        foreach ($file_ids as $file_id) {
            $id = $this->storage->manage()->find($file_id);
            if ($id === null) {
                continue;
            }
            $r = $this->storage->manage()->getCurrentRevision($id)->getInformation();

            $infos[] = new BasicFileInfoResult($this->getFileIdentifierParameterName(), $file_id, $r->getTitle(), $r->getSize(), $r->getMimeType());
        }

        return $infos;
    }
}