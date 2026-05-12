<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\UI;
use ILIAS\Refinery;
use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\UI\Renderer;
use ILIAS\UI\URLBuilder;
use ILIAS\Plugin\LongEssayAssessment\Dependencies\PluginDic;
use ILIAS\FileDelivery\Services as FileDeliveryServices;
use Edutiek\AssessmentService\System\Format\FullService as FormatService;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\Data;

class Factory
{
    private Action\Factory $action_factory;
    private \ILIAS\Data\Factory $data_factory;
    private Column\Factory $column_factory;

    public function __construct(
        protected PluginDic $pdic,
        protected UI\Factory $ui_factory,
        protected UI\Component\Table\Factory $ui_table_factory,
        protected \ilUIService $ui_service,
        protected Renderer $renderer,
        protected Refinery\Factory $refinery,
        protected ArrayBasedRequestWrapper $query,
        protected ServerRequestInterface $request,
        protected FileDeliveryServices $delivery,
        protected \ilLanguage $lng,
        protected FormatService $format_service
    ) {
        $this->action_factory = new Action\Factory();
        $this->data_factory = new \ILIAS\Data\Factory();
        $this->column_factory = new Column\Factory($this->lng, $this->format_service);
    }

    public function action(): Action\Factory
    {
        return $this->action_factory;
    }

    public function column(): Column\Factory
    {
        return $this->column_factory;
    }

    public function standard(
        string $title,
        array $columns,
        DataRetrieval $data_retrieval
    ): Data
    {
        return $this->ui_factory->table()->data($title, $columns, $data_retrieval);
    }

    public function dataTable(
        string $ui_name,
        DataTableParent $parent,
        ?string $uri = null
    ) {
        if ($uri !== null) {
            $table_uri = $this->data_factory->uri($uri);
        } else {
            $table_uri = $this->data_factory->uri($this->request->getUri()->__toString());
        }

        $url_builder = new URLBuilder($table_uri);
        $query_params_namespace = ["xlas", "actions"];

        list($url_builder, $csrf_token, $action_parameter_token, $row_id_token) =
            $url_builder->acquireParameters(
                $query_params_namespace,
                $ui_name . "_csrf",
                $ui_name . "_action",
                $ui_name . "_item"
            );

        return new DataTable(
            $ui_name,
            $parent,
            $url_builder,
            $csrf_token,
            $row_id_token,
            $action_parameter_token,
            $this->ui_factory,
            $this->ui_table_factory,
            $this->pdic->uiFactory(),
            $this->ui_service,
            $this->renderer,
            $this->refinery,
            $this->query,
            $this->request,
            $this->delivery,
            $this->pdic->plugin(),
            $this->lng
        );
    }

    public function formGroup(
        string $ui_name,
        FormGroupParent $parent,
        ?string $uri = null
    ) {
        if ($uri !== null) {
            $table_uri = $this->data_factory->uri($uri);
        } else {
            $table_uri = $this->data_factory->uri($this->request->getUri()->__toString());
        }

        $url_builder = new URLBuilder($table_uri);
        $query_params_namespace = ["xlas", "actions"];

        list($url_builder, $csrf_token, $action_parameter_token, $row_id_token) =
            $url_builder->acquireParameters(
                $query_params_namespace,
                $ui_name . "_csrf",
                $ui_name . "_action",
                $ui_name . "_item"
            );

        return new FormGroup($ui_name, $parent, $url_builder, $csrf_token, $row_id_token, $action_parameter_token, $this->ui_factory, $this->pdic->uiFactory(), $this->ui_service, $this->renderer, $this->refinery, $this->query, $this->request, $this->delivery, $this->pdic->plugin(), $this->lng);
    }
}
