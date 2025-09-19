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

use Closure;
use DateTimeImmutable;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as Permissions;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\Manager as TaskService;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo as Task;
use Edutiek\AssessmentService\EssayTask\Data\Essay;
use Edutiek\AssessmentService\EssayTask\Essay\FullService as EssayService;
use Edutiek\AssessmentService\System\File\Disposition;
use Edutiek\AssessmentService\System\File\Storage as FileStorage;
use Edutiek\AssessmentService\Task\Resource\FullService as ResourceApi;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\HTTP\StatusCode;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\Handler\PreProcessor;
use ILIAS\Plugin\LongEssayAssessment\Handler\Upload;
use ILIAS\Plugin\LongEssayAssessment\Handler\UploadHelper;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo;
use ILIAS\ResourceStorage\Services as ILIASResourceStorage;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI: ilObjLongEssayAssessmentGUI
 */
class WriterUploadGUI extends BaseGUI
{
    private readonly Permissions $perms;
    private readonly Writer $writer;
    private readonly ILIASResourceStorage $storage;
    private readonly UploadHelper $upload;
    private EssayService $essay_service;
    private TaskService $task_service;
    private FileStorage $file_storage;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->perms = $this->assessment_api->permissions($this->object->getContextId());
        $this->writer = $this->assessment_api->writer()->getByUserId($this->user->getId());
        $this->essay_service = $this->essay_task_api->essay();
        $this->task_service = $this->task_api->manager();
        $this->file_storage = $this->system_api->fileStorage();

