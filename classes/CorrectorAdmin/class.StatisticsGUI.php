<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\Data\UUID\Factory as UUID;
use ilObjUser;
use ILIAS\UI\Component\Table\Presentation;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\GradeLevel;
use ILIAS\UI\Component\Listing\Descriptive;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\Statistic;

abstract class StatisticsGUI extends BaseGUI
{
    protected \ilAccessHandler $access;
    protected \ilUIService $ui_service;
    protected EssayRepository $essay_repo;
    protected ObjectRepository $object_repo;
    protected CorrectorAdminService $service;
    protected array $grade_level = [];
    protected array $summaries = [];
    protected array $essays = [];
    protected array $usernames = [];
    protected array $objects = [];

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->ui_service = $this->dic->uiService();
        $this->essay_repo = $this->localDI->getEssayRepo();
        $this->object_repo = $this->localDI->getObjectRepo();
        $this->access = $this->dic->access();
    }

    protected function buildCSV(array $records, string $count_title, string $finalized_title, bool $has_obj_id = true) : string
    {
        $grade_levels = array_unique(array_merge(...array_map(fn (array $x) => array_keys($x['grade_statistics']), $records)));

        if($has_obj_id) {
            $obj_titles = [];
            foreach($this->objects as $obj) {
                $obj_titles[(int)$obj['obj_id']] = $obj['title'];
            }
        }

        $csv = new \ilCSVWriter();
        $csv->setDoUTF8Decoding(true);
        $csv->setSeparator(';');
        $csv->setDelimiter('"');
        $csv->addColumn($this->lng->txt('login'));
        $csv->addColumn($this->lng->txt('firstname'));
        $csv->addColumn($this->lng->txt('lastname'));
        $csv->addColumn($this->lng->txt('matriculation'));
        if($has_obj_id) {
            $csv->addColumn($this->lng->txt('object'));
        }
        $csv->addColumn($count_title);
        $csv->addColumn($finalized_title);
        $csv->addColumn($this->plugin->txt('statistic_not_attended'));
        $csv->addColumn($this->plugin->txt('statistic_passed'));
        $csv->addColumn($this->plugin->txt('statistic_not_passed'));
        $csv->addColumn($this->plugin->txt('essay_not_passed_quota'));
        $csv->addColumn($this->plugin->txt('essay_average_points'));

        foreach($grade_levels as $value) {
            $csv->addColumn($value);
        }

        foreach($records as $record) {
            if(!isset($record["usr_id"])) {
                continue;
            }
            $csv->addRow();
            $user = new ilObjUser($record["usr_id"]);
            $statistic = $record["statistic"];

            $csv->addColumn($user->getLogin());
            $csv->addColumn($user->getFirstname());
            $csv->addColumn($user->getLastname());
            $csv->addColumn($user->getMatriculation());
            if($has_obj_id) {
                $csv->addColumn($obj_titles[(int)$record["obj_id"]]);
            }
            $csv->addColumn((string)$statistic[CorrectorAdminService::STATISTIC_COUNT]);
            $csv->addColumn((string)$statistic[CorrectorAdminService::STATISTIC_FINAL]);
            $csv->addColumn((string)($statistic[CorrectorAdminService::STATISTIC_NOT_ATTENDED] ?? 0));
            $csv->addColumn((string)$statistic[CorrectorAdminService::STATISTIC_PASSED]);
            $csv->addColumn((string)$statistic[CorrectorAdminService::STATISTIC_NOT_PASSED]);
            $csv->addColumn($statistic[CorrectorAdminService::STATISTIC_NOT_PASSED_QUOTA] !== null ? sprintf('%.2f', $statistic[CorrectorAdminService::STATISTIC_NOT_PASSED_QUOTA]) : "");
            $csv->addColumn($statistic[CorrectorAdminService::STATISTIC_AVERAGE] !== null ? sprintf('%.2f', $statistic[CorrectorAdminService::STATISTIC_AVERAGE]) : "");
            foreach($grade_levels as $value) {
                $csv->addColumn((string)($record['grade_statistics'][$value] ?? 0));

            }
        }
        $storage = $this->dic->filesystem()->temp();
        $basedir = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
        $file = 'xlas/'. (new UUID)->uuid4AsString() . '.csv';
        $storage->write($file, $csv->getCSVString());

        return $basedir . '/' . $file;
    }

    protected function buildPresentationTable() : Presentation
    {
        return $this->uiFactory->table()->presentation(
            $this->plugin->txt('statistic'), //title
            [],
            function (PresentationRow $row, $record, $ui_factory, $environment) { //mapping-closure
                if(count($record) == 1) {
                    return [$this->uiFactory->divider()->horizontal()->withLabel("<h4>" . $record["title"] . "</h4>")];
                }

                $statistic = $record["statistic"];
                $properties = [];
                $fproperties = [];
                $pseudonym = [];
                $properties[$record['count']] = (string)$statistic[CorrectorAdminService::STATISTIC_COUNT];
                $properties[$record['final']] = (string)$statistic[CorrectorAdminService::STATISTIC_FINAL];
                if($statistic[CorrectorAdminService::STATISTIC_NOT_ATTENDED] !== null) {
                    $properties[$this->plugin->txt('statistic_not_attended')] = (string)$statistic[CorrectorAdminService::STATISTIC_NOT_ATTENDED];
                }
                $properties[$this->plugin->txt('statistic_passed')] = (string)$statistic[CorrectorAdminService::STATISTIC_PASSED];
                $properties[$this->plugin->txt('statistic_not_passed')] = (string)$statistic[CorrectorAdminService::STATISTIC_NOT_PASSED];

                if($statistic[CorrectorAdminService::STATISTIC_NOT_PASSED_QUOTA] !== null) {
                    $perc = 100 * $statistic[CorrectorAdminService::STATISTIC_NOT_PASSED_QUOTA];
                    $properties[$this->plugin->txt('essay_not_passed_quota')] = sprintf('%.1f', $perc) . '%';
                }

                if($statistic[CorrectorAdminService::STATISTIC_AVERAGE] !== null) {
                    $properties[$this->plugin->txt('essay_average_points')] = sprintf('%.2f', $statistic[CorrectorAdminService::STATISTIC_AVERAGE]);
                }

                foreach($record['grade_statistics'] as $key => $value) {
                    $fproperties[$key . " "/*Hack to ensure a string*/] = (string)$value;
                }

                if(isset($record["pseudonym"])) {
                    $pseudonym = [$this->plugin->txt("pseudonym") => implode(", ", array_unique($record["pseudonym"]))];
                }


                if(isset($record["grade"])){
                    $row = $row->withSubheadline($this->plugin->txt("final_result") . $record["grade"]);
                }

                return $row
                    ->withHeadline($record['title'])
                    ->withImportantFields($properties)
                    ->withContent($ui_factory->listing()->descriptive(array_merge($pseudonym, $properties)))
                    ->withFurtherFieldsHeadline($this->plugin->txt('grade_distribution'))
                    ->withFurtherFields($fproperties);
            }
        );
    }

    protected function loadObjectsInContext() : void
    {
        $objects = $this->object_services->iliasContext()->getAllEssaysInThisContext();
        $this->objects = array_filter($objects, fn ($object) => ($this->access->checkAccess("maintain_correctors", '', $object["ref_id"])));
    }

    protected function loadDataForObject($obj_id) : void
    {
        $this->summaries[$obj_id] = $this->essay_repo->getCorrectorSummariesByTaskId($obj_id);
        $this->grade_level[$obj_id] = $this->object_repo->getGradeLevelsByObjectId($obj_id);
        $this->essays[$obj_id] = $this->essay_repo->getEssaysByTaskId($obj_id);
    }

    protected function printGradeLevelConsistencyInfo() : void
    {
        if(!$this->checkGradeLevelsConsistency()) {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt('statistic_grade_level_inconsistency_info'));
        }
    }

    protected function checkGradeLevelsConsistency(): bool
    {
        $context_levels = array_map(fn (array $x) => array_map(fn (GradeLevel $level) => strtolower($level->getGrade()), $x), $this->grade_level);
        $exemplar = array_pop($context_levels);

        if($exemplar === null) {
            return true;
        }

        foreach($context_levels as $context_level) {
            if(!empty(array_diff($exemplar, $context_level))) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param CorrectorSummary[]|Essay[] $grading_objects
     * @return array
     */
    protected function getStatistic(array $grading_objects): array
    {
        return $this->service->gradeStatistics($grading_objects, array_merge(...$this->grade_level));
    }

    protected function getGradeStatistic(array $statistic, int $obj_id) : array
    {
        $grade_statistics = [];
        foreach($this->grade_level[$obj_id] as $level) {
            $grade_statistics[$level->getGrade()] = $statistic[CorrectorAdminService::STATISTIC_COUNT_BY_LEVEL][$level->getId()] ?? 0;
        }
        return $grade_statistics;
    }

    protected function getGradeStatisticOverAll(array $statistic) : array
    {
        $grade_statistic = [];
        foreach(array_merge(...$this->grade_level) as $level) {
            if(isset($grade_statistic[$level->getGrade()])) {
                $grade_statistic[$level->getGrade()] += $statistic[CorrectorAdminService::STATISTIC_COUNT_BY_LEVEL][$level->getId()] ?? 0;
            } else {
                $grade_statistic[$level->getGrade()] = $statistic[CorrectorAdminService::STATISTIC_COUNT_BY_LEVEL][$level->getId()] ?? 0;
            }

        }
        return $grade_statistic;
    }

    protected function createStatisticItem(string $title, array $statistic, bool $is_essay = true): Statistic
    {
        $item =  $this->localDI->getUIFactory()->statistic()->statistic(
            $title,
            $statistic[CorrectorAdminService::STATISTIC_COUNT],
            $is_essay ? $this->plugin->txt('essay_count') : $this->plugin->txt('correction_count'),
            $statistic[CorrectorAdminService::STATISTIC_FINAL],
            $is_essay ? $this->plugin->txt('essay_final') : $this->plugin->txt('correction_final'),
        );

        if($statistic[CorrectorAdminService::STATISTIC_NOT_ATTENDED] !== null) {
            $item = $item->withNotAttended($statistic[CorrectorAdminService::STATISTIC_NOT_ATTENDED]);
        }

        if($statistic[CorrectorAdminService::STATISTIC_NOT_PASSED] !== null) {
            $item = $item->withNotPassed($statistic[CorrectorAdminService::STATISTIC_NOT_PASSED]);
        }

        if($statistic[CorrectorAdminService::STATISTIC_PASSED] !== null) {
            $item = $item->withPassed($statistic[CorrectorAdminService::STATISTIC_PASSED]);
        }

        if($statistic[CorrectorAdminService::STATISTIC_AVERAGE] !== null) {
            $item = $item->withAveragePoints($statistic[CorrectorAdminService::STATISTIC_AVERAGE]);
        }

        if($statistic[CorrectorAdminService::STATISTIC_NOT_PASSED_QUOTA] !== null) {
            $item = $item->withNotPassedQuota($statistic[CorrectorAdminService::STATISTIC_NOT_PASSED_QUOTA]);
        }
        return $item;
    }
}
