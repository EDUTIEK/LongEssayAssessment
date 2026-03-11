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
use ILIAS\Plugin\LongEssayAssessment\UI as LocalUI;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\UI\Renderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Form;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Modal;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Confirmation;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Direct;
use Generator;
use ILIAS\UI\Implementation\Component\Signal;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Type;
use ILIAS\Session;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Export;
use ILIAS\FileDelivery\Services as FileDeliveryServices;
use ILIAS\Data\Range;
use ILIAS\UI\Implementation\Component\Table\Data as UIDataTable;

abstract class Table implements TableParent, FilterParent, Component\Component
{
    private string $title  = "";
    private ?Filter\Standard $filter = null;
    private ?Component\Component $table = null;
    /**
     * @var Component\Modal\Modal
     */
    private array $modal = [];
    /**
     * @var Filter\FilterInput[]
     */
    private array $filter_inputs = [];
    private bool $action_enabled = true;
    private ?array $filter_data = null;
    /**
     * @var Action[]
     */
    protected array $actions = [];
    public function __construct(
        protected readonly string $ui_name,
        protected TableParent|FilterParent $parent,
        protected URLBuilder $url_builder,
        protected URLBuilderToken $csrf_token,
        protected URLBuilderToken $row_id_token,
        protected URLBuilderToken $action_parameter_token,
        protected UI\Factory $ui_factory,
        protected LocalUI\Factory $local_factory,
        protected \ilUIService $ui_service,
        protected Renderer $renderer,
        protected Refinery\Factory $refinery,
        protected ArrayBasedRequestWrapper $query,
        protected ServerRequestInterface $request,
        protected FileDeliveryServices $delivery,
        protected \ilLongEssayAssessmentPlugin $plugin,
        protected \ilLanguage $lng
    ) {
        $this->initCSRFToken();
        $this->initActions($parent);
        if ($this->parent instanceof FilterParent) {
            $this->initFilter($parent);
        }
    }

    public function initCSRFToken()
    {
        if (!\ilSession::has('xlas_csrf')) {
            \ilSession::set('xlas_csrf', bin2hex(random_bytes(32)));
        }

        $token = \ilSession::get('xlas_csrf');
        $this->url_builder = $this->url_builder->withParameter($this->csrf_token, $token);
    }

