<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once(__DIR__ . "/class.ilLongEssayTaskPlugin.php");

/**
 * Plugin GUI Class
 * This is the entry point for the ILIAS controller
 * It delegates
 *
 * @ilCtrl_isCalledBy ilObjLongEssayTaskGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjLongEssayTaskGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilExportGUI
 */
class ilObjLongEssayTaskGUI extends ilObjectPluginGUI
{
    /** @var ilObjLongEssayTask */
	public $object;

	/** @var ilLongEssayTaskPlugin */
	public $plugin;

    /**
     * Definition of the plugin specific sub tabs
     * @var array tab_id => [ ['id' => string, 'txt' => string, 'url' => string, ... ]
     * @see setTabs()
     */
	protected $subtabs = [];


    /**
     * Goto redirection
     * Enhanced for direct return to writer or corrector start screens
     * @see \ILIAS\Plugin\LongEssayTask\Corrector\CorrectorContext::getReturnUrl
     */
    public static function _goto($a_target)
    {
        global $DIC;

        $t = explode("_", $a_target[0]);
        $ref_id = (int) $t[0];

        if ($DIC->access()->checkAccess("read", "", $ref_id)) {
            if (isset($t[1])) {
                if ($t[1] == 'writer') {
                    $class_name = 'ilias\plugin\longessaytask\writer\writerstartgui';
                }
                if ($t[1] == 'corrector') {
                    $class_name = 'ilias\plugin\longessaytask\corrector\correctorstartgui';
                }
                if (isset($class_name)) {
                    $DIC->ctrl()->initBaseClass("ilObjPluginDispatchGUI");
                    $DIC->ctrl()->getCallStructure(strtolower("ilObjPluginDispatchGUI"));
                    $DIC->ctrl()->setParameterByClass("ilobjlongessaytaskgui", "ref_id", $ref_id);
                    $DIC->ctrl()->redirectByClass(array("ilobjplugindispatchgui", "ilobjlongessaytaskgui", $class_name), "");
                }
            }
        }
        parent::_goto($a_target);
    }


