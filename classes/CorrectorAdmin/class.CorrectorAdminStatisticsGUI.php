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
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorAdminStatisticsGUI extends BaseGUI
{
    /** @var CorrectorAdminService */
    protected $service;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->service = $this->localDI->getCorrectorAdminService($this->object->getId());
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


    /**
     * Show the items
     */
    protected function showStartPage()
    {
        $di = $this->localDI;
        $corrector_repo = $di->getCorrectorRepo();
        $essay_repo = $di->getEssayRepo();
        $object_repo = $di->getObjectRepo();
        $corrector_service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $correctors = $corrector_repo->getCorrectorsByTaskId($this->object->getId());
        $summaries = $essay_repo->getCorrectorSummariesByTaskId($this->object->getId());
        $grade_level = $object_repo->getGradeLevelsByObjectId($this->object->getId());
        $usernames = $this->common_services->userDataHelper()->getNames(array_map(fn (Corrector $x) => $x->getUserId(), $correctors));
        $summary_statistics = $corrector_service->gradeStatistics($summaries);
        $essays = $essay_repo->getEssaysByTaskId($this->object->getId());
        $essay_statistics = $corrector_service->gradeStatistics($essays);

        $rows = [['title' => $this->plugin->txt('essay_correction_finlized'), 'count' => $this->plugin->txt('essay_count'),
                  'final' => $this->plugin->txt('essay_final'), 'statistic' => $essay_statistics],
                 ['title' => $this->plugin->txt('corrections_all') , 'count' => $this->plugin->txt('correction_count'),
                  'final' => $this->plugin->txt('correction_final'), 'statistic' => $summary_statistics]];

        foreach($correctors as $corrector) {
            $corrector_id = $corrector->getId();
            $corrector_summaries = array_filter($summaries, fn (CorrectorSummary $x) => ($x->getCorrectorId() === $corrector_id));
            $statistics = $corrector_service->gradeStatistics($corrector_summaries);
            $rows[] = ['title' => $usernames[$corrector->getUserId()], 'count' => $this->plugin->txt('correction_count'),
                       'final' => $this->plugin->txt('correction_final'), 'statistic' => $statistics];
        }

        $ptable = $this->uiFactory->table()->presentation(
            $this->plugin->txt('statistic'), //title
            [],
            function ($row, $record, $ui_factory, $environment) use ($grade_level) { //mapping-closure
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

                foreach($grade_level as $level) {
                    $fproperties[$level->getGrade()] = (string)$statistic[CorrectorAdminService::STATISTIC_COUNT_BY_LEVEL][$level->getId()] ?? 0;
                }

                return $row
                    ->withHeadline($record['title'])
                    ->withImportantFields($properties)
                    ->withContent($ui_factory->listing()->descriptive($properties))
                    ->withFurtherFieldsHeadline($this->plugin->txt('grade_distribution'))
                    ->withFurtherFields($fproperties);
            }
        );
        $this->tpl->setContent($this->renderer->render($ptable->withData($rows)));
    }
}
