<?php

namespace ILIAS\Plugin\LongEssayAssessment\GUI\Correction;

use Edutiek\AssessmentService\Assessment\Data\CorrectionStatus;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Test\Participants\TableAction;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\FilterParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use Edutiek\AssessmentService\Task\Data\ResourceType;
use Edutiek\AssessmentService\Task\Data\Settings;
use Edutiek\AssessmentService\Task\Settings\FullService as SettingsService;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\GradingStatus;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaService;
use Edutiek\AssessmentService\Assessment\Writer\FullService as WriterService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use Edutiek\AssessmentService\EssayTask\Essay\ClientService as EssayService;
use Edutiek\AssessmentService\Task\AssessmentStatus\FullService as AssessmentStatus;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Task\CorrectorSummary\FullService as SummaryService;
use Edutiek\AssessmentService\Task\CorrectorAssignments\FullService as CorrectorAssignmentsService;
use Edutiek\AssessmentService\Assessment\Corrector\FullService as CorrectorService;
use Edutiek\AssessmentService\Assessment\Data\Location;
use Edutiek\AssessmentService\Assessment\AssessmentGrading\ReadService as GradingService;
use Edutiek\AssessmentService\Assessment\Data\CorrectionSettings;
use ILIAS\UI\Implementation\Component\Modal\RoundTrip;
use Edutiek\AssessmentService\System\Data\UserData;
use ILIAS\Refinery\Transformation;
use Edutiek\AssessmentService\Task\CorrectionProcess\FullService as CorrectionProcess;
use Edutiek\AssessmentService\Assessment\Data\CombinedStatus;
use Edutiek\AssessmentService\Views\Data\Correction;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTable;
use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\HasColumns;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\HighligtedColumns;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\InitialVisibleColumns;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\HasFilterFields;

class CorrectionTableParent implements DataTableParent, FilterParent
{
    use ConfirmationIds;
    use HasColumns;
    use HighligtedColumns;
    use InitialVisibleColumns;
    use HasFilterFields;

    public const FILTER_YES = "1";
    public const FILTER_NO = "2";
    private ?array $location = null;
    private ?int $required_correctors = null;
    private \ilLanguage $lng;
    private \ilLongEssayAssessmentPlugin $plugin;
    private \ILIAS\UI\Factory $ui_factory;
    protected \ILIAS\Refinery\Factory $refinery;
    private \ilObjUser $user;
    private \ILIAS\Plugin\LongEssayAssessment\View\Data\CorrectionsViewRepo $corrections_view;
    /**
     * @var Action\Action[]
     */
    private array $actions = [];

    public function __construct(Container $dic, \ilLongEssayAssessmentPlugin $plugin, private array $ass_ids, private string $base_action)
    {
        $this->lng = $dic->language();
        $this->plugin = $plugin;
        $this->ui_factory = $dic->ui()->factory();
        $this->user = $dic->user();
        $this->corrections_view = $this->plugin->dic()->view()->corrections();
        $this->refinery = $dic->refinery();
    }

    public function getColumnMapping(
        CorrectionItem|\ILIAS\Plugin\LongEssayAssessment\UI\Table\Item $item,
        ?array $additional_parameters
    ): \ArrayAccess {
        $assessment_api = $this->plugin->dic()->assessment($item->getWriter()->getAssId(), $this->user->getId());
        $task_api = $this->plugin->dic()->task($item->getWriter()->getAssId(), $this->user->getId());
        $grading_service = $assessment_api->assessmentGrading();
        $ass_format = $assessment_api->format($assessment_api->orgaSettings()->get());
        $task_format = $task_api->format();

        return new CorrectionItemColumnMap(
            $this->lng,
            $this->plugin,
            $this->ui_factory,
            new \DateTimeZone($this->user->getTimeZone()),
            $grading_service,
            $ass_format,
            $task_format,
            $item
        );
    }

