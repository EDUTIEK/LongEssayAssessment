<?php

namespace ILIAS\Plugin\LongEssayTask;


use ILIAS\Plugin\LongEssayTask\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorRepository;
use ILIAS\Plugin\LongEssayTask\Data\DataService;
use ILIAS\Plugin\LongEssayTask\Data\EssayDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\EssayRepository;
use ILIAS\Plugin\LongEssayTask\Data\ObjectDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\ObjectRepository;
use ILIAS\Plugin\LongEssayTask\Data\TaskDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\TaskRepository;
use ILIAS\Plugin\LongEssayTask\Data\WriterDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\WriterRepository;
use ILIAS\Plugin\LongEssayTask\UI\Implementation\Factory;
use ILIAS\Plugin\LongEssayTask\UI\PluginLoader;
use ILIAS\Plugin\LongEssayTask\UI\PluginRendererFactory;
use ILIAS\Plugin\LongEssayTask\UI\PluginTemplateFactory;
use ILIAS\UI\Implementation\Render\ComponentRenderer;
use Pimple\Container;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class LongEssayTaskDI
{
    protected static $instance;
    protected $object;
    protected $task;
    protected $essay;
    protected $writer;
    protected $corrector;
    protected $dataServices = [];
    protected $correctorAdminServices = [];

	/**
	 * @var    \ILIAS\DI\Container
	 */
	protected $container;

	protected function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function init(\ilPlugin $plugin){
		$dic = $this->container;

		$dic["plugin"] = $plugin;

		$dic["custom_renderer_loader"] =  function ($dic ) {
			return new PluginLoader($dic["ui.component_renderer_loader"],
				new PluginRendererFactory(
					$dic["ui.factory"],
					new PluginTemplateFactory($dic["ui.template_factory"], $dic["plugin"], $dic["tpl"]),
					$dic["lng"],
					$dic["ui.javascript_binding"],
					$dic["refinery"],
					$dic["ui.pathresolver"]
				)
			);
		};

		$dic["custom_renderer"] = function ($dic) {
			return new \ILIAS\UI\Implementation\DefaultRenderer(
				$dic["custom_renderer_loader"]
			);
		};

		$dic["custom_factory"] = function ($dic) {
			$data_factory = new \ILIAS\Data\Factory();
			$refinery = new \ILIAS\Refinery\Factory($data_factory, $dic["lng"]);
			return new Factory(
				$dic["ui.signal_generator"],
				$data_factory,
				$refinery,
				$dic["lng"]
			);
		};
	}



    public static function getInstance(): LongEssayTaskDI
    {
		global $DIC;

        if (self::$instance === null) {
            self::$instance = new self($DIC);
        }

        return self::$instance;
    }

    /**
     * @return ObjectRepository
     */
    public function getObjectRepo(): ObjectRepository
    {
        if ($this->object === null) {
            $this->object = new ObjectDatabaseRepository();
        }

        return $this->object;
    }

    /**
     * @return TaskRepository
     */
    public function getTaskRepo(): TaskRepository
    {
        if ($this->task === null) {
            $this->task = new TaskDatabaseRepository();
        }

        return $this->task;
    }

    /**
     * @return EssayRepository
     */
    public function getEssayRepo(): EssayRepository
    {
        if ($this->essay === null) {
            $this->essay = new EssayDatabaseRepository();
        }

        return $this->essay;
    }

    /**
     * @return WriterRepository
     */
    public function getWriterRepo(): WriterRepository
    {
        if ($this->writer === null) {
            $this->writer = new WriterDatabaseRepository();
        }

        return $this->writer;
    }

    /**
     * @return CorrectorRepository
     */
    public function getCorrectorRepo(): CorrectorRepository
    {
        if ($this->corrector === null) {
            $this->corrector = new CorrectorDatabaseRepository();
        }

        return $this->corrector;
    }

    /**
     * @param int $task_id
     * @return DataService
     */
    public function getDataService(int $task_id) : DataService
    {
        if (!isset($this->dataServices[$task_id])) {
            $this->dataServices[$task_id] = new DataService($task_id);
        }
        return $this->dataServices[$task_id];
    }

    /**
     * @param int $task_id
     * @return CorrectorAdminService
     */
    public function getCorrectorAdminService(int $task_id) : CorrectorAdminService
    {
        if (!isset($this->correctorAdminServices[$task_id])) {
            $this->correctorAdminServices[$task_id] = new CorrectorAdminService($task_id);
        }
        return $this->correctorAdminServices[$task_id];
    }

	/**
	 * @return ComponentRenderer
	 */
	public function custom_renderer()
	{
		return $this->container["custom_renderer"];
	}

	/**
	 * @return Factory
	 */
	public function custom_factory()
	{
		return $this->container["custom_factory"];
	}

}