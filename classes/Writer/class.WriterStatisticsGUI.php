<?php

namespace ILIAS\Plugin\LongEssayAssessment\Writer;

use Edutiek\AssessmentService\Views\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\StatisticHelper;
use Edutiek\AssessmentService\System\User\ReadService as UserService;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class WriterStatisticsGUI extends BaseGUI
{
    use StatisticHelper;

    private StatisticViewRepo $statistic_repo;
    private UserService $user_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->statistic_repo = $this->plugin->dic()->view()->statistic();
        $this->user_service = $this->plugin->dic()->system()->user();
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
        $user = $this->user_service->getUser($this->user->getId());
        $puf = $this->plugin_ui_factory;
        $general = $this->statistic_repo->someAssessments(["ass_id" => $this->object->getAssId()]);
        $general_statistic = $this->buildStatistic($general, true, $this->plugin->txt('statistic'));

        $gradings = $general->fromUser($user)->getGradingObjects();
        $own_grading = $gradings[array_key_first($gradings)] ?? null;

        if ($own_grading !== null) {
            $general_statistic = $general_statistic->withOwnGrade($own_grading->getGrade());
        }

        $this->tpl->setContent($this->renderer->render([$general_statistic]));
    }
}
