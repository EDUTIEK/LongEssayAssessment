<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\CorrectorAdmin;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
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
        $button->setCaption($this->lng->txt("assign_writers"), false);
        $button->setPrimary(true);
        $this->toolbar->addButtonInstance($button);

		$di = LongEssayTaskDI::getInstance();
		$writers_repo = $di->getWriterRepo();
		$corrector_repo = $di->getCorrectorRepo();
		$items = [];

		$writers = $writers_repo->getWritersByTaskId($this->object->getId());
		$correctors = $corrector_repo->getCorrectorsByTaskId($this->object->getId());
		$assignments = [];

		foreach($corrector_repo->getAssignmentsByTaskId($this->object->getId()) as $assignment) {
			$assignments[$assignment->getWriterId()][$assignment->getPosition()] = &$assignment;
		}

		$user_ids = array_merge(
			array_map(fn ($row) => $row->getUserId(), $writers),
			array_map(fn ($row) => $row->getUserId(), $correctors)
		);

		$users = [];
		foreach(\ilObjUser::_getUserData(array_unique($user_ids)) as $user) {$users[(int)$user["usr_id"]] = &$user;}

		/**
		 * @param Writer $writer
		 */
		foreach($writers as $writer)
		{
			$name = $this->fullname($users[$writer->getUserId()])." (" . $users[$writer->getUserId()]["login"] . ")";
			$firstcorr_name = "-";
			$secondcorr_name = "-";
			$status = "Stichentscheid gefordert";//TODO: determine Status

			if(isset($assignment[$writer->getId()][0])){
				$id = $assignment[$writer->getId()][0]->getCorrectorId();
				$firstcorr_name = $this->fullname($users[$id])." (" . $users[$id]["login"] . ")";
			}

			if(isset($assignment[$writer->getId()][1])){
				$id = $assignment[$writer->getId()][1]->getCorrectorId();
				$secondcorr_name = $this->fullname($users[$id])." (" . $users[$id]["login"] . ")";
			}

			$this->ctrl->setParameter($this, "some_id", "id");
			$view_correction_action = $this->ctrl->getLinkTarget($this, "viewCorrection");
			$this->ctrl->setParameter($this, "writer_id", $writer->getId());
			$change_corrector_action = $this->ctrl->getLinkTarget($this, "changeCorrector");
			$this->ctrl->setParameter($this, "some_id", "id");
			$review_action = $this->ctrl->getLinkTarget($this, "review");

			$items[] = $this->uiFactory->item()->standard($this->uiFactory->link()->standard($name ,''))
				->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'))
				->withProperties(array(
					$this->lng->txt("first_corrector") => $firstcorr_name,
					$this->lng->txt("second_corrector") => $secondcorr_name,
					$this->lng->txt("status") => $status,

				))
				->withActions(
					$this->uiFactory->dropdown()->standard([
						$this->uiFactory->button()->shy($this->lng->txt('view_correction'), $view_correction_action),
						$this->uiFactory->button()->shy($this->lng->txt('change_corrector'), $change_corrector_action),
						$this->uiFactory->button()->shy($this->lng->txt('change_corrector'), $review_action),

					]));
		}

        $actions = array(
            "Alle" => "all",
            "Korrigiert" => "",
            "Noch nicht korrigiert" => "",
            "Stichentscheid gefordert" => "",
        );

        $aria_label = "change_the_currently_displayed_mode";
        $view_control = $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive("Alle");

//        $item1 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Theo Teststudent (theo.teststudent)",''))
//            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'))
//            ->withProperties(array(
//                "Erstkorrektor" => "Volker Reuschenbach",
//                "Zweitkorrektor" => "Matthias Kunkel",
//                "Status" => "Stichentscheid gefordert",
//
//            ))
//            ->withActions(
//                $this->uiFactory->dropdown()->standard([
//                    $this->uiFactory->button()->shy('Korrektur einsehen', '#'),
//                    $this->uiFactory->button()->shy('Korrektorenzuweisung ändern', '#'),
//                    $this->uiFactory->button()->shy('Stichentscheid', '#'),
//
//                ]));
//
//        $item2 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Thekla Teststudentin (thekla.teststudentin)", ''))
//            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'editor', 'medium'))
//            ->withProperties(array(
//                "Erstkorrektor" => "Matthias Kunkel",
//                "Zweitkorrektor" => "Volker Reuschenbach",
//                "Status" => "noch nicht korrigiert",
//
//            ))
//            ->withActions(
//                $this->uiFactory->dropdown()->standard([
//                    $this->uiFactory->button()->shy('Korrektur einsehen', '#'),
//                    $this->uiFactory->button()->shy('Korrektorenzuweung ändern', '#'),
//                ]));
//
//		$items[] = $item1; $items[] = $item2;

        $resources = $this->uiFactory->item()->group($this->lng->txt("correctable_exams"), $items);

        $this->tpl->setContent(

            $this->renderer->render($view_control) . '<br><br>' .
            $this->renderer->render($resources)

        );
	}

	protected function assignWriters(){
		$this->service->assignMissingCorrectors();
		ilUtil::sendSuccess($this->lng->txt("assigned_writers"), true);
		$this->ctrl->redirect($this, "showStartPage");
	}

	protected function changeCorrector(){
		$this->tpl->setContent("Empty!");
	}

	private function fullname($user): string
	{
		$fullname = "";

		 if ($user["title"]) {
			 $fullname = $user["title"] . " ";
		 }
		 if ($user["firstname"]) {
			 $fullname .= $user["firstname"] . " ";
		 }
		 if ($user["lastname"]) {
			 $fullname .= $user["lastname"];
		 }
		 return $fullname;
	}
}