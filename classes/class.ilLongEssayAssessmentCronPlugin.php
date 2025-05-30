<?php
/* Copyright (c) 2024 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Setup\ImplementationOfInterfaceFinder;

class ilLongEssayAssessmentCronPlugin extends ilCronHookPlugin
{
    private ImplementationOfInterfaceFinder $interface_finder;
    /**
     * @var ilCronJob[]
     */
    private array $objects = [];

    private ?array $classes = null;

    private \ILIAS\DI\Container $dic;

    public function __construct(ilDBInterface $db, ilComponentRepositoryWrite $component_repository, string $id)
    {
        global $DIC;
        parent::__construct($db, $component_repository, $id);
        $this->interface_finder = new ImplementationOfInterfaceFinder();
        $this->dic = $DIC;
    }

    public function getPluginName():string
    {
        return "LongEssayAssessmentCron";
    }

    private function getJobClasses()
    {
        if($this->classes !== null) {
            return $this->classes;
        }

        $this->classes = [];

        if(interface_exists("ILIAS\Plugin\LongEssayAssessment\Cron\CronJobInterface", true)) {
            $job_classes = $this->interface_finder->getMatchingClassNames(
                ILIAS\Plugin\LongEssayAssessment\Cron\CronJobInterface::class,
                [],
                "[/]Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment/classes/.*"
            );

            foreach ($job_classes as $class_name) {
                if(is_subclass_of($class_name, ilCronJob::class)) {
                    $this->classes[$class_name::id()] = $class_name;
                }
            }
        }

        return $this->classes;
    }

    private function getJobObject(string $class_name): ilCronJob
    {
        if(isset($this->objects[$class_name])) {
            return $this->objects[$class_name];
        }

        $xlas_plugin = ilLongEssayAssessmentPlugin::getInstance();
        $xlas_dic = \ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI::getInstance();

        return $this->objects[$class_name] = new $class_name($xlas_plugin, $xlas_dic, $this->dic);
    }


    public function getCronJobInstances(): array
    {
        $jobs = [];

        foreach ($this->getJobClasses() as $id => $class_name) {
            $jobs[] =  $this->getJobObject($class_name);
        }

        return $jobs;
    }

    public function getCronJobInstance($jobId): ilCronJob
    {
        $jobs = $this->getJobClasses();
        if(!isset($jobs[$jobId])) {
            throw new ilCronException(
                "Job [$jobId] not found."
            );
        }
        return $this->getJobObject($jobs[$jobId]);
    }
}
