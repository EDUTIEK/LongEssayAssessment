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
use Edutiek\AssessmentService\EssayTask\Essay\ClientService as EssayService;
use Edutiek\AssessmentService\System\ConstraintHandling\ResultStatus;
use Edutiek\AssessmentService\System\File\Disposition;
use Edutiek\AssessmentService\System\File\Storage as FileStorage;
use ILIAS\HTTP\StatusCode;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\UI\Component\Modal\Modal;
use ilLongEssayAssessmentUploadHandlerGUI;

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

    public function init()
    {
        $this->essay_service = $this->essay_task_api->essay(false);
        $this->task_service = $this->task_api->manager();
        $this->file_storage = $this->system_api->fileStorage();

        $this->upload_handler = new ilLongEssayAssessmentUploadHandlerGUI(
            $this->file_storage,
            $this->plugin->dic()->uploadTempFile()
        );
        $this->perms = $this->assessment_api->permissions($this->object->getContextId());
        $this->writer = $this->assessment_api->writer()->getByUserId($this->user->getId());
        $this->tasks = $this->task_service->all();
        $this->essays = $this->essay_service->getByWriterId($this->writer->getId());
    }

    public function executeCommand(): void
    {
        $this->init();
        $this->{$this->ctrl->getCmd('preview')}();
    }

    private function return()
    {
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
    }

    public function preview(): void
    {
        if (!$this->perms->canWrite() && !$this->perms->canReviewWrittenAssessment()) {
            $this->failure($this->lng->txt('permission_denied'), true);
            $this->return();
        }

        if ($this->perms->canWrite()) {
            $this->info($this->plugin->txt(count($this->tasks) == 1 ? 'writer_authorize_pdf_info' : 'writer_authorize_pdfs_info'));
        }

        $incomplete_tasks = [];
        foreach ($this->tasks as $task) {
            $essay = $this->essays[$task->getId()];
            $content = [];

            $file_info = $this->file_storage->getFileInfo($essay->getPdfVersion());
            if ($file_info) {
                $this->ctrl->setParameter($this, 'task_id', $task->getId());
                $content[] = $this->plugin->dic()->uiFactory()->viewer()->pdf(
                    $this->ctrl->getLinkTarget($this, 'deliver'),
                    $file_info->getFileName()
                );
            } else {
                $incomplete_tasks[] = $task;
                $content[] = $this->ui_factory->legacy('<p>' . $this->plugin->txt('writer_upload_pdf_missing') . '</p>');
            }
            if ($this->perms->canWrite()) {
                $this->add($modal = $this->upload($task->getId()));
                if ($modal) {
                    $content[] = $this->ui_factory->button()->standard('upload', '')->withOnClick($modal->getShowSignal())->withLabel($this->plugin->txt($file_info ? 'writer_replace_pdf' : 'writer_upload_pdf'));
                }
                if ($file_info) {
                    $essay = $this->essays[$task->getId()];
                    $result = $this->essay_service->canChange($essay);

                    if ($result->status() !== ResultStatus::BLOCK) {
                        $this->ctrl->setParameter($this, 'task_id', $task->getId());
                        $this->add($modal = $this->ui_factory->modal()->interruptive(
                            $this->plugin->txt($task->getTitle()),
                            $this->plugin->txt('confirm_delete_file')
                            . ($result->status() === ResultStatus::ASK ? ' ' . implode(' ', $result->messages()) : ''),
                            $this->ctrl->getFormAction($this, 'delete'),
                        )->withAffectedItems([$this->ui_factory->modal()->interruptiveItem()->standard(
                            $file_info->getId(),
                            $file_info->getFileName(),
                            $this->ui_factory->image()->standard('./assets/images/standard/icon_file.svg', $this->lng->txt('file'))
                        )]));
                        $content[] = $this->ui_factory->button()->standard(
                            $this->plugin->txt('delete_file'),
                            ''
                        )->withOnClick($modal->getShowSignal());
                    }
                }
            }

            $this->add($this->ui_factory->panel()->standard($task->getTitle(), $content));
        }


        $this->add($modal = $this->ui_factory->modal()->interruptive(
            $this->plugin->txt('writer_authorize_pdf'),
            $this->plugin->txt(empty($incomplete_tasks) ? 'writer_authorize_pdf_question' : 'writer_authorize_pdf_question_incomplete'),
            $this->ctrl->getFormAction($this, 'authorize'),
        )->withActionButtonLabel($this->plugin->txt('writer_authorize_pdf'))
            ->withAffectedItems(
                array_map(fn($task) => $this->ui_factory->modal()->interruptiveItem()->standard(
                    (string) $task->getId(),
                    $task->getTitle()
                ), $incomplete_tasks)
            ));

        $this->add($this->ui_factory->button()->primary(
            $this->plugin->txt('writer_authorize_pdf'),
            $this->ctrl->getLinkTarget($this, 'authorize')
        )->withOnClick($modal->getShowSignal()));

        $this->add(
            $this->ui_factory->button()->standard(
                $this->lng->txt('cancel'),
                $this->ctrl->getLinkTargetByClass(WriterStartGUI::class)
            )
        );

        $this->show();
    }

    public function delete(): void
    {
        if (!$this->perms->canWrite()) {
            $this->failure($this->lng->txt('permission_denied'), true);
            $this->ctrl->redirect($this);
        }
        $essay = $this->essay_service->oneByWriterIdAndTaskId(
            $this->writer->getId(),
            $this->get->integer('task_id')
        );
        $result = $this->essay_service->canChange($essay);
        if ($result->status() !== ResultStatus::BLOCK) {
            $this->essay_service->deletePdf($essay);
            $this->success($this->plugin->txt('file_deleted'), true);
        } else {
            $this->failure(implode('<br>', $result->messages()), true);
        }
        $this->ctrl->redirect($this);
    }

    public function deliver(): void
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

    public function authorize(): void
    {
        if (!$this->perms->canWrite()) {
            $this->failure($this->lng->txt('permission_denied'), true);
            $this->return();
        }

        foreach ($this->essays as $essay) {
            if ($essay->getPdfVersion() === null) {
                $this->failure($this->plugin->txt('pdf_version_not_found'), true);
                $this->return();
            }
        }

        $this->authorizeWriting($this->essays);
        $this->ctrl->setParameterByClass(WriterStartGUI::class, 'returned', '1');
        $this->return();
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

    /**
     * Get an upload modal or handle an upload
     * Return null if upload is not possible
     * Redirect if an upload was successfully handled
     */
    private function upload(?int $task_id = null): ?Modal
    {
        $task = $this->task_service->one($task_id ?? $this->get->integer('task_id'));
        $essay = $this->essays[$task->getId()];
        $result = $this->essay_service->canChange($essay);

        $question = null;
        if ($result->status() == ResultStatus::BLOCK) {
            return null;
        } elseif ($result->status() == ResultStatus::ASK) {
            $question = $this->ui_factory->messageBox()->confirmation($this->lng->txt('writer_upload_question') . ' '
                . implode(' ', $result->messages()));
        }

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
            $question,
            $fields,
            $this->ctrl->getFormAction($this, 'upload'),
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
                $this->essay_service->replacePdf($essay, $stored->getId());
                $this->success($this->plugin->txt("writer_upload_pdf_finished"), true);
            }

            $this->ctrl->redirect($this);
        }

        return $modal;
    }
}
