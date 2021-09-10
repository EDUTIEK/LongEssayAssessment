<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayTask\Data\PluginConfig;

/**
 * Plugin Configuration GUI
 * @author Fred Neumann <fred.neumann@ilias.de>
 *
 * @ilCtrl_Calls: ilLongEssayTaskConfigGUI: ilPropertyFormGUI
 */
class ilLongEssayTaskConfigGUI extends ilPluginConfigGUI
{
    /** @var Container */
    protected $dic;

	/** @var ilLongEssayTaskPlugin  */
	protected $plugin;

	/** @var PluginConfig  */
	protected $config;

	/** @var ilTabsGUI  */
    protected $tabs;

    /** @var ilCtrl  */
    protected $ctrl;

    /** @var ilLanguage  */
	protected $lng;

    /** @var ilGlobalTemplateInterface  */
	protected $tpl;

    /** @var  ilToolbarGUI  */
    protected $toolbar;

    /**
	 * Handles all commands, default is "configure"
     * @throws Exception
	 */
	public function performCommand($cmd)
	{
        global $DIC;

        // this can't be in the constructor
        $this->dic = $DIC;
        $this->plugin = $this->getPluginObject();
        $this->config = $this->plugin->getConfig();
        $this->lng = $DIC->language();
        $this->tabs = $DIC->tabs();
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->toolbar = $DIC->toolbar();

        $this->setToolbar();

        switch ($this->dic->ctrl()->getNextClass())
        {
            case 'ilpropertyformgui':
                $this->dic->ctrl()->forwardCommand($this->initConfigForm());
                break;

            default:
                switch ($cmd)
                {
                    case "configure":
                    case "saveConfig":
                    case "updateLanguages":
                    case "generateDBUpdate":
                    case 'reloadControlStructure':
                        $this->$cmd();
                        break;
                }
        }
	}

    /**
     * Set the toolbar
     */
    protected function setToolbar()
    {
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));

        $button = ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, 'updateLanguages'));
        $button->setCaption($this->plugin->txt('update_languages'), false);
        $this->toolbar->addButtonInstance($button);

        $button = ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, 'reloadControlStructure'));
        $button->setCaption($this->plugin->txt('reload_control_structure'), false);
        $this->toolbar->addButtonInstance($button);

//        $button = ilLinkButton::getInstance();
//        $button->setUrl($this->ctrl->getLinkTarget($this, 'generateDBUpdate'));
//        $button->setCaption($this->plugin->txt('generate_db_update'), false);
//        $this->toolbar->addButtonInstance($button);
    }

    /**
	 * Show base configuration screen
	 */
	protected function configure()
	{
		$form = $this->initConfigForm();
		$this->tpl->setContent($form->getHtml());
	}

    /**
     * Save the basic settings
     */
    protected function saveConfig()
    {
        $form = $this->initConfigForm();
        if ($form->checkInput()) {
            $this->config->setEskriptUrl((string) $form->getInput('eskript_url'));
            $this->config->setEskriptKey((string) $form->getInput('eskript_key'));
            $this->config->save();
            ilUtil::sendSuccess($this->lng->txt('settings_saved'));
            $this->ctrl->redirect($this, 'configure');
        }
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHtml());
    }

    /**
     * Initialize the configuration form
     * @return ilPropertyFormGUI form object
     */
    protected function initConfigForm()
    {
        $form = new ilPropertyFormGUI();

        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->plugin->txt('configuration'));

        $eskript_url = new ilTextInputGUI($this->plugin->txt('eskript_url'), 'eskript_url');
        $eskript_url->setInfo($this->plugin->txt('eskript_url_info'));
        $eskript_url->setValue($this->config->getEskriptUrl());
        $form->addItem($eskript_url);

        $eskript_key = new ilTextInputGUI($this->plugin->txt('eskript_key'), 'eskript_key');
        $eskript_key->setInfo($this->plugin->txt('eskript_key_info'));
        $eskript_key->setValue($this->config->getEskriptKey());
        $form->addItem($eskript_key);

        $form->addCommandButton('saveConfig', $this->lng->txt('save'));
        return $form;
    }


    /**
     * Update Languages
     */
    protected function updateLanguages()
    {
        $this->plugin->updateLanguages();
        $this->ctrl->redirect($this, 'configure');
    }

    /**
     * Generate the db update steps for an active record object
     */
	protected function generateDBUpdate()
    {
        $arBuilder = new arBuilder(new \ILIAS\Plugin\LongEssayTask\Data\EditorSettings());
        $arBuilder->generateDBUpdateForInstallation();
    }


    /**
     * Reload the plugin control structure
     */
    protected function reloadControlStructure() {

        ilGlobalCache::flushAll();
        $this->plugin->reloadControlStructure();
        ilGlobalCache::flushAll();

//        $this->ctrl->redirect($this, 'configure');
    }
}