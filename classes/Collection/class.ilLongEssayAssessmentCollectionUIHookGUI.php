<?php

use ILIAS\Plugin\LongEssayAssessment\Collection\CollectionGUI;
use ILIAS\Plugin\LongEssayAssessment\Collection\CollectionCorrectorAdminStatisticsGUI;
use ILIAS\Plugin\LongEssayAssessment\Collection\CollectionWriterStatisticsGUI;
use ILIAS\Plugin\LongEssayAssessment\Collection\CollectionWriterAdminStatisticsGUI;

class ilLongEssayAssessmentCollectionUIHookGUI extends ilUIHookPluginGUI
{
    /**
     * @var \ILIAS\DI\Container
     */
    private \ILIAS\DI\Container $dic;
    private ?string $context_type;
    private ?ilTabsGUI $tabs;
    private ilTree $tree;
    private ilCtrlInterface $ctrl;
    private \ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper $query;
    private \ILIAS\Refinery\Factory $refinery;
    private ilLanguage $lng;
    private ilAccessHandler $access;

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->tabs = $this->dic->isDependencyAvailable("tabs") ? $this->dic->tabs() : null;
        $this->context_type = $this->dic->ctrl()->getContextObjType();
        $this->tree = $DIC->repositoryTree();
        $this->ctrl = $DIC->ctrl();
        $this->query = $DIC->http()->wrapper()->query();
        $this->refinery = $DIC->refinery();
        $this->lng = $DIC->language();
        $this->access = $DIC->access();
    }


    function modifyGUI($a_comp, $a_part, $a_par = array()): void
    {
        if ($a_part == "tabs")
        {
            if(in_array($this->context_type, ['crs', 'grp'])) {

                $ref_id = $this->query->has('ref_id')
                    ? $this->query->retrieve('ref_id', $this->refinery->kindlyTo()->int())
                    : null;
                if($ref_id !== null){
                    $data = $this->tree->getNodeData($ref_id);
                    $xlas_childs = $this->tree->getSubTree($data, false, ['xlas']);

                    array_filter($xlas_childs, fn($x) => $this->access->checkAccess('read', '', $x));

                    if( count($xlas_childs) > 1 ) {
                        /**
                         * @var ilTabsGUI $tabs;
                         */
                        $tabs = $a_par["tabs"];
                        $this->ctrl->setParameterByClass(CollectionGUI::class, 'ref_id', $ref_id);
                        $tabs->addTab(
                            "edutiek",
                            $this->getPluginObject()->txt("objs_xlas"),
                            $this->ctrl->getLinkTargetByClass([ilLongEssayAssessmentDispatchGUI::class, CollectionGUI::class]),
                        );
                        $last = array_pop($tabs->target);

                        array_splice($tabs->target, 1, 0 , [$last]);
                        $this->saveTabs("Course");
                    }
                }
            }
            if($this->query->has("cmdClass") && in_array($this->query->retrieve("cmdClass", $this->refinery->kindlyTo()->string()),
                    array_map(fn($class) => str_replace('\\', '', $class), [CollectionGUI::class, CollectionCorrectorAdminStatisticsGUI::class, CollectionWriterAdminStatisticsGUI::class, CollectionWriterStatisticsGUI::class])))
            {
                $this->lng->loadLanguageModule("crs");
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