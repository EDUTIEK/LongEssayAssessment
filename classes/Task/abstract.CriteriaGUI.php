<?php

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\RatingCriterion;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\Factory as CustomFactory;
use ILIAS\UI\Implementation\Component\Signal;

abstract class CriteriaGUI extends BaseGUI
{
	private CustomFactory $custom_factory;
	private ObjectRepository $object_repo;
	private CorrectorRepository $corrector_repo;

	public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
	{
		parent::__construct($objectGUI);

		$this->custom_factory = $this->localDI->getUIFactory();
		$this->object_repo = $this->localDI->getObjectRepo();
		$this->corrector_repo = $this->localDI->getCorrectorRepo();
	}

	public function executeCommand()
	{
		$cmd = $this->ctrl->getCmd('showItems');
		switch ($cmd)
		{
			case 'showItems':
			case 'saveItemAsync':
			case 'deleteItems':
				$this->$cmd();
				break;
			case 'copyItems':
			case 'publishRatingCriterion':
			case 'previewItemsAsync':
				if($this->allowCopyInContext())
					$this->$cmd();
				else
					$this->tpl->setContent('not allowed command: ' . $cmd);
				break;
			default:
				$this->tpl->setContent('unknown command: ' . $cmd);
		}
	}

	/**
	 * @return RatingCriterion[]
	 */
	abstract protected function getRatingCriterionFromContext():array;
	abstract protected function getRatingCriterionModelFromContext(): RatingCriterion;
	abstract protected function getCorrectorIdFromContext(): ?int;
	abstract protected function allowCopyInContext(): bool;

	public function showItems()
	{
		$criteria = $this->getRatingCriterionFromContext();
		$create_modal = $this->uiFactory->modal()->roundtrip("", [])->withAsyncRenderUrl(
			$this->ctrl->getLinkTarget($this, $this->ctrl->getLinkTarget($this, "saveItemAsync"))
		);
		$modals = [$create_modal];

		$this->toolbar->addComponent(
			$this->uiFactory->button()->primary($this->plugin->txt("criteria_add"), "")
			->withOnClick($create_modal->getShowSignal())
		);

		$this->addCopyToolbar();
		$items = [];

		foreach ($criteria as $item){
			$this->ctrl->setParameter($this, "criterion_id", $item->getId());
			$modals[] = $edit_modal = $this->uiFactory->modal()->roundtrip("", [])
				->withAsyncRenderUrl($this->ctrl->getLinkTarget($this, "saveItemAsync"));

			$items[] = $this->custom_factory->item()->formItem($this->buildItemTitle($item))
				->withName($item->getId())
				->withNoLead()
				->withDescription(nl2br($item->getDescription()))
				->withActions($this->uiFactory->dropdown()->standard([
					$this->uiFactory->button()->shy($this->lng->txt("edit"), "")->withOnClick($edit_modal->getShowSignal()),
					$this->uiFactory->button()->shy($this->lng->txt("remove"), $this->ctrl->getLinkTarget($this, "deleteItems"))
				]));
		}
		$this->ctrl->clearParameters($this);

		$form_group = $this->custom_factory->item()->formGroup($this->plugin->txt("criteria"), $items,
			$this->ctrl->getLinkTarget($this, "deleteItems"))->withActionLabel($this->lng->txt('remove'));
		$this->tpl->setContent($this->renderer->render(array_merge([$form_group], $modals)));
	}

	protected function buildItemTitle(RatingCriterion $item): string
	{
		return $item->getTitle() . " | " . $this->plugin->txt("criteria_max_point") . ": " . $item->getPoints();
	}

	public function saveItemAsync()
	{
		$criteria_ids = array_map(fn (RatingCriterion $x) => $x->getId(), $this->getRatingCriterionFromContext());

		if (($id = $this->getRatingCriterionId()) !== null && in_array($id, $criteria_ids)) {
			$this->ctrl->saveParameter($this, "criterion_id");
			$item = $this->object_repo->getRatingCriterionById($id);
			$title = $this->plugin->txt('criteria_edit');
		}
		else {
			$item = $this->getRatingCriterionModelFromContext();
			$title = $this->plugin->txt('criteria_add');
		}
		$form = $this->buildItemForm($item);

		if($this->request->getMethod() === "POST"){
			$form = $form->withRequest($this->request);

			if(!empty($data = $form->getData())){
				$item->setTitle($data['title']);
				$item->setDescription($data['description']);
				$item->setPoints($data['points']);
				$this->object_repo->save($item);

				\ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
				exit();
			}else {
				echo($this->renderer->render($form));
				exit();
			}
		}
		$modal = $this->uiFactory->modal()->roundtrip($title, $form)->withActionButtons([
			$this->uiFactory->button()->primary($this->lng->txt('submit'), "")->withOnClick($form->getSubmitAsyncSignal())
		]);
		echo($this->renderer->renderAsync($modal));
		exit();
	}

