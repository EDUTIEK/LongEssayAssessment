<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Base class for GUI classes (except tables)
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
abstract class ilLongEssayTaskBaseGUI
{
	/** @var ilObjLongEssayTaskGUI */
	public $parent;

	/** @var  ilObjLongEssayTask */
	public $object;

	/** @var  ilLongEssayTaskPlugin */
	public $plugin;

	/** @var ilCtrl */
	public $ctrl;

	/** @var  ilTabsGUI */
	public $tabs;

	/** @var ilGlobalTemplateInterface */
	public $tpl;

	/** @var ilLanguage */
	public $lng;

	/** @var ilToolbarGUI */
	protected $toolbar;

	/**
	 * Constructor
	 * @param ilObjLongEssayTaskGUI  $a_parent_gui
	 */
	public function __construct($a_parent_gui)
	{
		global $DIC;

		$this->parent = $a_parent_gui;
		$this->object = $this->parent->object;
		$this->plugin = $this->parent->plugin;
		$this->ctrl = $DIC->ctrl();
		$this->tabs = $DIC->tabs();
		$this->toolbar = $DIC->toolbar();
        $this->lng = $DIC->language();
		$this->tpl = $DIC->ui()->mainTemplate();
	}

	/**
	 * Execute a command
	 * This should be overridden in the child classes
	 * note: permissions are already checked in parent gui
	 */
	public function executeCommand()
	{
		$cmd = $this->ctrl->getCmd('xxx');
		switch ($cmd)
		{
			case 'yyy':
			case 'zzz':
				$this->$cmd();
				return;

			default:
				// show unknown command
                $this->tpl->setContent('unknown command: ' . $cmd);
				return;
		}
	}
}