    public function getColumns(?array $additional_parameters): array
    {
        $cf = $this->ui_factory->table()->column();
        $cfp = $this->plugin->dic()->uiFactory()->table()->column();

        $df = new \ILIAS\Data\Factory();
        $date_without_seconds = $this->user->getDateTimeFormat();
        $date_with_seconds = $df->dateFormat()->amend($date_without_seconds)->colon()->seconds()->get();

        $corrections = $this->getRequiredCorrectors();

        $columns = [
            "image" => $cfp->image($this->lng->txt("image"))->withIsOptional(true)->withIsSortable(false),
            "name" => $cf->text($this->lng->txt("name"))->withIsOptional(false)->withIsSortable(true),
            "login" => $cf->text($this->lng->txt("login"))->withIsOptional(false)->withIsSortable(true),
            "pseudonym" => $cf->text($this->plugin->txt("pseudonym"))->withIsOptional(false)->withIsSortable(true),
            "location" => $cf->text($this->plugin->txt("location"))->withIsOptional(true)->withIsSortable(true),
            "assessment" => $cf->text($this->plugin->txt("assessment"))->withIsOptional(true)->withIsSortable(true),
            "task" => $cf->text($this->plugin->txt("task"))->withIsOptional(true)->withIsSortable(true),
            "status" => $cf->status($this->plugin->txt("status"))->withIsOptional(true)->withIsSortable(true),
            "writing_last_save" => $cfp->nullableDate(
                $this->plugin->txt("writing_last_save"),
                $date_with_seconds
            )->withIsOptional(true)->withIsSortable(true),
            "word_count" => $cf->number($this->plugin->txt('word_count'))->withIsOptional(true)->withIsSortable(true),
            "pdf_version" => $cf->boolean(
                $this->plugin->txt("pdf_version"),
                $this->lng->txt("yes"),
                $this->lng->txt("no")
            )->withIsOptional(true)->withIsSortable(true)
        ];

        foreach (range(0, $corrections - 1) as $p) {
            if ($corrections == 1) {
                $cor = $this->plugin->txt("assignment_pos_single");
            } else {
                switch ($p) {
                    case 0:
                        $cor = $this->plugin->txt("grading_pos_first");
                        break;
                    case 1:
                        $cor = $this->plugin->txt("grading_pos_second");
                        break;
                    default:
                        $cor = $this->plugin->txt("assignment_pos_other");
                        break;
                }
            }
            $cor = $cor . " ";
            $columns += [
                "corr_{$p}" => $cf->text($cor)->withIsOptional(true)->withIsSortable(false),
                "corr_{$p}_name" => $cf->text($cor . $this->lng->txt("name"))->withIsOptional(true)->withIsSortable(true),
                "corr_{$p}_status" => $cf->status($cor . $this->plugin->txt("status"))->withIsOptional(true)->withIsSortable(true),
                "corr_{$p}_points" => $cfp->nullableNumber($cor . $this->plugin->txt("points"))->withIsOptional(true)->withIsSortable(true),
            ];

            $columns["corr_{$p}_grade"] = $cf->text($cor . $this->lng->txt("grade"))->withIsOptional(true)->withIsSortable(true); // Should be disabled for multi-task
            $columns["corr_{$p}_authorized"] = $cf->boolean(
                $cor . $this->plugin->txt("grading_authorized"),
                $this->lng->txt('yes'),
                $this->lng->txt('no')
            )->withIsOptional(true)->withIsSortable(true);
        }

        $columns += [
            "result" => $cf->text($this->plugin->txt("result"))->withIsOptional(true)->withIsSortable(false),
            "points" => $cfp->nullableNumber($this->plugin->txt("final_points"))->withIsOptional(true)->withIsSortable(true),
            "grade" => $cf->text($this->plugin->txt("final_grade"))->withIsOptional(true)->withIsSortable(true),
            "finalized" => $cfp->nullableDate(
                $this->plugin->txt("finalized_at"),
                $date_without_seconds
            )->withIsOptional(true)->withIsSortable(true),
            "finalized_from" => $cf->text($this->plugin->txt("finalized_from"))->withIsOptional(true)->withIsSortable(true),
        ];

        if (!empty($this->getHasColumns())) {
            $columns = $this->filterColumns($columns);
        }
        if (!empty($this->getInitialVisibleColumns())) {
            $columns = $this->setInitialVisible($columns);
        }
        if (!empty($this->getHighlightedColumns())) {
            $columns = $this->setHighlighted($columns);
        }

        return $columns;
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        return $this->corrections_view->count($filter_data);
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null): \Generator
    {
        $filter = ['ass_id' => $this->ass_ids];

        if (!empty($filter_data)) {
            $filter = array_merge($filter, $filter_data);
        }
        if (!empty($ids)) {
            $filter['id'] = $ids;
        }

        foreach ($this->corrections_view->some($filter) as $view) {
            yield new CorrectionItem(
                $view->getWriter()->getId(),
                $view->getWriter(),
                $view->getWriterData(),
                $view->getWriterDisplay(),
                $view->getLocation(),
                $view->getEssay(),
                array_map(fn(Correction $c) => $c->getCorrectorSummary(), $view->getCorrections()),
                array_map(fn(Correction $c) => $c->getCorrectorData(), $view->getCorrections()),
                $view->getFinalizedByData(),
                $view->getAuthorizedByData(),
                $view->getExcludedByData(),
                $view->getTask(),
                $view->getAssessmentProperties(),
            );
        }
    }

