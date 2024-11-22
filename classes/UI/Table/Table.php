<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Action;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;
use ILIAS\UI;
use ILIAS\UI\Component as Component;
use ILIAS\UI\Component\Input\Container\Filter;
use ILIAS\Refinery;
use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation as LocalUI;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\UI\Renderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Form;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Modal;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Confirmation;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Direct;

abstract class Table
{
    private string $title  = "";
    private Filter\Standard $filter;
    private Component\Component $table;
    /**
     * @var Component\Modal\Modal
     */
    private array $modal = [];
    /**
     * @var Action[]
     */
    protected array $actions = [];
    public function __construct(
        private readonly string $ui_name,
        protected TableParent $parent,
        protected URLBuilder $url_builder,
        protected URLBuilderToken $row_id_token,
        protected URLBuilderToken $action_parameter_token,
        protected UI\Factory $ui_factory,
        protected LocalUI\Factory $local_factory,
        protected \ilUIService $ui_service,
        protected Renderer $renderer,
        protected Refinery\Factory $refinery,
        protected ArrayBasedRequestWrapper $query,
        protected ServerRequestInterface $request,
    ) {
        $this->initActions();
    }

    public function getUiName() : string
    {
        return $this->ui_name;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setTitle(string $title) : void
    {
        $this->title = $title;
    }

    private function initActions()
    {
        foreach ($this->parent->getTableActions() as $action) {
            $this->actions[$action->name()] = $action;
        }
    }

    protected function getActionByName(string $name): Action
    {
        if(array_key_exists($name, $this->actions)) {
            return $this->actions[$name];
        }
        throw new \ilException("Action '$name' not found");
    }

    public function hasActiveAction() : bool
    {
        return $this->query->has($this->action_parameter_token->getName());
    }

    protected function currentAction(): Action
    {
        if ($this->hasActiveAction()) {
            $action_name = $this->query->retrieve(
                $this->action_parameter_token->getName(),
                $this->refinery->to()->string()
            );
            return $this->getActionByName($action_name);
        } else {
            throw new \ilException("no current action");
        }
    }

    protected function getActionFormLink() : string
    {
        $action = $this->currentAction();
        $ids = $this->currentIds();
        return $this->url_builder->withParameter($this->action_parameter_token, $action->name())->withParameter($this->row_id_token, $ids)->buildURI()->__toString();
    }

    public function executeAction()
    {
        if($this->hasActiveAction()) {
            $action = $this->currentAction();
            switch (true) {
                case $action instanceof Direct:
                    $this->direct($action);
                    break;
                case $action instanceof Form:
                    $this->form($action);
                    break;
                case $action instanceof Modal:
                    $this->modal($action);
                    break;
                case $action instanceof Confirmation:
                    $this->confirmation($action);
                    break;
            }
        }
    }

    /**
     * @return int[]
     * @throws \ilException
     */
    public function currentIds(): array
    {
        if($this->query->has($this->row_id_token->getName())) {
            return $this->query->retrieve(
                $this->row_id_token->getName(),
                $this->refinery->in()->series([
                    $this->refinery->custom()->transformation(function ($x) {
                        if(is_array($x) && $x[0] === "ALL_OBJECTS") {
                            return array_map(fn ($x) => $x->getId(), iterator_to_array($this->parent->getTableItems()));
                        } else {
                            return $x;
                        }
                    }),
                    $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int())
                ])
            );
        } else {
            throw new \ilException("no ids");
        }
    }

    public function currentId(): int
    {
        $array = $this->currentIds();

        if(!empty($array)) {
            return array_pop($array);
        }
        throw new \ilException("too few ids");
    }

    protected function getFilter() : Filter\Standard
    {
        if($this->filter !== null) {
            return $this->filter;
        }

        $filter = $this->buildFilter();
        $this->filter = $filter;
        return $filter;
    }
    private function buildFilter() : Filter\Standard
    {
        return $this->ui_service->filter()->standard(
            $this->getUIName() . "_filter",
            $this->request->getUri()->__toString(),
            $this->getFilterInputs(),
            $this->getFilterInputActivation(),
            true,
            true
        );
    }

