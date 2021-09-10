<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayTask\Data\ObjectSettings;

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

    /** @var ObjectSettings */
    protected $objectSettings;

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
        $this->objectSettings = ObjectSettings::findOrGetInstance($this->getId());
		$this->objectSettings->create();
	}

	/**
	 * Read data from db
	 */
    protected function doRead()
	{
        $this->objectSettings = ObjectSettings::findOrGetInstance($this->getId());
	    $this->objectSettings->read();
	}

	/**
	 * Update data
	 */
    protected function doUpdate()
	{
        $this->objectSettings->update();
	}

	/**
	 * Delete data from db
	 */
    protected function doDelete()
	{
		$this->objectSettings->delete();
	}

	/**
	 * Do Cloning
     * @param self $new_obj
     * @param int $a_target_id
     * @param int|null $a_copy_id
	 */
    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null)
	{
		$new_obj->objectSettings = clone $this->objectSettings;
		$new_obj->objectSettings->setObjId($new_obj->getId());
		$new_obj->update();
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
     * Get the plugin object
     * @return object
     */
	public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * Check if the current user can view the
     */
    public function canViewWriterScreen()
    {
        return $this->access->checkAccess('read', '', $this->getRefId());
    }

    /**
     * Check if the current user can view the
     */
    public function canViewCorrectorScreen()
    {
        return $this->access->checkAccess('read', '', $this->getRefId());
    }


    /**
     * Check if the current user can edit the organisational settings (online, dates)
     */
    public function canEditOrgaSettings()
    {
        return $this->access->checkAccess('write', '', $this->getRefId());
    }

    /**
     *Check if the user can edit additional material
     */
    public function canEditTechnicalSettings()
    {
        return $this->access->checkAccess('write', '', $this->getRefId());
    }

    /**
     *Check if the user can edit the content settings
     */
    public function canEditContentSettings()
    {
        return $this->access->checkAccess('maintain_task', '', $this->getRefId());
    }

    /**
     *Check if the user can edit the grades
     */
    public function canEditGrades()
    {
        return $this->access->checkAccess('maintain_task', '', $this->getRefId());
    }

    /**
     *Check if the user can edit the criteria
     */
    public function canEditCriteria()
    {
        return $this->access->checkAccess('maintain_task', '', $this->getRefId());
    }

    /**
     *Check if the user can edit additional material
     */
    public function canEditMaterial()
    {
        return $this->access->checkAccess('maintain_task', '', $this->getRefId());
    }

    /**
     *Check if the user can maintain the writers
     */
    public function canMaintainWriters() {
        return $this->access->checkAccess('maintain_writers', '', $this->getRefId());
    }

    /**
     *Check if the user can maintain the writers
     */
    public function canMaintainCorrectors() {
        return $this->access->checkAccess('maintain_correctors', '', $this->getRefId());
    }
}
