<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\CorrectorAdmin;

use Edutiek\LongEssayService\Corrector\Service;
use Edutiek\LongEssayService\Data\DocuItem;
use Edutiek\LongEssayService\Data\WritingTask;
use ILIAS\Plugin\LongEssayTask\BaseService;
use ILIAS\Plugin\LongEssayTask\Corrector\CorrectorContext;
use ILIAS\Plugin\LongEssayTask\Data\CorrectionSettings;
use ILIAS\Plugin\LongEssayTask\Data\Corrector;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorAssignment;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorRepository;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorSummary;
use ILIAS\Plugin\LongEssayTask\Data\DataService;
use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\Data\EssayRepository;
use ILIAS\Plugin\LongEssayTask\Data\GradeLevel;
use ILIAS\Plugin\LongEssayTask\Data\TaskRepository;
use ILIAS\Plugin\LongEssayTask\Data\TaskSettings;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\Data\WriterRepository;
use ILIAS\Data\UUID\Factory as UUID;
use ilObjUser;

/**
 * Service for maintaining correctors (business logic)
 * @package ILIAS\Plugin\LongEssayTask\CorrectorAdmin
 */
class CorrectorAdminService extends BaseService
{
    /** @var CorrectionSettings */
    protected $settings;

    /** @var WriterRepository */
    protected $writerRepo;

    /** @var CorrectorRepository */
    protected $correctorRepo;

    /** @var EssayRepository */
    protected $essayRepo;

    /** @var TaskRepository */
    protected $taskRepo;

    /** @var DataService */
    protected $dataService;

    /**
     * @inheritDoc
     */
    public function __construct(int $task_id)
    {
        parent::__construct($task_id);

        $this->settings = $this->localDI->getTaskRepo()->getCorrectionSettingsById($this->task_id) ??
            new CorrectionSettings($this->task_id);

        $this->writerRepo = $this->localDI->getWriterRepo();
        $this->correctorRepo = $this->localDI->getCorrectorRepo();
        $this->essayRepo = $this->localDI->getEssayRepo();
        $this->taskRepo = $this->localDI->getTaskRepo();
        $this->dataService = $this->localDI->getDataService($this->task_id);
    }

    /**
     * Get the Correction settings
     * @return CorrectionSettings
     */
    public function getSettings() : CorrectionSettings
    {
        return $this->settings;
    }

    /**
     * Add an ilias user as corrector to the task
     * @param $user_id
     */
    public function addUserAsCorrector($user_id)
    {
        $corrector = $this->correctorRepo->getCorrectorByUserId($user_id, $this->settings->getTaskId());
        if (!isset($corrector)) {
            $corrector = new Corrector();
            $corrector->setUserId($user_id);
            $corrector->setTaskId($this->settings->getTaskId());
            $this->correctorRepo->createCorrector($corrector);
        }
    }

    /**
     * Get the number of available correctors
     * @return int
     */
    public function countAvailableCorrectors() : int
    {
        return count($this->correctorRepo->getCorrectorsByTaskId($this->settings->getTaskId()));
    }

    /**
     * Get the number of missing correctors
     * @return int
     */
    public function countMissingCorrectors() : int
    {
        $required = $this->settings->getRequiredCorrectors();
        $missing = 0;
        foreach ($this->writerRepo->getWritersByTaskId($this->settings->getTaskId()) as $writer) {
            // get only writers with authorized essays without exclusion
            $essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($writer->getId(), $this->settings->getTaskId());
            if (!isset($essay) || (empty($essay->getWritingAuthorized())) || !empty($essay->getWritingExcluded())) {
                continue;
            }
            $assigned = count($this->correctorRepo->getAssignmentsByWriterId($writer->getId()));
            $missing += max(0, $required - $assigned);
        }
        return $missing;
    }

    /**
     * Assign correctors to empty corrector positions for the candidates
     * @return int number of new assignments
     */
    public function assignMissingCorrectors() : int
    {
        switch ($this->settings->getAssignMode()) {
            case CorrectionSettings::ASSIGN_MODE_RANDOM_EQUAL:
            default:
                return $this->assignByRandomEqualMode();
        }
    }

