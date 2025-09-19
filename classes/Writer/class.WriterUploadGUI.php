<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Writer;

use DateTimeImmutable;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as Permissions;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\Manager as TaskService;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo as Task;
use Edutiek\AssessmentService\EssayTask\Data\Essay;
use Edutiek\AssessmentService\EssayTask\Essay\FullService as EssayService;
use Edutiek\AssessmentService\System\File\Disposition;
use Edutiek\AssessmentService\System\File\Storage as FileStorage;
use ILIAS\HTTP\StatusCode;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\Handler\UploadHelper;
use ilLongEssayAssessmentUploadHandlerGUI;
use ILIAS\UI\Component\Modal\Modal;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI: ilObjLongEssayAssessmentGUI
 */
class WriterUploadGUI extends BaseGUI
{
    private readonly Permissions $perms;
    private readonly Writer $writer;
    private EssayService $essay_service;
    private TaskService $task_service;
    private FileStorage $file_storage;
    /**
     * @var Task[]
     */
    private array $tasks;
    /**
     * @var Essay[], indexed by task_id
     */
    private array $essays;
    private ilLongEssayAssessmentUploadHandlerGUI $upload_handler;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->essay_service = $this->essay_task_api->essay();
        $this->task_service = $this->task_api->manager();
        $this->file_storage = $this->system_api->fileStorage();

        $this->upload_handler = new ilLongEssayAssessmentUploadHandlerGUI(
            $this->file_storage,
            $this->plugin->dic()->uploadTempFile()
        );
    }

    public function executeCommand(): void
    {
        $this->perms = $this->assessment_api->permissions($this->object->getContextId());
        $this->writer = $this->assessment_api->writer()->getByUserId($this->user->getId());
        $this->tasks = $this->task_service->all();
        $this->essays = $this->essay_service->getByWriterId($this->writer->getId());

        $this->{$this->ctrl->getCmd()}();
    }

    public function reviewPdf(): void
    {
        if (!$this->perms->canWrite() && !$this->perms->canReviewWrittenAssessment()) {
            $this->failure($this->lng->txt('permission_denied'), true);
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
        }

        foreach ($this->tasks as $task) {
            $essay = $this->essays[$task->getId()];

            $modal = $this->uploadModal($task->getId());
            $button = $this->ui_factory->button()->standard('upload', '')
                ->withOnClick($modal->getShowSignal());

            $file_info = $this->file_storage->getFileInfo($essay->getPdfVersion());
            if ($file_info) {
                $this->ctrl->setParameter($this, 'task_id', $task->getId());
                $content = [
                    $this->plugin->dic()->uiFactory()->viewer()->pdf(
                        $this->ctrl->getLinkTarget($this, 'deliverPdf'),
                        $file_info->getFileName()
                    ),
                    $modal, $button->withLabel($this->plugin->txt('writer_replace_pdf'))
                ];
            } else {
                $content = [
                    $this->ui_factory->messageBox()->info($this->plugin->txt('writer_upload_pdf_missing')),
                    $modal, $button->withLabel($this->plugin->txt('writer_upload_pdf'))
                ];
            }

            $this->add($this->ui_factory->panel()->standard($task->getTitle(), $content));
        }

        $this->add(
            $this->ui_factory->button()->primary(
                $this->plugin->txt('writer_authorize_pdf'),
                $this->ctrl->getLinkTarget($this, 'authorizePdf')
            )
        );

        $this->add(
            $this->ui_factory->button()->standard(
                $this->lng->txt('cancel'),
                $this->ctrl->getLinkTargetByClass(WriterStartGUI::class)
            )
        );

        $this->show();
    }

    public function deliverPdf(): void
    {
        $essay = $this->essay_service->oneByWriterIdAndTaskId(
            $this->writer->getId(),
            $this->get->integer('task_id')
        );

        if ($essay?->getPdfVersion()) {
            $this->system_api->fileDelivery()->sendFile($essay->getPdfVersion(), Disposition::ATTACHMENT);
        } else {
            $response = $this->http->response()->withStatus(StatusCode::HTTP_NOT_FOUND);
            $this->http->saveResponse($response);
            $this->http->sendResponse();
            $this->http->close();
        }
    }

    public function authorizePdf(): void
    {
        if (!$this->perms->canWrite()) {
            $this->failure($this->lng->txt('permission_denied'), true);
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
        }

        foreach ($this->essays as $essay) {
            if ($essay->getPdfVersion() === null) {
                $this->failure($this->plugin->txt('pdf_version_not_found'), true);
                $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
            }
        }

        $this->authorizeWriting($this->essays);

        $this->ctrl->setParameterByClass(WriterStartGUI::class, 'returned', '1');
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
    }

    /**
     * @todo: provide a service function for the authorization
     */
    private function authorizeWriting(array $essays): void
    {
        $now = new DateTimeImmutable();

        foreach ($essays as $essay) {
            if (!$essay->getFirstChange()) {
                $essay->setFirstChange($now);
                $this->essay_service->save($essay);
            }
        }

        $this->writer->setWritingAuthorized($now);
        $this->writer->setWritingAuthorizedBy($this->user->getId());

        $this->assessment_api->writer()->save($this->writer);
    }

    private function uploadModal(?int $task_id = null): Modal
    {
        $task = $this->task_service->one($task_id ?? $this->get->integer('task_id'));
        $essay = $this->essays[$task->getId()];

        $fields = [
            'file_id' => $this->ui_factory->input()->field()->file(
                $this->upload_handler,
                $this->plugin->txt('new_file'),
                ''
            )->withAcceptedMimeTypes(['application/pdf'])
        ];

        $this->ctrl->setParameter($this, 'task_id', $task->getId());
        $modal = $this->ui_factory->modal()->roundtrip(
            $task->getTitle(),
            null,
            $fields,
            $this->ctrl->getFormAction($this, 'uploadModal'),
        );

        if ($this->request->getMethod() === 'POST') {
            $modal = $modal->withRequest($this->request);
            $data = $modal->getData() ?? [];

            $uploaded = current($data['file_id'] ?? []);
            if ($uploaded) {
                $existing = $essay->getPdfVersion();
                $stored = $this->file_storage->saveFile(
                    $this->upload_handler->getApiStream($uploaded),
                    $this->upload_handler->getApiInfo($uploaded)
                );
                $essay->setPdfVersion($stored->getId());
                $this->essay_service->save($essay);
                if ($existing) {
                    $this->file_storage->deleteFile($existing);
                }

                // @todo: test and fix
                // $this->essay_task_api->pdfInput()->handleInput($essay);

                $this->tpl->setOnScreenMessage("success", $this->plugin->txt("writer_upload_pdf_finished"), true);
            }

            $this->ctrl->redirect($this, 'reviewPdf');
        }

        return $modal;
    }
}
