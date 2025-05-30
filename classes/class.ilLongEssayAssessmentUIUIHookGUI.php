<?php

class ilLongEssayAssessmentUIUIHookGUI extends ilUIHookPluginGUI
{
    /**
     * @var \ILIAS\DI\Container
     */
    private \ILIAS\DI\Container $dic;
    private ?string $context_type;

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;

        $this->context_type = $this->dic->ctrl()->getContextObjType();
    }

    /**
     * Modify HTML output of GUI elements. Modifications modes are:
     * - ilUIHookPluginGUI::KEEP (No modification)
     * - ilUIHookPluginGUI::REPLACE (Replace default HTML with your HTML)
     * - ilUIHookPluginGUI::APPEND (Append your HTML to the default HTML)
     * - ilUIHookPluginGUI::PREPEND (Prepend your HTML to the default HTML)
     *
     * @param string $a_comp component
     * @param string $a_part string that identifies the part of the UI that is handled
     * @param string $a_par array of parameters (depend on $a_comp and $a_part)
     *
     * @return array array with entries "mode" => modification mode, "html" => your html
     */
    function getHTML($a_comp, $a_part, $a_par = array()): array
    {
        return array("mode" => ilUIHookPluginGUI::KEEP, "html" => "");
    }

    /**
     * Modify GUI objects, before they generate ouput
     *
     * @param string $a_comp component
     * @param string $a_part string that identifies the part of the UI that is handled
     * @param string $a_par array of parameters (depend on $a_comp and $a_part)
     */
    function modifyGUI($a_comp, $a_part, $a_par = array()): void
    {
        if ($a_part == "tabs" && $this->context_type === "crs")
        {
            /**
             * @var ilTabsGUI $tabs;
             */
            $tabs = $a_par["tabs"];
            $tabs->addTab("edutiek", "Langtext-Aufgabe", "");
            $last = array_pop($tabs->target);


            array_splice($tabs->target, 1, 0 , [$last]);
        }
    }

}