    /**
	 * Initialisation
	 */
	protected function afterConstructor()
	{
	    $this->plugin = ilLongEssayTaskPlugin::getInstance();

        // Description is not shown by ilObjectPluginGUI
        if (isset($this->object))
        {
            $this->tpl->setDescription($this->object->getDescription());
            $alerts = array();
            if (!$this->object->isOnline())
            {
                array_push($alerts, array(
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
	final function getType()
	{
		return ilLongEssayTaskPlugin::ID;
	}


    /**
     * Ger the repository object
     * @return ilObjLongEssayTask
     */
	public function getObject()
    {
        return $this->object;
    }

    /**
     * Get the plugin object
     * @return ilLongEssayTaskPlugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }


    /**
	 * Handles all commands of this class, centralizes permission checks
	 */
	function performCommand($cmd)
	{
        $next_class = $this->ctrl->getNextClass();
        if (!empty($next_class)) {
            switch ($next_class) {
                case 'ilias\plugin\longessaytask\task\orgasettingsgui':
                    if ($this->object->canEditOrgaSettings()) {
                        $this->activateTab('tab_task', 'tab_orga_settings');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\Task\OrgaSettingsGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessaytask\task\contentsettingsgui':
                    if ($this->object->canEditContentSettings()) {
                        $this->activateTab('tab_task', 'tab_content_settings');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\Task\ContentSettingsGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessaytask\task\resourcesadmingui':
                    if ($this->object->canEditContentSettings()) {
                        $this->activateTab('tab_task', 'tab_resources');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\Task\ResourcesAdminGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessaytask\task\editorsettingsgui':
                    if ($this->object->canEditTechnicalSettings()) {
                        $this->activateTab('tab_task', 'tab_technical_settings');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\Task\EditorSettingsGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessaytask\task\criteriaadmingui':
                    if ($this->object->canEditContentSettings()) {
                        $this->activateTab('tab_task', 'tab_criteria');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\Task\CriteriaAdminGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessaytask\task\gradesadmingui':
                    if ($this->object->canEditContentSettings()) {
                        $this->activateTab('tab_task', 'tab_grades');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\Task\GradesAdminGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessaytask\writer\writerstartgui':
                    if ($this->object->canViewWriterScreen()) {
                        $this->activateTab('tab_writer', 'tab_writer_start');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\Writer\WriterStartGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessaytask\corrector\correctorstartgui':
                    if ($this->object->canViewCorrectorScreen()) {
                        $this->activateTab('tab_corrector', 'tab_corrector_start');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\Corrector\CorrectorStartGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessaytask\writeradmin\writeradmingui':
                    if ($this->object->canMaintainWriters()) {
                        $this->activateTab('tab_writer_admin', 'tab_writer_admin');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\WriterAdmin\WriterAdminGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessaytask\writeradmin\writeradminloggui':
                    if ($this->object->canMaintainWriters()) {
                        $this->activateTab('tab_writer_admin', 'tab_writer_admin_log');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\WriterAdmin\WriterAdminLogGUI($this));
                    }
                    break;
                case 'ilias\plugin\longessaytask\correctoradmin\correctoradmingui':
                    if ($this->object->canMaintainCorrectors()) {
						$cmd = $this->ctrl->getCmd('showStartPage');
						$active_sub = 'tab_corrector_admin';
						if(in_array($cmd, ["showCorrectors", "start", "performSearch"])){
							$active_sub = 'tab_corrector_list';
						}
                        $this->activateTab('tab_corrector_admin', $active_sub);
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\CorrectorAdmin\CorrectorAdminGUI($this));
                    }
                    break;
                default:
                    ilUtil::sendFailure('Unsupported cmdClass: ' . $next_class);
            }
        }
        else {
            switch ($cmd)
            {
                case 'jumpToOrgaSettings':
                    $this->checkPermission("write");
                    $this->$cmd();
                    break;

                // list all commands that need read permission here
                case "standardCommand":
                    $this->$cmd();
                    break;

                default:
                    ilUtil::sendFailure('Unsupported cmd: ' . $next_class);
            }
        }
	}

	/**
	 * After object has been created -> jump to this command
	 */
	function getAfterCreationCmd()
	{
		return "jumpToOrgaSettings";
	}

	/**
	 * Get standard command
	 */
	function getStandardCmd()
	{
		return "standardCommand";
	}

    /**
     * Apply the standard command
     */
    protected function standardCommand()
    {
        if ($this->object->canViewWriterScreen()) {
            $this->ctrl->redirectByClass('ilias\plugin\longessaytask\writer\writerstartgui');
        }
        if ($this->object->canViewCorrectorScreen()) {
            $this->ctrl->redirectByClass('ilias\plugin\longessaytask\corrector\correctorstartgui');
        }
        if ($this->object->canEditTechnicalSettings()) {
            $this->ctrl->redirectByClass('ilias\plugin\longessaytask\task\orgasettingsgui');
        }
        if ($this->object->canEditContentSettings()) {
            $this->ctrl->redirectByClass('ilias\plugin\longessaytask\task\contentsettingsgui');
        }

        $this->ctrl->redirectByClass('ilInfoScreenGUI');
    }

    /**
     * Jump to the editing of organisational settings
     */
    protected function jumpToOrgaSettings()
    {
        $this->ctrl->redirectByClass('ilias\plugin\longessaytask\task\orgasettingsgui');
    }


    /**
	 * Set tabs (called already by ilObjPluginGUI before performCommand is called)
     * This defines the available sub tabs for each tab, based on the permissions
     * A Tab is added to the GUI with the URL of the first available sub tab
     * The actual sub tabs are added to the GUI in self::activateTab() when the current tab is known
	 */
	function setTabs()
	{
        $this->subtabs = [];

        // Task Definition Tab
        $tabs = [];
        if ($this->object->canEditOrgaSettings()) {
            $tabs[] = [
                'id' => 'tab_orga_settings',
                'txt' => $this->plugin->txt('tab_orga_settings'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\task\orgasettingsgui')
            ];
        }
        if ($this->object->canEditContentSettings()) {
            $tabs[] = [
                'id' => 'tab_content_settings',
                'txt' => $this->plugin->txt('tab_content_settings'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\task\contentsettingsgui')
            ];
        }
        if ($this->object->canEditContentSettings()) {
            $tabs[] = [
                'id' => 'tab_resources',
                'txt' => $this->plugin->txt('tab_resources'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\task\resourcesadmingui')
            ];
        }
        if ($this->object->canEditTechnicalSettings()) {
            $tabs[] = [
                'id' => 'tab_technical_settings',
                'txt' => $this->plugin->txt('tab_technical_settings'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\task\editorsettingsgui')
            ];
        }
//        if ($this->object->canEditContentSettings()) {
//            $tabs[] = [
//                'id' => 'tab_criteria',
//                'txt' => $this->plugin->txt('tab_criteria'),
//                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\task\criteriaadmingui')
//            ];
//        }
        if ($this->object->canEditContentSettings()) {
            $tabs[] = [
                'id' => 'tab_grades',
                'txt' => $this->plugin->txt('tab_grades'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\task\gradesadmingui')
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_task', $this->plugin->txt('tab_task'), $tabs[0]['url']);
            $this->subtabs['tab_task'] = $tabs;
        }

        // Writer Tab
        $tabs = [];
        if ($this->object->canViewWriterScreen()) {
            $tabs[] = [
                'id' => 'tab_writer_start',
                'txt' => $this->plugin->txt('tab_writer_start'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\writer\writerstartgui')
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_writer', $this->plugin->txt('tab_writer'), $tabs[0]['url']);
            $this->subtabs['tab_writer'] = $tabs;
        }


        // Corrector Tab
        $tabs = [];
        if ($this->object->canViewCorrectorScreen()) {
            $tabs[] = [
                'id' => 'tab_corrector_start',
                'txt' => $this->plugin->txt('tab_corrector_start'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\corrector\correctorstartgui')
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_corrector', $this->plugin->txt('tab_corrector'), $tabs[0]['url']);
            $this->subtabs['tab_corrector'] = $tabs;
        }

        // Writer Admin Tab
        $tabs = [];
        if ($this->object->canMaintainWriters()) {
            $tabs[] = [
                'id' => 'tab_writer_admin',
                'txt' => $this->plugin->txt('tab_writer_admin'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\writerAdmin\writeradmingui')
            ];
            $tabs[] = [
                'id' => 'tab_writer_admin_log',
                'txt' => $this->plugin->txt('tab_writer_admin_log'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\writerAdmin\writeradminloggui')
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
                'id' => 'tab_corrector_admin',
                'txt' => $this->plugin->txt('tab_corrector_admin'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\correctorAdmin\correctoradmingui')
            ];
			$tabs[] = [
				'id' => 'tab_corrector_list',
				'txt' => $this->plugin->txt('tab_corrector_list'),
				'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\correctorAdmin\correctoradmingui', "showCorrectors")
			];
            $tabs[] = [
                'id' => 'tab_corrector_export',
                'txt' => $this->plugin->txt('tab_corrector_export'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\correctorAdmin\correctoradmingui')
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_corrector_admin', $this->plugin->txt('tab_corrector_admin'), $tabs[0]['url']);
            $this->subtabs['tab_corrector_admin'] = $tabs;
        }



        // standard info screen tab
        // $this->addInfoTab();

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
	protected function activateTab ($a_tab_id, $a_subtab_id = '') {

        $this->tabs->activateTab($a_tab_id);

        if (!empty($this->subtabs[$a_tab_id])) {
            foreach($this->subtabs[$a_tab_id] as $subtab) {
                $this->tabs->addSubTab($subtab['id'], $subtab['txt'], $subtab['url']);
            }
        }

        if (!empty($a_subtab_id)) {
            $this->tabs->activateSubTab($a_subtab_id);
        }
	}
}