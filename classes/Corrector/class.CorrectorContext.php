<?php

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use Edutiek\LongEssayAssessmentService\Corrector\Context;
use Edutiek\LongEssayAssessmentService\Corrector\Service;
use Edutiek\LongEssayAssessmentService\Data\CorrectionGradeLevel;
use Edutiek\LongEssayAssessmentService\Data\CorrectionItem;
use Edutiek\LongEssayAssessmentService\Data\CorrectionSettings;
use Edutiek\LongEssayAssessmentService\Data\CorrectionSummary;
use Edutiek\LongEssayAssessmentService\Data\CorrectionTask;
use Edutiek\LongEssayAssessmentService\Data\Corrector;
use Edutiek\LongEssayAssessmentService\Data\WrittenEssay;
use Edutiek\LongEssayAssessmentService\Exceptions\ContextException;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\ServiceContext;
use Edutiek\LongEssayAssessmentService\Data\CorrectionRatingCriterion;
use Edutiek\LongEssayAssessmentService\Data\CorrectionComment;
use Edutiek\LongEssayAssessmentService\Data\CorrectionPoints;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorComment;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CriterionPoints;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings as PluginCorrectionSettings;
use Edutiek\LongEssayAssessmentService\Data\PageData;
use Edutiek\LongEssayAssessmentService\Data\CorrectionMark;
use Edutiek\LongEssayAssessmentService\Data\CorrectionPreferences;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorPreferences;

class CorrectorContext extends ServiceContext implements Context
{
    /**
     * List the availabilities for which resources should be provided in the app
     * @see Resource
     */
    const RESOURCES_AVAILABILITIES = [
        Resource::RESOURCE_AVAILABILITY_BEFORE,
        Resource::RESOURCE_AVAILABILITY_DURING,
        Resource::RESOURCE_AVAILABILITY_AFTER
    ];

    /**
     * id of the selected writer
     * @var ?int
     */
    protected $selected_writer_id;

    protected bool $is_review = false;
    protected bool $is_stitch_decision = false;


    /**
     * Select id of the writer for being corrected
     * The corrector app will load the correction item of this writer
     * @see Writer::getId()
     */
    public function selectWriterId(int $id) : void
    {
        $this->selected_writer_id = $id;
    }

    /**
     * @inheritDoc
     * here: check if user has the permission to review corrections
     */
    public function setReview(bool $is_review)
    {
        if ($is_review && !$this->object->canMaintainCorrectors()) {
            throw new ContextException('User cannot review corrections', ContextException::PERMISSION_DENIED);
        }
        $this->is_review = $is_review;
    }

    /**
     * @inheritDoc
     */
    public function isReview(): bool
    {
        return $this->is_review;
    }

    /**
     * @inheritDoc
     * here: check if user has the permission to draw stitch decisions
     */
    public function setStitchDecision(bool $is_stitch_decision)
    {
        if ($is_stitch_decision && !$this->object->canMaintainCorrectors()) {
            throw new ContextException('User cannot draw stitch decisions', ContextException::PERMISSION_DENIED);
        }
        $this->is_stitch_decision = $is_stitch_decision;
    }

    /**
     * @inheritDoc
     */
    public function isStitchDecision(): bool
    {
        return $this->is_stitch_decision;
    }


    /**
     * @inheritDoc
     * here: support a separate url from the plugin config (for development purposes)
     */
    public function getFrontendUrl(): string
    {
        $config = $this->plugin->getConfig();

        if (!empty($config->getCorrectorUrl())) {
            return $config->getCorrectorUrl();
        } else {
            return  $this->getPluginHttpPath()
                . "/vendor/edutiek/long-essay-assessment-service"
                . "/" . Service::getFrontendRelativeUrl();
        }
    }

    /**
     * @inheritDoc
     * here: URL of the corrector_service script
     */
    public function getBackendUrl(): string
    {
        return  $this->getPluginHttpPath()
            . "/corrector_service.php"
            . "?client_id=" . CLIENT_ID;
    }

    /**
     * @inheritDoc
     * here: get the link to the repo object
     * The ILIAS session still has to exist, otherwise the user has to log in again
     */
    public function getReturnUrl(): string
    {
        if ($this->isReview() || $this->isStitchDecision()) {
            return \ilLink::_getStaticLink($this->object->getRefId(), 'xlas', true, '_correctoradmin');
        }
        return \ilLink::_getStaticLink($this->object->getRefId(), 'xlas', true, '_corrector');
    }

