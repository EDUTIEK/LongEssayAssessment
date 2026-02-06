<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin;

use Edutiek\AssessmentService\Views\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin\CorrectorAdminWriterStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorAdminWriterStatisticsGUI  extends BaseGUI
{
    private StatisticViewRepo $statistic_repo;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->statistic_repo = $this->plugin->dic()->view()->statistic();
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

    public function showStartPage()
    {
        $puf = $this->plugin_ui_factory;
        $general = $this->statistic_repo->oneAssessment($this->object->getAssId(), []);

        $general_statistic = $puf->statistic()->statistic(
            $this->plugin->txt('total_statistic'),
            $general->getCount(),
            $this->plugin->txt('essay_count'),
            $general->getAttended(),
            $this->plugin->txt('essay_final')
        )->withNotAttended($general->getNotAttended())
                                 ->withNotPassed($general->getNotPassed())
                                 ->withPassed($general->getPassed())
                                 ->withAveragePoints($general->getAveragePoints()??0)
                                 ->withNotPassedQuota($general->getNotPassedQuota()??0);

        if ($general->isGradesUniform()) {
            $general_statistic = $general_statistic->withGrades($general->getGradeCounts());
        }

        if ($general->isMaxPointUniform()) {
            $general_statistic = $general_statistic->withPoints($general->getPointsCounts());
        }


        $this->tpl->setContent($this->renderer->render([$general_statistic]));
    }
}