    public function getTableItem(int $id): \ILIAS\Plugin\LongEssayAssessment\UI\Table\Item
    {
        $corrections_view = $this->plugin->dic()->view()->corrections();
        $view = $corrections_view->some(['id' => $id, 'ass_id' => $this->ass_ids]);
        $view = empty($view) ? null : $view[0];

        if ($view === null) {
            throw new \Exception("Writer with id $id not found");
        }

        return new CorrectionItem(
            $view->getWriter()->getId(),
            $view->getWriter(),
            $view->getWriterData(),
            $view->getWriterDisplay(),
            $view->getLocation(),
            $view->getEssay(),
            array_map(fn(Correction $c) => $c->getCorrectorSummary(), $view->getCorrections()),
            array_map(fn(Correction $c) => $c->getCorrectorData(), $view->getCorrections()),
            $view->getFinalizedByData(),
            $view->getAuthorizedByData(),
            $view->getExcludedByData(),
            $view->getTask(),
            $view->getAssessmentProperties(),
        );
    }

    public function getFilterInputs(): array
    {
        $status = [
            (string) CombinedStatus::WRITING_EXCLUDED->value => $this->plugin->txt(
                CombinedStatus::WRITING_EXCLUDED->langVar()
            ),
            (string) CombinedStatus::WRITING_NOT_STARTED->value => $this->plugin->txt(
                CombinedStatus::WRITING_NOT_STARTED->langVar()
            ),
            (string) CombinedStatus::WRITING_STARTED->value => $this->plugin->txt(
                CombinedStatus::WRITING_STARTED->langVar()
            ),
            (string) CombinedStatus::WRITING_AUTHORIZED->value => $this->plugin->txt(
                CombinedStatus::WRITING_AUTHORIZED->langVar()
            ),
            (string) CombinedStatus::OPEN->value => $this->plugin->txt(CombinedStatus::OPEN->langVar()),
            (string) CombinedStatus::APPROXIMATION->value => $this->plugin->txt(
                CombinedStatus::APPROXIMATION->langVar()
            ),
            (string) CombinedStatus::CONSULTING->value => $this->plugin->txt(CombinedStatus::CONSULTING->langVar()),
            (string) CombinedStatus::STITCH_NEEDED->value => $this->plugin->txt(
                CombinedStatus::STITCH_NEEDED->langVar()
            ),
            (string) CombinedStatus::FINALIZED->value => $this->plugin->txt(CombinedStatus::FINALIZED->langVar()),
        ];
        $locations = [];
        foreach ($this->getLocations() as $location) {
            $locations[$location->getId()] = $location->getTitle();
        }

        $filter = [
            "name" => $this->ui_factory->input()->field()->text($this->plugin->txt("participants")),
            "task" => $this->ui_factory->input()->field()->multiselect($this->plugin->txt("task"), $this->getTasks()),
            "location" => $this->ui_factory->input()->field()->multiselect($this->plugin->txt("locations"), $locations),
            "min_words" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("min_word_count"))
                                            ->withAdditionalTransformation(
                                                $this->refinery->int()->isGreaterThanOrEqual(0)
                                            ),
            "max_words" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("max_word_count"))
                                            ->withAdditionalTransformation(
                                                $this->refinery->int()->isGreaterThanOrEqual(1)
                                            ),
            "status" => $this->ui_factory->input()->field()->multiSelect($this->plugin->txt("status"), $status),
            "assigned" => $this->ui_factory->input()->field()->select(
                $this->plugin->txt("filter_assigned"),
                [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
            ),
            "pdf_version" => $this->ui_factory->input()->field()->select(
                $this->plugin->txt("filter_pdf_version"),
                [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
            )
        ];

        if (!empty($this->getHasFilterFields())) {
            $filter = $this->filterFilterFields($filter);
        }


        return $filter;

    }

    public function getFilterInputActivation(): array
    {
        $act = ["name" => true, "task" => true, "location" => true, "min_words" => true, "max_words" => true, "status" => true, "assigned" => true, "pdf_version" => true];

        if (!empty($this->getHasFilterFields())) {
            $act = $this->filterFilterFields($act);
        }
        return $act;
    }

    public function getFilterBaseAction(): string
    {
        return $this->base_action;
    }

    protected function getLocations(): array
    {
        return $this->location ??= $this->corrections_view->locations($this->ass_ids);
    }

    protected function getTasks(): array
    {
        $tasks = [];
        $assessments = $this->corrections_view->assessments($this->ass_ids);

        foreach ($this->corrections_view->tasks($this->ass_ids) as $task) {
            $tasks[$task->getTaskId()] = ($assessments[$task->getAssId()] ?? "") . " &raquo; " . $task->getTitle();
        }


        return $tasks;
    }

    protected function getRequiredCorrectors(): int
    {
        return $this->required_correctors ??= $this->corrections_view->requiredCorrectors($this->ass_ids);
    }

    public function getTableActions(): array
    {
        return $this->actions;
    }

    /**
     * @param Action\Action[] $actions
     * @return void
     */
    public function setTableActions(array $actions): self
    {
        $this->actions = $actions;
        return $this;
    }

    protected function hasColumns(): array
    {
        return [];
    }

    protected function highlightedColumns(): array
    {
        return [];
    }

    protected function initialVisibleColumns(): array
    {
        return [];
    }

    protected function hasFilterFields(): array
    {
        return [];
    }
}
