<?php

namespace ILIAS\Plugin\LongEssayAssessment\Cron;

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ilCronJobResult;

abstract class CronJob extends \ilCronJob implements CronJobInterface
{
    protected \ILIAS\DI\Container $global_dic;
    protected \ilLongEssayAssessmentPlugin $plugin;
    protected LongEssayAssessmentDI $local_dic;

    public function __construct(\ilLongEssayAssessmentPlugin $plugin, LongEssayAssessmentDI $local_dic, \ILIAS\DI\Container $global_dic)
    {
        $this->plugin = $plugin;
        $this->local_dic = $local_dic;
        $this->global_dic = $global_dic;
    }

    public function run(): ilCronJobResult
    {
        if(!$this->plugin->isActive()) {
            $result =  new ilCronJobResult();
            $result->setStatus(ilCronJobResult::STATUS_INVALID_CONFIGURATION);
            $result->setCode(ilCronJobResult::CODE_SUPPOSED_CRASH);
            $result->setMessage($this->plugin->txt("xlas_not_active"));
            return $result;
        }

        return $this->runJob();
    }

    abstract public function runJob(): ilCronJobResult;
}
