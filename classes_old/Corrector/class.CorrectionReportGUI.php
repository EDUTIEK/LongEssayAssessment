<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\UI\UIService;

/**
 * Report given by corrector
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectionReportGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectionReportGUI extends BaseGUI
{
    private UIService $ui_service;
    private CorrectorRepository $corrector_repo;
    private Corrector $corrector;
    private CorrectionSettings $settings;

    private bool $can_correct;


    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);

        $this->ui_service = $this->localDI->getUIService();
        $this->corrector_repo = $this->localDI->getCorrectorRepo();
        $this->corrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->object->getId());

        $this->can_correct = $this->object->canCorrect();
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('edit');
        switch ($cmd) {
            case "edit":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * Edit and save the report
     * Use classic Property form for richtext editing
     */
    protected function edit()
    {
        $components = [];

        if (!$this->can_correct) {
            $components[] = $this->uiFactory->panel()->standard(
                    $this->plugin->txt("correction_report"),
                    $this->uiFactory->legacy($this->displayContent($this->corrector->getCorrectionReport() ?? ""))
            );
        }
        else {
            $this->ui_service->addTinyMCEToTextareas(); // Has to be called last for the noRTEditor Tags to be effective
            $fields = [];
            $fields['correction_report'] = $this->localDI->getUIFactory()->field()
                 ->textareaModified($this->plugin->txt("correction_report"), $this->plugin->txt("correction_report_info"))
                 ->withDisabled(!$this->can_correct)
                 ->withValue($this->corrector->getCorrectionReport() ?? "")
                 ->withAdditionalTransformation($this->ui_service->stringTransformationByRTETagSet());
            $form = $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $fields);
            $components[] = $form;

            // apply inputs
            if ($this->request->getMethod() == "POST") {
                $form = $form->withRequest($this->request);
                $data = $form->getData();
                $result = $form->getInputGroup()->getContent();

                if ($result->isOK()) {
                    $this->corrector->setCorrectionReport((string) $this->data->trimRichText($data['correction_report'] ?? null));
                    $this->corrector_repo->save($this->corrector);

                    $this->tpl->setOnScreenMessage("success", $this->plugin->txt("correction_report_saved"), true);
                    $this->ctrl->redirect($this, "edit");
                } else {
                    $this->tpl->setOnScreenMessage("failure", $this->lng->txt("validation_error"), true);
                }
            }
        }

        $this->tpl->setContent($this->renderer->render($components));
    }
}
