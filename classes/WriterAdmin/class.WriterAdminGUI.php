<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\GUI\Writer\WriterTableGUI;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Export;

/**
 * Writer Admin GUI class
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI: ilRepositorySearchGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI: ILIAS\Plugin\LongEssayAssessment\WriterAdmin\ImportEssayGUI
 */
class WriterAdminGUI extends WriterTableGUI
{
    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            case 'ilrepositorysearchgui':
                $rep_search = new \ilRepositorySearchGUI();
                $rep_search->addUserAccessFilterCallable([$this, 'filterUserIdsByParticipants']);
                $rep_search->setCallback($this, "assignWriters");
                $this->ctrl->setReturn($this, 'showItems');
                $ret = $this->ctrl->forwardCommand($rep_search);
                break;
            case strtolower(ImportEssayGUI::class):
                $this->ctrl->forwardCommand(new ImportEssayGUI($this->object));
                break;
            default:
                $cmd = $this->ctrl->getCmd('showItems');
                switch ($cmd) {
                    case 'deliverEssayPdf':
                    case 'editPdfVersion':
                    case 'changeTextToPdf':
                    case 'authorizeWriting':
                    case 'unauthorizeWriting':
                    case 'showItems':
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
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));

        \ilRepositorySearchGUI::fillAutoCompleteToolbar(
            $this,
            $this->toolbar,
            array()
        );

        // search button
        $search_button = $this->ui_factory->button()->standard(
            $this->plugin->txt("search_participants"),
            $this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI', 'start')
        );
        $this->toolbar->addComponent($search_button);

        // spacer
        $this->toolbar->addSeparator();

        $upload_button = $this->ui_factory->button()->standard(
            $this->plugin->txt('essay_import'),
            $this->ctrl->getLinkTargetByClass(ImportEssayGUI::class, 'showForm')
        );
        $this->toolbar->addComponent($upload_button);

        $table = $this->plugin_ui_factory->table()->dataTable('writer_admin_table', $this);
        $table->executeAction();
        $this->tpl->setContent($this->renderer->render($table));
    }

    /**
     * Add users as writers
     */
    public function assignWriters(array $a_usr_ids, $a_type = null)
    {
        if (count($a_usr_ids) <= 0) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_user_id'), true);
            $this->ctrl->redirect($this, "showItems");
        }

        foreach ($a_usr_ids as $id) {
            // this creates the writer data if not yet existing
            $this->writer_service->getByUserId($id);
        }

        if (count($a_usr_ids) == 1) {
            $anchor = "user_" . $a_usr_ids[0];
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt('assign_writer_success'), true);
        $this->ctrl->redirect($this, "showItems", $anchor ?? "");
    }

    public function filterUserIdsByParticipants($a_user_ids)
    {
        $writers = array_map(fn($row) => $row->getUserId(), $this->writer_service->all());
        return array_filter($a_user_ids, fn($user_id) => !in_array((int) $user_id, $writers));
    }

    public function getTableActions(): array
    {
        return [
            $this->viewProccessingAction(),
            $this->downloadWritingAction(),
            $this->exportStepsAction(),
            $this->addLogEntryAction(),
            $this->mailToWriterAction(),
            $this->workingTimeChangeAction(),
            $this->workingTimeDeleteAction(),
            $this->changeLocationAction(),
            $this->authorizeWritingAction(),
            $this->unauthorizeWritingAction(),
            $this->editPdfVersionAction(),
            $this->changeTextToPdfAction(),
            $this->excludeParticipantAction(),
            $this->repealExcludeParticipantAction(),
            $this->removeWriterAction(),
            $this->exportTableAction(),
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

    public function exportTableAction(): Export
    {
        return $this->plugin_ui_factory->table()->action()->export('export', $this->plugin->txt('table_export'), $this->plugin->txt('writer_admin_table_export_filename'));
    }
}