    /**
     * @return Filter\FilterInput[]
     */
    protected function getFilterInputs(): array
    {
        return [];
    }

    /**
     * @return bool[]
     */
    protected function getFilterInputActivation(): array
    {
        return array_map(fn ($x) => true, $this->getFilterInputs());
    }

    protected function addModal(Component\Modal\Modal $modal)
    {
        $this->modal[] = $modal;
    }

    /**
     * @return Component\Modal\Modal[]
     */
    protected function getModal() : array
    {
        return $this->modal;
    }

    public function getTable() : Component\Component
    {
        if($this->table !== null) {
            return $this->table;
        }

        $table = $this->buildTable();
        $this->table = $table;
        return $table;
    }
    abstract protected function buildTable() : Component\Component;

    /**
     * @return Component\Component[]
     */
    public function getComponents() : array
    {
        $components = [];

        if(count($this->getFilterInputActivation()) > 0) {
            $components[] = $this->getFilter();
        }

        $components[] = $this->getTable();

        return array_merge($components, $this->getModal());
    }

    protected function form(Form $action) : void
    {
        $ids  = $this->currentIds();
        $items = iterator_to_array($this->parent->getTableItems($ids));

        $link = $this->getActionFormLink();

        $fields = $action->fields($items);
        $content = $action->content($items);

        $form = $this->local_factory->field()->asyncForm($link, $fields);

        if($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if(!empty($data = $form->getData())) {
                $action->save($items, $data);
                exit();
            } else {
                echo($this->renderer->render(array_merge($content, [$form])));
                exit();
            }
        }

        $modal = $this->ui_factory->modal()->roundtrip($action->label(), array_merge($content, [$form]))->withActionButtons([
            $this->ui_factory->button()->primary($action->actionLabel(), "")->withOnClick($form->getSubmitAsyncSignal())
        ]);

        echo($this->renderer->renderAsync([
            $modal
        ]));
        exit();
    }

    protected function modal(Modal $action) : void
    {
        $ids  = $this->currentIds();
        $items = iterator_to_array($this->parent->getTableItems($ids));

        $modal = $action->modal($items);

        if($modal instanceof RoundTrip) {
            $link = $this->getActionFormLink();
            $reload_button = $this->ui_factory->button()->standard($this->lng->txt("refresh"), "")
                                             ->withLoadingAnimationOnClick(true)
                                             ->withOnLoadCode(
                                                 function ($id) use ($link) {
                                                     return
                                                         "$('#{$id}').click(function() { 
                                                        n_url = '{$link}';
                                                        text = $('#$id').html();
                                                        $('#$id').html('...');
                                                        il.UI.core.replaceContent($(this).closest('.modal').attr('id'), n_url, 'component');
                                                        $('#$id').html(text);
                                                        il.UI.button.deactivateLoadingAnimation('$id');
                                                        return false;
                                                    }
                                                );";
                                                 }
                                             );
            $modal = $modal->withActionButtons([$reload_button]);
        }

        echo($this->renderer->renderAsync($modal));
        exit();
    }

    protected function confirmation(Confirmation $action) : void
    {
        $ids  = $this->currentIds();
        $items = $this->parent->getTableItems($ids);

        $confirmation_items = [];

        foreach ($items as $item) {
            if($action->enabled($item)) {
                $confirmation_items[] = $this->ui_factory->modal()->interruptiveItem()->standard($item->getId(), $action->itemName($item));
            }
        }

        if(empty($confirmation_items)) {
            exit();
        }
        echo($this->renderer->renderAsync([
            $this->ui_factory->modal()->interruptive(
                $action->label(),
                $action->message(),
                $action->formAction()
            )->withAffectedItems($confirmation_items)->withActionButtonLabel($action->actionLabel())
        ]));
        exit();
    }

    protected function direct(Direct $action) : void
    {
        $ids = $this->currentIds();
        $items = $this->parent->getTableItems($ids);
        $action->action($items);
    }
}
