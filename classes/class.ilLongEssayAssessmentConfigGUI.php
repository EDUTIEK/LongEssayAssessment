<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\Data\System\PluginConfig;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;

/**
 * Plugin Configuration GUI
 * @author Fred Neumann <fred.neumann@ilias.de>
 *
 * @ilCtrl_IsCalledBy ilLongEssayAssessmentConfigGUI: ilObjComponentSettingsGUI
 *
 */
class ilLongEssayAssessmentConfigGUI extends ilPluginConfigGUI
{
    private ilHelpGUI $help;
    /** @var Container */
    protected $dic;

    /** @var ilLongEssayAssessmentPlugin  */
    protected $plugin;

    /** @var \ILIAS\Plugin\LongEssayAssessment\Data\System\PluginConfig  */
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
    public function performCommand($cmd) : void
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
        $this->help = $DIC->help();

        switch ($this->dic->ctrl()->getNextClass()) {
            case 'ilpropertyformgui':
                $this->dic->ctrl()->forwardCommand($this->initConfigForm());
                break;

            default:
                switch ($cmd) {
                    case "configure":
                    case "saveConfig":
                        $this->$cmd();
                        break;
                }
        }
    }


    /**
     * Show base configuration screen
     */
    protected function configure()
    {
        $this->help->setScreenIdComponent($this->getPluginObject()->getId());
        $this->help->setScreenId("adm");

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
            $this->config->setWriterUrl((string) $form->getInput('writer_url'));
            $this->config->setCorrectorUrl((string) $form->getInput('corrector_url'));
            $this->config->setPrimaryColor((string) $form->getInput('primary_color'));
            $this->config->setPrimaryTextColor((string) $form->getInput('primary_text_color'));
            $this->config->setSimulateOffline((bool) $form->getInput('simulate_offline'));

            $di = LongEssayAssessmentDI::getInstance();
            $di->getSystemRepo()->save($this->config);

            $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
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

        $primary_color = new ilColorPickerInputGUI($this->plugin->txt('primary_color'), 'primary_color');
        $primary_color->setInfo($this->plugin->txt('primary_color_info'));
        $primary_color->setValue($this->config->getPrimaryColor());
        $form->addItem($primary_color);

        $primary_text_color = new ilColorPickerInputGUI($this->plugin->txt('primary_text_color'), 'primary_text_color');
        $primary_text_color->setInfo($this->plugin->txt('primary_text_color_info'));
        $primary_text_color->setValue($this->config->getPrimaryTextColor());
        $form->addItem($primary_text_color);

        $developer = new ilFormSectionHeaderGUI();
        $developer->setTitle($this->plugin->txt('developer_settings'));
        $developer->setInfo($this->plugin->txt('developer_settings_info'));
        $form->addItem($developer);

        $writer_url = new ilTextInputGUI($this->plugin->txt('writer_url'), 'writer_url');
        $writer_url->setInfo($this->plugin->txt('writer_url_info'));
        $writer_url->setValue($this->config->getWriterUrl());
        $form->addItem($writer_url);

        $corrector_url = new ilTextInputGUI($this->plugin->txt('corrector_url'), 'corrector_url');
        $corrector_url->setInfo($this->plugin->txt('corrector_url_info'));
        $corrector_url->setValue($this->config->getCorrectorUrl());
        $form->addItem($corrector_url);

        $simulate = new ilCheckboxInputGUI($this->plugin->txt('simulate_offline'), 'simulate_offline');
        $simulate->setInfo($this->plugin->txt('simulate_offline_info'));
        $simulate->setChecked($this->config->getSimulateOffline());
        $form->addItem($simulate);

        $form->addCommandButton('saveConfig', $this->lng->txt('save'));
        return $form;
    }
}
