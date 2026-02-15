<?php

namespace ILIAS\Plugin\LongEssayAssessment\Writer;

use Edutiek\AssessmentService\Views\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\StatisticHelper;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class WriterStatisticsGUI extends BaseGUI
{
    use StatisticHelper;

    private StatisticViewRepo $statistic_repo;
    private \Edutiek\AssessmentService\Assessment\Data\Writer $writer;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->statistic_repo = $this->plugin->dic()->view()->statistic();
        $this->writer = $this->assessment_api->writer()->getByUserId($this->user->getId());
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
        $general = $this->statistic_repo->someAssessments(["ass_id" => $this->object->getAssId()]);
        $general_statistic = $this->buildStatistic($general, true, $this->plugin->txt('statistic'));

        if ($this->writer->isCorrectionFinalized() && $this->writer->getFinalGradeLevelId() !== null) {
            $grade = $this->assessment_api->gradeLevel()->one($this->writer->getFinalGradeLevelId());
            $general_statistic = $general_statistic->withOwnGrade($grade?->getGrade() ?? "");
        }

        $this->tpl->setContent($this->renderer->render([$general_statistic]));
    }
}
