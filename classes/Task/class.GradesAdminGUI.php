<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\GradeLevel;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use ILIAS\Plugin\LongEssayTask\UI\Implementation\Numeric;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\UI\Factory;
use \ilUtil;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayTask\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\Task\GradesAdminGUI: ilObjLongEssayTaskGUI
 */
class GradesAdminGUI extends BaseGUI
{
    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showItems');
        switch ($cmd)
        {
			case 'updateItem':
            case 'showItems':
            case "editItem":
			case 'deleteItem':
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * Get the Table Data
     */
    protected function getItemData()
    {
		$obj_repo  = LongEssayTaskDI::getInstance()->getObjectRepo();
		$records = $obj_repo->getGradeLevelsByObjectId($this->object->getId());
		$item_data = [];

		foreach($records as $record){

			$important = [
				$this->plugin->txt('min_points').":" => $record->getMinPoints(),
				$this->plugin->txt('passed').":" => $record->isPassed() ? $this->lng->txt('yes') : $this->lng->txt('no')
			];

			if($record->getCode() !== null && $record->getCode() !== ""){
				$important[$this->plugin->txt('grade_level_code')] = $record->getCode();
			}

			$item_data[] = [
				'id' => $record->getId(),
				'headline' => $record->getGrade(),
				'subheadline' => '',
				'important' => $important,
			];
		}

        return $item_data;
    }

    /**
     * Show the items
     */
    protected function showItems()
    {
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
        $button = \ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, 'editItem'));
        $button->setCaption($this->plugin->txt("add_grade_level"), false);
        $this->toolbar->addButtonInstance($button);

		$can_delete = true;
		$task_repo = LongEssayTaskDI::getInstance()->getTaskRepo();
		$settings = $task_repo->getTaskSettingsById($this->object->getId());

		if($settings->getCorrectionStart() !== null) {
			$correction_start = new \ilDateTime($settings->getCorrectionStart(), IL_CAL_DATETIME);

			$today = new \ilDateTime(time(), IL_CAL_UNIX);

			$can_delete = !\ilDate::_after($today, $correction_start);
		}



        $ptable = $this->uiFactory->table()->presentation(
            $this->plugin->txt('grade_levels'),
            [],
            function (
                PresentationRow $row,
                array $record,
                Factory $ui_factory,
                $environment) use ($can_delete)  {

				$this->setGradeLevelId($record["id"]);
				$edit_link = $this->ctrl->getLinkTarget($this, "editItem");
				$this->setGradeLevelId($record["id"]);
				$delete_link = $this->ctrl->getLinkTarget($this, "deleteItem");

				$approve_modal = $ui_factory->modal()->interruptive(
					$this->plugin->txt("delete_grade_level"),
					$this->plugin->txt("delete_grade_level_confirmation"),
					$delete_link
				)->withAffectedItems([
					$ui_factory->modal()->interruptiveItem($record["id"], $record['headline'])
				]);

				if($can_delete){
					$action = $ui_factory->dropdown()->standard([
						$ui_factory->button()->shy($this->lng->txt('edit'), $edit_link),
						$ui_factory->button()->shy($this->lng->txt('delete'), '')
							->withOnClick($approve_modal->getShowSignal())
					])->withLabel($this->lng->txt("actions"));
				}else
				{
					$action = $ui_factory->button()->standard($this->lng->txt('edit'), $edit_link);
				}

                return $row
                    ->withHeadline($record['headline']. $this->renderer->render($approve_modal))
                    //->withSubheadline($record['subheadline'])
                    ->withImportantFields($record['important'])
                    ->withContent($ui_factory->listing()->descriptive([$this->lng->txt("description")=> $record['subheadline']]))
                    ->withFurtherFieldsHeadline('')
                    ->withFurtherFields($record['important'])
                    ->withAction($action);
            }
        );

