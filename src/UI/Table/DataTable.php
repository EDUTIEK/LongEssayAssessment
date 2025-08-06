<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

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
use ILIAS\Plugin\LongEssayAssessment\UI as LocalUI;
use ILIAS\Refinery;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\SmallView;
use ILIAS\UI\Component\Table\OrderingBinding;
use Closure;

class DataTable extends Table implements DataRetrieval, DataTableParent
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
        ServerRequestInterface $request,
        protected \ilLanguage $lng
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
            $request,
            $lng
        );
    }

    private array $additional_parameter = [];

    protected function buildTable(): \ILIAS\UI\Component\Component
    {
        $tf = $this->ui_factory->table();

        $table = $tf->data($this->getTitle(), $this->getColumns($this->getAdditionalParameter()), $this)
                    ->withId($this->getUIName() . "_table")
                    ->withRequest($this->request)
                    ->withAdditionalParameters($this->getAdditionalParameter());

        $small_view = $this->smallView($this->getAdditionalParameter());

        if (!empty($this->actions) && !$small_view && $this->isActionEnabled()) {
            $actions = $this->getDataTableActions();
            $table = $table->withActions($actions);
        }

        if (!empty($this->getFilterInputActivation())) {
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
        yield from new DataTableRowBuilder(
            $row_builder,
            $this->getTableItems(null, $filter_data),
            $this,
            $visible_column_ids,
            $range,
            $order,
            $this->actions,
            $additional_parameters
        );
    }

    protected function buildDataTabeActionByType(
        Action\Type $type,
        string $label,
        URLBuilder $url_builder,
        URLBuilderToken $row_id_parameter
    ): UIAction {
        $tf = $this->ui_factory->table()->action();
        switch ($type) {
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
    protected function getDataTableActions(): array
    {
        $data_actions = [];
        $tf = $this->ui_factory->table()->action();

        foreach ($this->actions as $action) {
            if ($action->type() == Action\Type::Global) {
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

    public function getAdditionalParameter(): array
    {
        return $this->additional_parameter;
    }

    public function setAdditionalParameter(array $additional_parameter): void
    {
        $this->additional_parameter = $additional_parameter;
    }

    # DATA TABLE PARENT fassade
    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        return $this->dt_parent->getTotalRowCount($filter_data, $additional_parameters);
    }

    public function getColumnMapping(Item $item, ?array $additional_parameters): array|\ArrayAccess
    {
        return $this->dt_parent->getColumnMapping($item, $additional_parameters);
    }

    /**
     * @return Column[]
     */
    public function getColumns(?array $additional_parameters): array
    {
        return $this->dt_parent->getColumns($additional_parameters);
    }

}
