<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Corrector;

use Edutiek\LongEssayService\Corrector\Service;
use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayTask\Data\CorrectionSettings;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorAssignment;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorRepository;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorSummary;
use ILIAS\Plugin\LongEssayTask\Data\DataService;
use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use ILIAS\UI\Component\Button\Shy;
use ILIAS\UI\Component\Item\Standard;
use ILIAS\UI\Component\Link\Link;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Factory;
use \ilUtil;
use Sabre\CalDAV\Notifications\Plugin;

/**
 *Start page for correctors
 *
 * @package ILIAS\Plugin\LongEssayTask\Writer
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\Corrector\CorrectorStartGUI: ilObjLongEssayTaskGUI
 */
class CorrectorStartGUI extends BaseGUI
{
    /** @var CorrectorAdminService */
    protected $service;

    /** @var CorrectionSettings  */
    protected $settings;

	/** @var CorrectorRepository */
	protected CorrectorRepository $correctorRepo;
	private bool $can_correct;

	private int $ready_items = 0;


	public function __construct(\ilObjLongEssayTaskGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->settings = $this->service->getSettings();
		$this->correctorRepo = $this->localDI->getCorrectorRepo();
		$this->can_correct =  $this->object->canCorrect();;
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
            case 'startCorrector':
            case 'finalizeCorrection':
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

	/**
	 * Fetches all possible corrections (unfiltered)
	 * @return array
	 */
	protected function getItems(){
		$corrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->settings->getTaskId());
		foreach ($this->localDI->getCorrectorRepo()->getAssignmentsByCorrectorId($corrector->getId()) as $assignment) {
			$writer = $this->localDI->getWriterRepo()->getWriterById($assignment->getWriterId());
			$essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($assignment->getWriterId(), $this->settings->getTaskId());

			if (empty($essay)) {
				continue;
			}

			$summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId(
				(isset($essay) ? $essay->getId() : 0), $corrector->getId());

			$properties = [
				$this->plugin->txt('writing_status') => $this->data->formatWritingStatus($essay),
				$this->plugin->txt('correction_status') => $this->data->formatCorrectionStatus($essay),
				$this->plugin->txt('own_grading') => $this->data->formatCorrectionResult($summary),
				$this->plugin->txt('result') => $this->data->formatFinalResult($essay)
			];
			foreach ($this->localDI->getCorrectorRepo()->getAssignmentsByWriterId($assignment->getWriterId()) as $otherAssignment) {
                if ($otherAssignment->getCorrectorId() != $corrector->getId()) {
                    $properties[$this->data->formatCorrectorPosition($otherAssignment)] = $this->data->formatCorrectorAssignment($otherAssignment);
                }
            }

			$title = $writer->getPseudonym();
			                if ($this->can_correct && $this->service->isCorrectionPossible($essay, $summary)) {
				$this->ready_items++;
				$this->ctrl->setParameter($this, 'writer_id', $assignment->getWriterId());
				$title = $this->uiFactory->link()->standard($title, $this->ctrl->getLinkTarget($this, 'startCorrector'));
			}

			$items[] = [
				"title" => $title,
				"properties" => $properties,
				"position" => $assignment->getPosition(),
				"pseudonym" => $writer->getPseudonym(),
				"correction_status" => $this->data->getOwnCorrectionStatus($essay, $summary)
			];
		}
		return $items;
	}

	/**
	 * Build filter view control
	 *
	 * @return void
	 */
	protected function filterViewControl()
	{
		$user_id = $this->dic->user()->getId();
		$fcorr = $this->data->getCorrectionStatusFilter($user_id);
		$fpos = $this->data->getCorrectorPositionFilter($user_id);

		$ctrl = $this->ctrl;

		$correction_actions = [
			DataService::ALL => $this->lng->txt("all"),
			CorrectorSummary::STATUS_DUE => $this->plugin->txt('correction_filter_not_started'),
			CorrectorSummary::STATUS_STARTED => $this->plugin->txt('correction_filter_started'),
			CorrectorSummary::STATUS_AUTHORIZED => $this->plugin->txt('correction_filter_authorized'),
			CorrectorSummary::STATUS_STITCH => $this->plugin->txt('correction_filter_stitch'),
		];

		$correction_aria_label = "change_the_currently_displayed_mode";
		$view_control_correction = $this->uiFactory->viewControl()->mode($this->prepareActionList($correction_actions, "fcorr"), $correction_aria_label)
			->withActive($correction_actions[$fcorr]);
		$ctrl->setParameter($this, "fcorr", $fcorr);//Reset ctrl saved parameter

		$position_aria_label = "change_the_currently_displayed_mode";
		$position_actions = [
			DataService::ALL => $this->lng->txt("all"),
			"1" => $this->plugin->txt('assignment_pos_first'),
			"2" => $this->plugin->txt('assignment_pos_second'),
		];
		$view_control_position = $this->uiFactory->viewControl()->mode($this->prepareActionList($position_actions, "fpos"), $position_aria_label)
			->withActive($position_actions[$fpos]);
		$ctrl->setParameter($this, "fpos", $fpos);//Reset ctrl saved parameter
		$this->toolbar->addText($this->plugin->txt("own_correction") . ":");
		$this->toolbar->addComponent($view_control_correction);
		$this->toolbar->addSeparator();
		$this->toolbar->addText($this->plugin->txt("own_position") . ":");
		$this->toolbar->addComponent($view_control_position);
		$this->toolbar->addSeparator();
	}

