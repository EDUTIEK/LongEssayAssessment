<?php

namespace ILIAS\Plugin\LongEssayAssessment;

use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\DataService;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\System\SystemRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\Factory;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\InputFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\IconFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\ItemFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\PluginTemplateFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\UIService;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminService;
use ILIAS\Plugin\LongEssayAssessment\Task\LoggingService;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAssignmentsService;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\ServicesFactory;
use ILIAS\Plugin\LongEssayAssessment\Data\DataConstraints;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class LongEssayAssessmentDI
{
    protected static ?LongEssayAssessmentDI $instance = null;
    protected static bool $inited = false;

    protected $dataServices = [];
    protected $loggingServices = [];
    protected $writerAdminServices = [];
    protected $correctorAdminServices = [];
    protected $correctorAssignmentServices = [];

    protected \ILIAS\DI\Container $container;

    protected function __construct(\ILIAS\DI\Container $container)
    {
        $this->container = $container;
    }

    public function init(\ilLongEssayAssessmentPlugin $plugin)
    {
        if(self::$inited) {
            return;
        }

        $dic = $this->container;

        $dic["xlas.plugin"] = $plugin;

        $dic["xlas.data_constraints"] = function() use ($dic) {
            return new DataConstraints(
                new \ILIAS\Data\Factory(),
                $dic->language()
            );
        };

        $dic["xlas.custom_template_factory"] = function () use ($dic) {
            return new PluginTemplateFactory($dic["ui.template_factory"], $dic["xlas.plugin"], $dic["tpl"]);
        };

        $dic["xlas.custom_factory"] = function (\ILIAS\DI\Container $dic) {
            $data_factory = new \ILIAS\Data\Factory();
            $refinery = new \ILIAS\Refinery\Factory($data_factory, $dic["lng"]);
            return new Factory(
                new InputFactory(
                    $dic["ui.factory.input.field"],
                    $dic["ui.signal_generator"],
                    $data_factory,
                    $refinery,
                    $dic["lng"]
                ),
                new IconFactory(
                    $dic->ui()->factory()->symbol()->icon(),
                    $dic["xlas.plugin"]
                ),
                new ItemFactory(
                    $dic->ui()->factory()->symbol()->icon(),
                    $dic["xlas.plugin"],
                    $dic["ui.signal_generator"]
                )
            );
        };

        $dic["xlas.system_repository"] = function (\ILIAS\DI\Container $dic) {
            return new SystemRepository($dic->database(), $dic->logger()->xlas());
        };

        $dic["xlas.essay_repository"] = function (\ILIAS\DI\Container $dic) {
            return new EssayRepository(
                $dic->database(),
                $dic->logger()->xlas()
            );
        };

        $dic["xlas.corrector_repository"] = function (\ILIAS\DI\Container $dic) {
            return new CorrectorRepository(
                $dic->database(),
                $dic->logger()->xlas(),
                $dic["xlas.essay_repository"]
            );
        };

        $dic["xlas.writer_repository"] = function (\ILIAS\DI\Container $dic) {
            return new WriterRepository(
                $dic->database(),
                $dic->logger()->xlas(),
                $dic["xlas.essay_repository"],
                $dic["xlas.corrector_repository"]
            );
        };

        $dic["xlas.task_repository"] = function (\ILIAS\DI\Container $dic) {
            return new TaskRepository(
                $dic->database(),
                $dic->logger()->xlas(),
                $dic["xlas.essay_repository"],
                $dic["xlas.corrector_repository"],
                $dic["xlas.writer_repository"]
            );
        };

        $dic["xlas.object_repository"] = function (\ILIAS\DI\Container $dic) {
            return new ObjectRepository(
                $dic->database(),
                $dic->logger()->xlas(),
                $dic["xlas.essay_repository"],
                $dic["xlas.task_repository"]
            );
        };

        $dic["xlas.upload_temp"] = function (\ILIAS\DI\Container $dic) {
            return new ilLongEssayAssessmentUploadTempFile($dic->resourceStorage(), $dic->filesystem(), $dic->upload());
        };

        $dic["xlas.ui_service"] = function (\ILIAS\DI\Container $dic) {
            return new UIService($dic["lng"], $dic["refinery"]);
        };

        $dic["xlas.services_factory"] = function (\ILIAS\DI\Container $dic) {
            return new ServicesFactory($dic, $this);
        };

        self::$inited = true;
    }


    public static function getInstance(): LongEssayAssessmentDI
    {
        global $DIC;

        if (self::$instance === null) {
            self::$instance = new self($DIC);
        }

        return self::$instance;
    }

    public function constraints() : DataConstraints
    {
        return $this->container["xlas.data_constraints"];
    }

    public function services() : ServicesFactory
    {
        return $this->container["xlas.services_factory"];
    }


    public function getSystemRepo(): SystemRepository
    {
        return $this->container["xlas.system_repository"];
    }

    public function getObjectRepo(): ObjectRepository
    {
        return $this->container["xlas.object_repository"];
    }

    public function getTaskRepo(): TaskRepository
    {
        return $this->container["xlas.task_repository"];
    }

    public function getEssayRepo(): EssayRepository
    {
        return $this->container["xlas.essay_repository"];
    }

    public function getWriterRepo(): WriterRepository
    {
        return $this->container["xlas.writer_repository"];
    }

    public function getCorrectorRepo(): CorrectorRepository
    {
        return $this->container["xlas.corrector_repository"];
    }


    //    /**
    //     * @return ComponentRenderer
    //     */
    //    public function getUIRenderer()
    //    {
    //        return $this->container["xlas.custom_renderer"];
    //    }

    /**
     * @return Factory
     */
    public function getUIFactory(): Factory
    {
        return $this->container["xlas.custom_factory"];
    }

    /**
     * @return ilLongEssayAssessmentUploadTempFile
     */
    public function getUploadTempFile(): ilLongEssayAssessmentUploadTempFile
    {
        return $this->container["xlas.upload_temp"];
    }

    /**
     * @return UIService
     */
    public function getUIService(): UIService
    {
        return $this->container["xlas.ui_service"];
    }


    public function getDataService(int $task_id) : DataService
    {
        if (!isset($this->dataServices[$task_id])) {
            $this->dataServices[$task_id] = new DataService($task_id);
        }
        return $this->dataServices[$task_id];
    }

    public function getLoggingService(int $task_id) : LoggingService
    {
        if (!isset($this->loggingServices[$task_id])) {
            $this->loggingServices[$task_id] = new LoggingService($task_id);
        }
        return $this->loggingServices[$task_id];
    }


    public function getWriterAdminService(int $task_id) : WriterAdminService
    {
        if (!isset($this->writerAdminServices[$task_id])) {
            $this->writerAdminServices[$task_id] = new WriterAdminService($task_id);
        }
        return $this->writerAdminServices[$task_id];
    }

    public function getCorrectorAdminService(int $task_id) : CorrectorAdminService
    {
        if (!isset($this->correctorAdminServices[$task_id])) {
            $this->correctorAdminServices[$task_id] = new CorrectorAdminService($task_id);
        }
        return $this->correctorAdminServices[$task_id];
    }

    public function getCorrectorAssignmentService(int $task_id) : CorrectorAssignmentsService
    {
        if (!isset($this->correctorAssignmentServices[$task_id])) {
            $this->correctorAssignmentServices[$task_id] = new CorrectorAssignmentsService($task_id);
        }
        return $this->correctorAssignmentServices[$task_id];
    }

    public function getPlugin() : \ilLongEssayAssessmentPlugin
    {
        return $this->container["xlas.plugin"];
    }
}
