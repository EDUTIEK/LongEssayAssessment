<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

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
 * Edit summary template by corrector
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorTemplateGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorTemplateGUI extends BaseGUI
{
    private EntityService $entity_service;
    private CorrectorService $corrector_service;
    private TransformService $transform_service;
    private TemplateServiceService $templates_service;

    private Corrector $corrector;
    private CorrectorTemplate $template;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->entity_service = $this->system_api->entity();
        $this->transform_service = $this->system_api->transform();
        $this->corrector_service = $this->assessment_api->corrector();
        $this->templates_service = $this->task_api->correctorTemplates();
    }

    public function executeCommand()
    {
        $this->initTools(true, false);

        $this->corrector = $this->assessment_api->corrector()->oneByUserId($this->user->getId());
        $this->template = $this->templates_service->getByTaskIdAndCorrectorId($this->task_info->getId(), $this->corrector->getId());

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
        $this->template->setContent(
            $this->transform_service->trimRichText($data['form']['content'])
        );

        $this->entity_service->secure($this->template, CorrectorTemplate::class);
        $this->templates_service->save($this->template);

        $this->success($this->plugin->txt("corrector_template_saved"), true);
        $this->ctrl->redirect($this, "edit");
    }

    private function buildForm(): Standard
    {
        $factory = $this->ui_factory->input()->field();
        $sections = [];
        $fields = [];

        $fields['content'] = $this->plugin_ui_factory->field()
           ->tinyMCE($this->plugin->txt("corrector_template_label"), $this->plugin->txt("corrector_template_info"))
           ->withValue($this->template->getContent() ?? '');

        $sections["form"] = $factory->section($fields, $this->plugin->txt('edit_corrector_template'));

        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }
}
