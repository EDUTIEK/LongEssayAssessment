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
	protected $dic;

	/** @var \ilCtrl */
    protected $ctrl;

	/** @var  \ilTabsGUI */
    protected $tabs;

	/** @var \ilGlobalTemplateInterface */
    protected $tpl;

	/** @var \ilLanguage */
    protected $lng;

	/** @var \ilToolbarGUI */
	protected $toolbar;

    /** @var \ilObjLongEssayTaskGUI */
    protected $objectGUI;

    /** @var  \ilObjLongEssayTask */
    protected $object;

    /** @var  \ilLongEssayTaskPlugin */
    protected $plugin;

    /** @var \ILIAS\UI\Factory  */
    protected $uiFactory;

    /** @var \ILIAS\UI\Renderer  */
    protected $renderer;

    /** @var \Psr\Http\Message\RequestInterface|\Psr\Http\Message\ServerRequestInterface  */
    protected $request;

    protected $refinery;

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
        $this->uiFactory = $this->dic->ui()->factory();
        $this->renderer = $this->dic->ui()->renderer();
        $this->request = $this->dic->http()->request();
        $this->refinery = $this->dic->refinery();

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