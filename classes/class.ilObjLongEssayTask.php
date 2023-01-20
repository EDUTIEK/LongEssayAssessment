<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayTask\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayTask\Data\CorrectionSettings;
use ILIAS\Plugin\LongEssayTask\Data\DataService;
use ILIAS\Plugin\LongEssayTask\Data\EditorSettings;
use ILIAS\Plugin\LongEssayTask\Data\GradeLevel;
use ILIAS\Plugin\LongEssayTask\Data\ObjectSettings;
use ILIAS\Plugin\LongEssayTask\Data\PluginConfig;
use ILIAS\Plugin\LongEssayTask\Data\RatingCriterion;
use ILIAS\Plugin\LongEssayTask\Data\Resource;
use ILIAS\Plugin\LongEssayTask\Data\TaskSettings;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use ILIAS\Plugin\LongEssayTask\Task\ResourceResourceStakeholder;

/**
 * Repository object
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class ilObjLongEssayTask extends ilObjectPlugin
{
    /** @var Container */
    protected $dic;

    /** @var ilAccess */
    protected $access;

    /** @var ilObjUser */
    protected $user;

    /** @var ObjectSettings */
    protected $objectSettings;

    /** @var TaskSettings */
    protected $taskSettings;

    /** @var LongEssayTaskDI */
    protected $localDI;

    /** @var ilLongEssayTaskPlugin */
    protected $plugin;

    /** @var DataService  */
    protected $data;
	private \ILIAS\ResourceStorage\Services $resource;

	/**
	 * Constructor
	 *
	 * @access        public
	 * @param int $a_ref_id
	 */
	function __construct($a_ref_id = 0)
	{
	    global $DIC;
	    $this->dic = $DIC;
        $this->access = $DIC->access();
        $this->user = $DIC->user();
        $this->localDI = LongEssayTaskDI::getInstance();
        $this->plugin = ilLongEssayTaskPlugin::getInstance();
		$this->resource = $DIC->resourceStorage();

		parent::__construct($a_ref_id);
	}


	/**
	 * Get type.
	 */
	final function initType()
	{
		$this->setType(ilLongEssayTaskPlugin::ID);
	}

	/**
	 * Create object
	 */
	protected function doCreate()
	{
        $di = LongEssayTaskDI::getInstance();
        $object_repo = $di->getObjectRepo();
        $task_repo = $di->getTaskRepo();

        $new_obj_settings = new ObjectSettings($this->getId());
        $new_task_settings = new TaskSettings($this->getId());
        $new_editor_settings = new EditorSettings($this->getId());
        $new_correction_settings = new CorrectionSettings($this->getId());

        $object_repo->createObject($new_obj_settings);
        $task_repo->createTask($new_task_settings, $new_editor_settings, $new_correction_settings);
        $this->objectSettings = $new_obj_settings;
        $this->taskSettings = $new_task_settings;
	}

	/**
	 * Read data from db
	 */
    protected function doRead()
	{
        $this->data = $this->localDI->getDataService($this->getId());
        $this->objectSettings = $this->localDI->getObjectRepo()->getObjectSettingsById($this->getId());
        $this->taskSettings = $this->localDI->getTaskRepo()->getTaskSettingsById($this->getId());
	}

	/**
	 * Update data
	 */
    protected function doUpdate()
	{
        $this->localDI->getObjectRepo()->updateObjectSettings($this->objectSettings);
	}

	/**
	 * Delete data from db
	 */
    protected function doDelete()
	{
		$task_repo = $this->localDI->getTaskRepo();

		$old_resource = $task_repo->getResourceByTaskId($this->getId());
		foreach($old_resource as $resource){
			if($resource instanceof Resource &&
				$resource->getFileId() !== null &&
				($identifier = $this->resource->manage()->find($resource->getFileId())))
			{
				$this->resource->manage()->remove($identifier, new ResourceResourceStakeholder());
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
    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null)
	{
        $object_repo = $this->localDI->getObjectRepo();
        $task_repo = $this->localDI->getTaskRepo();

        //Cloning Area
		$new_obj->objectSettings = clone $this->objectSettings;
        $new_obj_settings = $new_obj->objectSettings->setObjId($new_obj->getId());

        $new_plugin_settings = clone $object_repo->getPluginConfigById($this->getId());
        $new_task_settings = clone $task_repo->getTaskSettingsById($this->getId());
        $new_editor_settings = clone $task_repo->getEditorSettingsById($this->getId());
        $new_correction_settings = clone $task_repo->getCorrectionSettingsById($this->getId());

        $old_grade_level = $object_repo->getGradeLevelsByObjectId($this->getId());
        $new_grade_level = [];
        foreach($old_grade_level as $grade_level)
        {
            if ($grade_level instanceof GradeLevel)
            {
                $new_grade_level[] = (clone $grade_level)->setObjectId($new_obj->getId())->setId(0);
            }
        }

        $old_rating_criterion = $object_repo->getRatingCriterionByObjectId($this->getId());
        $new_rating_criterion = [];
        foreach($old_rating_criterion as $rating_criterion)
        {
            if ($rating_criterion instanceof RatingCriterion)
            {
                $new_rating_criterion[] = (clone $rating_criterion)->setObjectId($new_obj->getId())->setId(0);
            }
        }

		$old_resource = $task_repo->getResourceByTaskId($this->getId());
		$new_resource = [];
		foreach($old_resource as $resource){
			if($resource instanceof Resource){
				$new_resource[] = (clone $resource)->setTaskId($new_obj->getId())->setId(0);
			}
		}

        // Creation Area
        $object_repo->updateObjectSettings($new_obj_settings);
        $object_repo->updatePluginConfig($new_plugin_settings->setId($new_obj->getId()));

        $task_repo->updateTaskSettings($new_task_settings->setTaskId($new_obj->getId()));
        $task_repo->updateEditorSettings($new_editor_settings->setTaskId($new_obj->getId()));
        $task_repo->updateCorrectionSettings($new_correction_settings->setTaskId($new_obj->getId()));

        foreach($new_grade_level as $grade_level)
        {
            $object_repo->createGradeLevel($grade_level);
        }

        foreach($new_rating_criterion as $rating_criterion)
        {
            $object_repo->createRatingCriterion($rating_criterion);
        }

		foreach($new_resource as $resource)
		{
			if($resource->getFileId() !== null &&
				($identifier = $this->resource->manage()->find($resource->getFileId()))
			) {
				$new_file_id = $this->resource->manage()->clone($identifier);
				$resource->setFileId((string) $new_file_id);
			}

			$task_repo->createResource($resource);
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

    /**
     * Check if the current user can view the writer screen
     * The screen is available until the end of the writing period or if the user is a writer
     */
    public function canViewWriterScreen() : bool
    {
        if (!$this->access->checkAccess('read', '', $this->getRefId())) {
            // no permission
            return false;
        }
        elseif ($this->localDI->getWriterRepo()->ifUserExistsInTasksAsWriter($this->user->getId(), $this->getId())) {
            // always show screen if user is added as writer or has started writing an essay
            return true;
        }
        elseif ($this->objectSettings->getParticipationType() != ObjectSettings::PARTICIPATION_TYPE_INSTANT) {
            // don't show screen if instant participation is not allowed
            return false;
        }
        elseif (!empty($this->taskSettings->getWritingEnd())) {
            // show screen until the end of the writing period if an end is set
            return time() < $this->data->dbTimeToUnix($this->taskSettings->getWritingEnd());
        }
        else {
            // instant participation without writing end allowed
            return true;
        }
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

        // check if in writing time
        if (!$this->data->isInRange(time(),
            $this->data->dbTimeToUnix($this->taskSettings->getWritingStart()),
            $this->data->dbTimeToUnix($this->taskSettings->getWritingEnd()) + $this->data->getOwnTimeExtensionSeconds())
        ) {
            return false;
        }

        return true;
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
        return $this->data->isInRange(time(),
            $this->data->dbTimeToUnix($this->taskSettings->getSolutionAvailableDate()),
            null);
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
                return $this->data->isInRange(time(),
                    $this->data->dbTimeToUnix($this->taskSettings->getSolutionAvailableDate()),
                    null);
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

        // no review if writing is (still) possible
        if ($this->canWrite()) {
            return false;
        }
        return ($this->taskSettings->getKeepEssayAvailable()  && !empty($this->data->getOwnEssay()));
    }


    /**
     * Check if the user can review the correction his/her own essay
     */
    public function canReviewCorrectedEssay() : bool
    {
        if (!$this->canViewWriterScreen()) {
            return false;
        }

        if (!$this->data->isInRange(time(),
            $this->data->dbTimeToUnix($this->taskSettings->getReviewStart()),
            $this->data->dbTimeToUnix($this->taskSettings->getReviewEnd()))) {
            return false;
        }

        // check if essay is authorized
        if (empty($essay = $this->data->getOwnEssay())) {
            return false;
        }
        elseif(empty($essay->getWritingAuthorized())) {
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

        if (!$this->data->isInRange(time(),
            $this->data->dbTimeToUnix($this->taskSettings->getCorrectionStart()),
            $this->data->dbTimeToUnix($this->taskSettings->getCorrectionEnd()))) {
            return false;
        }

        return true;
    }
}
