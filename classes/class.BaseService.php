<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask;

/**
 * Base class for service classes
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
abstract class BaseService
{
	/** @var \ILIAS\DI\Container */
	public $dic;

	/** @var \ilLanguage */
	public $lng;

    /** @var  \ilObjLongEssayTask */
    public $object;

    /** @var  \ilLongEssayTaskPlugin */
    public $plugin;

    /** @var LongEssayTaskDI */
    protected $localDI;


    /**
	 * Constructor
	 * @param \ilObjLongEssayTask
	 */
	public function __construct($object)
	{
		global $DIC;

		// ILIAS dependencies
        $this->dic = $DIC;
        $this->ctrl = $this->dic->ctrl();
        $this->lng = $this->dic->language();

        // Plugin dependencies
        $this->object = $object;
		$this->plugin = $this->object->getPlugin();
        $this->localDI = LongEssayTaskDI::getInstance();
	}
}