<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use Edutiek\AssessmentService\Assessment\Api\Factory as AssessmentFactory;
use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use Edutiek\AssessmentService\Assessment\Api\ForRest as RestApi;
use Edutiek\AssessmentService\EssayTask\Api\Factory as EssayTaskFactory;
use Edutiek\AssessmentService\EssayTask\Api\ForClients as EssayTaskApi;
use Edutiek\AssessmentService\System\Api\Factory as SystemFactory;
use Edutiek\AssessmentService\System\Api\ForClients as SystemClientApi;
use Edutiek\AssessmentService\System\Api\ForServices as SystemServicesApi;
use Edutiek\AssessmentService\System\Api\ForEvents as EventApi;
use Edutiek\AssessmentService\Task\Api\Factory as TaskFactory;
use Edutiek\AssessmentService\Task\Api\ForClients as TaskClientApi;
use Edutiek\AssessmentService\Task\Api\ForTypes as TaskTypesApi;
use ILIAS\DI\Container;
use ILIAS\Data\UUID\Factory as UUIDFactory;
use ILIAS\Plugin\LongEssayAssessment\Common\Constraints\DataConstraints;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Common\Session\SessionValues;
use ILIAS\Plugin\LongEssayAssessment\Common\Upload\UploadTempFile;
use ILIAS\Plugin\LongEssayAssessment\Setup\ModelObjective;
use ILIAS\Plugin\LongEssayAssessment\UI\Factory;
use ILIAS\Plugin\LongEssayAssessment\UI\IconFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Input\InputFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Item\ItemFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\PluginTemplateFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Statistic\StatisticFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\UIService;
use ILIAS\Plugin\LongEssayAssessment\UI\Viewer\ViewerFactory;
use ilLongEssayAssessmentPlugin;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory as TableFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Tree\TreeFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Protocol\Factory as ProtocolFactory;
use ILIAS\Plugin\LongEssayAssessment\System\Context\Service as ContextService;
use Edutiek\AssessmentService\Assessment\EventHandling\Observer as AssessmentObserver;
use Edutiek\AssessmentService\EssayTask\EventHandling\Observer as EssayTaskObserver;
use Edutiek\AssessmentService\Task\EventHandling\Observer as TaskObserver;

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

        $dic[IconFactory::class] = function () use ($dic) {
            return new IconFactory($dic->ui()->factory()->symbol()->icon());
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
                $dic[IconFactory::class],
                new ItemFactory(
                    $dic->ui()->factory()->symbol()->icon(),
                    $this->plugin(),
                    $dic["ui.signal_generator"]
                ),
                new StatisticFactory(),
                new ViewerFactory(),
                new TableFactory(
                    $this,
                    $dic->ui()->factory(),
                    $dic->uiService(),
                    $dic->ui()->renderer(),
                    $dic->refinery(),
                    $dic->http()->wrapper()->query(),
                    $dic->http()->request(),
                    $dic->fileDelivery(),
                    $dic->language()
                ),
                new TreeFactory(
                    $dic["ui.factory.tree"],
                    $dic["ui.factory.symbol.icon"],
                    $dic->repositoryTree(),
                    $dic->access(),
                    $dic->language(),
                    $dic->http(),
                    $dic->refinery(),
                    $dic->ui()->factory(),
                    $dic->ui()->renderer()
                ),
                new ProtocolFactory(
                    $dic[IconFactory::class],
                    $dic->ui()->factory(),
                    $dic[ilLongEssayAssessmentPlugin::class],
                    $dic->http(),
                    $dic->refinery()
                )
            );
        };

        $dic[UploadTempFile::class] = function (Container $dic) {
            return new UploadTempFile(
                $dic->filesystem(),
                $dic->upload(),
                $this->sessionValues(UploadTempFile::class),
                new UUIDFactory()
            );
        };

        $dic[UIService::class] = function (Container $dic) {
            return new UIService($dic[ilLongEssayAssessmentPlugin::class], $dic["lng"], $dic["refinery"]);
        };

        $dic[Generate::class] = function (Container $dic) {
            return new Generate(ModelObjective::PATH());
        };

        // Dependencies of the assessment service components

        $dic[SystemDic::class] = function (Container $dic) {
            return new SystemDic($dic);
        };
        $dic[AssessmentDic::class] = function (Container $dic) {
            return new AssessmentDic($dic);
        };
        $dic[EssayTaskDic::class] = function (Container $dic) {
            return new EssayTaskDic($dic);
        };
        $dic[TaskDic::class] = function (Container $dic) {
            return new TaskDic($dic);
        };

        // Factories of the assessment service components

        $dic[SystemFactory::class] = function (Container $dic) {
            return new SystemFactory($dic[SystemDic::class]);
        };
        $dic[AssessmentFactory::class] = function (Container $dic) {
            return new AssessmentFactory($dic[AssessmentDic::class]);
        };
        $dic[EssayTaskFactory::class] = function (Container $dic) {
            return new EssayTaskFactory($dic[EssayTaskDic::class]);
        };
        $dic[TaskFactory::class] = function (Container $dic) {
            return new TaskFactory($dic[TaskDic::class]);
        };

        // Sub factories for assessment service components

        $dic[SystemServicesApi::class] = function (Container $dic) {
            return $dic[SystemFactory::class]->forServices();
        };

        $dic[TaskTypesApi::class] = function (Container $dic) {
            return $dic[TaskFactory::class]->forTypes();
        };

        // Event Management

        $dic[EventApi::class] = function (Container $dic) {
            return $dic[SystemFactory::class]->forEvents([
                $dic[AssessmentFactory::class]->forEvents(),
                $dic[EssayTaskFactory::class]->forEvents(),
                $dic[TaskFactory::class]->forEvents(),
            ]);
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

    public function sessionValues(string $class, ?int $ass_id = null, ?int $task_id = null): SessionValues
    {
        return new SessionValues($class, $ass_id, $task_id);
    }

    public function uploadTempFile(): UploadTempFile
    {
        return $this->dic[UploadTempFile::class];
    }

    public function system(): SystemClientApi
    {
        return ($this->dic[SystemFactory::class])->forClients();
    }

    public function assessment(int $ass_id, int $user_id): AssessmentApi
    {
        return ($this->dic[AssessmentFactory::class])->forClients($ass_id, $user_id);
    }

    public function rest(): RestApi
    {
        return ($this->dic[AssessmentFactory::class])->forRest();
    }

    public function task(int $ass_id, int $user_id): TaskClientApi
    {
        return ($this->dic[TaskFactory::class])->forClients($ass_id, $user_id);
    }

    public function essayTask(int $ass_id, int $user_id): EssayTaskApi
    {
        return ($this->dic[EssayTaskFactory::class])->forClients($ass_id, $user_id);
    }

    public function context(int $ref_id): ContextService
    {
        return $this->dic[ContextService::class . "_$ref_id"] ??= new ContextService($ref_id, $this->dic->repositoryTree());
    }
}
