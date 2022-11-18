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
use ILIAS\Plugin\LongEssayTask\UI\Implementation\FieldFactory;
use ILIAS\Plugin\LongEssayTask\UI\Implementation\IconFactory;
use ILIAS\Plugin\LongEssayTask\UI\PluginLoader;
use ILIAS\Plugin\LongEssayTask\UI\PluginRendererFactory;
use ILIAS\Plugin\LongEssayTask\UI\PluginTemplateFactory;
use ILIAS\Plugin\LongEssayTask\WriterAdmin\WriterAdminService;
use ILIAS\UI\Implementation\Render\ComponentRenderer;
use Pimple\Container;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class LongEssayTaskDI
{
    protected static $instance;
    protected $dataServices = [];
    protected $writerAdminServices = [];
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

		$dic["custom_renderer_loader"] =  function (\ILIAS\DI\Container $dic ) {
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

		$dic["custom_renderer"] = function (\ILIAS\DI\Container $dic) {
			return new \ILIAS\UI\Implementation\DefaultRenderer(
				$dic["custom_renderer_loader"]
			);
		};

		$dic["custom_factory"] = function (\ILIAS\DI\Container $dic) {
			$data_factory = new \ILIAS\Data\Factory();
			$refinery = new \ILIAS\Refinery\Factory($data_factory, $dic["lng"]);
			return new Factory(
				new FieldFactory($dic["ui.signal_generator"],
					$data_factory,
					$refinery,
					$dic["lng"]),
				new IconFactory(
					$dic->ui()->factory()->symbol()->icon(),
					$dic["plugin"]
				)
			);
		};

		$dic["essay_repository"] = function (\ILIAS\DI\Container $dic) {
			return new EssayDatabaseRepository($dic["ilDB"]);
		};

		$dic["corrector_repository"] = function (\ILIAS\DI\Container $dic) {
			return new CorrectorDatabaseRepository($dic["ilDB"], $dic["essay_repository"]);
		};

		$dic["writer_repository"] = function (\ILIAS\DI\Container $dic) {
			return new WriterDatabaseRepository($dic["ilDB"], $dic["essay_repository"], $dic["corrector_repository"]);
		};

		$dic["task_repository"] = function (\ILIAS\DI\Container $dic) {
			return new TaskDatabaseRepository(
				$dic["ilDB"],
				$dic["essay_repository"],
				$dic["corrector_repository"],
				$dic["writer_repository"]);
		};

		$dic["object_repository"] = function (\ILIAS\DI\Container $dic) {
			return new ObjectDatabaseRepository(
				$dic["ilDB"],
				$dic["essay_repository"],
				$dic["task_repository"]
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
		return $this->container["object_repository"];
    }

    /**
     * @return TaskRepository
     */
    public function getTaskRepo(): TaskRepository
    {
		return $this->container["task_repository"];
    }

    /**
     * @return EssayRepository
     */
    public function getEssayRepo(): EssayRepository
    {
		return $this->container["essay_repository"];
    }

    /**
     * @return WriterRepository
     */
    public function getWriterRepo(): WriterRepository
    {
		return $this->container["writer_repository"];
    }

    /**
     * @return CorrectorRepository
     */
    public function getCorrectorRepo(): CorrectorRepository
    {
		return $this->container["corrector_repository"];
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
     * @return WriterAdminService
     */
    public function getWriterAdminService(int $task_id) : WriterAdminService
    {
        if (!isset($this->writerAdminServices[$task_id])) {
            $this->writerAdminServices[$task_id] = new WriterAdminService($task_id);
        }
        return $this->writerAdminServices[$task_id];
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
	public function getUIRenderer()
	{
		return $this->container["custom_renderer"];
	}

	/**
	 * @return Factory
	 */
	public function getUIFactory()
	{
		return $this->container["custom_factory"];
	}

}