    /**
     * Assign correctors randomly so that they get nearly equal number of corrections
     * @return int number of new assignments
     */
    protected function assignByRandomEqualMode() : int
    {
        $required = $this->settings->getRequiredCorrectors();
        if ($required <= 1) {
            return 0;
        }

        $assigned = 0;
        $writerCorrectors = [];
        $correctorWriters = [];

        // collect assignment data
        foreach ($this->correctorRepo->getCorrectorsByTaskId($this->settings->getTaskId()) as $corrector) {
            // init list of correctors with writers
            $correctorWriters[$corrector->getId()] = [];
        }
        foreach ($this->writerRepo->getWritersByTaskId($this->settings->getTaskId()) as $writer) {

            // get only writers with authorized essays
            $essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($writer->getId(), $this->settings->getTaskId());
            if (!isset($essay) || empty($essay->getWritingAuthorized()) || !empty($essay->getWritingExcluded())) {
                continue;
            }

            // init list writers with correctors
            $writerCorrectors[$writer->getId()] = [];

            foreach($this->correctorRepo->getAssignmentsByWriterId($writer->getId()) as $assignment) {
                // list the assigned corrector positions for each writer, give the corrector for each position
                $writerCorrectors[$assignment->getWriterId()][$assignment->getPosition()] = $assignment->getCorrectorId();
                // list the assigned writers for each corrector, give the corrector position per writer
                $correctorWriters[$assignment->getCorrectorId()][$assignment->getWriterId()] = $assignment->getPosition();
            }
        }

        // assign empty corrector positions
        foreach ($writerCorrectors as $writerId => $correctorsByPos) {
            for ($position = 0; $position < $required; $position++) {
                // empty corrector position
                if (!isset($correctorsByPos[$position])) {

                    // collect the candidate corrector ids for the position
                    $candidatesByCount = [];
                    foreach ($correctorWriters as $correctorId => $posByWriterId) {

                        // corrector has not yet the writer assigned
                        if (!isset($posByWriterId[$writerId])) {
                            // group the candidates by their number of existing assignments
                            $candidatesByCount[count($posByWriterId)][] = $correctorId;
                        }
                    }
                    if (!empty($candidatesByCount)) {

                        // get the candidate group with the smallest number of assignments
                        ksort($candidatesByCount);
                        reset($candidatesByCount);
                        $candidateIds = current($candidatesByCount);
                        $candidateIds = array_unique($candidateIds);

                        // get a random candidate id
                        shuffle($candidateIds);
                        $correctorId = current($candidateIds);

                        // assign the corrector to the writer
                        $assignment = new CorrectorAssignment();
                        $assignment->setCorrectorId($correctorId);
                        $assignment->setWriterId($writerId);
                        $assignment->setPosition($position);
                        $this->correctorRepo->createCorrectorAssignment($assignment);
                        $assigned++;

                        // remember the assignment for the next candidate collection
                        $correctorWriters[$correctorId][$writerId] = $position;
                        // not really needed, this fills the current empty corrector position
                        $writerCorrectors[$writerId][$position] = $correctorId;
                    }
                }
            }
        }
        return $assigned;
    }

    /**
     * Check if the correction of an essay is possible
     */
    public function isCorrectionPossible(?Essay $essay, ?CorrectorSummary $summary) : bool
    {
        if (empty($essay) || empty($essay->getWritingAuthorized() || !empty($essay->getWritingExcluded()))) {
            return false;
        }
        if (!empty($summary) && !empty($summary->getCorrectionAuthorized())) {
            return false;
        }
        return true;
    }

    /**
     * Check if the correction for an essay needs a stitch decision
     */
    public function haveAllCorrectorsAuthorized(?Essay $essay) : bool
    {
        return count($this->getAuthorizedSummaries($essay)) >= $this->settings->getRequiredCorrectors();
    }


    /**
     * Check if the correction for an essay needs a stitch decision
     */
    public function isStitchDecisionNeeded(?Essay $essay) : bool
    {
        return empty($essay->getCorrectionFinalized()) && $this->isStitchDecisionNeededForSummaries($this->getAuthorizedSummaries($essay));
    }

    /**
     * Check if the correction for an essay needs a stitch decision
     * @param CorrectorSummary[] $summaries
     */
    protected function isStitchDecisionNeededForSummaries(array $summaries) : bool
    {
        if (count($summaries) < $this->settings->getRequiredCorrectors()) {
            // not enough correctors authorized => not yet ready
            return false;
        }
        $minPoints = null;
        $maxPoints = null;
        foreach ($summaries as $summary) {
            $minPoints = (isset($minPoints) ? min($minPoints, $summary->getPoints()) : $summary->getPoints());
            $maxPoints = (isset($maxPoints) ? max($maxPoints, $summary->getPoints()) : $summary->getPoints());
        }

        if (abs($maxPoints - $minPoints) <= $this->settings->getMaxAutoDistance()) {
            // distance is within limit
            return false;
        }
        return true;
    }

