<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use Edutiek\AssessmentService\System\Data\UserData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use Edutiek\AssessmentService\Assessment\Data\Corrector;
use Edutiek\AssessmentService\Assessment\Corrector\FullService as CorrectorService;
use Edutiek\AssessmentService\Task\CorrectorTemplate\FullService as TemplateServiceService;
use Edutiek\AssessmentService\Task\Data\CorrectorTemplate;
use Edutiek\AssessmentService\System\Entity\FullService as EntityService;
use Edutiek\AssessmentService\System\Transform\FullService as TransformService;

/**
 * Edit correction report by corrector
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorReportGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorReportGUI extends BaseGUI
{
    private EntityService $entity_service;
    private CorrectorService $corrector_service;
    private TransformService $transform_service;

    private Corrector $corrector;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->entity_service = $this->system_api->entity();
        $this->transform_service = $this->system_api->transform();
        $this->corrector_service = $this->assessment_api->corrector();
    }

    public function executeCommand()
    {
        $this->initTools(true, false);

        $this->corrector = $this->corrector_service->oneByUserId($this->user->getId());

        $cmd = $this->ctrl->getCmd('edit');
        switch ($cmd) {
            case 'edit':
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    private function edit()
    {
        $form = $this->buildForm();
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
            $result = $form->getInputGroup()->getContent();

            if ($result->isOK()) {
                $this->update($data);
            }
        }

        $this->add($form)->show();
    }

    private function update(array $data): void
    {
        $this->corrector->setCorrectionReport($this->transform_service->trimRichText($data['content']));
        $this->entity_service->secure($this->corrector, Corrector::class);
        $this->corrector_service->save($this->corrector);


        $this->success($this->plugin->txt("correction_report_saved"), true);
        $this->ctrl->redirect($this, "edit");
    }

    private function buildForm(): Standard
    {
        $fields = [
            'content' => $this->plugin_ui_factory->field()
                ->tinyMCE($this->plugin->txt("correction_report"), $this->plugin->txt("correction_report_info"))
                ->withValue($this->corrector->getCorrectionReport() ?? '')
        ];

        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $fields);
    }
}
