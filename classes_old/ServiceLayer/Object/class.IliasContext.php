<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Object;

use ILIAS\Plugin\LongEssayAssessment\BaseService;

/**
 * Service functions related to the ILIAS context of the object
 */
class IliasContext extends BaseService
{
    protected \ilTree $tree;
    protected int $ref_id;
    protected int $obj_id;

    /**
     * Constructor
     */
    public function __construct(int $ref_id)
    {
        parent::__construct();

        $this->tree = $this->dic->repositoryTree();
        $this->ref_id = $ref_id;
        $this->obj_id = \ilObject::_lookupObjId($ref_id);
    }


    /**
     * Check if the objects is in a course
     */
    public function isInCourse() : bool
    {
        return !empty($this->tree->checkForParentType($this->ref_id, "crs"));
    }

    /**
     * Get the user ids of tutors in a parent course
     * @return int[]
     */
    public function getCourseTutors() : array
    {
        if (!empty($ref_id = $this->tree->checkForParentType($this->ref_id, 'crs'))) {

            $part_obj = new \ilCourseParticipants(\ilObject::_lookupObjId($ref_id));
            return $part_obj->getTutors();
        }
        return [];
    }

    /**
     * Get Node Data of all LongEssayAssessment plugins of this context (parent downwards the tree)
     *
     * @return array
     */
    public function getAllEssaysInThisContext() : array
    {
        $parent = $this->tree->getParentNodeData($this->ref_id);
        $nodes = $this->tree->getSubTree($parent);
        return array_filter($nodes, fn ($node) => $node["type"] === "xlas");
    }
}
