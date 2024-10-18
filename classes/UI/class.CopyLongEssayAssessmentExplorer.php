<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI;

use ILIAS\Repository\StandardGUIRequest;
use ILIAS\UI\Component\ReplaceSignal;

class CopyLongEssayAssessmentExplorer extends \ilTreeExplorerGUI
{
    protected \Closure $url_callback;
    protected ReplaceSignal $onclick;
    protected \ilObjLongEssayAssessment $object;
    protected \ilSetting $settings;
    protected \ilAccessHandler $access;
    protected \ilRbacSystem $rbacsystem;
    protected array $parent_node_id = [];
    protected array $node_data = [];
    protected StandardGUIRequest $request;
    protected int $cur_ref_id = 0;
    protected int $top_node_id;

    public function __construct(
        $a_parent_obj,
        string $a_parent_cmd,
        \ilObjLongEssayAssessment $a_object,
    ) {
        /** @var \ILIAS\DI\Container $DIC */
        global $DIC;

        $this->settings = $DIC->settings();
        $this->lng = $DIC->language();
        $this->access = $DIC->access();
        $this->rbacsystem = $DIC->rbac()->system();
        $this->request = $DIC->repository()->internal()->gui()->standardRequest();
        $this->object = $a_object;
        $this->cur_ref_id = $this->request->getRefId();
        $this->top_node_id = self::getTopNodeForRefId($this->cur_ref_id);

        parent::__construct("rep_exp", $a_parent_obj, $a_parent_cmd, $DIC->repositoryTree());

        $this->setSkipRootNode(false);
        $this->setNodeOpen($this->tree->readRootId());
        $this->setAjax(true);
        $this->setOrderField("title");
        $this->setTypeWhiteList(["cat", "crs", "grp", "fold", "xlas"]);

        if ($this->cur_ref_id > 0) {
            $this->setPathOpen($this->cur_ref_id);
        }

        $this->setChildLimit((int) $DIC->settings()->get("rep_tree_limit_number"));
    }

    public function getRootNode()
    {
        if ($this->top_node_id > 0) {
            $root_node = $this->getTree()->getNodeData($this->top_node_id);
        } else {
            $root_node = parent::getRootNode();
        }
        $this->node_data[$root_node["child"]] = $root_node;
        return $root_node;
    }

    public function getNodeContent($a_node): string
    {
        $lng = $this->lng;

        $title = $a_node["title"];

        if ($a_node["child"] == $this->getNodeId($this->getRootNode())) {
            if ($title === "ILIAS") {
                $title = $lng->txt("repository");
            }
        }
        if ($this->isNodeClickable($a_node)) {
            $title = "<span class='btn btn-link'>" . $title . "</span>";// Workaround to get the link style
        }

        return $title;
    }

    public function getNodeIcon($a_node): string
    {
        $obj_id = \ilObject::_lookupObjId((int) $a_node["child"]);
        return \ilObject::_getIcon($obj_id, "tiny", $a_node["type"]);
    }


    public function isNodeHighlighted($a_node): bool
    {
        if ((int) $a_node["child"] === $this->cur_ref_id ||
            ($this->cur_ref_id === 0 && (int) $a_node["child"] === (int) $this->getNodeId($this->getRootNode()))) {
            return true;
        }
        return false;
    }

    public function getNodeHref($a_node): string
    {
        return "#";
    }

    public function isNodeVisible($a_node): bool
    {
        $ilAccess = $this->access;
        $tree = $this->tree;
        $ilSetting = $this->settings;

        if (!$ilAccess->checkAccess('visible', '', $a_node["child"])) {
            return false;
        }

        return true;
    }


    public function getChildsOfNode($a_parent_node_id): array
    {
        $rbacsystem = $this->rbacsystem;

        if (!$rbacsystem->checkAccess("read", $a_parent_node_id)) {
            return [];
        }

        $obj_id = \ilObject::_lookupObjId($a_parent_node_id);
        if (!\ilConditionHandler::_checkAllConditionsOfTarget($a_parent_node_id, $obj_id)) {
            return [];
        }

        $childs = parent::getChildsOfNode($a_parent_node_id);

        foreach ($childs as $c) {
            $this->parent_node_id[$c["child"]] = $a_parent_node_id;
            $this->node_data[$c["child"]] = $c;
        }

        return $childs;
    }

    public function isNodeClickable($a_node): bool
    {
        if ($a_node['child'] == $this->cur_ref_id) {
            return false;
        }

        return $a_node["type"] === "xlas" && $this->object->canEditGrades();
    }

    public function build(
        \ILIAS\UI\Component\Tree\Node\Factory $factory,
        $record,
        $environment = null
    ): \ILIAS\UI\Component\Tree\Node\Node {
        $node =  parent::build($factory, $record, $environment);

        if ($this->isNodeHighlighted($record)) {
            $node = $node->withHighlighted(true);
        }
        if ($this->isNodeClickable($record)) {
            global $DIC;
            $callback = $this->url_callback;
            $node = $node->withOnClick($this->onclick->withAsyncRenderUrl($callback($record)));
        }

        return $node;
    }

    public static function getTopNodeForRefId(int $ref_id): int
    {
        global $DIC;

        $setting = $DIC->settings();
        $tree = $DIC->repositoryTree();

        $top_node = 0;
        if ($ref_id > 0 && $setting->get("rep_tree_limit_grp_crs")) {
            $path = $tree->getPathId($ref_id);
            foreach ($path as $n) {
                if ($top_node > 0) {
                    break;
                }
                if (in_array(
                    \ilObject::_lookupType(\ilObject::_lookupObjId($n)),
                    ["crs", "grp"]
                )) {
                    $top_node = $n;
                }
            }
        }
        return $top_node;
    }

    public function setOnclick(ReplaceSignal $signal, \Closure $url_callback)
    {
        $this->onclick = $signal;
        $this->url_callback = $url_callback;
    }
}
