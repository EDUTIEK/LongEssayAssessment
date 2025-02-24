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
 */
class ilObjLongEssayAssessment extends ilObjectPlugin
{
    /** @var ilLongEssayAssessmentPlugin */
    protected ?ilPlugin $plugin = null;


    public function __construct($a_ref_id = 0)
    {
        parent::__construct($a_ref_id);
    }

    final public function initType(): void
    {
        $this->setType(ilLongEssayAssessmentPlugin::ID);
    }

    /**
     * Create object
     * @param bool $clone_mode
     */
    protected function doCreate(bool $clone_mode = false): void
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
    protected function doRead(): void
    {
        $this->data = $this->localDI->getDataService($this->getId());
        $this->objectSettings = $this->localDI->getObjectRepo()->getObjectSettingsById($this->getId());
        $this->taskSettings = $this->localDI->getTaskRepo()->getTaskSettingsById($this->getId());
        $this->correctionSettings = $this->localDI->getTaskRepo()->getCorrectionSettingsById($this->getId());
    }

    /**
     * Update data
     */
    protected function doUpdate(): void
    {
        $this->localDI->getObjectRepo()->save($this->objectSettings);
    }

    /**
     * Delete data from db
     */
    protected function doDelete(): void
    {
        $task_repo = $this->localDI->getTaskRepo();
        $essay_repo = $this->localDI->getEssayRepo();

        $old_resource = $task_repo->getResourceByTaskId($this->getId());
        foreach ($old_resource as $resource) {
            if ($resource instanceof Resource &&
                $resource->getFileId() !== null &&
                ($identifier = $this->resource->manage()->find($resource->getFileId()))) {
                $this->resource->manage()->remove($identifier, new ResourceResourceStakeholder());
            }
        }
        $old_essays = $essay_repo->getEssaysByTaskId($this->getId());
        foreach ($old_essays as $essay) {
            if ($essay->getPdfVersion() !== null && ($identifier = $this->resource->manage()->find($essay->getPdfVersion()))) {
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
    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null): void
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
        foreach ($old_grade_level as $grade_level) {
            if ($grade_level instanceof GradeLevel) {
                $new_grade_level[] = (clone $grade_level)->setObjectId($new_obj->getId())->setId(0);
            }
        }

        $old_rating_criterion = $object_repo->getRatingCriteriaByObjectId($this->getId());
        $new_rating_criterion = [];
        foreach ($old_rating_criterion as $rating_criterion) {
            if ($rating_criterion instanceof RatingCriterion) {
                $new_rating_criterion[] = (clone $rating_criterion)->setObjectId($new_obj->getId())->setId(0);
            }
        }

        $old_resource = $task_repo->getResourceByTaskId($this->getId());
        $new_resource = [];
        foreach ($old_resource as $resource) {
            if ($resource instanceof Resource) {
                $new_resource[] = (clone $resource)->setTaskId($new_obj->getId())->setId(0);
            }
        }

        // Creation Area
        $object_repo->save($new_obj_settings);
        $task_repo->save($new_task_settings->setTaskId($new_obj->getId()));
        $task_repo->save($new_editor_settings->setTaskId($new_obj->getId()));
        $task_repo->save($new_correction_settings->setTaskId($new_obj->getId()));

        foreach ($new_grade_level as $grade_level) {
            $object_repo->save($grade_level);
        }

        foreach ($new_rating_criterion as $rating_criterion) {
            $object_repo->save($rating_criterion);
        }

        foreach ($new_resource as $resource) {
            if ($resource->getFileId() !== null &&
                ($identifier = $this->resource->manage()->find($resource->getFileId()))
            ) {
                $new_file_id = $this->resource->manage()->clone($identifier);
                $resource->setFileId((string) $new_file_id);
            }

            $task_repo->save($resource);
        }
    }



    /**
     * Check if the user can edit the fixed rating criteria
     * @deprecated - use assessment service
     */
    public function canEditFixedRatingCriteria(): bool
    {
        if ($this->canEditContentSettings()) {
            $repo = $this->localDI->getTaskRepo();
            $settings = $repo->getCorrectionSettingsById($this->getId()) ?? new CorrectionSettings($this->getId());
            return in_array($settings->getCriteriaMode(), [CorrectionSettings::CRITERIA_MODE_FIXED, CorrectionSettings::CRITERIA_MODE_CORRECTOR]);
        }
        return false;
    }

    /**
     * @deprecated - use assessment service
     */
    public function canEditOwnRatingCriteria(): bool
    {
        if ($this->canViewCorrectorScreen()) {
            $repo = $this->localDI->getTaskRepo();
            $settings = $repo->getCorrectionSettingsById($this->getId()) ?? new CorrectionSettings($this->getId());
            return ($settings->getCriteriaMode() == CorrectionSettings::CRITERIA_MODE_CORRECTOR);
        }
        return false;
    }


    /**
     *Check if the user can edit the criteria
     * @deprecated - use assessment service
     */
    public function canEditCriteria(): bool
    {
        return $this->access->checkAccess('maintain_task', '', $this->getRefId());
    }

    /**
     *Check if the user can edit additional material
     * @deprecated - use assessment service
     */
    public function canEditMaterial(): bool
    {
        return $this->access->checkAccess('maintain_task', '', $this->getRefId());
    }
}