    /**
     * Get the average Points of the correction summaries
     * @param CorrectorSummary[] $summaries
     */
    public function getAveragePointsOfSummaries(array $summaries) : ?float
    {
        $countOfPoints = 0;
        $sumOfPoints = null;
        foreach ($summaries as $summary) {
            if ($summary->getPoints() !== null) {
                $countOfPoints++;
                $sumOfPoints += $summary->getPoints();
            }
        }
        if ($countOfPoints > 0) {
            return $sumOfPoints / $countOfPoints;
        }
        return null;
    }

    /**
     * Get all correction summaries saved for an essay
     * @param Essay|null $essay
     * @return CorrectorSummary[]
     */
    protected function getAuthorizedSummaries(?Essay $essay) : array
    {
        if (empty($essay) || empty($essay->getWritingAuthorized())) {
            // essay is not authorized
            return [];
        }

        $summaries = [];
        foreach ($this->correctorRepo->getAssignmentsByWriterId($essay->getWriterId()) as $assignment) {
            $summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId(
                $essay->getId(), $assignment->getCorrectorId());
            if (!empty($summary) && !empty($summary->getCorrectionAuthorized())) {
                $summaries[] = $summary;
            }
        }
        return $summaries;
    }


    /**
     * Get the resulting grade level for certain points
     * @param float $points
     * @return GradeLevel|null
     */
    protected function getGradeLevelForPoints(float $points) : ?GradeLevel
    {
        $objectRepo = $this->localDI->getObjectRepo();

        $level = null;
        $last_points = 0;
        foreach ($objectRepo->getGradeLevelsByObjectId($this->task_id) as $levelCandidate) {
            if ($levelCandidate->getMinPoints() <= $points
                && $levelCandidate->getMinPoints() >= $last_points
            ) {
                $level = $levelCandidate;
                $last_points = $level->getMinPoints();
            }
        }
        return $level;
    }

    /**
     * Try the finalisation of a correction
     */
    public function tryFinalisation(Essay $essay, int $user_id) : bool
    {
        $summaries = $this->getAuthorizedSummaries($essay);

        if (count($summaries) < $this->getSettings()->getRequiredCorrectors()) {
            return false;
        }

        if (!$this->isStitchDecisionNeededForSummaries($summaries)) {
            echo "average possible\n";
            $average= $this->getAveragePointsOfSummaries($summaries);
            if ($average !== null) {
                echo "average: $average \n";
                $essay->setFinalPoints($average);
                if (!empty($level = $this->getGradeLevelForPoints($average))) {
                    echo "level: " . $level->getGrade() . "\n";
                    $essay->setFinalGradeLevelId($level->getId());
                    $essay->setCorrectionFinalized($this->dataService->unixTimeToDb(time()));
                    $essay->setCorrectionFinalizedBy($user_id);

                    $essayRepo = $this->localDI->getEssayRepo();
                    $essayRepo->updateEssay($essay);

                    return true;
                }
            }
        }
        echo "finit";
        exit;
        return false;
    }

    /**
     * Create an export file for the corrections
     * @param \ilObjLongEssayTask $object
     * @return string   file path of the export
     */
    public function createCorrectionsExport(\ilObjLongEssayTask $object) : string
    {
        $storage = $this->dic->filesystem()->temp();
        $basedir = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
        $tempdir = 'xlet/'. (new UUID)->uuid4AsString();
        $zipdir = $tempdir . '/' . \ilUtil::getASCIIFilename($object->getTitle());
        $storage->createDir($zipdir);

        $repoTask = $this->taskRepo->getTaskSettingsById($object->getId());
        foreach ($this->essayRepo->getEssaysByTaskId($repoTask->getTaskId()) as $repoEssay) {
            $repoWriter = $this->writerRepo->getWriterById($repoEssay->getWriterId());

            $filename = \ilUtil::getASCIIFilename(
                \ilObjUser::_lookupFullname($repoWriter->getUserId()) . ' (' . \ilObjUser::_lookupLogin($repoWriter->getUserId()) . ')') . '.pdf';

            $storage->write($zipdir . '/'. $filename, $this->getCorrectionAsPdf($object, $repoTask, $repoWriter));
        }

        $zipfile = $basedir . '/' . $tempdir . '/' . \ilUtil::getASCIIFilename($object->getTitle()) . '.zip';
        \ilUtil::zip($basedir . '/' . $zipdir, $zipfile);

        $storage->deleteDir($zipdir);
        return $zipfile;

        // check if that can be used without abolute path
        // then also the tempdir can be deleted
        //$delivery = new \ilFileDelivery()
    }

