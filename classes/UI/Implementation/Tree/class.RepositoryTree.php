<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation\Tree;

use ILIAS\UI\Component\Tree\TreeRecursion;
use ILIAS\UI\Component\Tree\Factory as TreeFactory;
use ILIAS\UI\Component\Symbol\Icon\Factory as IconFactory;
use ILIAS\UI\Component\Tree\Node\Factory as NodeFactory;
use ILIAS\UI\Component\Tree\Node\Node;
use ILIAS\UI\Component\ReplaceSignal;
use ILIAS\UI\Component\Tree\Tree;
use ILIAS\UI\Component\Symbol\Icon\Icon;

class RepositoryTree implements TreeRecursion
{
    protected TreeFactory $tree_factory;
    protected IconFactory $icon_factory;
    protected \ilTree $repo_tree;
    protected \ilAccessHandler $access;
    protected \ilLanguage $lng;

    protected ?int $start_ref_id = null;
    protected ?int $current_ref_id = null;
    protected bool $is_subtree = false;

    protected array $visible_types = [];
    protected array $clickable_types = [];

    protected ?\Closure $visible_callback = null;
    protected ?\Closure $clickable_callback = null;
    protected ?\Closure $expand_callback = null;
    protected ?\Closure $onclick_callback = null;
    protected ?ReplaceSignal $onclick_signal = null;

    protected $current_path = [];

    public function __construct(
        TreeFactory $tree_factory,
        IconFactory $icon_factory,
        \ilTree $repo_tree,
        \ilAccessHandler $access,
        \ilLanguage $lng,
        ?int $start_ref_id,
        ?int $current_ref_id,
        bool $is_subtree,
    ) {
        $this->tree_factory = $tree_factory;
        $this->icon_factory = $icon_factory;
        $this->repo_tree = $repo_tree;
        $this->access = $access;
        $this->lng = $lng;

        $this->start_ref_id = $start_ref_id ?? $this->repo_tree->getRootId();
        $this->current_ref_id = $current_ref_id;
        $this->is_subtree = $is_subtree;

        $this->visible_types = $this->getRepoContainerTypes();
    }

    /**
     * Set the list of visible object types
     * @param string[] $list
     */
    public function setVisibleTypes(array $list)
    {
        $this->visible_types = $list;
    }

    /**
     * Set the list of clickable object types
     * @param string[] $list
     */
    public function setClickableTypes(array $list)
    {
        $this->clickable_types = $list;
    }

    /**
     * Callback should return true if node is visible
     * The visible types and visible permission are already checked
     * @param Closure(int $ref_id, string $type): bool $callback
     */
    public function setVisibleCallback(\Closure $callback)
    {
        $this->visible_callback = $callback;
    }

    /**
     * Callback should return true if node is clickable
     * The clickable types and read permission are already checked
     * @param Closure(int $ref_id, string $type): bool $callback
     */
    public function setClickableCallback(\Closure $callback)
    {
        $this->clickable_callback = $callback;
    }

    /**
     * Callback should return async url to expand a node
     * @param Closure(int $ref_id): string $callback
     */
    public function setExpandCallback(\Closure $callback)
    {
        $this->expand_callback = $callback;
    }

    /**
     * Callback should return async url to be called when a node is clicked
     * @param Closure(int $ref_id): string $callback
     */
    public function setOnclickCallback(\Closure $callback)
    {
        $this->onclick_callback = $callback;
    }

    /**
     * Signal is sent when a node is clicked
     */
    public function setOnclickSignal(ReplaceSignal $signal)
    {
        $this->onclick_signal = $signal;
    }

    /**
     * @return string[]
     */
    public function getRepoContainerTypes() : array
    {
        return ['root', 'cat', 'crs', 'grp', 'fold', 'lso', 'prg'];
    }

    /**
     * Get the Tree UI component
     */
    public function getComponent(): Tree
    {
        if ($this->current_ref_id !== null) {
            $this->current_path = $this->repo_tree->getPathId($this->current_ref_id);
        }

        if ($this->is_subtree) {
            $records = [];
            foreach ($this->repo_tree->getChilds($this->start_ref_id) as $record) {
                if ($this->isVisible((int) $record['ref_id'], (string) $record['type'])) {
                    $records[] = $record;
                }
            }
            return $this->tree_factory->expandable('', $this)
                    ->withData($records)->withIsSubTree(true);
        } else {
            $record = $this->repo_tree->getNodeData($this->start_ref_id);
            return $this->tree_factory->expandable($record['title'], $this)
                    ->withData([$record]);
        }
    }

    /**
     * Recursively called when the tree component is rendered
     * This should fill all nodes on the path to the current node
     */
    public function getChildren($record, $environment = null): array
    {
        if (!$this->is_subtree) {
            $ref_id = (int) $record['ref_id'];

            $records = [];
            if (in_array($ref_id, $this->current_path)) {
                foreach ($this->repo_tree->getChilds($ref_id) as $record) {
                    if ($this->isVisible((int) $record['ref_id'], (string) $record['type'])) {
                        $records[] = $record;
                    }
                }
            }
            return $records;
        }
        return [];
    }

    /**
     * Recursively called when the tree component is rendered
     */
    public function build(
        NodeFactory $factory,
        $record,
        $environment = null
    ): Node {
        $ref_id = (int) $record['ref_id'];
        $obj_id = (int) $record['obj_id'];
        $type = (string) $record['type'];
        $title = (string) $record['title'];

        $clickable = $this->isClickable($ref_id, $type);

        if ($ref_id == $this->repo_tree->getRootId() && $title === "ILIAS") {
            $title = $this->lng->txt("repository");
        }

        if ($clickable) {
            $title = "<span class='btn btn-link'>" . $title . "</span>";// Workaround to get the link style
        }

        $node = $factory->simple($title, $this->getIcon($obj_id, $type))
                        ->withHighlighted($ref_id == $this->current_ref_id)
                        ->withExpanded($ref_id !== $this->current_ref_id && in_array($ref_id, $this->current_path));

        if (in_array($type, $this->getRepoContainerTypes())
            && $this->expand_callback !== null
            && ($this->is_subtree || !in_array($ref_id, $this->current_path)) // current path is already rendered
        ) {
            $node = $node->withAsyncURL(($this->expand_callback)($ref_id));
        }

        if ($clickable && $this->onclick_signal !== null) {
            $signal = $this->onclick_signal;
            if ($this->onclick_callback !== null) {
                $signal = $signal->withAsyncRenderUrl(($this->onclick_callback)($ref_id));
            }

            $node = $node->withOnClick($signal);
        }

        return $node;
    }

    protected function isVisible(int $ref_id, string $type)
    {
        $visible = in_array($type, $this->visible_types)
            && $this->access->checkAccess('visible', '', $ref_id, $type);

        if ($visible && $this->visible_callback !== null) {
            return ($this->visible_callback)($ref_id, $type);
        }

        return $visible;
    }

    protected function isClickable(int $ref_id, string $type)
    {
        $clickable = in_array($type, $this->clickable_types)
            && $this->access->checkAccess('read', '', $ref_id, $type);

        if ($clickable && $this->clickable_callback !== null) {
            return ($this->clickable_callback)($ref_id, $type);
        }

        return $clickable;
    }

    protected function getIcon(int $obj_id, string $type): ?Icon
    {
        $path = \ilObject::_getIcon($obj_id, "tiny", $type);
        if ($path !== '') {
            return $this->icon_factory->custom($path, '');
        }
        return null;
    }
}
