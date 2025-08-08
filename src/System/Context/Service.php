<?php

namespace ILIAS\Plugin\LongEssayAssessment\System\Context;

class Service
{
    public function __construct(
        private int $ref_id,
        private \ilTree $tree,
    ) {}

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

    public function lookupAssIdFromReference(int $ref_id)
    {
        return \ilObject::_lookupObjId($ref_id);
    }
}