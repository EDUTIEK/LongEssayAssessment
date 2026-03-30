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
            case 'share':
            case 'adopt':
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

        $this->setToolbar();
        $this->addCopyForm();

        $panel = $this->ui_factory->panel()->standard($this->plugin->txt('edit_corrector_template'), $form);
        $this->add($panel)->show();
    }

    private function update(array $data): void
    {
        $this->template->setContent(
            $this->transform_service->trimRichText($data['content'])
        );

        $this->entity_service->secure($this->template, CorrectorTemplate::class);
        $this->templates_service->save($this->template);

        $this->success($this->plugin->txt("corrector_template_saved"), true);
        $this->ctrl->redirect($this, "edit");
    }

    private function share(): void
    {
        $toggle = $this->get->string('shared', 'off');
        $this->templates_service->save($this->template->setShared($toggle === 'on'));
        $this->ctrl->redirect($this, "edit");
    }

    public function adopt(): void
    {
        if (!empty($template = $this->getTemplateToAdopt())) {
            $this->templates_service->save($this->template->setContent($template->getContent()));
            $this->success($this->plugin->txt("corrector_template_adopted"), true);
        }
        $this->ctrl->redirect($this, "edit");
    }

    private function buildForm(): Standard
    {
        $fields = [
            'content' => $this->plugin_ui_factory->field()
                ->tinyMCE($this->plugin->txt("corrector_template_label"), $this->plugin->txt("corrector_template_info"))
                ->withValue($this->template->getContent() ?? '')
        ];

        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $fields);
    }

    private function getTemplateToAdopt(): ?CorrectorTemplate
    {
        $share_id = $this->get->integer('share_id');
        if ($share_id) {
            $template = $this->templates_service->getByTaskIdAndCorrectorId($this->task_info->getId(), $share_id);
            if ($template?->getShared()) {
                return $template;
            }
        }
        return null;
    }

    private function addCopyForm(): void
    {
        if (empty($template = $this->getTemplateToAdopt())) {
            return;
        }

        $corrector = $this->corrector_service->oneById($template->getCorrectorId());
        $user = $this->system_api->user()->getUser($corrector?->getUserId() ?? 0);

        $fields = [
            'preview' => $this->plugin_ui_factory->field()->info(
                $user?->getListname(false) ?? $this->plugin->txt('unknown'),
            )->withInfo(
                $this->ui_factory->legacy($this->displayContent($template->getContent()))
            )
        ];
        $form = $this->plugin_ui_factory->field()->blankForm('#', $fields);

        $this->ctrl->setParameter($this, 'share_id', $template->getCorrectorId());
        $adopt = $this->ui_factory->button()->primary(
            $this->plugin->txt('adopt_corrector_template_confirm'),
            $this->ctrl->getLinkTarget($this, 'adopt')
        );
        $this->ctrl->clearParameters($this);
        $cancel = $this->ui_factory->button()->standard($this->lng->txt('cancel'), $this->ctrl->getLinkTarget($this, 'edit'));

        $panel = $this->ui_factory->panel()->standard($this->plugin->txt('adopt_corrector_template'), [
            $form, $adopt, $cancel,
        ]);

        $this->add($panel);
    }

    private function setToolbar()
    {
        $action = $this->ctrl->getFormAction($this, "edit");

        $corrector_ids = array_filter(
            $this->templates_service->getSharableCorrectorIds($this->task_info->getId()),
            fn($id) => $id !== $this->corrector->getId()
        );

        $correctors = $this->corrector_service->some($corrector_ids);
        /** @var UserData[] $users */
        $users = $this->system_api->user()->getUsersByIds(array_map(fn(Corrector $c) => $c->getUserId(), $correctors));

        $items = [];
        foreach ($correctors as $corrector) {
            $this->ctrl->setParameter($this, 'share_id', $corrector->getId());
            $items[] = $this->ui_factory->link()->standard(
                ($users[$corrector->getUserId()] ?? null)?->getListname(true) ?? $this->plugin->txt('unknown'),
                $this->ctrl->getLinkTarget($this, 'edit'),
            );
        }
        $listing = $this->ui_factory->listing()->unordered($items);

        $modal = $this->ui_factory->modal()->roundtrip(
            $this->plugin->txt('adopt_corrector_template'),
            [
                $this->ui_factory->messageBox()->info($this->plugin->txt('adopt_corrector_template_message')),
                $listing
            ],
            []
        )->withActionButtons([]);
        $button = $this->ui_factory->button()->standard($this->plugin->txt('adopt_corrector_template'), '')
            ->withOnClick($modal->getShowSignal())
        ->withUnavailableAction(empty($items));

        $this->add($modal);
        $this->toolbar->addComponent($button);

        $this->ctrl->setParameter($this, "shared", "on");
        $on_action = $this->ctrl->getFormAction($this, "share");

        $this->ctrl->setParameter($this, "shared", "off");
        $off_action = $this->ctrl->getFormAction($this, "share");

        $this->ctrl->clearParameters($this);
        $this->toolbar->addText($this->plugin->txt("share_corrector_template"));
        $this->toolbar->addComponent(
            $this->ui_factory->button()->toggle(
                "",
                $on_action,
                $off_action,
                $this->template->getShared()
            )
        );
    }
}
