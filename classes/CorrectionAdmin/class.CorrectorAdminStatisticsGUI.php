<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin;

use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\View\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\View\Data\StatisticView;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use Edutiek\AssessmentService\Assessment\Corrector\FullService as CorrectorService;
use Edutiek\AssessmentService\Assessment\Data\Corrector;
use ILIAS\Plugin\LongEssayAssessment\StatisticHelper;
use ilFileDelivery;
use ILIAS\Data\UUID\Factory as UUID;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin\CorrectorAdminStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorAdminStatisticsGUI extends BaseGUI
{
    use StatisticHelper;

    private StatisticViewRepo $statistic_repo;
    private UserService $user_service;
    private CorrectorService $corrector_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->statistic_repo = $this->plugin->dic()->view()->statistic();
        $this->user_service = $this->system_api->user();
        $this->corrector_service = $this->assessment_api->corrector();
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

    public function showStartPage()
    {
        $this->toolbar->addComponent($this->ui_factory->button()->primary(
            $this->plugin->txt("export_statistics"),
            $this->ctrl->getLinkTarget($this, "exportCSV")
        ));

        $puf = $this->plugin_ui_factory;
        $general = $this->statistic_repo->someCorrections(['ass_id' => $this->object->getAssId()]);
        $general_statistic = $this->buildStatistic($general, false, $this->plugin->txt('corrections_all'));

        $sections = [
            $puf->statistic()->statisticSection($this->plugin->txt("total_statistic")),
            $general_statistic,
            $puf->statistic()->statisticSection($this->plugin->txt("correctors"))
        ];

        $users = $this->user_service->getUsersByIds(array_map(fn (Corrector $corrector) => $corrector->getUserId(), $this->corrector_service->all()));
        foreach ($users as $user) {
            $corrector_statistic = $general->fromUser($user);
            $sections[] = $this->buildStatistic($corrector_statistic, false);
        }

        $this->tpl->setContent($this->renderer->render(
            $puf->statistic()->extendableStatisticGroup($this->plugin->txt("statistic"), $sections)
        ));
    }

    public function exportCSV(): void
    {
        $general = $this->statistic_repo->someCorrections(['ass_id' => $this->object->getAssId()]);
        $views = [];

        foreach ($general->getUsers() as $user) {
            $views[] =  $general->fromUser($user);
        }

        $users = $this->user_service->getUsersByIds(array_map(fn (Corrector $corrector) => $corrector->getUserId(), $this->corrector_service->all()));
        foreach ($users as $user) {
            $view = $general->fromUser($user);
            if (!empty($view->getGradingObjects())) {
                $views[] = $view;
            }
        }

        $csv = $this->buildStatisticExport($views, false, false);

        $storage = $this->dic->filesystem()->temp();
        $basedir = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
        $file = 'xlas/'. (new UUID)->uuid4AsString() . '.csv';
        $storage->write($file, $csv->getCSVString());
        $filename = ilFileDelivery::returnASCIIFilename($this->plugin->txt('export_statistics_corrector_file')). '.csv';

        ilFileDelivery::deliverFileAttached($basedir . '/' . $file, $filename, 'text/csv', true);
    }
}
