<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Alert;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\LogEntry;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use \ilUtil;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\WriterAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminLogGUI: ilObjLongEssayAssessmentGUI
 */
class WriterAdminLogGUI extends BaseGUI
{
    /** @var WriterAdminService */
    protected $service;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->service = $this->localDI->getWriterAdminService($this->object->getId());
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showStartPage');
        switch ($cmd) {
            case 'showStartPage':
            case 'createAlert':
            case 'createLogEntry':
            case 'exportLog':
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }


    /**
     * Show the items
     */
    protected function showStartPage()
    {
        $modal_log_entry = $this->buildFormModalLogEntry();
        $button_log_entry = $this->uiFactory->button()->primary($this->plugin->txt("create_log_entry"), '#')
            ->withOnClick($modal_log_entry->getShowSignal());
        $this->toolbar->addComponent($button_log_entry);

        $modal_writer_notice = $this->buildFormModalWriterNotice();
        $button_writer_notice = $this->uiFactory->button()->standard($this->plugin->txt("create_alert"), '#')
            ->withOnClick($modal_writer_notice->getShowSignal());
        $this->toolbar->addComponent($button_writer_notice);

        $this->toolbar->addSeparator();
        $button_export = $this->uiFactory->button()->standard(
            $this->plugin->txt("export_log"),
            $this->ctrl->getLinkTarget($this, 'exportLog')
        );
        $this->toolbar->addComponent($button_export);

        $task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();

        $list = new WriterAdminLogListGUI($this, "showStartPage", $this->plugin, $this->object->getId());
        $list->addLogEntries($task_repo->getLogEntriesByTaskId($this->object->getId()));
        $list->addAlerts($task_repo->getAlertsByTaskId($this->object->getId()));

        $this->tpl->setContent($this->renderer->render([$modal_log_entry, $modal_writer_notice]) . $list->getContent());
    }

    private function createAlert()
    {
        if ($this->request->getMethod() == "POST") {
            $data = $_POST;

            // inputs are ok => save data
            if (array_key_exists("text", $data) && array_key_exists("recipient", $data) && strlen($data["text"]) > 0) {
                $alert = new Alert();
                $alert->setTaskId($this->object->getId());
                $alert->setShownFrom((new \ilDateTime(time(), IL_CAL_UNIX))->get(IL_CAL_DATETIME));
                $alert->setMessage($data['text']);

                if($data['recipient'] != -1) {
                    $alert->setWriterId((int) $data['recipient']);
                }
                $task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
                $task_repo->save($alert);

                ilUtil::sendSuccess($this->plugin->txt("alert_created"), true);
            } else {
                ilUtil::sendFailure($this->lng->txt("validation_error"), true);
            }
            $this->ctrl->redirect($this, "showStartPage");
        }
    }

    private function createLogEntry()
    {

        if ($this->request->getMethod() == "POST") {
            $data = $_POST;

            // inputs are ok => save data
            if (array_key_exists("entry", $data) && strlen($data["entry"]) > 0) {
                $log_entry = new LogEntry();
                $log_entry->setTaskId($this->object->getId());
                $log_entry->setTimestamp((new \ilDateTime(time(), IL_CAL_UNIX))->get(IL_CAL_DATETIME));
                $log_entry->setEntry($data['entry']);
                $log_entry->setCategory(LogEntry::CATEGORY_NOTE);

                $task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
                $task_repo->save($log_entry);

                ilUtil::sendSuccess($this->plugin->txt("log_entry_created"), true);
            } else {
                ilUtil::sendFailure($this->lng->txt("validation_error"), true);
            }
            $this->ctrl->redirect($this, "showStartPage");
        }
    }

    private function buildFormModalWriterNotice(): \ILIAS\UI\Component\Modal\RoundTrip
    {
        $form = new \ilPropertyFormGUI();
        $form->setId(uniqid('form'));

        $options = array_replace(
            ["-1" => $this->plugin->txt("alert_recipient_all")],
            $this->getWriterNameOptions()
        );

        $item = new \ilSelectInputGUI($this->plugin->txt("alert_recipient"), 'recipient');
        $item->setOptions($options);
        $item->setRequired(true);
        $form->addItem($item);

        $item = new \ilTextAreaInputGUI($this->plugin->txt("alert_text"), 'text');
        $item->setRequired(true);
        $form->addItem($item);

        $form->setFormAction($this->ctrl->getFormAction($this, "createAlert"));

        $item = new \ilHiddenInputGUI('cmd');
        $item->setValue('submit');
        $form->addItem($item);

        return $this->buildFormModal($this->plugin->txt("create_alert"), $this->lng->txt('send'), $form);
    }

    private function buildFormModalLogEntry(): \ILIAS\UI\Component\Modal\RoundTrip
    {

        $form = new \ilPropertyFormGUI();
        $form->setId(uniqid('form'));

        $item = new \ilTextAreaInputGUI($this->plugin->txt("log_entry_text"), 'entry');
        $item->setRequired(true);
        $form->addItem($item);

        $form->setFormAction($this->ctrl->getFormAction($this, "createLogEntry"));

        $item = new \ilHiddenInputGUI('cmd');
        $item->setValue('submit');
        $form->addItem($item);

        return $this->buildFormModal($this->plugin->txt("create_log_entry"), $this->lng->txt('save'), $form);
    }

    /**
     * @param string             $title     title of the modal
     * @param string             $submit    text of the submit button (send or save))
     * @param \ilPropertyFormGUI $form      form to be displayed i nthe modal
     * @return \ILIAS\UI\Component\Modal\RoundTrip
     */
    private function buildFormModal(string $title, string $submit, \ilPropertyFormGUI $form): \ILIAS\UI\Component\Modal\RoundTrip
    {
        global $DIC;
        $factory = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();

        // Build the form
        $item = new \ilHiddenInputGUI('cmd');
        $item->setValue('submit');
        $form->addItem($item);

        // Build a submit button (action button) for the modal footer
        $form_id = 'form_' . $form->getId();
        $submit = $factory->button()->primary($submit, '#')
            ->withOnLoadCode(function ($id) use ($form_id) {
                return "$('#{$id}').click(function() { $('#{$form_id}').submit(); return false; });";
            });

        return $factory->modal()->roundtrip($title, $factory->legacy($form->getHTML()))
            ->withActionButtons([$submit]);
    }

    private function getWriterNameOptions(): array
    {
        $writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
        $writers = [];
        foreach($writer_repo->getWritersByTaskId($this->object->getId()) as $writer) {
            $writers[$writer->getUserId()] = $writer;
        }

        $user_ids = array_map(function ($x) {
            return $x->getUserId();
        }, $writers);
        $out = [];

        foreach (\ilUserUtil::getNamePresentation(array_unique($user_ids), false, false, "", true) as $usr_id => $user) {
            $out[(string)$writers[$usr_id]->getId()] = $user;
        }

        return $out;
    }

    private function exportLog()
    {
        $filename = \ilUtil::getASCIIFilename($this->plugin->txt('export_log_file_prefix') .' ' . $this->object->getTitle()) . '.csv';
        ilUtil::deliverFile($this->service->createLogExport(), $filename, 'text/csv', true, true);
    }

}
