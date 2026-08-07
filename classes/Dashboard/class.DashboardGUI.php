<?php

namespace ILIAS\Plugin\LongEssayAssessment\Dashboard;

use ILIAS\Plugin\LongEssayAssessment\GUI\Writer\WriterTableGUI;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Export;

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
                    case 'unauthorizeWriting':
                    case 'workingTimeDelete':
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
        $live_panel = $lsp_f->panel("Schreib-Status", $this->ctrl->getLinkTarget($this, "liveData"))
        ->withAdditionalProperties([
            $lsp_f->property('online', 'Online', 0, '#online'),
            $lsp_f->property('offline', 'Offline', 0, '#offline'),
            $lsp_f->property('battery', 'niedriger Batteriestatus', 0, '#battery'),
            $lsp_f->property('locked', 'gesperrter Bildschirm', 0, '#locked'),
            $lsp_f->property('multi', 'Mehrfach-Login', 0, '#multi'),
        ]);

        $table = $this->plugin_ui_factory->table()->dataTable('dashboard_table', $this);
        $table->executeAction();
        $this->tpl->setContent($this->renderer->render([$live_panel, $table]));
    }


    public function getTableActions(): array
    {
        return [
            $this->viewProccessingAction(),
            // $this->sendAlertAction(),
            $this->addLogEntryAction(),
            $this->exportTableAction(),
            $this->workingTimeChangeAction(),
            $this->workingTimeDeleteAction(),
            $this->changeLocationAction(),
            $this->unauthorizeWritingAction(),
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

    public function exportTableAction(): Export
    {
        return $this->plugin_ui_factory->table()->action()->export('export', $this->plugin->txt('table_export'), $this->plugin->txt('dashboard_table_export_filename'));
    }
}
