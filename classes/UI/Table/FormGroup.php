<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormItem;
use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;
use ILIAS\UI;
use ILIAS\UI\Renderer;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation as LocalUI;
use ILIAS\Refinery;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;

class FormGroup extends Table
{
    public function __construct(
        string $ui_name,
        protected FormGroupParent $fg_parent,
        URLBuilder $url_builder,
        URLBuilderToken $row_id_token,
        URLBuilderToken $action_parameter_token,
        UI\Factory $ui_factory,
        LocalUI\Factory $local_factory,
        \ilUIService $ui_service,
        Renderer $renderer,
        Refinery\Factory $refinery,
        ArrayBasedRequestWrapper $query,
        ServerRequestInterface $request,
        protected \ilLanguage $lng
    ) {
        parent::__construct(
            $ui_name,
            $fg_parent,
            $url_builder,
            $row_id_token,
            $action_parameter_token,
            $ui_factory,
            $local_factory,
            $ui_service,
            $renderer,
            $refinery,
            $query,
            $request,
            $lng
        );
    }

    protected function getActionLink(Action\Action $action, ?Item $item = null): string
    {
        $url_builder = $this->url_builder->withParameter($this->action_parameter_token, $action->name());

        if($item !== null) {
            $url_builder = $url_builder->withParameter($this->row_id_token, $item->getId());
        }

        return (string) $url_builder->buildURI();
    }

    protected function getActionsForTable(\ILIAS\Plugin\LongEssayAssessment\UI\Implementation\FormGroup $table) : array
    {
        $action_buttons = [];
        $fbtn = $this->ui_factory->button();
        $fmod = $this->ui_factory->modal();

        foreach(array_filter($this->actions, fn ($x) => in_array($x->type(), [Action\Type::Multi, Action\Type::Standard])) as $action) {
            switch (true) {
                case $action instanceof Action\Form:
                case $action instanceof Action\Confirmation:
                case $action instanceof Action\Modal:
                    $callback_signal = $table->generateDSCallbackSignal();
                    $modal = $table->addDSModalTriggerToModal(
                        $fmod->roundtrip("", []),
                        $this->getActionLink($action),
                        $this->getUIName() . "_item",
                        $callback_signal
                    );

                    $action_buttons[] = $table->addDSModalTriggerToButton(
                        $fbtn->shy($action->label(), "#"),
                        $callback_signal
                    );
                    $this->addModal($modal);
                    break;
                case $action instanceof Action\Direct:
                    $callback_signal = $table->generateDSCallbackSignal();
                    $action_buttons[] = $table->addDSTriggerToButton(
                        $fbtn->shy($action->label(), "#"),
                        $this->getActionLink($action),
                        $this->getUIName() . "_item",
                        $callback_signal
                    );
                    break;
            }
        }

        return $action_buttons;
    }

    protected function getActionsForItem(Item $item) : array
    {
        $action_buttons = [];
        $fbtn = $this->ui_factory->button();
        $fmod = $this->ui_factory->modal();

        foreach(array_filter($this->actions, fn ($x) => $x->enabled($item) && in_array($x->type(), [Action\Type::Single, Action\Type::Standard])) as $action) {
            switch (true) {
                case $action instanceof Action\Form:
                case $action instanceof Action\Confirmation:
                case $action instanceof Action\Modal:
                    $modal = $fmod->roundtrip("", [])->withAsyncRenderUrl($this->getActionLink($action, $item));
                    $action_buttons[] = $fbtn->shy($action->label(), '')->withOnClick($modal->getShowSignal());
                    $this->addModal($modal);
                    break;
                case $action instanceof Action\Direct:
                    $actions[] = $fbtn->shy($action->label(), $this->getActionLink($action, $item));
                    break;
            }
        }

        return $action_buttons;
    }

    private function buildItem(Item $item): FormItem
    {
        return $this->fg_parent->buildItem($item);
    }

    protected function buildTable() : \ILIAS\UI\Component\Component
    {
        $items = [];
        $filter_data = [];

        if(!empty($this->getFilterInputActivation())) {
            $filter_gui = $this->getFilter();
            $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? [];
        }
        # $small_view = isset($additional_parameters["small_view"]) && (bool)$additional_parameters["small_view"];
        $small_view = false;

        foreach($this->parent->getTableItems(null, $filter_data) as $item) {
            $actions = $this->getActionsForItem($item);
            $form_item= $this->buildItem($item)->withName($item->getId());
            if(!empty($actions) && !$small_view) {
                $form_item = $form_item->withActions($this->ui_factory->dropdown()->standard($actions));
            }
            $items[] = $form_item;
        }

        if($small_view) {
            return $this->ui_factory->item()->group($this->getTitle(), $items);
        }

        $table = $this->local_factory->item()->formGroup(
            $this->getTitle(),
            $items,
            ""
        );
        $actions = $this->getActionsForTable($table);
        if(!empty($actions) && $this->isActionEnabled()) {
            $table = $table->withActions($this->ui_factory->dropdown()->standard($actions));
        }
        return $table;
    }
}
