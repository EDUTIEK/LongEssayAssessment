<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

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
use \ilUtil;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Object\IliasContext;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorAdminStatisticsGUI extends BaseGUI
{
    private \ilAccessHandler $access;
    private \ilUIService $ui_service;
    private CorrectorRepository $corrector_repo;
    private EssayRepository $essay_repo;
    private ObjectRepository $object_repo;
    private CorrectorAdminService $service;
    private array $grade_level = [];
    private array $correctors = [];
    private array $summaries = [];
    private array $essays = [];
    private array $usernames = [];
    private array $objects = [];

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->ui_service = $this->dic->uiService();
        $this->corrector_repo = $this->localDI->getCorrectorRepo();
        $this->essay_repo = $this->localDI->getEssayRepo();
        $this->object_repo = $this->localDI->getObjectRepo();
        $this->access = $this->dic->access();
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
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }
    }

    protected function showStartPage()
    {
        $this->loadObjectsInContext();

        foreach($this->objects as $obj) {
            $this->loadDataForObject($obj["obj_id"]);
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
            $data = [$this->getItemDataOverall()];
        }

        foreach($sections as $obj) {
            $this->loadDataForObject($obj["obj_id"]);
            $rows = $this->getItemDataForObject($obj["obj_id"], $filter_data["correctors"]);
            if($filter_data['context'] !== null && count($filter_data['context']) > 1) {
                array_unshift($rows, ["title" => $obj["title"]]);
            }
            $data[] = $rows;
        }

        $ptable = $this->uiFactory->table()->presentation(
            $this->plugin->txt('statistic'), //title
            [],
            function (PresentationRow $row, $record, $ui_factory, $environment) { //mapping-closure
                if(count($record) == 1) {
                    return [$this->uiFactory->divider()->horizontal()->withLabel("<h4>" . $record["title"] . "</h4>")];
                }

                $statistic = $record["statistic"];
                $properties = [];
                $fproperties = [];
                $properties[$record['count']] = (string)$statistic[CorrectorAdminService::STATISTIC_COUNT];
                $properties[$record['final']] = (string)$statistic[CorrectorAdminService::STATISTIC_FINAL];
                if($statistic[CorrectorAdminService::STATISTIC_NOT_ATTENDED] !== null) {
                    $properties[$this->plugin->txt('essay_not_attended')] = (string)$statistic[CorrectorAdminService::STATISTIC_NOT_ATTENDED];
                }
                $properties[$this->plugin->txt('essay_passed')] = (string)$statistic[CorrectorAdminService::STATISTIC_PASSED];
                $properties[$this->plugin->txt('essay_not_passed')] = (string)$statistic[CorrectorAdminService::STATISTIC_NOT_PASSED];

                if($statistic[CorrectorAdminService::STATISTIC_NOT_PASSED_QUOTA] !== null) {
                    $properties[$this->plugin->txt('essay_not_passed_quota')] = sprintf('%.2f', $statistic[CorrectorAdminService::STATISTIC_NOT_PASSED_QUOTA]);
                }

                if($statistic[CorrectorAdminService::STATISTIC_AVERAGE] !== null) {
                    $properties[$this->plugin->txt('essay_average_points')] = sprintf('%.2f', $statistic[CorrectorAdminService::STATISTIC_AVERAGE]);
                }

                foreach($record['grade_statistics'] as $key => $value) {
                    $fproperties[$key . " "/*Hack to ensure a string*/] = (string)$value;
                }

                return $row
                    ->withHeadline($record['title'])
                    ->withImportantFields($properties)
                    ->withContent($ui_factory->listing()->descriptive($properties))
                    ->withFurtherFieldsHeadline($this->plugin->txt('grade_distribution'))
                    ->withFurtherFields($fproperties);
            }
        );
        $this->tpl->setContent($this->renderer->render([$filter_gui, $ptable->withData(array_merge(...$data))]));
    }

    protected function buildFilter()
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
            "context" => $this->uiFactory->input()->field()->multiSelect($this->lng->txt("context"), $context)
                                         ->withValue([$this->object->getId()]),
            "correctors" => $this->uiFactory->input()->field()->multiSelect($this->plugin->txt("correctors"), $corr)
        ], [true, true], true, true);
        return $filter_gui;
    }

    private function loadObjectsInContext()
    {
        $objects = $this->object_services->iliasContext()->getAllEssaysInThisContext();
        $this->objects = array_filter($objects, fn ($object) => ($this->access->checkAccess("maintain_correctors", '', $object["ref_id"])));
    }

    private function loadDataForObject($obj_id)
    {
        $this->correctors[$obj_id] = $this->corrector_repo->getCorrectorsByTaskId($obj_id);
        $this->summaries[$obj_id] = $this->essay_repo->getCorrectorSummariesByTaskId($obj_id);
        $this->grade_level[$obj_id] = $this->object_repo->getGradeLevelsByObjectId($obj_id);
        $this->usernames[$obj_id] = \ilUserUtil::getNamePresentation(array_unique(array_map(fn (Corrector $x) => $x->getUserId(), $this->correctors[$obj_id])), false, false, "", true);
        $this->essays[$obj_id] = $this->essay_repo->getEssaysByTaskId($obj_id);
    }

    private function getItemDataForObject($obj_id, $corrector_filter = null) : array
    {
        $corrector_service = $this->localDI->getCorrectorAdminService($obj_id);
        $summary_statistics = $corrector_service->gradeStatistics($this->summaries[$obj_id]);
        $essay_statistics = $corrector_service->gradeStatistics($this->essays[$obj_id]);

        $grade_statistics = function (array $statistic) use ($obj_id) {
            $grade_statistics = [];
            foreach($this->grade_level[$obj_id] as $level) {
                $grade_statistics[$level->getGrade()] = $statistic[CorrectorAdminService::STATISTIC_COUNT_BY_LEVEL][$level->getId()] ?? 0;
            }
            return $grade_statistics;
        };

        $rows = [['title' => $this->plugin->txt('essay_correction_finlized'), 'count' => $this->plugin->txt('essay_count'),
                  'final' => $this->plugin->txt('essay_final'), 'statistic' => $essay_statistics, 'grade_statistics' => $grade_statistics($essay_statistics)],
                 ['title' => $this->plugin->txt('corrections_all') , 'count' => $this->plugin->txt('correction_count'),
                  'final' => $this->plugin->txt('correction_final'), 'statistic' => $summary_statistics, 'grade_statistics' => $grade_statistics($summary_statistics)]];

        foreach($this->correctors[$obj_id] as $corrector) {

            if($corrector_filter !== null && !in_array($corrector->getUserId(), $corrector_filter)) {
                continue;
            }
            $corrector_id = $corrector->getId();
            $corrector_summaries = array_filter($this->summaries[$obj_id], fn (CorrectorSummary $x) => ($x->getCorrectorId() === $corrector_id));
            $statistics = $corrector_service->gradeStatistics($corrector_summaries);
            $rows[] = ['title' => $this->usernames[$obj_id][$corrector->getUserId()], 'count' => $this->plugin->txt('correction_count'),
                       'final' => $this->plugin->txt('correction_final'), 'statistic' => $statistics, 'grade_statistics' => $grade_statistics($statistics)];
        }
        return $rows;
    }

    private function getItemDataOverall()
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
        $essay_statistics = $this->service->gradeStatistics(array_merge(...$this->essays));
        $summary_statistics = $this->service->gradeStatistics(array_merge(...$this->summaries));

        return [ ['title' => $this->plugin->txt("total_statistic")],
                 ['title' => $this->plugin->txt('essay_correction_finlized'), 'count' => $this->plugin->txt('essay_count'),
                  'final' => $this->plugin->txt('essay_final'), 'statistic' => $essay_statistics, 'grade_statistics' => $grade_statistics($essay_statistics)],
                 ['title' => $this->plugin->txt('corrections_all') , 'count' => $this->plugin->txt('correction_count'),
                  'final' => $this->plugin->txt('correction_final'), 'statistic' => $summary_statistics, 'grade_statistics' => $grade_statistics($summary_statistics)]];
    }
}
