<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Tree;

use ILIAS\UI\Component\Tree\Tree;
use ILIAS\UI\Component\Tree\Node\Factory as NodeFactory;
use ILIAS\UI\Component\Tree\Node\Node;
use ILIAS\UI\Component\Symbol\Icon\Icon;
use ILIAS\UI\Component\Tree\Factory as TreeFactory;
use ILIAS\UI\Component\Symbol\Icon\Factory as IconFactory;
use Edutiek\AssessmentService\Task\Manager\Service as TaskManager;

class RepositoryTaskTree extends RepositoryTree
{
    public function __construct(
        private \ilLongEssayAssessmentPlugin $plugin,
        TreeFactory $tree_factory,
        IconFactory $icon_factory,
        \ilTree $repo_tree,
        \ilAccessHandler $access,
        private \ilObjUser $user,
        \ilLanguage $lng,
        ?int $start_ref_id,
        ?int $current_ref_id,
        bool $is_subtree
    ) {

        parent::__construct(
            $tree_factory, $icon_factory, $repo_tree, $access, $lng, $start_ref_id, $current_ref_id, $is_subtree
        );
    }

    /**
     * @return string[]
     */
    public function getRepoContainerTypes() : array
    {
        return ['root', 'cat', 'crs', 'grp', 'fold', 'lso', 'prg', 'xlas'];
    }

    /**
     * Recursively called when the tree component is rendered
     * This should fill all nodes on the path to the current node
     */
    public function getChildren($record, $environment = null): array
    {
        if (!$this->is_subtree && $record['type'] === 'xlas') {
            $ref_id = (int) $record['ref_id'];
            if (in_array($ref_id, $this->current_path)) {

                $manager = $this->plugin->dic()->task($record['obj_id'], $this->user->getId())->manager();
                $tasks = [];

                foreach($manager->all() as $task_info) {
                    $title = $task_info->getTitle();
                    if(empty($title)) {
                        $title = $this->plugin->txt('single_task');
                    }
                    $tasks[] = ['ref_id' => $task_info->getId(),
                                'obj_id' => $record['obj_id'],
                                'type' => 'xlas_task',
                                'title' => $title];
                }

                return $tasks;
            }
            return [];
        }else{
            return parent::getChildren($record, $environment);
        }
    }

    protected function isVisible(int $ref_id, string $type)
    {
        if($type === 'xlas_task') {
            if ($this->visible_callback !== null) {
                return ($this->visible_callback)($ref_id, $type);
            }
            return true;
        } else {
            return parent::isVisible($ref_id, $type);
        }
    }

    protected function isClickable(int $ref_id, string $type)
    {
        if($type === 'xlas_task') {
            if ($this->clickable_callback !== null) {
                return ($this->clickable_callback)($ref_id, $type);
            }
            return true;
        } else {
            return false;
        }
    }

    protected function getIcon(int $obj_id, string $type): ?Icon
    {
        if($type === 'xlas_task') {
            return null;
        } else {
            return parent::getIcon($obj_id, $type);
        }
    }
}