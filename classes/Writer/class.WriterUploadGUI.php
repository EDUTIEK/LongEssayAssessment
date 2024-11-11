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
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminService;
use ilLongEssayAssessmentUploadHandlerGUI;
use ilFileDelivery;

/**
 * Upload page for writers
 *
 * @package           ILIAS\Plugin\LongEssayAssessment\Writer
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI: ilObjLongEssayAssessmentGUI
 */
class WriterUploadGUI extends BaseGUI
{

    protected TaskRepository $task_repo;
    protected TaskSettings $task;
    protected ?Writer $writer;
    protected ?Essay $essay;
    protected WriterAdminService $writer_admin_service;
    protected CorrectorAdminService $corrector_admin_service;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);

        $this->task_repo = $this->localDI->getTaskRepo();
        $this->task = $this->task_repo->getTaskSettingsById($this->object->getId());
        $this->writer_admin_service = $this->localDI->getWriterAdminService($this->task->getTaskId());
        $this->corrector_admin_service = $this->localDI->getCorrectorAdminService($this->task->getTaskId());
    }

    /**
     * Execute a command
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $this->tabs->setBackTarget(
            $this->lng->txt("back"),
            $this->getBackLink()
        );

        if (empty($this->writer = $this->data->getOwnWriter())) {
            $this->tpl->setOnScreenMessage("failure", $this->lng->txt('permission denied'));
            return;
        }

        $cmd = $this->ctrl->getCmd();
        switch ($cmd) {
            case 'uploadPdf':
            case 'reviewPdf':
            case 'authorizePdf':
            case 'downloadPdf':
            case 'deliverPdf':
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    protected function getBackLink()
    {
        return $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\writer\writerstartgui');
    }

    protected function uploadPdf()
    {
        $essay = $this->writer_admin_service->getOrCreateEssayForWriter($this->writer);

        $download = $essay->getPdfVersion() !== null ?
            "</br>" . $this->renderer->render(
                $this->uiFactory->link()->standard(
                    $this->plugin->txt("download"),
                    $this->ctrl->getFormAction($this, "downloadPdf", "", true)
                )
            ) : "";

        $fields = [];
        $fields["pdf_version"] = $this->uiFactory->input()->field()->file(
            new ilLongEssayAssessmentUploadHandlerGUI($this->storage, $this->localDI->getUploadTempFile()),
            $this->lng->txt("file"),
            $this->localDI->getUIService()->getMaxFileSizeString() . $download
        )->withAcceptedMimeTypes(['application/pdf'])
         ->withValue($essay->getPdfVersion() !== null ? [$essay->getPdfVersion()] : []);

        $form = $this->uiFactory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'uploadPdf', '', true), $fields)
        ->withSubmitLabel($this->plugin->txt($essay->getPdfVersion() === null ? 'writer_upload_pdf' : 'writer_replace_pdf'));

        if ($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if ($data = $form->getData()) {
                $file_id = $data["pdf_version"][0] ?? null;

                if ($file_id != $essay->getPdfVersion()) {
                    $this->writer_admin_service->handlePDFVersionInput($essay, $file_id);
                    $this->writer_admin_service->purgeCorrectorComments($essay);

                    if ($file_id !== null) {
                        $this->ctrl->redirect($this, 'reviewPdf');
                    } else {
                        $this->tpl->setOnScreenMessage(
                            "success", $this->plugin->txt("pdf_version_upload_successful_removed"), true
                        );
                        $this->ctrl->redirectToURL($this->getBackLink());
                    }
                } else {
                    $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("pdf_version_upload_failure"), true);
                    $this->ctrl->redirect($this, "uploadPdf");
                }
            }
        }

        $this->tpl->setContent($this->renderer->render([$form]));
    }

    protected function reviewPdf()
    {
        $essay = $this->writer_admin_service->getOrCreateEssayForWriter($this->writer);
        $resource = null;
        if ($essay->getPdfVersion() !== null) {
            $identifier = $this->storage->manage()->find($essay->getPdfVersion());
            $resource = $this->storage->manage()->getResource($identifier);
        }
        if (!isset($resource)) {
            $this->tpl->setOnScreenMessage(\ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $this->plugin->txt('pdf_version_not_found'));
            $this->ctrl->redirectToURL($this->getBackLink());
        }

        $components = [];
        $components[] = $this->localDI->getUIFactory()->viewer()->pdf(
            $this->ctrl->getLinkTarget($this, 'deliverPdf'),
            $resource->getCurrentRevision()->getTitle());

        $components[] = $this->uiFactory->button()->primary($this->plugin->txt('writer_authorize_pdf'),
            $this->ctrl->getLinkTarget($this, 'authorizePdf'));

        $components[] = $this->uiFactory->button()->standard($this->lng->txt('cancel'),
           $this->getBackLink());

        $panel = $this->uiFactory->panel()->standard(
            $this->plugin->txt('writer_review_pdf'),
            $components
        );

        $this->tpl->setContent($this->renderer->render($panel));
    }

    protected function authorizePdf()
    {
        $this->tpl->setContent('Authorize PDF');
    }

    protected function deliverPdf() {
        $essay = $this->writer_admin_service->getOrCreateEssayForWriter($this->writer);
        if ($essay->getPdfVersion() !== null) {
            $this->localDI->services()->common()->fileHelper()->deliverResource($essay->getPdfVersion());
        }
    }

    protected function downloadPdf()
    {
        $essay = $this->writer_admin_service->getOrCreateEssayForWriter($this->writer);
        if ($essay->getPdfVersion() !== null
            && ($identifier = $this->storage->manage()->find($essay->getPdfVersion()))) {
            $this->storage->consume()->download($identifier)->run();
        }
        else {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("pdf_version_not_found"), true);
            $this->ctrl->redirectToURL($this->getBackLink());
        }
    }
}