    /**
     * Get the correction of an essay as PDF string
     */
    public function getCorrectionAsPdf(\ilObjLongEssayTask $object, TaskSettings $repoTask, Writer $repoWriter) : string
    {
        $context = new CorrectorContext();
        $context->init((string) $this->dic->user()->getId(), (string) $object->getRefId());

        $writingTask = $context->getWritingTaskByWriterId($repoWriter->getId());
        $writtenEssay = $context->getEssayOfItem((string) $repoWriter->getId());

        $correctionSummaries = [];
        foreach ($this->correctorRepo->getAssignmentsByWriterId($repoWriter->getId()) as $assignment) {
            $repoCorrector = $this->correctorRepo->getCorrectorById($assignment->getCorrectorId());
            if (!empty($summary = $context->getCorrectionSummary((string) $repoWriter->getId(), (string) $repoCorrector->getId()))) {
                $correctionSummaries[] = $summary;
            }
        }

        $item = new DocuItem(
            $writingTask,
            $writtenEssay,
            $correctionSummaries
        );

        $service = new Service($context);
        return $service->getCorrectionAsPdf($item);
    }



    public function createResultsExport() : string
    {
        $csv = new \ilCSVWriter();
        $csv->setSeparator(';');

        $csv->addColumn($this->lng->txt('login'));
        $csv->addColumn($this->lng->txt('firstname'));
        $csv->addColumn($this->lng->txt('lastname'));
        $csv->addColumn($this->lng->txt('matriculation'));
        $csv->addColumn($this->plugin->txt('essay_status'));
        $csv->addColumn($this->plugin->txt('writing_last_save'));
        $csv->addColumn($this->plugin->txt('correction_status'));
        $csv->addColumn($this->plugin->txt('points'));
        $csv->addColumn($this->plugin->txt('grade_level'));
        $csv->addColumn($this->plugin->txt('grade_level_code'));
        $csv->addColumn($this->plugin->txt('passed'));

        $repoTask = $this->taskRepo->getTaskSettingsById($this->task_id);
        foreach ($this->essayRepo->getEssaysByTaskId($repoTask->getTaskId()) as $repoEssay) {
            $repoWriter = $this->writerRepo->getWriterById($repoEssay->getWriterId());
            $user = new ilObjUser($repoWriter->getUserId());
            $csv->addRow();
            $csv->addColumn($user->getLogin());
            $csv->addColumn($user->getFirstname());
            $csv->addColumn($user->getLastname());
            $csv->addColumn($user->getMatriculation());
            if (!empty($repoEssay->getWritingAuthorized())) {
                $csv->addColumn($this->plugin->txt('writing_status_authorized'));
            }
            elseif (!empty($repoEssay->getEditStarted())) {
                $csv->addColumn($this->plugin->txt('writing_status_not_authorized'));
            }
            else {
                $csv->addColumn($this->plugin->txt('writing_status_not_written'));
            }
            $csv->addColumn($repoEssay->getEditEnded());
            if (empty($repoEssay->getCorrectionFinalized())) {
                $csv->addColumn($this->plugin->txt('correction_status_open'));
                $csv->addColumn(null);
                $csv->addColumn(null);
                $csv->addColumn(null);
                $csv->addColumn(null);
            }
            elseif (empty($repoEssay->getWritingAuthorized())) {
                $csv->addColumn($this->plugin->txt('correction_status_not_possible'));
                $csv->addColumn(null);
                $csv->addColumn(null);
                $csv->addColumn(null);
                $csv->addColumn(null);
            }
            else {
                $csv->addColumn($this->plugin->txt('correction_status_finished'));
                $csv->addColumn($repoEssay->getFinalPoints());
                if (!empty($level = $this->localDI->getObjectRepo()->getGradeLevelById((int) $repoEssay->getFinalGradeLevelId()))) {
                    $csv->addColumn($level->getGrade());
                    $csv->addColumn($level->getCode());
                    $csv->addColumn($level->isPassed());
                }
            }
        }

        $storage = $this->dic->filesystem()->temp();
        $basedir = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
        $file = 'xlet/'. (new UUID)->uuid4AsString() . '.csv';
        $storage->write($file, $csv->getCSVString());

        return $basedir . '/' . $file;
    }


}