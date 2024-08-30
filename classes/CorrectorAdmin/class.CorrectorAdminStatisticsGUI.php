<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use ILIAS\UI\Component\Input\Container\Filter;
use Edutiek\LongEssayAssessmentService\Corrector\Service;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorContext;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\CorrectorAdminListGUI;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\CorrectorListGUI;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ilFileDelivery;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorAdminStatisticsGUI extends StatisticsGUI
{
    protected CorrectorRepository $corrector_repo;
    protected array $correctors = [];

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->corrector_repo = $this->localDI->getCorrectorRepo();
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
            $this->loadCorrectorForObject($obj["obj_id"]);
        }

        $filter_gui = $this->buildFilter();
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? ['context' => null, 'correctors' => null];

        $sections = array_filter(
            $this->objects,
            fn ($x) => $filter_data['context'] !== null
                ? in_array($x['obj_id'], $filter_data['context'])
                : (int)$x['obj_id'] === $this->object->getId()
        );
        $data = [];

        if($filter_data['context'] !== null && count($filter_data['context']) > 1) {
            $data = $this->getItemDataOverall();
        }

        foreach($sections as $obj) {
            $rows = $this->getItemDataForObject($obj["obj_id"], $filter_data["correctors"]);

            if(count($rows) === 2) {
                continue;//Don't show object if no correctors are shown
            }

            if($filter_data['context'] !== null && count($filter_data['context']) > 1) {
                $data[] = $this->localDI->getUIFactory()->statistic()->statisticSection($obj["title"]);
            }
            foreach($rows as $row) {
                $data[] = $row['item'];
            }
        }



        $ptable = $this->localDI->getUIFactory()->statistic()->extendableStatisticGroup($this->plugin->txt('statistic'), $data);
        $this->tpl->setContent($this->renderer->render([$filter_gui, $ptable]));
    }

    protected function exportCSV() : void
    {
        $this->loadObjectsInContext();

        foreach($this->objects as $obj) {
            $this->loadDataForObject($obj["obj_id"]);
            $this->loadCorrectorForObject($obj["obj_id"]);
        }
        $filter_gui = $this->buildFilter() ;
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? ['context' => null, 'finalized' => null];

        $data = [];
        $sections = array_filter(
            $this->objects,
            fn ($x) => $filter_data['context'] !== null
                ? in_array($x['obj_id'], $filter_data['context'])
                : (int)$x['obj_id'] === $this->object->getId()
        );
        foreach($sections as $obj) {
            $data[] = $this->getItemDataForObject($obj["obj_id"], $filter_data["correctors"], true);
        }

        $filename = ilFileDelivery::returnASCIIFilename($this->plugin->txt('export_statistics_corrector_file')). '.csv';
        ilFileDelivery::deliverFileAttached($this->buildCSV(
            array_merge(...$data),
            $this->plugin->txt('correction_count'),
            $this->plugin->txt('correction_final'),
            true
        ), $filename, 'text/csv', false);
    }

    protected function buildFilter() : Filter\Standard
    {
        $context = [];
        $corr = [];

        foreach($this->objects as $node) {
            $context[$node["obj_id"]] = $node["title"];
        }

        foreach(array_merge(...$this->correctors) as $corrector) {
            if(!isset($corr[$corrector->getUserId()])) {
                $corr[$corrector->getUserId()] = \ilobjUser::_lookupFullname($corrector->getUserId());
            }
        }

        $base_action = $this->ctrl->getFormAction($this, 'showStartPage');
        $filter_gui = $this->ui_service->filter()->standard("xlas_statistics", $base_action, [
            "context" => $this->uiFactory->input()->field()->multiSelect($this->plugin->txt("statistic_context_filter"), $context)
                                                           ->withAdditionalOnLoadCode($this->localDI->getUIService()->checkAllInMultiselectFilter())
                                                           ->withValue([$this->object->getId()]),
            "correctors" => $this->uiFactory->input()->field()->multiSelect($this->plugin->txt("correctors"), $corr)
                                                              ->withAdditionalOnLoadCode($this->localDI->getUIService()->checkAllInMultiselectFilter())
        ], [true, true], true, true);
        return $filter_gui;
    }

    private function getItemDataForObject($obj_id, $corrector_filter = null) : array
    {
        $corrector_service = $this->localDI->getCorrectorAdminService($obj_id);
        $summary_statistics = $corrector_service->gradeStatistics($this->summaries[$obj_id], $this->grade_level[$obj_id]);
        $essay_statistics = $corrector_service->gradeStatistics($this->essays[$obj_id], $this->grade_level[$obj_id]);

        $rows =[
            ["item" => $this->createStatisticItem($this->plugin->txt('essay_correction_finlized'), $essay_statistics, true)
                 ->withGrades($this->getGradeStatisticOverAll($essay_statistics))],
            ["item" =>$this->createStatisticItem($this->plugin->txt('corrections_all'), $summary_statistics, false)
                 ->withGrades($this->getGradeStatisticOverAll($summary_statistics))]
        ];

        foreach($this->correctors[$obj_id] as $corrector) {

            if($corrector_filter !== null && !in_array($corrector->getUserId(), $corrector_filter)) {
                continue;
            }
            $corrector_id = $corrector->getId();
            $corrector_summaries = array_filter($this->summaries[$obj_id], fn (CorrectorSummary $x) => ($x->getCorrectorId() === $corrector_id));
            $statistics = $corrector_service->gradeStatistics($corrector_summaries, $this->grade_level[$obj_id]);

            $statistic_item = $this->createStatisticItem($this->usernames[$obj_id][$corrector->getUserId()], $statistics, false)
                                            ->withGrades($this->getGradeStatisticOverAll($statistics));

            $rows[] = ['usr_id' => $corrector->getUserId(),
                       'obj_id' => $obj_id,
                       'title' => $this->usernames[$obj_id][$corrector->getUserId()], 'count' => $this->plugin->txt('correction_count'),
                       'final' => $this->plugin->txt('correction_final'), 'statistic' => $statistics, 'grade_statistics' => $this->getGradeStatisticOverAll($statistics),
                       'item' => $statistic_item];
        }
        return $rows;
    }

    private function getItemDataOverall() : array
    {
        $grade_statistics = function (array $statistic) {
            $grade_statistic = [];
            foreach(array_merge(...$this->grade_level) as $level) {
                if(!empty($level->getCode())) {
                    if(isset($grade_statistic[$level->getCode()])) {
                        $grade_statistic[$level->getCode()] += $statistic[CorrectorAdminService::STATISTIC_COUNT_BY_LEVEL][$level->getId()] ?? 0;
                    } else {
                        $grade_statistic[$level->getCode()] = $statistic[CorrectorAdminService::STATISTIC_COUNT_BY_LEVEL][$level->getId()] ?? 0;
                    }
                }
            }
            return $grade_statistic;
        };
        $essay_statistics = $this->getStatistic(array_merge(...$this->essays));
        $summary_statistics = $this->getStatistic(array_merge(...$this->summaries));

        return [
            $this->localDI->getUIFactory()->statistic()->statisticSection($this->plugin->txt("total_statistic")),
            $this->createStatisticItem($this->plugin->txt('essay_correction_finlized'), $essay_statistics, true)
                                   ->withGrades($grade_statistics($essay_statistics)),
            $this->createStatisticItem($this->plugin->txt('corrections_all'), $summary_statistics, false)
                 ->withGrades($grade_statistics($summary_statistics)),
        ];
    }

    protected function loadCorrectorForObject($obj_id) : void
    {
        $this->correctors[$obj_id] = $this->corrector_repo->getCorrectorsByTaskId($obj_id);
        $this->usernames[$obj_id] = \ilUserUtil::getNamePresentation(array_unique(array_map(fn (Corrector $x) => $x->getUserId(), $this->correctors[$obj_id])), false, false, "", true);
    }
}
