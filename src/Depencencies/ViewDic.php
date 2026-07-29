<?php

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\Plugin\LongEssayAssessment\Dependencies\PluginDic;
use ILIAS\Plugin\LongEssayAssessment\View\Data\WriterViewRepo;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\RepositoryFactory as AssessmentRepositoryFactory;
use ILIAS\Plugin\LongEssayAssessment\EssayTask\Data\RepositoryFactory as EssayTaskRepositoryFactory;
use ILIAS\Plugin\LongEssayAssessment\Dependencies\SystemDic;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryFactory as FactoryTrait;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\Writer;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\Location;
use ILIAS\Plugin\LongEssayAssessment\EssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\View\Data\CorrectionsViewRepo;
use ILIAS\Plugin\LongEssayAssessment\Task\Data\Settings as Task;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\Properties;
use ILIAS\Plugin\LongEssayAssessment\Task\Data\CorrectorAssignment;
use ILIAS\Plugin\LongEssayAssessment\Task\Data\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\Corrector;
use Edutiek\AssessmentService\Views\Api\ForClients;
use ILIAS\Plugin\LongEssayAssessment\View\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\GradeLevel;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\CorrectionSettings;

class ViewDic implements ForClients
{
    use FactoryTrait;
    public function __construct(
        private readonly \ILIAS\DI\Container $dic
    ) {
        $this->db = $this->dic->database();
        $this->g = $dic[Generate::class];
    }

    public function writer(): WriterViewRepo
    {
        return new WriterViewRepo(
            $this->dic->database(),
            $this->add(Writer::class),
            $this->add(Location::class),
            $this->add(Essay::class),
            $this->dic[SystemDic::class]->userDataRepo(),
            $this->dic[SystemDic::class]->userDisplayRepo(),
            new \DateTimeZone($this->dic->user()->getTimeZone())
        );
    }
    public function corrections(): CorrectionsViewRepo
    {
        return new CorrectionsViewRepo(
            $this->dic->database(),
            $this->add(Writer::class),
            $this->add(Task::class),
            $this->dic[AssessmentDic::class]->repositories()->properties(),
            $this->add(Location::class),
            $this->add(Essay::class),
            $this->add(CorrectorAssignment::class),
            $this->add(CorrectorSummary::class),
            $this->add(Corrector::class),
            $this->dic[SystemDic::class]->userDataRepo(),
            $this->dic[SystemDic::class]->userDisplayRepo(),
            new \DateTimeZone($this->dic->user()->getTimeZone())
        );
    }

    public function statistic()
    {
        return new StatisticViewRepo(
            $this->dic->database(),
            $this->add(Writer::class),
            $this->add(Corrector::class),
            $this->add(GradeLevel::class),
            $this->add(CorrectorSummary::class),
            $this->add(CorrectorAssignment::class),
            $this->add(CorrectionSettings::class),
            $this->dic[AssessmentDic::class]->repositories()->properties(),
            $this->dic[SystemDic::class]->userDataRepo(),
        );
    }

}
