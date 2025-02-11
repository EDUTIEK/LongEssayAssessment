<?php

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\RatingCriterion;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\Factory as CustomFactory;
use ILIAS\UI\Implementation\Component\Signal;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use LTI\ilGlobalTemplate;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorCriteriaService;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ILIAS\UI\Component\Table\Column\Column;
use Generator;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\SmallView;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use ILIAS\Export\ImportStatus\Exception\ilException;
use ILIAS\Plugin\LongEssayAssessment\UI\Tree\RepositorySelectModal;
use ILIAS\UI\Component\Component;

abstract class CriteriaGUI extends BaseGUI implements DataTableParent
{
    use SmallView, ConfirmationIds;
    protected CustomFactory $custom_factory;
    protected ObjectRepository $object_repo;
    protected WriterRepository $writer_repo;
    protected CorrectorRepository $corrector_repo;
    protected TaskRepository $task_repo;
    protected CorrectionSettings $settings;
    protected CorrectorAdminService $admin_service;
    protected CorrectorCriteriaService $criteria_service;
    protected \ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory $table_factory;
    protected ?int $copy_context = null;

    private ?bool $has_authorized_corrections = null;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->post = $this->dic->http()->wrapper()->post();
        $this->custom_factory = $this->localDI->getUIFactory();
        $this->object_repo = $this->localDI->getObjectRepo();
        $this->writer_repo = $this->localDI->getWriterRepo();
        $this->corrector_repo = $this->localDI->getCorrectorRepo();
        $this->task_repo = $this->localDI->getTaskRepo();

        $this->settings = $this->task_repo->getCorrectionSettingsById($this->object->getId());
        $this->admin_service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->criteria_service = $this->localDI->getCorrectorCriteriaService($this->object->getId());