	protected function buildItemForm(RatingCriterion $item): BlankForm
	{
		$fields = [
			'title' =>  $this->uiFactory->input()->field()->text($this->lng->txt("title"))
				->withAdditionalTransformation($this->refinery->string()->hasMinLength(1))
				->withRequired(true)
				->withValue($item->getTitle()),
			'description' =>  $this->uiFactory->input()->field()->textarea($this->lng->txt("description"))
				->withValue($item->getDescription() !== null ? $item->getDescription(): ""),
			'points' => $this->custom_factory->field()->numeric($this->plugin->txt('criteria_max_point'),
				$this->plugin->txt('criteria_max_point_desc'))
				->withAdditionalTransformation($this->refinery->kindlyTo()->int())
				->withAdditionalTransformation($this->refinery->int()->isGreaterThan(0))
				->withRequired(true)
				->withValue($item->getPoints())
		];

		return $this->custom_factory->field()->blankForm($this->ctrl->getFormAction($this, "saveItemAsync"), $fields);
	}


	public function deleteItems(){
		$criteria_ids = array_map(fn (RatingCriterion $x) => $x->getId(), $this->getRatingCriterionFromContext());
		$delete_ids = $this->getRatingCriterionIds();
		$success = false;

		if (!empty($delete_ids) !== null ) {
			foreach ($delete_ids as $id) {
				if (in_array($id, $criteria_ids)) {
					$this->object_repo->deleteRatingCriterion($id);
					$success = true;
				}
			}
		}

		if($success)
		{
			\ilUtil::sendSuccess($this->plugin->txt("delete_criteria_successful"), true);
		}else{
			\ilUtil::sendFailure($this->plugin->txt("delete_criteria_failure"), true);
		}
		$this->ctrl->clearParameters($this);
		$this->ctrl->redirect($this, "showItems");
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

	/**
	 * @return int[]
	 */
	protected function getRatingCriterionIds(): array
	{
		$ids = [];
		$query_params = $this->request->getQueryParams();
		$post = $this->request->getParsedBody();
		if(isset($post["cb"])){
			$ids = array_map(fn($x) => (int)$x, $post["cb"]);
		}else if(isset($query_params["criterion_id"]) && $query_params["criterion_id"] !== ""){
			$ids[] = (int) $query_params["criterion_id"];
		}elseif (isset($query_params["criterion_ids"])){
			foreach(explode('/', $query_params["criterion_ids"]) as $value){
				$ids[] = (int) $value;
			}
		}
		return $ids;
	}

	public function publishRatingCriterion()
	{
		$param = $this->request->getQueryParams();
		if(isset($param["publish"]) && $this->allowCopyInContext() && $this->getCorrectorIdFromContext() !== null)
		{
			$toggle = $param["publish"] == "on";
			$corrector = $this->corrector_repo->getCorrectorById($this->getCorrectorIdFromContext());

			$corrector->setCriterionCopyEnabled($toggle);
			$this->corrector_repo->save($corrector);

			if($toggle){
				\ilUtil::sendSuccess($this->plugin->txt("criteria_publish_enabled"), true);
			}else{
				\ilUtil::sendSuccess($this->plugin->txt("criteria_publish_disabled"), true);
			}
		}
		$this->ctrl->clearParameters($this);
		$this->ctrl->redirect($this, "showItems");
	}

	public function previewItemsAsync()
	{
		$query = $this->request->getQueryParams();
		$content = [];
		$title = "not found";
		$allowed_corrector = array_map(fn ($x) => $x['corrector_id'], $this->object_repo->getRatingCriterionGroupForCopy($this->object->getId()));
		$allowed_corrector[] = null;

		if(isset($query["criteria_group"]) && $this->getCorrectorIdFromContext() !== null) {
			if ($query["criteria_group"] == "group_-1") {
				$from_corrector_id = null;
			} else {
				$from_corrector_id =  (int)str_replace("group_", "", $query["criteria_group"]);
			}

			if (in_array($from_corrector_id, $allowed_corrector)){
				$group = $this->object_repo->getRatingCriteriaByObjectId($this->object->getId(), $from_corrector_id);
				$items = [];
				foreach ($group as $criterion){
					$items[] = $this->uiFactory->item()->standard($this->buildItemTitle($criterion))
						->withDescription(nl2br($criterion->getDescription()));
				}
				if($from_corrector_id !== null) {
					$corrector = $this->corrector_repo->getCorrectorById($from_corrector_id);
					$names = \ilUserUtil::getNamePresentation([$corrector->getUserId()], false, false, "", true);
					$title = sprintf($this->plugin->txt('criteria_from'), $names[$corrector->getUserId()]);
				}else{
					$title = $this->plugin->txt('criteria_template');
				}

				$content[] = $this->uiFactory->item()->group("", $items);
			}
		}
		$modal = $this->uiFactory->modal()->roundtrip($title,$content);
		echo($this->renderer->renderAsync($modal));
		exit();
	}

	public function copyItems(){
		$query = $this->request->getQueryParams();
		$allowed_corrector = array_map(fn ($x) => $x['corrector_id'], $this->object_repo->getRatingCriterionGroupForCopy($this->object->getId()));
		$allowed_corrector[] = null;
		$success = false;

		if(isset($query["criteria_group"]) && $this->getCorrectorIdFromContext() !== null) {
			if ($query["criteria_group"] == "group_-1") {
				$from_corrector_id = null;
			} else {
				$from_corrector_id = (int)str_replace("group_", "", $query["criteria_group"]);
			}

			if (in_array($from_corrector_id, $allowed_corrector)){
				$to_corrector_id = $this->getCorrectorIdFromContext();

				$group = $this->object_repo->getRatingCriteriaByObjectId($this->object->getId(), $from_corrector_id);
				$this->object_repo->deleteRatingCriterionByObjectIdAndCorrectorId($this->object->getId(), $to_corrector_id);

				foreach ($group as $criterion) {
					$new = clone $criterion;
					$new->setId(0);
					$new->setCorrectorId($to_corrector_id);
					$this->object_repo->save($new);
				}
				$success = true;
			}
		}

		if($success){
			\ilUtil::sendSuccess($this->plugin->txt("copy_criteria_successful"), true);
		}else{
			\ilUtil::sendFailure($this->plugin->txt("copy_criteria_failure"), true);
		}
		$this->ctrl->clearParameters($this);
		$this->ctrl->redirect($this, "showItems");
	}

	protected function addCopyToolbar()
	{
		if($this->allowCopyInContext() && $this->getCorrectorIdFromContext() !== null){
			$corrector = $this->corrector_repo->getCorrectorById($this->getCorrectorIdFromContext());
			$select = $this->copyGroupSelect();
			if(!empty($select->getOptions())){
				$modal = $this->uiFactory->modal()->roundtrip("", [])->withAsyncRenderUrl("#");
				$signal = new Signal(str_replace(".", "_", uniqid('il_signal_', true)));
				$preview_link = $this->ctrl->getLinkTarget($this, "previewItemsAsync", "", true);

				$modal = $modal->withOnLoadCode(function ($id) use($signal, $preview_link){
					return "$(document).on('{$signal}', function(event, signalData) {
					 	il.UI.modal.showModal(
							'{$id}', 
							{'url': '#{$id}', 'ajaxRenderUrl': '$preview_link&criteria_group=' + $('#criteria_group').val(), 'keyboard': true},
							signalData
						); 
						return false;
					 });";
				});

				$copy_action = $this->ctrl->getFormAction($this, "copyItems");

				$this->toolbar->addComponent($modal);
				$this->toolbar->addSeparator();
				$this->toolbar->addText($this->plugin->txt('copy_rating_criterion_from'));
				$this->toolbar->addInputItem($select);
				$this->toolbar->addComponent($this->uiFactory->button()->standard($this->lng->txt('copy'), "")
					->withOnLoadCode(function ($id) use ($copy_action){
						return "$('#$id').on( 'click', function() {
  							location.href='$copy_action&criteria_group=' + $('#criteria_group').val();
} 						);";
					})
				);
				$this->toolbar->addComponent($this->uiFactory->button()->standard($this->lng->txt('preview'), "#")->withOnClick($signal)
				);
			}
			$this->ctrl->setParameter($this, "publish", "on");
			$on_action = $this->ctrl->getFormAction($this, "publishRatingCriterion");

			$this->ctrl->setParameter($this, "publish", "off");
			$off_action = $this->ctrl->getFormAction($this, "publishRatingCriterion");

			$this->ctrl->clearParameters($this);
			$this->toolbar->addSeparator();
			$this->toolbar->addText($this->plugin->txt("publish_rating_criterion"));
			$this->toolbar->addComponent($this->uiFactory->button()->toggle("", $on_action, $off_action, $corrector->isCriterionCopyEnabled())
			);
		}
	}

	protected function copyGroupSelect()
	{
		$group = $this->object_repo->getRatingCriterionGroupForCopy($this->object->getId());
		$global = $this->object_repo->getRatingCriteriaByObjectId($this->object->getId());
		$corrector_id = $this->getCorrectorIdFromContext();

		$items = [];
		$names = \ilUserUtil::getNamePresentation(
			array_unique(array_map(fn ($x) => $x["usr_id"], $group)),
			false, false, "", true);

		foreach ($group as $item){
			if($item["corrector_id"] == $corrector_id){
				continue;
			} else if(isset($names[$item["usr_id"]])){
				$name = $names[$item["usr_id"]];
			}else{
				continue;
			}
			$items["group_" . $item["corrector_id"]] = $name;
		}
		if(!empty($global)){
			$items = array_merge(["group_-1" => $this->plugin->txt('criteria_template')], $items);
		}
		$select = new \ilSelectInputGUI("", "criteria_group");
		$select->setOptions($items);
		return $select;
	}

}