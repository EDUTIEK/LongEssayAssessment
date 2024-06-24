<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use ILIAS\UI\Component\Input\Container\Filter;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay;
use ilFileDelivery;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminWriterStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorAdminWriterStatisticsGUI extends StatisticsGUI
{
    private \ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository $writer_repo;
    private array $writer = [];

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->writer_repo = $this->localDI->getWriterRepo();

    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                $cmd = $this->ctrl->getCmd('showStartPage');
                switch ($cmd) {
                    case 'showStartPage':
                    case 'exportCSV':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }
    }

    protected function showStartPage() : void
    {
        $this->toolbar->addComponent($this->uiFactory->button()->primary(
            $this->plugin->txt("export_statistics"),
            $this->ctrl->getLinkTarget($this, "exportCSV")
        ));

        $this->loadObjectsInContext();

        foreach($this->objects as $obj) {
            $this->loadDataForObject($obj["obj_id"]);
            $this->loadWriterForObject($obj["obj_id"]);
        }
        $this->loadUserdata();

        $filter_gui = $this->buildFilter() ;
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? ['context' => null, 'finalized' => null];

        $sections = array_filter(
            $this->objects,
            fn ($x) => $filter_data['context'] !== null
                ? in_array($x['obj_id'], $filter_data['context'])
                : (int)$x['obj_id'] === $this->object->getId()
        );
        $data = [];

        $essay_statistics = $this->service->gradeStatistics(array_filter(array_merge(...$this->essays), fn (Essay $x) => in_array($x->getTaskId(), $filter_data['context'] ?? [$this->object->getId()])));

        $data = [ ['title' => $this->plugin->txt("total_statistic")],
                  ['title' => $this->plugin->txt('essay_correction_finlized'), 'count' => $this->plugin->txt('essay_count'),
                  'final' => $this->plugin->txt('essay_final'), 'statistic' => $essay_statistics, 'grade_statistics' => $this->getGradeStatisticOverAll($essay_statistics)],
                  ['title' => $this->plugin->txt("participants")]];

        $data = array_merge($data, $this->getItemData($filter_data['context'] ?? [$this->object->getId()], (int)$filter_data['finalized'] ?? 1));

        $ptable = $this->buildPresentationTable();

        $this->tpl->setContent($this->renderer->render([$filter_gui, $ptable->withData($data)]));
    }

    protected function exportCSV() : void
    {
        $this->loadObjectsInContext();

        foreach($this->objects as $obj) {
            $this->loadDataForObject($obj["obj_id"]);
            $this->loadWriterForObject($obj["obj_id"]);
        }
        $filter_gui = $this->buildFilter() ;
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? ['context' => null, 'finalized' => null];

        $data = $this->getItemData($filter_data['context'] ?? [$this->object->getId()], (int)$filter_data['finalized'] ?? 1);

        $filename = ilFileDelivery::returnASCIIFilename($this->plugin->txt('export_statistics_writer_file')) . '.csv';
        ilFileDelivery::deliverFileAttached($this->buildCSV($data, false), $filename, 'text/csv', false);
    }

    protected function buildFilter() : Filter\Standard
    {
        $context = [];
        $corr = [];

        foreach($this->objects as $node) {
            $context[$node["obj_id"]] = $node["title"];
        }

        $base_action = $this->ctrl->getFormAction($this, 'showStartPage');
        $filter_gui = $this->ui_service->filter()->standard("xlas_statistics", $base_action, [
            "context" => $this->uiFactory->input()->field()->multiSelect($this->plugin->txt("objs_xlas"), $context)
                                         ->withAdditionalTransformation($this->refinery->to()->listOf($this->refinery->to()->int()))
                                         ->withValue([$this->object->getId()]),
            "finalized" => $this->uiFactory->input()->field()->numeric($this->plugin->txt("min_finalized_corrections"))
                                         ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0))
                                         ->withAdditionalTransformation($this->refinery->to()->int())
                                         ->withValue(1)
        ], [true, true], true, true);
        return $filter_gui;
    }

    private function getItemData(array $context_filter, int $min_finalized) : array
    {
        $writer_objs = array_filter(array_merge(...$this->writer), fn (Writer $x) => in_array($x->getTaskId(), $context_filter));

        $rows = [];
        foreach(array_unique(array_map(fn (Writer $x) => $x->getUserId(), $writer_objs)) as $wrtier_usr_id) {
            $writer = array_filter($writer_objs, fn (Writer $x) => $x->getUserId() === $wrtier_usr_id);
            $writer_ids = array_map(fn (Writer $x) => $x->getId(), $writer);
            $writer_essays = array_filter(array_merge(...$this->essays), fn (Essay $x) => in_array($x->getWriterId(), $writer_ids));
            $statistics = $this->service->gradeStatistics($writer_essays);

            if($statistics[CorrectorAdminService::STATISTIC_FINAL] < $min_finalized) {
                continue;
            }

            $pseudonym = array_map(fn (Writer $x) => $x->getPseudonym(), $writer);
            $rows[] = ['usr_id' => $wrtier_usr_id,
                       'title' => $this->common_services->userDataHelper()->getPresentation($wrtier_usr_id) ?? "",
                       'count' => $this->plugin->txt('essay_count'),
                       'final' => $this->plugin->txt('essay_final'), 'statistic' => $statistics,
                       'grade_statistics' => $this->getGradeStatisticOverAll($statistics),
                       'pseudonym' => $pseudonym];
        }
        return $rows;
    }

    private function getItemDataOverall() : array
    {
        $essay_statistics = $this->service->gradeStatistics(array_merge(...$this->essays));

        return [ ['title' => $this->plugin->txt("total_statistic")],
                 ['title' => $this->plugin->txt('essay_correction_finlized'), 'count' => $this->plugin->txt('essay_count'),
                  'final' => $this->plugin->txt('essay_final'), 'statistic' => $essay_statistics, 'grade_statistics' => $this->getGradeStatisticOverAll($essay_statistics)],];
    }
    protected function loadWriterForObject($obj_id) : void
    {
        $this->writer[$obj_id] = $this->writer_repo->getWritersByTaskId($obj_id);
    }

    protected function loadUserdata() : void
    {
        $this->common_services->userDataHelper()->preload(array_map(fn (Writer $x) => $x->getUserId(), array_merge(...$this->writer)));
    }
}
