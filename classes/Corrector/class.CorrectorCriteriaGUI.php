<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;


use ILIAS\Plugin\LongEssayAssessment\Criteria\CriteriaGUI;
use Edutiek\AssessmentService\Assessment\Data\Corrector;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Task\Data\CorrectionSettings;
use Edutiek\AssessmentService\Task\Data\CriteriaMode;
use Edutiek\AssessmentService\Task\Data\RatingCriterion;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Tree\RepositoryTaskTree;
use ILIAS\Plugin\LongEssayAssessment\UI\Tree\RepositorySelectModal;
use ILIAS\UI\Component\Component;
use Edutiek\AssessmentService\Task\Data\CorrectorTaskPrefs;
use Edutiek\AssessmentService\Task\CorrectorTaskPrefs\FullService as CorrectorTaskPrefsService;
use Edutiek\AssessmentService\Assessment\Corrector\FullService as CorrectorService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use ILIAS\UI\Implementation\Component\Signal;
use Edutiek\AssessmentService\System\Data\UserData;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorCriteriaGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorCriteriaGUI extends CriteriaGUI
{

    private CorrectionSettings $settings;
    private CorrectorTaskPrefsService $corrector_task_pref_service;
    private CorrectorService $corrector_service;
    private UserService $user_service;
    protected ?Corrector $corrector;

    public function __construct(BaseObjectData $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->settings = $this->task_api->correctionSettings()->get();
        $this->corrector = $this->assessment_api->corrector()->getByUserId($this->dic->user()->getId());
        $this->corrector_task_pref_service = $this->task_api->correctorTaskPrefs();
        $this->corrector_service = $this->assessment_api->corrector();
        $this->user_service = $this->system_api->user();
    }

    public function executeCommand()
    {
        if($this->corrector === null) {
            $this->tpl->setContent('unknown corrector ');
            return;
        }
        parent::executeCommand();
    }


    protected function getRatingCriteriaFromContext(): array
    {
        switch ($this->settings->getCriteriaMode()) {
            case CriteriaMode::CORRECTOR:
                return $this->criterion_service->allForCorrector($this->getCorrectorIdFromContext());
            case CriteriaMode::FIXED:
                return $this->criterion_service->allByCorrectorId(null);
            default:
                return [];
        }
    }

    protected function getRatingCriterionModelFromContext(): RatingCriterion
    {
        return $this->criterion_service->new()->setCorrectorId($this->getCorrectorIdFromContext());
    }

    protected function getCorrectorIdFromContext(): ?int
    {
        return $this->corrector->getId();
    }

    protected function allowChangeInContext(): bool
    {
        return $this->settings->getCriteriaMode() == CriteriaMode::CORRECTOR
            && !$this->hasAuthorizedCorrections();
    }

    protected function allowSettingsInContext(): bool
    {
        return false;
    }

    protected function allowShareInContext(): bool
    {
        return $this->settings->getCriteriaMode() == CriteriaMode::CORRECTOR;
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

        $this->addCopyToolbar();

        $components[] = $table;
        $this->tpl->setContent($this->renderer->render($components));

    }

    public function publishRatingCriterion()
    {
        $query = $this->dic->http()->wrapper()->query();


        if($query->has('publish') && $this->allowShareInContext()) {
            $toggle = $query->retrieve('publish', $this->refinery->kindlyTo()->string()) === 'on';
            $task_prefs = $this->corrector_task_pref_service->get($this->corrector->getId(), $this->task_info->getId());
            $task_prefs->setCriterionCopy($toggle);
            $this->corrector_task_pref_service->save($task_prefs);

            if($toggle) {
                $this->tpl->setOnScreenMessage("success", $this->plugin->txt("criteria_publish_enabled"), true);
            } else {
                $this->tpl->setOnScreenMessage("success", $this->plugin->txt("criteria_publish_disabled"), true);
            }
        }
        $this->ctrl->clearParameters($this);
        $this->ctrl->redirect($this, "showItems");
    }

    public function previewItemsAsync()
    {
        $query = $this->request->getQueryParams();
        $content = [];
        $title = "not found";
        $allowed_corrector = $this->corrector_task_pref_service->getEnabledCriterionCopyCorrectorIds($this->task_info->getId());
        $allowed_corrector[] = null;

        if(isset($query["criteria_group"]) && $this->getCorrectorIdFromContext() !== null) {
            if ($query["criteria_group"] == "group_-1") {
                $from_corrector_id = null;
            } else {
                $from_corrector_id =  (int)str_replace("group_", "", $query["criteria_group"]);
            }

            if (in_array($from_corrector_id, $allowed_corrector)) {
                $group = $this->criterion_service->allByCorrectorId($from_corrector_id);
                $items = [];
                foreach ($group as $criterion) {
                    $items[] = $this->ui_factory->item()->standard($this->buildItemTitle($criterion))
                                               ->withDescription(nl2br($criterion->getDescription()));
                }
                if($from_corrector_id !== null) {
                    $corrector = $this->corrector_service->oneById($from_corrector_id);
                    $title = sprintf(
                        $this->plugin->txt('criteria_from'),
                        $this->user_service->getUser($corrector->getUserId())->getFullname(true)
                    );
                } else {
                    $title = $this->plugin->txt('criteria_template');
                }

                $content[] = $this->ui_factory->item()->group("", $items);
            }
        }
        $modal = $this->ui_factory->modal()->roundtrip($title, $content);
        echo($this->renderer->renderAsync($modal));
        exit();
    }

    /**
     * Copy criteria from another corrector or from the default criteria
     */
    public function copyItems()
    {
        $query = $this->request->getQueryParams();
        $allowed_corrector = $this->corrector_task_pref_service->getEnabledCriterionCopyCorrectorIds($this->task_info->getId());
        $allowed_corrector[] = null;
        $success = false;

        if(isset($query["criteria_group"]) && $this->getCorrectorIdFromContext() !== null) {
            if ($query["criteria_group"] == "group_-1") {
                $from_corrector_id = null;
            } else {
                $from_corrector_id = (int)str_replace("group_", "", $query["criteria_group"]);
            }

            if (in_array($from_corrector_id, $allowed_corrector)) {
                $to_corrector_id = $this->getCorrectorIdFromContext();
                $this->criterion_service->copyFromCorrector($this->task_info->getId(), $to_corrector_id, $from_corrector_id);
                $success = true;
            }
        }

        if($success) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("copy_criteria_successful"), true);
        } else {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("copy_criteria_failure"), true);
        }
        $this->ctrl->clearParameters($this);
        $this->ctrl->redirect($this, "showItems");
    }

    /**
     * Add toolbar for sharing and copy of criteria between correctors
     */
    protected function addCopyToolbar()
    {
        // add button to copy criteria from other corrector
        if ($this->getCorrectorIdFromContext() !== null && $this->allowChangeInContext()) {
            $select = $this->copyGroupSelect();
            if(!empty($select->getOptions())) {

                $modal = $this->ui_factory->modal()->roundtrip("", [])->withAsyncRenderUrl("#");
                $signal = new Signal(str_replace(".", "_", uniqid('il_signal_', true)));
                $close_signal = $modal->getCloseSignal();
                $preview_link = $this->ctrl->getLinkTarget($this, "previewItemsAsync", "", true);

                $modal = $modal->withOnLoadCode(function ($id) use ($signal, $preview_link, $close_signal) {
                    return "$(document).on('{$signal}', function(event, signalData) {
                        console.log('$preview_link&criteria_group=' + $('#criteria_group').val());
                        let modal = document.getElementById('$id');
					 	il.UI.modal.showModal(
							modal, 
							{'url': '#{$id}', 'ajaxRenderUrl': '$preview_link&criteria_group=' + $('#criteria_group').val(), 'keyboard': true},
							signalData,
							'$close_signal'
						); 
						return false;
					 });";
                });

                $copy_action = $this->ctrl->getFormAction($this, "copyItems");

                $this->toolbar->addComponent($modal);
                $this->toolbar->addText($this->plugin->txt('copy_rating_criterion_from'));
                $this->toolbar->addInputItem($select);
                $this->toolbar->addComponent(
                    $this->ui_factory->button()->standard($this->lng->txt('copy'), "")
                                    ->withOnLoadCode(function ($id) use ($copy_action) {
                                        return "$('#$id').on( 'click', function() {
                                            location.href='$copy_action&criteria_group=' + $('#criteria_group').val();
                                        });";
                                    })
                );
                $this->toolbar->addComponent(
                    $this->ui_factory->button()->standard($this->lng->txt('preview'), "#")->withOnClick($signal)
                );
            }

            if ($this->allowShareInContext()) {
                $prefs = $this->corrector_task_pref_service->get($this->getCorrectorIdFromContext(), $this->task_info->getId());

                $this->ctrl->setParameter($this, "publish", "on");
                $on_action = $this->ctrl->getFormAction($this, "publishRatingCriterion");

                $this->ctrl->setParameter($this, "publish", "off");
                $off_action = $this->ctrl->getFormAction($this, "publishRatingCriterion");

                $this->ctrl->clearParameters($this);
                $this->toolbar->addText($this->plugin->txt("publish_rating_criterion"));
                $this->toolbar->addComponent(
                    $this->ui_factory->button()->toggle("", $on_action, $off_action, $prefs->getCriterionCopy())
                );
            }
        }
    }

    protected function copyGroupSelect()
    {
        $group = $this->corrector_task_pref_service->getEnabledCriterionCopyCorrectorIds($this->task_info->getId());
        $global = $this->criterion_service->allByCorrectorId(null);


        $items = [];
        $user_ids = [];
        $names = [];

        foreach($this->corrector_service->all() as $corrector) {
            $user_ids[$corrector->getId()] = $corrector->getUserId();
        }
        $corrector_ids = array_flip($user_ids);

        foreach ($this->user_service->getUsersByIds($user_ids) as $user)
        {
            $corrector_id = $corrector_ids[$user->getId()] ?? null;
            $names[$corrector_id] = $user->getFullname(true);
        }

        foreach ($group as $corrector_id) {
            if($corrector_id == $this->getCorrectorIdFromContext()) {
                continue;
            } elseif(isset($names[$corrector_id])) {
                $name = $names[$corrector_id];
            } else {
                continue;
            }
            $items["group_" . $corrector_id] = $name;
        }
        if(!empty($global)) {
            $items = array_merge(["group_-1" => $this->plugin->txt('criteria_template')], $items);
        }
        $select = new \ilSelectInputGUI("", "criteria_group");
        $select->setOptions($items);
        return $select;
    }

    public function getTableActions(): array
    {
        return [
            $this->createAction(),
            $this->editAction(),
            $this->deleteAction()
        ];
    }
}