    /**
     * @inheritDoc
     */
    public function getCorrectionTask(): CorrectionTask
    {
        return new CorrectionTask(
            (string) $this->object->getTitle(),
            (string) $this->task->getInstructions(),
            (string) $this->task->getSolution(),
            $this->data->dbTimeToUnix($this->task->getCorrectionEnd())
        );
    }

    /**
     * @inheritDoc
     */
    public function getCorrectionSettings(): CorrectionSettings
    {
        $repoEditorSettings = $this->localDI->getTaskRepo()->getEditorSettingsById($this->task->getTaskId());
        $repoCorrectionSettings = $this->localDI->getTaskRepo()->getCorrectionSettingsById($this->task->getTaskId());
        return new CorrectionSettings(
            (bool) $repoCorrectionSettings->getMutualVisibility(),
            (bool) $repoCorrectionSettings->getMultiColorHighlight(),
            (int) $repoCorrectionSettings->getMaxPoints(),
            (float) $repoCorrectionSettings->getMaxAutoDistance(),
            (bool) $repoCorrectionSettings->getStitchWhenDistance(),
            (bool) $repoCorrectionSettings->getStitchWhenDecimals(),
            (string) $repoCorrectionSettings->getPositiveRating(),
            (string) $repoCorrectionSettings->getNegativeRating(),
            (string) $repoEditorSettings->getHeadlineScheme()
        );
    }

    /**
     * @inheritDoc
     */
    public function getCorrectionPreferences(?string $corrector_key): CorrectionPreferences
    {
        $repoPrefs = $this->localDI->getCorrectorRepo()->getCorrectorPreferences((int) $corrector_key);
        return new CorrectionPreferences(
            (string) $corrector_key,
            $repoPrefs->getEssayPageZoom(),
            $repoPrefs->getEssayTextZoom(),
            $repoPrefs->getSummaryTextZoom(),
            $repoPrefs->getIncludeComments(),
            $repoPrefs->getIncludeCommentRatings(),
            $repoPrefs->getIncludeCommentPoints(),
            $repoPrefs->getIncludeCriteriaPoints(),
            $repoPrefs->getIncludeWriterNotes()
        );
    }


    /**
     * @inheritDoc
     */
    public function getGradeLevels(): array
    {
        $objectRepo = $this->localDI->getObjectRepo();

        $levels = [];
        foreach ($objectRepo->getGradeLevelsByObjectId($this->object->getId()) as $repoLevel) {
            $levels[] = new CorrectionGradeLevel(
                (string) $repoLevel->getId(),
                $repoLevel->getGrade(),
                $repoLevel->getMinPoints()
            );
        }
        return $levels;
    }

    /**
     * @inheritDoc
     */
    public function getRatingCriteria(?string $corrector_key = null): array
    {
        $objectRepo = $this->localDI->getObjectRepo();
        $taskRepo = $this->localDI->getTaskRepo();
        $correctorRepo = $this->localDI->getCorrectorRepo();
        
        $settings = $taskRepo->getCorrectionSettingsById($this->task->getTaskId());
        if (!isset($settings)) {
            return [];

        }
        
        $criteria = [];
        switch ($settings->getCriteriaMode()) {
            case PluginCorrectionSettings::CRITERIA_MODE_NONE:
                return [];

            case PluginCorrectionSettings::CRITERIA_MODE_CORRECTOR:
                if (!empty($corrector_key)) {
                    $corrector_ids = [(int) $corrector_key];
                } else {
                    $corrector_ids = [];
                    foreach ($this->getCorrectionItems() as $correction_item) {
                        foreach ($correctorRepo->getAssignmentsByWriterId((int) $correction_item->getKey()) as $assignment) {
                            $corrector_ids[$assignment->getCorrectorId()] = $assignment->getCorrectorId();
                        }
                    }
                }
                
                foreach ($corrector_ids as $corrector_id) {
                    foreach ($objectRepo->getRatingCriteriaByObjectId($this->object->getId(), $corrector_id) as $repoCriterion) {
                        $criteria[] = new CorrectionRatingCriterion(
                            (string) $repoCriterion->getId(),
                            (string) $repoCriterion->getCorrectorId(),
                            $repoCriterion->getTitle(),
                            $repoCriterion->getDescription(),
                            $repoCriterion->getPoints()
                        );
                    }
                }
                return $criteria;
                
            case PluginCorrectionSettings::CRITERIA_MODE_FIXED:
                foreach ($objectRepo->getRatingCriteriaByObjectId($this->object->getId()) as $repoCriterion) {
                    $criteria[] = new CorrectionRatingCriterion(
                        (string) $repoCriterion->getId(),
                        (string) $repoCriterion->getCorrectorId(),
                        $repoCriterion->getTitle(),
                        $repoCriterion->getDescription(),
                        $repoCriterion->getPoints()
                    );
                }
                return $criteria;
        }
        
        return [];
    }


