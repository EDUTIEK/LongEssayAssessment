<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation\Tree;

use ILIAS\UI\Component\Tree\Factory as UiTreeFactory;
use ILIAS\UI\Component\Symbol\Icon\Factory as UiIconFactory;

class TreeFactory
{
    private UiTreeFactory $tree_factory;
    private UiIconFactory $icon_factory;
    private \ilTree $repo_tree;
    private \ilAccessHandler $access;
    private \ilLanguage $lng;

    public function __construct(
        UiTreeFactory $tree_factory,
        UiIconFactory $icon_factory,
        \ilTree $repo_tree,
        \ilAccessHandler $access,
        \ilLanguage $lng,
    ) {
        $this->tree_factory = $tree_factory;
        $this->icon_factory = $icon_factory;
        $this->repo_tree = $repo_tree;
        $this->access = $access;
        $this->lng = $lng;
    }


    public function repository(
        ?int $start_ref_id,
        ?int $current_ref_id,
        bool $is_subtree
    ): RepositoryTree {
        return new RepositoryTree(
            $this->tree_factory,
            $this->icon_factory,
            $this->repo_tree,
            $this->access,
            $this->lng,
            $start_ref_id,
            $current_ref_id,
            $is_subtree
        );
    }
}
