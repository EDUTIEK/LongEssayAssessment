<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\DI\Exceptions\Exception;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Location;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\LogEntry;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\TimeExtension;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;
use ILIAS\Plugin\LongEssayAssessment\Writer\WriterContext;
use ILIAS\Plugin\LongEssayAssessment\Task\LoggingService;
use ilFileDelivery;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\WriterAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI: ilRepositorySearchGUI
 */
class WriterAdminGUI extends BaseGUI
{
    protected LoggingService $loggingService;
    protected WriterAdminService $writerAdminService;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->loggingService = $this->localDI->getLoggingService($this->object->getId());
        $this->writerAdminService = $this->localDI->getWriterAdminService($this->object->getId());
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            case 'ilrepositorysearchgui':
                $rep_search = new \ilRepositorySearchGUI();
                $rep_search->addUserAccessFilterCallable([$this, 'filterUserIdsByLETMembership']);
                $rep_search->setCallback($this, "assignWriters");
                $this->ctrl->setReturn($this, 'showStartPage');
                $ret = $this->ctrl->forwardCommand($rep_search);
                break;
            default:
                $cmd = $this->ctrl->getCmd('showStartPage');
                if(in_array($cmd, ["remove", "change"])) { // Workaround to use fallback cmd for generic cmds from interruptive modals
                    $cmd = $this->request->getQueryParams()["fallbackCmd"] ?? $cmd;
                }
                switch ($cmd) {
                    case 'showStartPage':
                    case 'addWriter':
                    case 'excludeWriter':
                    case 'editExtension':
                    case 'updateExtension':
                    case 'authorizeWriting':
                    case 'unauthorizeWriting':
                    case 'repealExclusion':
                    case 'deleteWriterData':
                    case 'removeWriter':
                    case 'exportSteps':
                    case 'editLocationMulti':
                    case 'editLocation':
                    case 'showEssay':
                    case 'editExtensionMulti':
                    case 'removeWriterMultiConfirmation':
                    case 'changeTextToPdfMultiConfirmation':
                    case 'changeTextToPdf':
                    case 'uploadPDFVersion':
                    case 'downloadPDFVersion':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }
    }

    /**
     * Show the items
     */
    protected function showStartPage()
    {
        $this->addContentCss();
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));

        \ilRepositorySearchGUI::fillAutoCompleteToolbar(
            $this,
            $this->toolbar,
            array()
        );

        // search button
        $delete_writer_data_button = $this->uiFactory->button()->standard(
            $this->plugin->txt("search_participants"),
            $this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI', 'start')
        );
        $this->toolbar->addComponent($delete_writer_data_button);

        // spacer
        $this->toolbar->addSeparator();

        $delete_writer_data_modal = $this->buildDeleteWriterDataModal();
        $delete_writer_data_button = $this->uiFactory->button()->standard($this->plugin->txt("delete_writer_data"), "#")
            ->withOnClick($delete_writer_data_modal->getShowSignal());
        $this->toolbar->addComponent($delete_writer_data_button);

        $writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
        $essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
        $task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();

        $list_gui = new WriterAdminListGUI($this, "showStartPage", $this->plugin);
        $list_gui->setWriters($writer_repo->getWritersByTaskId($this->object->getId()));
        $list_gui->setExtensions($writer_repo->getTimeExtensionsByTaskId($this->object->getId()));
        $list_gui->setEssays($essay_repo->getEssaysByTaskId($this->object->getId()));
        $list_gui->setLocations($task_repo->getLocationsByTaskId($this->object->getId()));

        $this->tpl->setContent($this->renderer->render($delete_writer_data_modal) . $list_gui->getContent());
    }

    private function excludeWriter()
    {
        if(($id = $this->getWriterId()) === null) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_writer_id'), true);
            $this->ctrl->redirect($this, "showStartPage");
        }
        $writer = $this->localDI->getWriterRepo()->getWriterById($id);

        if($writer === null || $writer->getTaskId() !== $this->object->getId()) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_writer'), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        // create essay to store the exclusion, even if writer hasn't started
        // otherwise writer could start again in objects with instant participation
        $essay = $this->writerAdminService->getOrCreateEssayForWriter($writer);

        $datetime = new \ilDateTime(time(), IL_CAL_UNIX);
        $essay->setWritingExcluded($datetime->get(IL_CAL_DATETIME));
        $essay->setWritingExcludedBy($this->dic->user()->getId());
        $this->localDI->getEssayRepo()->save($essay);

        $this->loggingService->addEntry(LogEntry::TYPE_WRITER_EXCLUSION, $this->dic->user()->getId(), $writer->getUserId());

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("exclude_writer_success"), true);
        $this->ctrl->redirect($this, "showStartPage");
    }

    private function repealExclusion()
    {
        global $DIC;
        if(($id = $this->getWriterId()) === null) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_writer_id'), true);
            $this->ctrl->redirect($this, "showStartPage");
        }
        $writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
        $writer = $writer_repo->getWriterById($id);
        $essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
        $essay = $essay_repo->getEssayByWriterIdAndTaskId($writer->getId(), $this->object->getId());

        if($writer === null || $writer->getTaskId() !== $this->object->getId() || $essay === null) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_essay'), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        if($essay->getWritingExcluded() === null) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('essay_not_excluded'), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        $essay->setWritingExcluded(null);
        $essay->setWritingExcludedBy(null);
        $essay_repo->save($essay);

        $this->loggingService->addEntry(LogEntry::TYPE_WRITER_REPEAL_EXCLUSION, $this->dic->user()->getId(), $writer->getUserId());

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("exclude_writer_repeal_success"), true);
        $this->ctrl->redirect($this, "showStartPage");
    }


    protected function removeWriterMultiConfirmation()
    {
        $writer_ids = $this->getWriterIds();
        $writers = $this->localDI->getWriterRepo()->getWritersByTaskId($this->object->getId());
        $user_data = \ilUserUtil::getNamePresentation(array_unique(array_map(fn (Writer $x) => $x->getUserId(), $writers)), true, true, "", true);

        $items = [];

        foreach ($writer_ids as $writer_id) {
            if(array_key_exists($writer_id, $writers)) {
                $writer = $writers[$writer_id];
                $items[] = $this->uiFactory->modal()->interruptiveItem(
                    $writer->getId(),
                    $user_data[$writer->getUserId()]
                );
            }
        }

        $remove_modal = $this->uiFactory->modal()->interruptive(
            $this->plugin->txt("remove_writer"),
            $this->plugin->txt("remove_writer_confirmation"),
            $this->ctrl->getFormAction($this, "removeWriter")
        )->withAffectedItems($items)->withActionButtonLabel("remove");

        echo($this->renderer->renderAsync($remove_modal));
        exit();
    }

    private function removeWriter()
    {
        $ids = [];
        $multi = false;


        if(($id = $this->getWriterId()) !== null) {
            $ids[] = (int)$id;
        } elseif(is_array($items = $this->request->getParsedBody()) && array_key_exists("interruptive_items", $items)) {
            foreach($items["interruptive_items"] as $item) {
                $ids[] = (int) $item;
            }
            $multi = true;
        }

        if(count($ids) < 1) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_writer_id'), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        $essay_repo = $this->localDI->getEssayRepo();
        $writer_repo = $this->localDI->getWriterRepo();
        $corr_repo = $this->localDI->getCorrectorRepo();

        foreach($ids as $id) {
            $writer = $writer_repo->getWriterById($id);

            if(!$multi && ($writer === null || $writer->getTaskId() !== $this->object->getId())) {
                $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_writer'), true);
                $this->ctrl->redirect($this, "showStartPage");
            }

            $essay_repo->deleteEssayByWriterId($id);
            $writer_repo->deleteWriter($id);
            $corr_repo->deleteCorrectorAssignmentByWriter($id);

            $this->loggingService->addEntry(LogEntry::TYPE_WRITER_REMOVAL, $this->dic->user()->getId(), $writer->getUserId());
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt('remove_writer_success'), true);
        $this->ctrl->redirect($this, "showStartPage");
    }

    public function assignWriters(array $a_usr_ids, $a_type = null)
    {
        if (count($a_usr_ids) <= 0) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('no_writer_set'), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        foreach($a_usr_ids as $id) {
            $this->localDI->getWriterAdminService($this->object->getId())
                ->getOrCreateWriterFromUserId((int) $id);
        }

        if(count($a_usr_ids) == 1) {
            $anchor =  "user_" . $a_usr_ids[0];
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt('assign_writer_success'), true);
        $this->ctrl->redirect($this, "showStartPage", $anchor ?? "");
    }

    private function getWriterId(): ?int
    {
        $query = $this->request->getQueryParams();
        if(isset($query["writer_id"])) {
            return (int) $query["writer_id"];
        }
        return null;
    }

    public function filterUserIdsByLETMembership($a_user_ids)
    {
        $user_ids = [];
        $writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
        $writers = array_map(fn ($row) => $row->getUserId(), $writer_repo->getWritersByTaskId($this->object->getId()));

        foreach ($a_user_ids as $user_id) {
            if(!in_array((int)$user_id, $writers)) {
                $user_ids[] = $user_id;
            }
        }

        return $user_ids;
    }


    protected function buildExtensionForm($value = null):BlankForm
    {
        $settings = $this->localDI->getTaskRepo()->getTaskSettingsById($this->object->getId());
        $this->ctrl->saveParameter($this, "writer_id");

        $extension_input = $this->uiFactory->input()->field()
            ->numeric($this->lng->txt('minutes'), $this->plugin->txt("time_extension_caption"))
            ->withRequired(true)
            ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(-1))
            ->withAdditionalTransformation($this->refinery->custom()->constraint(function ($var) use ($settings) {
                if($settings->getCorrectionStart() !== null
                    && $settings->getWritingEnd() !== null) {
                    $solution_available = new \ilDateTime($settings->getSolutionAvailableDate(), IL_CAL_DATETIME);
                    $writing_end = new \ilDateTime($settings->getWritingEnd(), IL_CAL_DATETIME);
                    $extension_date = clone $writing_end;
                    $extension_date->increment(\ilDate::MINUTE, $var);
                    return !\ilDate::_before($extension_date, $solution_available);
                } else {
                    return true;
                }
            }, $this->plugin->txt("time_exceeds_solution_availability")));

        if($value !== null) {
            $extension_input = $extension_input->withValue($value);
        }

        return $this->localDI->getUIFactory()->field()->blankForm(
            $this->ctrl->getFormAction($this, "updateExtension"),
            ['extension' => $extension_input]
        )->withAsyncOnEnter();
    }

    /**
     * Edit and save the settings
     */
    protected function editExtension($form = null)
    {
        $writer_id = $this->getWriterId();
        $extension = $this->getExtension($writer_id);
        $value = $extension->getMinutes();
        $this->ctrl->saveParameter($this, "writer_id");
        $form = $this->buildExtensionForm($value);

        $modal = $this->uiFactory->modal()->roundtrip($this->plugin->txt("extent_time"), $form)->withActionButtons([
            $this->uiFactory->button()->primary($this->lng->txt("submit"), "")->withOnClick($form->getSubmitAsyncSignal())
        ]);
        echo($this->renderer->renderAsync($modal));
        exit();
    }

    protected function editExtensionMulti()
    {
        $this->ctrl->saveParameter($this, "writer_ids");
        $form = $this->buildExtensionForm();
        $modal = $this->uiFactory->modal()->roundtrip($this->plugin->txt("extent_time"), $form)->withActionButtons([
            $this->uiFactory->button()->primary($this->lng->txt("submit"), "")->withOnClick($form->getSubmitAsyncSignal())
        ]);
        echo($this->renderer->renderAsync($modal));
        exit();
    }

    protected function updateExtension()
    {
        $form = $this->buildExtensionForm()->withRequest($this->request);

        if ($data = $form->getData()) {
            $writer_repo = $this->localDI->getWriterRepo();
            foreach ($this->getWriterIds() as $writer_id) {
                $record = $this->getExtension($writer_id);
                $record->setMinutes($data['extension']);
                if ($record->getMinutes() === 0) {
                    $writer_repo->deleteTimeExtension($record->getWriterId(), $record->getTaskId());
                } else {
                    $writer_repo->save($record);
                }

                $writer = $writer_repo->getWriterById($writer_id);
                $this->loggingService->addEntry(
                    LogEntry::TYPE_TIME_EXTENSION,
                    $this->dic->user()->getId(),
                    $writer->getUserId(),
                    sprintf(
                        $this->plugin->txt('log_entry_time_extension_note', $this->plugin->getDefaultLanguage()),
                        $record->getMinutes()
                    )
                );
            }
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
        } else {
            echo($this->renderer->render($form));
        }
        exit();
    }


    protected function authorizeWriting()
    {

        if (($id = $this->getWriterId()) === null) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("writing_autorized"), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        $essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
        $essay = $essay_repo->getEssayByWriterIdAndTaskId($id, $this->object->getId());

        if($essay === null) {
            throw new Exception("No Essay found for writer.");
        }

        $this->localDI->getWriterAdminService($this->object->getId())->authorizeWriting($essay, $this->dic->user()->getId());
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("writing_authorized"), true);
        $this->ctrl->redirect($this, "showStartPage", "writer_" . $id);
    }

    protected function unauthorizeWriting()
    {

        if (($id = $this->getWriterId()) === null) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("writing_unautorized"), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        $essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
        $essay = $essay_repo->getEssayByWriterIdAndTaskId($id, $this->object->getId());

        if($essay === null) {
            throw new Exception("No Essay found for writer.");
        }

        $this->localDI->getWriterAdminService($this->object->getId())->removeAuthorizationWriting($essay, $this->dic->user()->getId());
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("writing_unautorized"), true);
        $this->ctrl->redirect($this, "showStartPage", "writer_" . $id);
    }


    protected function deleteWriterData()
    {
        $essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
        $writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
        $task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
        $corr_repo = LongEssayAssessmentDI::getInstance()->getCorrectorRepo();

        $essay_repo->deleteEssayByTaskId($this->object->getId());
        $writer_repo->deleteWriterByTaskId($this->object->getId());
        $task_repo->deleteLogEntryByTaskId($this->object->getId());
        $task_repo->deleteAlertByTaskId($this->object->getId());
        $corr_repo->deleteCorrectorAssignmentByTask($this->object->getId());


        /* TODO: Besprechen ob manuell hinzugefügte Teilnehmer bei nicht selbständigem Start erhalten bleiben sollen.
        $object_repo = LongEssayAssessmentDI::getInstance()->getObjectRepo();
        $settings = $object_repo->getObjectSettingsById($this->object->getId());

        // when self participation is active also delete all writer
        if($settings->getParticipationType() === ObjectSettings::PARTICIPATION_TYPE_INSTANT){
            $writer_repo->deleteWriterByTaskId($this->object->getId());
        }*/
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("delete_writer_data_success"), true);
        $this->ctrl->redirect($this, "showStartPage");
    }

    protected function getExtension(int $writer_id): ?TimeExtension
    {
        $writer_repo  = LongEssayAssessmentDI::getInstance()->getWriterRepo();
        $record = $writer_repo->getTimeExtensionByWriterId($writer_id, $this->object->getId());

        if(!$record) {
            return (new TimeExtension())->setWriterId($writer_id)->setTaskId($this->object->getId());
        }

        return $record;
    }

    private function buildDeleteWriterDataModal()
    {
        return $this->uiFactory->modal()->interruptive(
            $this->plugin->txt("delete_writer_data"),
            $this->plugin->txt("delete_writer_data_confirmation"),
            $this->ctrl->getLinkTarget($this, "deleteWriterData")
        )->withActionButtonLabel("remove");
    }

    private function exportSteps()
    {
        if (empty($repoWriter = $this->localDI->getWriterRepo()->getWriterById((int) $this->getWriterId()))) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("missing_writer_id"), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        $service = $this->localDI->getWriterAdminService($this->object->getId());
        $name = ilFileDelivery::returnASCIIFilename($this->object->getTitle() .'_' . \ilObjUser::_lookupFullname($repoWriter->getUserId()));
        $zipfile = $service->createWritingStepsExport($this->object, $repoWriter, $name);
        if (empty($zipfile)) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("content_not_available"), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        ilFileDelivery::deliverFileAttached($zipfile, $name . '.zip', 'application/zip', false);
    }

    protected function buildLocationForm($value = null): BlankForm
    {
        $task_repo = $this->localDI->getTaskRepo();
        $locations = $task_repo->getLocationsByTaskId($this->object->getId());
        $options = [];
        foreach ($locations as $location) {
            $options[$location->getId()] = $location->getTitle();
        }
        $location_input = $this->uiFactory->input()->field()->select($this->plugin->txt("location"), $options);

        if($value !== null) {
            $location_input = $location_input->withValue($value);
        }
        return $this->localDI->getUIFactory()->field()->blankForm(
            $this->ctrl->getFormAction($this, "editLocation"),
            ["location" => $location_input]
        );
    }

    protected function updateLocation($data)
    {

        $essay_repo = $this->localDI->getEssayRepo();
        $task_repo = $this->localDI->getTaskRepo();
        $location = $task_repo->ifLocationExistsById((int)$data["location"]) ? (int)$data["location"] : null;

        $essays = $this->getEssaysFromWriterIds();

        foreach($essays as $writer_id => $essay) {
            if($essay === null) {
                $essay = Essay::model();
                $essay->setTaskId($this->object->getId())
                    ->setWriterId($writer_id)
                    ->setUuid($essay->generateUUID4())
                    ->setRawTextHash('');
                ;
            }
            $essay_repo->save($essay->setLocation($location));
        }
    }

    protected function editLocationMulti()
    {
        $this->ctrl->saveParameter($this, "writer_ids");
        $form = $this->buildLocationForm();

        if($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if($data = $form->getData()) {
                $this->updateLocation($data);
                $this->tpl->setOnScreenMessage("success", $this->plugin->txt("location_assigned"), true);
                exit();
            } else {
                echo($this->renderer->render($form));
                exit();
            }
        }

        $modal = $this->uiFactory->modal()->roundtrip($this->plugin->txt("assign_location"), $form)->withActionButtons([
            $this->uiFactory->button()->primary($this->lng->txt("submit"), "")->withOnClick($form->getSubmitAsyncSignal())
        ]);
        echo($this->renderer->renderAsync($modal));
        exit();
    }

    protected function editLocation()
    {
        $essays = $this->getEssaysFromWriterIds();
        $value = count($essays) > 0 && ($essay =  array_pop($essays)) !== null? $essay->getLocation() : null;
        $this->ctrl->saveParameter($this, "writer_id");
        $form = $this->buildLocationForm($value);

        if($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if($data = $form->getData()) {
                $this->updateLocation($data);
                $this->tpl->setOnScreenMessage("success", $this->plugin->txt("location_assigned"), true);
                exit();
            } else {
                echo($this->renderer->render($form));
                exit();
            }
        }

        $modal = $this->uiFactory->modal()->roundtrip($this->plugin->txt("change_location"), $form)->withActionButtons([
            $this->uiFactory->button()->primary($this->lng->txt("submit"), "")->withOnClick($form->getSubmitAsyncSignal())
        ]);
        echo($this->renderer->renderAsync($modal));
        exit();
    }

    protected function getWriterIds(): array
    {
        $ids = [];
        $query_params = $this->request->getQueryParams();

        if(isset($query_params["writer_id"]) && $query_params["writer_id"] !== "") {
            $ids[] = (int) $query_params["writer_id"];
        } elseif (isset($query_params["writer_ids"])) {
            foreach(explode('/', $query_params["writer_ids"]) as $value) {
                $ids[] = (int) $value;
            }
        }
        return $ids;
    }

    /**
     * @return Essay[]
     */
    protected function getEssaysFromWriterIds(): array
    {
        $ids = [];
        $query_params = $this->request->getQueryParams();
        $essay_repo = $this->localDI->getEssayRepo();

        if(isset($query_params["writer_id"]) && $query_params["writer_id"] !== "") {
            $ids[] = (int) $query_params["writer_id"];
        } elseif (isset($query_params["writer_ids"])) {
            foreach(explode('/', $query_params["writer_ids"]) as $value) {
                $ids[] = (int) $value;
            }
        }
        $essays = [];
        foreach ($essay_repo->getEssaysByTaskId($this->object->getId()) as $essay) {
            $essays[$essay->getWriterId()] = $essay;
        }
        $ret = [];

        foreach($ids as $id) {
            $ret[$id] = $essays[$id] ?? null;
        }

        return $ret;
    }

    protected function showEssay()
    {
        $essays = $this->getEssaysFromWriterIds();
        $value = count($essays) > 0 && ($essay =  array_pop($essays)) !== null? $essay->getWrittenText() : '';
        $value = $this->displayContent($this->localDI->getDataService($this->object->getId())->cleanupRichText($value));

        $this->ctrl->saveParameter($this, "writer_id");
        $link = $this->ctrl->getFormAction($this, "showEssay", "", true);

        $sight_modal = $this->uiFactory->modal()->roundtrip(
            $this->plugin->txt("submission"),
            $this->uiFactory->legacy($value)
        );
        $reload_button = $this->uiFactory->button()->standard($this->lng->txt("refresh"), "")
            ->withLoadingAnimationOnClick(true)
            ->withOnLoadCode(
                function ($id) use ($link) {
                    return
                        "$('#{$id}').click(function() { 
						n_url = '{$link}';
						text = $('#$id').html();
						$('#$id').html('...');
						il.UI.core.replaceContent($(this).closest('.modal').attr('id'), n_url, 'component');
						$('#$id').html(text);
						il.UI.button.deactivateLoadingAnimation('$id');
						return false;
					}
				);";
                }
            );

        echo($this->renderer->renderAsync($sight_modal->withActionButtons([$reload_button])));
        exit();
    }

    public function buildPDFVersionForm(Essay $essay): Standard
    {
        $this->ctrl->saveParameter($this, "writer_id");
        $link = $this->ctrl->getFormAction($this, "uploadPDFVersion", "", true);
        $download = $essay->getPdfVersion() !== null ?
            "</br>" . $this->renderer->render(
                $this->uiFactory->link()->standard(
                    $this->plugin->txt("download"),
                    $this->ctrl->getFormAction($this, "downloadPDFVersion", "", true)
                )
            ) : "";


        $fields = [];
        $fields["pdf_version"] = $this->uiFactory->input()->field()->file(
            new \ilLongEssayAssessmentUploadHandlerGUI($this->storage, $this->localDI->getUploadTempFile()),
            $this->lng->txt("file"),
            $this->localDI->getUIService()->getMaxFileSizeString() . $download
        )
            ->withAcceptedMimeTypes(['application/pdf'])
            ->withValue($essay->getPdfVersion() !== null ? [$essay->getPdfVersion()]: null);

        //		$fields["edit_time"] = $this->uiFactory->input()->field()->optionalGroup([
        //			"edit_start" => $this->uiFactory->input()->field()->dateTime($this->plugin->txt("edit_start"))->withValue($essay->getEditStarted() ?? ""),
        //			"edit_end" => $this->uiFactory->input()->field()->dateTime($this->plugin->txt("edit_end"))->withValue($essay->getEditEnded() ?? "")
        //		], "Schreibzeitraum")
        //			->withByline($this->plugin->txt("edit_time_info")/*"Optional: Schreibzeitrum mit Protokollieren"*/);

        $fields["authorize"] = $this->uiFactory->input()->field()->checkbox($this->plugin->txt("authorize_writing"))
            ->withByline($this->plugin->txt("authorize_pdf_version_info"))
            ->withValue($essay->getWritingAuthorized() !== null && $essay->getWritingAuthorizedBy() === $this->dic->user()->getId());

        return $this->uiFactory->input()->container()->form()->standard($link, $fields, "");
    }

    protected function changeTextToPdfMultiConfirmation()
    {
        $essayRepo = $this->localDI->getEssayRepo();
        
        $writer_ids = $this->getWriterIds();
        $writers = $this->localDI->getWriterRepo()->getWritersByTaskId($this->object->getId());
        $user_data = \ilUserUtil::getNamePresentation(array_unique(array_map(fn (Writer $x) => $x->getUserId(), $writers)), true, true, "", true);

        $items = [];
        foreach ($writer_ids as $writer_id) {
            if(array_key_exists($writer_id, $writers)) {
                $writer = $writers[$writer_id];
                $essay = $essayRepo->getEssayByWriterIdAndTaskId($writer->getId(), $writer->getTaskId());
                if (empty($essay->getWrittenText()) || !empty($essay->getPdfVersion())) {
                    continue;
                }
                if(!empty(
                    $this->localDI->getCorrectorAdminService(
                        $this->object->getId()
                    )->getAuthorizedSummaries($essay))
                ) {
                    continue;
                }
                
                $items[] = $this->uiFactory->modal()->interruptiveItem(
                    $writer->getId(),
                    $user_data[$writer->getUserId()]
                );
            }
        }

        if(empty($items)) {
            $change_modal = $this->uiFactory->modal()->roundtrip(
                $this->plugin->txt("change_text_to_pdf"),
                $this->uiFactory->legacy($this->plugin->txt("change_text_to_pdf_none_possible")),
            );
        } else {
            $change_modal = $this->uiFactory->modal()->interruptive(
                $this->plugin->txt("change_text_to_pdf"),
                $this->plugin->txt("change_text_to_pdf_confirmation"),
                $this->ctrl->getFormAction($this, "changeTextToPdf")
            )->withAffectedItems($items)->withActionButtonLabel('change');
        }

        echo($this->renderer->renderAsync($change_modal));
        exit();
    }

    public function changeTextToPdf()
    {
        $writer_ids = [];
        if(($id = $this->getWriterId()) !== null) {
            $writer_ids[] = (int) $id;
        } elseif(is_array($items = $this->request->getParsedBody()) && array_key_exists("interruptive_items", $items)) {
            foreach($items["interruptive_items"] as $item) {
                $writer_ids[] = (int) $item;
            }
        }

        if(empty($writer_ids)) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("missing_writer_id"), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        $essayRepo = $this->localDI->getEssayRepo();
        $writers = $this->localDI->getWriterRepo()->getWritersByTaskId($this->object->getId());
        $service = $this->localDI->getWriterAdminService($this->object->getId());
        
        foreach ($writer_ids as $writer_id) {
            if(array_key_exists($writer_id, $writers)) {
                $writer = $writers[$writer_id];
                $essay = $essayRepo->getEssayByWriterIdAndTaskId($writer->getId(), $writer->getTaskId());
                if (empty($essay->getWrittenText()) || !empty($essay->getPdfVersion())) {
                    continue;
                }
                if(!empty(
                    $this->localDI->getCorrectorAdminService(
                        $this->object->getId()
                    )->getAuthorizedSummaries($essay))
                ) {
                    continue;
                }

                $context = new WriterContext();
                $context->init((string) $writer->getUserId(), (string) $this->object->getRefId());
                $service->createPdfFromText($this->object, $essay, $writer);
                $service->purgeCorrectorComments($essay);
            }
        }

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("change_text_to_pdf_success"), true);
        $this->ctrl->redirect($this);
    }

    public function uploadPDFVersion()
    {
        $writer_repo = $this->localDI->getWriterRepo();
        $task_repo = $this->localDI->getTaskRepo();
        $task_id = $this->object->getId();

        $service = $this->localDI->getWriterAdminService($this->object->getId());
        
        $this->tabs->setBackTarget($this->lng->txt("back"), $this->ctrl->getLinkTarget($this));

        if(($id = $this->getWriterId()) !== null && ($writer = $writer_repo->getWriterById($id)) !== null) {
            $essay = $service->getOrCreateEssayForWriter($writer);
            
            if(!empty(
                $this->localDI->getCorrectorAdminService(
                    $this->object->getId()
                )->getAuthorizedSummaries($essay))
            ) {
                $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("pdf_version_upload_not_allowed_by_corrections"), true);
                $this->ctrl->redirect($this);
            }
            
            $form = $this->buildPDFVersionForm($essay);

            if($this->request->getMethod() === "POST") {
                $form = $form->withRequest($this->request);

                if($data = $form->getData()) {
                    $file_id = $data["pdf_version"][0];

                    if($file_id != $essay->getPdfVersion()) {
                        
                        if((int)$data["authorize"] == 1 && $file_id !== null) {
                            $service->authorizeWriting($essay, $this->dic->user()->getId());
                            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("pdf_version_upload_successful_auth"), true);
                        } elseif($file_id !== null) {
                            $service->removeAuthorizationWriting($essay, $this->dic->user()->getId());
                            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("pdf_version_upload_successful_no_auth"), true);
                        } else {
                            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("pdf_version_upload_successful_removed"), true);
                        }

                        $service->handlePDFVersionInput($essay, $file_id);
                        $service->createEssayImages($this->object, $essay, $writer);
                        $service->purgeCorrectorComments($essay);
                        
                        $this->ctrl->redirect($this);
                    } else {
                        $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("pdf_version_upload_failure"), true);
                    }
                }
            }

            $names = \ilUserUtil::getNamePresentation(
                [$writer->getUserId()],
                true,
                true,
                $this->ctrl->getLinkTarget($this, "uploadPDFVersion"),
                true
            );

            $user_properties = [
                "" => $names[$writer->getUserId()],
                $this->plugin->txt("pseudonym") => $writer->getPseudonym()
            ];

            if($essay->getLocation() !== null) {
                $location = $task_repo->getLocationById($essay->getLocation());
                if($location !== null) {
                    $user_properties[$this->plugin->txt("location")] = (string) $location;
                }
            }
            $user_properties[$this->plugin->txt("writing_status")] = $this->localDI->getDataService($task_id)->formatWritingStatus($essay, false);

            $user_info = $this->uiFactory->card()->standard($this->plugin->txt("participant"))
                ->withSections([$this->uiFactory->listing()->descriptive($user_properties)]);

            $subs = [
                $this->uiFactory->panel()->sub($essay->getPdfVersion() !== null
                    ? $this->plugin->txt("pdf_version_edit")
                    : $this->plugin->txt("pdf_version_upload"), $form)->withCard($user_info)
            ];

            if($essay->getEditStarted()) {
                if ($essay->getPdfVersion() !== null) {
                    if ($service->hasCorrectorComments($essay)) {
                        $this->tpl->setOnScreenMessage("question", $this->plugin->txt("pdf_version_info_already_uploaded"), false);
                    }
                } elseif($essay->getWritingAuthorized() !== null && $essay->getWritingAuthorizedBy() === $writer->getUserId()) {
                    $this->tpl->setOnScreenMessage("question", $this->plugin->txt("pdf_version_warning_authorized_essay"), false);
                } else {
                    $this->tpl->setOnScreenMessage("question", $this->plugin->txt("pdf_version_info_started_essay"), false);
                }

                $this->addContentCss();
                $subs[] = $this->uiFactory->panel()->sub(
                    $this->plugin->txt("pdf_version_header_writing"),
                    $this->uiFactory->legacy($this->displayContent($this->localDI->getDataService($task_id)->cleanupRichText($essay->getWrittenText())))
                );
            }

            $panel = $this->uiFactory->panel()->standard("", $subs);

            $this->tpl->setContent($this->renderer->render([$panel]));
        } else {
            $this->ctrl->redirect($this->ctrl->getLinkTarget($this));
        }
    }

    public function downloadPDFVersion()
    {
        $writer_repo = $this->localDI->getWriterRepo();
        $essay_repo = $this->localDI->getEssayRepo();

        if(($id = $this->getWriterId()) !== null
            && ($writer = $writer_repo->getWriterById($id)) !== null
            && ($essay = $essay_repo->getEssayByWriterIdAndTaskId($id, $this->object->getId()))
            && $essay->getPdfVersion() !== null
            && ($identifier = $this->storage->manage()->find($essay->getPdfVersion()))) {
            $name = ilFileDelivery::returnASCIIFilename(
                $this->object->getTitle() . '_' .
                $this->plugin->txt("pdf_version") . '_' .
                \ilObjUser::_lookupFullname($writer->getUserId())
            );

            $this->storage->consume()->download($identifier)->overrideFileName($name . ".pdf")->run();
        } else {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("pdf_version_not_found"), true);
        }
        $this->ctrl->redirect($this);
    }
}
