<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;

require_once(__DIR__ . "/class.ilLongEssayAssessmentPlugin.php");

/**
 * Plugin GUI Class
 * This is the entry point for the ILIAS controller
 * It delegates
 *
 * @ilCtrl_isCalledBy ilObjLongEssayAssessmentGUI: ilRepositoryGUI, ilAdministrationGUI, ilLongEssayAssessmentDispatchGUI
 * @ilCtrl_Calls ilObjLongEssayAssessmentGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilExportGUI
 */
class ilObjLongEssayAssessmentGUI extends ilObjectPluginGUI
{
    private ilHelpGUI $help;
    /** @var ilObjLongEssayAssessment */
    protected ?ilObject $object = null;

    /** @var ilLongEssayAssessmentPlugin */
    protected ?ilPlugin $plugin = null;

    /**
     * Definition of the plugin specific sub tabs
     * @var array tab_id => [ ['id' => string, 'txt' => string, 'url' => string, ... ]
     * @see setTabs()
     */
    protected $subtabs = [];


    /**
     * Redirection for goto links
     * Overrides standard function for plugins to use the own plugin dispatcher
     * Special treatment of a direct return from the writer or corrector web app
     * @see \ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorContext::getReturnUrl
     */
    public static function _goto($a_target) : void
    {
        global $DIC;

        $t = explode("_", $a_target[0]);
        $ref_id = (int) $t[0];

        if ($DIC->access()->checkAccess("read", "", $ref_id)) {
            if (isset($t[1])) {
                if ($t[1] == 'writer') {
                    $class_name = 'ilias\plugin\longessayassessment\writer\writerstartgui';
                }
                if ($t[1] == 'corrector') {
                    $class_name = 'ilias\plugin\longessayassessment\corrector\correctorstartgui';
                }
                if ($t[1] == 'correctoradmin') {
                    $class_name = 'ilias\plugin\longessayassessment\correctoradmin\correctoradmingui';
                }
                if (isset($class_name)) {
                    $DIC->ctrl()->setParameterByClass("ilObjLongEssayAssessmentgui", "ref_id", $ref_id);
                    $DIC->ctrl()->setParameterByClass($class_name, "returned", '1');
                    $DIC->ctrl()->redirectByClass(array("illongessayassessmentdispatchgui", "ilObjLongEssayAssessmentgui", $class_name), "");
                }
            }
        }

        // no read access or not a special return
        $DIC->ctrl()->setParameterByClass("ilObjLongEssayAssessmentgui", "ref_id", $ref_id);
        $DIC->ctrl()->redirectByClass(array("illongessayassessmentdispatchgui", "ilObjLongEssayAssessmentgui"), "standardCommand");
    }


    /**
     * Initialisation
     */
    protected function afterConstructor() : void
    {
        global $DIC;
        
        ilLongEssayAssessmentPlugin::initAutoload();
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
        $this->help = $DIC->help();
        // Description is not shown by ilObjectPluginGUI
        if (isset($this->object)) {
            $this->tpl->setDescription($this->object->getDescription());
            $alerts = array();
            if (!$this->object->isOnline()) {
                array_push(
                    $alerts,
                    array(
                        'property' => $this->plugin->txt('status'),
                        'value' => $this->plugin->txt('offline'))
                );
            }
            $this->tpl->setAlertProperties($alerts);
        }
    }

    /**
     * Get type.
     */
    final public function getType() : string
    {
        return ilLongEssayAssessmentPlugin::ID;
    }


    /**
     * Ger the repository object
     * @return ilObjLongEssayAssessment
     */
    public function getObject() : ?ilObject
    {
        return $this->object;
    }


