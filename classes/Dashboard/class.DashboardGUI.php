<?php

namespace ILIAS\Plugin\LongEssayAssessment\Dashboard;

use ILIAS\Plugin\LongEssayAssessment\GUI\Writer\WriterTableGUI;

/**
 * Dashboard GUI class
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Dashboard\DashboardGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\Dashboard\DashboardGUI: ILIAS\Plugin\LongEssayAssessment\WriterAdmin\ImportEssayGUI
 */
class DashboardGUI extends WriterTableGUI
{
    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                $cmd = $this->ctrl->getCmd('showItems');
                switch ($cmd) {
                    case 'showItems':
                    case 'deleteWorkingTime':
                    case 'liveData':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
                break;
        }
    }

    public function showItems(): void
    {
        $lsp_f = $this->plugin_ui_factory->liveStatusPanel();
        $live_panel = $lsp_f->panel("Status", $this->ctrl->getLinkTarget($this, "liveData"))
        ->withAdditionalProperties([
            $lsp_f->property('online', 'Online', 0, '#online'),
            $lsp_f->property('offline', 'Offline', 0, '#offline'),
            $lsp_f->property('connection', 'Verbindungsprobleme', 0, '#connection'),
            $lsp_f->property('battery', 'niedriger Batteriestatus', 0, '#battery'),
            $lsp_f->property('locked', 'gesperrte Bildschirme', 0, '#locked'),
        ]);

        $table = $this->plugin_ui_factory->table()->dataTable('writer_admin_table', $this);
        $table->executeAction();
        $this->tpl->setContent($this->renderer->render([$live_panel, $table]));
    }


    public function getTableActions(): array
    {
        return [
// todo: activate when implemented
//            $this->viewProccessingAction(),
//            $this->addLogEntryAction(),
//            $this->unauthorizeWritingAction(),
//            $this->workingTimeChangeAction(),
//            $this->workingTimeDeleteAction(),
//            $this->changeLocationAction(),
        ];
    }

    protected function hasColumns(): array
    {
        return [
            "image",
     "name",
     "login",
     "pseudonym",
     "location",
     "status",
     "writing_last_save",
     "word_count",
     "pdf_version",
     "working_start",
     "working_end",
     "working_duration",
     "assessment_start",
     "assessment_end",
     "assessment_duration",
     "time_limit_changed",
     "authorized",
     "authorized_from",
     "excluded",
     "excluded_from",
];
    }

    protected function hasFilterFields(): array
    {
        return [
            "name",
            "location",
            "status",
            "time_limit_changed",
            "min_words",
            "max_words",
            "pdf_version"
        ];
    }

    protected function initialVisibleColumns(): array
    {
        return ["name", "login", "status", "working_start", "working_end", "time_limit_changed", "authorized", "excluded"];

    }

    protected function liveData(): void
    {
        echo(json_encode([
            'online' => rand(50, 100),
            'offline' => rand(1, 10),
            'connection' => rand(1, 15),
            'battery' => rand(1, 25),
            'locked' => rand(1, 5),
        ]));
        exit();
    }
}
