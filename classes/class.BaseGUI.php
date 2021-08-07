<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask;

/**
 * Base class for GUI classes (excxept the plugin guis required by ILIAS)
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
abstract class BaseGUI
{
	/** @var \ILIAS\DI\Container */
	public $dic;

	/** @var \ilCtrl */
	public $ctrl;

	/** @var  \ilTabsGUI */
	public $tabs;

	/** @var \ilGlobalTemplateInterface */
	public $tpl;

	/** @var \ilLanguage */
	public $lng;

	/** @var \ilToolbarGUI */
	protected $toolbar;

    /** @var \ilObjLongEssayTaskGUI */
    public $objectGUI;

    /** @var  \ilObjLongEssayTask */
    public $object;

    /** @var  \ilLongEssayTaskPlugin */
    public $plugin;


    /**
	 * Constructor
	 * @param \ilObjLongEssayTaskGUI  $objectGUI
	 */
	public function __construct($objectGUI)
	{
		global $DIC;

		// ILIAS dependencies
        $this->dic = $DIC;
        $this->ctrl = $this->dic->ctrl();
        $this->tabs = $this->dic->tabs();
        $this->toolbar = $this->dic->toolbar();
        $this->lng = $this->dic->language();
        $this->tpl = $this->dic->ui()->mainTemplate();

        // Plugin dependencies
        $this->objectGUI = $objectGUI;
        $this->object = $this->objectGUI->getObject();
		$this->plugin = $this->objectGUI->getPlugin();


	}

	/**
	 * Execute a command
	 * This should be overridden in the child classes
	 * note: permissions are already checked in the object gui
	 */
	public function executeCommand()
	{
		$cmd = $this->ctrl->getCmd('xxx');
		switch ($cmd)
		{
			case 'yyy':
			case 'zzz':
				$this->$cmd();
				break;

			default:
				// show unknown command
                $this->tpl->setContent('unknown command: ' . $cmd);
		}
	}
}