    /**
     * Handles all commands of this class, centralizes permission checks
     */
    public function performCommand($cmd) : void
    {
        global $DIC;

        $next_class = $this->ctrl->getNextClass();
        if (!empty($next_class)) {
            switch ($next_class) {
                case 'illongessayassessmentuploadhandlergui':
                    // No permission check needed because it only stores temp files
                    $this->ctrl->forwardCommand(new ilLongEssayAssessmentUploadHandlerGUI(
                        $DIC->resourceStorage(),
                        new \ILIAS\Plugin\LongEssayAssessment\ilLongEssayAssessmentUploadTempFile(
                            $DIC->resourceStorage(),
                            $DIC->filesystem(),
                            $DIC->upload()
                        )
                    ));
                    break;
                case 'ilias\plugin\longessayassessment\task\orgasettingsgui':
                    if ($this->object->canEditOrgaSettings()) {
                        $this->activateTab('tab_task', 'tab_orga_settings');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\OrgaSettingsGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\task\instructionssettingsgui':
                    if ($this->object->canEditContentSettings()) {
                        $this->activateTab('tab_task', 'tab_instructions_settings');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\InstructionsSettingsGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\task\solutionsettingsgui':
                    if ($this->object->canEditContentSettings()) {
                        $this->activateTab('tab_task', 'tab_solution_settings');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\SolutionSettingsGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\task\resourcesadmingui':
                    if ($this->object->canEditContentSettings()) {
                        $this->activateTab('tab_task', 'tab_resources');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\ResourcesAdminGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\task\resourceuploadhandlergui':
                    if ($this->object->canEditMaterial()) {
                        $task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
                        $this->ctrl->forwardCommand(
                            new \ILIAS\Plugin\LongEssayAssessment\Task\ResourceUploadHandlerGUI($DIC->resourceStorage(), $task_repo)
                        );
                    }
                    break;
                case 'ilias\plugin\longessayassessment\task\editorsettingsgui':
                    if ($this->object->canEditTechnicalSettings()) {
                        $this->activateTab('tab_task', 'tab_technical_settings');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\EditorSettingsGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\task\correctionsettingsgui':
                    if ($this->object->canEditTechnicalSettings()) {
                        $this->activateTab('tab_task', 'tab_correction_settings');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\CorrectionSettingsGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\task\criteriaadmingui':
                    if ($this->object->canEditContentSettings()) {
                        $this->activateTab('tab_task', 'tab_criteria');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\CriteriaAdminGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\task\gradesadmingui':
                    if ($this->object->canEditContentSettings()) {
                        $this->activateTab('tab_task', 'tab_grades');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\GradesAdminGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\writer\writerstartgui':
                    if ($this->object->canViewWriterScreen()) {
                        $this->activateTab('tab_writer', 'tab_writer_start');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Writer\WriterStartGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\writer\writeruploadgui':
                    if ($this->object->canViewWriterScreen()) {
                        $this->activateTab('tab_writer', 'tab_writer_start');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\corrector\correctorstartgui':
                    if ($this->object->canViewCorrectorScreen()) {
                        $this->activateTab('tab_corrector', 'tab_corrector_start');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorStartGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\corrector\correctorcriteriagui':
                    if ($this->object->canEditOwnRatingCriteria()) {
                        $this->activateTab('tab_corrector', 'tab_corrector_criteria');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorCriteriaGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\corrector\correctionreportgui':
                    if ($this->object->canWriteCorrectionReport()) {
                        $this->activateTab('tab_corrector', 'tab_correction_report');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectionReportGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\writeradmin\writeradmingui':
                    if ($this->object->canMaintainWriters()) {
                        $this->activateTab('tab_writer_admin', 'tab_writer_admin');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\writeradmin\writeradminloggui':
                    if ($this->object->canMaintainWriters()) {
                        $this->activateTab('tab_writer_admin', 'tab_writer_admin_log');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminLogGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\correctoradmin\correctoradmingui':
                    if ($this->object->canMaintainCorrectors()) {
                        $cmd = $this->ctrl->getCmd('showStartPage');
                        $active_sub = 'tab_correction_items';
                        if(in_array($cmd, ["showCorrectors", "start", "performSearch"])) {
                            $active_sub = 'tab_corrector_list';
                        }
                        $this->activateTab('tab_corrector_admin', $active_sub);
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\correctoradmin\correctoradminstatisticsgui':
                    if ($this->object->canMaintainCorrectors()) {
                        $cmd = $this->ctrl->getCmd('showStartPage');
                        $active_sub = 'tab_corrector_adm_statistic';
                        $this->activateTab('tab_corrector_admin', $active_sub);
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminStatisticsGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\correctoradmin\correctoradminwriterstatisticsgui':
                    if ($this->object->canMaintainCorrectors()) {
                        $cmd = $this->ctrl->getCmd('showStartPage');
                        $active_sub = 'tab_writer_statistic';
                        $this->activateTab('tab_corrector_admin', $active_sub);
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminWriterStatisticsGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessayassessment\corrector\correctorstatisticsgui':
                    if ($this->object->canViewCorrectorScreen()) {
                        $cmd = $this->ctrl->getCmd('showStartPage');
                        $active_sub = 'tab_corrector_statistic';
                        $this->activateTab('tab_corrector', $active_sub);
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorStatisticsGUI($this));
                    }
                    break;
                default:
                    $this->tpl->setOnScreenMessage("failure", 'Unsupported cmdClass: ' . $next_class, true);
            }
        } else {
            switch ($cmd) {
                case 'jumpToOrgaSettings':
                    $this->checkPermission("write");
                    $this->$cmd();
                    break;

                    // list all commands that need read permission here
                case "standardCommand":
                    $this->$cmd();
                    break;

                default:
                    $this->tpl->setOnScreenMessage("failure", 'Unsupported cmd: ' . $cmd);
            }
        }
    }

    /**
     * Redirect after a new object is saves
     * Here: use illongessayassessmentdispatchgui instead of ilobjplugindispatchgui
     */
    protected function afterSave(ilObject $new_object): void
    {
        $ilCtrl = $this->ctrl;
        // always send a message
        $this->tpl->setOnScreenMessage('success', $this->lng->txt("object_added"), true);

        $ilCtrl->setTargetScript('ilias.php');
        $ilCtrl->setParameterByClass(get_class($this), "ref_id", $new_object->getRefId());
        $ilCtrl->redirectByClass(['illongessayassessmentdispatchgui', get_class($this)], $this->getAfterCreationCmd());
    }


    /**
     * After object has been created -> jump to this command
     */
    public function getAfterCreationCmd() : string
    {
        return "jumpToOrgaSettings";
    }

    /**
     * Get standard command
     */
    public function getStandardCmd() : string
    {
        return "standardCommand";
    }

    /**
     * Apply the standard command
     */
    protected function standardCommand()
    {
        if ($this->object->canEditOrgaSettings()) {
            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\task\orgasettingsgui');
        }
        if ($this->object->canEditContentSettings()) {
            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\task\solutionsettingsgui');
        }
        if ($this->object->canMaintainWriters()) {
            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\writerAdmin\writeradmingui');
        }
        if ($this->object->canMaintainCorrectors()) {
            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\correctorAdmin\correctoradmingui');
        }
        if ($this->object->canViewCorrectorScreen()) {
            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\corrector\correctorstartgui');
        }
        if ($this->object->canViewWriterScreen()) {
            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\writer\writerstartgui');
        }

        $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('message_no_admin_writer_corrector'), true);
    }

    /**
     * Jump to the editing of organisational settings (used in actions menu)
     */
    protected function jumpToOrgaSettings()
    {
        $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\task\orgasettingsgui');
    }


    /**
     * Set tabs (called already by ilObjPluginGUI before performCommand is called)
     * This defines the available sub tabs for each tab, based on the permissions
     * A Tab is added to the GUI with the URL of the first available sub tab
     * The actual sub tabs are added to the GUI in self::activateTab() when the current tab is known
     */
    public function setTabs() : void
    {
        $this->help->setScreenIdComponent($this->getPlugin()->getId());
        
        $this->subtabs = [];

        // Task Definition Tab
        $tabs = [];
        if ($this->object->canEditOrgaSettings()) {
            $tabs[] = [
                'id' => 'tab_orga_settings',
                'txt' => $this->plugin->txt('tab_orga_settings'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\orgasettingsgui')
            ];
        }
        if ($this->object->canEditContentSettings()) {
            $tabs[] = [
                'id' => 'tab_instructions_settings',
                'txt' => $this->plugin->txt('tab_instructions_settings'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\instructionssettingsgui')
            ];
        }
        if ($this->object->canEditContentSettings()) {
            $tabs[] = [
                'id' => 'tab_solution_settings',
                'txt' => $this->plugin->txt('tab_solution_settings'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\solutionsettingsgui')
            ];
        }
        if ($this->object->canEditContentSettings()) {
            $tabs[] = [
                'id' => 'tab_resources',
                'txt' => $this->plugin->txt('tab_resources'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\resourcesadmingui')
            ];
        }
        if ($this->object->canEditTechnicalSettings()) {
            $tabs[] = [
                'id' => 'tab_technical_settings',
                'txt' => $this->plugin->txt('tab_technical_settings'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\editorsettingsgui')
            ];
        }
        if ($this->object->canEditTechnicalSettings()) {
            $tabs[] = [
                'id' => 'tab_correction_settings',
                'txt' => $this->plugin->txt('tab_correction_settings'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\correctionsettingsgui')
            ];
        }

        if ($this->object->canEditFixedRatingCriteria()) {
            $tabs[] = [
                'id' => 'tab_criteria',
                'txt' => $this->plugin->txt('tab_criteria'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\criteriaadmingui')
            ];
        }
        if ($this->object->canEditContentSettings()) {
            $tabs[] = [
                'id' => 'tab_grades',
                'txt' => $this->plugin->txt('tab_grades'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\gradesadmingui')
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_task', $this->plugin->txt('tab_task'), $tabs[0]['url']);
            $this->subtabs['tab_task'] = $tabs;
        }
        
        // Corrector Tab
        $tabs = [];
        if ($this->object->canViewCorrectorScreen()) {
            $tabs[] = [
                'id' => 'tab_corrector_start',
                'txt' => $this->plugin->txt('tab_corrector_start'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\corrector\correctorstartgui')
            ];
            if($this->object->canEditOwnRatingCriteria()) {
                $tabs[] = [
                    'id' => 'tab_corrector_criteria',
                    'txt' => $this->plugin->txt('tab_criteria'),
                    'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\corrector\correctorcriteriagui')
                ];
            }
            $tabs[] = [
                'id' => 'tab_corrector_statistic',
                'txt' => $this->plugin->txt('tab_corrector_statistic'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\corrector\correctorstatisticsgui')
            ];
        }
        if ($this->object->canWriteCorrectionReport()) {
            $tabs[] = [
                'id' => 'tab_correction_report',
                'txt' => $this->plugin->txt('tab_correction_report'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\corrector\correctionreportgui')
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_corrector', $this->plugin->txt('tab_corrector'), $tabs[0]['url']);
            $this->subtabs['tab_corrector'] = $tabs;
        }

        // Writer Tab
        $tabs = [];
        if ($this->object->canViewWriterScreen()) {
            $tabs[] = [
                'id' => 'tab_writer_start',
                'txt' => $this->plugin->txt('tab_writer_start'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\writer\writerstartgui')
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_writer', $this->plugin->txt('tab_writer'), $tabs[0]['url']);
            $this->subtabs['tab_writer'] = $tabs;
        }


        // Writer Admin Tab
        $tabs = [];
        if ($this->object->canMaintainWriters()) {
            $tabs[] = [
                'id' => 'tab_writer_admin',
                'txt' => $this->plugin->txt('tab_writer_admin'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\writerAdmin\writeradmingui')
            ];
            $tabs[] = [
                'id' => 'tab_writer_admin_log',
                'txt' => $this->plugin->txt('tab_writer_admin_log'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\writerAdmin\writeradminloggui')
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_writer_admin', $this->plugin->txt('tab_writer_admin'), $tabs[0]['url']);
            $this->subtabs['tab_writer_admin'] = $tabs;
        }

        // Corrector Admin Tab
        $tabs = [];
        if ($this->object->canMaintainCorrectors()) {
            $tabs[] = [
                'id' => 'tab_correction_items',
                'txt' => $this->plugin->txt('tab_correction_items'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\correctorAdmin\correctoradmingui')
            ];
            $tabs[] = [
                'id' => 'tab_corrector_list',
                'txt' => $this->plugin->txt('tab_corrector_list'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\correctorAdmin\correctoradmingui', "showCorrectors")
            ];
            $tabs[] = [
                'id' => 'tab_corrector_adm_statistic',
                'txt' => $this->plugin->txt('tab_corrector_admin_statistic'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\correctorAdmin\correctoradminstatisticsgui', "showStartPage")
            ];
            $tabs[] = [
                'id' => 'tab_writer_statistic',
                'txt' => $this->plugin->txt('tab_writer_statistic'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\correctorAdmin\correctoradminwriterstatisticsgui', "showStartPage")
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_corrector_admin', $this->plugin->txt('tab_corrector_admin'), $tabs[0]['url']);
            $this->subtabs['tab_corrector_admin'] = $tabs;
        }



        // standard info screen tab
        if ($this->object->canViewInfoScreen()) {
            $this->addInfoTab();
        }

        // standard export tab
        // $this->addExportTab();

        // standard permission tab
        $this->addPermissionTab();

        // activate tab for some external GUIs
        $next_class = $this->ctrl->getCmdClass();
        switch($next_class) {
            case 'ilexportgui':
                $this->tabs->activateTab("export");
                break;
        }
    }



    /**
     * Activate a tab, add its sub tabs and activate a sub tab
     *
     * @param string    $a_tab_id
     * @param string    $a_subtab_id
     */
    protected function activateTab($a_tab_id, $a_subtab_id = '')
    {

        $this->tabs->activateTab($a_tab_id);

        if (!empty($this->subtabs[$a_tab_id])) {
            foreach($this->subtabs[$a_tab_id] as $subtab) {
                $this->tabs->addSubTab($subtab['id'], $subtab['txt'], $subtab['url']);
            }
            $this->help->setScreenId(str_replace("tab_", "", $a_tab_id));
        }

        if (!empty($a_subtab_id)) {
            $this->tabs->activateSubTab($a_subtab_id);
            $this->help->setSubScreenId(str_replace("tab_", "", $a_subtab_id));
        }
    }
}
