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

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as Permissions;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\EssayTask\Data\Essay;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo as Task;
use Edutiek\AssessmentService\Task\Resource\FullService as ResourceApi;
use ILIAS\Data\DataSize;
use ILIAS\Plugin\LongEssayAssessment\Handler\Upload;
use ILIAS\Plugin\LongEssayAssessment\Handler\PreProcessor;
use Edutiek\AssessmentService\System\File\Disposition;
use ILIAS\HTTP\StatusCode;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\Plugin\LongEssayAssessment\EssayTask\Data\Essay as TheEssay;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskType;
use Exception;
use ILIAS\ResourceStorage\Services as ILIASResourceStorage;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo;
use Closure;
use DateTimeImmutable;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI: ilObjLongEssayAssessmentGUI
 */
class WriterUploadGUI extends BaseGUI
{
    private readonly Permissions $perms;
    private readonly Writer $writer;
    private readonly ILIASResourceStorage $storage;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->perms = $this->assessment_api->permissions($this->object->getId());
        $this->writer = $this->assessment_api->writer()->getByUserId($this->user->getId());
        $this->storage = $this->dic->resourceStorage();
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
                $this->mapEssays(function (Essay $essay, Task $task, ResourceApi $resource_api) {
                    $resource = $essay->getPdfVersion() !== null ?
                        $resource_api->one((int) $essay->getPdfVersion()) :
                        null;

                    if ($resource === null) { // Should probably not fail completely if just one resource could not be found.
                        $this->failure($this->plugin->txt('pdf_version_not_found'), true);
                        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
                    }

                    $this->info($this->plugin->txt('writer_authorize_pdf_info'));

                    $this->ctrl->setParameter($this, 'task', $task->getId());
                    $this->ctrl->setParameter($this, 'resource', $resource->getId());

                    return $this->plugin->dic()->uiFactory()->viewer()->pdf(
                        $this->ctrl->getLinkTarget($this, 'deliverPdf'),
                        $resource->getTitle()
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
        $entries = array_column($this->mapEssays(fn (Essay $essay, Task $task, ResourceApi $resource_api): array => [
            'key' => $essay->getId(),
            'component' => $this->ui_factory->input()->field()->file(
                new Upload($this->storage, $this->task_api, $this->linkTo($task->getId() . ':' . $essay->getPdfVersion())),
                $this->plugin->txt('new_file'),
                '', // @todo: $this->localDI->getUIService()->getMaxFileSizeString()
            )->withAcceptedMimeTypes(['application/pdf'])->withValue(
                $essay->getPdfVersion() !== null ? [$task->getId() . ':' . $essay->getPdfVersion()] : []
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
                    $this->task_api->resource($essay->getTaskId())->delete($pdf);
                } else {
                    $essay->setPdfVersion(current($data[$essay->getId()]));
                    $this->essay_task_api->essay()->save($essay);
                    if ($pdf && $essay->getPdfVersion() !== $pdf) {
                        $this->task_api->resource($essay->getTaskId())->delete($pdf);
                    }
                    $this->essay_task_api->pdfInput()->handleInput($essay);
                }
            }
            $this->ctrl->redirect($this, 'reviewPdf');
        });

        $this->renderContent($form);
    }

    public function upload(): never
    {
        if (!$this->perms->canWrite()) {
            $this->exitWithJson($this->errorJson($this->lng->txt('permission_denied')));
        }

        [$task_id, $resource_id] = array_map('intval', explode(':', $this->get->string('id')));

        $upload = $this->dic->upload();
        $return_id = null;
        $upload->register(new PreProcessor(function ($stream, $metadata) use ($task_id, $resource_id, &$return_id) {
            $resource_api = $this->task_api->resource($task_id);
            $info = (new FileInfo())
                ->setMimeType($metadata->getMimeType())
                ->setFileName($metadata->getFilename());
            if ($resource_id) {
                $resource = $resource_api->one($resource_id);
                $return_id = $resource->getId();
                $info->setId($resource->getFileId());
                $this->plugin->dic()->system()->fileStorage()->saveFile($stream, $info);
            } else {
                $resource = $resource_api->new();
                $resource->setFileId($this->plugin->dic()->system()->fileStorage()->saveFile($stream, $info)->getId());
                $resource_api->save($resource);
                $return_id = $resource->getId();
            }
            return null;
        }));
        $upload->process();
        $result_array = $upload->getResults();
        if (!((current($result_array) ?: null)?->isOk())) {
            $this->exitWithJson($this->errorJson('No upload'));
        }

        $this->exitWithJson($this->okJson($return_id));
    }

    public function deliverPdf(): void
    {
        // @Todo: Either add access check or add $this->essay_task_api->essay()->one(...) method.

        // $essay = $this->essay_task_api->essay()->one($this->get->integer('essay_id')) ?? $this->notFound();
        // $essay->getPdfVersion() ?? $this->notFound();
        // $task = $this->task_api->manager()->one($essay->getTaskId()) ?? $this->notFound();
        // $resource_api = $this->task_api->resource($task->getId());
        // $resource = $resource_api->one((int) $essay->getPdfVersion()) ?? $this->notFound();
        $task = $this->task_api->manager()->one($this->get->integer('task') ?? $this->notFound()) ?? $this->notFound();
        $resource_api = $this->task_api->resource($task->getId());
        $resource = $resource_api->one($this->get->integer('resource') ?? $this->notFound()) ?? $this->notFound();
        $this->system_api->fileDelivery()->sendFile($resource->getFileId(), Disposition::ATTACHMENT);
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
            return $proc($essay, $task, $this->task_api->resource($task->getId()));
        }, $essays);
    }

    private function essays(): array
    {
        // return [
        //     (new TheEssay())->setId(3)->setPdfVersion('29'),
        //     (new TheEssay())->setId(4)->setPdfVersion('29'),
        // ];

        return $this->essay_task_api->essay()->allByWriterId($this->writer->getId());
    }

    private function linkTo(string $id): Closure
    {
        return function (string $cmd) use ($id): string {
            $this->ctrl->setParameter($this, 'id', $id);
            return $this->ctrl->getLinkTarget($this, $cmd);
        };
    }

    private function notFound(): never
    {
        $response = $this->http->response()->withStatus(StatusCode::HTTP_NOT_FOUND);
        $this->http->saveResponse($response);
        $this->http->sendResponse();
        $this->http->close();
    }

    private function exitWithJson($value): never
    {
        // ... The content type cannot be set to application/json, because the components/ILIAS/UI/src/templates/js/Input/Field/file.js:392
        //     does not expect that the content type is correct and parses it again ...
        $this->dic->http()->saveResponse($this->dic->http()->response()/* ->withHeader('Content-Type', 'application/json') */->withBody(
            Streams::ofString(json_encode($value))
        ));

        $this->dic->http()->sendResponse();
        $this->dic->http()->close();
    }

    private function okJson(int $file_id): array
    {
        return ['status' => 1, 'file_id' => $file_id];
    }

    private function errorJson(string $message): array
    {
        return ['status' => 'error', 'message' => $message];
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
}