    /**
     * @inheritDoc
     * here:    the item keys are strings of the writer ids
     *          the item titles are the pseudonymous writer names
     */
    public function getCorrectionItems(bool $use_filter = false): array
    {
        if ($this->isStitchDecision()) {
            return $this->getCorrectionItemsForStitchDecision();
        } elseif ($this->isReview()) {
            return $this->getCorrectionItemsForReview();
        } else {
            return $this->getCorrectionItemsForCorrector($use_filter);
        }
    }

    /**
     * Get the correction items that can be reviewed
     */
    protected function getCorrectionItemsForReview() : array
    {
        $essayRepo = $this->localDI->getEssayRepo();
        $writerRepo = $this->localDI->getWriterRepo();

        $items = [];
        $essays = $essayRepo->getEssaysByTaskId($this->object->getId());
        foreach ($essays as $essay) {
            if(!empty($essay->getWritingAuthorized())) {
                $repoWriter = $writerRepo->getWriterById($essay->getWriterId());
                $items[] = new CorrectionItem(
                    (string) $essay->getWriterId(),
                    (string) $repoWriter->getPseudonym(),
                    false,
                    false
                );
            }
        }
        return $items;
    }


    /**
     * Get the correction items that need a stitch decision
     */
    protected function getCorrectionItemsForStitchDecision() : array
    {
        $essayRepo = $this->localDI->getEssayRepo();
        $writerRepo = $this->localDI->getWriterRepo();
        $adminService = $this->localDI->getCorrectorAdminService($this->object->getId());

        $items = [];
        $essays = $essayRepo->getEssaysByTaskId($this->object->getId());
        foreach ($essays as $essay) {
            if($adminService->isStitchDecisionNeeded($essay)) {
                $repoWriter = $writerRepo->getWriterById($essay->getWriterId());
                $items[] = new CorrectionItem(
                    (string) $essay->getWriterId(),
                    (string) $repoWriter->getPseudonym(),
                    false,
                    false
                );
            }
        }
        return $items;
    }


