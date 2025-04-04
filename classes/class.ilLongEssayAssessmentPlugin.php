<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\Dependencies\PluginDic;
use ILIAS\Plugin\LongEssayAssessment\Setup\DBUpdateSteps10;
use ILIAS\Plugin\LongEssayAssessment\Task\ResourceResourceStakeholder;
use ILIAS\Plugin\LongEssayAssessment\UI\PluginTemplateFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Input\InputRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Item\ItemRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Statistic\StatisticRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Viewer\ViewerRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\PluginRenderer;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\EssayImageResourceStakeholder;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\PDFVersionResourceStakeholder;

/**
 * Basic plugin file
 */
class ilLongEssayAssessmentPlugin extends ilRepositoryObjectPlugin
{
    public const ID = "xlas";   // must be public for GUI and List GUI

    private const LANGUAGES = ['de'];
    private const PLUGIN_PATH = "public/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment";
    private const ICON_PATH = '/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment/resources//images';

    protected Container $ilias_dic;
    protected ilLanguage $lng;
    protected ilDBInterface $db;

    protected static $instance;

    public function __construct(
        \ilDBInterface $db,
        \ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        global $DIC;

        $this->ilias_dic = $DIC;
        $this->lng = $DIC->language();
        $this->db = $DIC->database();

        parent::__construct($db, $component_repository, $id);
    }

    /**
     * Get the dependency injection container of the plugin
     */
    public function dic()
    {
        return PluginDic::getInstance($this->ilias_dic, $this);
    }


    public function allowCopy(): bool
    {
        return true;
    }

    /**
     * Install the plugin with custom data
     */
    public function install(): void
    {
        parent::install();

        (new DBUpdateSteps10())->install($this->db);
    }


    /**
     * Uninstall the plugin
     * Overridden from ilPlugin::uninstall to catch an exception
     * that would be thrown by ilRepositoryObjectPlugin::beforeUninstall
     * if the last uninstall went wrong
     */
    public function uninstall(): bool
    {
        try {
            $rep_util = new ilRepUtil();
            $rep_util->deleteObjectType($this->getId());
        } catch (Exception $e) {
            // repo object type may already be deleted if the uninstall went wrong in the last call
            // do nothing here to try the uninstall again
        }

        $this->uninstallCustom();

        $this->getLanguageHandler()->uninstall();
        $this->component_repository->removeStateInformationOf($this->getId());
        return true;
    }

    /**
     * Uninstall custom data of this plugin
     */
    protected function uninstallCustom(): void
    {
        (new DBUpdateSteps10())->uninstall($this->db);
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            global $DIC;
            self::$instance = new self($DIC->database(), $DIC["component.repository"], self::ID);
        }
        return self::$instance;
    }

    /**
     * Check if the plugin supports a language
     */
    public function hasLanguage($a_lang_code): bool
    {
        return in_array($a_lang_code, self::LANGUAGES);
    }

    /**
     * Get the default Language with fallback to a supported langguage
     */
    public function getDefaultLanguage(): string
    {
        if ($this->hasLanguage($this->lng->getDefaultLanguage())) {
            return $this->lng->getDefaultLanguage();
        }
        return self::LANGUAGES[0];
    }

    /**
     * Get a plugin text and use the variable, if not translated, take the current language
     */
    public function txt(string $a_var, ?string $a_lang_code = null): string
    {
        if (isset($a_lang_code)) {
            $txt = $this->lng->txtlng($this->getPrefix(), $this->getPrefix()
                . "_" . $a_var, $a_lang_code);
        } else {
            $txt = parent::txt($a_var);
        }

        if (substr($txt, 0, 5) == '-rep_') {
            return $a_var;
        }
        return $txt;
    }

    public function exchangeUIRendererAfterInitialization(Container $dic): Closure
    {
        #return $dic->raw('ui.renderer');

        $this->init();
        //Safe the origin renderer closure
        $renderer = $dic->raw('ui.renderer');

        //return origin if plugin is not active
        if (!$this->isActive()) {
            return $renderer;
        }
        $this->dic(); // init plugin dic
        $dic->language()->loadLanguageModule($this->getPrefix());
        //else return own renderer with origin as default
        //be aware that you can not provide the renderer itself for the closure since its state changes
        return function () use ($dic, $renderer) {
            return new PluginRenderer(
                $renderer($dic),
                new ItemRenderer(
                    $dic["ui.factory"],
                    $dic[PluginTemplateFactory::class],
                    $dic["lng"],
                    $dic["ui.javascript_binding"],
                    $dic["ui.pathresolver"],
                    $dic["ui.data_factory"],
                    $dic["help.text_retriever"],
                    $dic["ui.upload_limit_resolver"]
                ),
                (new InputRenderer(
                    $dic["ui.factory"],
                    $dic[PluginTemplateFactory::class],
                    $dic["lng"],
                    $dic["ui.javascript_binding"],
                    $dic["ui.pathresolver"],
                    $dic["ui.data_factory"],
                    $dic["help.text_retriever"],
                    $dic["ui.upload_limit_resolver"]
                ))->setGlobalTemplate($dic["tpl"]),//ugly but the constructor is final :(
                new StatisticRenderer(
                    $dic["ui.factory"],
                    $dic[PluginTemplateFactory::class],
                    $dic["lng"],
                    $dic["ui.javascript_binding"],
                    $dic["ui.pathresolver"],
                    $dic["ui.data_factory"],
                    $dic["help.text_retriever"],
                    $dic["ui.upload_limit_resolver"]
                ),
                new ViewerRenderer(
                    $dic["ui.factory"],
                    $dic[PluginTemplateFactory::class],
                    $dic["lng"],
                    $dic["ui.javascript_binding"],
                    $dic["ui.pathresolver"],
                    $dic["ui.data_factory"],
                    $dic["help.text_retriever"],
                    $dic["ui.upload_limit_resolver"]
                )
            );
        };
    }

    /**
     * Handle an event
     * @deprecated - needs refactoring
     * @todo: refactor
     */
    public function handleEvent($a_component, $a_event, $a_parameter)
    {
        // todo refacoring
        //        if ('Services/User' == $a_component && 'deleteUser' == $a_event) {
        //            $usr_id = $a_parameter['usr_id'];
        //            $di = LongEssayAssessmentDI::getInstance();
        //            $writer_repo = $di->getWriterRepo();
        //            $writer = $writer_repo->getWritersByUserId($usr_id);
        //            foreach ($writer as $w) {
        //                $writer_repo->deleteWriter($w->getId());
        //            }
        //        }
    }

    public function getIconURL(string $name): string
    {
        return  ILIAS_HTTP_PATH . '/' . self::ICON_PATH . '/' . $name;
    }

    /**
     * Temporary override because of issues in the core with paths
     *
     * @param string $a_template
     * @param bool   $a_par1
     * @param bool   $a_par2
     * @return ilTemplate
     * @throws ilSystemStyleException
     * @throws ilTemplateException
     */
    public function getTemplate(string $a_template, bool $a_par1 = true, bool $a_par2 = true): ilTemplate
    {
        return new ilTemplate( $a_template, $a_par1, $a_par2, self::PLUGIN_PATH);
    }

}
