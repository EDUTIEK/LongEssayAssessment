<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

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
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Object\IliasContext;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\StatisticsGUI;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorStatisticsGUI extends StatisticsGUI
{
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

    protected function showStartPage() : void
    {
        $corrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->object->getId());

        $this->loadDataForObject($this->object->getId());

        $data = $this->getItemDataForCorrector($corrector);

        $ptable = $this->buildPresentationTable();
        $this->tpl->setContent($this->renderer->render($ptable->withData($data)));
    }

    private function getItemDataForCorrector(Corrector $corrector) : array
    {

        $obj_id = $this->object->getId();
        $corrector_id = $corrector->getId();
        $summary_statistics = $this->getStatistic($this->summaries[$obj_id]);
        $corrector_summaries = array_filter($this->summaries[$obj_id], fn (CorrectorSummary $x) => ($x->getCorrectorId() === $corrector_id));
        $statistics = $this->getStatistic($corrector_summaries);

        $grade_statistics = function (array $statistic) use ($obj_id) {
            $grade_statistics = [];
            foreach($this->grade_level[$obj_id] as $level) {
                $grade_statistics[$level->getGrade()] = $statistic[CorrectorAdminService::STATISTIC_COUNT_BY_LEVEL][$level->getId()] ?? 0;
            }
            return $grade_statistics;
        };

        return [['title' => $this->plugin->txt('corrections_all') , 'count' => $this->plugin->txt('correction_count'),
                 'final' => $this->plugin->txt('correction_final'), 'statistic' => $summary_statistics, 'grade_statistics' => $grade_statistics($summary_statistics)],
                ['title' => $this->plugin->txt('tab_corrector'), 'count' => $this->plugin->txt('correction_count'),
                 'final' => $this->plugin->txt('correction_final'), 'statistic' => $statistics, 'grade_statistics' => $grade_statistics($statistics)]];
    }
}
