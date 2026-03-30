<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Protocol;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Factory as UIFacotry;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use ILIAS\Language\Language as LanguageService;
use ILIAS\Data\DateFormat\DateFormat;
use Edutiek\AssessmentService\Assessment\LogEntry\Category as LogEntryCategory;
use ILIAS\UI\Component\Item\Item as GroupItem;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface ;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;
use ILIAS\HTTP\Services as HttpService;
use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper as QueryService;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\IconFactory;

class Group implements Component
{
    public const MODE_ATTR = "mode";
    public const PAGE_ATTR = "page";
    public const PAGE_SIZE = 10;

    /**
     * @var Item[]
     */
    private array $items = [];
    private URLBuilderToken $page_token;
    private URLBuilderToken $mode_token;
    private URLBuilder $url_builder;
    private QueryService $query;
    private ?EntryType $mode = null;

    public function __construct(
        private IconFactory $icon_factory,
        private UIFacotry $ui_factory,
        private LanguageService $lng,
        HttpService $http,
        private RefineryFactory $refinery,
        private DateFormat $date_format
    ) {
        $df = new \ILIAS\Data\Factory();
        $uri = $df->uri($http->request()->getUri()->__toString());

        $url_builder = new URLBuilder($uri);
        $query_params_namespace = ["xlas", "protocol"];

        list($url_builder, $mode_token, $page_token) =
            $url_builder->acquireParameters(
                $query_params_namespace,
                "mode",
                "page"
            );

        $this->page_token = $page_token;
        $this->mode_token = $mode_token;
        $this->url_builder = $url_builder;
        $this->query = $http->wrapper()->query();
    }

    public function getCanonicalName(): string
    {
        return 'ProtocolGroup';
    }

    public function withAdditionalItems(array $items): self
    {
        $clone = clone $this;

        $mode = $this->getCurrentMode();
        if ($mode !== EntryType::ALL) {
            $items = array_filter($items, fn(Item $item) => $item->type() == $mode);
        }

        $clone->items = array_merge($this->items, $items);
        return $clone;
    }

    /**
     * @return Component[]
     */
    public function getComponents(): array
    {
        usort($this->items, function (Item $a, Item $b) {
            return $b->sortBy()->getTimestamp() - $a->sortBy()->getTimestamp();
        });

        $current_page = $this->getCurrentPage();

        $items = [];
        $count = 0;
        $start = $current_page * self::PAGE_SIZE;
        $end = ($current_page * self::PAGE_SIZE) + self::PAGE_SIZE;

        foreach ($this->items as $item) {
            $count++;
            if ($count <= $start || $count > $end) {
                continue;
            }
            if ($item instanceof Alert) {
                $items[] = $this->buildAlert($item);
            } elseif ($item instanceof LogEntry) {
                $items[] = $this->buildLogEntry($item);
            }
        }

        $resources = $this->ui_factory->item()->group($this->lng->txt("log_entries"), $items);

        return array_merge(
            [$this->buildModeControl(), $this->ui_factory->legacy("</br></br>")],
            $this->surroundWithPagination($resources)
        );
    }

    private function buildAlert(Alert $alert): GroupItem
    {
        $icon_factory = $this->icon_factory;
        $recipient = $alert->getRecipient() !== null
            ? $alert->getRecipient()
            : $this->lng->txt("alert_recipient_all");

        return $this->ui_factory->item()->standard($alert->getMessage())
                               ->withLeadIcon($icon_factory->appr('alert', 'medium'))
                               ->withProperties(array(
                                   $this->lng->txt("log_type") => $this->lng->txt("log_type_alert"),
                                   $this->lng->txt("alert_send") => $this->date_format->applyTo($alert->getSendDate()),
                                   $this->lng->txt("alert_recipient") => $recipient
                               ));
    }

    private function buildLogEntry(LogEntry $log_entry): GroupItem
    {
        $icon_factory = $this->icon_factory;
        $icon = match($log_entry->getCategory()) {
            LogEntryCategory::EXCLUSION => $icon_factory->disq('exclusion', 'medium'),
            LogEntryCategory::AUTHORIZE => $icon_factory->appr('authorize', 'medium'),
            LogEntryCategory::WORKING_TIME => $icon_factory->time('working_time', 'medium'),
            LogEntryCategory::NOTE => $icon_factory->nots('note', 'medium'),
            default => $this->ui_factory->symbol()->icon()->standard('nots', 'notes', 'medium')
        };

        return $this->ui_factory->item()->standard($log_entry->getEntry())
                               ->withLeadIcon($icon)
                               ->withProperties(array(
                                   $this->lng->txt("log_type") => $this->lng->txt("log_type_" . $log_entry->getCategory()->value),
                                   $this->lng->txt("log_entry_entered") => $this->date_format->applyTo($log_entry->getTimestamp()),
                               ));
    }

    private function surroundWithPagination($component)
    {
        if (count($this->items) > self::PAGE_SIZE) {
            $uis = [];
            $pagination = $this->ui_factory->viewControl()->pagination()
                                          ->withTargetURL($this->url_builder->buildURI()->__toString(), $this->page_token->getName())
                                          ->withTotalEntries(count($this->items))
                                          ->withPageSize(self::PAGE_SIZE)
                                          ->withCurrentPage($this->getCurrentPage());

            $uis[] = $pagination;
            if (is_array($component)) {
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
        $active = $this->getCurrentMode()->value;
        $modes = array_column(EntryType::cases(), 'value');
        $actions = [];

        foreach ($modes as $mode) {
            $actions[$this->lng->txt("log_type_" . $mode)] = $this->url_builder->withParameter($this->mode_token, $mode)->buildURI()->__toString();
        }

        $aria_label = $this->lng->txt("change_the_currently_displayed_mode");

        return $this->ui_factory->viewControl()
                                ->mode($actions, $aria_label)
                                ->withActive($this->lng->txt("log_type_" . $active));
    }

    private function getCurrentPage(): int
    {
        $page = "";
        if ($this->query->has($this->page_token->getName())) {
            $page = $this->query->retrieve($this->page_token->getName(), $this->refinery->to()->string());
        }
        return !empty($page) ? intval($page) : 0;
    }

    private function getCurrentMode(): EntryType
    {
        if ($this->mode !== null) {
            return $this->mode;
        }
        $mode = $this->query->has($this->mode_token->getName())
            ? $this->query->retrieve($this->mode_token->getName(), $this->refinery->kindlyTo()->string())
            : EntryType::ALL->value;

        if (EntryType::tryFrom($mode) !== null) {
            \ilSession::set('long_essay_assessment_protocol_mode', $mode);
        } else {
            $mode = (string) \ilSession::get('long_essay_assessment_protocol_mode');
        }

        return $this->mode = (EntryType::tryFrom($mode) ?? EntryType::ALL);
    }

}
