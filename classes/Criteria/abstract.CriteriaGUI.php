<?php

namespace ILIAS\Plugin\LongEssayAssessment\Criteria;

use Edutiek\AssessmentService\Task\Data\CriteriaMode;
use Edutiek\AssessmentService\Task\Data\RatingCriterion;
use Generator;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTable;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\SmallView;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;

abstract class CriteriaGUI extends BaseGUI implements DataTableParent
{
    use SmallView;
    use ConfirmationIds;

    protected \Edutiek\AssessmentService\System\Entity\FullService $entity_service;
    protected \Edutiek\AssessmentService\Task\CorrectionSettings\FullService $correction_settings_service;
    protected \Edutiek\AssessmentService\Task\Data\CorrectionSettings $correction_settings;
    protected \Edutiek\AssessmentService\Task\AssessmentStatus\FullService $assessment_status;
    protected \Edutiek\AssessmentService\Task\RatingCriterion\FullService $criterion_service;
    protected \ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory $table_factory;
    #private \Edutiek\AssessmentService\Assessment\Corrector\Service $corrector_service;

    protected ?int $copy_context = null;
    private ?bool $has_authorized_corrections = null;

    public function __construct(BaseObjectData $objectGUI)
    {
        parent::__construct($objectGUI);

        $this->table_factory = $this->plugin_ui_factory->table();
        $this->correction_settings_service = $this->task_api->correctionSettings();
        $this->correction_settings = $this->correction_settings_service->get();
        $this->assessment_status = $this->task_api->assessmentStatus();
        $this->entity_service = $this->system_api->entity();

        #$this->corrector_service = $this->assessment_api->corrector();
        #$this->corrector_pref_service = $this->essay_task_api->

    }

