<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\DataService;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\EditorSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\GradeLevel;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\RatingCriterion;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\Task\ResourceResourceStakeholder;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\PDFVersionResourceStakeholder;

/**
 * Repository object
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class ilObjLongEssayAssessment extends ilObjectPlugin
{
    /** @var Container */
    protected $dic;

    /** @var ilAccess */
    protected $access;

    /** @var ilObjUser */
    protected ilObjUser $user;

    /** @var \ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectSettings */
    protected $objectSettings;

    /** @var \ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings */
    protected $taskSettings;

    /** @var \ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings */
    protected $correctionSettings;


    /** @var LongEssayAssessmentDI */
    protected $localDI;

    /** @var ilLongEssayAssessmentPlugin */
    protected ?ilPlugin $plugin;

    /** @var DataService  */
    protected $data;
    private \ILIAS\ResourceStorage\Services $resource;

    /**
     * Constructor
     *
     * @access        public
     * @param int $a_ref_id
     */
    public function __construct($a_ref_id = 0)
    {
        global $DIC;
        $this->dic = $DIC;
        $this->access = $DIC->access();
        $this->user = $DIC->user();
        $this->localDI = LongEssayAssessmentDI::getInstance();
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
        $this->resource = $DIC->resourceStorage();

        parent::__construct($a_ref_id);
    }


    /**
     * Get type.
     */
    final public function initType() : void
    {
        $this->setType(ilLongEssayAssessmentPlugin::ID);
    }

    /**
     * Create object
     * @param bool $clone_mode
     */
    protected function doCreate(bool $clone_mode = false) : void
    {
        $object_repo = $this->localDI->getObjectRepo();
        $task_repo = $this->localDI->getTaskRepo();

        $new_correction_settings = (new CorrectionSettings($this->getId()))
            ->setPositiveRating($this->plugin->txt("comment_rating_positive_default"))
            ->setNegativeRating($this->plugin->txt("comment_rating_negative_default"));
        $task_repo->save($new_correction_settings);

        $this->objectSettings = $object_repo->getObjectSettingsById($this->getId());
        $this->taskSettings = $task_repo->getTaskSettingsById($this->getId());
        $this->correctionSettings = $task_repo->getCorrectionSettingsById($this->getId());
    }

    /**
     * Read data from db
     */
    protected function doRead() : void
    {
        $this->data = $this->localDI->getDataService($this->getId());
        $this->objectSettings = $this->localDI->getObjectRepo()->getObjectSettingsById($this->getId());
        $this->taskSettings = $this->localDI->getTaskRepo()->getTaskSettingsById($this->getId());
        $this->correctionSettings = $this->localDI->getTaskRepo()->getCorrectionSettingsById($this->getId());
    }

    /**
     * Update data
     */
    protected function doUpdate() : void
    {
        $this->localDI->getObjectRepo()->save($this->objectSettings);
    }

    /**
     * Delete data from db
     */
    protected function doDelete() : void
    {
        $task_repo = $this->localDI->getTaskRepo();
        $essay_repo = $this->localDI->getEssayRepo();

        $old_resource = $task_repo->getResourceByTaskId($this->getId());
        foreach($old_resource as $resource) {
            if($resource instanceof Resource &&
                $resource->getFileId() !== null &&
                ($identifier = $this->resource->manage()->find($resource->getFileId()))) {
                $this->resource->manage()->remove($identifier, new ResourceResourceStakeholder());
            }
        }
        $old_essays = $essay_repo->getEssaysByTaskId($this->getId());
        foreach($old_essays as $essay) {
            if($essay->getPdfVersion() !== null && ($identifier = $this->resource->manage()->find($essay->getPdfVersion()))) {
                $this->resource->manage()->remove($identifier, new PDFVersionResourceStakeholder());
            }
        }

        $object_repo = $this->localDI->getObjectRepo();
        $object_repo->deleteObject($this->getId());

    }

    /**
     * Do Cloning
     * @param self $new_obj
     * @param int $a_target_id
     * @param int|null $a_copy_id
     */
    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null) : void
    {
        $object_repo = $this->localDI->getObjectRepo();
        $task_repo = $this->localDI->getTaskRepo();

        //Cloning Area
        $new_obj->objectSettings = clone $this->objectSettings;
        $new_obj_settings = $new_obj->objectSettings->setObjId($new_obj->getId());

        $new_task_settings = clone $task_repo->getTaskSettingsById($this->getId());
        $new_editor_settings = clone $task_repo->getEditorSettingsById($this->getId());
        $new_correction_settings = clone $task_repo->getCorrectionSettingsById($this->getId());

        $old_grade_level = $object_repo->getGradeLevelsByObjectId($this->getId());
        $new_grade_level = [];
        foreach($old_grade_level as $grade_level) {
            if ($grade_level instanceof GradeLevel) {
                $new_grade_level[] = (clone $grade_level)->setObjectId($new_obj->getId())->setId(0);
            }
        }

        $old_rating_criterion = $object_repo->getRatingCriteriaByObjectId($this->getId());
        $new_rating_criterion = [];
        foreach($old_rating_criterion as $rating_criterion) {
            if ($rating_criterion instanceof RatingCriterion) {
                $new_rating_criterion[] = (clone $rating_criterion)->setObjectId($new_obj->getId())->setId(0);
            }
        }

        $old_resource = $task_repo->getResourceByTaskId($this->getId());
        $new_resource = [];
        foreach($old_resource as $resource) {
            if($resource instanceof Resource) {
                $new_resource[] = (clone $resource)->setTaskId($new_obj->getId())->setId(0);
            }
        }

        // Creation Area
        $object_repo->save($new_obj_settings);
        $task_repo->save($new_task_settings->setTaskId($new_obj->getId()));
        $task_repo->save($new_editor_settings->setTaskId($new_obj->getId()));
        $task_repo->save($new_correction_settings->setTaskId($new_obj->getId()));

        foreach($new_grade_level as $grade_level) {
            $object_repo->save($grade_level);
        }

        foreach($new_rating_criterion as $rating_criterion) {
            $object_repo->save($rating_criterion);
        }

        foreach($new_resource as $resource) {
            if($resource->getFileId() !== null &&
                ($identifier = $this->resource->manage()->find($resource->getFileId()))
            ) {
                $new_file_id = $this->resource->manage()->clone($identifier);
                $resource->setFileId((string) $new_file_id);
            }

            $task_repo->save($resource);
        }
    }

    /**
     * Set online
     *
     * @param boolean $a_val
     */
    public function setOnline($a_val)
    {
        $this->objectSettings->setOnline($a_val);
    }

    /**
     * Set the Participation Type
     * @param string $a_type
     */
    public function setParticipationType($a_type)
    {
        $this->objectSettings->setParticipationType($a_type);
    }

    /**
     * Get the Participation Type
     * @return string
     */
    public function getParticipationType()
    {
        return $this->objectSettings->getParticipationType();
    }

    /**
     * Get online
     * @return bool
     */
    public function isOnline()
    {
        return (bool) $this->objectSettings->isOnline();
    }

    public function canViewInfoScreen() : bool
    {
        return $this->access->checkAccess('write', '', $this->getRefId());
    }
    
    /**
     * Check if the current user can view the writer screen
     * The screen is available until the end of the writing period or if the user is a writer
     */
    public function canViewWriterScreen() : bool
    {
        return $this->access->checkAccess('read', '', $this->getRefId())
            && (
                $this->objectSettings->getParticipationType() == ObjectSettings::PARTICIPATION_TYPE_INSTANT
                || $this->localDI->getWriterRepo()->ifUserExistsInTasksAsWriter($this->user->getId(), $this->getId())
            );
    }

    /**
     * Check if the current user can view the corrector screen
     */
    public function canViewCorrectorScreen() : bool
    {
        return $this->access->checkAccess('read', '', $this->getRefId())
            && $this->localDI->getCorrectorRepo()->ifUserExistsInTaskAsCorrector($this->user->getId(), $this->getId());
    }


    /**
     * Check if the current user can edit the organisational settings (online, dates)
     */
    public function canEditOrgaSettings() : bool
    {
        return $this->access->checkAccess('write', '', $this->getRefId());
    }

    /**
     *Check if the user can edit additional material
     */
    public function canEditTechnicalSettings() : bool
    {
        return $this->access->checkAccess('write', '', $this->getRefId());
    }

    /**
     *Check if the user can edit the content settings
     */
    public function canEditContentSettings() : bool
    {
        return $this->access->checkAccess('maintain_task', '', $this->getRefId());
    }

    /**
     *Check if the user can edit the fixed rating criteria
     */
    public function canEditFixedRatingCriteria() : bool
    {
        if ($this->canEditContentSettings()) {
            $repo = $this->localDI->getTaskRepo();
            $settings = $repo->getCorrectionSettingsById($this->getId()) ?? new CorrectionSettings($this->getId());
            return in_array($settings->getCriteriaMode(), [CorrectionSettings::CRITERIA_MODE_FIXED, CorrectionSettings::CRITERIA_MODE_CORRECTOR]);
        }
        return false;
    }

    public function canEditOwnRatingCriteria() : bool
    {
        if ($this->canViewCorrectorScreen()) {
            $repo = $this->localDI->getTaskRepo();
            $settings = $repo->getCorrectionSettingsById($this->getId()) ?? new CorrectionSettings($this->getId());
            return ($settings->getCriteriaMode() == CorrectionSettings::CRITERIA_MODE_CORRECTOR);
        }
        return false;
    }


    /**
     *Check if the user can edit the grades
     */
    public function canEditGrades() : bool
    {
        return $this->access->checkAccess('maintain_task', '', $this->getRefId());
    }

    /**
     *Check if the user can edit the criteria
     */
    public function canEditCriteria() : bool
    {
        return $this->access->checkAccess('maintain_task', '', $this->getRefId());
    }

    /**
     *Check if the user can edit additional material
     */
    public function canEditMaterial() : bool
    {
        return $this->access->checkAccess('maintain_task', '', $this->getRefId());
    }

    /**
     *Check if the user can maintain the writers
     */
    public function canMaintainWriters() : bool
    {
        return $this->access->checkAccess('maintain_writers', '', $this->getRefId());
    }

    /**
     *Check if the user can maintain the writers
     */
    public function canMaintainCorrectors() : bool
    {
        return $this->access->checkAccess('maintain_correctors', '', $this->getRefId());
    }

    /**
     * Check if the user can write the essay
     */
    public function canWrite() : bool
    {
        if (!$this->canViewWriterScreen()) {
            return false;
        }

        // check if not authorized
        if (!empty($essay = $this->data->getOwnEssay())) {
            if (!empty($essay->getWritingAuthorized())) {
                return false;
            }
            if (!empty($essay->getWritingExcluded())) {
                return false;
            }
        }

        // check time in range
        $start = $this->data->dbTimeToUnix($this->taskSettings->getWritingStart());
        $end = $this->data->dbTimeToUnix($this->taskSettings->getWritingEnd());
        if (!empty($end)) {
            $end += $this->data->getOwnTimeExtensionSeconds();
        }
        return $this->data->isInRange(time(), $start, $end);
    }

    /**
     *  Check if the user can view the solution
     */
    public function canViewSolution() : bool
    {
        if (!$this->canViewWriterScreen()) {
            return false;
        }
        if (!$this->taskSettings->isSolutionAvailable()) {
            return false;
        }
        return $this->data->isInRange(
            time(),
            $this->data->dbTimeToUnix($this->taskSettings->getSolutionAvailableDate()),
            null
        );
    }

    public function canViewWriterStatistics(): bool
    {
        if(!$this->canViewResult()){
            return false;
        }
        return $this->taskSettings->isStatisticsAvailable();
    }

    public function canViewResult() : bool
    {
        if (!$this->canViewWriterScreen()) {
            return false;
        }

        if (empty($essay = $this->data->getOwnEssay()) || empty($essay->getCorrectionFinalized())) {
            return false;
        }

        switch ($this->taskSettings->getResultAvailableType()) {
            case TaskSettings::RESULT_AVAILABLE_FINALISED:
                return true;
            case TaskSettings::RESULT_AVAILABLE_REVIEW:
                return $this->canReviewCorrectedEssay();
            case TaskSettings::RESULT_AVAILABLE_DATE:
                return $this->data->isInRange(
                    time(),
                    $this->data->dbTimeToUnix($this->taskSettings->getResultAvailableDate()),
                    null
                );
        }
        return false;
    }

    /**
     *  Check if the user can review his/her own written essay (authorized or not)
     */
    public function canReviewWrittenEssay() : bool
    {
        if (!$this->canViewWriterScreen()) {
            return false;
        }

        if ($this->canWrite()) {
            // no review if writing is (still) possible
            return false;
        }

        if (!$this->taskSettings->getKeepEssayAvailable()) {
            return false;
        }

        $essay = $this->data->getOwnEssay();

        switch ($this->taskSettings->getTaskType()) {
            case TaskSettings::TYPE_ESSAY_EDITOR:
                // writing has started, but text may still be completely offline in the writer app
                return $essay !== null;

            case TaskSettings::TYPE_PDF_UPLOAD:
                // a pdf upload exists
                return $essay !== null && $essay->getPdfVersion() !== null;
        }

        return false;
    }


    /**
     * Check if the user can review the correction his/her own essay
     */
    public function canReviewCorrectedEssay() : bool
    {
        if (!$this->canViewWriterScreen()) {
            return false;
        }

        if (!$this->taskSettings->isReviewEnabled()) {
            return false;
        }
        elseif (!$this->data->isInRange(
                time(),
                $this->data->dbTimeToUnix($this->taskSettings->getReviewStart()),
                $this->data->dbTimeToUnix($this->taskSettings->getReviewEnd())
        )) {
            return false;
        }

        // check if essay is authorized
        if (empty($essay = $this->data->getOwnEssay())) {
            return false;
        } elseif (empty($essay->getCorrectionFinalized())) {
            return false;
        }

        return true;
    }

    /**
     * Check if the user can correct essays
     */
    public function canCorrect() : bool
    {
        if (!$this->canViewCorrectorScreen()) {
            return false;
        }

        if (!$this->data->isInRange(
            time(),
            $this->data->dbTimeToUnix($this->taskSettings->getCorrectionStart()),
            $this->data->dbTimeToUnix($this->taskSettings->getCorrectionEnd())
        )) {
            return false;
        }

        return true;
    }

    /**
     * Check if the user can write a correction report
     */
    public function canWriteCorrectionReport() : bool
    {
        if (!$this->canViewCorrectorScreen()) {
            return false;
        }

        return $this->correctionSettings->getReportsEnabled();

        if (!$this->data->isInRange(
            time(),
            $this->data->dbTimeToUnix($this->taskSettings->getCorrectionStart()),
            $this->data->dbTimeToUnix($this->taskSettings->getCorrectionEnd())
        )) {
            return false;
        }

        return true;
    }

    /**
     * Check if the user can download a correction report
     */
    public function canDownloadCorrectionReports() : bool
    {
        if (!$this->canViewWriterScreen()) {
            return false;
        }

        if (!$this->correctionSettings->getReportsEnabled() || !$this->data->isInRange(
                time(),
                $this->data->dbTimeToUnix($this->correctionSettings->getReportsAvailableStart()),
                null
        )) {
            return false;
        }

        return true;
    }

}
