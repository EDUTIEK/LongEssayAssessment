<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\UI;
use ILIAS\UI\Component\Input\Container\Filter;
use ILIAS\Refinery;
use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation as LocalUI;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\UI\Renderer;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;

class Factory
{
    private Action\Factory $action_factory;
    private \ILIAS\Data\Factory $data_factory;

    public function __construct(
        protected UI\Factory $ui_factory,
        protected LocalUI\Factory $local_factory,
        protected \ilUIService $ui_service,
        protected Renderer $renderer,
        protected Refinery\Factory $refinery,
        protected ArrayBasedRequestWrapper $query,
        protected ServerRequestInterface $request,
    ) {
        $this->action_factory = new Action\Factory();
        $this->data_factory = new \ILIAS\Data\Factory();
    }

    public function action() : Action\Factory
    {
        return $this->action_factory;
    }

    public function dataTable(
        string $ui_name,
        DataTableParent $parent,
        ?string $uri = null
    ) {
        if($uri !== null) {
            $table_uri = $this->data_factory->uri($uri);
        } else {
            $table_uri = $this->data_factory->uri($this->request->getUri()->__toString());
        }

        $url_builder = new URLBuilder($table_uri);
        $query_params_namespace = ["xlas", "actions"];

        list($url_builder, $action_parameter_token, $row_id_token) =
            $url_builder->acquireParameters(
                $query_params_namespace,
                $ui_name . "_action",
                $ui_name . "_item"
            );

        return new DataTable($ui_name, $parent, $url_builder, $row_id_token, $action_parameter_token, $this->ui_factory, $this->local_factory, $this->ui_service, $this->renderer, $this->refinery, $this->query, $this->request);
    }

    public function formGroup(
        string $ui_name,
        FormGroupParent $parent,
        ?string $uri = null
    ) {
        if($uri !== null) {
            $table_uri = $this->data_factory->uri($uri);
        } else {
            $table_uri = $this->data_factory->uri($this->request->getUri()->__toString());
        }

        $url_builder = new URLBuilder($table_uri);
        $query_params_namespace = ["xlas", "actions"];

        list($url_builder, $action_parameter_token, $row_id_token) =
            $url_builder->acquireParameters(
                $query_params_namespace,
                $ui_name . "_action",
                $ui_name . "_item"
            );

        return new FormGroup($ui_name, $parent, $url_builder, $row_id_token, $action_parameter_token, $this->ui_factory, $this->local_factory, $this->ui_service, $this->renderer, $this->refinery, $this->query, $this->request);
    }
}
