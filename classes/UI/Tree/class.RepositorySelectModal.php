<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Tree;

use ILIAS\UI\Implementation\Component\ReplaceSignal;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\Export\ImportStatus\Exception\ilException;
use ILIAS\UI\Implementation\Component\Button\Button;

class RepositorySelectModal
{
    private URLBuilder $url_builder;
    private URLBuilderToken $return_signal_token;
    private URLBuilderToken $ref_id_token;
    private URLBuilderToken $action_token;
    private ?string $message = null;
    private string $permission = 'maintain_task';
    private array $selectable_types = ['xlas'];
    private ?string $action_label = null;
    protected \ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper $query;

    public function __construct(
        protected \ILIAS\HTTP\Services $http,
        protected \ILIAS\Refinery\Factory $refinery,
        protected \ILIAS\UI\Factory $ui_factory,
        protected \ILIAS\UI\Renderer $renderer,
        protected \ilLanguage $lng,
        protected TreeFactory $tree_factory,
        protected \ilAccessHandler $access,
        protected int $ref_id,
        protected string $title,
        protected $view_callback,
        ?string $uri = null,
    ) {
        $this->query = $this->http->wrapper()->query();
        $df = new \ILIAS\Data\Factory();
        $here_uri = $df->uri($uri ?? $this->http->request()->getUri()->__toString());

        global $DIC;
        $query_params_namespace = ['xlas', 'copy'];
        $url_builder = new URLBuilder($here_uri);
        list($this->url_builder, $this->return_signal_token, $this->ref_id_token, $this->action_token) = $url_builder->acquireParameters($query_params_namespace, "return_signal", "ref_id", "action");
    }

