<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\Column\Column;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;
use ILIAS\UI\Component\Table\Action\Action as UIAction;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Type;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;
use ILIAS\UI;
use ILIAS\UI\Renderer;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation as LocalUI;
use ILIAS\Refinery;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\SmallView;

class DataTable extends Table implements DataRetrieval
{
    use SmallView;
    public function __construct(
        string $ui_name,
        protected DataTableParent $dt_parent,
        URLBuilder $url_builder,
        URLBuilderToken $row_id_token,
        URLBuilderToken $action_parameter_token,
        UI\Factory $ui_factory,
        LocalUI\Factory $local_factory,
        \ilUIService $ui_service,
        Renderer $renderer,
        Refinery\Factory $refinery,
        ArrayBasedRequestWrapper $query,
        ServerRequestInterface $request
    ) {
        parent::__construct(
            $ui_name,
            $dt_parent,
            $url_builder,
            $row_id_token,
            $action_parameter_token,
            $ui_factory,
            $local_factory,
            $ui_service,
            $renderer,
            $refinery,
            $query,
            $request
        );
    }

    private array $additional_parameter = [];

    private function getColumnMapping(Item $item, ?array $additional_parameters): array
    {
        return $this->dt_parent->getColumnMapping($item, $additional_parameters);
    }

    /**
     * @return Column[]
     */
    private function getColumns(?array $additional_parameters): array
    {
        return $this->dt_parent->getColumns($additional_parameters);
    }

    protected function buildTable(): \ILIAS\UI\Component\Component
    {
        $tf = $this->ui_factory->table();

        $table = $tf->data($this->getTitle(), $this->dt_parent->getColumns($this->getAdditionalParameter()), $this)
                    ->withId($this->getUIName() . "_table")
                    ->withRequest($this->request)
                    ->withAdditionalParameters($this->getAdditionalParameter());

        $small_view = $this->smallView($this->getAdditionalParameter());

        if(!empty($this->actions) && ! $small_view) {
            $actions = $this->getDataTableActions();
            $table = $table->withActions($actions);
        }

        if(!empty($this->getFilterInputActivation())) {
            $filter_gui = $this->getFilter();
            $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? [];
            $table = $table->withFilter($filter_data);
        }

        return $table;
    }

    public function getRows(
        DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): \Generator {
        foreach ($this->parent->getTableItems(null, $filter_data) as $item) {
            $row = $row_builder->buildDataRow($item->getId(), $this->getColumnMapping($item, $additional_parameters));
            foreach(array_filter($this->actions, fn (Action\Action $x) => in_array($x->type(), [Action\Type::Standard, Action\Type::Single])) as $action) {
                $row = $row->withDisabledAction($action->name(), !$action->enabled($item));
            }
            yield $row;
        }
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters) : ?int
    {
        return $this->dt_parent->getTotalRowCount($filter_data, $additional_parameters);
    }

    protected function buildDataTabeActionByType(
        Action\Type $type,
        string $label,
        URLBuilder $url_builder,
        URLBuilderToken $row_id_parameter
    ): UIAction {
        $tf = $this->ui_factory->table()->action();
        switch($type) {
            case Action\Type::Single:
                return $tf->single($label, $url_builder, $row_id_parameter);
            case Action\Type::Multi:
                return $tf->multi($label, $url_builder, $row_id_parameter);
            case Action\Type::Standard:
            default:
                return $tf->standard($label, $url_builder, $row_id_parameter);
        }
    }

    /**
     * @return UIAction[]
     */
    protected function getDataTableActions() : array
    {
        $data_actions = [];
        $tf = $this->ui_factory->table()->action();

        foreach ($this->actions as $action) {
            if($action->type() == Action\Type::Global) {
                continue;
            }
            switch (true) {
                case $action instanceof Action\Direct:
                    $data_actions[$action->name()] = $this->buildDataTabeActionByType(
                        $action->type(),
                        $action->label(),
                        $this->url_builder->withParameter($this->action_parameter_token, $action->name()),
                        $this->row_id_token
                    );
                    break;
                case $action instanceof Action\Form:
                case $action instanceof Action\Modal:
                case $action instanceof Action\Confirmation:
                    $data_actions[$action->name()] = $this->buildDataTabeActionByType(
                        $action->type(),
                        $action->label(),
                        $this->url_builder->withParameter($this->action_parameter_token, $action->name()),
                        $this->row_id_token
                    )->withAsync(true);
                    break;
            }
        }
        return $data_actions;
    }

    public function getAdditionalParameter() : array
    {
        return $this->additional_parameter;
    }

    public function setAdditionalParameter(array $additional_parameter) : void
    {
        $this->additional_parameter = $additional_parameter;
    }

}
