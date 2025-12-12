<?php

namespace ILIAS\Plugin\LongEssayAssessment\Dashboard;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Action;
use ILIAS\UI\Component\Table\Column\Column;
use Generator;
use Edutiek\AssessmentService\Assessment\LogEntry\FullService as LogEntryService;
use Edutiek\AssessmentService\Assessment\Alert\FullService as AlertService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use Edutiek\AssessmentService\Assessment\Writer\FullService as WriterService;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\LogEntry\Type as LogEntryType;
use Edutiek\AssessmentService\Assessment\LogEntry\MentionUser;
use ILIAS\UI\Implementation\Component\Signal;
use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\Data\Alert;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Dashboard\ProtocolGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\Dashboard\ProtocolGUI:
 */
class ProtocolGUI extends BaseGUI
{
    private LogEntryService $log_entry_service;
    private AlertService $alert_service;
    private UserService $user_service;
    private WriterService $writer_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->log_entry_service = $this->assessment_api->logEntry();
        $this->alert_service = $this->assessment_api->alert();
        $this->user_service = $this->system_api->user();
        $this->writer_service = $this->assessment_api->writer();
    }

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

    protected function showStartPage()
    {

        $this->add($modal_log_entry = $this->buildFormModalLogEntry());
        $button_log_entry = $this->ui_factory->button()->primary($this->plugin->txt("create_log_entry"), '#')
                                            ->withOnClick($modal_log_entry->getShowSignal());
        $this->toolbar->addComponent($button_log_entry);

        $this->add($modal_writer_notice = $this->buildFormModalWriterNotice());
        $button_writer_notice = $this->ui_factory->button()->standard($this->plugin->txt("create_alert"), '#')
                                                ->withOnClick($modal_writer_notice->getShowSignal());
        $this->toolbar->addComponent($button_writer_notice);

        $this->toolbar->addSeparator();
        $button_export = $this->ui_factory->button()->standard(
            $this->plugin->txt("export_log"),
            $this->ctrl->getLinkTarget($this, 'exportLog')
        );
        $this->toolbar->addComponent($button_export);

        $protocol_factory = $this->plugin_ui_factory->protocol();
        $alerts = $this->alert_service->all();
        $writer_ids = array_unique(array_map(fn(Alert $alert) => $alert->getWriterId(), $alerts));
        $writer_id_user_id_map = [];

        foreach ($this->writer_service->all() as $writer) {
            if (in_array($writer->getId(), $writer_ids)) {
                $writer_id_user_id_map[$writer->getId()] = $writer->getUserId();
            }
        }
        $users = $this->user_service->getUsersByIds($writer_id_user_id_map);

        $items_alert = [];
        foreach ($alerts as $alert) {
            $user_id = $writer_id_user_id_map[$alert->getWriterId()] ?? null;

            $recipient = $user_id !== null
                ? ($users[$user_id] ?? null)?->getFullname(true) ?? " - "
                : $this->plugin->txt("alert_recipient_all");
            $items_alert[] = $protocol_factory->alert(
                $recipient,
                $alert->getMessage(),
                $alert->getShownFrom()
            );
        }

        $items_log_entry = [];
        foreach ($this->log_entry_service->all() as $log_entry)  {
            $items_log_entry[] = $protocol_factory->logEntry($log_entry->getCategory(), $log_entry->getEntry()?? "", $log_entry->getTimestamp());
        }

        $this->add(
            $protocol_factory->group($this->user->getDateTimeFormat())
                                     ->withAdditionalItems($items_alert)
                                     ->withAdditionalItems($items_log_entry)
        );
        $this->show();
    }

    private function createAlert()
    {
        if ($this->request->getMethod() == "POST") {
            $modal = $this->buildFormModalWriterNotice()
                          ->withRequest($this->request);
            $data = $modal->getData();

            // inputs are ok => save data
            if (is_array($data) && array_key_exists("text", $data) && array_key_exists("recipient", $data) && strlen($data["text"]) > 0) {
                $alert = $this->alert_service->new();
                $alert->setShownFrom(new \DateTimeImmutable('now'));
                $alert->setMessage($data['text']);

                if ($data['recipient'] != -1 && $this->writer_service->has($data['recipient'])) {
                    $alert->setWriterId($data['recipient']);
                }
                $this->alert_service->create($alert);

                $this->tpl->setOnScreenMessage("success", $this->plugin->txt("alert_created"), true);
                $this->ctrl->redirect($this, "showStartPage");
            } else {
                $ret = $this->ctrl->getLinkTarget($this);
                $close = new Signal((new \ILIAS\Data\UUID\Factory())->uuid4AsString());
                $modal = $modal->withOnClose($close)
                               ->withAdditionalOnLoadCode(fn ($id) => "$(document).on('$close', function() {window.location.replace('$ret');});");

                $this->add($modal->withOnLoad($modal->getShowSignal()));
                $this->showStartPage();
            }
        }
    }

    private function createLogEntry()
    {
        if ($this->request->getMethod() == "POST") {
            $modal = $this->buildFormModalLogEntry()
                          ->withRequest($this->request);
            $data = $modal->getData();

            // inputs are ok => save data
            if (is_array($data) && !empty($data['entry'])) {
                $this->log_entry_service->addEntry(
                    LogEntryType::NOTE,
                    MentionUser::fromSystem($this->dic->user()->getId()),
                    null,
                    $data['entry']
                );

                $this->tpl->setOnScreenMessage("success", $this->plugin->txt("log_entry_created"), true);
                $this->ctrl->redirect($this, "showStartPage");
            } else {
                $this->add($modal->withOnLoad($modal->getShowSignal()));
                $this->showStartPage();
            }
        }
    }

    private function buildFormModalWriterNotice() : Standard
    {
        $options = array_replace(
            ["-1" => $this->plugin->txt("alert_recipient_all")],
            $this->getWriterNameOptions()
        );

        $inputs = [
            "recipient" => $this->ui_factory->input()->field()
                                                     ->select($this->plugin->txt("alert_recipient"), $options)
                                                     ->withAdditionalTransformation($this->refinery->kindlyTo()->int())
                                                     ->withRequired(true),
            "text" => $this->ui_factory->input()->field()
                                                ->textarea($this->plugin->txt("alert_text"))
                                                ->withRequired(true)
        ];
        return $this->ui_factory->modal()->roundtrip(
            $this->plugin->txt("create_alert"),
            [],
            $inputs,
            $this->ctrl->getFormAction($this, "createAlert")
        )->withSubmitLabel(
            $this->lng->txt("send")
        );
    }

    private function buildFormModalLogEntry() : Standard
    {

        $inputs = [
            "entry" => $this->ui_factory->input()->field()->textarea($this->plugin->txt("log_entry_text"))->withRequired(true)
        ];

        return $this->ui_factory->modal()->roundtrip(
            $this->plugin->txt("create_log_entry"),
            [],
            $inputs,
            $this->ctrl->getFormAction($this, "createLogEntry")
        )->withSubmitLabel(
            $this->lng->txt("save")
        );
    }

    private function getWriterNameOptions(): array
    {
        $writers = [];
        foreach ($this->writer_service->all() as $writer) {
            $writers[$writer->getUserId()] = $writer;
        }

        $user_ids = array_map(function ($x) {
            return $x->getUserId();
        }, $writers);
        $out = [];

        foreach ($this->user_service->getUsersByIds($user_ids) as $usr_id => $user) {
            if (isset($writers[$usr_id])) {
                $out[(string)$writers[$usr_id]->getId()] = $user->getFullname(true);
            }
        }

        return $out;
    }

    private function exportLog()
    {
        // TODO: implement csv download
        #$filename = ilFileDelivery::returnASCIIFilename($this->plugin->txt('export_log_file_prefix') .' ' . $this->object->getTitle()) . '.csv';
        #$this->common_services->fileHelper()->deliverData($this->log_entry_service->createCsv(), $filename, 'text/csv');
    }
}
