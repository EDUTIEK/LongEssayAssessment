<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Writer;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminService;
use ilLongEssayAssessmentUploadHandlerGUI;
use ilGlobalTemplateInterface as Tpl;
use ILIAS\HTTP\StatusCode;

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
        $this->tabs->clearTargets();

        $this->tabs->setBackTarget(
            $this->lng->txt("back"),
            $this->getBackLink()
        );

        $this->writer = $this->writer_admin_service->getOrCreateWriterFromUserId($this->user->getId());

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
        return $this->ctrl->getLinkTargetByClass(WriterStartGUI::class);
    }

    protected function uploadPdf()
    {
        if (!$this->object->canWrite()) {
            $this->tpl->setOnScreenMessage(Tpl::MESSAGE_TYPE_FAILURE, $this->lng->txt('permission_denied'), true);
            $this->ctrl->redirectToURL($this->getBackLink());
        }

        $essay = $this->writer_admin_service->getEssayForWriter($this->writer); // may not yet be saved

        $download = $essay->getPdfVersion() !== null ?
            "</br>" . $this->renderer->render(
                $this->uiFactory->link()->standard(
                    $this->plugin->txt("download"),
                    $this->ctrl->getFormAction($this, "downloadPdf", "", true)
                )
            ) : "";

        $fields = ["pdf_version" => $this->uiFactory->input()->field()->file(
            new ilLongEssayAssessmentUploadHandlerGUI($this->storage, $this->localDI->getUploadTempFile()),
            $this->lng->txt("file"),
            $this->localDI->getUIService()->getMaxFileSizeString() . $download
        )->withAcceptedMimeTypes(['application/pdf'])
         ->withValue($essay->getPdfVersion() !== null ? [$essay->getPdfVersion()] : [])
        ];

        $form = $this->uiFactory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'uploadPdf', '', true), $fields)
        ->withSubmitLabel($this->plugin->txt($essay->getPdfVersion() === null ? 'writer_upload_pdf' : 'writer_replace_pdf'));

        if ($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if ($data = $form->getData()) {
                $file_id = $data["pdf_version"][0] ?? null;

                if ($file_id !== $essay->getPdfVersion()) {
                    $this->writer_admin_service->handlePDFVersionInput($essay, $file_id);
                    $this->writer_admin_service->removeEssayImages($essay->getId());
                    $this->writer_admin_service->purgeCorrectorComments($essay);

                    if ($file_id !== null) {
                        $this->ctrl->redirect($this, 'reviewPdf');
                    } else {
                        $this->tpl->setOnScreenMessage(
                            "success", $this->plugin->txt("pdf_version_upload_successful_removed"), true
                        );
                        $this->ctrl->redirectToURL($this->getBackLink());
                    }
                } elseif ($essay->getPdfVersion() !== null) {
                    $this->ctrl->redirect($this, 'reviewPdf');
                } else {
                    $this->tpl->setOnScreenMessage(Tpl::MESSAGE_TYPE_FAILURE, $this->plugin->txt("pdf_version_upload_failure"), true);
                    $this->ctrl->redirect($this, "uploadPdf");
                }
            }
        }

        $this->tpl->setContent($this->renderer->render([$form]));
    }

    protected function reviewPdf()
    {
        if (!$this->object->canWrite() && !$this->object->canReviewWrittenEssay()) {
            $this->tpl->setOnScreenMessage(Tpl::MESSAGE_TYPE_FAILURE, $this->lng->txt('permission_denied'), true);
            $this->ctrl->redirectToURL($this->getBackLink());
        }

        $resource = null;
        $essay = $this->writer_admin_service->getEssayForWriter($this->writer);

        if ($essay->getPdfVersion() !== null) {
            $identifier = $this->storage->manage()->find($essay->getPdfVersion());
            $resource = $this->storage->manage()->getResource($identifier);
        }
        if (!isset($resource)) {
            $this->tpl->setOnScreenMessage(Tpl::MESSAGE_TYPE_FAILURE, $this->plugin->txt('pdf_version_not_found'), true);
            $this->ctrl->redirectToURL($this->getBackLink());
        }

        $this->tpl->setOnScreenMessage(Tpl::MESSAGE_TYPE_INFO, $this->plugin->txt('writer_authorize_pdf_info'));
        $components = [];
        $components[]  = $this->uiFactory->panel()->standard($this->plugin->txt('writer_review_pdf'),
            [   $this->localDI->getUIFactory()->viewer()->pdf(
                $this->ctrl->getLinkTarget($this, 'deliverPdf'),
                $resource->getCurrentRevision()->getTitle())
            ]
        );
        $components[] = $this->uiFactory->button()->primary($this->plugin->txt('writer_authorize_pdf'),
            $this->ctrl->getLinkTarget($this, 'authorizePdf'));
        $components[] = $this->uiFactory->button()->standard($this->lng->txt('cancel'),
           $this->getBackLink());


        $this->tpl->setContent($this->renderer->render($components));
    }

    protected function authorizePdf()
    {
        if (!$this->object->canWrite() && !$this->object->canReviewWrittenEssay()) {
            $this->tpl->setOnScreenMessage(Tpl::MESSAGE_TYPE_FAILURE, $this->lng->txt('permission_denied'), true);
            $this->ctrl->redirectToURL($this->getBackLink());
        }

        $essay = $this->writer_admin_service->getEssayForWriter($this->writer);
        if ($essay->getPdfVersion() === null) {
            $this->tpl->setOnScreenMessage(Tpl::MESSAGE_TYPE_FAILURE, $this->plugin->txt('pdf_version_not_found'), true);
            $this->ctrl->redirectToURL($this->getBackLink());
        }

        $this->writer_admin_service->authorizeWriting($essay, $this->writer->getUserId());
        $this->writer_admin_service->createEssayImagesInBackground(
            $this->object->getRefId(), $essay->getTaskId(), $this->writer->getId(), $essay->getId(), false
        );

        $this->ctrl->setParameterByClass(WriterStartGUI::class, 'returned', '1');
        $this->ctrl->redirectToURL($this->getBackLink());
    }

    protected function deliverPdf() {
         $essay = $this->writer_admin_service->getEssayForWriter($this->writer);
        if ($essay->getPdfVersion() !== null) {
            $this->localDI->services()->common()->fileHelper()->deliverResource($essay->getPdfVersion());
        }
        else {
            $response = $this->http->response()->withStatus(StatusCode::HTTP_NOT_FOUND);
            $this->http->saveResponse($response);
            $this->http->sendResponse();
            $this->http->close();
        }
    }

    protected function downloadPdf()
    {
        $essay = $this->writer_admin_service->getEssayForWriter($this->writer);
        if ($essay->getPdfVersion() !== null
            && ($identifier = $this->storage->manage()->find($essay->getPdfVersion()))) {
            $this->storage->consume()->download($identifier)->run();
        }
        else {
            $this->tpl->setOnScreenMessage(Tpl::MESSAGE_TYPE_FAILURE, $this->plugin->txt("pdf_version_not_found"), true);
            $this->ctrl->redirectToURL($this->getBackLink());
        }
    }
}