        $this->table_factory = $this->localDI->getTableFactory();
    }

    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showItems');

        switch ($cmd) {
            case 'showItems':
                $this->$cmd();
                break;

            case 'publishRatingCriterion':
                $this->allowShareInContext() ? $this->$cmd() : $this->tpl->setContent('not allowed command: ' . $cmd);
                break;

            case 'deleteItems':
            case 'copyCriteria':
            case 'copyItems':
            case 'previewItemsAsync':
                $this->allowChangeInContext() ? $this->$cmd() : $this->tpl->setContent('not allowed command: ' . $cmd);
                break;

            case 'settingsAsync':
                $this->allowSettingsInContext() ? $this->$cmd() : $this->tpl->setContent('not allowed command: ' . $cmd);
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * @return RatingCriterion[]
     */
    abstract protected function getRatingCriteriaFromContext():array;
    abstract protected function getRatingCriterionModelFromContext(): RatingCriterion;
    abstract protected function getCorrectorIdFromContext(): ?int;
    abstract protected function allowChangeInContext(): bool;
    abstract protected function allowShareInContext(): bool;
    abstract protected function allowSettingsInContext(): bool;

    protected function hasAuthorizedCorrections(): bool
    {
        return $this->has_authorized_corrections ??= $this->admin_service->authorizedCorrectionsExists();
    }

    public function showItems()
    {
        $components = [];

        // message if change woulds be possible, but is blocked by authorized corrections
        if (!$this->allowChangeInContext()) {
            if ($this->getCorrectorIdFromContext() === null && $this->settings->getCriteriaMode() !== CorrectionSettings::CRITERIA_MODE_NONE) {
                if ($this->hasAuthorizedCorrections()) {
                    $this->tpl->setOnScreenMessage(ilGlobalTemplate::MESSAGE_TYPE_INFO, $this->plugin->txt('criteria_admin_authorized_message'));
                }
            }
            if ($this->getCorrectorIdFromContext() !== null && $this->settings->getCriteriaMode() == CorrectionSettings::CRITERIA_MODE_CORRECTOR) {
                if ($this->admin_service->finalizedCorrectionsExist($this->getCorrectorIdFromContext())) {
                    $this->tpl->setOnScreenMessage(ilGlobalTemplate::MESSAGE_TYPE_INFO, $this->plugin->txt('criteria_corrector_finalized_message'));
                }
                elseif ($this->hasAuthorizedCorrections()) {
                    $this->tpl->setOnScreenMessage(ilGlobalTemplate::MESSAGE_TYPE_INFO, $this->plugin->txt('criteria_corrector_authorized_message'));
                }
            }
        }

        // panel showing an explanation of the criteria mode
        switch($this->settings->getCriteriaMode()) {
            case CorrectionSettings::CRITERIA_MODE_NONE:
                $mode_message = $this->plugin->txt('criteria_mode_none_info');
                break;
            case CorrectionSettings::CRITERIA_MODE_FIXED:
                $mode_message = $this->plugin->txt('criteria_mode_fixed_info');
                break;
            case CorrectionSettings::CRITERIA_MODE_CORRECTOR:
                $mode_message = $this->plugin->txt('criteria_mode_corrector_info');
                break;
        }
        $components[] = $this->uiFactory->panel()->standard($this->plugin->txt('criteria_mode'), $this->uiFactory->legacy($mode_message));

        $table = $this->table_factory->dataTable("criteria", $this);
        $table->executeAction();
        $table->setTitle($this->plugin->txt("criteria"));

        if($this->allowChangeInContext()) {
            $table->addActionToToolbar($this->toolbar, $table->getActionByName("add_criteria"), true);
        } else {
            $table->disableAction(true);
        }

        if ($this->allowSettingsInContext()) {
            $table->addActionToToolbar($this->toolbar, $table->getActionByName("criteria_settings"));
        }
        
        $this->addCopyToolbar();

        $this->setContent($this->renderer->render(array_merge($components, $table->getComponents())));
    }

    protected function buildItemTitle(RatingCriterion $item): string
    {
        return $item->getTitle() . " | "
            . ($item->getIsGeneral() ? $this->plugin->txt('criterion_type_general') : $this->plugin->txt('criterion_type_comment')) . " | "
            . $this->plugin->txt("criteria_max_point") . ": " . $item->getPoints();
    }

    public function deleteItems()
    {
        $criteria_ids = array_map(fn (RatingCriterion $x) => $x->getId(), $this->getRatingCriteriaFromContext());
        $delete_ids = $this->confirmationIds();
        $success = false;

        if (!empty($delete_ids) !== null) {
            foreach ($delete_ids as $id) {
                if (in_array($id, $criteria_ids)) {
                    $this->object_repo->deleteRatingCriterion($id);
                    $success = true;
                }
            }
        }

        if($success) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("delete_criteria_successful"), true);
        } else {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("delete_criteria_failure"), true);
        }
        $this->ctrl->clearParameters($this);
        $this->ctrl->redirect($this, "showItems");
    }

    public function publishRatingCriterion()
    {
        $param = $this->request->getQueryParams();
        if(isset($param["publish"]) && $this->allowShareInContext()) {
            $toggle = $param["publish"] == "on";
            $corrector = $this->corrector_repo->getCorrectorById($this->getCorrectorIdFromContext());

            $corrector->setCriterionCopyEnabled($toggle);
            $this->corrector_repo->save($corrector);

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
        $allowed_corrector = array_map(fn ($x) => $x['corrector_id'], $this->object_repo->getRatingCriterionGroupForCopy($this->object->getId()));
        $allowed_corrector[] = null;

        if(isset($query["criteria_group"]) && $this->getCorrectorIdFromContext() !== null) {
            if ($query["criteria_group"] == "group_-1") {
                $from_corrector_id = null;
            } else {
                $from_corrector_id =  (int)str_replace("group_", "", $query["criteria_group"]);
            }

            if (in_array($from_corrector_id, $allowed_corrector)) {
                $group = $this->object_repo->getRatingCriteriaByObjectId($this->object->getId(), $from_corrector_id);
                $items = [];
                foreach ($group as $criterion) {
                    $items[] = $this->uiFactory->item()->standard($this->buildItemTitle($criterion))
                        ->withDescription(nl2br($criterion->getDescription()));
                }
                if($from_corrector_id !== null) {
                    $corrector = $this->corrector_repo->getCorrectorById($from_corrector_id);
                    $title = sprintf(
                        $this->plugin->txt('criteria_from'),
                        $this->common_services->userDataHelper()->getPresentation($corrector->getUserId())
                    );
                } else {
                    $title = $this->plugin->txt('criteria_template');
                }

                $content[] = $this->uiFactory->item()->group("", $items);
            }
        }
        $modal = $this->uiFactory->modal()->roundtrip($title, $content);
        echo($this->renderer->renderAsync($modal));
        exit();
    }

    /**
     * Copy criteria from another corrector or from the default criteria 
     */
    public function copyItems()
    {
        $query = $this->request->getQueryParams();
        $allowed_corrector = array_map(fn ($x) => $x['corrector_id'], $this->object_repo->getRatingCriterionGroupForCopy($this->object->getId()));
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

                $group = $this->object_repo->getRatingCriteriaByObjectId($this->object->getId(), $from_corrector_id);
                $this->object_repo->deleteRatingCriterionByObjectIdAndCorrectorId($this->object->getId(), $to_corrector_id);

                foreach ($group as $criterion) {
                    $new = clone $criterion;
                    $new->setId(0);
                    $new->setCorrectorId($to_corrector_id);
                    $this->object_repo->save($new);
                }
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
        // add button to copy criteria from other object
        if($this->getCorrectorIdFromContext() === null && $this->allowChangeInContext()) {
            $select = $this->buildRepositorySelect();
            list($btn, $modal) = $select->getToolbarComponents($this->plugin->txt("copy_criteria"));
            $this->addModal($modal);
            $this->toolbar->addComponent($btn);
        }

        // add button to copy criteria from other corrector
        if ($this->getCorrectorIdFromContext() !== null && $this->allowChangeInContext()) {
            $corrector = $this->corrector_repo->getCorrectorById($this->getCorrectorIdFromContext());
            $select = $this->copyGroupSelect();
            if(!empty($select->getOptions())) {
                $modal = $this->uiFactory->modal()->roundtrip("", [])->withAsyncRenderUrl("#");
                $signal = new Signal(str_replace(".", "_", uniqid('il_signal_', true)));
                $preview_link = $this->ctrl->getLinkTarget($this, "previewItemsAsync", "", true);

                $modal = $modal->withOnLoadCode(function ($id) use ($signal, $preview_link) {
                    return "$(document).on('{$signal}', function(event, signalData) {
					 	il.UI.modal.showModal(
							'{$id}', 
							{'url': '#{$id}', 'ajaxRenderUrl': '$preview_link&criteria_group=' + $('#criteria_group').val(), 'keyboard': true},
							signalData
						); 
						return false;
					 });";
                });

                $copy_action = $this->ctrl->getFormAction($this, "copyItems");

                $this->toolbar->addComponent($modal);
                $this->toolbar->addText($this->plugin->txt('copy_rating_criterion_from'));
                $this->toolbar->addInputItem($select);
                $this->toolbar->addComponent(
                    $this->uiFactory->button()->standard($this->lng->txt('copy'), "")
                    ->withOnLoadCode(function ($id) use ($copy_action) {
                        return "$('#$id').on( 'click', function() {
  							location.href='$copy_action&criteria_group=' + $('#criteria_group').val();
} 						);";
                    })
                );
                $this->toolbar->addComponent(
                    $this->uiFactory->button()->standard($this->lng->txt('preview'), "#")->withOnClick($signal)
                );
            }

            if ($this->allowShareInContext()) {
                $this->ctrl->setParameter($this, "publish", "on");
                $on_action = $this->ctrl->getFormAction($this, "publishRatingCriterion");

                $this->ctrl->setParameter($this, "publish", "off");
                $off_action = $this->ctrl->getFormAction($this, "publishRatingCriterion");

                $this->ctrl->clearParameters($this);
                $this->toolbar->addText($this->plugin->txt("publish_rating_criterion"));
                $this->toolbar->addComponent(
                    $this->uiFactory->button()->toggle("", $on_action, $off_action, $corrector->isCriterionCopyEnabled())
                );
            }
        }
    }

    protected function copyGroupSelect()
    {
        $group = $this->object_repo->getRatingCriterionGroupForCopy($this->object->getId());
        $global = $this->object_repo->getRatingCriteriaByObjectId($this->object->getId());
        $corrector_id = $this->getCorrectorIdFromContext();

        $items = [];
        $names = $this->common_services->userDataHelper()->getNames(array_map(fn ($x) => $x["usr_id"], $group));

        foreach ($group as $item) {
            if($item["corrector_id"] == $corrector_id) {
                continue;
            } elseif(isset($names[$item["usr_id"]])) {
                $name = $names[$item["usr_id"]];
            } else {
                continue;
            }
            $items["group_" . $item["corrector_id"]] = $name;
        }
        if(!empty($global)) {
            $items = array_merge(["group_-1" => $this->plugin->txt('criteria_template')], $items);
        }
        $select = new \ilSelectInputGUI("", "criteria_group");
        $select->setOptions($items);
        return $select;
    }

    public function getColumnMapping(Item $item, ?array $additional_parameters) : array
    {

        /**
         * @var CriteriaItem $item
         */
        return [
            "title" => $item->getTitle(),
            "description" => $item->getDescription(),
            "general" => $item->isGeneral(),
            "points" => $item->getMaxPoints()
        ];
    }

    public function getColumns(?array $additional_parameters) : array
    {
        $tf = $this->uiFactory->table();
        $small_view = $this->smallView($additional_parameters);
        $sortable = !$small_view;

        return [
            "title" => $tf->column()->text($this->plugin->txt('title'))->withIsSortable($sortable),
            "description" => $tf->column()->text($this->plugin->txt('description'))->withIsSortable($sortable),
            "general" => $tf->column()->boolean($this->plugin->txt('criterion_type'), $this->plugin->txt('criterion_type_general'), $this->plugin->txt('criterion_type_comment'))->withIsSortable($sortable),
            "points" => $tf->column()->number($this->plugin->txt('criteria_max_point'))->withIsSortable($sortable),
        ];
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters) : ?int
    {
        return -1;
    }

    public function getTableActions() : array
    {
        return [
            $this->editAction(),
            $this->deleteAction(),
            $this->createAction(),
            $this->settingsAction()
        ];
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null) : Generator
    {
        if ($this->copy_context !== null) {
            $obj_id = \ilObject2::_lookupObjectId($this->copy_context);
            //check ref access_rights
            foreach ($this->object_repo->getRatingCriteriaByObjectId($obj_id) as $object) {
                yield $this->tableItemFromData($object);
            }
            return;
        }
        if ($ids === [0]) {
            yield $this->tableItemFromData($this->getRatingCriterionModelFromContext());
            return;
        }
        foreach ($this->getRatingCriteriaFromContext() as $object) {
            if(empty($ids) || in_array($object->getId(), $ids)) {
                yield $this->tableItemFromData($object);
            }
        }
    }

    public function getTableItem(int $id) : Item
    {
        $item = $this->object_repo->getRatingCriterionById($id);
        if($item->getObjectId() !== $this->object->getId() && $item->getCorrectorId() !== $this->getCorrectorIdFromContext()) {
            throw new \Exception("Item is not allowed in this context.");
        }
        return $this->tableItemFromData($item);
    }

    protected function tableItemFromData(RatingCriterion $item): CriteriaItem
    {
        return new CriteriaItem($item->getId(), $item->getTitle(), $item->getDescription(), (int)$item->getIsGeneral(), $item->getPoints());
    }

    protected function createAction() : Action\Form
    {
        return $this->table_factory->action()->form(
            "add_criteria",
            $this->plugin->txt('criteria_add'),
            $this->lng->txt('save'),
            fn (CriteriaItem $item) => $this->buildFields($item),
            fn (CriteriaItem $item, array $data) => $this->save($item, $data),
            fn (CriteriaItem $x) => $this->allowChangeInContext(),
            Action\Type::Global
        );
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
        switch ($this->settings->getCriteriaMode()) {
            case CorrectionSettings::CRITERIA_MODE_FIXED:
                $box = $this->uiFactory->messageBox()->info($this->plugin->txt('criteria_mode_change_from_fixed_message'));
                break;
            case CorrectionSettings::CRITERIA_MODE_CORRECTOR:
                $box = $this->uiFactory->messageBox()->info($this->plugin->txt('criteria_mode_change_from_corrector_message'));
                break;
            case CorrectionSettings::CRITERIA_MODE_NONE:
            default:
                $box = $this->uiFactory->messageBox()->info($this->plugin->txt('criteria_mode_change_from_none_message'));
                break;
        }
        return $form->withContent([$box]);

    }

    protected function editAction() : Action\Form
    {
        return $this->table_factory->action()->form(
            "edit_criteria",
            $this->lng->txt('edit'),
            $this->lng->txt('save'),
            fn (CriteriaItem $item) => $this->buildFields($item),
            fn (CriteriaItem $item, array $data) => $this->save($item, $data),
            fn (CriteriaItem $x) => $this->allowChangeInContext(),
            Action\Type::Single
        );
    }

    protected function deleteAction() : Action\Confirmation
    {
        return $this->table_factory->action()->confirmation(
            "delete_criteria",
            $this->lng->txt('delete'),
            $this->lng->txt('delete'),
            $this->plugin->txt('delete_criteria_confirmation'),
            $this->ctrl->getFormAction($this, 'deleteItems'),
            fn (CriteriaItem $item) => $item->getTitle(),
            fn (CriteriaItem $x) => $this->allowChangeInContext(),
            Action\Type::Standard
        );
    }

    protected function buildFields(CriteriaItem $item) : array
    {
        return [
            'title' =>  $this->uiFactory->input()->field()->text($this->lng->txt("title"))
                                        ->withAdditionalTransformation($this->refinery->string()->hasMinLength(1))
                                        ->withRequired(true)
                                        ->withValue($item->getTitle()),
            'description' =>  $this->uiFactory->input()->field()->textarea($this->lng->txt("description"))
                                              ->withValue($item->getDescription() !== null ? $item->getDescription(): ""),
            'is_general' => $this->uiFactory->input()->field()->radio(
                $this->plugin->txt('criterion_type'))
                                            ->withOption('1', $this->plugin->txt('criterion_type_general'), $this->plugin->txt('criterion_type_general_info'))
                                            ->withOption('0', $this->plugin->txt('criterion_type_comment'), $this->plugin->txt('criterion_type_comment_info'))
                                            ->withValue( $item->isGeneral() ? '1' : '0')
                                            ->withDisabled(!empty($item->getId())),
            'points' => $this->custom_factory->field()->numeric(
                $this->plugin->txt('criteria_max_point'),
                $this->plugin->txt('criteria_max_point_desc')
            )
                                             ->withAdditionalTransformation($this->refinery->kindlyTo()->int())
                                             ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(0))
                                             ->withRequired(true)
                                             ->withValue($item->getMaxPoints())
        ];
    }

    protected function buildSettingsFields(): array
    {
        return ['criteria_mode' => $this->uiFactory->input()->field()->radio($this->plugin->txt('criteria_mode'))
              ->withRequired(true)
              ->withOption(
                  CorrectionSettings::CRITERIA_MODE_NONE,
                  $this->plugin->txt('criteria_mode_none'),
                  $this->plugin->txt('criteria_mode_none_info')
              )
              ->withOption(
                  CorrectionSettings::CRITERIA_MODE_FIXED,
                  $this->plugin->txt('criteria_mode_fixed'),
                  $this->plugin->txt('criteria_mode_fixed_info')
              )
              ->withOption(
                  CorrectionSettings::CRITERIA_MODE_CORRECTOR,
                  $this->plugin->txt('criteria_mode_corrector'),
                  $this->plugin->txt('criteria_mode_corrector_info')
              )
              ->withValue($this->settings->getCriteriaMode())
        ];
    }


    protected function save(CriteriaItem $item, array $data) : void
    {
        if ($item->getId() === 0) {
            $criterion = $this->getRatingCriterionModelFromContext();
        } else {
            $criterion = $this->object_repo->getRatingCriterionById($item->getId());
        }
        if (empty($criterion->getId())) {
            $criterion->setIsGeneral($data['is_general']);
        }
        $criterion->setTitle($data['title'])
                  ->setDescription($data['description'])
                  ->setPoints($data['points']);

        $this->object_repo->save($criterion);
        $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
    }

    public function saveSettings(array $data)
    {
        $old_mode = $this->settings->getCriteriaMode();
        $new_mode = (string) $data['criteria_mode'];
        if ($old_mode !== $new_mode && $this->allowSettingsInContext()) {
            $this->settings->setCriteriaMode($new_mode);
            $this->task_repo->save($this->settings);

            $this->criteria_service->changeCriteriaMode($old_mode, $new_mode);
            foreach ($this->writer_repo->getWritersByTaskId($this->object->getId()) as $writer) {
                $this->admin_service->removeAuthorizations($writer);
            }
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
        }
    }

    /**
     * Copy criteria from another object                 
     */
    protected function copyCriteria() : void
    {
        if($this->getCorrectorIdFromContext() !== null) {
            throw new ilException("Operation not permitted");
        }
        $select = $this->buildRepositorySelect();

        if($select->hasSelected()) {
            $ref_id = $select->getSelectedId();

            $criteria = $this->object_repo->getRatingCriteriaByObjectId(\ilObject2::_lookupObjectId($ref_id), null);
            $this->object_repo->deleteRatingCriterionByObjectIdAndCorrectorId($this->object->getId(), null);

            foreach ($criteria as $criterion) {
                $new = clone $criterion;
                $new->setId(0);
                $new->setCorrectorId(null);
                $new->setObjectId($this->object->getId());
                $this->object_repo->save($new);
            }

            $this->tpl->setOnScreenMessage("success", $this->plugin->txt('copy_criteria_successful'), true);
            $this->ctrl->redirect($this, "showItems");
        } else {
            $select->showAsync();
        }
    }

    protected function buildRepositorySelect() : RepositorySelectModal
    {
        return $this->localDI->getUIFactory()->tree()->repositorySelect(
            $this->object->getRefId(),
            $this->plugin->txt("copy_criteria"),
            [$this, "listCriterion"],
            $this->ctrl->getLinkTarget($this, 'copyCriteria', null, true)
        )->setPermission("maintain_task")
         ->setMessage($this->plugin->txt('copy_criteria_info'));
    }

    public function listCriterion(int $ref_id) : Component
    {
        $this->copy_context = $ref_id;

        $table = $this->table_factory->dataTable("copy_criteria", $this);
        $table->setAdditionalParameter($this->setSmallView());

        return $table->getTable();
    }

}
