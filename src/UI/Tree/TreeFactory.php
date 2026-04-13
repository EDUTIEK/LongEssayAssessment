<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Tree;

use ILIAS\UI\Component\Tree\Factory as UiTreeFactory;
use ILIAS\UI\Component\Symbol\Icon\Factory as UiIconFactory;
use ILIAS\Data\URI;

class TreeFactory
{
    private UiTreeFactory $tree_factory;
    private UiIconFactory $icon_factory;
    private \ilTree $repo_tree;
    private \ilAccessHandler $access;
    private \ilLanguage $lng;

    public function __construct(
        private \ilLongEssayAssessmentPlugin $plugin,
        UiTreeFactory $tree_factory,
        UiIconFactory $icon_factory,
        \ilTree $repo_tree,
        \ilAccessHandler $access,
        private \ilObjUser $user,
        \ilLanguage $lng,
        protected \ILIAS\HTTP\Services $http,
        protected \ILIAS\Refinery\Factory $refinery,
        protected \ILIAS\UI\Factory $ui_factory,
        protected \ILIAS\UI\Renderer $renderer,
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

    /**
     * todo: selecting tasks in tree does not work
     */
    public function task(
        ?int $start_ref_id,
        ?int $current_ref_id,
        bool $is_subtree
    ): RepositoryTaskTree {
        return new RepositoryTaskTree(
            $this->plugin,
            $this->tree_factory,
            $this->icon_factory,
            $this->repo_tree,
            $this->access,
            $this->user,
            $this->lng,
            $start_ref_id,
            $current_ref_id,
            $is_subtree
        );
    }

    /**
     * @param $view_callback (int $ref_id)
     */
    public function repositorySelect(int $ref_id, string $title, callable $view_callback, ?string $uri_or_target = null): RepositorySelectModal
    {
        return $this->modal($ref_id, $title, $view_callback, fn(...$x) => $this->repository(...$x), false, $uri_or_target);
    }

    /**
     * @param $view_callback (int $ref_id, int $task_id)
     */
    public function repositoryTaskSelect(int $ref_id, string $title, callable $view_callback, ?string $uri_or_target = null): RepositorySelectModal
    {
        return $this->modal($ref_id, $title, $view_callback, fn(...$x) => $this->repository(...$x), true, $uri_or_target);
    }

    private function modal(int $ref_id, string $title, callable $view_callback, callable $tree_factory, bool $select_task = false, ?string $uri_or_target = null): RepositorySelectModal
    {
        if ($uri_or_target !== null && !preg_match('/\Ahttp[s]?:\/\//', $uri_or_target)) {
            $uri_or_target = rtrim(ILIAS_HTTP_PATH, '/') . "/" . ltrim($uri_or_target, '/');
        }

        return new RepositorySelectModal(
            $this->http,
            $this->refinery,
            $this->ui_factory,
            $this->renderer,
            $this->lng,
            $this,
            $this->user,
            $this->access,
            $this->plugin,
            $ref_id,
            $title,
            $view_callback,
            $tree_factory,
            $select_task,
            $uri_or_target,
        );
    }
}
