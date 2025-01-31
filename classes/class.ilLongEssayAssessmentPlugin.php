<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\Dependencies\PluginDic;
use ILIAS\Plugin\LongEssayAssessment\Task\ResourceResourceStakeholder;
use ILIAS\Plugin\LongEssayAssessment\UI\PluginTemplateFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\InputRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\ItemRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\StatisticRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\ViewerRenderer;
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
     * Init the local autoload of all external plugin dependencies
     *  This is not done in init() to avoid conflicts with other package versions in ILIAS
     *  Note: init() is called from the ilPlugin constructor in ILIAS initialisation
     *
     *  The local autoload needs only to be initialized:
     *  - for the GUI of the plugin
     *  - for the entry points of REST calls
     *  - for a cron job
     */
    public function dic()
    {
        return PluginDic::getInstance($this->ilias_dic, $this);
    }

    /**
     * Get the plugin path
     * must be relative to the ILIAS directory without leading and trailing slash
     */
    public function getPluginPath(): string
    {
        return 'Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment';
    }

    public function allowCopy(): bool
    {
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
            // repo object type may already be deleted
            // if the uninstallCustom went wrong in the last call
            // do nothing here
            // to try the uninstallCustom again
        }

        $this->uninstallCustom();

        $this->getLanguageHandler()->uninstall();
        $this->component_repository->removeStateInformationOf($this->getId());
        return true;
    }

    /**
     * Uninstall custom data of this plugin
     * @todo: refactor
     */
    protected function uninstallCustom(): void
    {
        $tables = [
            'xlas_access_token',
            'xlas_alert',
            'xlas_corrector',
            'xlas_corrector_ass',
            'xlas_corrector_comment',
            'xlas_corrector_prefs',
            'xlas_corrector_summary',
            'xlas_corr_setting',
            'xlas_crit_points',
            'xlas_editor_settings',
            'xlas_essay',
            'xlas_essay_image',
            'xlas_grade_level',
            'xlas_location',
            'xlas_log_entry',
            'xlas_object_settings',
            'xlas_pdf_settings',
            'xlas_plugin_config',
            'xlas_rating_crit',
            'xlas_resource',
            'xlas_task_settings',
            'xlas_time_extension',
            'xlas_writer',
            'xlas_writer_comment',
            'xlas_writer_history',
            'xlas_writer_notice',
            'xlas_writer_prefs'
        ];

        if ($this->db->tableExists('xlas_resource')) {
            $result = $this->db->query("SELECT file_id FROM xlas_resource WHERE file_id IS NOT NULL");
            while ($row = $this->db->fetchAssoc($result)) {
                if ($identifier = $this->ilias_dic->resourceStorage()->manage()->find($row["file_id"])) {
                    $this->ilias_dic->resourceStorage()->manage()->remove($identifier, new ResourceResourceStakeholder());
                }
            }
        }

        if ($this->db->tableExists('xlas_essay')) {
            $result = $this->db->query("SELECT pdf_version FROM xlas_essay WHERE pdf_version IS NOT NULL");
            while ($row = $this->db->fetchAssoc($result)) {
                if ($identifier = $this->ilias_dic->resourceStorage()->manage()->find($row["pdf_version"])) {
                    $this->ilias_dic->resourceStorage()->manage()->remove($identifier, new PDFVersionResourceStakeholder());
                }
            }
        }

        if ($this->db->tableExists('xlas_essay_image')) {
            $result = $this->db->query("SELECT file_id FROM xlas_essay_image");
            while ($row = $this->db->fetchAssoc($result)) {
                if ($identifier = $this->ilias_dic->resourceStorage()->manage()->find($row["file_id"])) {
                    $this->ilias_dic->resourceStorage()->manage()->remove($identifier, new EssayImageResourceStakeholder());
                }
            }
        }

        foreach ($tables as $table) {
            if ($this->db->tableExists($table)) {
                $this->db->dropTable($table);
            }
        }
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
        return $dic->raw('ui.renderer');

        $this->init();
        //Safe the origin renderer closure
        $renderer = $dic->raw('ui.renderer');

        //return origin if plugin is not active
        if (!$this->isActive()) {
            return $renderer;
        }

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
                new InputRenderer(
                    $dic["ui.factory"],
                    $dic[PluginTemplateFactory::class],
                    $dic["lng"],
                    $dic["ui.javascript_binding"],
                    $dic["ui.pathresolver"],
                    $dic["ui.data_factory"],
                    $dic["help.text_retriever"],
                    $dic["ui.upload_limit_resolver"]
                ),
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

}