	protected function prepareActionList ($actions, $type) : array{
		$ret = [];
		foreach($actions as $key => $value){
			$this->ctrl->setParameter($this, $type, $key);
			$action = $this->ctrl->getLinkTarget($this);
			$ret[$value] = $action;
		}
		return $ret;
	}

	/**
	 * Save filter params from URL
	 * @return void
	 */
	protected function saveFilterParams(){
		$user_id = $this->dic->user()->getId();
		$this->ctrl->saveParameter($this, "fcorr");
		$this->ctrl->saveParameter($this, "fpos");
		$fcorr = $this->data->getCorrectionStatusFilter($user_id);
		$fpos = $this->data->getCorrectorPositionFilter($user_id);

		if(isset($_GET["fpos"]) && $_GET["fpos"] != $fpos){
			$this->data->saveCorrectorPositionFilter($user_id, $_GET["fpos"]);
			$fpos = $_GET["fpos"];
		}

		if(isset($_GET["fcorr"]) && $_GET["fcorr"] != $fcorr){
			$this->data->saveCorrectionStatusFilter($user_id, $_GET["fcorr"]);
			$fcorr = $_GET["fcorr"];
		}
	}

    /**
     * Show the items
     */
	protected function showStartPage()
    {
		$toolbar = [];
		$this->saveFilterParams();
		$items = $this->getItems();

		$is_empty_before_filter = empty($items);
		$admin_service = $this->localDI->getCorrectorAdminService($this->object->getId());
		$admin_service->sortCorrectionsArray($items);
		$items = $admin_service->filterCorrections($this->dic->user()->getId(), $items);
		$is_empty_after_filter = empty($items);

		if(!$is_empty_before_filter){
			$this->filterViewControl();
		}

        if ($this->can_correct && $this->ready_items > 0) {
            $this->ctrl->clearParameters($this);
			$button = $this->uiFactory->button()->primary(
				$this->plugin->txt('start_correction'),
				!$is_empty_after_filter ? $this->ctrl->getLinkTarget($this, "startCorrector") : "#"
			);

			if($is_empty_after_filter){
				$this->toolbar->addComponent($button->withUnavailableAction());
			}else{
				$this->toolbar->addComponent($button);
			}
        }

		$object_from_item = function(array $item): \ILIAS\UI\Component\Item\Item {
			return $this->uiFactory->item()->standard($item["title"])
				->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'))
				->withProperties($item["properties"]);
		};

		if (!$is_empty_before_filter) {
            $essays = $this->uiFactory->item()->group($this->plugin->txt('assigned_writings'), array_map($object_from_item, $items));
            $this->tpl->setContent($this->renderer->render($essays));
            $taskSettings = $this->localDI->getTaskRepo()->getTaskSettingsById($this->settings->getTaskId());
            if (!empty($period = $this->data->formatPeriod($taskSettings->getCorrectionStart(), $taskSettings->getCorrectionEnd()))) {
                ilUtil::sendInfo($this->plugin->txt('correction_period') . ': ' . $period);
            }
        }
        else {
            ilUtil::sendInfo($this->plugin->txt('message_no_correction_items'));
        }
     }


    /**
     * Start the Writer Web app
     */
    protected function startCorrector()
    {
        $context = new CorrectorContext();
        $context->init((string) $this->dic->user()->getId(), (string) $this->object->getRefId());

        $params = $this->request->getQueryParams();
        if (!empty($params['writer_id'])) {
            $context->selectWriterId((int) $params['writer_id']);
        }
        $service = new Service($context);
        $service->openFrontend();
    }

    protected function finalizeCorrection()
    {

    }

}