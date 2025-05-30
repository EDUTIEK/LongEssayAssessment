<?php

class ilLongEssayAssessmentUIUIHookGUI extends ilUIHookPluginGUI
{
    /**
     * @var \ILIAS\DI\Container
     */
    private \ILIAS\DI\Container $dic;
    private ?string $context_type;
    private ilTabsGUI $tabs;

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->tabs = $DIC->tabs();
        $this->context_type = $this->dic->ctrl()->getContextObjType();
    }


    function modifyGUI($a_comp, $a_part, $a_par = array()): void
    {
        if ($a_part == "tabs")
        {
            if($this->context_type === "crs") {
                /**
                 * @var ilTabsGUI $tabs;
                 */
                $tabs = $a_par["tabs"];
                $tabs->addTab("edutiek", "Langtext-Aufgabe", "");
                $last = array_pop($tabs->target);

                array_splice($tabs->target, 1, 0 , [$last]);
                $this->saveTabs("Course");
            }

            if($this->dic->ctrl->getCmdClass() == 'illongessayassessmentcoursegui')
            {
                $this->restoreTabs("Course");
                $this->tabs->activateTab("edutiek");
            }
        }
    }

    /**
     * Save the tabs for reuse on the plugin pages
     * @param string $a_context context for which the tabs should be saved
     */
    protected function saveTabs(string $a_context) : void
    {
        $this->setArrayInSession($a_context, 'TabTarget', $this->tabs->target);
        $this->setArrayInSession($a_context, 'TabSubTarget', $this->tabs->sub_target);
    }

    /**
     * Restore the tabs for reuse on the plugin pages
     * @param string $a_context context for which the tabs should be saved
     */
    protected function restoreTabs(string $a_context) : void
    {
        // reuse the tabs that were saved from the parent gui
        if (!empty($target = $this->getArrayFromSession($a_context, 'TabTarget'))) {
            $this->tabs->target = $target;
        }
        if (!empty($target = $this->getArrayFromSession($a_context, 'TabSubTarget'))) {
            $this->tabs->sub_target = $target;
        }
    }

    protected function setArrayInSession(string $a_context, string $name, array $array) : void
    {
        ilSession::set(__class__ . '.' . $a_context . '.' . $name, serialize($array));
    }

    protected function getArrayFromSession(string $a_context, string $name) : ?array
    {
        try {
            return unserialize(ilSession::get(__class__ . '.' . $a_context . '.' . $name));
        }
        catch (Exception $e) {
            return null;
        }
    }

}