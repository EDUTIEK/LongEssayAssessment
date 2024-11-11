<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Writer;

use Edutiek\LongEssayAssessmentService\Writer\Service;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\Task\ResourceAdmin;
use \ilUtil;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminService;

/**
 * Upload page for writers
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Writer
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI: ilObjLongEssayAssessmentGUI
 */
class WriterUploadGUI extends BaseGUI
{

    protected TaskRepository $task_repo;
    protected TaskSettings $task;
    protected CorrectorAdminService $corrector_admin_service;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);

        $this->task_repo = $this->localDI->getTaskRepo();
        $this->task = $this->task_repo->getTaskSettingsById($this->object->getId());
        $this->corrector_admin_service = $this->localDI->getCorrectorAdminService($this->object->getId());
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        switch ($this->ctrl->getNextClass()) {

            default:
                $cmd = $this->ctrl->getCmd('uploadPdf');
                switch ($cmd) {
                    case 'uploadPdf':
                    case 'reviewPdf':
                    case 'authorizePdf':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }
    }

    protected function uploadPdf()
    {
        $this->tpl->setContent('Upload PDF');
    }

    protected function reviewPdf()
    {
        $this->tpl->setContent('Review PDF');
    }

    protected function authorizePdf()
    {
        $this->tpl->setContent('Authorize PDF');
    }

}
