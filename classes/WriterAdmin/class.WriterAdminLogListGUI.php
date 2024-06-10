<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\Data\Task\Alert;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\LogEntry;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;

class WriterAdminLogListGUI
{
    const MODE_ATTR = "mode";
    const PAGE_ATTR = "page";
    const PAGE_SIZE = 10;
    /**
     * @var mixed[]
     */
    protected $entries = [];

    protected $user_ids = [];

    protected $user_data = [];

    /**
     * @var Writer[]
     */
    protected $writer = [];


    protected \ILIAS\UI\Factory $uiFactory;
    protected \ilCtrl $ctrl;
    protected \ilLongEssayAssessmentPlugin $plugin;
    protected \ILIAS\UI\Renderer $renderer;
    protected object $parent;
    protected string $parent_cmd;
    protected int $task_id;

    public function __construct(object $parent, string $parent_cmd, \ilLongEssayAssessmentPlugin $plugin, $task_id)
    {
        global $DIC;
        $this->parent = $parent;
        $this->parent_cmd = $parent_cmd;
        $this->uiFactory = $DIC->ui()->factory();
        $this->ctrl = $DIC->ctrl();
        $this->plugin = $plugin;
        $this->renderer = $DIC->ui()->renderer();
        $this->task_id = $task_id;
    }


    private function buildAlert(Alert $alert)
    {
        $recipient = "";
        $custom_factory = LongEssayAssessmentDI::getInstance()->getUIFactory();

        if($alert->getWriterId() !== null) {
            $id = -1;
            if(array_key_exists($alert->getWriterId(), $this->writer)) {
                $id = $this->writer[$alert->getWriterId()]->getUserId();
            }
            $recipient = $this->getUsername($id);
        } else {
            $recipient = $this->plugin->txt("alert_recipient_all");
        }

        return $this->uiFactory->item()->standard(nl2br($alert->getMessage()))
            ->withLeadIcon($custom_factory->icon()->appr('alert', 'medium'))
            ->withProperties(array(
                $this->plugin->txt("log_type") => $this->plugin->txt("log_type_alert"),
                $this->plugin->txt("alert_send") => $this->getFormattedTime($alert->getShownFrom()),
                $this->plugin->txt("alert_recipient") => $recipient

            ));
    }

    private function buildLogEntry(LogEntry $log_entry)
    {
        $custom_factory = LongEssayAssessmentDI::getInstance()->getUIFactory();
        switch($log_entry->getCategory()) {
            case LogEntry::CATEGORY_EXCLUSION:
                $icon = $custom_factory->icon()->disq('exclusion', 'medium');
                break;
            case LogEntry::CATEGORY_AUTHORIZE:
                $icon = $custom_factory->icon()->appr('authorize', 'medium');
                break;
            case LogEntry::CATEGORY_EXTENSION:
                $icon = $custom_factory->icon()->time('extension', 'medium');
                break;
            case LogEntry::CATEGORY_NOTE:
                $icon = $custom_factory->icon()->nots('note', 'medium');
                break;
            default:
                $icon = $this->uiFactory->symbol()->icon()->standard('nots', 'notes', 'medium');
                break;
        }

        return $this->uiFactory->item()->standard(nl2br($this->replaceUserIDs($log_entry->getEntry())))
            ->withLeadIcon($icon)
            ->withProperties(array(
                $this->plugin->txt("log_type") => $this->plugin->txt("log_type_" . $log_entry->getCategory()),
                $this->plugin->txt("log_entry_entered") => $this->getFormattedTime($log_entry->getTimestamp()),
            ));
    }

    public function getContent() :string
    {
        $this->loadUserData();
        try {
            $this->sortEntries();
        } catch (\ilDateTimeException $e) {
        }

        $items = [];
        $count = 0;
        $start = $this->getActualPage() * self::PAGE_SIZE;
        $end = ($this->getActualPage() * self::PAGE_SIZE) + self::PAGE_SIZE;

        foreach($this->entries as $key => $entry) {
            $count ++;
            if($count <= $start || $count > $end) {
                continue;
            }
            if($entry instanceof Alert) {
                $items[] = $this->buildAlert($entry);
            } elseif ($entry instanceof LogEntry) {
                $items[] = $this->buildLogEntry($entry);
            }
        }

        $resources = $this->uiFactory->item()->group($this->plugin->txt("log_entries"), $items);

        return $this->renderer->render(array_merge(
            [$this->buildModeControl(), $this->uiFactory->legacy("</br></br>")],
            $this->surroundWithPagination($resources)
        ));
    }