    public function checkCSRFToken()
    {
        $csrf_token_uri = $this->query->has($this->csrf_token->getName())
            ? $this->query->retrieve(
                $this->csrf_token->getName(),
                $this->refinery->to()->string()
            )
            : "";
        $csrf_token_session = \ilSession::get('xlas_csrf');

        if ($csrf_token_uri !== $csrf_token_session) {
            throw new \ilCtrlException('Wrong CSRF token.');
        }
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

    protected function initActions(TableParent $parent)
    {
        foreach ($parent->getTableActions() as $action) {
            $this->actions[$action->name()] = $action;
        }
    }

    protected function initFilter(FilterParent $parent)
    {
        $this->filter_inputs = [];

        foreach (array_filter($parent->getFilterInputs()) as $key => $filter) {
            $this->filter_inputs[$key] = $filter;
        }
    }

    public function getActionByName(string $name): Action
    {
        if (array_key_exists($name, $this->actions)) {
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
        if ($this->hasActiveAction()) {
            $this->checkCSRFToken();
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
                case $action instanceof Export:
                    $this->export($action);
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
        if ($this->query->has($this->row_id_token->getName())) {
            return $this->query->retrieve(
                $this->row_id_token->getName(),
                $this->refinery->in()->series([
                    $this->refinery->custom()->transformation(function ($x) {
                        if (is_array($x) && $x[0] === "ALL_OBJECTS") {
                            return array_map(fn ($x) => $x->getId(), iterator_to_array($this->getTableItems()));
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

        if (!empty($array)) {
            return array_pop($array);
        }
        throw new \ilException("too few ids");
    }

    protected function getFilter() : Filter\Standard
    {
        if ($this->filter !== null) {
            return $this->filter;
        }

        $filter = $this->buildFilter();
        $this->filter = $filter;
        return $filter;
    }
    public function getFilterData(): array
    {
        if ($this->filter_data !== null) {
            return $this->filter_data;
        }

        $filter_gui = $this->getFilter();
        return $this->filter_data = $this->ui_service->filter()->getData($filter_gui) ?? [];
    }

    private function buildFilter() : Filter\Standard
    {
        return $this->ui_service->filter()->standard(
            $this->getUIName() . "_filter",
            $this->parent->getFilterBaseAction(),
            $this->getFilterInputs(),
            $this->getFilterInputActivation(),
            true,
            true
        )->withAdditionalOnLoadCode(function ($id) {
            return "$(document).ready(function () {
                        function updateMultiSelectedValues() {
                        console.log('updateMultiSelectedValues');
                        
                        $('.c-field-multiselect').each(function () {
                            var selected = [];
                            console.log($(this).closest('div.il-popover-container').find('label.input-group-addon').text());
                            
                            $(this).find('input[type=\"checkbox\"]:checked').each(function () {
                                var text = $(this).siblings('.c-field-multiselect__label-text').text();
                                console.log(text);
                                selected.push(text);
                            });
                            
                            $(this).closest('div.il-popover-container').find('span.il-filter-field').text(selected.join(', '));;
                        });
                    }
                
                    // Run on page load
                    updateMultiSelectedValues();
                
                    // Run after every checkbox change
                    $(document).on('change', '.c-field-multiselect input[type=\"checkbox\"]', function () {
                        updateMultiSelectedValues();
                    });
                
                });
            ";
        });
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
        if ($this->table !== null) {
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

        if (count($this->getFilterInputs()) > 0) {
            $components[] = $this->getFilter();
        }

        $components[] = $this->getTable();

        return array_merge($components, $this->getModal());
    }

    public function addActionToToolbar(\ilToolbarGUI $toolbar, Action $action, bool $primary = false) : void
    {
        $button = $primary ?
            $this->ui_factory->button()->primary($action->label(), "") :
            $this->ui_factory->button()->standard($action->label(), "");

        switch (true) {
            case $action instanceof Modal:
            case $action instanceof Form:
                $async_url = $this->url_builder->withParameter($this->action_parameter_token, $action->name())->withParameter($this->row_id_token, [0])->buildURI();
                $this->addModal($modal = $this->ui_factory->modal()->roundtrip($action->label(), [])->withAsyncRenderUrl($async_url));
                $toolbar->addComponent($button->withOnClick($modal->getShowSignal()));
                break;
        }
    }

    protected function form(Form $action) : void
    {
        $ids  = $this->currentIds();
        $items = iterator_to_array($this->getTableItems($ids));

        $link = $this->getActionFormLink();

        $fields = $action->fields($items);
        $content = $action->content($items);
        $action_buttons = $action->actionButtons($items);
        $transformations = $action->transformations($items);

        $modal = $this->ui_factory->modal()->roundtrip($action->label(), $content, $fields, $link)
            ->withActionButtons($action_buttons)
            ->withSubmitLabel($action->actionLabel());

        if (!empty($transformations)) {
            foreach ($transformations as $transformation) {
                $modal = $modal->withAdditionalTransformation($transformation);
            }
        }

        if ($this->request->getMethod() === "POST" || $action->type() === Type::Global) {
            // Reload Page when closing a modal to deter side effects:
            // 1) because of the reload warning after a POST
            // 2) a bug which breaks form modal if a form modal of type global was opened before

            $ret = $this->url_builder->buildURI()
                                     ->withParameter($this->action_parameter_token, null)
                                     ->withParameter($this->row_id_token, null)
                                     ->__toString();
            $close = new Signal((new \ILIAS\Data\UUID\Factory())->uuid4AsString());
            $modal = $modal->withOnClose($close)
                           ->withAdditionalOnLoadCode(fn ($id) => "$(document).on('$close', function() {window.location.replace('$ret');});");
        }

        //$form = $this->local_factory->field()->blankForm($link, $fields);

        if ($this->request->getMethod() === "POST" && $action->name() == $this->currentAction()->name()) {
            $modal = $modal->withOnLoad($modal->getShowSignal())
                         ->withRequest($this->request);

            if ($modal->getData() !== null) {
                $action->save($items, $modal->getData());
            } else {
                $this->addModal($modal);
            }
            return;
        }

        echo($this->renderer->renderAsync([
            $modal
        ]));
        exit();
    }

    protected function modal(Modal $action) : void
    {
        $ids  = $this->currentIds();
        $items = iterator_to_array($this->getTableItems($ids));

        $modal = $action->modal($items);

        if ($modal instanceof RoundTrip && $action->hasUpdateButton()) {
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
        $items = $this->getTableItems($ids);

        $confirmation_items = [];

        foreach ($items as $item) {
            if ($action->enabled($item)) {
                $confirmation_items[] = $this->ui_factory->modal()->interruptiveItem()->standard(
                    $item->getId(),
                    $action->itemName($item),
                    $action->itemIcon($item),
                    $action->itemDescription($item)
                );
            }
        }

        if (empty($confirmation_items)) {
            echo($this->renderer->renderAsync([
                $this->ui_factory->modal()->roundtrip(
                    $action->label(),
                    [$this->ui_factory->messageBox()->failure($this->plugin->txt("no_items"))]
                )
            ]));
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
        $items = iterator_to_array($this->getTableItems($ids));
        $action->action($items);
    }

    protected function export(Export $action)
    {
        $table = $this->getTable();
        $selected_columns = null;
        $selected_rows = $this->currentIds();
        $rows_all_selected = false;

        if ($table instanceof UIDataTable) {
            $columns = array_filter($table->getColumns(), fn ($x) => !$x instanceof Column\Image);
            $rows_all_selected = count($selected_rows ?? []) >= $table->getDataRetrieval()->getTotalRowCount($table->getFilter(), $table->getAdditionalParameters());
            $modal = $this->ui_factory->modal()->roundtrip(
                $action->label(),
                [],
                [
                    "rows" => $this->ui_factory->input()->field()->checkbox($this->plugin->txt('table_all_rows'), $this->plugin->txt('table_all_rows_info'))->withValue($rows_all_selected),
                    "columns" => $this->ui_factory->input()->field()->switchableGroup([
                        "visible" => $this->ui_factory->input()->field()->group([], $this->plugin->txt('table_select_columns_visible')),
                        "all" => $this->ui_factory->input()->field()->group([], $this->plugin->txt('table_select_columns_all')),
                        "selected" => $this->ui_factory->input()->field()->group(
                            array_map(
                                fn (Component\Table\Column\Column $c) =>
                                $this->ui_factory->input()->field()->checkbox($c->getTitle())->withValue(!$c->isOptional())->withDisabled(!$c->isOptional()),
                                $columns
                            ), $this->plugin->txt('table_select_columns_selected'))
                    ], $this->plugin->txt('table_select_columns'))->withValue("visible")
                ], $this->getActionFormLink()
            );

            if ($this->request->getMethod() === "POST") {
                $modal = $modal->withRequest($this->request);
                $data = $modal->getData();

                if (!empty($data["rows"])) {
                    $selected_rows = null;
                }
                if ($data["columns"][0] === "all") {
                    $selected_columns = array_keys($columns);
                }
                if ($data["columns"][0] === "selected") {
                    $selected_columns = array_keys(array_filter($data["columns"][1]));
                }
            } else {
                echo($this->renderer->renderAsync($modal));
                exit();
            }
        }
        $stream = $action->export($table, $selected_columns, $selected_rows, $rows_all_selected);

        $this->delivery->delivery()->attached(
            $stream,
            $action->getFilename() . $action->getExtension(),
            $action->getMimeType()
        );
    }

    # Table Parent fassade
    public function getTableActions() : array
    {
        return $this->actions;
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null) : Generator
    {
        return $this->parent->getTableItems($ids, $filter_data);
    }

    public function getTableItem(int $id) : Item
    {
        return $this->parent->getTableItem($id);
    }

    # FILTER PARENT fassade
    /**
     * @return Filter\FilterInput[]
     */
    public function getFilterInputs(): array
    {
        return $this->filter_inputs;
    }

    /**
     * @return bool[]
     */
    public function getFilterInputActivation(): array
    {
        if ($this->parent instanceof FilterParent && ($activation = $this->parent->getFilterInputActivation()) !== null) {
            return $activation;
        }

        return array_map(fn ($x) => true, $this->getFilterInputs());
    }

    public function getFilterBaseAction(): string
    {
        if ($this->parent instanceof FilterParent) {
            return $this->parent->getFilterBaseAction();
        }
        return "";
    }

    public function disableAction(bool $disable)
    {
        $this->action_enabled = !$disable;
    }

    public function isActionEnabled(): bool
    {
        return $this->action_enabled;
    }

    public function getCanonicalName(): string
    {
        return "XLAS_Table";
    }

}
