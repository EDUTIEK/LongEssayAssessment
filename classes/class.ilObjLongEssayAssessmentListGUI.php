<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponding ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 */
class ilObjLongEssayAssessmentListGUI extends ilObjectPluginListGUI
{

    /**
     * Init type
     */
    public function initType()
    {
        $this->setType(ilLongEssayAssessmentPlugin::ID);
    }

    /**
     * Get name of gui class handling the commands
     */
    public function getGuiClass() : string
    {
        return "ilObjLongEssayAssessmentGUI";
    }

    public function getCommandLink(string $cmd): string
    {
        // separate method for this line
        $cmd_link = "ilias.php?baseClass=ilLongEssayAssessmentDispatchGUI&amp;" .
            "cmd=forward&amp;ref_id=" . $this->ref_id . "&amp;forwardCmd=" . $cmd;

        return $cmd_link;
    }

    /**
     * Get commands
     */
    public function initCommands() : array
    {
        return array(
            array(
                "permission" => "read",
                "cmd" => "standardCommand",
                "default" => true),
            array(
                "permission" => "write",
                "cmd" => "jumpToOrgaSettings",
                "txt" => $this->txt("edit"),
                "default" => false)
        );
    }

    /**
     * Get item properties
     *
     * @return        array           array of property arrays:
     *                                "alert" (boolean) => display as an alert property (usually in red)
     *                                "property" (string) => property name
     *                                "value" (string) => property value
     */
    public function getProperties() : array
    {
        $props = array();

        if (!ilObjLongEssayAssessmentAccess::checkOnline($this->obj_id)) {
            $props[] = array("alert" => true, "property" => $this->txt("status"),
                "value" => $this->txt("offline"));
        }

        return $props;
    }
}
