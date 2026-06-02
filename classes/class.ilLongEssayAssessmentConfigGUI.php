<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use Edutiek\AssessmentService\System\Config\FullService as ConfigService;
use Edutiek\AssessmentService\System\Data\Config;
use ILIAS\DI\Container;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory as UiFactory;
use ILIAS\UI\Renderer;
use ILIAS\Data\Color;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    private Container $dic;
    private ilLongEssayAssessmentPlugin $plugin;
    private \ILIAS\Plugin\LongEssayAssessment\UI\Factory $plugin_ui_factory;
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;
    protected ilToolbarGUI $toolbar;
    protected UiFactory $ui_factory;
    protected Renderer $renderer;
    /** @var RequestInterface|ServerRequestInterface */
    protected RequestInterface $request;

    protected ConfigService $service;
    private Config $config;

    /**
     * Handles all commands, default is "configure"
     * @throws Exception
     */
    public function performCommand($cmd): void
    {
        global $DIC;

        // this can't be in the constructor
        $this->dic = $DIC;
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->toolbar = $DIC->toolbar();
        $this->help = $DIC->help();
        $this->ui_factory = $this->dic->ui()->factory();
        $this->plugin_ui_factory = $this->plugin->dic()->uiFactory();
        $this->renderer = $this->dic->ui()->renderer();
        $this->request = $this->dic->http()->request();

        $this->service = $this->plugin->dic()->system()->config();
        $this->config = $this->service->getConfig();

        switch ($cmd) {
            case "configure":
            case "saveConfig":
                $this->$cmd();
                break;
        }
    }

    protected function configure()
    {
        $this->help->setScreenIdComponent($this->getPluginObject()->getId());
        $this->help->setScreenId("adm");

        $form = $this->buildForm();
        $this->tpl->setContent($this->renderer->render($form));
    }

    protected function saveConfig()
    {
        $form = $this->buildForm()->withRequest($this->request);
        $data = $form->getData();

        if ($data !== null) {

            $this->config->setPrimaryColor($this->colorValue($data['prod']['primary_color']));
            $this->config->setPrimaryTextColor($this->colorValue($data['prod']['primary_text_color']));

            $this->config->setCorrector1Color($this->colorValue($data['prod']['corrector1_color']));
            $this->config->setCorrector2Color($this->colorValue($data['prod']['corrector2_color']));
            $this->config->setCorrector3Color($this->colorValue($data['prod']['corrector3_color']));

            $this->config->setPathToGhostscript($data['prod']['path_to_ghostscript'] ?? null);
            $this->config->setPathToPdftk($data['prod']['path_to_pdftk'] ?? null);
            $this->config->setHashAlgo((string) $data['prod']['hash_algo'] ?? '');

            $this->config->setWriterUrl($data['dev']['writer_url'] ?? null);
            $this->config->setCorrectorUrl($data['dev']['corrector_url'] ?? null);
            $this->config->setSimulateOffline((bool) $data['dev']['simulate_offline'] ?? false);

            $this->service->saveConfig($this->config);
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, 'configure');

        }
        $this->tpl->setContent($this->renderer->render($form));
    }


    protected function buildForm(): Standard
    {

        $factory = $this->ui_factory->input()->field();
        $pfactory = $this->plugin_ui_factory->field();

        $prod = [];

        $prod['primary_color'] = $pfactory->colorSelect(
            $this->plugin->txt('primary_color'),
            $this->plugin->txt('primary_color_info')
        )->withValue('#' . $this->config->getPrimaryColor() ?? Config::DEFAULT_PRIMARY_COLOR);

        $prod['primary_text_color'] = $pfactory->colorSelect(
            $this->plugin->txt('primary_text_color'),
            $this->plugin->txt('primary_text_color_info')
        )->withValue('#' . $this->config->getPrimaryTextColor() ?? Config::DEFAULT_PRIMARY_TEXT_COLOR);

        $prod['corrector1_color'] = $pfactory->colorSelect(
            $this->plugin->txt('corrector1_color'),
            $this->plugin->txt('corrector1_color_info')
        )->withValue('#' . $this->config->getCorrector1Color() ?? Config::DEFAULT_CORRECTOR1_COLOR);

        $prod['corrector2_color'] = $pfactory->colorSelect(
            $this->plugin->txt('corrector2_color'),
            $this->plugin->txt('corrector2_color_info')
        )->withValue('#' . $this->config->getCorrector2Color() ?? Config::DEFAULT_CORRECTOR2_COLOR);

        $prod['corrector3_color'] = $pfactory->colorSelect(
            $this->plugin->txt('corrector3_color'),
            $this->plugin->txt('corrector3_color_info')
        )->withValue('#' . $this->config->getCorrector3Color() ?? Config::DEFAULT_CORRECTOR3_COLOR);

        $prod['path_to_ghostscript'] = $factory->text(
            $this->plugin->txt('path_to_ghostscript'),
            $this->plugin->txt('path_to_ghostscript_info') . '<br>' . sprintf(
                $this->plugin->txt('ghostscript_used'),
                '<strong>' . $this->service->getPathToGhostscript() . '</strong>'
            )
        )->withValue($this->config->getPathToGhostscript() ?? '');

        $prod['path_to_pdftk'] = $factory->text(
            $this->plugin->txt('path_to_pdftk'),
            $this->plugin->txt('path_to_pdftk_info') . '<br>' . sprintf(
                $this->plugin->txt('pdftk_used'),
                '<strong>' . $this->service->getPathToPdftk() . '</strong>'
            )
        )->withValue($this->config->getPathToPdftk() ?? '');


        $prod['hash_algo'] = $factory->select(
            $this->plugin->txt('hash_algo'),
            $this->config->getHashAlgoOptions(),
            $this->plugin->txt('hash_algo_info')
        )->withValue($this->config->getHashAlgo() ?? Config::DEFAULT_HASH_ALGO);

        $dev = [];

        $dev['writer_url'] = $factory->text(
            $this->plugin->txt('writer_url'),
            $this->plugin->txt('writer_url_info')
        )->withValue($this->config->getWriterUrl() ?? '');

        $dev['corrector_url'] = $factory->text(
            $this->plugin->txt('corrector_url'),
            $this->plugin->txt('corrector_url_info')
        )->withValue($this->config->getCorrectorUrl() ?? '');

        $dev['simulate_offline'] = $factory->checkbox(
            $this->plugin->txt('simulate_offline'),
            $this->plugin->txt('simulate_offline_info')
        )->withValue($this->config->getSimulateOffline());

        $sections = [
            'prod' => $factory->section($prod, $this->plugin->txt('configuration')),
            'dev' => $factory->section(
                $dev,
                $this->plugin->txt('developer_settings'),
                $this->plugin->txt('developer_settings_info')
            )
        ];

        return $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'saveConfig'),
            $sections,
        );
    }

    private function colorValue(Color $color)
    {
        return str_replace("#", "", $color->asHex());
    }
}