    protected function getTitle(): string
    {
        return $this->title;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    protected function getMessage(): ?string
    {
        return $this->message;
    }

    protected function getView(int $ref_id): array|Component
    {
        return ($this->view_callback)($ref_id);
    }

    public function setPermission(string $permission): self
    {
        $this->permission = $permission;
        return $this;
    }

    protected function getPermission(): string
    {
        return $this->permission;
    }

    public function setSelectableTypes(array $types) : self
    {
        $this->selectable_types = $types;
        return $this;
    }

    protected function getSelectableTypes(): array
    {
        return $this->selectable_types;
    }

    public function setActionLabel(string $label): self
    {
        $this->action_label = $label;
        return $this;
    }

    protected function getActionLabel(): string
    {
        return $this->action_label ?? $this->lng->txt('copy');
    }

    public function hasSelected(): bool
    {
        $action_name = $this->action_token->getName();
        return $this->query->has($action_name) && $this->query->retrieve($action_name, $this->refinery->kindlyTo()->string()) === 'copy';
    }

    public function getSelectedId() : int
    {
        if(!$this->query->has($this->ref_id_token->getName()) || !$this->hasSelected()) {
            throw new \ilException("There wasn't a selection yet.");
        }

        return $this->query->retrieve($this->ref_id_token->getName(), $this->refinery->kindlyTo()->int());
    }

    public function showAsync()
    {
        if (!$this->query->has($this->query->has($this->return_signal_token->getName()))) {
            $replace_signal_str = $this->query->retrieve($this->return_signal_token->getName(), $this->refinery->kindlyTo()->string());
        } else {
            exit();
        }

        $replace_signal = new ReplaceSignal($replace_signal_str);

        switch($this->query->retrieve($this->action_token->getName(), $this->refinery->kindlyTo()->string())) {
            case "preview":
                $this->preview($replace_signal);
                break;
            case "tree":
                $this->tree($replace_signal);
                break;
            case "modal":
            default:
                $this->modal($replace_signal);
                break;
        }
    }

    protected function preview(ReplaceSignal $replace_signal)
    {
        $ref_id = null;
        if ($this->query->has($this->ref_id_token->getName())) {
            $ref_id = $this->query->retrieve($this->ref_id_token->getName(), $this->refinery->kindlyTo()->int());
        } else {
            exit();
        }
        $obj_id = \ilObject2::_lookupObjectId($ref_id);

        $title = $this->getTitle(). ": " . \ilObject2::_lookupTitle($obj_id);

        $back_link = $this->url_builder
            ->withParameter($this->ref_id_token, $ref_id)
            ->withParameter($this->action_token, 'modal')
            ->withParameter($this->return_signal_token, $replace_signal)->buildURI()->__toString();
        $copy_link = $this->url_builder
            ->withParameter($this->ref_id_token, $ref_id)
            ->withParameter($this->action_token, 'copy')->buildURI()->__toString();

        $components = [];

        if($this->getMessage()) {
            $components[] = $this->ui_factory->messageBox()->info($this->getMessage());
        }
        $contents = $this->getView($ref_id);
        if(is_array($contents)) {
            $components = array_merge($components, $contents);
        } else {
            $components[] = $contents;
        }

        $modal = $this->ui_factory->modal()->roundtrip(
            $title,
            $components
        )->withActionButtons([
            $this->ui_factory->button()->primary($this->getActionLabel(), $copy_link),
            $this->ui_factory->button()->standard($this->lng->txt('back'), "#")
                             ->withOnClick($replace_signal->withAsyncRenderUrl($back_link))
        ]);

        $this->send([$modal]);
    }

    protected function tree(ReplaceSignal $replace_signal)
    {
        $start_ref_id = null;
        $current_ref_id = null;

        $ref_id = null;
        if ($this->query->has($this->ref_id_token->getName())) {
            $start_ref_id = $this->query->retrieve($this->ref_id_token->getName(), $this->refinery->kindlyTo()->int());
        }

        $modal = $this->getModal($start_ref_id, $current_ref_id, true, $replace_signal);
        $this->send($modal->getContent());
    }

    protected function modal(ReplaceSignal $replace_signal)
    {
        $ref_id = null;
        if ($this->query->has($this->ref_id_token->getName())) {
            $ref_id = $this->query->retrieve($this->ref_id_token->getName(), $this->refinery->kindlyTo()->int());
        }
        $modal = $this->getModal(null, $ref_id, false, $replace_signal);

        $this->send([$modal]);
    }

    protected function getModal(
        ?int $start_ref_id = null,
        ?int $current_ref_id = null,
        bool $is_subtree = false,
        ?ReplaceSignal $replace_signal = null
    ): RoundTrip {
        $here = $this->ref_id;
        $current_ref_id = $current_ref_id ?? $here;

        $tree = $this->tree_factory->repository(
            $start_ref_id,
            $current_ref_id,
            $is_subtree
        );

        $tree->setVisibleTypes(array_merge($this->getSelectableTypes(), $tree->getRepoContainerTypes()));
        $tree->setClickableTypes($this->getSelectableTypes());

        $tree->setClickableCallback(function ($ref_id, $type) use ($here) {
            return $this->access->checkAccess($this->getPermission(), '', $ref_id, $type) && $ref_id !== $here;
        });

        $modal = $this->ui_factory->modal()->roundtrip($this->getTitle(), [
            $tree->getComponent()
        ]);
        if ($replace_signal === null) {
            $replace_signal = $modal->getReplaceSignal();
        }

        $tree->setExpandCallback(function ($ref_id) use ($replace_signal) {
            return $this->url_builder
                ->withParameter($this->action_token, "tree")
                ->withParameter($this->ref_id_token, $ref_id)
                ->withParameter($this->return_signal_token, $replace_signal)->buildURI()->__toString();
        });

        $tree->setOnclickCallback(function ($ref_id) use ($replace_signal) {
            return $this->url_builder
                ->withParameter($this->action_token, "preview")
                ->withParameter($this->ref_id_token, $ref_id)
                ->withParameter($this->return_signal_token, $replace_signal)->buildURI()->__toString();
        });

        $tree->setOnclickSignal($replace_signal);

        return $modal;
    }

    private function send(array $components)
    {
        $this->http->saveResponse($this->http->response()->withBody(
            Streams::ofString($this->renderer->renderAsync($components))
        ));
        $this->http->sendResponse();
        $this->http->close();
    }

    /**
     * @param string $action_title
     * @return array{0: \ILIAS\UI\Component\Button\Button, 1: Roundtrip}
     */
    public function getToolbarComponents(string $action_title) : array
    {
        $modal = $this->getModal();
        $btn = $this->ui_factory->button()->standard($action_title, "#")->withOnClick($modal->getShowSignal());
        return [$btn, $modal];
    }
}
