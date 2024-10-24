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
            $csv->addColumn(mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8'));
        }

        foreach($records as $record) {
            if(!isset($record["usr_id"])) {
                continue;
            }
            $csv->addRow();
            $user = new ilObjUser($record["usr_id"]);
            $statistic = $record["statistic"];

            $csv->addColumn(mb_convert_encoding($user->getLogin(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding($user->getFirstname(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding($user->getLastname(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding($user->getMatriculation(), 'ISO-8859-1', 'UTF-8'));
            if($has_obj_id) {
                $csv->addColumn(mb_convert_encoding($obj_titles[(int)$record["obj_id"]], 'ISO-8859-1', 'UTF-8'));
            }
            $csv->addColumn(mb_convert_encoding((string)$statistic[CorrectorAdminService::STATISTIC_COUNT], 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding((string)$statistic[CorrectorAdminService::STATISTIC_FINAL], 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding((string)($statistic[CorrectorAdminService::STATISTIC_NOT_ATTENDED] ?? 0), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding((string)$statistic[CorrectorAdminService::STATISTIC_PASSED], 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding((string)$statistic[CorrectorAdminService::STATISTIC_NOT_PASSED], 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding($statistic[CorrectorAdminService::STATISTIC_NOT_PASSED_QUOTA] !== null ? sprintf('%.2f', $statistic[CorrectorAdminService::STATISTIC_NOT_PASSED_QUOTA]) : "", 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding($statistic[CorrectorAdminService::STATISTIC_AVERAGE] !== null ? sprintf('%.2f', $statistic[CorrectorAdminService::STATISTIC_AVERAGE]) : "", 'ISO-8859-1', 'UTF-8'));
            foreach($grade_levels as $value) {
                $csv->addColumn(mb_convert_encoding((string)($record['grade_statistics'][$value] ?? 0), 'ISO-8859-1', 'UTF-8'));

            }
        }
        $storage = $this->dic->filesystem()->temp();
        $basedir = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
        $file = 'xlas/'. (new UUID)->uuid4AsString() . '.csv';
        $storage->write($file, $csv->getCSVString());

        return $basedir . '/' . $file;
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