        $this->tpl->setContent($this->renderer->render($ptable->withData($this->getItemData())));
    }

	protected function buildEditForm($data):\ILIAS\UI\Component\Input\Container\Form\Standard{
		if($id = $this->getGradeLevelId()){
			$section_title = $this->plugin->txt('edit_grade_level');
			$this->setGradeLevelId($id);
		}
		else {
			$section_title = $this->plugin->txt('add_grade_level');
		}

		$factory = $this->uiFactory->input()->field();
		$custom_factory = LongEssayTaskDI::getInstance()->custom_factory();
		$sections = [];

		$fields = [];
		$fields['grade'] = $factory->text($this->plugin->txt("grade_level"))
			->withRequired(true)
			->withValue($data["grade"]);

		$fields['code'] = $factory->text($this->plugin->txt("grade_level_code"), $this->plugin->txt("grade_level_code_caption"))
			->withRequired(false)
			->withValue($data["code"]!== null ? $data["code"] : "");

		$fields['points'] = $custom_factory->numeric($this->plugin->txt('min_points'), $this->plugin->txt("min_points_caption"))
			->withStep(0.01)
			->withRequired(true)
			->withValue((float)$data["points"]);

		$fields['passed'] =$factory->checkbox($this->plugin->txt('passed'), $this->plugin->txt("passed_caption"))
			->withRequired(true)
			->withValue($data["passed"]);

		$sections['form'] = $factory->section($fields, $section_title);


		return $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this,"updateItem"), $sections);
	}

	protected function updateItem(){
		$form = $this->buildEditForm([
			"grade" => "",
			"points" => 0,
			"code" => "",
			"passed" => false
		]);

		if ($this->request->getMethod() == "POST") {
			$form = $form->withRequest($this->request);
			$data = $form->getData();

			if($id = $this->getGradeLevelId()){
				$record = $this->getGradeLevel($id);
			}else {
				$record = new GradeLevel();
				$record->setObjectId($this->object->getId());
			}

			// inputs are ok => save data
			if (isset($data)) {
				$record->setGrade($data["form"]["grade"]);
				$record->setMinPoints($data["form"]["points"]);
				$record->setCode($data["form"]["code"]);
				$record->setPassed($data["form"]["passed"]);
				$obj_repo  = LongEssayTaskDI::getInstance()->getObjectRepo();

				if($id !== null){
					$obj_repo->updateGradeLevel($record);
				}else {
					$obj_repo->createGradeLevel($record);
				}
				ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
				$this->ctrl->redirect($this, "showItems");
			}else {
				// ilUtil::sendFailure($this->lng->txt("validation_error"), false);
				$this->editItem($form);
			}
		}
	}


    /**
     * Edit and save the settings
     */
    protected function editItem($form = null)
    {
		if($form === null){
			if ($id = $this->getGradeLevelId())
			{
				$record = $this->getGradeLevel($id);
				$form = $this->buildEditForm([
					"grade" => $record->getGrade(),
					"points" => $record->getMinPoints(),
					"code" => $record->getCode(),
					"passed" => $record->isPassed()
				]);
			}else {
				$form = $this->buildEditForm([
					"grade" => "",
					"points" => 0,
					"code" => "",
					"passed" => false
				]);
			}
		}

        $this->tpl->setContent($this->renderer->render($form));
    }

	protected function deleteItem(){
		// TODO: Zwischenfrage hinzufügen!
		if(($id = $this->getGradeLevelId()) !== null){
			$this->getGradeLevel($id, true);//Permission check
			$obj_repo  = LongEssayTaskDI::getInstance()->getObjectRepo();
			$obj_repo->deleteGradeLevel($id);
			ilUtil::sendSuccess($this->plugin->txt("delete_grade_level_successful"), true);
		}else{
			ilUtil::sendFailure($this->plugin->txt("delete_grade_level_failure"), true);
		}
		$this->ctrl->redirect($this, "showItems");
	}

	protected function checkRecordInObject(?GradeLevel $record, bool $throw_permission_error = true): bool
	{
		if($record !== null && $this->object->getId() === $record->getObjectId()){
			return true;
		}

		if($throw_permission_error) {
			$this->raisePermissionError();
		}
		return false;
	}

	protected function getGradeLevel(int $id, bool $throw_permission_error = true): ?GradeLevel
	{
		$obj_repo  = LongEssayTaskDI::getInstance()->getObjectRepo();
		$record = $obj_repo->getGradeLevelById($id);
		if($throw_permission_error){
			$this->checkRecordInObject($record, true);
		}
		return $record;
	}

	protected function setGradeLevelId(int $id)
	{
		$this->ctrl->setParameter($this, "grade_level", $id);
	}

	protected function getGradeLevelId(): ?int
	{
		if (isset($_GET["grade_level"]))
		{
			return (int) $_GET["grade_level"];
		}
		else{
			return null;
		}
	}
}