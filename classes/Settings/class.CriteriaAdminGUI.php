<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\UI\Factory;
use Edutiek\AssessmentService\EssayTask\Data\CriteriaMode;
use ILIAS\Plugin\LongEssayAssessment\UI\Tree\RepositorySelectModal;
use ILIAS\UI\Component\Component;
use ILIAS\Plugin\LongEssayAssessment\EssayTask\Data\RatingCriterion;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\Criteria\CriteriaGUI;
use ILIAS\Plugin\LongEssayAssessment\Criteria\CriteriaItem;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Settings
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\CriteriaAdminGUI: ilObjLongEssayAssessmentGUI
 */
class CriteriaAdminGUI extends CriteriaGUI
{
    protected function getRatingCriteriaFromContext(): array
    {
        return $this->criterion_service->allByCorrectorId(null);
    }

    protected function getRatingCriterionModelFromContext(): \Edutiek\AssessmentService\EssayTask\Data\RatingCriterion
    {
        return $this->criterion_service->new()->setCorrectorId(null);
    }

    protected function getCorrectorIdFromContext(): ?int
    {
        return null;
    }

    protected function allowChangeInContext(): bool
    {
        switch ($this->correction_settings->getCriteriaMode()) {
            case CriteriaMode::NONE:
                return false;
            case CriteriaMode::FIXED:
            case CriteriaMode::CORRECTOR:
                return !$this->hasAuthorizedCorrections();
        }
        return false;
    }

    protected function allowSettingsInContext(): bool
    {
        return !$this->hasAuthorizedCorrections();
    }

    protected function allowShareInContext(): bool
    {
        return false;
    }

    public function showItems()
    {
        $components = [];
        if ($this->hasAuthorizedCorrections()) {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt('criteria_admin_authorized_message'));
        }
        switch ($this->correction_settings->getCriteriaMode()) {
            case CriteriaMode::NONE:
                $mode_message = $this->plugin->txt('criteria_mode_none_info');
                break;
            case CriteriaMode::FIXED:
                $mode_message = $this->plugin->txt('criteria_mode_fixed_info');
                break;
            case CriteriaMode::CORRECTOR:
                $mode_message = $this->plugin->txt('criteria_mode_corrector_info');
                break;
        }
        $components[] = $this->ui_factory->panel()->standard($this->plugin->txt('criteria_mode'), $this->ui_factory->legacy($mode_message));

        $table = $this->table();

        if ($this->allowChangeInContext()) {
            $table->addActionToToolbar($this->toolbar, $table->getActionByName("add_criteria"), true);
        } else {
            $table->disableAction(true);
        }

        $table->addActionToToolbar($this->toolbar, $table->getActionByName("criteria_settings"));
        $components[] = $table;
        $this->tpl->setContent($this->renderer->render($components));

    }

    protected function settingsAction() : Action\Form
    {
        $form =  $this->table_factory->action()->form(
            "criteria_settings",
            $this->lng->txt('settings'),
            $this->lng->txt('save'),
            fn (CriteriaItem $item) => $this->buildSettingsFields(),
            fn (CriteriaItem $item, array $data) => $this->saveSettings($data),
            fn (CriteriaItem $x) => $this->allowChangeInContext(),
            Action\Type::Global
        );
        switch ($this->correction_settings->getCriteriaMode()) {
            case CriteriaMode::FIXED:
                $box = $this->ui_factory->messageBox()->info($this->plugin->txt('criteria_mode_change_from_fixed_message'));
                break;
            case CriteriaMode::CORRECTOR:
                $box = $this->ui_factory->messageBox()->info($this->plugin->txt('criteria_mode_change_from_corrector_message'));
                break;
            case CriteriaMode::NONE:
            default:
                $box = $this->ui_factory->messageBox()->info($this->plugin->txt('criteria_mode_change_from_none_message'));
                break;
        }
        return $form->withContent([$box]);
    }

    protected function buildSettingsFields(): array
    {
        return ['criteria_mode' => $this->ui_factory->input()->field()->radio($this->plugin->txt('criteria_mode'))
                                                    ->withRequired(true)
                                                    ->withOption(
                                                        CriteriaMode::NONE->value,
                                                        $this->plugin->txt('criteria_mode_none'),
                                                        $this->plugin->txt('criteria_mode_none_info')
                                                    )
                                                    ->withOption(
                                                        CriteriaMode::FIXED->value,
                                                        $this->plugin->txt('criteria_mode_fixed'),
                                                        $this->plugin->txt('criteria_mode_fixed_info')
                                                    )
                                                    ->withOption(
                                                        CriteriaMode::CORRECTOR->value,
                                                        $this->plugin->txt('criteria_mode_corrector'),
                                                        $this->plugin->txt('criteria_mode_corrector_info')
                                                    )
                                                    ->withValue($this->correction_settings->getCriteriaMode()->value)
        ];
    }


    public function saveSettings(array $data)
    {
        $old_mode = $this->correction_settings->getCriteriaMode();
        $new_mode = CriteriaMode::from($data['criteria_mode']);
        if ($old_mode !== $new_mode) {
            $this->correction_settings->setCriteriaMode($new_mode);
            $this->correction_settings_service->save($this->correction_settings);

            // This triggers the transition from criteria modes asisde to the removing of authorisations and logging of such
            $this->correction_settings_service->changeCriteriaMode($old_mode, $new_mode);

            $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
        }
    }


    // TODO: This feature need a task selector, when select rating criterions to copy. will skip for now.
//    /**
//     * Copy criteria from another object
//     */
//    protected function copyCriteria() : void
//    {
//        if ($this->getCorrectorIdFromContext() !== null) {
//            throw new ilException("Operation not permitted");
//        }
//        $select = $this->buildRepositorySelect();
//
//        if ($select->hasSelected()) {
//            $copy_object = new \ilObjLongEssayAssessment($select->getSelectedId());
//            $copy_assessment_api = $this->plugin->dic()->assessment($copy_object->getAssId(), $this->user->getId());
//            $copy_grade_service = $copy_assessment_api->r();
//
//            foreach ($criteria as $criterion) {
//                $new = clone $criterion;
//                $new->setId(0);
//                $new->setCorrectorId(null);
//                $new->setObjectId($this->object->getId());
//                $this->object_repo->save($new);
//            }
//
//            $this->tpl->setOnScreenMessage("success", $this->plugin->txt('copy_criteria_successful'), true);
//            $this->ctrl->redirect($this, "showItems");
//        } else {
//            $select->showAsync();
//        }
//    }
//
//    protected function buildRepositorySelect() : RepositorySelectModal
//    {
//        return $this->plugin_ui_factory->tree()->repositorySelect(
//            $this->object->getRefId(),
//            $this->plugin->txt("copy_criteria"),
//            [$this, "listCriterion"],
//            $this->ctrl->getLinkTarget($this, 'copyCriteria', null, true)
//        )->setPermission("maintain_task")
//                             ->setMessage($this->plugin->txt('copy_criteria_info'));
//    }
//
//    public function listCriterion(int $ref_id) : Component
//    {
//        $this->copy_context = $ref_id;
//
//        $table = $this->table_factory->dataTable("copy_criteria", $this);
//        $table->setAdditionalParameter($this->setSmallView());
//
//        return $table->getTable();
//    }

    public function getTableActions(): array
    {
        return [
            $this->createAction(),
            $this->editAction(),
            $this->deleteAction(),
            $this->settingsAction()
        ];
    }
}