    public function executeCommand()
    {
        $this->initTools(true, false);
        $this->criterion_service = $this->task_api->ratingCriterion($this->task_info->getId());

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
    abstract protected function getRatingCriteriaFromContext(): array;
    abstract protected function getRatingCriterionModelFromContext(): RatingCriterion;
    abstract protected function getCorrectorIdFromContext(): ?int;
    abstract protected function allowChangeInContext(): bool;
    abstract protected function allowShareInContext(): bool;
    abstract protected function allowSettingsInContext(): bool;

    protected function hasAuthorizedCorrections(): bool
    {
        return $this->has_authorized_corrections ??= $this->assessment_status->hasAuthorizedSummaries(
            $this->getCorrectorIdFromContext()
        );
    }

    public function table(): DataTable
    {
        $table = $this->plugin_ui_factory->table()->dataTable("criteria", $this);
        $table->executeAction();
        $table->setTitle($this->plugin->txt("criteria"));
        return $table;
    }

    abstract public function showItems();

    protected function buildItemTitle(RatingCriterion $item): string
    {
        return $item->getTitle() . " | "
            . ($item->getGeneral() ? $this->plugin->txt('criterion_type_general') : $this->plugin->txt('criterion_type_comment')) . " | "
            . $this->plugin->txt("criteria_max_point") . ": " . $item->getPoints();
    }

    public function deleteItems()
    {
        $delete_ids = $this->confirmationIds();
        $success = false;

        if (!empty($delete_ids) !== null) {
            foreach ($delete_ids as $id) {
                $rating_criterion = $this->criterion_service->one($id);
                if ($rating_criterion !== null) {
                    $this->criterion_service->delete($rating_criterion);
                    $success = true;
                }
            }
        }

        if ($success) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("delete_criteria_successful"), true);
        } else {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("delete_criteria_failure"), true);
        }
        $this->ctrl->clearParameters($this);
        $this->ctrl->redirect($this, "showItems");
    }

    public function getColumnMapping(Item $item, ?array $additional_parameters): array
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

    public function getColumns(?array $additional_parameters): array
    {
        $tf = $this->ui_factory->table();
        $small_view = $this->smallView($additional_parameters);
        $sortable = !$small_view;

        return [
            "title" => $tf->column()->text($this->plugin->txt('title'))->withIsSortable($sortable),
            "description" => $tf->column()->text($this->plugin->txt('description'))->withIsSortable($sortable),
            "general" => $tf->column()->boolean($this->plugin->txt('criterion_type'), $this->plugin->txt('criterion_type_general'), $this->plugin->txt('criterion_type_comment'))->withIsSortable($sortable),
            "points" => $tf->column()->number($this->plugin->txt('criteria_max_point'))->withIsSortable($sortable),
        ];
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        $items = iterator_to_array($this->getTableItems(null, $filter_data));
        return count($items);
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null): Generator
    {
        if ($this->copy_context !== null) {
            //$obj_id = \ilObject2::_lookupObjectId($this->copy_context);
            //check ref access_rights
            foreach ($this->criterion_service->allByCorrectorId($this->getCorrectorIdFromContext()) as $object) {
                yield $this->tableItemFromData($object);
            }
            return;
        }
        if ($ids === [0]) {
            yield $this->tableItemFromData($this->getRatingCriterionModelFromContext());
            return;
        }
        foreach ($this->getRatingCriteriaFromContext() as $object) {
            if (empty($ids) || in_array($object->getId(), $ids)) {
                yield $this->tableItemFromData($object);
            }
        }
    }

    public function getTableItem(int $id): Item
    {
        $item = $this->criterion_service->one($id);
        if ($item->getTaskId() !== $this->task_info->getId() && $item->getCorrectorId() !== $this->getCorrectorIdFromContext()) {
            throw new \Exception("Item is not allowed in this context.");
        }
        return $this->tableItemFromData($item);
    }

    protected function tableItemFromData(RatingCriterion $item): CriteriaItem
    {
        return new CriteriaItem($item->getId(), $item->getTitle(), $item->getDescription(), (int) $item->getGeneral(), $item->getPoints());
    }

    protected function createAction(): Action\Form
    {
        return $this->table_factory->action()->form(
            "add_criteria",
            $this->plugin->txt('criteria_add'),
            $this->lng->txt('save'),
            fn(CriteriaItem $item) => $this->buildFields($item),
            fn(CriteriaItem $item, array $data) => $this->save($item, $data),
            fn(CriteriaItem $x) => $this->allowChangeInContext(),
            Action\Type::Global
        );
    }

    protected function editAction(): Action\Form
    {
        return $this->table_factory->action()->form(
            "edit_criteria",
            $this->lng->txt('edit'),
            $this->lng->txt('save'),
            fn(CriteriaItem $item) => $this->buildFields($item),
            fn(CriteriaItem $item, array $data) => $this->save($item, $data),
            fn(CriteriaItem $x) => $this->allowChangeInContext(),
            Action\Type::Single
        );
    }

    protected function deleteAction(): Action\Confirmation
    {
        return $this->table_factory->action()->confirmation(
            "delete_criteria",
            $this->lng->txt('delete'),
            $this->lng->txt('delete'),
            $this->plugin->txt('delete_criteria_confirmation'),
            $this->ctrl->getFormAction($this, 'deleteItems'),
            fn(CriteriaItem $item) => $item->getTitle(),
            fn(CriteriaItem $x) => $this->allowChangeInContext(),
            Action\Type::Standard
        );
    }

    protected function buildFields(CriteriaItem $item): array
    {
        return [
            'title' => $this->ui_factory->input()->field()->text($this->lng->txt("title"))
                                        ->withAdditionalTransformation($this->refinery->string()->hasMinLength(1))
                                        ->withRequired(true)
                                        ->withValue($item->getTitle()),
            'description' => $this->ui_factory->input()->field()->textarea($this->lng->txt("description"))
                                              ->withValue($item->getDescription() !== null ? $item->getDescription() : ""),
            'is_general' => $this->ui_factory->input()->field()->radio(
                $this->plugin->txt('criterion_type')
            )
                                            ->withOption('1', $this->plugin->txt('criterion_type_general'), $this->plugin->txt('criterion_type_general_info'))
                                            ->withOption('0', $this->plugin->txt('criterion_type_comment'), $this->plugin->txt('criterion_type_comment_info'))
                                            ->withValue($item->isGeneral() ? '1' : '0')
                                            ->withDisabled(!empty($item->getId())),
            'points' => $this->plugin_ui_factory->field()->numeric(
                $this->plugin->txt('criteria_max_point'),
                $this->plugin->txt('criteria_max_point_desc')
            )
                                             ->withAdditionalTransformation($this->refinery->kindlyTo()->int())
                                             ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(0))
                                             ->withRequired(true)
                                             ->withValue($item->getMaxPoints())
        ];
    }

    protected function save(CriteriaItem $item, array $data): void
    {
        if ($item->getId() === 0) {
            $criterion = $this->getRatingCriterionModelFromContext();
        } else {
            $criterion = $this->criterion_service->one($item->getId());
        }
        if (empty($criterion->getId())) {
            $criterion->setGeneral($data['is_general']);
        }
        $criterion->setTitle($data['title'])
                  ->setDescription($data['description'])
                  ->setPoints($data['points']);

        $this->entity_service->secure($criterion, RatingCriterion::class);
        $this->criterion_service->save($criterion);
        $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
    }
}
