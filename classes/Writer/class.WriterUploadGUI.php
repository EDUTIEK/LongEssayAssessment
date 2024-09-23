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
    protected ?Writer $writer;
    protected ?Essay $essay;
    protected WriterAdminService $writer_admin_service;
    protected CorrectorAdminService $corrector_admin_service;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);

        $this->task_repo = $this->localDI->getTaskRepo();
        $this->task = $this->task_repo->getTaskSettingsById($this->object->getId());
        $this->writer_admin_service = $this->localDI->getWriterAdminService($this->task->getId());
        $this->corrector_admin_service = $this->localDI->getCorrectorAdminService($this->task->getId());
    }

    /**
     * Execute a command
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $this->tabs->setBackTarget($this->lng->txt("back"),
            $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\writer\writeruploadgui'));

        if (empty($this->writer = $this->data->getOwnWriter())) {
            $this->tpl->setOnScreenMessage("failure", $this->lng->txt('permission denied'));
            return;
        }

        $cmd = $this->ctrl->getCmd();
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

    protected function uploadPdf()
    {
        $essay = $this->writer_admin_service->getOrCreateEssayForWriter($this->writer);

        $form = $this->buildPDFVersionForm($essay);

        if($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if($data = $form->getData()) {
                $file_id = $data["pdf_version"][0] ?? null;

                if($file_id != $essay->getPdfVersion()) {

                    if((int)$data["authorize"] == 1 && $file_id !== null) {
                        $this->writer_admin_service->authorizeWriting($essay, $this->dic->user()->getId());
                        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("pdf_version_upload_successful_auth"), true);
                    } elseif($file_id !== null) {
                        $this->writer_admin_service->removeAuthorizationWriting($essay, $this->dic->user()->getId());
                        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("pdf_version_upload_successful_no_auth"), true);
                    } else {
                        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("pdf_version_upload_successful_removed"), true);
                    }

                    $this->writer_admin_service->handlePDFVersionInput($essay, $file_id);
                    $this->writer_admin_service->createEssayImages($this->object, $essay, $this->writer);
                    $this->writer_admin_service->purgeCorrectorComments($essay);

                    $this->ctrl->redirect($this);
                } else {
                    $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("pdf_version_upload_failure"), true);
                    $this->ctrl->redirect($this, "uploadPDFVersion");
                }
            }
        }

        $name =  $this->common_services->userDataUIHelper()->getUserProfileLink(
            $this->writer->getUserId(),
            $this->ctrl->getLinkTarget($this, "uploadPDFVersion"),
            false,
            null
        ) ?? $this->common_services->userDataHelper()->getPresentation($this->writer->getUserId());

        $user_properties = [
            "" => $name,
            $this->plugin->txt("pseudonym") => $this->writer->getPseudonym()
        ];

        if($essay->getLocation() !== null) {
            $location = $this->task_repo->getLocationById($essay->getLocation());
            if($location !== null) {
                $user_properties[$this->plugin->txt("location")] = (string) $location;
            }
        }
        $user_properties[$this->plugin->txt("writing_status")] = $this->localDI->getDataService($this->task->getId())->formatWritingStatus($essay, false);

        $user_info = $this->uiFactory->card()->standard($this->plugin->txt("participant"))
                                     ->withSections([$this->uiFactory->listing()->descriptive($user_properties)]);

        $subs = [
            $this->uiFactory->panel()->sub($essay->getPdfVersion() !== null
                ? $this->plugin->txt("pdf_version_edit")
                : $this->plugin->txt("pdf_version_upload"), $form)->withFurtherInformation($user_info)
        ];

        if($essay->getEditStarted()) {
            if ($essay->getPdfVersion() !== null) {
                if ($this->writer_admin_service->hasCorrectorComments($essay)) {
                    $this->tpl->setOnScreenMessage("question", $this->plugin->txt("pdf_version_info_already_uploaded"), false);
                }
            } elseif($essay->getWritingAuthorized() !== null && $essay->getWritingAuthorizedBy() === $this->writer->getUserId()) {
                $this->tpl->setOnScreenMessage("question", $this->plugin->txt("pdf_version_warning_authorized_essay"), false);
            } else {
                $this->tpl->setOnScreenMessage("question", $this->plugin->txt("pdf_version_info_started_essay"), false);
            }

            $this->addContentCss();
            $subs[] = $this->uiFactory->panel()->sub(
                $this->plugin->txt("pdf_version_header_writing"),
                $this->uiFactory->legacy($this->displayContent($this->localDI->getDataService($this->task->getId())->cleanupRichText($essay->getWrittenText())))
            );
        }

        $panel = $this->uiFactory->panel()->standard("", $subs);

        $this->tpl->setContent($this->renderer->render([$panel]));
    }

    public function buildPDFVersionForm(Essay $essay): Standard
    {
        $this->ctrl->saveParameter($this, "writer_id");
        $link = $this->ctrl->getFormAction($this, "uploadPDFVersion", "", true);
        $download = $essay->getPdfVersion() !== null ?
            "</br>" . $this->renderer->render(
                $this->uiFactory->link()->standard(
                    $this->plugin->txt("download"),
                    $this->ctrl->getFormAction($this, "downloadPDFVersion", "", true)
                )
            ) : "";


        $fields = [];
        $fields["pdf_version"] = $this->uiFactory->input()->field()->file(
            new \ilLongEssayAssessmentUploadHandlerGUI($this->storage, $this->localDI->getUploadTempFile()),
            $this->lng->txt("file"),
            $this->localDI->getUIService()->getMaxFileSizeString() . $download
        )->withAcceptedMimeTypes(['application/pdf'])
                                                 ->withValue($essay->getPdfVersion() !== null ? [$essay->getPdfVersion()]: []);

        //		$fields["edit_time"] = $this->uiFactory->input()->field()->optionalGroup([
        //			"edit_start" => $this->uiFactory->input()->field()->dateTime($this->plugin->txt("edit_start"))->withValue($essay->getEditStarted() ?? ""),
        //			"edit_end" => $this->uiFactory->input()->field()->dateTime($this->plugin->txt("edit_end"))->withValue($essay->getEditEnded() ?? "")
        //		], "Schreibzeitraum")
        //			->withByline($this->plugin->txt("edit_time_info")/*"Optional: Schreibzeitrum mit Protokollieren"*/);

        $fields["authorize"] = $this->uiFactory->input()->field()->checkbox($this->plugin->txt("authorize_writing"))
                                               ->withByline($this->plugin->txt("authorize_pdf_version_info"))
                                               ->withValue($essay->getWritingAuthorized() !== null && $essay->getWritingAuthorizedBy() === $this->dic->user()->getId());

        return $this->uiFactory->input()->container()->form()->standard($link, $fields, "");
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