    private function surroundWithPagination($component)
    {

        if (count($this->entries) > self::PAGE_SIZE) {
            $uis = [];
            $pagination = $this->uiFactory->viewControl()->pagination()
                ->withTargetURL($this->ctrl->getLinkTarget($this->parent, $this->parent_cmd), self::PAGE_ATTR)
                ->withTotalEntries(count($this->entries))
                ->withPageSize(self::PAGE_SIZE)
                ->withCurrentPage($this->getActualPage());

            $uis[] = $pagination;
            if(is_array($component)) {
                foreach ($component as $subcomp) {
                    $uis[] = $subcomp;
                }
            } else {
                $uis[] = $component;
            }

            $uis[] = $pagination;
            return $uis;
        }
        return [$component];
    }

    private function buildModeControl()
    {
        $target = $this->ctrl->getLinkTarget($this->parent, $this->parent_cmd);
        $param = self::MODE_ATTR;

        $active = $this->getActualMode();

        $modes = ["all", "alert", "note", "exclusion", "extension", "authorize"];
        $actions = [];

        foreach($modes as $mode) {
            $actions[$this->plugin->txt("log_type_" . $mode)] = "$target&$param=$mode";
        }

        $aria_label = "change_the_currently_displayed_mode";
        return $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive($this->plugin->txt("log_type_" . $active));
    }

    /**
     * @param LogEntry[] $log_entries
     * @return void
     */
    public function addLogEntries(array $log_entries)
    {
        foreach ($log_entries as $log_entry) {
            if(!in_array($this->getActualMode(), ["all", $log_entry->getCategory()])) {
                continue;
            }
            // use timestamp for sorting, add id to include all with same timestamp
            $this->entries[$log_entry->getTimestamp() . 'log' . $log_entry->getId()] = $log_entry;
            $this->user_ids= array_merge($this->user_ids, $this->parseUserIDs($log_entry->getEntry()));
        }
    }

    /**
     * @param Alert[] $alerts
     * @return void
     */
    public function addAlerts(array $alerts)
    {
        if(!in_array($this->getActualMode(), ["all", "alert"])) {
            return;
        }

        $writer_ids = [];

        foreach ($alerts as $alert) {
            $this->entries[$alert->getShownFrom(). 'alert'. $alert->getId()] = $alert;
            if($alert->getWriterId() !== null) {
                $writer_ids[] = $alert->getWriterId();
            }
        }

        $writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
        $user_ids = [];

        foreach ($writer_repo->getWritersByTaskId($this->task_id, $writer_ids) as $writer) {
            $this->writer[$writer->getId()] = $writer;
            $user_ids[] = $writer->getUserId();
        }

        $this->user_ids = array_merge($this->user_ids, $user_ids);
    }

    /**
     * @return void
     */
    private function sortEntries()
    {
        krsort($this->entries);
    }

    /**
     * @param ?string $text
     * @return int[]
     */
    private function parseUserIDs(?string $text): array
    {
        if($text === "" || $text === null) {
            return [];
        }

        $output_array = [];

        preg_match_all('/\[user=(\d+)\]/', $text, $output_array);

        return array_map('intval', $output_array[1]);
    }

    /**
     * @param ?string $text
     * @return array|string|string[]|null
     */
    private function replaceUserIDs(?string $text)
    {
        if($text === "" || $text === null) {
            return "";
        }

        return preg_replace_callback(
            '/\[user=(\d+)\]/',
            function ($matches) {
                return $this->getUsername($matches[1], true);
            },
            $text
        );
    }

    /**
     * Get Username
     *
     * @param $user_id
     * @return mixed|string
     */
    protected function getUsername($user_id, $strip_img = false)
    {
        if(isset($this->user_data[$user_id])) {
            if($strip_img) {
                return strip_tags($this->user_data[$user_id], ["a"]);
            } else {
                return $this->user_data[$user_id];
            }
        }
        return ' - ';
    }

    /**
     * Load needed Usernames From DB
     * @return void
     */
    protected function loadUserData()
    {
        $back = $this->ctrl->getLinkTarget($this->parent);
        $this->user_data = \ilUserUtil::getNamePresentation(array_unique($this->user_ids), true, true, $back, true);
    }

    /**
     * @param string $timestamp
     * @return string
     */
    protected function getFormattedTime($timestamp): string
    {
        try {
            return \ilDatePresentation::formatDate(
                new \ilDateTime($timestamp, IL_CAL_DATETIME)
            );
        } catch (\ilDateTimeException $e) {
            return " - ";
        }
    }

    private function getActualPage()
    {
        return $_GET[self::PAGE_ATTR] ?? 0;
    }

    private function getActualMode()
    {
        return $_GET[self::MODE_ATTR] ?? "all";
    }

}