    /**
     * Get the correction items for a corrector
     * @param bool $use_filter    apply a user filter
     * @todo: merge with CorrectorStartGUI::getItems() in a CorrectorAdminService function
     */
    protected function getCorrectionItemsForCorrector(bool $use_filter = false): array
    {
        $correctorRepo = $this->localDI->getCorrectorRepo();
        $writerRepo = $this->localDI->getWriterRepo();
        $essayRepo = $this->localDI->getEssayRepo();

        $sort_list = [];
        if (!empty($repoCorrector = $correctorRepo->getCorrectorByUserId($this->user->getId(), $this->task->getTaskId()))) {
            foreach ($correctorRepo->getAssignmentsByCorrectorId($repoCorrector->getId()) as $repoAssignment) {
                if (!empty($repoWriter = $writerRepo->getWriterById($repoAssignment->getWriterId()))) {
                    $repoEssay = $essayRepo->getEssayByWriterIdAndTaskId($repoAssignment->getWriterId(), $this->task->getTaskId());
                    if (empty($repoEssay)) {
                        continue;
                    }

                    // authorization is allowed if correctors with lower positions have already authorized
                    $authorization_allowed = true;
                    foreach ($correctorRepo->getAssignmentsByWriterId($repoWriter->getId()) as $otherAssignment) {
                        if ($otherAssignment->getCorrectorId() == $repoAssignment->getCorrectorId()
                            || $otherAssignment->getPosition() >= $repoAssignment->getPosition()) {
                            continue;
                        }
                        $otherSummary = $essayRepo->getCorrectorSummaryByEssayIdAndCorrectorId($repoEssay->getId(), $otherAssignment->getCorrectorId());
                        if (empty($otherSummary) || empty($otherSummary->getCorrectionAuthorized())) {
                            $authorization_allowed = false;
                        }
                    }

                    $title = $repoWriter->getPseudonym();
                    if (empty($repoEssay->getWritingAuthorized()) || !empty($repoEssay->getWritingExcluded())) {
                        $title .= ' - ' . $this->data->formatWritingStatus($repoEssay, false);
                    }

                    $summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId(
                        $repoEssay->getId(),
                        $repoCorrector->getId()
                    );

                    $sort_list[] = [
                        'item' => new CorrectionItem(
                            (string) $repoWriter->getId(),
                            $title,
                            true,
                            $authorization_allowed
                        ),
                        'position' => $repoAssignment->getPosition(),
                        'pseudonym' => $repoWriter->getPseudonym(),
                        'correction_status' => $this->data->getOwnCorrectionStatus($repoEssay, $summary)
                    ];
                }
            }
        }
        $admin_service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $admin_service->sortCorrectionsArray($sort_list);

        if ($use_filter) {
            $filtered_list = $admin_service->filterCorrections($repoCorrector->getUserId(), $sort_list);
            if(!empty($filtered_list)) { // If no Corrections where found due to filtering use all corrections
                $sort_list = $filtered_list;
            }
        }

        $items = [];
        foreach ($sort_list as $sort_item) {
            $items[] = $sort_item['item'];
        }
        return $items;
    }

