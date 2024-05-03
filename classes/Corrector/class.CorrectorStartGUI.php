<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use Edutiek\LongEssayAssessmentService\Corrector\Service;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Data\DataService;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use \ilUtil;

/**
 *Start page for correctors
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorStartGUI: ilObjLongEssayAssessmentGUI
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


    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->settings = $this->service->getSettings();
        $this->correctorRepo = $this->localDI->getCorrectorRepo();
        $this->can_correct =  $this->object->canCorrect();
        ;
    }


    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showStartPage');
        switch ($cmd) {
            case 'showStartPage':
            case 'startCorrector':
            case 'removeAuthorization':
            case 'removeAuthorizationConfirmationAsync':
            case 'authorizationConfirmationAsync':
            case 'authorizeCorrection':
            case 'downloadWrittenPdf':
            case 'downloadCorrectedPdf':
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
    protected function getItems() : array
    {
        $items = [];
        $corrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->settings->getTaskId());
        $preferences = $this->correctorRepo->getCorrectorPreferences($corrector->getId());
        $dataService = $this->localDI->getDataService($this->settings->getTaskId());
        $icon = $this->uiFactory->image()->responsive('./templates/default/images/icon_wiki.svg', '');

        foreach ($this->localDI->getCorrectorRepo()->getAssignmentsByCorrectorId($corrector->getId()) as $assignment) {
            $writer = $this->localDI->getWriterRepo()->getWriterById($assignment->getWriterId());
            $essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($assignment->getWriterId(), $this->settings->getTaskId());

            if (empty($essay)) {
                continue;
            }
            $modals = [];
            $actions = [];

            $summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId($essay->getId(), $corrector->getId());

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

            $this->ctrl->setParameter($this, 'writer_id', $writer->getId());
            $actions[] = $this->uiFactory->button()->shy(
                $this->plugin->txt('download_written_pdf'),
                $this->ctrl->getLinkTarget($this, 'downloadWrittenPdf')
            );

            $actions[] = $this->uiFactory->button()->shy(
                $this->plugin->txt('download_corrected_pdf'),
                $this->ctrl->getLinkTarget($this, 'downloadCorrectedPdf')
            );

            if ($this->service->canRemoveCorrectionAuthorize($essay, $summary)) {
                $this->ctrl->setParameter($this, 'writer_id', $essay->getWriterId());

                $modals[] = $remove_auth_modal = $this->uiFactory->modal()->interruptive(
                    $this->plugin->txt('remove_own_authorization'),
                    $this->plugin->txt('confirm_remove_own_authorization'),
                    $this->ctrl->getLinkTarget($this, 'removeAuthorization')
                )->withAffectedItems([
                    $this->uiFactory->modal()->interruptiveItem(
                        $writer->getId(),
                        $writer->getPseudonym() . ': ' . $dataService->formatCorrectionResult($summary),
                        $icon
                    )
                ])->withActionButtonLabel('ok');

                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('remove_own_authorization'), "")
                    ->withOnClick($remove_auth_modal->getShowSignal());
            }

            if ($this->service->canAuthorizeCorrection($essay, $summary)) {
                $this->ctrl->setParameter($this, 'writer_id', $essay->getWriterId());

                $modals[] = $auth_modal = $this->uiFactory->modal()->interruptive(
                    $this->plugin->txt('authorize_correction'),
                    $this->plugin->txt('confirm_authorize_correction'),
                    $this->ctrl->getLinkTarget($this, 'authorizeCorrection')
                )->withAffectedItems([
                    $this->uiFactory->modal()->interruptiveItem(
                        $writer->getId(),
                        $writer->getPseudonym() . ': ' . $dataService->formatCorrectionResult($summary),
                        $icon,
                        $dataService->formatCorrectionInclusions($summary, $preferences, $this->settings)
                    )
                ])->withActionButtonLabel('ok');

                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('authorize_correction'), "")
                    ->withOnClick($auth_modal->getShowSignal());
            }


            $title = $writer->getPseudonym();
            if ($this->can_correct && $this->service->isCorrectionPossible($essay, $summary)) {
                $this->ready_items++;
                $this->ctrl->setParameter($this, 'writer_id', $assignment->getWriterId());
                $title = $this->uiFactory->link()->standard($title, $this->ctrl->getLinkTarget($this, 'startCorrector'));
            }

            $items[] = [
                "title" => $title,
                "writer_id" => $writer->getId(),
                "properties" => $properties,
                "actions" => $actions,
                "modals" => $modals,
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
        ];
        
        if ($this->settings->getRequiredCorrectors() > 1) {
            $correction_actions[CorrectorSummary::STATUS_STITCH] = $this->plugin->txt('correction_filter_stitch');
        }
        
        if (!isset($correction_actions[$fcorr])) {
            $fcorr = DataService::ALL;
        }

        $correction_aria_label = "change_the_currently_displayed_mode";
        $view_control_correction = $this->uiFactory->viewControl()->mode($this->prepareActionList($correction_actions, "fcorr"), $correction_aria_label)
            ->withActive($correction_actions[$fcorr]);
        $ctrl->setParameter($this, "fcorr", $fcorr);//Reset ctrl saved parameter

        if ($this->settings->getRequiredCorrectors() > 1) {
            $position_aria_label = "change_the_currently_displayed_mode";
            $position_actions = [
                DataService::ALL => $this->lng->txt("all"),
                "1" => $this->plugin->txt('assignment_pos_first'),
                "2" => $this->plugin->txt('assignment_pos_second'),
            ];
            $view_control_position = $this->uiFactory->viewControl()->mode($this->prepareActionList($position_actions, "fpos"), $position_aria_label)
                                                     ->withActive($position_actions[$fpos]);
            $ctrl->setParameter($this, "fpos", $fpos);//Reset ctrl saved parameter
        }
        
        $this->toolbar->addText($this->plugin->txt("own_correction") . ":");
        $this->toolbar->addComponent($view_control_correction);
        $this->toolbar->addSeparator();

        if ($this->settings->getRequiredCorrectors() > 1) {
            $this->toolbar->addText($this->plugin->txt("own_position") . ":");
            $this->toolbar->addComponent($view_control_position);
            $this->toolbar->addSeparator();
        }
    }

    protected function prepareActionList($actions, $type) : array
    {
        $ret = [];
        foreach($actions as $key => $value) {
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
    protected function saveFilterParams()
    {
        $user_id = $this->dic->user()->getId();
        $this->ctrl->saveParameter($this, "fcorr");
        $this->ctrl->saveParameter($this, "fpos");
        $fcorr = $this->data->getCorrectionStatusFilter($user_id);
        $fpos = $this->data->getCorrectorPositionFilter($user_id);

        if(isset($_GET["fpos"]) && $_GET["fpos"] != $fpos) {
            $this->data->saveCorrectorPositionFilter($user_id, $_GET["fpos"]);
            $fpos = $_GET["fpos"];
        }

        if(isset($_GET["fcorr"]) && $_GET["fcorr"] != $fcorr) {
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
        $count_total = count($items);
        $admin_service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $admin_service->sortCorrectionsArray($items);
        $items = $admin_service->filterCorrections($this->dic->user()->getId(), $items);
        $is_empty_after_filter = empty($items);
        $count_filtered = count($items);
        $modals = [];

        if(!$is_empty_before_filter) {
            $this->filterViewControl();
        }

        if ($this->can_correct && $this->ready_items > 0) {
            $this->ctrl->clearParameters($this);
            $button = $this->uiFactory->button()->primary(
                $this->plugin->txt('start_correction'),
                !$is_empty_after_filter ? $this->ctrl->getLinkTarget($this, "startCorrector") : "#"
            );

            if($is_empty_after_filter) {
                $this->toolbar->addComponent($button->withUnavailableAction());
            } else {
                $this->toolbar->addComponent($button);
            }
        }

        $object_from_item = function (array $item) use (&$modals): \ILIAS\UI\Component\Item\Item {

            $object = $this->localDI->getUIFactory()->item()->formItem($item["title"])
                ->withName($item["writer_id"])
                ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'))
                ->withProperties($item["properties"]);
            if (!empty($item['actions'])) {
                $object = $object->withActions($this->uiFactory->dropdown()->standard($item['actions'])->withLabel($this->plugin->txt("actions")));
            }
            if(!empty($item["modals"])) {
                $modals = array_merge($modals, $item["modals"]);
            }

            return $object;
        };

        if (!$is_empty_before_filter) {

            $form_actions = [];

            $essays = $this->localDI->getUIFactory()->item()->formGroup(
                $this->plugin->txt('assigned_writings') . $this->data->formatCounterSuffix($count_filtered, $count_total),
                array_map($object_from_item, $items),
                ""
            );

            $auth_callback_signal = $essays->generateDSCallbackSignal();

            $modals[] = $essays->addDSModalTriggerToModal(
                $this->uiFactory->modal()->interruptive("", "", ""),
                $this->ctrl->getFormAction($this, "authorizationConfirmationAsync", "", true),
                "writer_ids",
                $auth_callback_signal
            );

            $form_actions[] = $essays->addDSModalTriggerToButton(
                $this->uiFactory->button()->shy($this->plugin->txt("authorize_correction"), "#"),
                $auth_callback_signal
            );

            $deauth_callback_signal = $essays->generateDSCallbackSignal();

            $modals[] = $essays->addDSModalTriggerToModal(
                $this->uiFactory->modal()->interruptive("", "", ""),
                $this->ctrl->getFormAction($this, "removeAuthorizationConfirmationAsync", "", true),
                "writer_ids",
                $deauth_callback_signal
            );

            $form_actions[] = $essays->addDSModalTriggerToButton(
                $this->uiFactory->button()->shy($this->plugin->txt("remove_own_authorization"), "#"),
                $deauth_callback_signal
            );

            $essays = $essays->withActions($this->uiFactory->dropdown()->standard($form_actions));

            $this->tpl->setContent($this->renderer->render(array_merge([$essays], $modals)));
            $taskSettings = $this->localDI->getTaskRepo()->getTaskSettingsById($this->settings->getTaskId());
            if (!empty($period = $this->data->formatPeriod($taskSettings->getCorrectionStart(), $taskSettings->getCorrectionEnd()))) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt("correction_period"). ': ' . $period, false);
            }
        } else {
            $this->tpl->setOnScreenMessage("info", $this->plugin->txt("message_no_correction_items"), false);
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

    protected function authorizeCorrection()
    {
        $corrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->settings->getTaskId());
        $preferences = $this->localDI->getCorrectorRepo()->getCorrectorPreferences($corrector->getId());
        
        $valid = false;

        foreach($this->getWriterIds() as $writer_id) {
            $essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($writer_id, $this->settings->getTaskId());

            if (empty($essay)) {
                continue;
            }
            $summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId($essay->getId(), $corrector->getId());

            if (empty($summary)) {
                continue;
            }
            $valid = true;
            $summary->applyPreferences($preferences);
            $this->service->authorizeCorrection($summary, $corrector->getUserId());
            $this->service->tryFinalisation($essay, $corrector->getUserId());
        }

        if($valid) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("authorize_correction_done"), true);
            $this->ctrl->redirect($this);
        } else {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("no_corrections_to_authorize"), true);
            $this->ctrl->redirect($this);
        }
    }

    protected function downloadWrittenPdf()
    {
        $params = $this->request->getQueryParams();
        $writer_id = (int) ($params['writer_id'] ?? 0);

        $service = $this->localDI->getWriterAdminService($this->object->getId());
        $repoWriter = $this->localDI->getWriterRepo()->getWriterById($writer_id);

        $filename = 'task' . $this->object->getId() . '_writer' . $repoWriter->getId(). '-writing.pdf';
        $this->common_services->fileHelper()->deliverData($service->getWritingAsPdf($this->object, $repoWriter, true), $filename, 'application/pdf');
    }

    protected function downloadCorrectedPdf()
    {
        $params = $this->request->getQueryParams();
        $writer_id = (int) ($params['writer_id'] ?? 0);

        $service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $repoWriter = $this->localDI->getWriterRepo()->getWriterById($writer_id);
        $repoCorrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->settings->getTaskId());

        $filename = 'task' . $this->object->getId() . '_writer' . $repoWriter->getId(). '-correction.pdf';
        $this->common_services->fileHelper()->deliverData($service->getCorrectionAsPdf($this->object, $repoWriter, $repoCorrector, true), $filename, 'application/pdf');
    }



    protected function removeAuthorization()
    {
        $corrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->settings->getTaskId());
        $success = false;

        foreach($this->getWriterIds() as $writer_id) {
            $writer = $this->localDI->getWriterRepo()->getWriterById($writer_id);
            if(empty($writer)) {
                continue;
            }
            if ($this->service->removeOwnAuthorization($writer, $corrector)) {
                $success = true;
            } else {
                $this->tpl->setOnScreenMessage("failure", sprintf($this->plugin->txt('remove_own_authorization_failed'), $writer->getPseudonym()), true);
            }
        }

        if($success) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt('remove_own_authorization_done'), true);
        }

        $this->ctrl->redirect($this);
    }

    protected function removeAuthorizationConfirmationAsync()
    {
        $ids = $this->getWriterIds();
        $corrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->settings->getTaskId());
        $items = [];


        foreach ($this->localDI->getCorrectorRepo()->getAssignmentsByCorrectorId($corrector->getId()) as $assignment) {
            if(!in_array($assignment->getWriterId(), $ids)) {
                continue;
            }

            $writer = $this->localDI->getWriterRepo()->getWriterById($assignment->getWriterId());
            $essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($assignment->getWriterId(), $this->settings->getTaskId());

            if (empty($essay)) {
                continue;
            }

            $summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId($essay->getId(), $corrector->getId());

            if($this->service->canRemoveCorrectionAuthorize($essay, $summary)) {
                $items[] = $this->uiFactory->modal()->interruptiveItem($writer->getId(), $writer->getPseudonym());
            }
        }

        if(count($items) > 0) {
            echo($this->renderer->render($this->uiFactory->modal()->interruptive(
                $this->plugin->txt('remove_own_authorization'),
                $this->plugin->txt('confirm_remove_own_authorization'),
                $this->ctrl->getFormAction($this, "removeAuthorization")
            )->withAffectedItems($items)
                ->withActionButtonLabel("ok")));
        } else {
            echo($this->renderer->render($this->uiFactory->modal()->roundtrip(
                "",
                $this->uiFactory->messageBox()->failure($this->plugin->txt("no_authorizations_to_remove"))
            )));
        }

        exit();
    }

    protected function authorizationConfirmationAsync()
    {
        $ids = $this->getWriterIds();
        $corrector = $this->correctorRepo->getCorrectorByUserId($this->dic->user()->getId(), $this->settings->getTaskId());
        $preferences = $this->correctorRepo->getCorrectorPreferences($corrector->getId());
        $dataService = $this->localDI->getDataService($this->settings->getTaskId());
        $icon = $this->uiFactory->image()->responsive('./templates/default/images/icon_wiki.svg', '');

        $items = [];

        foreach ($this->localDI->getCorrectorRepo()->getAssignmentsByCorrectorId($corrector->getId()) as $assignment) {
            if(!in_array($assignment->getWriterId(), $ids)) {
                continue;
            }

            $writer = $this->localDI->getWriterRepo()->getWriterById($assignment->getWriterId());
            $essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($assignment->getWriterId(), $this->settings->getTaskId());

            if (empty($essay)) {
                continue;
            }

            $summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId($essay->getId(), $corrector->getId());
            if($this->service->canAuthorizeCorrection($essay, $summary)) {
                $items[] = $this->uiFactory->modal()->interruptiveItem(
                    $writer->getId(),
                    $writer->getPseudonym() . ': ' . $dataService->formatCorrectionResult($summary),
                    $icon,
                    $dataService->formatCorrectionInclusions($summary, $preferences, $this->settings)
                );
            }
        }

        if(count($items) > 0) {
            echo($this->renderer->render($this->uiFactory->modal()->interruptive(
                $this->plugin->txt('authorize_correction'),
                $this->plugin->txt('confirm_authorize_correction'),
                $this->ctrl->getFormAction($this, "authorizeCorrection")
            )->withAffectedItems($items)
            ->withActionButtonLabel("ok")));
        } else {
            echo($this->renderer->render($this->uiFactory->modal()->roundtrip(
                "",
                $this->uiFactory->messageBox()->failure($this->plugin->txt("no_corrections_to_authorize"))
            )));
        }

        exit();
    }


    protected function getWriterFromRequest() : ?Writer
    {
        $query = $this->request->getQueryParams();
        if(isset($query["writer_id"])) {
            return $this->localDI->getWriterRepo()->getWriterById((int) $query["writer_id"]);
        }
        return null;
    }

    protected function getWriterIds(): array
    {
        $ids = [];
        $query_params = $this->request->getQueryParams();
        $post = $this->request->getParsedBody();

        if(isset($post["interruptive_items"])) {
            foreach($post["interruptive_items"] as $value) {
                $ids[] = intval($value);
            }
        } elseif (isset($query_params["writer_ids"])) {
            foreach(explode('/', $query_params["writer_ids"]) as $value) {
                $ids[] = (int) $value;
            }
        } elseif(isset($query_params["writer_id"]) && $query_params["writer_id"] !== "") {
            $ids[] = (int) $query_params["writer_id"];
        }

        return $ids;
    }
}
