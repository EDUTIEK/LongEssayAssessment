<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask;

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayTask\Data\DataService;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base class for GUI classes (except the plugin guis required by ILIAS)
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
abstract class BaseGUI
{
	/** @var Container */
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

    /** @var Factory  */
    protected $uiFactory;

    /** @var Renderer  */
    protected $renderer;

    /** @var RequestInterface|ServerRequestInterface  */
    protected $request;

    /** @var \ILIAS\Refinery\Factory  */
    protected $refinery;

    /** @var LongEssayTaskDI */
    protected $localDI;

    /** @var DataService */
    protected $data;

    /** @var array query params */
    protected $params;

    /**
	 * Constructor
	 * @param \ilObjLongEssayTaskGUI  $objectGUI
	 */
	public function __construct(\ilObjLongEssayTaskGUI $objectGUI)
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
		$this->renderer = LongEssayTaskDI::getInstance()->custom_renderer();
        $this->request = $this->dic->http()->request();
        $this->refinery = $this->dic->refinery();

        // Plugin dependencies
        $this->objectGUI = $objectGUI;
        $this->object = $this->objectGUI->getObject();
		$this->plugin = \ilLongEssayTaskPlugin::getInstance();
        $this->localDI = LongEssayTaskDI::getInstance();
        $this->data = $this->localDI->getDataService($this->object->getId());
        $this->params = $this->request->getQueryParams();
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

    /**
     * Raise a permission error
     * This may be needed if wrong ids for editing records are given
     */
	public function raisePermissionError()
    {
	   \ilUtil::sendFailure($this->lng->txt('permission_denied'), true);
	   $this->ctrl->clearParameters($this->objectGUI);
	   $this->ctrl->redirect($this->objectGUI);
    }
}