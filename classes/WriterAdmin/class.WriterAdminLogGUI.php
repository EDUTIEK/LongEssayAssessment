<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Alert;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\LogEntry;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use \ilUtil;
use ILIAS\Plugin\LongEssayAssessment\Task\LoggingService;
use ilFileDelivery;
use ILIAS\UI\Implementation\Component\ReplaceSignal;

/**
 * Maintenance of the writer admin log
 * NOTE: the log gets also entries from correction
 *
 * @package ILIAS\Plugin\LongEssayAssessment\WriterAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminLogGUI: ilObjLongEssayAssessmentGUI
 */
class WriterAdminLogGUI extends BaseGUI
{
    /** @var LoggingService */
    protected $service;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->service = $this->localDI->getLoggingService($this->object->getId());
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
        $this->addModal($modal_log_entry);

        $modal_writer_notice = $this->buildFormModalWriterNotice();
        $button_writer_notice = $this->uiFactory->button()->standard($this->plugin->txt("create_alert"), '#')
            ->withOnClick($modal_writer_notice->getShowSignal());
        $this->toolbar->addComponent($button_writer_notice);
        $this->addModal($modal_writer_notice);

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

        $this->setContent($list->getContent());
    }

    private function createAlert()
    {
        if ($this->request->getMethod() == "POST") {
            $modal = $this->buildFormModalWriterNotice();
            $modal = $modal->withRequest($this->request);
            $data = $modal->getData();

            // inputs are ok => save data
            if (is_array($data) && array_key_exists("text", $data) && array_key_exists("recipient", $data) && strlen($data["text"]) > 0) {
                $alert = new Alert();
                $alert->setTaskId($this->object->getId());
                $alert->setShownFrom((new \ilDateTime(time(), IL_CAL_UNIX))->get(IL_CAL_DATETIME));
                $alert->setMessage($data['text']);

                if($data['recipient'] != -1) {
                    $alert->setWriterId((int) $data['recipient']);
                }
                $task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
                $task_repo->save($alert);

                $this->tpl->setOnScreenMessage("success", $this->plugin->txt("alert_created"), true);
                $this->ctrl->redirect($this, "showStartPage");
            } else {
                //                $close = $modal->getCloseSignal();
                //                $modal = $modal->withAdditionalOnLoadCode(function ($id) use ($close): string {
                //                    $code = "$(document).on('$close', function() { location.reload();});";
                //                    return $code;
                //                });

                $this->addModal($modal->withOnLoad($modal->getShowSignal()));
                $this->showStartPage();
            }
        }
    }

    private function createLogEntry()
    {
        if ($this->request->getMethod() == "POST") {
            $modal = $this->buildFormModalLogEntry();
            $modal = $modal->withRequest($this->request);
            $data = $modal->getData();

            // inputs are ok => save data
            if (is_array($data) && !empty($data['entry'])) {
                $this->service->addEntry(LogEntry::TYPE_NOTE, $this->dic->user()->getId(), null, $data['entry']);

                $this->tpl->setOnScreenMessage("success", $this->plugin->txt("log_entry_created"), true);
                $this->ctrl->redirect($this, "showStartPage");
            } else {
                $this->addModal($modal->withOnLoad($modal->getShowSignal()));
                $this->showStartPage();
            }
        }
    }

    private function buildFormModalWriterNotice(): \ILIAS\UI\Component\Modal\RoundTrip
    {
        $options = array_replace(
            ["-1" => $this->plugin->txt("alert_recipient_all")],
            $this->getWriterNameOptions()
        );

        $inputs = [
            "recipient" => $this->uiFactory->input()->field()->select($this->plugin->txt("alert_recipient"), $options)->withRequired(true),
            "text" => $this->uiFactory->input()->field()->textarea($this->plugin->txt("alert_text"))->withRequired(true)
        ];
        return $this->uiFactory->modal()->roundtrip($this->plugin->txt("create_alert"), [], $inputs, $this->ctrl->getFormAction($this, "createAlert"))->withSubmitLabel($this->lng->txt("send"));
    }

    private function buildFormModalLogEntry(): \ILIAS\UI\Component\Modal\RoundTrip
    {

        $inputs = [
            "entry" => $this->uiFactory->input()->field()->textarea($this->plugin->txt("log_entry_text"))->withRequired(true)
        ];

        return $this->uiFactory->modal()->roundtrip($this->plugin->txt("create_log_entry"), [], $inputs, $this->ctrl->getFormAction($this, "createLogEntry"))->withSubmitLabel($this->lng->txt("save"));
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
        $filename = ilFileDelivery::returnASCIIFilename($this->plugin->txt('export_log_file_prefix') .' ' . $this->object->getTitle()) . '.csv';
        $this->common_services->fileHelper()->deliverData($this->service->createCsv(), $filename, 'text/csv');
    }

}