    /**
     * @inheritDoc
     * here:    the corrector key is a string of the corrector id
     */
    public function getCurrentCorrectorKey(): ?string
    {
        if ($this->isReview() || $this->isStitchDecision()) {
            return null;
        }

        $correctorRepo = $this->localDI->getCorrectorRepo();
        if (!empty($repoCorrector = $correctorRepo->getCorrectorByUserId($this->user->getId(), $this->task->getTaskId()))) {
            return (string) $repoCorrector->getId();
        }
        return null;
    }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the item title is the pseudonymous writer name
     */
    public function getCurrentItem(): ?CorrectionItem
    {
        $essayRepo = $this->localDI->getEssayRepo();
        $correctorRepo = $this->localDI->getCorrectorRepo();
        $writerRepo = $this->localDI->getWriterRepo();

        if (isset($this->selected_writer_id)) {
            if ($this->isReview() || $this->isStitchDecision()) {
                if (!empty($repoEssay = $essayRepo->getEssayByWriterIdAndTaskId($this->selected_writer_id, $this->task->getTaskId()))) {
                    $repoWriter = $writerRepo->getWriterById($repoEssay->getWriterId());
                    return new CorrectionItem(
                        (string) $repoEssay->getWriterId(),
                        $repoWriter->getPseudonym(),
                        false,
                        false
                    );
                }
            }
            if (!empty($repoCorrector = $correctorRepo->getCorrectorByUserId($this->user->getId(), $this->task->getTaskId())) &&
                !empty($repoWriter = $writerRepo->getWriterById((int) $this->selected_writer_id))) {
                foreach ($correctorRepo->getAssignmentsByWriterId((int) $this->selected_writer_id) as $assignment) {
                    if ($assignment->getCorrectorId() == $repoCorrector->getId()) {
                        return new CorrectionItem(
                            (string) $repoWriter->getId(),
                            $repoWriter->getPseudonym()
                        );
                    }
                }
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     */
    public function getEssayOfItem(string $item_key): ?WrittenEssay
    {
        if (!empty($repoEssay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId(
            (int) $item_key,
            $this->task->getTaskId()
        ))) {
            $user_data_helper = $this->localDI->services()->common()->userDataHelper();
            $writtenEssay = new WrittenEssay(
                $repoEssay->getWrittenText(),
                $repoEssay->getRawTextHash(),
                $repoEssay->getServiceVersion(),
                $this->data->dbTimeToUnix($repoEssay->getEditStarted()),
                $this->data->dbTimeToUnix($repoEssay->getEditEnded()),
                !empty($repoEssay->getWritingAuthorized()),
                $this->data->dbTimeToUnix($repoEssay->getWritingAuthorized()),
                !empty($repoEssay->getWritingAuthorized()) ? $user_data_helper->getFullname($repoEssay->getWritingAuthorizedBy()) : null
            );

            if ($repoEssay->getCorrectionFinalized()) {
                $writtenEssay = $writtenEssay
                    ->withCorrectionFinalized($this->data->dbTimeToUnix($repoEssay->getCorrectionFinalized()))
                    ->withCorrectionFinalizedBy($user_data_helper->getFullname($repoEssay->getCorrectionFinalizedBy()))
                    ->withFinalPoints($repoEssay->getFinalPoints())
                    ->withFinalGrade($this->localDI->getObjectRepo()->ifGradeLevelExistsById((int) $repoEssay->getFinalGradeLevelId()) ?
                        $this->localDI->getObjectRepo()->getGradeLevelById((int) $repoEssay->getFinalGradeLevelId())->getGrade() : '')
                    ->withStitchComment($repoEssay->getStitchComment());
            }

            return $writtenEssay;
        }
        return null;
    }


    /**
     * @inheritDoc
     */
    public function getPagesOfItem(string $item_key): array
    {
        $essay_repo = $this->localDI->getEssayRepo();
        $pages = [];
        if (!empty($repoEssay = $essay_repo->getEssayByWriterIdAndTaskId(
            (int) $item_key,
            $this->task->getTaskId()
        ))
        ) {
            foreach ($essay_repo->getEssayImagesByEssayID($repoEssay->getId()) as $repoImage) {
                $pages[] = new PageData(
                    (string) $repoImage->getId(),
                    $repoImage->getPageNo(),
                    $repoImage->getWidth(),
                    $repoImage->getHeight(),
                    null,
                    null
                );
            }
        }
        return $pages;
    }


    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the corrector keys are strings of the corrector ids
     *          the corrector titles are the usernames of the correctors
     */
    public function getCorrectorsOfItem(string $item_key): array
    {
        $dataService = $this->localDI->getDataService($this->task->getTaskId());
        $correctorRepo = $this->localDI->getCorrectorRepo();
        $currentCorrectorKey = $this->getCurrentCorrectorKey();
        
        $add_others = true;
        if (!$this->object->canMaintainCorrectors() && (
            empty($correctionSettings = $this->localDI->getTaskRepo()->getCorrectionSettingsById($this->task->getTaskId()))
                || $correctionSettings->getMutualVisibility() == 0)
        ) {
            $add_others = false;
        }
      
        $correctors = [];
        foreach ($correctorRepo->getAssignmentsByWriterId((int) $item_key) as $assignment) {
            if (!empty($repoCorrector = $correctorRepo->getCorrectorById($assignment->getCorrectorId()))) {
                
                if ($add_others || (string) $repoCorrector->getId() == $currentCorrectorKey) {
                    $user = $dataService->getCachedUser($repoCorrector->getUserId());
                    $corrector = new Corrector(
                        $item_key,
                        (string) $repoCorrector->getId(),
                        $user->getFullname(50),
                        $dataService->formatUserInitials($user),
                        $assignment->getPosition()
                    );
                    
                    $correctors[] = $corrector;
                }
            }
        }
        return $correctors;
    }

    /**
     * Get if a corrector is assigned to an item
     */
    public function isCorrectorOfItem(string $item_key, string $corrector_key) : bool
    {
        $correctorRepo = $this->localDI->getCorrectorRepo();
        return $correctorRepo->ifCorrectorIsAssigned((int) $item_key, (int) $corrector_key);
    }


    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the corrector key is a string of the corrector id
     */
    public function getCorrectionSummary(string $item_key, string $corrector_key): ?CorrectionSummary
    {
        $repoCorrector = $this->localDI->getCorrectorRepo()->getCorrectorById((int) $corrector_key);
        $essayRepo = $this->localDI->getEssayRepo();
        if (!empty($repoEssay = $essayRepo->getEssayByWriterIdAndTaskId((int) $item_key, $this->task->getTaskId()))) {
            if (!empty($repoSummary = $essayRepo->getCorrectorSummaryByEssayIdAndCorrectorId(
                $repoEssay->getId(),
                $repoCorrector->getId()
            )
            )) {
                $user_data_helper = $this->localDI->services()->common()->userDataHelper();
                return new CorrectionSummary(
                    $item_key,
                    $corrector_key,
                    $repoSummary->getSummaryText(),
                    (float) $repoSummary->getPoints(),
                    $repoSummary->getGradeLevelId() ? (string) $repoSummary->getGradeLevelId() : null,
                    $this->data->dbTimeToUnix($repoSummary->getLastChange()),
                    !empty($repoSummary->getCorrectionAuthorized()),
                    $repoSummary->getIncludeComments(),
                    $repoSummary->getIncludeCommentRatings(),
                    $repoSummary->getIncludeCommentPoints(),
                    $repoSummary->getIncludeCriteriaPoints(),
                    $repoSummary->getIncludeWriterNotes(),
                    $user_data_helper->getFullname($repoCorrector->getUserId()),
                    $this->localDI->getObjectRepo()->ifGradeLevelExistsById((int) $repoSummary->getGradeLevelId()) ?
                        $this->localDI->getObjectRepo()->getGradeLevelById((int) $repoSummary->getGradeLevelId())->getGrade() : ''
                );
            }
        }
        return null;
    }


    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the corrector key is a string of the corrector id
     */
    public function getCorrectionComments(string $item_key, string $corrector_key): array
    {
        $repoCorrector = $this->localDI->getCorrectorRepo()->getCorrectorById((int) $corrector_key);
        $essayRepo = $this->localDI->getEssayRepo();
        $comments = [];
        if (!empty($repoEssay = $essayRepo->getEssayByWriterIdAndTaskId((int) $item_key, $this->task->getTaskId()))) {
            foreach ($essayRepo->getCorrectorCommentsByEssayIdAndCorrectorId(
                $repoEssay->getId(),
                $repoCorrector->getId()
            ) as $repoComment) {
                $comments[] = new CorrectionComment(
                    (string) $repoComment->getId(),
                    $item_key,
                    $corrector_key,
                    $repoComment->getStartPosition(),
                    $repoComment->getEndPosition(),
                    $repoComment->getParentNumber(),
                    $repoComment->getComment(),
                    $repoComment->getRating(),
                    $repoComment->getPoints(),
                    CorrectionMark::multiFromArray((array) json_decode($repoComment->getMarksJson()))
                );
            }
        }
        return $comments;
    }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the corrector key is a string of the corrector id
     */
    public function getCorrectionPoints(string $item_key, string $corrector_key, ?string $comment_key = null): array
    {
        $repoCorrector = $this->localDI->getCorrectorRepo()->getCorrectorById((int) $corrector_key);
        $essayRepo = $this->localDI->getEssayRepo();
        $objectRepo = $this->localDI->getObjectRepo();
        $taskRepo = $this->localDI->getTaskRepo();
        
        $criteria_ids = [];
        $settings = $taskRepo->getCorrectionSettingsById($this->task->getTaskId());
        if (!isset($settings) || $settings->getCriteriaMode() == PluginCorrectionSettings::CRITERIA_MODE_NONE) {
            $criteria = [];
        } elseif ($settings->getCriteriaMode() == PluginCorrectionSettings::CRITERIA_MODE_FIXED) {
            $criteria = $objectRepo->getRatingCriteriaByObjectId($this->object->getId(), null);
        } else {
            $criteria = $objectRepo->getRatingCriteriaByObjectId($this->object->getId(), (int) $corrector_key);
        }
        foreach ($criteria as $criterion) {
            $criteria_ids[] = $criterion->getId();
        }
        
        $points = [];
        if (!empty($repoEssay = $essayRepo->getEssayByWriterIdAndTaskId((int) $item_key, $this->task->getTaskId()))) {
            foreach ($essayRepo->getCriterionPointsByEssayIdAndCorrectorId(
                $repoEssay->getId(),
                $repoCorrector->getId()
            ) as $repoPoints) {
                if (in_array($repoPoints->getCriterionId(), $criteria_ids)
                    && ($comment_key == null || $repoPoints->getCorrCommentId() == (int) $comment_key)
                ) {
                    $points[] = new CorrectionPoints(
                        (string) $repoPoints->getId(),
                        $item_key,
                        $corrector_key,
                        (string) $repoPoints->getCorrCommentId(),
                        (string) $repoPoints->getCriterionId(),
                        $repoPoints->getPoints()
                    );
                }
            }
        }
        return $points;
    }


    /**
     * @inheritDoc
     * here:     the corrector key is a string of the corrector id
     */
    public function saveCorrectionPreferences(CorrectionPreferences $preferences): bool
    {
        $repoPrefs = new CorrectorPreferences((int) $preferences->getCorrectorKey());
        $repoPrefs->setEssayPageZoom($preferences->getEssayPageZoom());
        $repoPrefs->setEssayTextZoom($preferences->getEssayTextZoom());
        $repoPrefs->setSummaryTextZoom($preferences->getSummaryTextZoom());
        $repoPrefs->setIncludeComments($preferences->getIncludeComments());
        $repoPrefs->setIncludeCommentRatings($preferences->getIncludeCommentRatings());
        $repoPrefs->setIncludeCommentPoints($preferences->getIncludeCommentPoints());
        $repoPrefs->setIncludeCriteriaPoints($preferences->getIncludeCriteriaPoints());
        $repoPrefs->setIncludeWriterNotes($preferences->getIncludeWriterNotes());
        
        $this->localDI->getCorrectorRepo()->save($repoPrefs);
        return true;
    }


    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the corrector key is a string of the corrector id
     */
    public function saveCorrectionSummary(CorrectionSummary $summary) : bool
    {
        $service = $this->localDI->getCorrectorAdminService($this->task->getTaskId());
        $essayRepo = $this->localDI->getEssayRepo();

        if (!empty($repoEssay = $essayRepo->getEssayByWriterIdAndTaskId((int) $summary->getItemKey(), $this->task->getTaskId()))) {

            $repoSummary = $essayRepo->getCorrectorSummaryByEssayIdAndCorrectorId($repoEssay->getId(), (int) $summary->getCorrectorKey());
            if (!isset($repoSummary)) {
                $repoSummary = new CorrectorSummary();
                $repoSummary->setEssayId($repoEssay->getId());
                $repoSummary->setCorrectorId((int) $summary->getCorrectorKey());
            }
            $repoSummary->setSummaryText($summary->getText());
            $repoSummary->setPoints($summary->getPoints());
            $repoSummary->setGradeLevelId($summary->getGradeKey() ? (int) $summary->getGradeKey() : null);
            $repoSummary->setLastChange($this->data->unixTimeToDb($summary->getLastChange()));
            $repoSummary->setIncludeComments($summary->getIncludeComments());
            $repoSummary->setIncludeCommentRatings($summary->getIncludeCommentRatings());
            $repoSummary->setIncludeCommentPoints($summary->getIncludeCommentPoints());
            $repoSummary->setIncludeCriteriaPoints($summary->getIncludeCriteriaPoints());
            $repoSummary->setIncludeWriterNotes($summary->getIncludeWriterNotes());
            $essayRepo->save($repoSummary);
            
            if ($summary->isAuthorized()) {
                $service->authorizeCorrection($repoSummary, $this->user->getId());
                if($service->tryFinalisation($repoEssay, $this->user->getId())) {
                    $service->sendReviewNotification($this->object->getRefId(), $repoEssay->getWriterId());
                }
            } else {
                $repoSummary->setCorrectionAuthorized(null);
                $repoSummary->setCorrectionAuthorizedBy(null);
                $essayRepo->save($repoSummary);
            }
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function saveStitchDecision(string $item_key, int $timestamp, ?float $points, ?string $grade_key, ?string $stitch_comment) : bool
    {
        $essayRepo = $this->localDI->getEssayRepo();
        $dataService = $this->localDI->getDataService($this->task->getTaskId());
        $service = $this->localDI->getCorrectorAdminService($this->task->getTaskId());

        if (!empty($repoEssay = $essayRepo->getEssayByWriterIdAndTaskId((int) $item_key, $this->task->getTaskId()))
            && empty($repoEssay->getCorrectionFinalized())) {
            $repoEssay->setFinalPoints($points);
            $repoEssay->setFinalGradeLevelId($grade_key ? (int) $grade_key : null);
            $repoEssay->setCorrectionFinalized($dataService->unixTimeToDb($timestamp));
            $repoEssay->setCorrectionFinalizedBy($this->user->getId());
            $repoEssay->setStitchComment($stitch_comment);
            $essayRepo->save($repoEssay);

            $service->sendReviewNotification($this->object->getRefId(), $repoEssay->getWriterId());

            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function saveCorrectionComment(CorrectionComment $comment): ?string
    {
        $correctorRepo = $this->localDI->getCorrectorRepo();
        if (!$correctorRepo->ifCorrectorIsAssigned((int) $comment->getItemKey(), (int) $comment->getCorrectorKey())) {
            return null;
        }

        $essayRepo = $this->localDI->getEssayRepo();
        $repoEssay = $essayRepo->getEssayByWriterIdAndTaskId((int) $comment->getItemKey(), $this->task->getTaskId());
        if (!isset($repoEssay) || $repoEssay->getWriterId() != (int) $comment->getItemKey()) {
            return null;
        }
        
        $repoComment = $essayRepo->getCorrectorCommentById((int) $comment->getKey());
        if (!isset($repoComment)) {
            $repoComment = CorrectorComment::model();
        } elseif ($repoComment->getEssayId() != $repoEssay->getId() || $repoComment->getCorrectorId() != (int) $comment->getCorrectorKey()) {
            return null;
        }
        $repoComment
            ->setEssayId($repoEssay->getId())
            ->setCorrectorId((int) $comment->getCorrectorKey())
            ->setStartPosition($comment->getStartPosition())
            ->setEndPosition($comment->getEndPosition())
            ->setParentNumber($comment->getParentNumber())
            ->setComment($comment->getComment())
            ->setRating($comment->getRating())
            ->setPoints($comment->getPoints())
            ->setMarksJson(json_encode(CorrectionMark::multiToArray($comment->getMarks())));
        
        $essayRepo->save($repoComment);
        
        return (string) $repoComment->getId();
    }

    /**
     * @inheritDoc
     */
    public function deleteCorrectionComment(string $comment_key, string $corrector_key): bool
    {
        $essayRepo = $this->localDI->getEssayRepo();
        $repoComment = $essayRepo->getCorrectorCommentById((int) $comment_key);
        if (!isset($repoComment)) {
            return true; // already deleted
        }
        if ((string) $repoComment->getCorrectorId() != $corrector_key) {
            return false;   // given corrector is not the owner corrector of that comment
        }
        $essayRepo->deleteCorrectorComment($repoComment->getId());
        return true;
    }

    /**
     * @inheritDoc
     */
    public function saveCorrectionPoints(CorrectionPoints $points): ?string
    {
        $essayRepo = $this->localDI->getEssayRepo();
        $repoComment = $essayRepo->getCorrectorCommentById((int) $points->getCommentKey());
        if (!isset($repoComment) || $repoComment->getCorrectorId() != (int) $points->getCorrectorKey()) {
            return null;
        }
        
        $objectRepo = $this->localDI->getObjectRepo();
        $repoCriterion = $objectRepo->getRatingCriterionById((int) $points->getCriterionKey());
        if (!isset($repoCriterion) || $repoCriterion->getObjectId() != $this->object->getId()) {
            return null;
        }
        
        $repoPoints = $essayRepo->getCriterionPointsById((int) $points->getKey());
        if (!isset($repoPoints)) {
            $repoPoints = CriterionPoints::model();
        } elseif ($repoPoints->getCorrCommentId() != (int) $points->getCommentKey()) {
            return null;
        }
        $repoPoints
            ->setCriterionId((int) $points->getCriterionKey())
            ->setCorrCommentId((int) $points->getCommentKey())
            ->setPoints($points->getPoints());

        $essayRepo->save($repoPoints);
        return (string) $repoPoints->getId();

    }

    /**
     * @inheritDoc
     */
    public function deleteCorrectionPoints(string $points_key, string $corrector_key): bool
    {
        $essayRepo = $this->localDI->getEssayRepo();
        $repoPoints = $essayRepo->getCriterionPointsById((int) $points_key);
        if (!isset($repoPoints)) {
            return true; // already deleted
        }
        $repoComment = $essayRepo->getCorrectorCommentById($repoPoints->getCorrCommentId());
        if (isset($repoComment) && (string) $repoComment->getCorrectorId() != $corrector_key) {
            return false;   // given corrector is not the owner of that comment
        }
        $essayRepo->deleteCriterionPoints($repoPoints->getId());
        return true;
    }
}
