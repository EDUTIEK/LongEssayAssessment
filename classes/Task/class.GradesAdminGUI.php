<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\GradeLevel;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
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
			$item_data[] = [
				'id' => $record->getId(),
				'headline' => $record->getGrade(),
				'subheadline' => '',
				'important' => [
					$this->plugin->txt('min_points') => $record->getMinPoints(),
					$record->getGrade()
				],
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


        $ptable = $this->uiFactory->table()->presentation(
            $this->plugin->txt('grade_levels'),
            [],
            function (
                PresentationRow $row,
                array $record,
                Factory $ui_factory,
                $environment) {

				$this->setGradeLevelId($record["id"]);
				$edit_link = $this->ctrl->getLinkTarget($this, "editItem");
				$this->setGradeLevelId($record["id"]);
				$delete_link = $this->ctrl->getLinkTarget($this, "deleteItem");

                return $row
                    ->withHeadline($record['headline'])
                    //->withSubheadline($record['subheadline'])
                    ->withImportantFields($record['important'])
                    ->withContent($ui_factory->listing()->descriptive([$this->lng->txt("description")=> $record['subheadline']]))
                    ->withFurtherFieldsHeadline('')
                    ->withFurtherFields($record['important'])
                    ->withAction(
                        $ui_factory->dropdown()->standard([
                            $ui_factory->button()->shy($this->lng->txt('edit'), $edit_link),
                            $ui_factory->button()->shy($this->lng->txt('delete'), $delete_link)
                            ])
                            ->withLabel($this->lng->txt("actions"))
                    )
                    ;
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

		$sections = [];

		$fields = [];
		$fields['grade'] = $factory->text($this->plugin->txt("grade_level"))
			->withRequired(true)
			->withValue($data["grade"]);

		$fields['points'] =$factory->numeric($this->plugin->txt('min_points'), $this->plugin->txt("min_points_caption"))
			->withRequired(true)
			->withValue($data["points"]);

		$sections['form'] = $factory->section($fields, $section_title);


		return $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this,"updateItem"), $sections);
	}

	protected function updateItem(){
		$form = $this->buildEditForm([
			"grade" => "",
			"points" => 0
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
				$obj_repo  = LongEssayTaskDI::getInstance()->getObjectRepo();

				if($id !== null){
					$obj_repo->updateGradeLevel($record);
				}else {
					$obj_repo->createGradeLevel($record);
				}
				ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
				$this->ctrl->redirect($this, "showItems");
			}else {
				ilUtil::sendFailure($this->lng->txt("validation_error"), false);
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
					"points" => $record->getMinPoints()
				]);
			}else {
				$form = $this->buildEditForm([
					"grade" => "",
					"points" => 0
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