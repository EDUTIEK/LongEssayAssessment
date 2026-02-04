<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\Dependencies\PluginDic;
use ILIAS\Plugin\LongEssayAssessment\Setup\DBUpdateSteps10;
use ILIAS\Plugin\LongEssayAssessment\UI\PluginTemplateFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Input\InputRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Item\ItemRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Statistic\StatisticRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Viewer\ViewerRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\PluginRenderer;
use ILIAS\Setup\ImplementationOfInterfaceFinder;
use ILIAS\Plugin\LongEssayAssessment\Cron\CronJobInterface;
use ILIAS\Plugin\LongEssayAssessment\Cron\CronJob;
use Edutiek\AssessmentService\System\EventHandling\Events\UserRemoved;

/**
 * Basic plugin file
 */
class ilLongEssayAssessmentPlugin extends ilRepositoryObjectPlugin implements \ilCronJobProvider
{
    public const ID = 'xlas';   // must be public for GUI and List GUI

    private const LANGUAGES = ['de'];
    private const PLUGIN_PATH = 'public/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment';
    private const ASSETS_PATH = 'components/EDUTIEK/LongEssayAssessment';

    protected Container $ilias_dic;
    protected ilLanguage $lng;
    protected ilDBInterface $db;
    /**
     * @var ilCronJob[]
     */
    private array $cron_objects = [];
    private ?array $cron_classes = null;
    protected static $instance;

    /**
     * Get the title icon
     * Used for object list, creation, gui
     * used by info, export, permission table
     */
    public static function _getIcon(string $a_type): string
    {
        return self::ASSETS_PATH . '/images/icon_xlas.svg';
    }

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


    public function asset(string $sub_path): string
    {
        return self::ASSETS_PATH . '/' . $sub_path;
    }

    /**
     * Get the dependency injection container of the plugin
     */
    public function dic(): PluginDic
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

    public function update(): bool
    {
        parent::update();
        (new DBUpdateSteps10())->install($this->db);
        return true;
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
     */
    public function handleEvent($a_component, $a_event, $a_parameter)
    {
        try {
            if ($a_component === 'Services/User' && $a_event === 'deleteUser' && isset($a_parameter['usr_id'])) {
                $usr_id = (int) $a_parameter['usr_id'];
                $this->dic()->eventDispatcher()->dispatchEvent(new UserRemoved($usr_id));
            }
        } catch (Throwable $e) {
            $this->ilias_dic->logger()->xlas()->error($e);
        }
    }

    /**
     * Get a template of the plugin
     * @param string $a_template
     */
    public function getTemplate(string $a_template, bool $a_par1 = true, bool $a_par2 = true): ilTemplate
    {
        return new ilTemplate($a_template, $a_par1, $a_par2, self::PLUGIN_PATH);
    }

    private function getJobClasses()
    {
        return $this->cron_classes ??= [
            //ReviewNotificationCronJob::id() => ReviewNotificationCronJob::class
        ];
    }

    private function getJobObject(string $class_name): ilCronJob
    {
        return $this->cron_objects[$class_name] ??= new $class_name($this, $this->dic(), $this->ilias_dic);
    }

    public function getCronJobInstances(): array
    {
        $jobs = [];

        foreach ($this->getJobClasses() as $id => $class_name) {
            $jobs[] = $this->getJobObject($class_name);
        }

        return $jobs;
    }

    public function getCronJobInstance($jobId): ilCronJob
    {
        $jobs = $this->getJobClasses();
        if (!isset($jobs[$jobId])) {
            throw new ilCronException(
                "Job [$jobId] not found."
            );
        }
        return $this->getJobObject($jobs[$jobId]);
    }
}
