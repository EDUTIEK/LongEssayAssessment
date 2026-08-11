<?php

namespace ILIAS\Plugin\LongEssayAssessment\Dashboard;

use Edutiek\AssessmentService\Views\Data\ClientFilterOptions;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\GUI\Writer\WriterTableGUI;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Export;
use ilSession;

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
        $this->handleClientFilter();
        $counts = $this->views->writer()->clientFilterCounts($this->object->getAssId());

        $lsp_f = $this->plugin_ui_factory->liveStatusPanel();
        $live_panel = $lsp_f->panel($this->plugin->txt('filter_client_status'), $this->ctrl->getLinkTarget($this, "liveData"))
        ->withAdditionalProperties([
            $lsp_f->property(
                'all',
                $this->plugin->txt('client_filter_all'),
                $counts['all'],
                $this->linkClientFilter('all'),
                $this->client_filter == 'all'
            ),
            $lsp_f->property(
                ClientFilterOptions::ONLINE->value,
                $this->plugin->txt('client_filter_online'),
                $counts[ClientFilterOptions::ONLINE->value],
                $this->linkClientFilter(ClientFilterOptions::ONLINE->value),
                $this->client_filter == ClientFilterOptions::ONLINE->value,
            ),
            $lsp_f->property(
                ClientFilterOptions::OFFLINE->value,
                $this->plugin->txt('client_filter_offline'),
                $counts[ClientFilterOptions::OFFLINE->value],
                $this->linkClientFilter(ClientFilterOptions::OFFLINE->value),
                $this->client_filter == ClientFilterOptions::OFFLINE->value,
            ),
            $lsp_f->property(
                ClientFilterOptions::LOW_BATTERY->value,
                $this->plugin->txt('client_filter_low_battery'),
                $counts[ClientFilterOptions::LOW_BATTERY->value],
                $this->linkClientFilter(ClientFilterOptions::LOW_BATTERY->value),
                $this->client_filter == ClientFilterOptions::LOW_BATTERY->value,
            ),
            $lsp_f->property(
                ClientFilterOptions::HIDDEN->value,
                $this->plugin->txt('client_filter_hidden'),
                $counts[ClientFilterOptions::HIDDEN->value],
                $this->linkClientFilter(ClientFilterOptions::HIDDEN->value),
                $this->client_filter == ClientFilterOptions::HIDDEN->value,
            ),
            $lsp_f->property(
                ClientFilterOptions::MULTI_SESSIONS->value,
                $this->plugin->txt('client_filter_multi_sessions'),
                $counts[ClientFilterOptions::MULTI_SESSIONS->value],
                $this->linkClientFilter(ClientFilterOptions::MULTI_SESSIONS->value),
                $this->client_filter == ClientFilterOptions::MULTI_SESSIONS->value,
            )
        ]);

        $table = $this->plugin_ui_factory->table()->dataTable('dashboard_table', $this);

        $table->executeAction();
        $this->tpl->setContent($this->renderer->render([$live_panel, $table]));
    }

    private function handleClientFilter()
    {
        $this->client_filter = $this->get->string('client_filter', null);
        if (empty($this->client_filter)) {
            $this->client_filter = ilSession::get(self::class . '.client_filter') ?? 'all';
        } else {
            ilSession::set(self::class . '.client_filter', $this->client_filter);
        }
    }

    private function linkClientFilter(string $value)
    {
        $this->ctrl->setParameter($this, 'client_filter', $value);
        $link = $this->ctrl->getLinkTarget($this, "showItems");
        $this->ctrl->clearParameterByClass(self::class, 'client_filter');
        return $link;
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
