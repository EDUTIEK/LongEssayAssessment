<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Protocol;

use ILIAS\Plugin\LongEssayAssessment\UI\Factory as PluginUIFactory;
use ILIAS\UI\Factory as UIFacotry;
use ILIAS\HTTP\Services as HttpService;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\Language\Language;
use Edutiek\AssessmentService\Assessment\LogEntry\Category as LogEntryCategory;
use DateTimeImmutable;
use ILIAS\Plugin\LongEssayAssessment\UI\IconFactory;
use ILIAS\Data\DateFormat\DateFormat;

class Factory
{
    public function __construct(
        private IconFactory $icon_factory,
        private UIFacotry $ui_factory,
        private \ilPlugin $plugin,
        private HttpService $http,
        private RefineryFactory $refinery
    ) {
    }
    public function group(DateFormat $date_format) : Group
    {
        return new Group(
            $this->icon_factory,
            $this->ui_factory,
            new class($this->plugin) implements Language {
                public function __construct(
                    private \ilPlugin $plugin
                ) {
                }

                public function txt(string $a_topic, string $a_default_lang_fallback_mod = ""): string
                {
                    return $this->plugin->txt($a_topic);
                }

                public function loadLanguageModule(string $a_module): void
                {
                }

                public function getLangKey(): string
                {
                    return "de";
                }

                public function toJS($key): void
                {
                }
            },
            $this->http,
            $this->refinery,
            $date_format
        );
    }

    public function alert(string $recipient, string $message, DateTimeImmutable $send_date): Alert
    {
        return new Alert($recipient, $message, $send_date);
    }

    public function logEntry(LogEntryCategory $category, string $entry, DateTimeImmutable $timestamp): LogEntry
    {
        return new LogEntry($category, $entry, $timestamp);
    }

}
