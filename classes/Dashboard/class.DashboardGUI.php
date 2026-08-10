<?php

namespace ILIAS\Plugin\LongEssayAssessment\Dashboard;

use Edutiek\AssessmentService\Views\Data\ClientFilterOptions;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
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
                    case 'deliverEssayPdf':
                    case 'showItems':
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
        $live_panel = $lsp_f->panel($this->plugin->txt('filter_client_status'), $this->ctrl->getLinkTarget($this, "liveData"))
        ->withAdditionalProperties([
            $lsp_f->property(ClientFilterOptions::ONLINE->value, $this->plugin->txt('client_filter_online'), 0, '#online'),
            $lsp_f->property(ClientFilterOptions::OFFLINE->value, $this->plugin->txt('client_filter_offline'), 0, '#offline'),
            $lsp_f->property(ClientFilterOptions::LOW_BATTERY->value, $this->plugin->txt('client_filter_low_battery'), 0, '#battery'),
            $lsp_f->property(ClientFilterOptions::HIDDEN->value, $this->plugin->txt('client_filter_hidden'), 0, '#locked'),
            $lsp_f->property(ClientFilterOptions::MULTI_SESSIONS->value, $this->plugin->txt('client_filter_multi_sessions'), 0, '#multi'),
        ]);

        $table = $this->plugin_ui_factory->table()->dataTable('dashboard_table', $this);
        $table->executeAction();
        $this->tpl->setContent($this->renderer->render([$live_panel, $table]));
    }


    public function getTableActions(): array
    {
        return [
            $this->viewProccessingAction(),
            // TODO
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
            "sessions",
            "first_access",
            "last_access",
            "battery",
            "hidden",
            "writing_last_save",
            "word_count",
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
            "client",
            "name",
            "location",
            "status",
            "time_limit_changed",
            "min_words",
            "max_words",
        ];
    }

    protected function initialVisibleColumns(): array
    {
        return ["name", "login", "status", "working_start", "working_end", "time_limit_changed", "authorized", "excluded"];

    }

    protected function liveData(): void
    {
        echo(json_encode($this->views->writer()->clientFilterCounts($this->object->getAssId())));
        exit();
    }

    public function exportTableAction(): Export
    {
        return $this->plugin_ui_factory->table()->action()->export('export', $this->plugin->txt('table_export'), $this->plugin->txt('dashboard_table_export_filename'));
    }
}
