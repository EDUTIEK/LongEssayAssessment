<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\RatingCriterion;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\UI\Factory;
use \ilUtil;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\CriteriaAdminGUI: ilObjLongEssayAssessmentGUI
 */
class CriteriaAdminGUI extends BaseGUI
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
            case 'showItems':
            case 'editItem':
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
		$creteria = $this->localDI->getObjectRepo()->getRatingCriteriaByObjectId($this->object->getId());
		$items = [];

		foreach($creteria as $obj){
			$items[] = [
				'id' => $obj->getId(),
				'headline' => $obj->getTitle(),
				'subheadline' => $obj->getDescription(),
				'important' => [
					$this->plugin->txt("criteria_max_point") => $obj->getPoints()
					]
				];
		}

        return $items;
    }

    /**
     * Show the items
     */
    protected function showItems()
    {
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
        $button = \ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, 'editItem'));
        $button->setCaption($this->plugin->txt("criteria_add"), false);
        $this->toolbar->addButtonInstance($button);

        $ptable = $this->uiFactory->table()->presentation(
            $this->plugin->txt("criteria"),
            [],
            function (
                PresentationRow $row,
                array $record,
                Factory $ui_factory,
                $environment) {
				$this->ctrl->setParameter($this, 'criterion_id', $record['id']);
				$edit_target = $this->ctrl->getLinkTarget($this, 'editItem');
				$this->ctrl->setParameter($this, 'criterion_id', $record['id']);
				$delete_target = $this->ctrl->getLinkTarget($this, 'deleteItem');

				$approve_modal = $ui_factory->modal()->interruptive(
					$this->plugin->txt("delete_criteria"),
					$this->plugin->txt("delete_criteria_confirmation"),
					$delete_target
				)->withAffectedItems([
					$ui_factory->modal()->interruptiveItem($record["id"], $record['headline'])
				]);

                return $row
                    ->withHeadline($record['headline'] . $this->renderer->render($approve_modal))
                    ->withImportantFields($record['important'])
                    ->withContent($ui_factory->listing()->descriptive([$this->lng->txt("description") => $record['subheadline']]))
                    ->withFurtherFieldsHeadline('')
                    ->withFurtherFields($record['important'])
                    ->withAction(
                        $ui_factory->dropdown()->standard([
                            $ui_factory->button()->shy($this->lng->txt('edit'), $edit_target),
                            $ui_factory->button()->shy($this->lng->txt('delete'), '')
								->withOnClick($approve_modal->getShowSignal())
                            ])
                            ->withLabel($this->lng->txt("actions"))
                    )
                    ;
            }
        );

        $this->tpl->setContent($this->renderer->render($ptable->withData($this->getItemData())));
    }


    /**
     * Edit and save the settings
     */
    protected function editItem()
    {
		$object_repo = $this->localDI->getObjectRepo();

        if (($id = $this->getRatingCriterionId()) !== null) {
            $record = $object_repo->getRatingCriterionById($id);
            $this->checkRecordInObject($record);
			$this->setRatingCriterionId($id);
            $section_title = $this->plugin->txt('criteria_edit');
        }
        else {
			$record = new RatingCriterion();
			$record->setId(0);
            $record->setObjectId($this->object->getId());
            $section_title = $this->plugin->txt('criteria_add');
        }

        $factory = $this->uiFactory->input()->field();
		$custom_factory = $this->localDI->getUIFactory();
		$custom_renderer = $this->localDI->getUIRenderer();
        $sections = [];

        $fields = [];
        $fields['title'] = $factory->text($this->lng->txt("title"))
			->withAdditionalTransformation($this->refinery->string()->hasMinLength(1))
            ->withRequired(true)
            ->withValue($record->getTitle());

        $fields['description'] = $factory->textarea($this->lng->txt("description"))
            ->withValue($record->getDescription() !== null ? $record->getDescription(): "");

        $fields['points'] = $custom_factory->field()->numeric($this->plugin->txt('criteria_max_point'), $this->plugin->txt('criteria_max_point_desc'))
			->withAdditionalTransformation($this->refinery->kindlyTo()->int())
			->withAdditionalTransformation($this->refinery->int()->isGreaterThan(0))
			->withRequired(true)
            ->withValue($record->getPoints());

        $sections['form'] = $factory->section($fields, $section_title);

        $form = $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this, "editItem"), $sections);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
        }

        // inputs are ok => save data
        if (isset($data)) {
            $record->setTitle($data['form']['title']);
			$record->setDescription($data['form']['description']);
			$record->setPoints($data['form']['points']);
            $object_repo->save($record);

            ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);

            $this->ctrl->redirect($this, "showItems");
        }

        $this->tpl->setContent($custom_renderer->render($form, $this->renderer));
    }

	protected function deleteItem(){
		if (($id = $this->getRatingCriterionId()) !== null) {
			$object_repo = $this->localDI->getObjectRepo();
			$record = $object_repo->getRatingCriterionById($id);
			$this->checkRecordInObject($record);
			$object_repo->deleteRatingCriterion($id);
			ilUtil::sendSuccess($this->plugin->txt("delete_criteria_successful"), true);
		}else{
			ilUtil::sendFailure($this->plugin->txt("delete_criteria_failure"), true);
		}
		$this->ctrl->redirect($this, "showItems");
	}

	/**
	 * @param RatingCriterion|null $record
	 * @param bool $throw_permission_error
	 * @return bool
	 */
	protected function checkRecordInObject(?RatingCriterion $record, bool $throw_permission_error = true): bool
	{
		if($record !== null && $this->object->getId() === $record->getObjectId()){
			return true;
		}

		if($throw_permission_error) {
			$this->raisePermissionError();
		}
		return false;
	}

	protected function setRatingCriterionId(int $id)
	{
		$this->ctrl->setParameter($this, "criterion_id", $id);
	}

	protected function getRatingCriterionId(): ?int
	{
		$params = $this->request->getQueryParams();

		if (isset($params["criterion_id"]))
		{
			return (int) $params["criterion_id"];
		}
		else{
			return null;
		}
	}
}