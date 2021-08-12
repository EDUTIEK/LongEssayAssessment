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
                        $this->activateTab('task', 'orgaSettings');
                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayTask\Task\OrgaSettingsGUI($this));
                    }
                    break;
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
                default:
                    $this->$cmd();
                    break;
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
        // TODO: check permissions and decide which gui can be shown
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

        // available sub tabs for the "task definition" tab
        if ($this->object->canEditOrgaSettings()) {
            $this->subtabs['task'][] = [
                'id' => 'OrgaSettings',
                'txt' => $this->plugin->txt('orga_settings'),
                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessaytask\task\orgasettingsgui')
            ];
        }

        // "task definition" tab
        if (!empty($this->subtabs['task'])) {
            $this->tabs->addTab('TaskDefinition', $this->plugin->txt('task_definition'), $this->subtabs['task'][0]['url']);
        }


        // standard info screen tab
        $this->addInfoTab();

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