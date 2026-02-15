<?php

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\View\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\View\Data\StatisticView;
use Edutiek\AssessmentService\Assessment\Data\Corrector;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use ILIAS\Plugin\LongEssayAssessment\StatisticHelper;

/**
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorStatisticsGUI extends BaseGUI
{
    use StatisticHelper;

    private StatisticViewRepo $statistic_repo;
    private ?Corrector $corrector;
    private UserService $user_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->statistic_repo = $this->plugin->dic()->view()->statistic();
        $this->corrector = $this->assessment_api->corrector()->oneByUserId($this->user->getId());
        $this->user_service = $this->system_api->user();
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
        $general = $this->statistic_repo->someCorrections(['ass_id' => $this->object->getAssId()]);
        $own = $general->fromUser($this->user_service->getCurrentUser());

        $general_statistic = $this->buildStatistic($general, false, $this->plugin->txt('corrections_all'));
        $own_statistic = $this->buildStatistic($own, false, $this->plugin->txt('corrections_my'));

        $this->tpl->setContent($this->renderer->render(
            $puf->statistic()->graphStatisticGroup($this->plugin->txt("statistic"), [$own_statistic, $general_statistic,])
        ));
    }

}
