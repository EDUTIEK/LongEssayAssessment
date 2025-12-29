<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\Data\WritingStatus;
use Edutiek\AssessmentService\Assessment\LogEntry\MentionUser as LogEntryMention;
use Edutiek\AssessmentService\Assessment\LogEntry\Type as LogEntryType;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaService;
use Edutiek\AssessmentService\Assessment\Data\ValidationError;
use Edutiek\AssessmentService\Assessment\Writer\FullService as WriterService;
use Edutiek\AssessmentService\EssayTask\AssessmentStatus\FullService as AssessmentStatus;
use Edutiek\AssessmentService\EssayTask\Essay\ClientService as EssayService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use ILIAS\Plugin\LongEssayAssessment\Assessment\WorkingTime\IndividualValidator;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\ValidationErrorStore;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\FilterParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\Plugin\LongEssayAssessment\GUI\Writer\WriterTableGUI;
use ILIAS\Plugin\LongEssayAssessment\GUI\Writer\WriterItem;

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
                    case 'showItems':
                    case 'deleteWorkingTime':
                    case 'removeWriter':
                    case 'repealExcludeParticipants':
                    case 'excludeParticipants':
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

// todo: activate when implemented
//        #$delete_writer_data_modal = $this->buildDeleteWriterDataModal();
//        $delete_writer_data_button = $this->ui_factory->button()->standard($this->plugin->txt("delete_writer_data"), "#");
//        #                                             ->withOnClick($delete_writer_data_modal->getShowSignal());
//        $this->toolbar->addComponent($delete_writer_data_button);

        $upload_button = $this->ui_factory->button()->standard(
            $this->plugin->txt('essay_import'),
            $this->ctrl->getLinkTargetByClass(ImportEssayGUI::class, 'showForm')
        );
        $this->toolbar->addComponent($upload_button);

        $table = $this->plugin_ui_factory->table()->dataTable('writer_admin_table', $this);
        $table->executeAction();
        $this->tpl->setContent($this->renderer->render($table));
    }

    public function assignWriters(array $a_usr_ids, $a_type = null)
    {
        if (count($a_usr_ids) <= 0) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('no_writer_set'), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        foreach ($a_usr_ids as $id) {
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
        $writers = array_map(fn ($row) => $row->getUserId(), $this->writer_service->all());
        return array_filter($a_user_ids, fn ($user_id) => !in_array((int) $user_id, $writers));
    }

    public function getTableActions(): array
    {
        return [
// todo: activate when implemented
//            $this->viewProccessingAction(),
//            $this->exportStepsAction(),
//            $this->addLogEntryAction(),
//            $this->mailToWriterAction(),
//            $this->authorizeWritingAction(),
//            $this->unauthorizeWritingAction(),
//            $this->workingTimeChangeAction(),
//            $this->workingTimeDeleteAction(),
//            $this->changeLocationAction(),
//            $this->pdfVersionDownloadAction(),
//            $this->editPdfVersionAction(),
//            $this->excludeParticipantAction(),
//            $this->repealExcludeParticipantAction(),
            $this->removeWriterAction(),
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
     "pdf_version"];
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
        $location_avaiable = $has_started = $duration_avaiable = true;
        return ["name", "login", "pseudonym", $location_avaiable ? "location" : null,  "status",
                $has_started ? "working_start": null, $has_started ? "working_end": null,
                $has_started ? "working_duration": null, $duration_avaiable ? "assessment_duration": null,
                "time_limit_changed", $has_started ? "authorized" : null, $has_started ? "excluded" : null];
    }
}