        $this->storage = $this->dic->resourceStorage();
        $this->upload = new UploadHelper($this->dic);
    }

    public function executeCommand(): void
    {
        $this->{$this->ctrl->getCmd()}();
    }


    public function reviewPdf(): void
    {
        if (!$this->perms->canWrite() && !$this->perms->canReviewWrittenAssessment()) {
            $this->failure($this->lng->txt('permission_denied'), true);
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
        }

        $this->renderContent([
            $this->ui_factory->panel()->standard(
                $this->plugin->txt('writer_review_pdf'),
                $this->mapEssays(function (Essay $essay, Task $task) {
                    $file_info = $this->file_storage->getFileInfo($essay->getPdfVersion());

                    if ($file_info === null) { // Should probably not fail completely if just one resource could not be found.
                        $this->failure($this->plugin->txt('pdf_version_not_found'), true);
                        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
                    }

                    $this->info($this->plugin->txt('writer_authorize_pdf_info'));

                    $this->ctrl->setParameter($this, 'task', $task->getId());

                    return $this->plugin->dic()->uiFactory()->viewer()->pdf(
                        $this->ctrl->getLinkTarget($this, 'deliverPdf'),
                        $file_info->getFileName()
                    );
                }, $this->essays())
            ),
            $this->ui_factory->button()->primary(
                $this->plugin->txt('writer_authorize_pdf'),
                $this->ctrl->getLinkTarget($this, 'authorizePdf')
            ),
            $this->ui_factory->button()->standard(
                $this->lng->txt('cancel'),
                $this->ctrl->getLinkTargetByClass(WriterStartGUI::class)
            )
        ]);
    }

    public function uploadPdf(): void
    {
        if (!$this->perms->canWrite()) {
            $this->failure($this->lng->txt('permission_denied'), true);
            $this->ctrl->redirectByClass(WriterStartGUI::class);
        }

        $essays = $this->essays();
        $entries = array_column($this->mapEssays(fn(Essay $essay, Task $task): array => [
            'key' => $essay->getId(),
            'component' => $this->ui_factory->input()->field()->file(
                new Upload($this->fileInfo(...), $this->linkTo($essay->getPdfVersion())),
                $this->plugin->txt('new_file'),
                '', // @todo: $this->localDI->getUIService()->getMaxFileSizeString()
            )->withAcceptedMimeTypes(['application/pdf'])->withValue(
                $essay->getPdfVersion() !== null ? [$essay->getPdfVersion()] : []
            )
        ], $essays), 'component', 'key');

        $form = $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'uploadPdf', '', true),
            $entries
        )->withSubmitLabel($this->lng->txt('upload'));

        $form = $this->withFormData($form, function (array $data) use ($essays): void {
            foreach ($essays as $essay) {
                $pdf = $essay->getPdfVersion();
                if ($data[$essay->getId()] === []) {
                    $essay->setPdfVersion(null);
                    $this->essay_task_api->essay()->save($essay);
                    $this->file_storage->deleteFile($pdf);
                } else {
                    $essay->setPdfVersion(current($data[$essay->getId()]));
                    $this->essay_task_api->essay()->save($essay);
                    if ($pdf && $essay->getPdfVersion() !== $pdf) {
                        $this->file_storage->deleteFile($pdf);
                    }

                    // @todo: test and fix
                    // $this->essay_task_api->pdfInput()->handleInput($essay);
                }
            }
            $this->ctrl->redirect($this, 'reviewPdf');
        });

        $this->renderContent($form);
    }

    public function upload(): never
    {
        if (!$this->perms->canWrite()) {
            $this->upload->exitWithJson($this->upload->errorJson($this->lng->txt('permission_denied')));
        }

        $file_id = $this->get->string('id', 'null');

        $upload = $this->dic->upload();
        $return_id = null;

        $upload->register(new PreProcessor(function ($stream, $metadata) use ($file_id, &$return_id) {
            $info = (new FileInfo())
                ->setId($file_id)
                ->setMimeType($metadata->getMimeType())
                ->setFileName($metadata->getFilename());
            $return_id = $this->file_storage->saveFile($stream, $info)?->getId();
        }));

        $upload->process();
        $result_array = $upload->getResults();
        if (!((current($result_array) ?: null)?->isOk())) {
            $this->upload->exitWithJson($this->upload->errorJson('No upload'));
        }

        $this->upload->exitWithJson($this->upload->okJson($return_id));
    }

    public function deliverPdf(): void
    {
        // @Todo: Either add access check or add $this->essay_task_api->essay()->one(...) method.

        // $essay = $this->essay_task_api->essay()->one($this->get->integer('essay_id')) ?? $this->notFound();
        // $essay->getPdfVersion() ?? $this->notFound();
        // $task = $this->task_api->manager()->one($essay->getTaskId()) ?? $this->notFound();
        // $resource_api = $this->task_api->resource($task->getId());
        // $resource = $resource_api->one((int) $essay->getPdfVersion()) ?? $this->notFound();

        $essay = $this->essay_task_api->essay()->oneByWriterIdAndTaskId(
            $this->writer->getId(),
            $this->get->integer('task')
        ) ?? $this->sendNotFound();

        if (!$essay->getPdfVersion()) {
            $this->sendNotFound();
        }
        $this->system_api->fileDelivery()->sendFile($essay->getPdfVersion(), Disposition::ATTACHMENT);
    }

    public function authorizePdf(): void
    {
        // if (!$this->perms->canWrite() && !$this->perms->canReviewWrittenAssessment()) {
        //     $this->failure($this->lng->txt('permission_denied'), true);
        //     $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
        // }

        $essays = $this->essays();
        foreach ($essays as $essay) {
            if ($essay->getPdfVersion() === null) {
                $this->failure($this->plugin->txt('pdf_version_not_found'), true);
                $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
            }
        }

        $this->authorizeWriting($essays);

        $this->ctrl->setParameterByClass(WriterStartGUI::class, 'returned', '1');
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
    }

    private function mapEssays(callable $proc, array $essays): array
    {
        return array_map(function (Essay $essay) use ($proc) {
            $task = $this->task_api->manager()->one($essay->getTaskId());
            return $proc($essay, $task);
        }, $essays);
    }

    private function essays(): array
    {
        return $this->essay_task_api->essay()->getByWriterId($this->writer->getId());
    }

    private function linkTo(?string $id): Closure
    {
        return function (string $cmd) use ($id): string {
            if (!empty($id)) {
                $this->ctrl->setParameter($this, 'id', $id);
            }
            return $this->ctrl->getLinkTarget($this, $cmd);
        };
    }

    private function sendNotFound(): void
    {
        $response = $this->http->response()->withStatus(StatusCode::HTTP_NOT_FOUND);
        $this->http->saveResponse($response);
        $this->http->sendResponse();
        $this->http->close();
    }

    private function authorizeWriting(array $essays): void
    {
        $now = new DateTimeImmutable();

        foreach ($essays as $essay) {
            if (!$essay->getFirstChange()) {
                $essay->setFirstChange($now);
                $this->essay_task_api->essay()->save($essay);
            }
        }

        $this->writer->setWritingAuthorized($now);
        $this->writer->setWritingAuthorizedBy($this->user->getId());

        $this->assessment_api->writer()->save($this->writer);

        // $writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
        // $writer = $writer_repo->getWriterById($essay->getWriterId());
        // $this->loggingService->addEntry(LogEntry::TYPE_WRITING_POST_AUTHORIZED, $user_id, $writer->getUserId());
    }

    private function fileInfo(string $file_id): ?FileInfoResult
    {
        $info = $this->file_storage->getFileInfo($file_id);
        if (!$info) {
            return null;
        }
        return new BasicFileInfoResult(
            $info->getId(),
            $info->getId(),
            $info->getFileName(),
            $info->getSize(),
            $info->getMimeType()
        );
    }
}
