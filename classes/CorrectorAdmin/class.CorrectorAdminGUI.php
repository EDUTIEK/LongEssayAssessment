<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\CorrectorAdmin;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use ILIAS\Plugin\LongEssayTask\WriterAdmin\CorrectorAdminListGUI;
use ILIAS\UI\Factory;
use \ilUtil;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayTask\CorrectorAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\CorrectorAdmin\CorrectorAdminGUI: ilObjLongEssayTaskGUI
 */
class CorrectorAdminGUI extends BaseGUI
{

	/** @var CorrectorAdminService */
	protected $service;

	public function __construct(\ilObjLongEssayTaskGUI $objectGUI)
	{
		parent::__construct($objectGUI);
		$this->service = $this->object->getCorrectorAdminService();
	}

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showStartPage');
        switch ($cmd)
        {
            case 'showStartPage':
			case 'assignWriters':
			case 'changeCorrector':
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }


    /**
     * Show the items
     */
    protected function showStartPage()
    {
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
		$assign_writers_action = $this->ctrl->getLinkTarget($this, "assignWriters");

        $button = \ilLinkButton::getInstance();
        $button->setUrl($assign_writers_action);
        $button->setCaption($this->plugin->txt("assign_writers"), false);
        $button->setPrimary(true);
        $this->toolbar->addButtonInstance($button);

		$di = LongEssayTaskDI::getInstance();
		$writers_repo = $di->getWriterRepo();
		$corrector_repo = $di->getCorrectorRepo();
		$essay_repo = $di->getEssayRepo();

		$list_gui = new CorrectorAdminListGUI($this, $this->plugin);
		$list_gui->setWriters($writers_repo->getWritersByTaskId($this->object->getId()));
		$list_gui->setCorrectors($corrector_repo->getCorrectorsByTaskId($this->object->getId()));
		$list_gui->setEssays($essay_repo->getEssaysByTaskId($this->object->getId()));
		$list_gui->setAssignments($corrector_repo->getAssignmentsByTaskId($this->object->getId()));
		$list_gui->loadUserData();

        $this->tpl->setContent($list_gui->getContent());
	}

	protected function assignWriters(){
		$this->service->assignMissingCorrectors();
		ilUtil::sendSuccess($this->plugin->txt("assigned_writers"), true);
		$this->ctrl->redirect($this, "showStartPage");
	}

	protected function changeCorrector(){
		$this->tpl->setContent("Empty!");
	}

}