<?php

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\RatingCriterion;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\Factory as CustomFactory;
use ILIAS\UI\Implementation\Component\Signal;
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
    private CustomFactory $custom_factory;
    private ObjectRepository $object_repo;
    private CorrectorRepository $corrector_repo;
    private \ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory $table_factory;
    private ?int $copy_context = null;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->post = $this->dic->http()->wrapper()->post();
        $this->custom_factory = $this->localDI->getUIFactory();
        $this->object_repo = $this->localDI->getObjectRepo();
        $this->corrector_repo = $this->localDI->getCorrectorRepo();
        $this->table_factory = $this->localDI->getTableFactory();
    }

    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showItems');
        switch ($cmd) {
            case 'showItems':
            case 'deleteItems':
            case 'copyCriteria':
                $this->$cmd();
                break;
            case 'copyItems':
            case 'publishRatingCriterion':
            case 'previewItemsAsync':
                if($this->allowCopyInContext()) {
                    $this->$cmd();
                } else {
                    $this->tpl->setContent('not allowed command: ' . $cmd);
                }
                break;
            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * @return RatingCriterion[]
     */
    abstract protected function getRatingCriterionFromContext():array;
    abstract protected function getRatingCriterionModelFromContext(): RatingCriterion;
    abstract protected function getCorrectorIdFromContext(): ?int;
    abstract protected function allowCopyInContext(): bool;

    public function showItems()
    {
        $table = $this->table_factory->dataTable("criteria", $this);
        $table->executeAction();
        $table->setTitle($this->plugin->txt("criteria"));

        $table->addActionToToolbar($this->toolbar, $table->getActionByName("add_criteria"), true);
        if($this->getCorrectorIdFromContext() === null) {
            $select = $this->buildRepositorySelect();
            list($btn, $modal) = $select->getToolbarComponents($this->plugin->txt("copy_criteria"));
            $this->addModal($modal);
            $this->toolbar->addComponent($btn);
        }

        $this->addCopyToolbar();

        $this->setContent($this->renderer->render($table->getComponents()));
    }

    protected function buildItemTitle(RatingCriterion $item): string
    {
        return $item->getTitle() . " | " . $this->plugin->txt("criteria_max_point") . ": " . $item->getPoints();
    }

    public function deleteItems()
    {
        $criteria_ids = array_map(fn (RatingCriterion $x) => $x->getId(), $this->getRatingCriterionFromContext());
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
        if(isset($param["publish"]) && $this->allowCopyInContext() && $this->getCorrectorIdFromContext() !== null) {
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

    protected function addCopyToolbar()
    {
        if($this->allowCopyInContext() && $this->getCorrectorIdFromContext() !== null) {
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
                $this->toolbar->addSeparator();
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
            $this->ctrl->setParameter($this, "publish", "on");
            $on_action = $this->ctrl->getFormAction($this, "publishRatingCriterion");

            $this->ctrl->setParameter($this, "publish", "off");
            $off_action = $this->ctrl->getFormAction($this, "publishRatingCriterion");

            $this->ctrl->clearParameters($this);
            $this->toolbar->addSeparator();
            $this->toolbar->addText($this->plugin->txt("publish_rating_criterion"));
            $this->toolbar->addComponent(
                $this->uiFactory->button()->toggle("", $on_action, $off_action, $corrector->isCriterionCopyEnabled())
            );
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
            $this->createAction()
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
        foreach ($this->getRatingCriterionFromContext() as $object) {
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
        return new CriteriaItem($item->getId(), $item->getTitle(), $item->getDescription(), $item->getPoints());
    }

    protected function createAction() : Action\Form
    {
        return $this->table_factory->action()->form(
            "add_criteria",
            $this->plugin->txt('criteria_add'),
            $this->lng->txt('save'),
            fn (CriteriaItem $item) => $this->buildFields($item),
            fn (CriteriaItem $item, array $data) => $this->save($item, $data),
            fn (CriteriaItem $x) => true,
            Action\Type::Global
        );
    }

    protected function editAction() : Action\Form
    {
        return $this->table_factory->action()->form(
            "edit_criteria",
            $this->lng->txt('edit'),
            $this->lng->txt('save'),
            fn (CriteriaItem $item) => $this->buildFields($item),
            fn (CriteriaItem $item, array $data) => $this->save($item, $data),
            fn (CriteriaItem $x) => true,
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
            fn (CriteriaItem $x) => true,
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

    protected function save(CriteriaItem $item, array $data) : void
    {
        if ($item->getId() === 0) {
            $criterion = $this->getRatingCriterionModelFromContext();
        } else {
            $criterion = $this->object_repo->getRatingCriterionById($item->getId());
        }
        $criterion->setTitle($data['title'])
                  ->setDescription($data['description'])
                  ->setPoints($data['points']);

        $this->object_repo->save($criterion);
        $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
    }

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
