<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\Data\System\PluginConfig;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\Task\ResourceResourceStakeholder;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\InputRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\ItemRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\PluginRenderer;

/**
 * Basic plugin file
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class ilLongEssayAssessmentPlugin extends ilRepositoryObjectPlugin
{
     const ID = "xlas";

    /** @var Container */
    protected $dic;

    /** @var self */
    protected static $instance;


    /**
     * Constructor.
     */
    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();
        require_once __DIR__ . '/../vendor/autoload.php';

		$di = LongEssayAssessmentDI::getInstance();
		$di->init($this);
    }

    /**
     * Get the Plugin name
     * must correspond to the plugin subdirectory
     * @return string
     */
    public function getPluginName()
	{
		return "LongEssayAssessment";
	}

    /**
     * @inheritdoc
     */
    public function getParentTypes()
    {
        return array("cat", "crs", "grp", "fold");
    }

    /**
     * @inheritdoc
     */
    public function allowCopy()
    {
        return true;
    }

    /**
     * Uninstall custom data of this plugin
     */
    protected function uninstallCustom()
    {
		$tables = ["xlas_access_token", "xlas_alert", "xlas_corr_setting", "xlas_corrector", "xlas_corrector_ass",
			"xlas_corrector_comment", "xlas_corrector_summary", "xlas_crit_points", "xlas_editor_comment",
			"xlas_editor_history", "xlas_editor_notice", "xlas_editor_settings", "xlas_essay", "xlas_grade_level",
            "xlas_log_entry",
			"xlas_object_settings", "xlas_participant", "xlas_plugin_config", "xlas_rating_crit", "xlas_task_settings",
			"xlas_time_extension", "xlas_writer_notice", "xlas_writer", "xlas_writer_comment", "xlas_writer_history",
            "xlas_resource", "xlas_location"];

		$files = $this->dic->database()->query("SELECT file_id FROM xlas_resource")->fetchAssoc();

		foreach ($files as $file){
			if($identifier = $this->dic->resourceStorage()->manage()->find($file["file_id"])){
				$this->dic->resourceStorage()->manage()->remove($identifier, new ResourceResourceStakeholder());
			}
		}

		foreach($tables as $table){
			if ($this->dic->database()->tableExists($table)){
				$this->dic->database()->dropTable($table);
			}
		}
		//TODO RBAC?
    }

    /**
     * Get the plugin instance
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the plugin configuration with loaded values
     */
    public function getConfig(): PluginConfig
    {
        $di = LongEssayAssessmentDI::getInstance();
        return $di->getSystemRepo()->getPluginConfig();
    }


    /**
     * Check if the current user has administrative access
     * @return bool
     */
    public function hasAdminAccess()
    {
        return $this->dic->rbac()->system()->checkAccess("visible", SYSTEM_FOLDER_ID);
    }


    /**
     * Get a plugin text and use the variable, if not translated
     * @param string $a_var
     * @return string
     */
    public function txt(string $a_var) : string
    {
        $txt = parent::txt($a_var);
        if (substr($txt, 0, 5) == '-rep_') {
            return $a_var;
        }
        return $txt;
    }


    public function reloadControlStructure() {
        // load control structure
        $structure_reader = new ilCtrlStructureReader();
        $structure_reader->readStructure(
            true,
            "./" . $this->getDirectory(),
            $this->getPrefix(),
            $this->getDirectory()
        );

        // add config gui to the ctrl calls
        $this->dic->ctrl()->insertCtrlCalls(
            "ilobjcomponentsettingsgui",
            ilPlugin::getConfigureClassName(["name" => $this->getPluginName()]),
            $this->getPrefix()
        );

        $this->readEventListening();
    }


	public function exchangeUIRendererAfterInitialization(Container $dic): Closure
	{
		$this->init();
		$custom_dic =
		//Safe the origin renderer closure
		$renderer = $dic->raw('ui.renderer');

		//return origin if plugin is not active
		if (!$this->isActive()) {
			return $renderer;
		}

		//else return own renderer with origin as default
		//be aware that you can not provide the renderer itself for the closure since its state changes
		return function () use ($dic, $renderer) {
			return new PluginRenderer(
				$renderer($dic),
				new ItemRenderer(
					$dic["ui.factory"],
					$dic["xlas.custom_template_factory"],
					$dic["lng"],
					$dic["ui.javascript_binding"],
					$dic["refinery"],
					$dic["ui.pathresolver"]
				),
				new InputRenderer(
					$dic["ui.factory"],
					$dic["xlas.custom_template_factory"],
					$dic["lng"],
					$dic["ui.javascript_binding"],
					$dic["refinery"],
					$dic["ui.pathresolver"]
				)
			);
		};
	}

}