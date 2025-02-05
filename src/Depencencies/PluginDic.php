<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use Edutiek\AssessmentService\System\Api\Factory as SystemFactory;
use Edutiek\AssessmentService\System\Api\ForClients as SystemApi;
use Edutiek\AssessmentService\Assessment\Api\Factory as AssessmentFactory;
use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use Edutiek\AssessmentService\Assessment\Api\ForRest as RestApi;
use Edutiek\AssessmentService\EssayTask\Api\Factory as EssayTaskFactory;
use Edutiek\AssessmentService\EssayTask\Api\ForClients as EssayTaskApi;
use Edutiek\AssessmentService\Task\Api\Factory as TaskFactory;
use Edutiek\AssessmentService\Task\Api\ForClients as TaskApi;
use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\Common\Constraints\DataConstraints;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\ilLongEssayAssessmentUploadTempFile;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\Factory;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\IconFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\InputFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\ItemFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\StatisticFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\ViewerFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\PluginTemplateFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\UIService;
use ilLongEssayAssessmentPlugin;

/**
 * Local Dependency Injection Container of the Plugin
 */
class PluginDic
{
    protected static ?self $instance = null;
    protected Container $dic;

    /**
     * Get the dependency injection container of the plugin
     *
     * Init the local autoload of all external plugin dependencies
     *  This is separated from init() to avoid conflicts with other package versions in ILIAS
     *  Note: init() is called from the ilPlugin constructor in ILIAS initialisation
     *
     *  The local autoload needs only to be initialized:
     *  - for the GUI of the plugin
     *  - for the entry points of REST calls
     *  - maybe later for a cron job
     */
    public static function getInstance(Container $dic, ilLongEssayAssessmentPlugin $plugin): self
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        return self::$instance ??= new self($dic, $plugin);
    }

    protected function __construct(Container $dic, ilLongEssayAssessmentPlugin $plugin)
    {
        $this->dic = $dic;

        $dic[ilLongEssayAssessmentPlugin::class] = $plugin;

        $dic[DataConstraints::class] = function () use ($dic) {
            return new DataConstraints(
                new \ILIAS\Data\Factory(),
                $dic->language()
            );
        };

        $dic[PluginTemplateFactory::class] = function () use ($dic) {
            return new PluginTemplateFactory(
                $dic["ui.template_factory"],
                $dic[ilLongEssayAssessmentPlugin::class],
                $dic->ui()->mainTemplate()
            );
        };

        $dic[Factory::class] = function (Container $dic) {
            $data_factory = new \ILIAS\Data\Factory();
            $refinery = new \ILIAS\Refinery\Factory($data_factory, $dic["lng"]);
            return new Factory(
                new InputFactory(
                    $dic->ui()->factory()->input()->field(),
                    $dic["ui.signal_generator"],
                    $data_factory,
                    $refinery,
                    $dic->language()
                ),
                new IconFactory(
                    $dic->ui()->factory()->symbol()->icon(),
                    $this->plugin()
                ),
                new ItemFactory(
                    $dic->ui()->factory()->symbol()->icon(),
                    $this->plugin(),
                    $dic["ui.signal_generator"]
                ),
                new StatisticFactory(),
                new ViewerFactory()
            );
        };

        $dic[ilLongEssayAssessmentUploadTempFile::class] = function (Container $dic) {
            return new ilLongEssayAssessmentUploadTempFile($dic->resourceStorage(), $dic->filesystem(), $dic->upload());
        };

        $dic[UIService::class] = function (Container $dic) {
            return new UIService($dic[ilLongEssayAssessmentPlugin::class], $dic["lng"], $dic["refinery"]);
        };

        $dic[Generate::class] = function (Container $dic) {
            return new Generate(__DIR__ . '/../../artifacts');
        };

        $dic[SystemDic::class] = function (Container $dic) {
            return new SystemDic($dic);
        };

        $dic[SystemFactory::class] = function (Container $dic) {
            return (new SystemFactory($dic[SystemDic::class]));
        };

        $dic[AssessmentDic::class] = function (Container $dic) {
            return new AssessmentDic($dic);
        };

        $dic[AssessmentFactory::class] = function (Container $dic) {
            return (new AssessmentFactory($dic[AssessmentDic::class]));
        };

        $dic[EssayTaskDic::class] = function (Container $dic) {
            return new EssayTaskDic($dic);
        };

        $dic[EssayTaskFactory::class] = function (Container $dic) {
            return (new EssayTaskFactory($dic[AssessmentDic::class]));
        };

        $dic[TaskDic::class] = function (Container $dic) {
            return new TaskDic($dic);
        };

        $dic[TaskFactory::class] = function (Container $dic) {
            return (new TaskFactory($dic[AssessmentDic::class]));
        };
    }

    public function constraints(): DataConstraints
    {
        return $this->dic[DataConstraints::class];
    }

    public function plugin(): ilLongEssayAssessmentPlugin
    {
        return $this->dic[ilLongEssayAssessmentPlugin::class];
    }

    public function uiFactory(): Factory
    {
        return $this->dic[Factory::class];
    }

    public function uiService(): UIService
    {
        return $this->dic[UIService::class];
    }

    public function uploadTempFile(): ilLongEssayAssessmentUploadTempFile
    {
        return $this->dic[ilLongEssayAssessmentUploadTempFile::class];
    }

    public function system(): SystemApi
    {
        return ($this->dic[SystemFactory::class])->forClients();
    }

    public function assessment(int $ass_id, int $context_id): AssessmentApi
    {
        return ($this->dic[AssessmentFactory::class])->forClients($ass_id, $context_id);
    }

    public function rest(int $ass_id, int $context_id): RestApi
    {
        return ($this->dic[AssessmentFactory::class])->forClients($ass_id, $context_id);
    }

    public function task(int $task_id, int $context_id): TaskApi
    {
        return ($this->dic[TaskFactory::class])->forClients($task_id);
    }

    public function essayTask(int $task_id, int $context_id): EssayTaskApi
    {
        return ($this->dic[EssayTaskFactory::class])->forClients($task_id);
    }
}
