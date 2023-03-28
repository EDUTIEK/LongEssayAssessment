<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use Edutiek\LongEssayAssessmentService\Corrector\Service;
use Edutiek\LongEssayAssessmentService\Data\DocuItem;
use ILIAS\Plugin\LongEssayAssessment\BaseService;
use ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorContext;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorAssignment;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Data\DataService;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\GradeLevel;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\LogEntry;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository;
use ILIAS\Data\UUID\Factory as UUID;
use ilObjUser;

/**
 * Service for maintaining correctors (business logic)
 * @package ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin
 */
class CorrectorAdminService extends BaseService
{
    /** @var CorrectionSettings */
    protected $settings;

    /** @var \ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository */
    protected $writerRepo;

    /** @var \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository */
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
     * Get or create a writer object for an ILIAS user
     * @param int $user_id
     * @return Corrector
     */
    public function getOrCreateCorrectorFromUserId(int $user_id) : Corrector
    {
        $corrector = $this->correctorRepo->getCorrectorByUserId($user_id, $this->settings->getTaskId());
        if (!isset($corrector)) {
            $corrector = Corrector::model();
            $corrector->setUserId($user_id);
            $corrector->setTaskId($this->settings->getTaskId());
            $this->correctorRepo->save($corrector);
        }
        return $corrector;
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
        if ($required < 1) {
            return 0;
        }

        $assigned = 0;
        $writerCorrectors = [];     // writer_id => [ position => $corrector_id ]
        $correctorWriters = [];     // corrector_id => [ writer_id => position ]
        $correctorPosCount = [];    // corrector_id => [ position => count ]

        // collect assignment data
        foreach ($this->correctorRepo->getCorrectorsByTaskId($this->settings->getTaskId()) as $corrector) {
            // init list of correctors with writers
            $correctorWriters[$corrector->getId()] = [];
            for ($position = 0; $position < $required; $position++) {
                $correctorPosCount[$corrector->getId()][$position] = 0;
            }
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
                // count the assignments per position for a corrector
                $correctorPosCount[$assignment->getCorrectorId()][$assignment->getPosition()]++;
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
                            // group the candidates by their number of existing assignments for the position
                            $candidatesByCount[$correctorPosCount[$correctorId][$position]][] = $correctorId;
                        }
                    }
                    if (!empty($candidatesByCount)) {

                        // get the candidate group with the smallest number of assignments for the position
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
                        $this->correctorRepo->save($assignment);
                        $assigned++;

                        // remember the assignment for the next candidate collection
                        $correctorWriters[$correctorId][$writerId] = $position;
                        // not really needed, this fills the current empty corrector position
                        $writerCorrectors[$writerId][$position] = $correctorId;
                        // increase the assignments per position for the corrector
                        $correctorPosCount[$correctorId][$position]++;
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
     * @param \ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary[] $summaries
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

        if ($this->settings->getStitchWhenDistance()) {
            if (abs($maxPoints - $minPoints) > $this->settings->getMaxAutoDistance()) {
                // distance is within limit
                return true;
            }
        }

        if ($this->settings->getStitchWhenDecimals()) {
            $average = $this->getAveragePointsOfSummaries($summaries);
            if ($average === null) {
                // one corrector hasn't stored points (should not happen)
                return true;
            }
            if (floor($average) < $average) {
                return true;
            }
        }

        return false;
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
     * @param \ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay|null $essay
     * @return \ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary[]
     */
    public function getAuthorizedSummaries(?Essay $essay) : array
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
            $average = $this->getAveragePointsOfSummaries($summaries);
            if ($average !== null) {
                $essay->setFinalPoints($average);
                if (!empty($level = $this->getGradeLevelForPoints($average))) {
                    $essay->setFinalGradeLevelId($level->getId());
                    $essay->setCorrectionFinalized($this->dataService->unixTimeToDb(time()));
                    $essay->setCorrectionFinalizedBy($user_id);

                    $essayRepo = $this->localDI->getEssayRepo();
                    $essayRepo->updateEssay($essay);

                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Create an export file for the corrections
     * @param \ilObjLongEssayAssessment $object
     * @return string   file path of the export
     */
    public function createCorrectionsExport(\ilObjLongEssayAssessment $object) : string
    {
        $storage = $this->dic->filesystem()->temp();
        $basedir = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
        $tempdir = 'xlas/'. (new UUID)->uuid4AsString();
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
    public function getCorrectionAsPdf(\ilObjLongEssayAssessment $object, TaskSettings $repoTask, Writer $repoWriter) : string
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
        $file = 'xlas/'. (new UUID)->uuid4AsString() . '.csv';
        $storage->write($file, $csv->getCSVString());

        return $basedir . '/' . $file;
    }

	/**
	 * Sorts Assoc Array bei Position and pseudonym
	 * Prio 1 sort by Position in $items[]["position"]
	 * Prio 2 sort by Pseudonym Name in $items[]["pseudonym"]
	 *
	 * @param array $items
	 * @return void
	 */
	public function sortCorrectionsArray(array &$items){
		usort($items, function(array $item_a, array $item_b){
			if($item_a["position"] == $item_b["position"]){
				return strtolower($item_a["pseudonym"]) <=> strtolower($item_b["pseudonym"]);
			}
			return $item_a["position"] <=> $item_b["position"];
		});
	}

	/**
	 * @param int $user_id
	 * @param array $array
	 * @return array
	 */
	public function filterCorrections(int $user_id, array $array): array
	{
		$position_filter = $this->dataService->getCorrectorPositionFilter($user_id);
		$status_filter = $this->dataService->getCorrectionStatusFilter($user_id);

		return array_filter($array, function (array $item) use($position_filter, $status_filter){
			$status_ok = $status_filter == DataService::ALL || $status_filter == $item["correction_status"];
			$position_ok = $position_filter == DataService::ALL || $position_filter == $item["position"] + 1;
			return $status_ok && $position_ok;
		});
	}

    public function removeAuthorizations(Writer $writer) : bool
    {
        global $DIC;

        if (empty($essay = $this->essayRepo->getEssayByWriterIdAndTaskId($writer->getId(), $writer->getTaskId()))) {
            return false;
        }

        // remove finalized status
        if (!empty($essay->getCorrectionFinalized())) {
            $essay->setCorrectionFinalized(null);
            $essay->setCorrectionFinalizedBy(null);
            $this->essayRepo->updateEssay($essay);
        }

        // remove authorizations
        foreach ($this->getAuthorizedSummaries($essay) as $summary) {
            $summary->setCorrectionAuthorized(null);
            $summary->setCorrectionAuthorizedBy(null);
            $this->essayRepo->updateCorrectorSummary($summary);
        }

        // log the actions
        $description = \ilLanguage::_lookupEntry(
            $this->lng->getDefaultLanguage(),
            $this->plugin->getPrefix(),
            $this->plugin->getPrefix() . "_remove_authorization_log"
        );

        $datetime = new \ilDateTime(time(), IL_CAL_UNIX);
        $names = \ilUserUtil::getNamePresentation([$writer->getUserId(), $DIC->user()->getId()], false, false, "", true);

        $log_entry = new LogEntry();
        $log_entry->setEntry(sprintf($description, $names[$writer->getUserId()] ?? "unknown", $names[$DIC->user()->getId()] ?? "unknown"))
            ->setTaskId($essay->getTaskId())
            ->setTimestamp($datetime->get(IL_CAL_DATETIME))
            ->setCategory(LogEntry::CATEGORY_AUTHORIZE);

        $this->taskRepo->createLogEntry($log_entry);

        return true;
    }

    public function removeSingleAuthorization(Writer $writer, Corrector $corrector) : bool
    {
        if (empty($essay = $this->essayRepo->getEssayByWriterIdAndTaskId($writer->getId(), $writer->getTaskId()))) {
            return false;
        }
        if (empty($summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId($essay->getId(), $corrector->getId()))) {
            return false;
        }

        // don't remove a singe authorization from a finalized correction
        if (!empty($essay->getCorrectionFinalized())) {
            return false;
        }

        $summary->setCorrectionAuthorized(null);
        $summary->setCorrectionAuthorizedBy(null);
        $this->essayRepo->updateCorrectorSummary($summary);

        // log the actions
        $description = \ilLanguage::_lookupEntry(
            $this->lng->getDefaultLanguage(),
            $this->plugin->getPrefix(),
            $this->plugin->getPrefix() . "_remove_own_authorization_log"
        );

        $datetime = new \ilDateTime(time(), IL_CAL_UNIX);
        $names = \ilUserUtil::getNamePresentation([$writer->getUserId(), $corrector->getUserId()], false, false, "", true);

        $log_entry = new LogEntry();
        $log_entry->setEntry(sprintf($description, $names[$writer->getUserId()] ?? "unknown", $names[$corrector->getUserId()] ?? "unknown"))
            ->setTaskId($essay->getTaskId())
            ->setTimestamp($datetime->get(IL_CAL_DATETIME))
            ->setCategory(LogEntry::CATEGORY_AUTHORIZE);

        $this->taskRepo->createLogEntry($log_entry);

        return true;
    }

}
